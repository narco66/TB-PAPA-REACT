<?php

namespace App\Reports\Listings;

use App\Models\Budget\BudgetMouvement;
use Illuminate\Support\Collection;

class ListePaiementsReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_paiements';
    }

    public function titre(): string
    {
        return 'Liste des paiements';
    }

    public function description(): string
    {
        return 'Paiements effectifs (étape finale du cycle IPSAS) avec bénéficiaire, mode et chaîne complète.';
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
            ['label' => 'N° pièce', 'key' => 'numero_piece', 'width' => '12%'],
            ['label' => 'Bénéficiaire', 'key' => 'beneficiaire'],
            ['label' => 'Mode', 'key' => 'mode_paiement', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Comptable', 'key' => 'comptable', 'width' => '14%'],
            ['label' => 'Montant', 'key' => 'montant', 'kind' => 'num', 'width' => '12%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = BudgetMouvement::where('type', 'paiement')
            ->with(['supplier:id,libelle', 'comptable:id,name']);

        if ($s = $this->filtre($filtres, 'statut')) {
            $q->where('statut_mouvement', $s);
        }

        return $q->latest('date_mouvement')->limit(1000)->get()->map(fn ($m) => [
            'reference' => $m->reference,
            'date' => $m->date_mouvement?->format('d/m/Y'),
            'numero_piece' => $m->numero_piece ?? '—',
            'beneficiaire' => $m->supplier?->libelle ?? $m->beneficiaire_nom ?? '—',
            'mode_paiement' => $m->mode_paiement ?? '—',
            'comptable' => $m->comptable?->name ?? '—',
            'montant' => (float) $m->montant,
            'statut' => $m->statut_mouvement,
        ]);
    }
}
