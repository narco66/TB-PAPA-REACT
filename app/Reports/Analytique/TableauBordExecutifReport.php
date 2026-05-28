<?php

namespace App\Reports\Analytique;

use App\Models\Activite;
use App\Models\Alerte;
use App\Models\Audit\AuditMission;
use App\Models\Audit\AuditRecommandation;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetMouvement;
use App\Models\Departement;
use App\Models\Indicateur;
use App\Models\Papa;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Reports\Report;
use Carbon\Carbon;

class TableauBordExecutifReport extends Report
{
    public function key(): string
    {
        return 'tableau_bord_executif';
    }

    public function titre(): string
    {
        return 'Tableau de bord exécutif PDF';
    }

    public function description(): string
    {
        return 'Export PDF du tableau de bord exécutif institutionnel : KPI consolidés, RBM/GAR, cycle budgétaire IPSAS, performance départements, alertes, audit interne, recommandations.';
    }

    public function categorie(): string
    {
        return self::CAT_ANALYTIQUE;
    }

    public function template(): string
    {
        return 'reports.analytique.tableau_bord';
    }

    public function icone(): string
    {
        return 'LayoutDashboard';
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
                'options' => Papa::orderByDesc('annee')->get(['id', 'annee'])
                    ->map(fn ($p) => ['value' => $p->id, 'label' => "PAPA {$p->annee}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $papa = isset($filtres['papa_id']) && $filtres['papa_id']
            ? Papa::find($filtres['papa_id'])
            : Papa::actif()->orderByDesc('annee')->first();

        $papaId = $papa?->id;

        $stats = $this->statsRbm($papaId, $papa);
        $execution = $this->indicateursExecution($papaId);
        $exercice = $papa ? BudgetExercice::where('annee', $papa->annee)->first() : BudgetExercice::orderByDesc('annee')->first();
        $budget = $this->budget($exercice);
        $cycleIpsas = $this->cycleIpsas($exercice);
        $sourceFinancement = $this->repartitionSourceFinancement($exercice);
        $tendance = $this->tendanceMensuelle($papaId, $papa);
        $topAxes = $this->topAxes($papaId);
        $axesEnRetard = $this->axesEnRetard($papaId);
        $departements = $this->performanceDepartements($papaId);
        $alertes = $this->alertes();
        $indicateursTypologie = $this->indicateursParTypologie($papaId);
        $audit = $this->auditInterne();
        $imports = $this->qualiteImports($exercice);
        $verdict = $this->verdict($stats, $execution, $budget, $alertes, $audit);
        $recommandations = $this->recommandations($stats, $execution, $budget, $alertes, $audit);

        return compact(
            'papa', 'stats', 'execution', 'budget', 'cycleIpsas', 'sourceFinancement',
            'tendance', 'topAxes', 'axesEnRetard', 'departements', 'alertes',
            'indicateursTypologie', 'audit', 'imports', 'verdict', 'recommandations',
        );
    }

    protected function statsRbm(?int $papaId, ?Papa $papa): array
    {
        return [
            'axes' => $papaId ? Axe::where('papa_id', $papaId)->count() : 0,
            'produits' => $papaId ? Produit::whereHas('axe', fn ($q) => $q->where('papa_id', $papaId))->count() : 0,
            'sous_produits' => $papaId ? SousProduit::whereHas('produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count() : 0,
            'activites' => $papaId ? Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count() : 0,
            'taches' => $papaId ? Tache::whereHas('activite.sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count() : 0,
            'indicateurs' => $papaId ? Indicateur::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count() : 0,
            'taux_global' => $papa?->tauxExecutionPhysique() ?? 0,
            'taux_financier' => $papa?->tauxExecutionFinancier() ?? 0,
        ];
    }

    protected function indicateursExecution(?int $papaId): array
    {
        if (! $papaId) {
            return ['activites_retard' => 0, 'activites_realisees' => 0, 'activites_en_cours' => 0, 'taches_realisees' => 0, 'taches_total' => 0, 'taux_taches' => 0];
        }
        $base = Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));

        $tachesBase = Tache::whereHas('activite.sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));
        $tachesTotal = (clone $tachesBase)->count();
        $tachesRealisees = (clone $tachesBase)->where('statut', 'realisee')->count();

        return [
            'activites_retard' => (clone $base)->whereDate('date_fin', '<', now())
                ->whereNotIn('statut', ['realisee', 'annulee'])
                ->where('taux_execution', '<', 100)
                ->count(),
            'activites_realisees' => (clone $base)->where('statut', 'realisee')->count(),
            'activites_en_cours' => (clone $base)->where('statut', 'en_cours')->count(),
            'taches_realisees' => $tachesRealisees,
            'taches_total' => $tachesTotal,
            'taux_taches' => $tachesTotal > 0 ? round($tachesRealisees / $tachesTotal * 100, 1) : 0,
        ];
    }

    protected function budget(?BudgetExercice $exercice): ?array
    {
        if (! $exercice) {
            return null;
        }

        $sum = BudgetLigne::where('exercice_id', $exercice->id)
            ->where('nature', 'depense')
            ->selectRaw('
                SUM(montant_total) as total,
                SUM(montant_ceeac_em) as ceeac_em,
                SUM(montant_ptf) as ptf,
                SUM(montant_engage) as engage,
                SUM(montant_liquide) as liquide,
                SUM(montant_ordonnance) as ordonnance,
                SUM(montant_paye) as paye,
                SUM(montant_disponible) as disponible,
                SUM(budget_annee_precedente) as ann_prec,
                SUM(realisation_annee_precedente) as real_prec
            ')->first();

        $total = (float) $sum->total;

        return [
            'exercice_annee' => $exercice->annee,
            'exercice_statut' => $exercice->statut,
            'devise' => $exercice->devise ?: 'XAF',
            'total' => $total,
            'ceeac_em' => (float) $sum->ceeac_em,
            'ptf' => (float) $sum->ptf,
            'engage' => (float) $sum->engage,
            'liquide' => (float) $sum->liquide,
            'ordonnance' => (float) $sum->ordonnance,
            'paye' => (float) $sum->paye,
            'disponible' => (float) $sum->disponible,
            'ann_prec' => (float) $sum->ann_prec,
            'real_prec' => (float) $sum->real_prec,
            'taux_ceeac' => $total > 0 ? round($sum->ceeac_em / $total * 100, 1) : 0,
            'taux_ptf' => $total > 0 ? round($sum->ptf / $total * 100, 1) : 0,
            'taux_engagement' => $total > 0 ? round($sum->engage / $total * 100, 1) : 0,
            'taux_paiement' => $total > 0 ? round($sum->paye / $total * 100, 1) : 0,
            'variation_n_n1' => (float) $sum->ann_prec > 0
                ? round(($total - (float) $sum->ann_prec) / (float) $sum->ann_prec * 100, 1)
                : 0,
        ];
    }

    protected function cycleIpsas(?BudgetExercice $exercice): array
    {
        if (! $exercice) {
            return [];
        }

        return BudgetMouvement::whereHas('ligne', fn ($q) => $q->where('exercice_id', $exercice->id))
            ->selectRaw('type, COUNT(*) as nombre, SUM(montant) as montant')
            ->groupBy('type')
            ->get()
            ->map(fn ($r) => [
                'type' => $r->type,
                'nombre' => (int) $r->nombre,
                'montant' => (float) $r->montant,
            ])
            ->all();
    }

    protected function repartitionSourceFinancement(?BudgetExercice $exercice): array
    {
        if (! $exercice) {
            return [];
        }

        return BudgetLigne::where('exercice_id', $exercice->id)
            ->where('nature', 'depense')
            ->whereNotNull('source_financement_id')
            ->with('source:id,code,libelle,type')
            ->selectRaw('source_financement_id, SUM(montant_total) as total')
            ->groupBy('source_financement_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'code' => $r->source?->code ?? '—',
                'libelle' => $r->source?->libelle ?? '—',
                'type' => $r->source?->type ?? '—',
                'montant' => (float) $r->total,
            ])
            ->all();
    }

    protected function tendanceMensuelle(?int $papaId, ?Papa $papa): array
    {
        if (! $papaId) {
            return [];
        }

        $debut = Carbon::parse($papa?->date_debut ?? now()->startOfYear())->startOfMonth();
        $fin = Carbon::parse($papa?->date_fin ?? now()->endOfYear())->endOfMonth();

        $mois = [];
        $curseur = $debut->copy();
        while ($curseur <= $fin && count($mois) < 12) {
            $finMois = $curseur->copy()->endOfMonth();
            $cumul = Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))
                ->where('statut', 'realisee')
                ->whereDate('date_fin_reelle', '<=', $finMois)
                ->count();
            $mois[] = [
                'mois' => $curseur->isoFormat('MMM YY'),
                'realisees' => $cumul,
            ];
            $curseur->addMonth();
        }

        return $mois;
    }

    protected function topAxes(?int $papaId): array
    {
        if (! $papaId) {
            return [];
        }

        return Axe::where('papa_id', $papaId)
            ->with('departement:id,code,libelle')
            ->orderByDesc('taux_execution')
            ->limit(5)
            ->get(['id', 'code', 'libelle', 'taux_execution', 'departement_id'])
            ->map(fn ($a) => [
                'code' => $a->code,
                'libelle' => $a->libelle,
                'taux' => round((float) $a->taux_execution, 1),
                'departement' => $a->departement?->code ?? '—',
            ])->all();
    }

    protected function axesEnRetard(?int $papaId): array
    {
        if (! $papaId) {
            return [];
        }

        return Axe::where('papa_id', $papaId)
            ->where('taux_execution', '<', 40)
            ->with('departement:id,code,libelle')
            ->orderBy('taux_execution')
            ->limit(5)
            ->get(['id', 'code', 'libelle', 'taux_execution', 'departement_id'])
            ->map(fn ($a) => [
                'code' => $a->code,
                'libelle' => $a->libelle,
                'taux' => round((float) $a->taux_execution, 1),
                'departement' => $a->departement?->code ?? '—',
            ])->all();
    }

    protected function performanceDepartements(?int $papaId): array
    {
        return Departement::with('commissaire:id,name,fonction')
            ->withCount(['axes' => fn ($q) => $papaId ? $q->where('papa_id', $papaId) : $q])
            ->get()
            ->map(function ($d) use ($papaId) {
                $tauxMoyen = $papaId
                    ? Axe::where('papa_id', $papaId)->where('departement_id', $d->id)->avg('taux_execution')
                    : 0;

                return [
                    'code' => $d->code,
                    'libelle' => $d->libelle,
                    'commissaire' => $d->commissaire?->name,
                    'axes_count' => $d->axes_count,
                    'taux' => round((float) ($tauxMoyen ?? 0), 1),
                ];
            })
            ->filter(fn ($d) => $d['axes_count'] > 0)
            ->values()
            ->all();
    }

    protected function alertes(): array
    {
        $base = Alerte::query();

        return [
            'critiques' => (clone $base)->ouvertes()->critiques()->count(),
            'ouvertes' => (clone $base)->ouvertes()->count(),
            'attention' => (clone $base)->ouvertes()->where('niveau', 'attention')->count(),
            'resolues_30j' => (clone $base)->where('statut', 'resolue')
                ->where('updated_at', '>=', now()->subDays(30))->count(),
            'top_critiques' => Alerte::ouvertes()->critiques()
                ->latest('id')->limit(5)
                ->get(['id', 'titre', 'categorie', 'created_at'])
                ->map(fn ($a) => [
                    'titre' => $a->titre,
                    'categorie' => $a->categorie,
                    'depuis_jours' => $a->created_at?->diffInDays(now()) ?? 0,
                ])->all(),
        ];
    }

    protected function indicateursParTypologie(?int $papaId): array
    {
        if (! $papaId) {
            return [];
        }

        return Indicateur::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))
            ->selectRaw('categorie, COUNT(*) as nb, AVG(taux_realisation) as taux_moyen')
            ->groupBy('categorie')
            ->get()
            ->map(fn ($r) => [
                'categorie' => $r->categorie ?: '—',
                'nb' => (int) $r->nb,
                'taux_moyen' => round((float) $r->taux_moyen, 1),
            ])
            ->all();
    }

    protected function auditInterne(): array
    {
        return [
            'missions_en_cours' => AuditMission::where('statut', 'en_cours')->count(),
            'missions_cloturees' => AuditMission::where('statut', 'cloturee')->count(),
            'recommandations_ouvertes' => AuditRecommandation::whereNotIn('statut', ['verifiee', 'rejetee', 'abandonnee'])->count(),
            'recommandations_en_retard' => AuditRecommandation::whereDate('date_echeance', '<', now())
                ->whereNotIn('statut', ['verifiee', 'rejetee', 'abandonnee'])->count(),
        ];
    }

    protected function qualiteImports(?BudgetExercice $exercice): array
    {
        if (! $exercice) {
            return ['total' => 0, 'reussis' => 0, 'echec' => 0, 'erreurs_total' => 0];
        }

        $base = BudgetImport::where('exercice_id', $exercice->id);

        return [
            'total' => (clone $base)->count(),
            'reussis' => (clone $base)->where('statut', 'reussi')->count(),
            'echec' => (clone $base)->where('statut', 'echec')->count(),
            'erreurs_total' => (clone $base)->sum('nb_erreurs'),
        ];
    }

    protected function verdict(array $stats, array $execution, ?array $budget, array $alertes, array $audit): array
    {
        $score = 0;
        $max = 0;

        $points = [];

        // Critère 1 : exécution physique
        $tg = $stats['taux_global'];
        if ($tg >= 75) {
            $score += 4;
            $points[] = ['icon' => '✓', 'level' => 'success', 'text' => "Exécution physique satisfaisante ({$tg}%)"];
        } elseif ($tg >= 40) {
            $score += 2;
            $points[] = ['icon' => '●', 'level' => 'warning', 'text' => "Exécution physique modérée ({$tg}%) — vigilance requise"];
        } else {
            $points[] = ['icon' => '⚠', 'level' => 'danger', 'text' => "Sous-performance physique ({$tg}%) — intervention stratégique"];
        }
        $max += 4;

        // Critère 2 : alertes critiques
        if ($alertes['critiques'] === 0) {
            $score += 4;
            $points[] = ['icon' => '✓', 'level' => 'success', 'text' => 'Aucune alerte critique ouverte'];
        } else {
            $points[] = ['icon' => '⚠', 'level' => 'danger', 'text' => "{$alertes['critiques']} alerte(s) critique(s) à traiter"];
        }
        $max += 4;

        // Critère 3 : retards activités
        if ($execution['activites_retard'] === 0) {
            $score += 3;
            $points[] = ['icon' => '✓', 'level' => 'success', 'text' => 'Aucune activité en retard'];
        } else {
            $points[] = ['icon' => '⚠', 'level' => 'warning', 'text' => "{$execution['activites_retard']} activité(s) en retard"];
        }
        $max += 3;

        // Critère 4 : cohérence physique vs financier
        if ($budget) {
            $ecart = abs($budget['taux_paiement'] - $stats['taux_global']);
            if ($ecart <= 15) {
                $score += 3;
                $points[] = ['icon' => '✓', 'level' => 'success', 'text' => 'Cohérence physique/financière satisfaisante'];
            } else {
                $points[] = ['icon' => '⚠', 'level' => 'warning', 'text' => "Écart {$ecart}pts entre exécution physique et financière"];
            }
            $max += 3;
        }

        // Critère 5 : audit recommandations en retard
        if ($audit['recommandations_en_retard'] === 0) {
            $score += 2;
            $points[] = ['icon' => '✓', 'level' => 'success', 'text' => 'Suivi audit interne à jour'];
        } else {
            $points[] = ['icon' => '⚠', 'level' => 'warning', 'text' => "{$audit['recommandations_en_retard']} recommandation(s) audit en retard"];
        }
        $max += 2;

        $maturite = $max > 0 ? round($score / $max * 100) : 0;
        $niveau = match (true) {
            $maturite >= 80 => 'success',
            $maturite >= 50 => 'warning',
            default => 'danger',
        };
        $libelleNiveau = match (true) {
            $maturite >= 80 => 'Pilotage maîtrisé',
            $maturite >= 50 => 'Pilotage à renforcer',
            default => 'Pilotage critique',
        };

        return compact('points', 'score', 'max', 'maturite', 'niveau', 'libelleNiveau');
    }

    protected function recommandations(array $stats, array $execution, ?array $budget, array $alertes, array $audit): array
    {
        $recos = [];

        if ($stats['taux_global'] < 50) {
            $recos[] = [
                'priorite' => 'critique',
                'titre' => 'Convoquer une revue stratégique de pilotage',
                'detail' => 'Le taux d\'exécution physique global est inférieur à 50%. Une réunion du Comité de pilotage est requise sous 15 jours.',
                'responsable' => 'Secrétariat Général',
                'echeance' => now()->addDays(15)->format('d/m/Y'),
            ];
        }

        if ($execution['activites_retard'] > 10) {
            $recos[] = [
                'priorite' => 'haute',
                'titre' => 'Plan de rattrapage activités en retard',
                'detail' => "{$execution['activites_retard']} activités en retard. Demander à chaque chef de service un plan correctif sous 7 jours.",
                'responsable' => 'Commissaires sectoriels',
                'echeance' => now()->addDays(7)->format('d/m/Y'),
            ];
        }

        if ($alertes['critiques'] > 0) {
            $recos[] = [
                'priorite' => 'critique',
                'titre' => 'Traitement immédiat des alertes critiques',
                'detail' => "{$alertes['critiques']} alerte(s) critique(s) doit/doivent être assignée(s) à un responsable et faire l'objet d'un plan d'action sous 3 jours.",
                'responsable' => 'Directeurs concernés',
                'echeance' => now()->addDays(3)->format('d/m/Y'),
            ];
        }

        if ($budget && $budget['taux_engagement'] < 30 && $stats['taux_global'] > 50) {
            $recos[] = [
                'priorite' => 'haute',
                'titre' => 'Accélération des engagements budgétaires',
                'detail' => 'Le taux d\'engagement budgétaire est anormalement bas par rapport à l\'avancement physique. Une revue des procédures de marché est recommandée.',
                'responsable' => 'Contrôle financier',
                'echeance' => now()->addDays(30)->format('d/m/Y'),
            ];
        }

        if ($audit['recommandations_en_retard'] > 0) {
            $recos[] = [
                'priorite' => 'haute',
                'titre' => 'Suivi des recommandations d\'audit en retard',
                'detail' => "{$audit['recommandations_en_retard']} recommandation(s) d'audit interne a/ont dépassé leur échéance.",
                'responsable' => 'Inspection Générale des Services',
                'echeance' => now()->addDays(14)->format('d/m/Y'),
            ];
        }

        if (empty($recos)) {
            $recos[] = [
                'priorite' => 'normale',
                'titre' => 'Maintien du rythme de revue trimestrielle',
                'detail' => 'Aucune anomalie majeure détectée. Poursuivre les revues de pilotage trimestrielles.',
                'responsable' => 'Comité de pilotage',
                'echeance' => now()->addMonths(3)->format('d/m/Y'),
            ];
        }

        return $recos;
    }
}
