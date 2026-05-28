<?php

namespace App\Reports\Budget;

use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetSourceFinancement;
use App\Reports\Report;

class BudgetLignesReport extends Report
{
    public function key(): string
    {
        return 'budget_lignes';
    }

    public function titre(): string
    {
        return 'Lignes budgétaires détaillées';
    }

    public function description(): string
    {
        return 'Export filtré des lignes budgétaires avec montants CEEAC-EM / PTF, exécution et rattachement RBM.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.budget.lignes';
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
                'options' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle'])
                    ->map(fn ($e) => ['value' => $e->id, 'label' => "Exercice {$e->annee}"])->toArray(),
            ],
            [
                'key' => 'nature',
                'label' => 'Nature',
                'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Toutes'],
                    ['value' => 'recette', 'label' => 'Recettes'],
                    ['value' => 'depense', 'label' => 'Dépenses'],
                ],
            ],
            [
                'key' => 'type_budget',
                'label' => 'Type de budget',
                'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => 'Tous'],
                    ['value' => 'recette_interne', 'label' => 'Recettes internes'],
                    ['value' => 'recette_externe', 'label' => 'Recettes externes'],
                    ['value' => 'fonctionnement', 'label' => 'Fonctionnement'],
                    ['value' => 'investissement', 'label' => 'Investissement'],
                    ['value' => 'equipement', 'label' => 'Équipement'],
                ],
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        // Construit la requête en respectant les mêmes filtres que la page index
        $query = BudgetLigne::query()->with([
            'exercice:id,annee,libelle',
            'source:id,code,libelle',
            'axe:id,code,libelle',
            'produit:id,code,libelle',
            'departement:id,code',
        ]);

        $exerciceId = $filtres['exercice_id'] ?? null;
        if ($exerciceId) {
            $query->where('exercice_id', $exerciceId);
        }
        if (! empty($filtres['q'])) {
            $q = $filtres['q'];
            $query->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$q}%")
                ->orWhere('code_action', 'like', "%{$q}%")
                ->orWhere('paragraphe_code', 'like', "%{$q}%"),
            );
        }
        if (! empty($filtres['nature'])) {
            $query->where('nature', $filtres['nature']);
        }
        if (! empty($filtres['type_budget'])) {
            $query->where('type_budget', $filtres['type_budget']);
        }
        if (! empty($filtres['pilier'])) {
            $query->where('pilier', (int) $filtres['pilier']);
        }
        if (! empty($filtres['axe_id'])) {
            $query->where('axe_id', (int) $filtres['axe_id']);
        }
        if (! empty($filtres['source_financement_id'])) {
            $query->where('source_financement_id', (int) $filtres['source_financement_id']);
        }

        $lignes = $query->orderBy('titre_code')->orderBy('chapitre_code')
            ->orderBy('article_code')->orderBy('code_action')
            ->limit(2000) // garde-fou pour éviter PDFs énormes
            ->get();

        $totaux = [
            'count' => $lignes->count(),
            'total' => (float) $lignes->sum('montant_total'),
            'ceeac_em' => (float) $lignes->sum('montant_ceeac_em'),
            'ptf' => (float) $lignes->sum('montant_ptf'),
            'engage' => (float) $lignes->sum('montant_engage'),
            'paye' => (float) $lignes->sum('montant_paye'),
        ];

        // Contexte de l'export pour l'en-tête
        $exercice = $exerciceId ? BudgetExercice::find($exerciceId) : null;
        $axe = ! empty($filtres['axe_id']) ? Axe::find($filtres['axe_id']) : null;
        $source = ! empty($filtres['source_financement_id'])
            ? BudgetSourceFinancement::find($filtres['source_financement_id'])
            : null;

        return [
            'lignes' => $lignes,
            'totaux' => $totaux,
            'exercice' => $exercice,
            'axe' => $axe,
            'source' => $source,
            'filtresAppliques' => $filtres,
        ];
    }
}
