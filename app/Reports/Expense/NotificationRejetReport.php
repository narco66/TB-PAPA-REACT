<?php

namespace App\Reports\Expense;

use App\Models\ExpenseRequest;
use App\Reports\Report;

class NotificationRejetReport extends Report
{
    public function key(): string
    {
        return 'notification_rejet';
    }

    public function titre(): string
    {
        return 'Notification de rejet';
    }

    public function description(): string
    {
        return "Notification officielle de rejet d'une expression du besoin avec motif détaillé.";
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.notification_rejet';
    }

    public function icone(): string
    {
        return 'XCircle';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [[
            'key' => 'expense_request_id',
            'label' => 'Expression rejetée',
            'type' => 'select', 'required' => true,
            'options' => ExpenseRequest::where('statut', 'rejete')
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
                'demandeur:id,name,fonction', 'departement',
                'valideurHierarchique:id,name,fonction',
            ])->findOrFail($id),
            'types_engagement' => ExpenseRequest::TYPES_ENGAGEMENT,
        ];
    }
}
