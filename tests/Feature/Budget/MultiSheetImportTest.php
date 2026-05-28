<?php

namespace Tests\Feature\Budget;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Papa;
use App\Models\User;
use App\Services\Budget\Import\MultiSheetImportOrchestrator;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class MultiSheetImportTest extends TestCase
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

    public function test_orchestrator_importe_axes_puis_produits_en_cascade(): void
    {
        $this->cheminFichier = $this->creerFichier([
            'Axes' => [
                ['Code', 'Libellé', 'Description', 'Poids'],
                ['', 'Axe Paix et Sécurité', 'Description axe 1', 30],
                ['', 'Axe Économie', 'Description axe 2', 40],
            ],
            'Produits' => [
                ['Code', 'Code axe', 'Libellé', 'Description', 'Poids'],
                ['', 'AXE 1', 'Produit 1.1', 'Produit Paix', 50],
                ['', 'AXE 1', 'Produit 1.2', 'Produit Sécurité', 50],
            ],
        ]);

        $orchestrator = app(MultiSheetImportOrchestrator::class);
        $parent = $orchestrator->executer(
            $this->exercice,
            $this->cheminFichier,
            'test.xlsx',
            filesize($this->cheminFichier),
            hash_file('sha256', $this->cheminFichier),
            $this->admin->id,
            [
                ['nom' => 'Axes', 'type' => 'axes'],
                ['nom' => 'Produits', 'type' => 'produits'],
            ],
            $this->papa->id,
        );

        $this->assertSame('reussi', $parent->statut);
        $this->assertSame(2, $parent->enfants()->count());

        $this->assertDatabaseCount('axes', 2);
        $this->assertDatabaseHas('axes', ['libelle' => 'Axe Paix et Sécurité', 'papa_id' => $this->papa->id]);

        $this->assertDatabaseCount('produits', 2);
        $axeId = Axe::where('libelle', 'Axe Paix et Sécurité')->value('id');
        $this->assertDatabaseHas('produits', ['libelle' => 'Produit 1.1', 'axe_id' => $axeId]);
    }

    public function test_orchestrator_signale_reference_inexistante(): void
    {
        $this->cheminFichier = $this->creerFichier([
            'Produits' => [
                ['Code', 'Code axe', 'Libellé'],
                ['', 'AXE-INEXISTANT', 'Produit orphelin'],
            ],
        ]);

        $orchestrator = app(MultiSheetImportOrchestrator::class);
        $parent = $orchestrator->executer(
            $this->exercice,
            $this->cheminFichier,
            'test.xlsx',
            filesize($this->cheminFichier),
            hash_file('sha256', $this->cheminFichier),
            $this->admin->id,
            [['nom' => 'Produits', 'type' => 'produits']],
            $this->papa->id,
        );

        $this->assertSame('echec', $parent->statut);
        $this->assertGreaterThan(0, $parent->nb_erreurs);
        $this->assertDatabaseMissing('produits', ['libelle' => 'Produit orphelin']);

        $erreur = $parent->enfants()->first()->erreurs()->first();
        $this->assertSame('reference_inexistante', $erreur->regle);
        $this->assertSame('code_axe', $erreur->colonne);
    }

    public function test_dry_run_ne_persiste_pas_les_donnees(): void
    {
        $this->cheminFichier = $this->creerFichier([
            'Axes' => [
                ['Code', 'Libellé', 'Poids'],
                ['', 'Axe test dry-run', 100],
            ],
        ]);

        $orchestrator = app(MultiSheetImportOrchestrator::class);
        $orchestrator->executer(
            $this->exercice,
            $this->cheminFichier,
            'test.xlsx',
            filesize($this->cheminFichier),
            hash_file('sha256', $this->cheminFichier),
            $this->admin->id,
            [['nom' => 'Axes', 'type' => 'axes']],
            $this->papa->id,
            dryRun: true,
        );

        $this->assertDatabaseMissing('axes', ['libelle' => 'Axe test dry-run']);
    }

    public function test_endpoint_multi_feuilles_via_http(): void
    {
        $this->cheminFichier = $this->creerFichier([
            'Axes' => [['Code', 'Libellé', 'Poids'], ['', 'Axe HTTP', 100]],
        ]);

        $file = new UploadedFile(
            $this->cheminFichier,
            'test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->actingAs($this->admin)
            ->post('/budget/imports/multi-feuilles', [
                'exercice_id' => $this->exercice->id,
                'fichier' => $file,
                'feuilles' => [['nom' => 'Axes', 'type' => 'axes']],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('axes', ['libelle' => 'Axe HTTP']);
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
