<?php

namespace App\Reports\Performance;

use App\Models\Axe;
use App\Reports\Report;

class FichePerformanceAxeReport extends Report
{
    public function key(): string
    {
        return 'fiche_performance_axe';
    }

    public function titre(): string
    {
        return 'Fiche de performance — Axe stratégique';
    }

    public function description(): string
    {
        return 'Fiche détaillée d\'un axe : Produits, Sous-Produits, Activités, taux d\'exécution, jalons et indicateurs.';
    }

    public function categorie(): string
    {
        return self::CAT_PERFORMANCE;
    }

    public function template(): string
    {
        return 'reports.performance.axe';
    }

    public function icone(): string
    {
        return 'Target';
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'axe_id',
                'label' => 'Axe stratégique',
                'type' => 'select',
                'required' => true,
                'options' => Axe::with('papa:id,annee')->orderBy('papa_id')->orderBy('ordre')
                    ->get(['id', 'code', 'libelle', 'papa_id'])
                    ->map(fn ($a) => [
                        'value' => $a->id,
                        'label' => "{$a->code} — {$a->libelle} (PAPA {$a->papa->annee})",
                    ])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $axeId = $filtres['axe_id'] ?? Axe::value('id');
        $axe = Axe::with([
            'papa:id,annee,libelle',
            'departement:id,code,libelle',
            'responsable:id,name,fonction',
            'produits' => fn ($q) => $q->orderBy('ordre'),
            'produits.sousProduits' => fn ($q) => $q->orderBy('ordre'),
            'produits.sousProduits.activites' => fn ($q) => $q->orderBy('ordre'),
            'produits.sousProduits.indicateurs',
        ])->findOrFail($axeId);

        // Stats
        $nbProduits = $axe->produits->count();
        $nbSousProduits = $axe->produits->sum(fn ($p) => $p->sousProduits->count());
        $nbActivites = $axe->produits->sum(fn ($p) => $p->sousProduits->sum(fn ($sp) => $sp->activites->count()));
        $activitesRetard = 0;
        $activitesRealisees = 0;
        foreach ($axe->produits as $p) {
            foreach ($p->sousProduits as $sp) {
                foreach ($sp->activites as $act) {
                    if ($act->estEnRetard()) {
                        $activitesRetard++;
                    }
                    if ($act->statut === 'realisee') {
                        $activitesRealisees++;
                    }
                }
            }
        }

        return [
            'axe' => $axe,
            'stats' => [
                'nb_produits' => $nbProduits,
                'nb_sous_produits' => $nbSousProduits,
                'nb_activites' => $nbActivites,
                'activites_retard' => $activitesRetard,
                'activites_realisees' => $activitesRealisees,
            ],
        ];
    }
}
