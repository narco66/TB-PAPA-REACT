<?php

namespace App\Reports\Rbm;

use App\Models\Axe;
use App\Models\Papa;
use App\Reports\Report;

class MatriceRbmReport extends Report
{
    public function key(): string
    {
        return 'matrice_rbm';
    }

    public function titre(): string
    {
        return 'Matrice RBM/GAR consolidée';
    }

    public function description(): string
    {
        return 'Matrice complète de la chaîne RBM officielle CEEAC : Axe → Produit → Sous-Produit → Activité → Tâche, avec taux d\'exécution à chaque niveau.';
    }

    public function categorie(): string
    {
        return self::CAT_RBM;
    }

    public function template(): string
    {
        return 'reports.rbm.matrice';
    }

    public function icone(): string
    {
        return 'GitBranch';
    }

    public function orientation(): string
    {
        return 'landscape';
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
        $papa = Papa::findOrFail($papaId);

        $axes = Axe::where('papa_id', $papaId)
            ->with([
                'produits' => fn ($q) => $q->orderBy('ordre'),
                'produits.sousProduits' => fn ($q) => $q->orderBy('ordre'),
                'produits.sousProduits.activites' => fn ($q) => $q->orderBy('ordre'),
                'produits.sousProduits.activites.taches' => fn ($q) => $q->orderBy('ordre'),
            ])
            ->orderBy('ordre')
            ->get();

        return compact('papa', 'axes');
    }
}
