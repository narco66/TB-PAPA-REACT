<?php

namespace App\Reports\Listings;

use App\Models\Produit;
use Illuminate\Support\Collection;

class ListeProduitsReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_produits';
    }

    public function titre(): string
    {
        return 'Liste des produits RBM';
    }

    public function description(): string
    {
        return 'Produits institutionnels rattachés aux axes stratégiques (niveau 2 RBM/GAR).';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'width' => '10%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Axe', 'key' => 'axe', 'width' => '12%'],
            ['label' => 'Direction', 'key' => 'direction', 'width' => '14%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Exécution', 'key' => 'taux_execution', 'kind' => 'progress', 'width' => '15%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = Produit::query()->with(['axe:id,code', 'direction:id,code,libelle']);

        if ($axeId = $this->filtre($filtres, 'axe_id')) {
            $q->where('axe_id', $axeId);
        }
        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        return $q->orderBy('ordre')->limit(1000)->get()->map(fn ($p) => [
            'code' => $p->code,
            'libelle' => $p->libelle,
            'axe' => $p->axe?->code ?? '—',
            'direction' => $p->direction?->code ?? '—',
            'statut' => $p->statut,
            'taux_execution' => round((float) $p->taux_execution, 1),
        ]);
    }
}
