<?php

namespace App\Reports\Listings;

use App\Models\Budget\BudgetExercice;
use Illuminate\Support\Collection;

class ListeBudgetExercicesReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_budget_exercices';
    }

    public function titre(): string
    {
        return 'Liste des exercices budgétaires';
    }

    public function description(): string
    {
        return 'Exercices budgétaires avec statut, période et totaux consolidés.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Année', 'key' => 'annee', 'kind' => 'num', 'width' => '8%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Lignes', 'key' => 'lignes_count', 'kind' => 'num', 'width' => '8%'],
            ['label' => 'Devise', 'key' => 'devise', 'width' => '8%'],
            ['label' => 'Total dépenses', 'key' => 'total_depenses', 'kind' => 'num', 'width' => '14%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        return BudgetExercice::query()
            ->withCount('lignes as lignes_count')
            ->orderByDesc('annee')
            ->limit(200)
            ->get()
            ->map(fn ($e) => [
                'annee' => $e->annee,
                'libelle' => $e->libelle,
                'statut' => $e->statut,
                'lignes_count' => $e->lignes_count,
                'devise' => $e->devise ?? 'XAF',
                'total_depenses' => number_format((float) $e->total_depenses, 0, ',', ' '),
            ]);
    }
}
