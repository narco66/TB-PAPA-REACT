<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\Alerte;
use App\Models\Budget;
use App\Models\Indicateur;

class AlerteService
{
    /**
     * Détection automatique des alertes système (Section 6.10 du CDC).
     * À appeler via scheduler ou commande artisan.
     */
    public function detecterAlertes(): array
    {
        $count = [
            'retards' => $this->detecterRetards(),
            'derives_budgetaires' => $this->detecterDerivesBudgetaires(),
            'sous_performances' => $this->detecterSousPerformances(),
        ];

        return $count;
    }

    protected function detecterRetards(): int
    {
        $count = 0;
        Activite::whereDate('date_fin_prevue', '<', now())
            ->whereNotIn('statut', ['realisee', 'annulee'])
            ->where('avancement', '<', 100)
            ->with('actionPrioritaire.commissaire')
            ->chunk(50, function ($activites) use (&$count) {
                foreach ($activites as $activite) {
                    $existe = Alerte::where('alertable_type', Activite::class)
                        ->where('alertable_id', $activite->id)
                        ->where('categorie', 'retard')
                        ->whereIn('statut', ['ouverte', 'en_traitement'])
                        ->exists();
                    if ($existe) {
                        continue;
                    }

                    $joursRetard = (int) abs($activite->date_fin_prevue->diffInDays(now()));
                    Alerte::create([
                        'alertable_type' => Activite::class,
                        'alertable_id' => $activite->id,
                        'niveau' => $joursRetard > 60 ? 'critique' : ($joursRetard > 30 ? 'attention' : 'info'),
                        'categorie' => 'retard',
                        'titre' => "Activité en retard : {$activite->code}",
                        'message' => "L'activité « {$activite->libelle} » accuse un retard de {$joursRetard} jours et n'est qu'à {$activite->avancement}%.",
                        'contexte' => [
                            'avancement' => $activite->avancement,
                            'date_fin_prevue' => $activite->date_fin_prevue?->toDateString(),
                            'jours_retard' => $joursRetard,
                        ],
                        'automatique' => true,
                        'statut' => 'ouverte',
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    protected function detecterDerivesBudgetaires(): int
    {
        $count = 0;
        Budget::chunk(50, function ($budgets) use (&$count) {
            foreach ($budgets as $budget) {
                $tauxConsommation = $budget->prevision > 0
                    ? ($budget->consommation / $budget->prevision) * 100
                    : 0;

                if ($tauxConsommation <= 100) {
                    continue;
                }

                $existe = Alerte::where('alertable_type', Budget::class)
                    ->where('alertable_id', $budget->id)
                    ->where('categorie', 'derive_budgetaire')
                    ->whereIn('statut', ['ouverte', 'en_traitement'])
                    ->exists();
                if ($existe) {
                    continue;
                }

                Alerte::create([
                    'alertable_type' => Budget::class,
                    'alertable_id' => $budget->id,
                    'niveau' => 'critique',
                    'categorie' => 'derive_budgetaire',
                    'titre' => 'Dépassement budgétaire détecté',
                    'message' => 'La consommation atteint ' . round($tauxConsommation, 1) . "% de la prévision (source : {$budget->source}).",
                    'contexte' => [
                        'prevision' => $budget->prevision,
                        'consommation' => $budget->consommation,
                        'taux' => round($tauxConsommation, 2),
                    ],
                    'automatique' => true,
                    'statut' => 'ouverte',
                ]);
                $count++;
            }
        });

        return $count;
    }

    protected function detecterSousPerformances(): int
    {
        $count = 0;
        Indicateur::chunk(50, function ($indicateurs) use (&$count) {
            foreach ($indicateurs as $i) {
                if ($i->taux_realisation === null || $i->taux_realisation >= 40) {
                    continue;
                }

                $existe = Alerte::where('alertable_type', Indicateur::class)
                    ->where('alertable_id', $i->id)
                    ->where('categorie', 'sous_performance')
                    ->whereIn('statut', ['ouverte', 'en_traitement'])
                    ->exists();
                if ($existe) {
                    continue;
                }

                Alerte::create([
                    'alertable_type' => Indicateur::class,
                    'alertable_id' => $i->id,
                    'niveau' => $i->taux_realisation < 20 ? 'critique' : 'attention',
                    'categorie' => 'sous_performance',
                    'titre' => "Indicateur sous-performant : {$i->code}",
                    'message' => "L'indicateur « {$i->libelle} » n'atteint que " . round($i->taux_realisation, 1) . '% de sa cible.',
                    'contexte' => [
                        'taux_realisation' => $i->taux_realisation,
                        'cible' => $i->cible,
                        'valeur_actuelle' => $i->valeur_actuelle,
                    ],
                    'automatique' => true,
                    'statut' => 'ouverte',
                ]);
                $count++;
            }
        });

        return $count;
    }
}
