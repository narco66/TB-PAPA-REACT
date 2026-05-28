<?php

namespace App\Reports\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Reports\Report;

class BonEngagementReport extends Report
{
    public function key(): string
    {
        return 'bon_engagement';
    }

    public function titre(): string
    {
        return 'Bon d\'engagement budgétaire';
    }

    public function description(): string
    {
        return "Bon d'engagement budgétaire : référence, montant, imputations multiples, fournisseur, ordonnateur, anti-dépassement vérifié.";
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.bon_engagement';
    }

    public function icone(): string
    {
        return 'FileCheck';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'mouvement_id',
                'label' => 'Mouvement (engagement)',
                'type' => 'select',
                'required' => true,
                'options' => BudgetMouvement::where('type', 'engagement')->latest()->limit(100)->get(['id', 'reference', 'montant'])
                    ->map(fn ($m) => ['value' => $m->id, 'label' => "{$m->reference} — " . number_format((float) $m->montant, 0, ',', ' ')])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $id = $filtres['mouvement_id'] ?? null;
        if (! $id) {
            return ['mouvement' => null];
        }

        $mouvement = BudgetMouvement::with([
            'ligne:id,budget_ligne_code,libelle',
            'expenseRequest:id,numero,objet,montant_estime',
            'supplier', 'ordonnateur:id,name,fonction', 'comptable:id,name,fonction',
            'saisiPar:id,name', 'validePar:id,name', 'commitmentLines.ligne:id,budget_ligne_code,libelle',
            'commitmentLines.source:id,code,libelle',
        ])->findOrFail($id);

        return ['mouvement' => $mouvement];
    }
}
