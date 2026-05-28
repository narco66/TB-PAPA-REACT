<?php

namespace App\Reports\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Models\LiquidationDetail;
use App\Reports\Report;

class FicheLiquidationReport extends Report
{
    public function key(): string
    {
        return 'fiche_liquidation';
    }

    public function titre(): string
    {
        return 'Fiche de liquidation';
    }

    public function description(): string
    {
        return 'Fiche détaillée de liquidation : montant brut, retenues (garantie, fiscale, autres), pénalités, montant net à payer.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.fiche_liquidation';
    }

    public function icone(): string
    {
        return 'Calculator';
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
                ->map(fn ($m) => ['value' => $m->id, 'label' => "{$m->reference} — " . number_format((float) $m->montant, 0, ',', ' ')])->toArray(),
        ]];
    }

    public function donnees(array $filtres = []): array
    {
        $id = $filtres['mouvement_id'] ?? null;
        if (! $id) {
            return ['mouvement' => null];
        }

        $mouvement = BudgetMouvement::with([
            'parent:id,reference,montant', 'supplier', 'ligne:id,budget_ligne_code,libelle',
            'validePar:id,name,fonction', 'ordonnateur:id,name,fonction',
        ])->findOrFail($id);

        $detail = LiquidationDetail::where('budget_mouvement_id', $id)->with('liquidePar:id,name,fonction')->first();

        return ['mouvement' => $mouvement, 'detail' => $detail];
    }
}
