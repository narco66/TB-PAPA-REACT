<?php

namespace App\Reports\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Reports\Report;

class BordereauOrdonnancementReport extends Report
{
    public function key(): string
    {
        return 'bordereau_ordonnancement';
    }

    public function titre(): string
    {
        return 'Bordereau d\'ordonnancement';
    }

    public function description(): string
    {
        return 'État récapitulatif des ordonnancements émis sur une période, à transmettre à la comptabilité.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.bordereau_ordonnancement';
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function icone(): string
    {
        return 'FileText';
    }

    public function permission(): string
    {
        return 'expense.viewAny';
    }

    public function filtres(): array
    {
        return [
            ['key' => 'date_debut', 'label' => 'Date début', 'type' => 'date'],
            ['key' => 'date_fin', 'label' => 'Date fin', 'type' => 'date'],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $q = BudgetMouvement::where('type', 'ordonnancement')
            ->with(['supplier:id,libelle', 'ordonnateur:id,name', 'parent:id,reference']);

        if ($d = $filtres['date_debut'] ?? null) {
            $q->whereDate('date_mouvement', '>=', $d);
        }
        if ($d = $filtres['date_fin'] ?? null) {
            $q->whereDate('date_mouvement', '<=', $d);
        }

        $items = $q->orderBy('date_mouvement')->limit(1000)->get();

        return [
            'items' => $items,
            'total' => (float) $items->sum('montant'),
            'periode' => [
                'debut' => $filtres['date_debut'] ?? null,
                'fin' => $filtres['date_fin'] ?? null,
            ],
        ];
    }
}
