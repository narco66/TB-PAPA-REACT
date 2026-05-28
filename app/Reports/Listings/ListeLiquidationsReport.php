<?php

namespace App\Reports\Listings;

use App\Models\Budget\BudgetMouvement;
use Illuminate\Support\Collection;

class ListeLiquidationsReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_liquidations';
    }

    public function titre(): string
    {
        return 'Liste des liquidations';
    }

    public function description(): string
    {
        return 'Liquidations budgétaires avec montant constaté, bénéficiaire et engagement source.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function permission(): string
    {
        return 'expense.viewAny';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Référence', 'key' => 'reference', 'width' => '12%'],
            ['label' => 'Date', 'key' => 'date', 'width' => '10%'],
            ['label' => 'Engagement source', 'key' => 'engagement', 'width' => '14%'],
            ['label' => 'Bénéficiaire', 'key' => 'beneficiaire'],
            ['label' => 'Ordonnateur', 'key' => 'ordonnateur', 'width' => '14%'],
            ['label' => 'Montant', 'key' => 'montant', 'kind' => 'num', 'width' => '12%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = BudgetMouvement::where('type', 'liquidation')
            ->with(['parent:id,reference', 'supplier:id,libelle', 'ordonnateur:id,name']);

        if ($s = $this->filtre($filtres, 'statut')) {
            $q->where('statut_mouvement', $s);
        }

        return $q->latest('date_mouvement')->limit(1000)->get()->map(fn ($m) => [
            'reference' => $m->reference,
            'date' => $m->date_mouvement?->format('d/m/Y'),
            'engagement' => $m->parent?->reference ?? '—',
            'beneficiaire' => $m->supplier?->libelle ?? $m->beneficiaire_nom ?? '—',
            'ordonnateur' => $m->ordonnateur?->name ?? '—',
            'montant' => (float) $m->montant,
            'statut' => $m->statut_mouvement,
        ]);
    }
}
