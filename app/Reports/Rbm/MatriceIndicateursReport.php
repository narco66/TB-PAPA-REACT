<?php

namespace App\Reports\Rbm;

use App\Models\Indicateur;
use App\Models\Papa;
use App\Reports\Report;

class MatriceIndicateursReport extends Report
{
    public function key(): string
    {
        return 'matrice_indicateurs';
    }

    public function titre(): string
    {
        return 'Matrice des indicateurs KPI';
    }

    public function description(): string
    {
        return 'Inventaire des indicateurs de performance avec baseline, cible, valeur actuelle, taux de réalisation et tendance.';
    }

    public function categorie(): string
    {
        return self::CAT_RBM;
    }

    public function template(): string
    {
        return 'reports.rbm.indicateurs';
    }

    public function icone(): string
    {
        return 'BarChart3';
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

        $indicateurs = Indicateur::query()
            ->with([
                'sousProduit:id,code,libelle,produit_id',
                'sousProduit.produit:id,code,libelle,axe_id',
                'sousProduit.produit.axe:id,code,libelle,papa_id',
                'responsable:id,name',
            ])
            ->when($papa, fn ($q) => $q->whereHas('sousProduit.produit.axe', fn ($w) => $w->where('papa_id', $papa->id)))
            ->orderBy('code')
            ->get();

        return compact('papa', 'indicateurs');
    }
}
