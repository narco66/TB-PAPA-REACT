<?php

namespace Tests\Feature\Budget;

use App\Http\Middleware\EnforceTwoFactor;
use App\Jobs\ProcessMultiSheetImportJob;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetImport;
use App\Models\Papa;
use App\Models\Produit;
use App\Models\User;
use App\Services\Budget\Import\ImportRollbackService;
use App\Services\Budget\Import\MultiSheetImportOrchestrator;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ImportRollbackTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BudgetExercice $exercice;

    protected Papa $papa;

    protected string $cheminFichier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->admin = User::factory()->create(['actif' => true]);
        $this->admin->assignRole('admin_technique');

        $this->papa = Papa::factory()->valide()->create();
        $this->exercice = BudgetExercice::create([
            'annee' => $this->papa->annee,
            'libelle' => "Exercice {$this->papa->annee}",
            'statut' => 'valide',
            'date_ouverture' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->cheminFichier) && file_exists($this->cheminFichier)) {
            @unlink($this->cheminFichier);
        }
        parent::tearDown();
    }

    public function test_rollback_supprime_les_ressources_creees_par_import(): void
    {
        $this->cheminFichier = $this->creerFichier([
            'Axes' => [['Code', 'Libellé', 'Poids'], ['', 'Axe A', 50], ['', 'Axe B', 50]],
            'Produits' => [['Code', 'Code axe', 'Libellé'], ['', 'AXE 1', 'Produit 1'], ['', 'AXE 2', 'Produit 2']],
        ]);

        $parent = app(MultiSheetImportOrchestrator::class)->executer(
            $this->exercice,
            $this->cheminFichier,
            'test.xlsx',
            filesize($this->cheminFichier),
            'hash',
            $this->admin->id,
            [
                ['nom' => 'Axes', 'type' => 'axes'],
                ['nom' => 'Produits', 'type' => 'produits'],
            ],
            $this->papa->id,
        );

        $this->assertSame('reussi', $parent->statut);
        $this->assertDatabaseCount('axes', 2);
        $this->assertDatabaseCount('produits', 2);

        $resultat = app(ImportRollbackService::class)->annulerImport($parent);

        $parent->refresh();
        $this->assertSame('rollback', $parent->statut);
        $this->assertSame(4, $resultat['total']);

        // Eloquent count respecte SoftDeletes → 0 axes "actifs"
        $this->assertSame(0, Axe::count());
        $this->assertSame(0, Produit::count());

        // Mais les rows existent toujours en BDD avec deleted_at non null
        $this->assertSame(2, Axe::onlyTrashed()->count());
        $this->assertSame(2, Produit::onlyTrashed()->count());
    }

    public function test_endpoint_rollback_via_http(): void
    {
        $this->cheminFichier = $this->creerFichier([
            'Axes' => [['Code', 'Libellé'], ['', 'Test rollback HTTP']],
        ]);

        $parent = app(MultiSheetImportOrchestrator::class)->executer(
            $this->exercice,
            $this->cheminFichier,
            'test.xlsx',
            filesize($this->cheminFichier),
            'hash',
            $this->admin->id,
            [['nom' => 'Axes', 'type' => 'axes']],
            $this->papa->id,
        );

        $this->actingAs($this->admin)
            ->post("/budget/imports/{$parent->id}/rollback")
            ->assertRedirect();

        $parent->refresh();
        $this->assertSame('rollback', $parent->statut);
        $this->assertSame(0, Axe::count()); // soft-deleted
    }

    public function test_rollback_refuse_pour_role_non_admin_technique(): void
    {
        $directeur = User::factory()->create(['actif' => true]);
        $directeur->assignRole('directeur_technique');

        $import = BudgetImport::create([
            'exercice_id' => $this->exercice->id,
            'fichier_nom' => 'test.xlsx',
            'statut' => 'reussi',
            'execute_par_id' => $this->admin->id,
            'execute_at' => now(),
        ]);

        $this->actingAs($directeur)
            ->post("/budget/imports/{$import->id}/rollback")
            ->assertForbidden();

        $import->refresh();
        $this->assertSame('reussi', $import->statut);
    }

    public function test_endpoint_statut_renvoie_letat_actuel(): void
    {
        $import = BudgetImport::create([
            'exercice_id' => $this->exercice->id,
            'fichier_nom' => 'test.xlsx',
            'statut' => 'en_cours',
            'execute_par_id' => $this->admin->id,
            'execute_at' => now(),
            'nb_lignes_lues' => 10,
            'nb_lignes_creees' => 5,
            'nb_erreurs' => 2,
        ]);

        $this->actingAs($this->admin)
            ->get("/budget/imports/{$import->id}/statut")
            ->assertOk()
            ->assertJson([
                'id' => $import->id,
                'statut' => 'en_cours',
                'nb_lignes_lues' => 10,
                'nb_lignes_creees' => 5,
                'nb_erreurs' => 2,
            ]);
    }

    public function test_job_async_est_dispatchable(): void
    {
        Queue::fake();

        ProcessMultiSheetImportJob::dispatch(
            $this->exercice->id,
            '/tmp/fake.xlsx',
            'fake.xlsx',
            1024,
            'hash',
            $this->admin->id,
            [['nom' => 'Axes', 'type' => 'axes']],
            $this->papa->id,
            false,
        );

        Queue::assertPushed(ProcessMultiSheetImportJob::class);
    }

    /**
     * @param array<string, array<int, array<int, string|int|float>>> $feuilles
     */
    protected function creerFichier(array $feuilles): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($feuilles as $nom => $rows) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(mb_substr($nom, 0, 31));
            foreach ($rows as $rowIdx => $row) {
                foreach ($row as $colIdx => $value) {
                    $col = chr(65 + $colIdx);
                    $sheet->setCellValue($col . ($rowIdx + 1), $value);
                }
            }
        }

        $temp = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($temp);

        return $temp;
    }
}
