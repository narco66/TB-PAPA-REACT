<?php

namespace App\Reports\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Reports\Report;

class OrdonnancePaiementReport extends Report
{
    public function key(): string
    {
        return 'ordonnance_paiement';
    }

    public function titre(): string
    {
        return 'Ordonnance de paiement';
    }

    public function description(): string
    {
        return "Ordre de payer émis par l'ordonnateur, transmis au comptable pour décaissement.";
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.ordonnance_paiement';
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
        return [[
            'key' => 'mouvement_id',
            'label' => 'Ordonnancement',
            'type' => 'select', 'required' => true,
            'options' => BudgetMouvement::where('type', 'ordonnancement')
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
                'parent:id,reference', 'parent.parent:id,reference,expense_request_id',
                'parent.parent.expenseRequest:id,numero,objet',
                'supplier', 'ligne:id,budget_ligne_code,libelle',
                'ordonnateur:id,name,fonction', 'comptable:id,name,fonction',
            ])->findOrFail($id),
        ];
    }
}
