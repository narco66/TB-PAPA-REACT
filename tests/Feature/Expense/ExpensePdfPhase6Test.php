<?php

namespace Tests\Feature\Expense;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\User;
use App\Services\Reporting\PdfGeneratorService;
use App\Services\Reporting\ReportRegistry;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpensePdfPhase6Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->user = User::factory()->create(['actif' => true]);
        $this->user->assignRole('admin_technique');
    }

    #[DataProvider('providerPdfPhase6')]
    public function test_pdf_genere_correctement(string $key): void
    {
        $reg = app(ReportRegistry::class);
        $svc = app(PdfGeneratorService::class);

        $report = $reg->trouver($key);
        $generated = $svc->generer($report, [], $this->user->id);

        $this->assertNotNull($generated);
        $this->assertGreaterThan(10_000, $generated->taille_octets, "Le PDF {$key} doit faire plus de 10 Ko");
        $this->assertNotEmpty($generated->code_verification);
        $this->assertNotEmpty($generated->hash_sha256);
    }

    public static function providerPdfPhase6(): array
    {
        return [
            ['demande_visa_financier'],
            ['visa_financier'],
            ['notification_rejet'],
            ['notification_retour'],
            ['fiche_liquidation'],
            ['decompte_paiement'],
            ['ordonnance_paiement'],
            ['bordereau_ordonnancement'],
            ['avis_paiement'],
            ['recu_paiement'],
        ];
    }

    public function test_registre_contient_les_10_nouveaux_pdf(): void
    {
        $reg = app(ReportRegistry::class);
        $tous = collect($reg->tous())->map(fn ($r) => $r->key())->toArray();

        $attendus = [
            'demande_visa_financier', 'visa_financier',
            'notification_rejet', 'notification_retour',
            'fiche_liquidation', 'decompte_paiement',
            'ordonnance_paiement', 'bordereau_ordonnancement',
            'avis_paiement', 'recu_paiement',
        ];

        foreach ($attendus as $key) {
            $this->assertContains($key, $tous, "Le rapport {$key} doit être enregistré dans le ReportRegistry");
        }
    }
}
