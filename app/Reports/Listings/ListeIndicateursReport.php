<?php

namespace App\Reports\Listings;

use App\Models\Indicateur;
use Illuminate\Support\Collection;

class ListeIndicateursReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_indicateurs';
    }

    public function titre(): string
    {
        return 'Liste des indicateurs CMR';
    }

    public function description(): string
    {
        return 'Indicateurs CMR avec baseline, cible, valeur actuelle et taux de réalisation.';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'width' => '10%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Type', 'key' => 'type', 'width' => '8%'],
            ['label' => 'Catégorie', 'key' => 'categorie', 'width' => '10%'],
            ['label' => 'Unité', 'key' => 'unite', 'width' => '6%'],
            ['label' => 'Baseline', 'key' => 'baseline', 'kind' => 'num', 'width' => '8%'],
            ['label' => 'Cible', 'key' => 'cible', 'kind' => 'num', 'width' => '8%'],
            ['label' => 'Valeur', 'key' => 'valeur_actuelle', 'kind' => 'num', 'width' => '8%'],
            ['label' => 'Réalisation', 'key' => 'taux_realisation', 'kind' => 'progress', 'width' => '12%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = Indicateur::query();
        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w->where('libelle', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }
        if ($cat = $this->filtre($filtres, 'categorie')) {
            $q->where('categorie', $cat);
        }

        return $q->orderBy('code')->limit(1000)->get()->map(fn ($i) => [
            'code' => $i->code,
            'libelle' => $i->libelle,
            'type' => $i->type,
            'categorie' => $i->categorie ?: '—',
            'unite' => $i->unite ?: '—',
            'baseline' => $i->baseline,
            'cible' => $i->cible,
            'valeur_actuelle' => $i->valeur_actuelle,
            'taux_realisation' => round((float) $i->taux_realisation, 1),
        ]);
    }
}
