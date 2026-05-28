<?php

namespace App\Reports\Budget;

use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Reports\Report;

class BudgetConsolideReport extends Report
{
    public function key(): string
    {
        return 'budget_consolide';
    }

    public function titre(): string
    {
        return 'Budget consolidé annuel — CEEAC';
    }

    public function description(): string
    {
        return 'Vue consolidée du budget : recettes, dépenses par type (fonctionnement / investissement / équipement), CEEAC-EM vs PTF, comparatif N-1.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.budget.consolide';
    }

    public function icone(): string
    {
        return 'PiggyBank';
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'exercice_id',
                'label' => 'Exercice budgétaire',
                'type' => 'select',
                'required' => true,
                'options' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle'])
                    ->map(fn ($e) => ['value' => $e->id, 'label' => "Exercice {$e->annee}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $exerciceId = $filtres['exercice_id'] ?? BudgetExercice::orderByDesc('annee')->value('id');
        $exercice = BudgetExercice::findOrFail($exerciceId);

        $recettes = BudgetLigne::where('exercice_id', $exerciceId)
            ->where('nature', 'recette')
            ->orderBy('titre_code')->orderBy('code_action')
            ->get();

        $depenses = BudgetLigne::where('exercice_id', $exerciceId)
            ->where('nature', 'depense')
            ->orderBy('type_budget')
            ->orderBy('titre_code')
            ->orderBy('code_action')
            ->get();

        $totaux = [
            'recettes' => $recettes->sum('montant_total'),
            'recettes_internes' => $recettes->where('type_budget', 'recette_interne')->sum('montant_total'),
            'recettes_externes' => $recettes->where('type_budget', 'recette_externe')->sum('montant_total'),
            'depenses' => $depenses->sum('montant_total'),
            'depenses_ceeac_em' => $depenses->sum('montant_ceeac_em'),
            'depenses_ptf' => $depenses->sum('montant_ptf'),
            'fonctionnement' => $depenses->where('type_budget', 'fonctionnement')->sum('montant_total'),
            'investissement' => $depenses->where('type_budget', 'investissement')->sum('montant_total'),
            'equipement' => $depenses->where('type_budget', 'equipement')->sum('montant_total'),
            'engage' => $depenses->sum('montant_engage'),
            'paye' => $depenses->sum('montant_paye'),
            'budget_n_1' => $depenses->sum('budget_annee_precedente'),
            'realisation_n_1' => $depenses->sum('realisation_annee_precedente'),
        ];

        $depensesParType = $depenses->groupBy('type_budget');

        return compact('exercice', 'recettes', 'depenses', 'totaux', 'depensesParType');
    }
}
