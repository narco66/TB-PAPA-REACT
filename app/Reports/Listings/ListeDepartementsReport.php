<?php

namespace App\Reports\Listings;

use App\Models\Departement;
use Illuminate\Support\Collection;

class ListeDepartementsReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_departements';
    }

    public function titre(): string
    {
        return 'Liste des départements';
    }

    public function description(): string
    {
        return 'Départements de la Commission CEEAC avec leur Commissaire et nombre d\'axes.';
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
            ['label' => 'Commissaire', 'key' => 'commissaire', 'width' => '22%'],
            ['label' => 'Axes', 'key' => 'axes_count', 'kind' => 'num', 'width' => '10%'],
            ['label' => 'Actif', 'key' => 'actif', 'kind' => 'badge', 'width' => '10%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        return Departement::query()
            ->with('commissaire:id,name')
            ->withCount('axes as axes_count')
            ->orderBy('ordre')
            ->orderBy('libelle')
            ->get()
            ->map(fn ($d) => [
                'code' => $d->code,
                'libelle' => $d->libelle,
                'commissaire' => $d->commissaire?->name ?? '—',
                'axes_count' => $d->axes_count,
                'actif' => $d->actif ? 'Actif' : 'Inactif',
            ]);
    }
}
