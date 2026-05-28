<?php

namespace App\Reports\Gouvernance;

use App\Models\Axe;
use App\Models\Papa;
use App\Reports\Report;

class MatriceRaciReport extends Report
{
    public function key(): string
    {
        return 'matrice_raci';
    }

    public function titre(): string
    {
        return 'Matrice RACI institutionnelle';
    }

    public function description(): string
    {
        return 'Matrice des responsabilités R-A-C-I (Responsable, Approbateur, Consulté, Informé) sur les Axes et Produits du PAPA.';
    }

    public function categorie(): string
    {
        return self::CAT_GOUVERNANCE;
    }

    public function template(): string
    {
        return 'reports.gouvernance.raci';
    }

    public function icone(): string
    {
        return 'Users';
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
        $papa = Papa::with('valideur:id,name')->findOrFail($papaId);

        $axes = Axe::where('papa_id', $papaId)
            ->with([
                'departement.commissaire:id,name',
                'responsable:id,name,fonction',
                'produits' => fn ($q) => $q->orderBy('ordre'),
                'produits.direction:id,code,libelle,directeur_id',
                'produits.direction.directeur:id,name',
                'produits.responsable:id,name',
            ])
            ->orderBy('ordre')
            ->get();

        return compact('papa', 'axes');
    }
}
