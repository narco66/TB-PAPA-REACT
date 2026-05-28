<?php

namespace App\Reports\Expense;

use App\Models\Budget\BudgetExercice;
use App\Reports\Report;
use App\Services\Expense\DashboardExpenseStatsService;

class TableauBordExpenseReport extends Report
{
    public function __construct(protected DashboardExpenseStatsService $stats) {}

    public function key(): string
    {
        return 'tableau_bord_expense';
    }

    public function titre(): string
    {
        return 'Tableau de bord — Chaîne de la dépense';
    }

    public function description(): string
    {
        return 'Pilotage consolidé de la chaîne RGCP : expressions, cycle IPSAS, performance par département/type/fournisseur, recommandations.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.tableau_bord';
    }

    public function icone(): string
    {
        return 'FileBarChart';
    }

    public function permission(): string
    {
        return 'expense.viewAny';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'exercice_id',
                'label' => 'Exercice',
                'type' => 'select',
                'options' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle'])
                    ->map(fn ($e) => ['value' => $e->id, 'label' => "Exercice {$e->annee}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $exerciceId = isset($filtres['exercice_id']) && $filtres['exercice_id'] ? (int) $filtres['exercice_id'] : null;

        return $this->stats->build($exerciceId);
    }
}
