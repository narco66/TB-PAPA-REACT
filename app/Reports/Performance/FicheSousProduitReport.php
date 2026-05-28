<?php

namespace App\Reports\Performance;

use App\Models\SousProduit;
use App\Reports\Report;

class FicheSousProduitReport extends Report
{
    public function key(): string
    {
        return 'fiche_sous_produit';
    }

    public function titre(): string
    {
        return 'Fiche Sous-Produit RBM';
    }

    public function description(): string
    {
        return 'Fiche d\'un Sous-Produit : produit parent, activités, indicateurs KPI rattachés.';
    }

    public function categorie(): string
    {
        return self::CAT_PERFORMANCE;
    }

    public function template(): string
    {
        return 'reports.performance.sous_produit';
    }

    public function icone(): string
    {
        return 'Boxes';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'sous_produit_id',
                'label' => 'Sous-Produit',
                'type' => 'select',
                'required' => true,
                'options' => SousProduit::orderBy('ordre')->get(['id', 'code', 'libelle'])
                    ->map(fn ($s) => ['value' => $s->id, 'label' => "{$s->code} — {$s->libelle}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $spId = $filtres['sous_produit_id'] ?? SousProduit::value('id');
        $sp = SousProduit::with([
            'produit:id,code,libelle,axe_id',
            'produit.axe:id,code,libelle,papa_id',
            'produit.axe.papa:id,annee',
            'direction:id,code,libelle',
            'responsable:id,name,fonction',
            'activites' => fn ($q) => $q->orderBy('ordre'),
            'activites.taches' => fn ($q) => $q->orderBy('ordre'),
            'indicateurs',
        ])->findOrFail($spId);

        return ['sous_produit' => $sp];
    }
}
