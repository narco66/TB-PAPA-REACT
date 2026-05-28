<?php

namespace App\Reports\Listings;

use App\Models\Supplier;
use Illuminate\Support\Collection;

class ListeSuppliersReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_suppliers';
    }

    public function titre(): string
    {
        return 'Liste des fournisseurs';
    }

    public function description(): string
    {
        return 'Référentiel fournisseurs avec NIF, RCCM, contacts et statut.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function permission(): string
    {
        return 'supplier.viewAny';
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'width' => '10%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Type', 'key' => 'type', 'width' => '14%'],
            ['label' => 'NIF', 'key' => 'nif', 'width' => '12%'],
            ['label' => 'Contact', 'key' => 'contact', 'width' => '20%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = Supplier::query();

        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('nif', 'like', "%{$search}%"));
        }
        if ($s = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $s);
        }

        return $q->orderBy('libelle')->limit(2000)->get()->map(fn ($s) => [
            'code' => $s->code,
            'libelle' => $s->libelle,
            'type' => $s->type,
            'nif' => $s->nif ?? '—',
            'contact' => $s->email ?? $s->telephone ?? '—',
            'statut' => $s->statut,
        ]);
    }
}
