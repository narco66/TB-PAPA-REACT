<?php

namespace App\Reports\Expense;

use App\Models\ExpenseRequest;
use App\Reports\Report;

class FicheExpressionBesoinReport extends Report
{
    public function key(): string
    {
        return 'fiche_expression_besoin';
    }

    public function titre(): string
    {
        return 'Fiche d\'expression du besoin';
    }

    public function description(): string
    {
        return "Document institutionnel de l'expression du besoin : objet, justification, montants, fournisseur pressenti, validation hiérarchique.";
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.fiche_expression_besoin';
    }

    public function icone(): string
    {
        return 'Receipt';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'expense_request_id',
                'label' => 'Expression du besoin',
                'type' => 'select',
                'required' => true,
                'options' => ExpenseRequest::latest()->limit(100)->get(['id', 'numero', 'objet'])
                    ->map(fn ($r) => ['value' => $r->id, 'label' => "{$r->numero} — {$r->objet}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $id = $filtres['expense_request_id'] ?? null;
        if (! $id) {
            return ['request' => null];
        }

        $request = ExpenseRequest::with([
            'exercice', 'demandeur:id,name,fonction', 'departement:id,code,libelle',
            'direction:id,code,libelle', 'activite:id,code,libelle', 'tache:id,code,libelle',
            'sourceFinancement:id,code,libelle', 'supplierPressenti',
            'valideurHierarchique:id,name,fonction', 'engagement',
        ])->findOrFail($id);

        return [
            'request' => $request,
            'types_engagement' => ExpenseRequest::TYPES_ENGAGEMENT,
        ];
    }
}
