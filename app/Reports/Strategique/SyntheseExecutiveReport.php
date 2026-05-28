<?php

namespace App\Reports\Strategique;

use App\Models\Activite;
use App\Models\Alerte;
use App\Models\Audit\AuditMission;
use App\Models\Audit\AuditRecommandation;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Indicateur;
use App\Models\Papa;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Models\Validation;
use App\Reports\Report;

class SyntheseExecutiveReport extends Report
{
    public function key(): string
    {
        return 'synthese_executive';
    }

    public function titre(): string
    {
        return 'Synthèse exécutive — Cabinet de la Présidence';
    }

    public function description(): string
    {
        return 'Note de synthèse stratégique restreinte : KPI consolidés, axes performants/en retard, indicateurs CMR, alertes critiques, audit interne, décisions présidentielles requises.';
    }

    public function categorie(): string
    {
        return self::CAT_STRATEGIQUE;
    }

    public function template(): string
    {
        return 'reports.strategique.synthese';
    }

    public function icone(): string
    {
        return 'TrendingUp';
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
                'options' => Papa::orderByDesc('annee')->get(['id', 'annee'])
                    ->map(fn ($p) => ['value' => $p->id, 'label' => "PAPA {$p->annee}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $papaId = $filtres['papa_id'] ?? Papa::actif()->orderByDesc('annee')->value('id');
        $papa = Papa::with(['valideur:id,name,fonction', 'createur:id,name,fonction'])->findOrFail($papaId);

        $stats = $this->stats($papaId, $papa);
        $execution = $this->execution($papaId);
        $budget = $this->budget($papa->annee);
        $topAxes = $this->topAxes($papaId);
        $axesEnRetard = $this->axesEnRetard($papaId);
        $indicateursCmr = $this->indicateursCmr($papaId);
        $alertesCritiques = $this->alertesCritiques();
        $cartoRisques = $this->cartoRisques();
        $audit = $this->audit();
        $validations = $this->validationsEnAttente();
        $decisions = $this->decisionsRequises($stats, $execution, $alertesCritiques, $audit, $validations);
        $recommandations = $this->recommandations($stats, $execution, $budget, $alertesCritiques, $axesEnRetard);

        return compact(
            'papa', 'stats', 'execution', 'budget', 'topAxes', 'axesEnRetard',
            'indicateursCmr', 'alertesCritiques', 'cartoRisques', 'audit',
            'validations', 'decisions', 'recommandations',
        );
    }

    protected function stats(int $papaId, Papa $papa): array
    {
        return [
            'axes' => Axe::where('papa_id', $papaId)->count(),
            'produits' => Produit::whereHas('axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'sous_produits' => SousProduit::whereHas('produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'activites' => Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'taches' => Tache::whereHas('activite.sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'indicateurs' => Indicateur::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'taux_global' => $papa->tauxExecutionPhysique(),
            'taux_financier' => $papa->tauxExecutionFinancier(),
        ];
    }

    protected function execution(int $papaId): array
    {
        $base = Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));

        return [
            'activites_realisees' => (clone $base)->where('statut', 'realisee')->count(),
            'activites_en_cours' => (clone $base)->where('statut', 'en_cours')->count(),
            'activites_planifiees' => (clone $base)->where('statut', 'planifiee')->count(),
            'activites_suspendues' => (clone $base)->where('statut', 'suspendue')->count(),
            'activites_retard' => (clone $base)->whereDate('date_fin', '<', now())
                ->whereNotIn('statut', ['realisee', 'annulee'])
                ->where('taux_execution', '<', 100)->count(),
        ];
    }

    protected function budget(int $annee): ?array
    {
        $exercice = BudgetExercice::where('annee', $annee)->first();
        if (! $exercice) {
            return null;
        }

        $sum = BudgetLigne::where('exercice_id', $exercice->id)
            ->where('nature', 'depense')
            ->selectRaw('SUM(montant_total) as total, SUM(montant_ceeac_em) as ceeac, SUM(montant_ptf) as ptf, SUM(montant_engage) as engage, SUM(montant_paye) as paye')
            ->first();

        $total = (float) $sum->total;

        return [
            'devise' => $exercice->devise ?: 'XAF',
            'total' => $total,
            'ceeac' => (float) $sum->ceeac,
            'ptf' => (float) $sum->ptf,
            'engage' => (float) $sum->engage,
            'paye' => (float) $sum->paye,
            'taux_engagement' => $total > 0 ? round($sum->engage / $total * 100, 1) : 0,
            'taux_paiement' => $total > 0 ? round($sum->paye / $total * 100, 1) : 0,
            'taux_ceeac' => $total > 0 ? round($sum->ceeac / $total * 100, 1) : 0,
            'taux_ptf' => $total > 0 ? round($sum->ptf / $total * 100, 1) : 0,
        ];
    }

    protected function topAxes(int $papaId)
    {
        return Axe::where('papa_id', $papaId)
            ->with('departement:id,code,libelle')
            ->orderByDesc('taux_execution')
            ->limit(5)
            ->get();
    }

    protected function axesEnRetard(int $papaId)
    {
        return Axe::where('papa_id', $papaId)
            ->where('taux_execution', '<', 40)
            ->with('departement:id,code,libelle')
            ->orderBy('taux_execution')
            ->limit(5)
            ->get();
    }

    protected function indicateursCmr(int $papaId): array
    {
        $base = Indicateur::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));

        return [
            'total' => (clone $base)->count(),
            'atteints' => (clone $base)->where('taux_realisation', '>=', 100)->count(),
            'a_risque' => (clone $base)->where('taux_realisation', '<', 40)->count(),
            'taux_moyen' => round((float) (clone $base)->avg('taux_realisation'), 1),
            'par_categorie' => (clone $base)->selectRaw('categorie, COUNT(*) as nb, AVG(taux_realisation) as taux')
                ->groupBy('categorie')
                ->get()
                ->map(fn ($r) => [
                    'categorie' => $r->categorie ?: '—',
                    'nb' => (int) $r->nb,
                    'taux' => round((float) $r->taux, 1),
                ])->all(),
        ];
    }

    protected function alertesCritiques()
    {
        return Alerte::ouvertes()->critiques()
            ->with('assignee:id,name')
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    protected function cartoRisques(): array
    {
        $niveaux = ['critique', 'attention', 'info'];
        $carto = [];

        foreach ($niveaux as $n) {
            $carto[$n] = Alerte::ouvertes()->where('niveau', $n)->count();
        }

        return $carto;
    }

    protected function audit(): array
    {
        return [
            'missions_en_cours' => AuditMission::where('statut', 'en_cours')->count(),
            'missions_cloturees_30j' => AuditMission::where('statut', 'cloturee')
                ->where('updated_at', '>=', now()->subDays(30))->count(),
            'recommandations_ouvertes' => AuditRecommandation::whereNotIn('statut', ['verifiee', 'rejetee', 'abandonnee'])->count(),
            'recommandations_en_retard' => AuditRecommandation::whereDate('date_echeance', '<', now())
                ->whereNotIn('statut', ['verifiee', 'rejetee', 'abandonnee'])->count(),
        ];
    }

    protected function validationsEnAttente(): array
    {
        return [
            'total' => Validation::where('decision', 'en_attente')->count(),
            'recentes' => Validation::where('decision', 'en_attente')
                ->latest('id')->limit(5)
                ->get()
                ->map(fn ($v) => [
                    'etape' => $v->etape,
                    'type' => class_basename($v->validable_type ?? ''),
                    'depuis_jours' => $v->created_at?->diffInDays(now()) ?? 0,
                ])->all(),
        ];
    }

    protected function decisionsRequises(array $stats, array $execution, $alertes, array $audit, array $validations): array
    {
        $decisions = [];

        if ($stats['taux_global'] < 40) {
            $decisions[] = [
                'titre' => 'Convocation du Comité de pilotage stratégique',
                'detail' => "Le taux d'exécution physique est de {$stats['taux_global']}%. Une revue présidentielle est requise.",
                'urgence' => 'critique',
            ];
        }

        if ($alertes->count() > 0) {
            $decisions[] = [
                'titre' => "Validation du plan d'action de traitement des alertes critiques",
                'detail' => "{$alertes->count()} alerte(s) critique(s) en cours.",
                'urgence' => 'haute',
            ];
        }

        if ($execution['activites_retard'] > 15) {
            $decisions[] = [
                'titre' => 'Rapport circonstancié des Commissaires',
                'detail' => "{$execution['activites_retard']} activités en retard. Demander un état des lieux par département.",
                'urgence' => 'haute',
            ];
        }

        if ($audit['recommandations_en_retard'] > 0) {
            $decisions[] = [
                'titre' => 'Arbitrage sur les recommandations audit en retard',
                'detail' => "{$audit['recommandations_en_retard']} recommandation(s) en retard sur leur échéance.",
                'urgence' => 'moyenne',
            ];
        }

        if ($validations['total'] > 0) {
            $decisions[] = [
                'titre' => 'Traitement de la file des validations en attente',
                'detail' => "{$validations['total']} validation(s) en attente d'arbitrage.",
                'urgence' => 'moyenne',
            ];
        }

        return $decisions;
    }

    protected function recommandations(array $stats, array $execution, ?array $budget, $alertes, $axesEnRetard): array
    {
        $recos = [];

        if ($stats['taux_global'] < 50) {
            $recos[] = "Convoquer un Comité de pilotage stratégique pour relancer les axes en retard ({$axesEnRetard->count()} axes < 40 %).";
        }

        if ($alertes->count() > 0) {
            $recos[] = "Assigner les {$alertes->count()} alerte(s) critique(s) à un responsable et exiger un plan d'action sous 7 jours.";
        }

        if ($axesEnRetard->count() > 0) {
            $recos[] = "Demander un rapport circonstancié aux Commissaires responsables des {$axesEnRetard->count()} axe(s) sous-performant(s).";
        }

        if ($budget && abs($budget['taux_paiement'] - $stats['taux_global']) > 15) {
            $recos[] = 'Audit budgétaire : écart de ' . round(abs($budget['taux_paiement'] - $stats['taux_global']), 1) . " pts entre exécution physique ({$stats['taux_global']}%) et taux de paiement ({$budget['taux_paiement']}%).";
        }

        if ($execution['activites_retard'] > 10) {
            $recos[] = "{$execution['activites_retard']} activités en retard — élaborer un plan de rattrapage par département.";
        }

        $recos[] = 'Maintenir le rythme de revue trimestrielle des indicateurs et du tableau de bord exécutif.';

        return $recos;
    }
}
