<?php

namespace Tests\Feature\Expense;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Expense\DashboardExpenseStatsService;
use App\Services\Expense\ExpenseRequestService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpenseDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $auditor;

    protected User $ordonnateur;

    protected User $initiateur;

    protected BudgetExercice $exercice;

    protected BudgetLigne $ligne;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->initiateur = User::factory()->create(['actif' => true]);
        $this->initiateur->assignRole('point_focal');

        $this->ordonnateur = User::factory()->create(['actif' => true]);
        $this->ordonnateur->assignRole('directeur_technique');

        $this->auditor = User::factory()->create(['actif' => true]);
        $this->auditor->assignRole('audit_interne');

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
    }

    public function test_dashboard_accessible_aux_utilisateurs_expense(): void
    {
        $this->actingAs($this->initiateur)->get('/expense')->assertOk();
        $this->actingAs($this->ordonnateur)->get('/expense')->assertOk();
        $this->actingAs($this->auditor)->get('/expense')->assertOk();
    }

    public function test_dashboard_stats_kpis_initiaux_zero(): void
    {
        $stats = app(DashboardExpenseStatsService::class)->build($this->exercice->id);

        $this->assertEquals(0, $stats['kpis']['expressions_total']);
        $this->assertEquals(0, $stats['kpis']['montant_engage']);
        $this->assertEquals(0, $stats['kpis']['montant_paye']);
        $this->assertEquals(0, $stats['kpis']['taux_paiement']);
    }

    public function test_dashboard_stats_apres_creation_expression(): void
    {
        $supplier = Supplier::create(['code' => 'F1', 'libelle' => 'Fournisseur', 'type' => 'personne_morale', 'statut' => 'actif']);
        $svc = app(ExpenseRequestService::class);

        $svc->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test',
            'justification' => 'Test',
            'montant_estime' => 100_000,
            'supplier_pressenti_id' => $supplier->id,
        ], $this->initiateur);

        $stats = app(DashboardExpenseStatsService::class)->build($this->exercice->id);

        $this->assertEquals(1, $stats['kpis']['expressions_total']);
        $this->assertEquals(1, $stats['expressions']['brouillon']);
    }

    public function test_dashboard_montre_cycle_complet_engagement(): void
    {
        $supplier = Supplier::create(['code' => 'F1', 'libelle' => 'F', 'type' => 'personne_morale', 'statut' => 'actif']);
        $svc = app(ExpenseRequestService::class);

        $r = $svc->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test',
            'justification' => 'Test',
            'montant_estime' => 500_000,
            'supplier_pressenti_id' => $supplier->id,
        ], $this->initiateur);

        $svc->soumettre($r, $this->initiateur);
        $svc->valider($r->fresh(), $this->ordonnateur);
        $svc->engager($r->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligne->id, 'libelle' => 'X', 'montant' => 500_000],
        ]);

        $stats = app(DashboardExpenseStatsService::class)->build($this->exercice->id);

        $this->assertEquals(1, $stats['kpis']['expressions_engagees']);
        $this->assertEquals(500_000, $stats['kpis']['montant_engage']);
        $this->assertEquals(1, $stats['cycle_ipsas']['engagement']['nb']);
    }
}
