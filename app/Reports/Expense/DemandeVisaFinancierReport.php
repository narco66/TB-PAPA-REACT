<?php

namespace App\Reports\Expense;

use App\Models\ExpenseRequest;
use App\Reports\Report;

class DemandeVisaFinancierReport extends Report
{
    public function key(): string
    {
        return 'demande_visa_financier';
    }

    public function titre(): string
    {
        return 'Demande de visa financier';
    }

    public function description(): string
    {
        return "Demande adressée au Contrôle financier pour visa préalable à l'engagement.";
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.demande_visa_financier';
    }

    public function icone(): string
    {
        return 'Send';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [[
            'key' => 'expense_request_id',
            'label' => 'Expression du besoin',
            'type' => 'select', 'required' => true,
            'options' => ExpenseRequest::whereIn('statut', ['soumis', 'en_validation_hierarchique', 'valide'])
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
                'exercice', 'demandeur:id,name,fonction', 'departement', 'direction',
                'sourceFinancement', 'supplierPressenti',
            ])->findOrFail($id),
            'types_engagement' => ExpenseRequest::TYPES_ENGAGEMENT,
        ];
    }
}
