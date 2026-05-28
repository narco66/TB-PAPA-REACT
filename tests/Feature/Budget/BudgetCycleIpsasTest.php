<?php

namespace Tests\Feature\Budget;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\User;
use App\Services\Budget\BudgetCycleService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BudgetCycleIpsasTest extends TestCase
{
    use RefreshDatabase;

    protected User $ordonnateur;

    protected User $comptable;

    protected BudgetLigne $ligne;

    protected BudgetCycleService $cycle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->ordonnateur = User::factory()->create(['actif' => true]);
        $this->ordonnateur->assignRole('directeur_technique');

        $this->comptable = User::factory()->create(['actif' => true]);
        $this->comptable->assignRole('controle_financier');

        $exercice = BudgetExercice::create([
            'annee' => 2027,
            'libelle' => 'Exercice 2027',
            'statut' => 'valide',
            'date_ouverture' => now(),
        ]);

        $this->ligne = BudgetLigne::create([
            'exercice_id' => $exercice->id,
            'libelle' => 'Fournitures de bureau',
            'nature' => 'depense',
            'type_budget' => 'fonctionnement',
            'montant_total' => 1_000_000,
        ]);

        $this->cycle = app(BudgetCycleService::class);
    }

    public function test_cycle_complet_engagement_liquidation_ordonnancement_paiement(): void
    {
        $engagement = $this->cycle->engager(
            $this->ligne, 500_000, $this->ordonnateur,
            'Fournisseur Bureau Plus', 'NIF-12345',
        );
        $this->assertSame('engagement', $engagement->type);
        $this->assertSame(500_000.0, $engagement->montant);

        $liquidation = $this->cycle->liquider($engagement, 500_000, $this->comptable);
        $this->assertSame('liquidation', $liquidation->type);

        $ordonnancement = $this->cycle->ordonnancer($liquidation, $this->ordonnateur);
        $this->assertSame('ordonnancement', $ordonnancement->type);

        $paiement = $this->cycle->payer($ordonnancement, $this->comptable, 'virement');
        $this->assertSame('paiement', $paiement->type);

        $this->ligne->refresh();
        $this->assertSame(500_000.0, (float) $this->ligne->montant_engage);
        $this->assertSame(500_000.0, (float) $this->ligne->montant_liquide);
        $this->assertSame(500_000.0, (float) $this->ligne->montant_ordonnance);
        $this->assertSame(500_000.0, (float) $this->ligne->montant_paye);
        $this->assertSame(500_000.0, (float) $this->ligne->montant_disponible);
        $this->assertSame(50.0, (float) $this->ligne->taux_consommation);
    }

    public function test_engagement_refuse_si_disponible_insuffisant(): void
    {
        $this->cycle->engager($this->ligne, 800_000, $this->ordonnateur);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Engagement refusé');

        $this->cycle->engager($this->ligne, 300_000, $this->ordonnateur);
    }

    public function test_liquidation_ne_peut_pas_depasser_engagement(): void
    {
        $engagement = $this->cycle->engager($this->ligne, 500_000, $this->ordonnateur);

        $this->expectException(\InvalidArgumentException::class);
        $this->cycle->liquider($engagement, 600_000, $this->comptable);
    }

    public function test_separation_ordonnateur_comptable_imposee_au_paiement(): void
    {
        $engagement = $this->cycle->engager($this->ligne, 500_000, $this->ordonnateur);
        $liquidation = $this->cycle->liquider($engagement, 500_000, $this->comptable);
        $ordonnancement = $this->cycle->ordonnancer($liquidation, $this->ordonnateur);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Séparation des tâches violée');

        // L'ordonnateur tente de payer son propre ordonnancement → refusé COSO
        $this->cycle->payer($ordonnancement, $this->ordonnateur, 'virement');
    }

    public function test_ordonnancement_unique_par_liquidation(): void
    {
        $engagement = $this->cycle->engager($this->ligne, 500_000, $this->ordonnateur);
        $liquidation = $this->cycle->liquider($engagement, 500_000, $this->comptable);
        $this->cycle->ordonnancer($liquidation, $this->ordonnateur);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('déjà été ordonnancée');

        $this->cycle->ordonnancer($liquidation, $this->ordonnateur);
    }

    public function test_paiement_unique_par_ordonnancement(): void
    {
        $engagement = $this->cycle->engager($this->ligne, 500_000, $this->ordonnateur);
        $liquidation = $this->cycle->liquider($engagement, 500_000, $this->comptable);
        $ordonnancement = $this->cycle->ordonnancer($liquidation, $this->ordonnateur);
        $this->cycle->payer($ordonnancement, $this->comptable, 'virement');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('déjà été payé');

        $this->cycle->payer($ordonnancement, $this->comptable, 'cheque');
    }

    public function test_chaine_mouvements_relie_les_4_etapes(): void
    {
        $engagement = $this->cycle->engager($this->ligne, 100_000, $this->ordonnateur);
        $liquidation = $this->cycle->liquider($engagement, 100_000, $this->comptable);
        $ordonnancement = $this->cycle->ordonnancer($liquidation, $this->ordonnateur);
        $paiement = $this->cycle->payer($ordonnancement, $this->comptable, 'virement');

        $this->assertNull($engagement->parent_mouvement_id);
        $this->assertSame($engagement->id, $liquidation->parent_mouvement_id);
        $this->assertSame($liquidation->id, $ordonnancement->parent_mouvement_id);
        $this->assertSame($ordonnancement->id, $paiement->parent_mouvement_id);

        $chaine = $paiement->chaineCompleter();
        $this->assertCount(4, $chaine);
        $this->assertSame(['engagement', 'liquidation', 'ordonnancement', 'paiement'], $chaine->pluck('type')->all());
    }

    public function test_endpoint_engager_via_http(): void
    {
        $this->actingAs($this->ordonnateur)
            ->post("/budget/lignes/{$this->ligne->id}/engager", [
                'montant' => 200_000,
                'beneficiaire_nom' => 'Test fournisseur',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('budget_mouvements', [
            'ligne_id' => $this->ligne->id,
            'type' => 'engagement',
            'montant' => 200_000,
        ]);
    }

    public function test_endpoint_engager_refuse_sans_permission(): void
    {
        $pointFocal = User::factory()->create(['actif' => true]);
        $pointFocal->assignRole('point_focal');

        $this->actingAs($pointFocal)
            ->post("/budget/lignes/{$this->ligne->id}/engager", ['montant' => 100])
            ->assertForbidden();

        $this->assertDatabaseMissing('budget_mouvements', ['ligne_id' => $this->ligne->id]);
    }

    public function test_disponible_decroit_apres_chaque_engagement(): void
    {
        $this->assertSame(1_000_000.0, $this->cycle->disponiblePour($this->ligne));

        $this->cycle->engager($this->ligne, 300_000, $this->ordonnateur);
        $this->assertSame(700_000.0, $this->cycle->disponiblePour($this->ligne));

        $this->cycle->engager($this->ligne, 400_000, $this->ordonnateur);
        $this->assertSame(300_000.0, $this->cycle->disponiblePour($this->ligne));
    }
}
