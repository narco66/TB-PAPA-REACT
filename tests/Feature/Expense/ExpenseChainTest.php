<?php

namespace Tests\Feature\Expense;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\ExpenseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Budget\BudgetCycleService;
use App\Services\Expense\ExpenseRequestService;
use App\Services\Expense\ServiceFaitReceptionService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpenseChainTest extends TestCase
{
    use RefreshDatabase;

    protected User $initiateur;

    protected User $valideur;

    protected User $ordonnateur;

    protected BudgetExercice $exercice;

    protected BudgetLigne $ligne;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->initiateur = User::factory()->create(['actif' => true]);
        $this->initiateur->assignRole('point_focal');

        $this->valideur = User::factory()->create(['actif' => true]);
        $this->valideur->assignRole('directeur_technique');

        $this->ordonnateur = User::factory()->create(['actif' => true]);
        $this->ordonnateur->assignRole('directeur_technique');

        $this->exercice = BudgetExercice::create([
            'annee' => 2027,
            'libelle' => 'Test 2027',
            'statut' => 'valide',
        ]);

        $this->ligne = BudgetLigne::create([
            'exercice_id' => $this->exercice->id,
            'libelle' => 'Fournitures',
            'nature' => 'depense',
            'type_budget' => 'fonctionnement',
            'montant_total' => 10_000_000,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'FOUR-001',
            'libelle' => 'Fournisseur test',
            'type' => 'personne_morale',
            'statut' => 'actif',
        ]);
    }

    public function test_creation_expression_du_besoin(): void
    {
        $service = app(ExpenseRequestService::class);

        $request = $service->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Achat fournitures bureau',
            'justification' => 'Réapprovisionnement annuel',
            'montant_estime' => 500_000,
            'supplier_pressenti_id' => $this->supplier->id,
        ], $this->initiateur);

        $this->assertEquals('brouillon', $request->statut);
        $this->assertEquals($this->initiateur->id, $request->demandeur_id);
        $this->assertStringStartsWith('EB-2027-', $request->numero);
    }

    public function test_workflow_soumission_validation_engagement(): void
    {
        $service = app(ExpenseRequestService::class);

        $request = $service->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test workflow',
            'justification' => 'Test',
            'montant_estime' => 200_000,
            'supplier_pressenti_id' => $this->supplier->id,
        ], $this->initiateur);

        $service->soumettre($request, $this->initiateur);
        $this->assertEquals('soumis', $request->fresh()->statut);

        $service->valider($request->fresh(), $this->valideur, 'OK');
        $request->refresh();
        $this->assertEquals('valide', $request->statut);
        $this->assertEquals($this->valideur->id, $request->valideur_hierarchique_id);

        $mouvement = $service->engager($request, $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligne->id, 'libelle' => 'Imputation', 'montant' => 200_000],
        ]);

        $request->refresh();
        $this->assertEquals('engage', $request->statut);
        $this->assertEquals('engagement', $mouvement->type);
        $this->assertEquals(200_000, $mouvement->montant);
        $this->assertEquals(1, $mouvement->commitmentLines()->count());
    }

    public function test_total_egal_ceeac_plus_ptf(): void
    {
        $service = app(ExpenseRequestService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test',
            'justification' => 'Test',
            'montant_estime' => 1000,
            'montant_estime_ceeac' => 600,
            'montant_estime_ptf' => 500, // 600+500 ≠ 1000
        ], $this->initiateur);
    }

    public function test_rejet_avec_motif(): void
    {
        $service = app(ExpenseRequestService::class);
        $request = $this->creerExpressionSoumise();

        $service->rejeter($request, $this->valideur, 'Pièces manquantes');

        $request->refresh();
        $this->assertEquals('rejete', $request->statut);
        $this->assertEquals('Pièces manquantes', $request->motif_decision);
    }

    public function test_retour_pour_correction(): void
    {
        $service = app(ExpenseRequestService::class);
        $request = $this->creerExpressionSoumise();

        $service->retourner($request, $this->valideur, 'Manque devis');

        $request->refresh();
        $this->assertEquals('retourne_correction', $request->statut);

        // Peut être resoumis
        $service->soumettre($request, $this->initiateur);
        $this->assertEquals('soumis', $request->fresh()->statut);
    }

    public function test_engagement_impossible_si_non_valide(): void
    {
        $service = app(ExpenseRequestService::class);
        $request = $this->creerExpressionSoumise();

        $this->expectException(\RuntimeException::class);
        $service->engager($request, $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligne->id, 'libelle' => 'X', 'montant' => 100],
        ]);
    }

    public function test_certificat_service_fait_et_pv_reception(): void
    {
        $expense = app(ExpenseRequestService::class);
        $sfService = app(ServiceFaitReceptionService::class);

        $request = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test SF',
            'justification' => 'Test',
            'montant_estime' => 500_000,
            'supplier_pressenti_id' => $this->supplier->id,
        ], $this->initiateur);

        $expense->soumettre($request, $this->initiateur);
        $expense->valider($request->fresh(), $this->valideur);

        $engagement = $expense->engager($request->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligne->id, 'libelle' => 'X', 'montant' => 500_000],
        ]);

        $cert = $sfService->creerCertificatServiceFait($engagement, [
            'date_constatation' => now()->toDateString(),
            'description' => 'Livraison fournitures',
            'montant_constate' => 500_000,
            'conformite_qualitative' => 'conforme',
            'conformite_quantitative' => 'conforme',
        ], $this->valideur);

        $this->assertEquals('projet', $cert->statut);
        $this->assertEquals(500_000, $cert->montant_constate);

        $sfService->validerCertificat($cert, $this->ordonnateur);
        $this->assertEquals('valide', $cert->fresh()->statut);

        $pv = $sfService->creerReception($engagement, [
            'date_reception' => now()->toDateString(),
            'type_reception' => 'definitive',
            'nature' => 'biens',
            'montant_recu' => 500_000,
            'conformite' => 'conforme',
        ], $this->initiateur);

        $this->assertEquals('projet', $pv->statut);
        $this->assertStringStartsWith('PV-', $pv->reference);
    }

    public function test_certificat_sf_refuse_montant_superieur_a_engagement(): void
    {
        $expense = app(ExpenseRequestService::class);
        $sfService = app(ServiceFaitReceptionService::class);

        $request = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test',
            'justification' => 'Test',
            'montant_estime' => 100_000,
        ], $this->initiateur);

        $expense->soumettre($request, $this->initiateur);
        $expense->valider($request->fresh(), $this->valideur);

        $engagement = $expense->engager($request->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligne->id, 'libelle' => 'X', 'montant' => 100_000],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $sfService->creerCertificatServiceFait($engagement, [
            'date_constatation' => now()->toDateString(),
            'description' => 'Test',
            'montant_constate' => 200_000, // > engagé
            'conformite_qualitative' => 'conforme',
            'conformite_quantitative' => 'conforme',
        ], $this->valideur);
    }

    public function test_liquidation_detail_avec_retenues(): void
    {
        $expense = app(ExpenseRequestService::class);
        $sfService = app(ServiceFaitReceptionService::class);

        $request = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'travaux',
            'objet' => 'Travaux',
            'justification' => 'Test',
            'montant_estime' => 1_000_000,
        ], $this->initiateur);
        $expense->soumettre($request, $this->initiateur);
        $expense->valider($request->fresh(), $this->valideur);
        $engagement = $expense->engager($request->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligne->id, 'libelle' => 'X', 'montant' => 1_000_000],
        ]);

        $cycle = app(BudgetCycleService::class);
        $liquidation = $cycle->liquider($engagement, 1_000_000, $this->ordonnateur);

        $detail = $sfService->creerDetailLiquidation($liquidation, [
            'montant_brut' => 1_000_000,
            'retenue_garantie' => 50_000, // 5%
            'retenue_fiscale' => 30_000,
            'penalites_retard' => 10_000,
        ], $this->valideur);

        $this->assertEquals(910_000, (float) $detail->montant_net_a_payer);
    }

    public function test_permissions_initiateur_peut_creer_pas_engager(): void
    {
        $this->assertTrue($this->initiateur->can('expense.create'));
        $this->assertFalse($this->initiateur->can('expense.engage'));

        $this->assertTrue($this->ordonnateur->can('expense.engage'));
    }

    public function test_endpoints_http_accessibles(): void
    {
        $this->actingAs($this->initiateur)->get('/expense/requests')->assertOk();
        $this->actingAs($this->initiateur)->get('/expense/requests/create')->assertOk();
        $this->actingAs($this->initiateur)->get('/expense/suppliers')->assertOk();
        $this->actingAs($this->initiateur)->get('/expense/service-fait')->assertOk();
    }

    protected function creerExpressionSoumise(): ExpenseRequest
    {
        $service = app(ExpenseRequestService::class);
        $r = $service->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test',
            'justification' => 'Test',
            'montant_estime' => 100_000,
        ], $this->initiateur);
        $service->soumettre($r, $this->initiateur);

        return $r->fresh();
    }
}
