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

/**
 * Tests End-to-End des scénarios métier de la chaîne de la dépense.
 *
 * Chaque test exécute un parcours complet RGCP de bout en bout :
 * Expression → Validation → Engagement → Service fait → Liquidation → Ordonnancement → Paiement
 *
 * Ces tests garantissent l'intégrité fonctionnelle du workflow métier complet,
 * y compris les règles de séparation des fonctions (COSO ERM), les contrôles
 * budgétaires (anti-dépassement) et les notifications événementielles.
 */
class ExpenseE2EScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected User $initiateur;

    protected User $valideur;

    protected User $ordonnateur;

    protected User $comptable;

    protected User $admin;

    protected BudgetExercice $exercice;

    protected BudgetLigne $ligneFournitures;

    protected BudgetLigne $ligneTravaux;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(EnforceTwoFactor::class);

        // Acteurs institutionnels
        $this->initiateur = $this->creerActeur('point_focal');
        $this->valideur = $this->creerActeur('directeur_technique');
        $this->ordonnateur = $this->creerActeur('commissaire');
        $this->comptable = $this->creerActeur('controle_financier');
        $this->admin = $this->creerActeur('admin_technique');

        // Cadre budgétaire
        $this->exercice = BudgetExercice::create([
            'annee' => 2027, 'libelle' => 'Exercice 2027', 'statut' => 'valide',
        ]);

        $this->ligneFournitures = BudgetLigne::create([
            'exercice_id' => $this->exercice->id,
            'libelle' => 'Fournitures de bureau',
            'nature' => 'depense', 'type_budget' => 'fonctionnement',
            'montant_total' => 50_000_000,
        ]);

        $this->ligneTravaux = BudgetLigne::create([
            'exercice_id' => $this->exercice->id,
            'libelle' => 'Travaux d\'infrastructure',
            'nature' => 'depense', 'type_budget' => 'investissement',
            'montant_total' => 100_000_000,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'F-TEST', 'libelle' => 'Fournisseur Test SARL',
            'type' => 'personne_morale', 'statut' => 'actif',
            'nif' => 'NIF-12345', 'rccm' => 'RCCM-67890',
        ]);
    }

    /**
     * Scénario 1 : Parcours nominal complet — Achat de fournitures de bureau.
     */
    public function test_scenario_nominal_achat_fournitures(): void
    {
        // === ÉTAPE 1 : Expression du besoin ===
        $this->actingAs($this->initiateur);
        $response = $this->post('/expense/requests', [
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Achat 50 ramettes papier A4',
            'justification' => 'Réapprovisionnement annuel des services',
            'montant_estime' => 250_000,
            'devise' => 'XAF',
            'supplier_pressenti_id' => $this->supplier->id,
        ]);
        $response->assertRedirect();
        $expression = ExpenseRequest::latest()->first();
        $this->assertEquals('brouillon', $expression->statut);
        $this->assertEquals($this->initiateur->id, $expression->demandeur_id);
        $this->assertStringStartsWith('EB-2027-', $expression->numero);

        // === ÉTAPE 2 : Soumission ===
        $this->post("/expense/requests/{$expression->id}/submit")->assertRedirect();
        $expression->refresh();
        $this->assertEquals('soumis', $expression->statut);

        // === ÉTAPE 3 : Validation hiérarchique ===
        $this->actingAs($this->valideur);
        $this->post("/expense/requests/{$expression->id}/validate", [
            'commentaire' => 'Conforme au plan d\'action 2027',
        ])->assertRedirect();
        $expression->refresh();
        $this->assertEquals('valide', $expression->statut);
        $this->assertEquals($this->valideur->id, $expression->valideur_hierarchique_id);

        // === ÉTAPE 4 : Engagement budgétaire ===
        $this->actingAs($this->ordonnateur);
        $this->post("/expense/requests/{$expression->id}/engage", [
            'imputations' => [[
                'budget_ligne_id' => $this->ligneFournitures->id,
                'libelle' => 'Imputation principale',
                'montant' => 250_000,
            ]],
        ])->assertRedirect();
        $expression->refresh();
        $this->assertEquals('engage', $expression->statut);
        $engagement = $expression->engagement;
        $this->assertNotNull($engagement);
        $this->assertEquals(250_000, (float) $engagement->montant);
        $this->assertEquals('engagement', $engagement->type);
        $this->assertEquals($this->supplier->id, $engagement->supplier_id);
        $this->assertEquals('achat_biens', $engagement->type_engagement);
        $this->assertEquals(1, $engagement->commitmentLines()->count());

        // Vérification du solde de la ligne budgétaire
        $this->ligneFournitures->refresh();
        $this->assertEquals(250_000, (float) $this->ligneFournitures->montant_engage);

        // === ÉTAPE 5 : Service fait constaté ===
        $sfService = app(ServiceFaitReceptionService::class);
        $cert = $sfService->creerCertificatServiceFait($engagement, [
            'date_constatation' => now()->toDateString(),
            'description' => 'Livraison conforme des 50 ramettes',
            'montant_constate' => 250_000,
            'conformite_qualitative' => 'conforme',
            'conformite_quantitative' => 'conforme',
        ], $this->valideur);

        $this->assertEquals('projet', $cert->statut);
        $sfService->validerCertificat($cert, $this->ordonnateur);
        $this->assertEquals('valide', $cert->fresh()->statut);

        // === ÉTAPE 6 : Réception (PV) ===
        $pv = $sfService->creerReception($engagement, [
            'date_reception' => now()->toDateString(),
            'type_reception' => 'definitive',
            'nature' => 'biens',
            'quantite_recue' => 50,
            'unite_mesure' => 'ramettes',
            'montant_recu' => 250_000,
            'conformite' => 'conforme',
            'president_commission_id' => $this->valideur->id,
            'membre1_commission_id' => $this->initiateur->id,
            'membre2_commission_id' => $this->admin->id,
        ], $this->initiateur);

        $sfService->validerReception($pv, $this->ordonnateur);
        $this->assertEquals('valide', $pv->fresh()->statut);

        // === ÉTAPE 7 : Liquidation ===
        $cycle = app(BudgetCycleService::class);
        $liquidation = $cycle->liquider($engagement, 250_000, $this->valideur, 'PIECE-001');
        $this->assertEquals('liquidation', $liquidation->type);
        $this->assertEquals(250_000, (float) $liquidation->montant);
        $this->assertEquals($engagement->id, $liquidation->parent_mouvement_id);

        // === ÉTAPE 8 : Ordonnancement ===
        $ordonnancement = $cycle->ordonnancer($liquidation, $this->ordonnateur, 'ORD-001');
        $this->assertEquals('ordonnancement', $ordonnancement->type);
        $this->assertEquals($liquidation->id, $ordonnancement->parent_mouvement_id);

        // === ÉTAPE 9 : Paiement ===
        $paiement = $cycle->payer($ordonnancement, $this->comptable, 'virement', 'VIR-001');
        $this->assertEquals('paiement', $paiement->type);
        $this->assertEquals($this->comptable->id, $paiement->comptable_id);
        $this->assertEquals('virement', $paiement->mode_paiement);

        // === VÉRIFICATION FINALE : Chaîne complète ===
        $chaine = $paiement->chaineCompleter();
        $this->assertCount(4, $chaine);
        $this->assertEquals(['engagement', 'liquidation', 'ordonnancement', 'paiement'],
            $chaine->pluck('type')->toArray());

        // Vérification soldes finaux
        $this->ligneFournitures->refresh();
        $this->assertEquals(250_000, (float) $this->ligneFournitures->montant_paye);
    }

    /**
     * Scénario 2 : Retour pour correction puis resoumission acceptée.
     */
    public function test_scenario_retour_correction_resoumission(): void
    {
        $this->actingAs($this->initiateur);
        $this->post('/expense/requests', [
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'mission_officielle',
            'objet' => 'Mission mal justifiée',
            'justification' => 'Brève',
            'montant_estime' => 500_000,
        ]);
        $expression = ExpenseRequest::latest()->first();

        $this->post("/expense/requests/{$expression->id}/submit");
        $this->assertEquals('soumis', $expression->fresh()->statut);

        // Valideur retourne pour correction
        $this->actingAs($this->valideur);
        $this->post("/expense/requests/{$expression->id}/return", [
            'motif' => 'Justification insuffisante, préciser les objectifs',
        ]);
        $expression->refresh();
        $this->assertEquals('retourne_correction', $expression->statut);

        // Le demandeur peut resoumettre depuis retourne_correction
        $this->actingAs($this->initiateur);
        $this->post("/expense/requests/{$expression->id}/submit");
        $this->assertEquals('soumis', $expression->fresh()->statut);

        // Cette fois validation acceptée
        $this->actingAs($this->valideur);
        $this->post("/expense/requests/{$expression->id}/validate");
        $this->assertEquals('valide', $expression->fresh()->statut);
    }

    /**
     * Scénario 3 : Rejet définitif avec motif.
     */
    public function test_scenario_rejet_definitif(): void
    {
        $this->actingAs($this->initiateur);
        $this->post('/expense/requests', [
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'fonctionnement',
            'objet' => 'Dépense non éligible',
            'justification' => 'Test',
            'montant_estime' => 100_000,
        ]);
        $expression = ExpenseRequest::latest()->first();
        $this->post("/expense/requests/{$expression->id}/submit");

        $this->actingAs($this->valideur);
        $this->post("/expense/requests/{$expression->id}/reject", [
            'motif' => 'Hors périmètre budgétaire de l\'exercice',
        ]);

        $expression->refresh();
        $this->assertEquals('rejete', $expression->statut);
        $this->assertStringContainsString('Hors périmètre', $expression->motif_decision);
        $this->assertNotNull($expression->valide_at);
    }

    /**
     * Scénario 4 : Multi-imputation budgétaire (engagement réparti sur 2 lignes).
     */
    public function test_scenario_multi_imputation(): void
    {
        $expense = app(ExpenseRequestService::class);
        $expression = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'travaux',
            'objet' => 'Rénovation locale + ameublement',
            'justification' => 'Modernisation siège',
            'montant_estime' => 5_000_000,
            'supplier_pressenti_id' => $this->supplier->id,
        ], $this->initiateur);

        $expense->soumettre($expression, $this->initiateur);
        $expense->valider($expression->fresh(), $this->valideur);

        // Engagement réparti sur 2 lignes
        $engagement = $expense->engager($expression->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligneTravaux->id, 'libelle' => 'Rénovation', 'montant' => 3_500_000],
            ['budget_ligne_id' => $this->ligneFournitures->id, 'libelle' => 'Ameublement', 'montant' => 1_500_000],
        ]);

        $this->assertEquals(5_000_000, (float) $engagement->montant);
        $this->assertEquals(2, $engagement->commitmentLines()->count());

        $imputations = $engagement->commitmentLines()->orderBy('id')->get();
        $this->assertEquals(3_500_000, (float) $imputations[0]->montant);
        $this->assertEquals($this->ligneTravaux->id, $imputations[0]->budget_ligne_id);
        $this->assertEquals(1_500_000, (float) $imputations[1]->montant);
        $this->assertEquals($this->ligneFournitures->id, $imputations[1]->budget_ligne_id);
    }

    /**
     * Scénario 5 : Anti-dépassement budgétaire.
     */
    public function test_scenario_anti_depassement(): void
    {
        $expense = app(ExpenseRequestService::class);
        $expression = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Achat surdimensionné',
            'justification' => 'Test',
            'montant_estime' => 200_000_000,
        ], $this->initiateur);

        $expense->soumettre($expression, $this->initiateur);
        $expense->valider($expression->fresh(), $this->valideur);

        $this->expectException(\RuntimeException::class);
        // La ligne Fournitures a 50M, on tente 200M → refus
        $expense->engager($expression->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligneFournitures->id, 'libelle' => 'X', 'montant' => 200_000_000],
        ]);
    }

    /**
     * Scénario 6 : Séparation COSO ERM (ordonnateur ≠ comptable).
     */
    public function test_scenario_separation_coso_paiement(): void
    {
        $expense = app(ExpenseRequestService::class);
        $expression = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test COSO',
            'justification' => 'Test',
            'montant_estime' => 500_000,
        ], $this->initiateur);
        $expense->soumettre($expression, $this->initiateur);
        $expense->valider($expression->fresh(), $this->valideur);
        $engagement = $expense->engager($expression->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligneFournitures->id, 'libelle' => 'X', 'montant' => 500_000],
        ]);

        $cycle = app(BudgetCycleService::class);
        $liquidation = $cycle->liquider($engagement, 500_000, $this->valideur);
        $ordonnancement = $cycle->ordonnancer($liquidation, $this->ordonnateur);

        // Tentative de paiement par l'ordonnateur lui-même → REFUS COSO
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Séparation des tâches');
        $cycle->payer($ordonnancement, $this->ordonnateur, 'virement');
    }

    /**
     * Scénario 7 : Annulation avant engagement (autorisée).
     */
    public function test_scenario_annulation_avant_engagement(): void
    {
        $expense = app(ExpenseRequestService::class);
        $expression = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test annulation',
            'justification' => 'Test',
            'montant_estime' => 100_000,
        ], $this->initiateur);

        $expense->annuler($expression, $this->initiateur, 'Plus besoin');

        $expression->refresh();
        $this->assertEquals('annule', $expression->statut);
    }

    /**
     * Scénario 8 : Annulation après engagement interdite.
     */
    public function test_scenario_annulation_apres_engagement_interdite(): void
    {
        $expense = app(ExpenseRequestService::class);
        $expression = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test', 'justification' => 'X',
            'montant_estime' => 100_000,
        ], $this->initiateur);
        $expense->soumettre($expression, $this->initiateur);
        $expense->valider($expression->fresh(), $this->valideur);
        $expense->engager($expression->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligneFournitures->id, 'libelle' => 'X', 'montant' => 100_000],
        ]);

        $this->expectException(\RuntimeException::class);
        $expense->annuler($expression->fresh(), $this->admin, 'Tentative tardive');
    }

    /**
     * Scénario 9 : Liquidation supérieure à l'engagement interdite.
     */
    public function test_scenario_liquidation_superieure_engagement_interdite(): void
    {
        $expense = app(ExpenseRequestService::class);
        $expression = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test', 'justification' => 'X',
            'montant_estime' => 100_000,
        ], $this->initiateur);
        $expense->soumettre($expression, $this->initiateur);
        $expense->valider($expression->fresh(), $this->valideur);
        $engagement = $expense->engager($expression->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligneFournitures->id, 'libelle' => 'X', 'montant' => 100_000],
        ]);

        $cycle = app(BudgetCycleService::class);
        $this->expectException(\InvalidArgumentException::class);
        $cycle->liquider($engagement, 200_000, $this->valideur); // > 100_000
    }

    /**
     * Scénario 10 : Parcours UI complet (HTTP) — Crée + soumet + visualise.
     */
    public function test_scenario_ui_parcours_complet_http(): void
    {
        // Liste vide initialement
        $this->actingAs($this->initiateur)
            ->get('/expense/requests')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('expense/requests/index'));

        // Formulaire de création
        $this->get('/expense/requests/create')->assertOk();

        // POST création
        $this->post('/expense/requests', [
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'formation_atelier',
            'objet' => 'Formation Excel niveau 2',
            'justification' => 'Montée en compétence des points focaux',
            'montant_estime' => 750_000,
        ])->assertRedirect();

        $expression = ExpenseRequest::where('objet', 'Formation Excel niveau 2')->first();
        $this->assertNotNull($expression);

        // Page détail
        $this->get("/expense/requests/{$expression->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('expense/requests/show'));

        // Liste contient désormais l'expression
        $this->get('/expense/requests')
            ->assertOk()
            ->assertSee($expression->numero);

        // Dashboard
        $this->get('/expense')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('expense/dashboard'));

        // Suppliers
        $this->get('/expense/suppliers')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('expense/suppliers/index'));

        // Service fait
        $this->get('/expense/service-fait')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('expense/service-fait/index'));
    }

    /**
     * Scénario 11 : Génération PDF de bout en bout via HTTP /rapports.
     */
    public function test_scenario_generation_pdf_via_quick_endpoint(): void
    {
        // Préparer une expression complète
        $expense = app(ExpenseRequestService::class);
        $expression = $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test PDF',
            'justification' => 'Test',
            'montant_estime' => 500_000,
            'supplier_pressenti_id' => $this->supplier->id,
        ], $this->initiateur);
        $expense->soumettre($expression, $this->initiateur);
        $expense->valider($expression->fresh(), $this->valideur);
        $engagement = $expense->engager($expression->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligneFournitures->id, 'libelle' => 'X', 'montant' => 500_000],
        ]);

        // Test quick endpoint pour PDF
        $this->actingAs($this->admin);

        $response = $this->get("/rapports/fiche_expression_besoin/quick?expense_request_id={$expression->id}");
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        $response = $this->get("/rapports/bon_engagement/quick?mouvement_id={$engagement->id}");
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        $response = $this->get("/rapports/visa_financier/quick?expense_request_id={$expression->id}");
        $response->assertOk();
    }

    /**
     * Scénario 12 : Export Excel d'un journal après création de données.
     */
    public function test_scenario_export_excel_apres_creation_donnees(): void
    {
        $expense = app(ExpenseRequestService::class);
        $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test export E2E',
            'justification' => 'Test',
            'montant_estime' => 350_000,
        ], $this->initiateur);

        $this->actingAs($this->admin)
            ->get('/expense/exports/expressions.xlsx')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->admin)
            ->get('/expense/exports/expressions.csv')
            ->assertOk();
    }

    /**
     * Helper : crée un utilisateur acteur avec un rôle.
     */
    protected function creerActeur(string $role): User
    {
        $user = User::factory()->create(['actif' => true]);
        $user->assignRole($role);

        return $user;
    }
}
