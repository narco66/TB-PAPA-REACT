<?php

namespace App\Reports\Expense;

use App\Models\ExpenseRequest;
use App\Reports\Report;

class NotificationRetourReport extends Report
{
    public function key(): string
    {
        return 'notification_retour';
    }

    public function titre(): string
    {
        return 'Notification de retour pour correction';
    }

    public function description(): string
    {
        return 'Notification adressée au demandeur pour correction de son expression du besoin avant resoumission.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.notification_retour';
    }

    public function icone(): string
    {
        return 'CornerUpLeft';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [[
            'key' => 'expense_request_id',
            'label' => 'Expression retournée',
            'type' => 'select', 'required' => true,
            'options' => ExpenseRequest::whereIn('statut', ['retourne_correction', 'soumis'])
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
