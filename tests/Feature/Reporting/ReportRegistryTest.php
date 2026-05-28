<?php

namespace Tests\Feature\Reporting;

use App\Services\Reporting\ReportRegistry;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReportRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_registry_contient_au_moins_16_rapports(): void
    {
        $registry = app(ReportRegistry::class);
        $this->assertGreaterThanOrEqual(16, count($registry->tous()));
    }

    public function test_registry_couvre_toutes_les_categories_attendues(): void
    {
        $registry = app(ReportRegistry::class);
        $categories = collect($registry->tous())->map(fn ($r) => $r->categorie())->unique()->values();

        foreach (['strategique', 'performance', 'rbm', 'budget', 'gouvernance', 'audit', 'analytique'] as $cat) {
            $this->assertContains($cat, $categories, "Catégorie manquante : {$cat}");
        }
    }

    public function test_trouver_renvoie_un_rapport_existant(): void
    {
        $registry = app(ReportRegistry::class);
        $rapport = $registry->trouver('historique_rapports');

        $this->assertSame('audit', $rapport->categorie());
    }

    public function test_trouver_jette_pour_cle_inconnue(): void
    {
        $registry = app(ReportRegistry::class);
        $this->expectException(InvalidArgumentException::class);
        $registry->trouver('inexistant_xyz');
    }

    public function test_par_categorie_regroupe_correctement(): void
    {
        $registry = app(ReportRegistry::class);
        $groupes = $registry->parCategorie();

        $this->assertArrayHasKey('strategique', $groupes);
        $this->assertArrayHasKey('audit', $groupes);
        $this->assertNotEmpty($groupes['strategique']);
    }
}
