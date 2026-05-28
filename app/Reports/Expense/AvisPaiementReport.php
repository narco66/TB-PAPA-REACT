<?php

namespace App\Reports\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Reports\Report;

class AvisPaiementReport extends Report
{
    public function key(): string
    {
        return 'avis_paiement';
    }

    public function titre(): string
    {
        return 'Avis de paiement';
    }

    public function description(): string
    {
        return 'Avis de paiement adressé au bénéficiaire confirmant le décaissement effectué.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.avis_paiement';
    }

    public function icone(): string
    {
        return 'Banknote';
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
                'parent:id,reference', 'parent.parent:id,reference',
                'parent.parent.parent:id,reference,expense_request_id',
                'parent.parent.parent.expenseRequest:id,numero,objet',
                'supplier', 'comptable:id,name,fonction', 'ordonnateur:id,name,fonction',
            ])->findOrFail($id),
        ];
    }
}
