<?php

namespace Tests\Feature\Indicateur;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Indicateur;
use App\Models\SousProduit;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicateurControllerTest extends TestCase
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

    public function test_listing_indicateurs(): void
    {
        Indicateur::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get('/indicateurs')
            ->assertOk();
    }

    public function test_creation_indicateur_valide(): void
    {
        $sp = SousProduit::factory()->create();

        $this->actingAs($this->admin)
            ->post('/indicateurs', [
                'sous_produit_id' => $sp->id,
                'code' => 'IND-TEST',
                'libelle' => 'Indicateur test',
                'type' => 'quantitatif',
                'categorie' => 'produit',
                'polarite' => 'positive',
                'frequence_collecte' => 'trimestrielle',
                'baseline' => 0,
                'cible' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('indicateurs', ['code' => 'IND-TEST']);
    }

    public function test_creation_refuse_code_duplique_dans_meme_sous_produit(): void
    {
        $sp = SousProduit::factory()->create();
        Indicateur::factory()->create(['sous_produit_id' => $sp->id, 'code' => 'IND-DUP']);

        $this->actingAs($this->admin)
            ->from('/indicateurs/create')
            ->post('/indicateurs', [
                'sous_produit_id' => $sp->id,
                'code' => 'IND-DUP',
                'libelle' => 'Doublon',
                'type' => 'quantitatif',
                'categorie' => 'produit',
                'frequence_collecte' => 'mensuelle',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_saisie_valeur_recalcule_taux_realisation(): void
    {
        $indicateur = Indicateur::factory()->create([
            'baseline' => 0,
            'cible' => 100,
            'valeur_actuelle' => null,
        ]);

        $this->actingAs($this->admin)
            ->post("/indicateurs/{$indicateur->id}/valeurs", [
                'date_observation' => '2026-04-01',
                'valeur' => 50,
            ])
            ->assertRedirect();

        $indicateur->refresh();
        $this->assertEquals(50, $indicateur->valeur_actuelle);
        $this->assertEquals(50, $indicateur->taux_realisation);
    }
}
