<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\Alerte;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Departement;
use App\Models\GeneratedReport;
use App\Models\Indicateur;
use App\Models\Papa;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Models\User;
use App\Models\Validation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

/**
 * Agrégations institutionnelles pour le centre de pilotage exécutif TB-PAPA-CEEAC.
 * Toutes les statistiques sont calculées à partir des données réelles (aucune valeur fictive).
 */
class DashboardStatsService
{
    public function build(?Papa $papa = null): array
    {
        $papa = $papa ?? Papa::actif()->orderByDesc('annee')->first();
        $papaId = $papa?->id;

        return Cache::remember(
            "dashboard.stats.v4.{$papaId}",
            now()->addMinutes(2),
            fn () => [
                'papa_actif' => $this->papaSnapshot($papa),
                'rbm' => $this->compteursRbm($papaId),
                'execution' => $this->execution($papaId),
                'activites' => $this->statistiquesActivites($papaId),
                'taches' => $this->statistiquesTaches($papaId),
                'budget' => $this->budget($papa),
                'alertes' => $this->alertes(),
                'validations' => $this->validations(),
                'indicateurs' => $this->indicateurs($papaId),
                'documents' => $this->documents(),
                'gouvernance' => $this->gouvernance(),
                'top' => [
                    'axes_performants' => $this->topAxesPerformants($papaId),
                    'activites_critiques' => $this->activitesCritiques($papaId),
                    'echeances_proches' => $this->echeancesProches($papaId),
                ],
                'departements' => $this->performanceDepartements($papaId),
                'evolution' => $this->evolutionMensuelle($papaId),
                'repartition' => $this->repartitionActivites($papaId),
                'feed_audit' => $this->feedAudit(),
            ],
        );
    }

    protected function papaSnapshot(?Papa $papa): ?array
    {
        if (! $papa) {
            return null;
        }

        $debut = $papa->date_debut;
        $fin = $papa->date_fin;
        $progressionTemporelle = 0;
        if ($debut && $fin) {
            $total = max(1, $debut->diffInDays($fin));
            $ecoule = max(0, $debut->diffInDays(now()));
            $progressionTemporelle = min(100, round(($ecoule / $total) * 100, 1));
        }

        return [
            'id' => $papa->id,
            'annee' => $papa->annee,
            'libelle' => $papa->libelle,
            'statut' => $papa->statut,
            'taux_execution_physique' => $papa->tauxExecutionPhysique(),
            'taux_execution_financier' => $papa->tauxExecutionFinancier(),
            'progression_temporelle' => $progressionTemporelle,
            'date_debut' => $debut?->toIso8601String(),
            'date_fin' => $fin?->toIso8601String(),
        ];
    }

    protected function compteursRbm(?int $papaId): array
    {
        if (! $papaId) {
            return ['axes' => 0, 'produits' => 0, 'sous_produits' => 0, 'activites' => 0, 'taches' => 0, 'indicateurs' => 0];
        }

        return [
            'axes' => Axe::where('papa_id', $papaId)->count(),
            'produits' => Produit::whereHas('axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'sous_produits' => SousProduit::whereHas('produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'activites' => Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'taches' => Tache::whereHas('activite.sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
            'indicateurs' => Indicateur::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))->count(),
        ];
    }

    protected function execution(?int $papaId): array
    {
        if (! $papaId) {
            return ['taux_global' => 0, 'taux_axes_moyen' => 0, 'taux_produits_moyen' => 0];
        }

        $axes = Axe::where('papa_id', $papaId)->get(['taux_execution', 'poids']);
        $produits = Produit::whereHas('axe', fn ($q) => $q->where('papa_id', $papaId))->get(['taux_execution', 'poids']);

        return [
            'taux_axes_moyen' => round((float) $axes->avg('taux_execution'), 1),
            'taux_produits_moyen' => round((float) $produits->avg('taux_execution'), 1),
            'taux_global' => round((float) $axes->avg('taux_execution'), 1),
        ];
    }

    protected function statistiquesActivites(?int $papaId): array
    {
        $base = Activite::query();
        if ($papaId) {
            $base->whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));
        }

        return [
            'total' => (clone $base)->count(),
            'planifiees' => (clone $base)->where('statut', 'planifiee')->count(),
            'en_cours' => (clone $base)->where('statut', 'en_cours')->count(),
            'realisees' => (clone $base)->where('statut', 'realisee')->count(),
            'suspendues' => (clone $base)->where('statut', 'suspendue')->count(),
            'annulees' => (clone $base)->where('statut', 'annulee')->count(),
            'en_retard' => (clone $base)
                ->whereDate('date_fin', '<', now())
                ->whereNotIn('statut', ['realisee', 'annulee'])
                ->where('taux_execution', '<', 100)
                ->count(),
            'jalons' => (clone $base)->where('est_jalon', true)->count(),
            'jalons_atteints' => (clone $base)->where('est_jalon', true)->where('statut', 'realisee')->count(),
            'risque_critique' => (clone $base)->where('niveau_risque', 'critique')->count(),
            'risque_eleve' => (clone $base)->where('niveau_risque', 'eleve')->count(),
        ];
    }

    protected function statistiquesTaches(?int $papaId): array
    {
        $base = Tache::query();
        if ($papaId) {
            $base->whereHas('activite.sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));
        }

        return [
            'total' => (clone $base)->count(),
            'planifiees' => (clone $base)->where('statut', 'planifiee')->count(),
            'en_cours' => (clone $base)->where('statut', 'en_cours')->count(),
            'realisees' => (clone $base)->where('statut', 'realisee')->count(),
            'suspendues' => (clone $base)->where('statut', 'suspendue')->count(),
        ];
    }

    protected function budget(?Papa $papa): array
    {
        $exerciceId = $papa?->annee
            ? BudgetExercice::where('annee', $papa->annee)->value('id')
            : null;

        $base = BudgetLigne::query();
        if ($exerciceId) {
            $base->where('exercice_id', $exerciceId);
        }

        $depenses = (clone $base)->where('nature', 'depense');
        $recettes = (clone $base)->where('nature', 'recette');

        $totalPrevu = (float) (clone $depenses)->sum('montant_total');
        $totalEngage = (float) (clone $depenses)->sum('montant_engage');
        $totalPaye = (float) (clone $depenses)->sum('montant_paye');
        $ceeacEm = (float) (clone $depenses)->sum('montant_ceeac_em');
        $ptf = (float) (clone $depenses)->sum('montant_ptf');

        return [
            'total_prevu' => $totalPrevu,
            'total_engage' => $totalEngage,
            'total_paye' => $totalPaye,
            'disponible' => max(0.0, $totalPrevu - $totalEngage),
            'taux_engagement' => $totalPrevu > 0 ? round(($totalEngage / $totalPrevu) * 100, 1) : 0,
            'taux_consommation' => $totalPrevu > 0 ? round(($totalPaye / $totalPrevu) * 100, 1) : 0,
            'ceeac_em' => $ceeacEm,
            'ptf' => $ptf,
            'ratio_autonomie' => ($ceeacEm + $ptf) > 0 ? round($ceeacEm / ($ceeacEm + $ptf) * 100, 1) : 0,
            'total_recettes' => (float) $recettes->sum('montant_total'),
            'nombre_lignes' => (clone $base)->count(),
        ];
    }

    protected function alertes(): array
    {
        return [
            'ouvertes' => Alerte::ouvertes()->count(),
            'critiques' => Alerte::ouvertes()->critiques()->count(),
            'attention' => Alerte::ouvertes()->where('niveau', 'attention')->count(),
            'info' => Alerte::ouvertes()->where('niveau', 'info')->count(),
            'resolues_30j' => Alerte::whereNotNull('resolue_at')
                ->where('resolue_at', '>=', now()->subDays(30))
                ->count(),
            'recentes' => Alerte::ouvertes()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'niveau', 'titre', 'created_at'])
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'niveau' => $a->niveau,
                    'titre' => $a->titre,
                    'date' => $a->created_at?->diffForHumans(),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function validations(): array
    {
        $base = Validation::query();

        return [
            'en_attente' => (clone $base)->where('decision', 'en_attente')->count(),
            'approuvees_30j' => (clone $base)
                ->where('decision', 'approuve')
                ->where('updated_at', '>=', now()->subDays(30))
                ->count(),
            'rejetees_30j' => (clone $base)
                ->where('decision', 'rejete')
                ->where('updated_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    protected function indicateurs(?int $papaId): array
    {
        $base = Indicateur::query();
        if ($papaId) {
            $base->whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));
        }

        $total = (clone $base)->count();
        $atteints = (clone $base)->where('taux_realisation', '>=', 100)->count();
        $aRisque = (clone $base)->where('taux_realisation', '<', 40)->whereNotNull('valeur_actuelle')->count();

        return [
            'total' => $total,
            'atteints' => $atteints,
            'a_risque' => $aRisque,
            'taux_moyen' => round((float) (clone $base)->whereNotNull('taux_realisation')->avg('taux_realisation'), 1),
        ];
    }

    protected function documents(): array
    {
        return [
            'rapports_30j' => GeneratedReport::where('genere_at', '>=', now()->subDays(30))->count(),
            'telechargements_total' => (int) GeneratedReport::sum('nb_telechargements'),
            'recents' => GeneratedReport::with('genereur:id,name')
                ->orderByDesc('genere_at')
                ->limit(5)
                ->get(['id', 'titre', 'categorie', 'genere_at', 'genere_par_id', 'code_verification'])
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'titre' => $r->titre,
                    'categorie' => $r->categorie,
                    'date' => $r->genere_at?->diffForHumans(),
                    'auteur' => $r->genereur?->name,
                ])
                ->values()
                ->all(),
        ];
    }

    protected function gouvernance(): array
    {
        return [
            'utilisateurs_actifs' => User::where('actif', true)->count(),
            'departements' => Departement::where('actif', true)->count(),
            'mfa_activee' => User::whereNotNull('two_factor_confirmed_at')->count(),
        ];
    }

    protected function topAxesPerformants(?int $papaId): array
    {
        if (! $papaId) {
            return [];
        }

        return Axe::where('papa_id', $papaId)
            ->with(['departement:id,code'])
            ->orderByDesc('taux_execution')
            ->limit(5)
            ->get(['id', 'code', 'libelle', 'taux_execution', 'departement_id'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'libelle' => $a->libelle,
                'taux' => round((float) $a->taux_execution, 1),
                'departement' => $a->departement?->code,
            ])
            ->all();
    }

    protected function activitesCritiques(?int $papaId): array
    {
        $base = Activite::query()
            ->with(['sousProduit.produit.axe:id,code']);
        if ($papaId) {
            $base->whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));
        }

        return $base
            ->whereDate('date_fin', '<', now())
            ->whereNotIn('statut', ['realisee', 'annulee'])
            ->where('taux_execution', '<', 100)
            ->orderBy('date_fin')
            ->limit(5)
            ->get(['id', 'code', 'libelle', 'date_fin', 'taux_execution', 'sous_produit_id'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'libelle' => $a->libelle,
                'date_fin' => $a->date_fin?->toDateString(),
                'jours_retard' => $a->date_fin ? (int) abs($a->date_fin->diffInDays(now())) : 0,
                'taux' => round((float) $a->taux_execution, 1),
                'axe' => $a->sousProduit?->produit?->axe?->code,
            ])
            ->all();
    }

    protected function echeancesProches(?int $papaId): array
    {
        $base = Activite::query()
            ->with(['sousProduit.produit.axe:id,code']);
        if ($papaId) {
            $base->whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId));
        }

        return $base
            ->whereBetween('date_fin', [now(), now()->addDays(30)])
            ->whereNotIn('statut', ['realisee', 'annulee'])
            ->orderBy('date_fin')
            ->limit(5)
            ->get(['id', 'code', 'libelle', 'date_fin', 'taux_execution'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'libelle' => $a->libelle,
                'date_fin' => $a->date_fin?->toDateString(),
                'jours_restants' => $a->date_fin ? (int) abs(now()->diffInDays($a->date_fin)) : 0,
                'taux' => round((float) $a->taux_execution, 1),
            ])
            ->all();
    }

    protected function performanceDepartements(?int $papaId): array
    {
        if (! $papaId) {
            return [];
        }

        return Departement::with(['axes' => fn ($q) => $q->where('papa_id', $papaId)])
            ->where('actif', true)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'code' => $d->code,
                'libelle' => $d->libelle,
                'nombre_axes' => $d->axes->count(),
                'taux_moyen' => $d->axes->count() > 0
                    ? round((float) $d->axes->avg('taux_execution'), 1)
                    : 0,
            ])
            ->filter(fn ($d) => $d['nombre_axes'] > 0)
            ->values()
            ->all();
    }

    protected function evolutionMensuelle(?int $papaId): array
    {
        if (! $papaId) {
            return [];
        }

        $papa = Papa::find($papaId);
        $debut = $papa?->date_debut ?? now()->startOfYear();
        $fin = $papa?->date_fin ?? now()->endOfYear();

        $mois = [];
        $curseur = Carbon::parse($debut)->startOfMonth();
        $maxMois = Carbon::parse($fin)->endOfMonth();

        while ($curseur <= $maxMois && count($mois) < 24) {
            $finMois = $curseur->copy()->endOfMonth();
            $activitesFinies = Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))
                ->where('statut', 'realisee')
                ->whereDate('date_fin_reelle', '<=', $finMois)
                ->count();
            $mois[] = [
                'mois' => $curseur->isoFormat('MMM YY'),
                'realisees' => $activitesFinies,
            ];
            $curseur->addMonth();
        }

        return $mois;
    }

    protected function repartitionActivites(?int $papaId): array
    {
        $stats = $this->statistiquesActivites($papaId);

        return [
            ['statut' => 'Planifiées', 'count' => $stats['planifiees'], 'color' => '#94a3b8'],
            ['statut' => 'En cours', 'count' => $stats['en_cours'], 'color' => '#3b82f6'],
            ['statut' => 'Réalisées', 'count' => $stats['realisees'], 'color' => '#10b981'],
            ['statut' => 'Suspendues', 'count' => $stats['suspendues'], 'color' => '#f59e0b'],
            ['statut' => 'Annulées', 'count' => $stats['annulees'], 'color' => '#ef4444'],
        ];
    }

    protected function feedAudit(): array
    {
        return Activity::with('causer:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'description', 'subject_type', 'event', 'created_at', 'causer_id'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'event' => $a->event,
                'subject' => class_basename($a->subject_type ?? ''),
                'auteur' => $a->causer?->name,
                'date' => $a->created_at?->diffForHumans(),
            ])
            ->all();
    }
}
