<?php

namespace App\Reports\Performance;

use App\Models\Produit;
use App\Reports\Report;

class FicheProduitReport extends Report
{
    public function key(): string
    {
        return 'fiche_produit';
    }

    public function titre(): string
    {
        return 'Fiche Produit RBM';
    }

    public function description(): string
    {
        return 'Fiche détaillée d\'un Produit : axe parent, sous-produits, taux d\'exécution, direction porteuse.';
    }

    public function categorie(): string
    {
        return self::CAT_PERFORMANCE;
    }

    public function template(): string
    {
        return 'reports.performance.produit';
    }

    public function icone(): string
    {
        return 'Package';
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'produit_id',
                'label' => 'Produit',
                'type' => 'select',
                'required' => true,
                'options' => Produit::with('axe:id,code')->orderBy('ordre')
                    ->get(['id', 'code', 'libelle', 'axe_id'])
                    ->map(fn ($p) => [
                        'value' => $p->id,
                        'label' => "{$p->code} — {$p->libelle}",
                    ])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $produitId = $filtres['produit_id'] ?? Produit::value('id');
        $produit = Produit::with([
            'axe:id,code,libelle,papa_id',
            'axe.papa:id,annee,libelle',
            'axe.departement:id,code,libelle',
            'direction:id,code,libelle,type',
            'responsable:id,name,fonction',
            'sousProduits' => fn ($q) => $q->orderBy('ordre'),
            'sousProduits.activites' => fn ($q) => $q->orderBy('ordre'),
        ])->findOrFail($produitId);

        $nbActivites = $produit->sousProduits->sum(fn ($sp) => $sp->activites->count());
        $activitesRealisees = 0;
        $activitesRetard = 0;
        foreach ($produit->sousProduits as $sp) {
            foreach ($sp->activites as $act) {
                if ($act->statut === 'realisee') {
                    $activitesRealisees++;
                }
                if ($act->estEnRetard()) {
                    $activitesRetard++;
                }
            }
        }

        return [
            'produit' => $produit,
            'stats' => [
                'nb_sous_produits' => $produit->sousProduits->count(),
                'nb_activites' => $nbActivites,
                'activites_realisees' => $activitesRealisees,
                'activites_retard' => $activitesRetard,
            ],
        ];
    }
}
