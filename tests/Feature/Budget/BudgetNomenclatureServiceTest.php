<?php

namespace Tests\Feature\Budget;

use App\Models\Budget\BudgetExercice;
use App\Services\Budget\BudgetNomenclatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetNomenclatureServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_decompose_un_code_budgetaire_officiel(): void
    {
        $parts = app(BudgetNomenclatureService::class)->decomposerCode('60111');

        $this->assertSame('60', $parts['chapitre_code']);
        $this->assertSame('601', $parts['article_code']);
        $this->assertSame('6011', $parts['paragraphe_code']);
        $this->assertSame('60111', $parts['budget_ligne_code']);
    }

    public function test_normalise_et_cree_les_referentiels_budgetaires(): void
    {
        $exercice = BudgetExercice::create([
            'annee' => 2026,
            'libelle' => 'Exercice 2026',
            'statut' => 'brouillon',
        ]);

        $data = app(BudgetNomenclatureService::class)->normaliserLigne([
            'code_action' => '60111',
            'libelle' => 'Fourniture de bureau',
            'montant_total' => 1000,
            'montant_ceeac_em' => 700,
            'montant_ptf' => 300,
        ], $exercice);

        $this->assertSame('60', $data['chapitre_code']);
        $this->assertSame('601', $data['article_code']);
        $this->assertSame('6011', $data['paragraphe_code']);
        $this->assertSame('60111', $data['budget_ligne_code']);
        $this->assertDatabaseHas('budget_chapitres', ['exercice_id' => $exercice->id, 'code' => '60']);
        $this->assertDatabaseHas('budget_articles', ['exercice_id' => $exercice->id, 'code' => '601']);
        $this->assertDatabaseHas('budget_paragraphes', ['exercice_id' => $exercice->id, 'code' => '6011']);
    }

    public function test_rejette_un_total_different_de_ceeac_plus_ptf(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $exercice = BudgetExercice::create([
            'annee' => 2026,
            'libelle' => 'Exercice 2026',
            'statut' => 'brouillon',
        ]);

        app(BudgetNomenclatureService::class)->normaliserLigne([
            'code_action' => '60111',
            'libelle' => 'Fourniture de bureau',
            'montant_total' => 1000,
            'montant_ceeac_em' => 700,
            'montant_ptf' => 250,
        ], $exercice);
    }
}
