<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\OrganizationalUnit;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationalHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->admin = User::factory()->create(['actif' => true]);
        $this->admin->assignRole('admin_technique');
    }

    public function test_creation_departement_avec_uuid_auto(): void
    {
        $dep = Departement::create([
            'code' => 'TEST-DEP', 'libelle' => 'Département Test', 'ordre' => 1, 'actif' => true,
        ]);

        $this->assertNotNull($dep->uuid);
        $this->assertMatchesRegularExpression('/[0-9a-f]{8}-/', $dep->uuid);
    }

    public function test_creation_direction_avec_uuid_auto(): void
    {
        $dep = Departement::create(['code' => 'DEP', 'libelle' => 'Dep', 'ordre' => 1, 'actif' => true]);
        $dir = Direction::create([
            'code' => 'DIR', 'libelle' => 'Direction Test', 'type' => 'technique',
            'departement_id' => $dep->id, 'actif' => true,
        ]);

        $this->assertNotNull($dir->uuid);
        $this->assertEquals($dep->id, $dir->departement_id);
    }

    public function test_service_auto_fill_departement_id_depuis_direction(): void
    {
        $dep = Departement::create(['code' => 'DEP', 'libelle' => 'D', 'ordre' => 1, 'actif' => true]);
        $dir = Direction::create([
            'code' => 'DIR', 'libelle' => 'D', 'type' => 'technique',
            'departement_id' => $dep->id, 'actif' => true,
        ]);
        $svc = Service::create([
            'code' => 'SVC', 'libelle' => 'Service Test',
            'direction_id' => $dir->id, 'actif' => true,
        ]);

        $svc->refresh();
        $this->assertEquals($dep->id, $svc->departement_id);
        $this->assertEquals('actif', $svc->statut);
        $this->assertNotNull($svc->uuid);
    }

    public function test_service_chemin_hierarchique(): void
    {
        $dep = Departement::create(['code' => 'D', 'libelle' => 'Présidence', 'ordre' => 1, 'actif' => true]);
        $dir = Direction::create(['code' => 'DI', 'libelle' => 'DSI', 'type' => 'appui_soutien', 'departement_id' => $dep->id, 'actif' => true]);
        $svc = Service::create(['code' => 'S', 'libelle' => 'Cellule Dev', 'direction_id' => $dir->id, 'actif' => true]);

        $this->assertEquals('Présidence > DSI > Cellule Dev', $svc->fresh()->chemin_hierarchique);
    }

    public function test_organizational_unit_calcul_niveau_auto(): void
    {
        $racine = OrganizationalUnit::create([
            'code' => 'C', 'libelle' => 'Commission', 'type' => 'commission', 'actif' => true,
        ]);
        $enfant = OrganizationalUnit::create([
            'code' => 'P', 'libelle' => 'Présidence', 'type' => 'presidence',
            'parent_id' => $racine->id, 'actif' => true,
        ]);
        $petitEnfant = OrganizationalUnit::create([
            'code' => 'D1', 'libelle' => 'Direction X', 'type' => 'direction',
            'parent_id' => $enfant->id, 'actif' => true,
        ]);

        $this->assertEquals(0, $racine->fresh()->niveau);
        $this->assertEquals(1, $enfant->fresh()->niveau);
        $this->assertEquals(2, $petitEnfant->fresh()->niveau);
    }

    public function test_organizational_unit_chemin_hierarchique(): void
    {
        $a = OrganizationalUnit::create(['code' => 'A', 'libelle' => 'Commission', 'type' => 'commission']);
        $b = OrganizationalUnit::create(['code' => 'B', 'libelle' => 'Présidence', 'type' => 'presidence', 'parent_id' => $a->id]);
        $c = OrganizationalUnit::create(['code' => 'C', 'libelle' => 'DSI', 'type' => 'direction', 'parent_id' => $b->id]);

        $this->assertEquals('Commission > Présidence > DSI', $c->fresh()->chemin_hierarchique);
        $this->assertCount(3, $c->fresh()->ancetres());
    }

    public function test_endpoints_admin_services_directions_organigramme(): void
    {
        $this->actingAs($this->admin)->get('/admin/services')->assertOk();
        $this->actingAs($this->admin)->get('/admin/directions')->assertOk();
        $this->actingAs($this->admin)->get('/admin/organigramme')->assertOk();
    }

    public function test_creation_service_via_http(): void
    {
        $dep = Departement::create(['code' => 'D', 'libelle' => 'D', 'ordre' => 1, 'actif' => true]);
        $dir = Direction::create(['code' => 'DI', 'libelle' => 'DI', 'type' => 'technique', 'departement_id' => $dep->id, 'actif' => true]);

        $this->actingAs($this->admin)->post('/admin/services', [
            'code' => 'NEW-SVC',
            'libelle' => 'Nouveau Service',
            'direction_id' => $dir->id,
            'statut' => 'actif',
        ])->assertRedirect();

        $this->assertDatabaseHas('services', ['code' => 'NEW-SVC', 'departement_id' => $dep->id]);
    }

    public function test_permissions_admin_technique_complete(): void
    {
        $this->assertTrue($this->admin->can('service.viewAny'));
        $this->assertTrue($this->admin->can('service.manage'));
        $this->assertTrue($this->admin->can('direction.manage'));
        $this->assertTrue($this->admin->can('departement.manage'));
    }
}
