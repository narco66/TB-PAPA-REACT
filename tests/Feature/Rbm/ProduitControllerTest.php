<?php

namespace Tests\Feature\Rbm;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Axe;
use App\Models\Produit;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduitControllerTest extends TestCase
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

    public function test_listing_produits(): void
    {
        Produit::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get('/rbm/produits')
            ->assertOk();
    }

    public function test_creation_genere_code_hierarchique(): void
    {
        $axe = Axe::factory()->create();

        $this->actingAs($this->admin)
            ->post('/rbm/produits', [
                'axe_id' => $axe->id,
                'libelle' => 'Produit test',
            ])
            ->assertRedirect();

        $produit = Produit::where('libelle', 'Produit test')->first();
        $this->assertNotNull($produit);
        $this->assertMatchesRegularExpression('/^P\.\d+\.\d+$/', $produit->code);
    }

    public function test_update_modifie_libelle(): void
    {
        $produit = Produit::factory()->create();

        $this->actingAs($this->admin)
            ->put("/rbm/produits/{$produit->id}", [
                'axe_id' => $produit->axe_id,
                'libelle' => 'Libellé modifié',
            ])
            ->assertRedirect();

        $produit->refresh();
        $this->assertSame('Libellé modifié', $produit->libelle);
    }

    public function test_delete_soft_delete(): void
    {
        $produit = Produit::factory()->create();

        $this->actingAs($this->admin)
            ->delete("/rbm/produits/{$produit->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('produits', ['id' => $produit->id]);
    }
}
