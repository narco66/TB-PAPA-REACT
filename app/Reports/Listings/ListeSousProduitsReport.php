<?php

namespace App\Reports\Listings;

use App\Models\SousProduit;
use Illuminate\Support\Collection;

class ListeSousProduitsReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_sous_produits';
    }

    public function titre(): string
    {
        return 'Liste des sous-produits RBM';
    }

    public function description(): string
    {
        return 'Sous-produits rattachés aux produits (niveau 3 RBM/GAR).';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'width' => '12%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Produit', 'key' => 'produit', 'width' => '15%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Exécution', 'key' => 'taux_execution', 'kind' => 'progress', 'width' => '15%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = SousProduit::query()->with(['produit:id,code']);

        if ($produitId = $this->filtre($filtres, 'produit_id')) {
            $q->where('produit_id', $produitId);
        }
        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        return $q->orderBy('ordre')->limit(1000)->get()->map(fn ($s) => [
            'code' => $s->code,
            'libelle' => $s->libelle,
            'produit' => $s->produit?->code ?? '—',
            'statut' => $s->statut,
            'taux_execution' => round((float) $s->taux_execution, 1),
        ]);
    }
}
