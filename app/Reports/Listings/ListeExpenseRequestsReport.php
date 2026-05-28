<?php

namespace App\Reports\Listings;

use App\Models\ExpenseRequest;
use Illuminate\Support\Collection;

class ListeExpenseRequestsReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_expense_requests';
    }

    public function titre(): string
    {
        return 'Liste des expressions du besoin';
    }

    public function description(): string
    {
        return 'Expressions du besoin (chaîne de la dépense) avec statut, type, montant, demandeur.';
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
            ['label' => 'N°', 'key' => 'numero', 'width' => '10%'],
            ['label' => 'Objet', 'key' => 'objet'],
            ['label' => 'Type', 'key' => 'type_label', 'width' => '14%'],
            ['label' => 'Demandeur', 'key' => 'demandeur', 'width' => '14%'],
            ['label' => 'Département', 'key' => 'departement', 'width' => '12%'],
            ['label' => 'Montant', 'key' => 'montant_estime', 'kind' => 'num', 'width' => '10%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = ExpenseRequest::query()
            ->with(['demandeur:id,name', 'departement:id,code']);

        if ($s = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $s);
        }
        if ($t = $this->filtre($filtres, 'type')) {
            $q->where('type_engagement', $t);
        }
        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w->where('numero', 'like', "%{$search}%")->orWhere('objet', 'like', "%{$search}%"));
        }

        return $q->latest()->limit(1000)->get()->map(fn ($r) => [
            'numero' => $r->numero,
            'objet' => $r->objet,
            'type_label' => ExpenseRequest::TYPES_ENGAGEMENT[$r->type_engagement] ?? $r->type_engagement,
            'demandeur' => $r->demandeur?->name ?? '—',
            'departement' => $r->departement?->code ?? '—',
            'montant_estime' => $r->montant_estime,
            'statut' => $r->statut,
        ]);
    }
}
