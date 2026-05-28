<?php

namespace Tests\Feature\Expense;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Expense\ExpenseJournalExportService;
use App\Services\Expense\ExpenseRequestService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpenseExportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $initiateur;

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

        $this->ordonnateur = User::factory()->create(['actif' => true]);
        $this->ordonnateur->assignRole('directeur_technique');

        $this->exercice = BudgetExercice::create(['annee' => 2027, 'libelle' => 'X', 'statut' => 'valide']);
        $this->ligne = BudgetLigne::create([
            'exercice_id' => $this->exercice->id, 'libelle' => 'F', 'nature' => 'depense',
            'type_budget' => 'fonctionnement', 'montant_total' => 10_000_000,
        ]);
        $this->supplier = Supplier::create(['code' => 'F', 'libelle' => 'F', 'type' => 'personne_morale', 'statut' => 'actif']);
    }

    public function test_export_xlsx_journaux_genere_fichier(): void
    {
        $svc = app(ExpenseJournalExportService::class);

        foreach (ExpenseJournalExportService::JOURNAUX as $journal) {
            $path = $svc->exporter($journal, 'xlsx');
            $this->assertFileExists($path);
            $this->assertGreaterThan(1024, filesize($path), "Le fichier {$journal}.xlsx doit faire plus d'1 Ko");
            unlink($path);
        }
    }

    public function test_export_csv_journaux_genere_fichier(): void
    {
        $svc = app(ExpenseJournalExportService::class);

        foreach (ExpenseJournalExportService::JOURNAUX as $journal) {
            $path = $svc->exporter($journal, 'csv');
            $this->assertFileExists($path);
            // CSV minimum : BOM + titre + header
            $this->assertGreaterThan(50, filesize($path));
            unlink($path);
        }
    }

    public function test_export_contient_les_donnees_des_expressions(): void
    {
        $expense = app(ExpenseRequestService::class);
        $expense->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'Test export',
            'justification' => 'Test',
            'montant_estime' => 250_000,
            'supplier_pressenti_id' => $this->supplier->id,
        ], $this->initiateur);

        $svc = app(ExpenseJournalExportService::class);
        $path = $svc->exporter('expressions', 'csv');
        $contenu = file_get_contents($path);

        $this->assertStringContainsString('Test export', $contenu);
        $this->assertStringContainsString('250000', $contenu);
        unlink($path);
    }

    public function test_endpoint_download_xlsx(): void
    {
        $this->actingAs($this->initiateur)
            ->get('/expense/exports/engagements.xlsx')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_endpoint_download_csv(): void
    {
        $this->actingAs($this->initiateur)
            ->get('/expense/exports/suppliers.csv')
            ->assertOk();
    }

    public function test_endpoint_404_journal_inconnu(): void
    {
        $this->actingAs($this->initiateur)
            ->get('/expense/exports/inexistant.xlsx')
            ->assertNotFound();
    }

    public function test_endpoint_refuse_format_inconnu(): void
    {
        $this->actingAs($this->initiateur)
            ->get('/expense/exports/engagements.pdf')
            ->assertNotFound();
    }
}
