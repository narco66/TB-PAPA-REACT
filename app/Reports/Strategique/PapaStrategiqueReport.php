<?php

namespace App\Reports\Strategique;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Indicateur;
use App\Models\Papa;
use App\Models\Tache;
use App\Reports\Report;

class PapaStrategiqueReport extends Report
{
    public function key(): string
    {
        return 'papa_strategique';
    }

    public function titre(): string
    {
        return 'Plan d\'Action Prioritaire Annuel — Vue stratégique';
    }

    public function description(): string
    {
        return 'Document institutionnel complet du PAPA : avant-propos, cadre logique RBM/GAR par axe, indicateurs CMR, budgétisation CEEAC/PTF, calendrier consolidé, gouvernance et annexes méthodologiques.';
    }

    public function categorie(): string
    {
        return self::CAT_STRATEGIQUE;
    }

    public function template(): string
    {
        return 'reports.strategique.papa';
    }

    public function icone(): string
    {
        return 'ClipboardList';
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'papa_id',
                'label' => 'PAPA',
                'type' => 'select',
                'required' => true,
                'options' => Papa::orderByDesc('annee')->get(['id', 'annee', 'libelle'])
                    ->map(fn ($p) => ['value' => $p->id, 'label' => "{$p->annee} — {$p->libelle}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $papaId = $filtres['papa_id'] ?? Papa::actif()->orderByDesc('annee')->value('id');
        $papa = Papa::with(['valideur:id,name,fonction', 'createur:id,name,fonction'])->findOrFail($papaId);

        $axes = Axe::where('papa_id', $papa->id)
            ->with([
                'departement:id,code,libelle',
                'responsable:id,name,fonction',
                'produits' => fn ($q) => $q->orderBy('ordre'),
                'produits.sousProduits' => fn ($q) => $q->orderBy('ordre'),
                'produits.sousProduits.indicateurs',
            ])
            ->orderBy('ordre')
            ->get();

        $stats = $this->stats($papa, $axes);
        $budgetParAxe = $this->budgetParAxe($papa);
        $indicateursParAxe = $this->indicateursParAxe($papa);
        $calendrier = $this->calendrierConsolide($papa->id);
        $gouvernance = $this->gouvernance($papa);

        return compact('papa', 'axes', 'stats', 'budgetParAxe', 'indicateursParAxe', 'calendrier', 'gouvernance');
    }

    protected function stats(Papa $papa, $axes): array
    {
        $papaId = $papa->id;

        return [
            'nb_axes' => $axes->count(),
            'nb_produits' => $axes->sum(fn ($a) => $a->produits->count()),
            'nb_sous_produits' => $axes->sum(fn ($a) => $a->produits->sum(fn ($p) => $p->sousProduits->count())),
            'nb_activites' => Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'nb_taches' => Tache::whereHas('activite.sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'nb_indicateurs' => Indicateur::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'taux_execution_global' => $papa->tauxExecutionPhysique(),
            'taux_execution_financier' => $papa->tauxExecutionFinancier(),
        ];
    }

    protected function budgetParAxe(Papa $papa): array
    {
        $exercice = BudgetExercice::where('annee', $papa->annee)->first();
        if (! $exercice) {
            return [];
        }

        return Axe::where('papa_id', $papa->id)
            ->orderBy('ordre')
            ->get(['id', 'code', 'libelle'])
            ->map(function ($axe) use ($exercice) {
                $sum = BudgetLigne::where('exercice_id', $exercice->id)
                    ->where('nature', 'depense')
                    ->where('axe_id', $axe->id)
                    ->selectRaw('SUM(montant_total) as total, SUM(montant_ceeac_em) as ceeac, SUM(montant_ptf) as ptf, SUM(montant_engage) as engage, SUM(montant_paye) as paye, COUNT(*) as nb')
                    ->first();
                $total = (float) ($sum->total ?? 0);

                return [
                    'code' => $axe->code,
                    'libelle' => $axe->libelle,
                    'nb_lignes' => (int) ($sum->nb ?? 0),
                    'total' => $total,
                    'ceeac' => (float) ($sum->ceeac ?? 0),
                    'ptf' => (float) ($sum->ptf ?? 0),
                    'engage' => (float) ($sum->engage ?? 0),
                    'paye' => (float) ($sum->paye ?? 0),
                    'taux_engagement' => $total > 0 ? round((float) $sum->engage / $total * 100, 1) : 0,
                ];
            })
            ->filter(fn ($a) => $a['nb_lignes'] > 0)
            ->values()
            ->all();
    }

    protected function indicateursParAxe(Papa $papa): array
    {
        return Axe::where('papa_id', $papa->id)
            ->orderBy('ordre')
            ->get(['id', 'code', 'libelle'])
            ->map(function ($axe) {
                $base = Indicateur::whereHas('sousProduit.produit', fn ($q) => $q->where('axe_id', $axe->id));

                return [
                    'code' => $axe->code,
                    'libelle' => $axe->libelle,
                    'nb' => (clone $base)->count(),
                    'taux_moyen' => round((float) (clone $base)->avg('taux_realisation'), 1),
                    'atteints' => (clone $base)->where('taux_realisation', '>=', 100)->count(),
                    'a_risque' => (clone $base)->where('taux_realisation', '<', 40)->count(),
                ];
            })
            ->filter(fn ($a) => $a['nb'] > 0)
            ->values()
            ->all();
    }

    protected function calendrierConsolide(int $papaId): array
    {
        $base = Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));

        return [
            'date_debut_min' => (clone $base)->min('date_debut'),
            'date_fin_max' => (clone $base)->max('date_fin'),
            'jalons_par_trimestre' => collect(range(1, 4))->map(function ($t) use ($base) {
                $year = now()->year;
                $debutT = sprintf('%d-%02d-01', $year, ($t - 1) * 3 + 1);
                $finT = match ($t) {
                    1 => "$year-03-31", 2 => "$year-06-30", 3 => "$year-09-30", default => "$year-12-31",
                };

                return [
                    'trimestre' => "T{$t}",
                    'activites' => (clone $base)
                        ->whereBetween('date_fin', [$debutT, $finT])
                        ->count(),
                    'realisees' => (clone $base)
                        ->whereBetween('date_fin', [$debutT, $finT])
                        ->where('statut', 'realisee')
                        ->count(),
                ];
            })->all(),
        ];
    }

    protected function gouvernance(Papa $papa): array
    {
        return [
            'createur' => $papa->createur?->name,
            'createur_fonction' => $papa->createur?->fonction,
            'valideur' => $papa->valideur?->name,
            'valideur_fonction' => $papa->valideur?->fonction,
            'date_validation' => $papa->date_validation,
            'verrouille' => $papa->verrouille,
            'periode' => [
                'debut' => $papa->date_debut,
                'fin' => $papa->date_fin,
            ],
        ];
    }
}
