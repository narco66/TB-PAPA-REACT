<?php

namespace App\Reports\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Models\LiquidationDetail;
use App\Reports\Report;

class DecomptePaiementReport extends Report
{
    public function key(): string
    {
        return 'decompte_paiement';
    }

    public function titre(): string
    {
        return 'Décompte de paiement';
    }

    public function description(): string
    {
        return 'État détaillé du décompte de paiement à partir de la liquidation : montant brut, retenues, montant net.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.decompte_paiement';
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
        return [[
            'key' => 'mouvement_id',
            'label' => 'Liquidation',
            'type' => 'select', 'required' => true,
            'options' => BudgetMouvement::where('type', 'liquidation')
                ->latest()->limit(100)->get(['id', 'reference', 'montant'])
                ->map(fn ($m) => ['value' => $m->id, 'label' => "{$m->reference}"])->toArray(),
        ]];
    }

    public function donnees(array $filtres = []): array
    {
        $id = $filtres['mouvement_id'] ?? null;
        if (! $id) {
            return ['mouvement' => null];
        }

        $mouvement = BudgetMouvement::with([
            'parent:id,reference,montant', 'parent.expenseRequest:id,numero,objet',
            'supplier', 'ligne:id,budget_ligne_code,libelle',
            'validePar:id,name,fonction',
        ])->findOrFail($id);

        $detail = LiquidationDetail::where('budget_mouvement_id', $id)->first();

        return ['mouvement' => $mouvement, 'detail' => $detail];
    }
}
