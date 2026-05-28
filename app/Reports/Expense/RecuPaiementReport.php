<?php

namespace App\Reports\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Reports\Report;

class RecuPaiementReport extends Report
{
    public function key(): string
    {
        return 'recu_paiement';
    }

    public function titre(): string
    {
        return 'Reçu de paiement (quittance)';
    }

    public function description(): string
    {
        return "Quittance officielle de paiement à remettre au bénéficiaire. Atteste de l'acquittement par la Commission.";
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.recu_paiement';
    }

    public function icone(): string
    {
        return 'ReceiptText';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [[
            'key' => 'mouvement_id',
            'label' => 'Paiement',
            'type' => 'select', 'required' => true,
            'options' => BudgetMouvement::where('type', 'paiement')
                ->latest()->limit(100)->get(['id', 'reference', 'montant'])
                ->map(fn ($m) => ['value' => $m->id, 'label' => "{$m->reference} — " . number_format((float) $m->montant, 0, ',', ' ')])->toArray(),
        ]];
    }

    public function donnees(array $filtres = []): array
    {
        $id = $filtres['mouvement_id'] ?? null;
        if (! $id) {
            return ['mouvement' => null];
        }

        return [
            'mouvement' => BudgetMouvement::with([
                'parent.parent.parent.expenseRequest',
                'supplier', 'comptable:id,name,fonction',
            ])->findOrFail($id),
        ];
    }
}
