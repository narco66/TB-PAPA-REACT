<?php

namespace Tests\Feature\Budget;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Budget\BudgetImportMapping;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportMappingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->admin = User::factory()->create(['actif' => true]);
        $this->admin->assignRole('admin_technique');
    }

    public function test_sauvegarder_un_mapping(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/budget/imports/mappings', [
                'type_donnees' => 'axes',
                'libelle' => 'Mapping Axes 2026',
                'mapping' => ['Code' => 'code', 'Libellé' => 'libelle'],
                'partage' => true,
            ])
            ->assertOk()
            ->assertJson(['succes' => true]);

        $this->assertDatabaseHas('budget_import_mappings', [
            'libelle' => 'Mapping Axes 2026',
            'type_donnees' => 'axes',
            'partage' => true,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_supprimer_son_propre_mapping(): void
    {
        $mapping = BudgetImportMapping::create([
            'user_id' => $this->admin->id,
            'type_donnees' => 'produits',
            'libelle' => 'Test',
            'mapping' => ['Code' => 'code'],
            'partage' => false,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/budget/imports/mappings/{$mapping->id}")
            ->assertOk()
            ->assertJson(['succes' => true]);

        $this->assertDatabaseMissing('budget_import_mappings', ['id' => $mapping->id]);
    }

    public function test_un_autre_utilisateur_ne_peut_pas_supprimer_un_mapping_prive(): void
    {
        $autre = User::factory()->create(['actif' => true]);
        $autre->assignRole('directeur_technique');

        $mapping = BudgetImportMapping::create([
            'user_id' => $this->admin->id,
            'type_donnees' => 'produits',
            'libelle' => 'Privé',
            'mapping' => ['Code' => 'code'],
            'partage' => false,
        ]);

        $this->actingAs($autre)
            ->deleteJson("/budget/imports/mappings/{$mapping->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('budget_import_mappings', ['id' => $mapping->id]);
    }

    public function test_validation_type_donnees_invalide(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/budget/imports/mappings', [
                'type_donnees' => 'invalide',
                'libelle' => 'Test',
                'mapping' => [],
            ])
            ->assertUnprocessable();
    }
}
