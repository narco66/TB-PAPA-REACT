<?php

namespace App\Reports\Expense;

use App\Models\ExpenseRequest;
use App\Reports\Report;

class VisaFinancierReport extends Report
{
    public function key(): string
    {
        return 'visa_financier';
    }

    public function titre(): string
    {
        return 'Visa financier';
    }

    public function description(): string
    {
        return 'Document officiel attestant le visa financier accordé à une expression du besoin validée.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.visa_financier';
    }

    public function icone(): string
    {
        return 'CheckCircle2';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [[
            'key' => 'expense_request_id',
            'label' => 'Expression validée',
            'type' => 'select', 'required' => true,
            'options' => ExpenseRequest::whereIn('statut', ['valide', 'engage'])
                ->latest()->limit(100)->get(['id', 'numero', 'objet'])
                ->map(fn ($r) => ['value' => $r->id, 'label' => "{$r->numero} — {$r->objet}"])->toArray(),
        ]];
    }

    public function donnees(array $filtres = []): array
    {
        $id = $filtres['expense_request_id'] ?? null;
        if (! $id) {
            return ['request' => null];
        }

        return [
            'request' => ExpenseRequest::with([
                'exercice', 'demandeur:id,name,fonction', 'departement', 'sourceFinancement',
                'supplierPressenti', 'valideurHierarchique:id,name,fonction', 'engagement',
            ])->findOrFail($id),
            'types_engagement' => ExpenseRequest::TYPES_ENGAGEMENT,
        ];
    }
}
