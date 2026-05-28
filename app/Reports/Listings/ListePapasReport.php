<?php

namespace App\Reports\Listings;

use App\Models\Papa;
use Illuminate\Support\Collection;

class ListePapasReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_papas';
    }

    public function titre(): string
    {
        return 'Liste des PAPA';
    }

    public function description(): string
    {
        return "Liste tabulaire des Plans d'Action Prioritaires Annuels (PAPA) — filtres : recherche, statut.";
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Année', 'key' => 'annee', 'width' => '8%', 'align' => 'right'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Version', 'key' => 'version', 'width' => '10%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '12%'],
            ['label' => 'Axes', 'key' => 'axes_count', 'kind' => 'num', 'width' => '8%'],
            ['label' => 'Période', 'key' => 'periode', 'width' => '20%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = Papa::query()->withCount('axes as axes_count');

        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('annee', 'like', "%{$search}%"));
        }
        if ($statut = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $statut);
        }

        return $q->orderByDesc('annee')->orderByDesc('id')->limit(500)->get()
            ->map(fn ($p) => [
                'annee' => $p->annee,
                'libelle' => $p->libelle,
                'version' => $p->version,
                'statut' => $p->statut,
                'axes_count' => $p->axes_count,
                'periode' => ($p->date_debut?->format('d/m/Y') ?? '—') . ' → ' . ($p->date_fin?->format('d/m/Y') ?? '—'),
            ]);
    }
}
