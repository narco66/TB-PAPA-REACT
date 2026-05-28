<?php

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Papa;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Services\Rbm\CodificationService;
use App\Services\Rbm\RecalculAvancementService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbmCodificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_codification_automatique_complete_chaine_rbm(): void
    {
        $papa = Papa::factory()->valide()->create();

        $axe = Axe::create([
            'papa_id' => $papa->id,
            'libelle' => 'Axe stratégique 1',
            'poids' => 100,
        ]);
        $this->assertSame('AXE 1', $axe->code);
        $this->assertSame(1, $axe->ordre);

        $axe2 = Axe::create([
            'papa_id' => $papa->id,
            'libelle' => 'Axe stratégique 2',
        ]);
        $this->assertSame('AXE 2', $axe2->code);

        $produit = Produit::create([
            'axe_id' => $axe->id,
            'libelle' => 'Produit 1.1',
        ]);
        $this->assertSame('P.1.1', $produit->code);

        $produit2 = Produit::create([
            'axe_id' => $axe->id,
            'libelle' => 'Produit 1.2',
        ]);
        $this->assertSame('P.1.2', $produit2->code);

        $sp = SousProduit::create([
            'produit_id' => $produit->id,
            'libelle' => 'Sous-Produit',
        ]);
        $this->assertSame('SP.1.1.1', $sp->code);

        $activite = Activite::create([
            'sous_produit_id' => $sp->id,
            'libelle' => 'Activité',
            'date_debut' => now(),
            'date_fin' => now()->addMonth(),
        ]);
        $this->assertSame('ACT.1.1.1.1', $activite->code);

        $tache = Tache::create([
            'activite_id' => $activite->id,
            'libelle' => 'Tâche',
        ]);
        $this->assertSame('T.1.1.1.1.1', $tache->code);
    }

    public function test_recalcul_bottom_up_du_taux_execution(): void
    {
        $papa = Papa::factory()->valide()->create();
        $axe = Axe::create(['papa_id' => $papa->id, 'libelle' => 'Axe', 'poids' => 100]);
        $produit = Produit::create(['axe_id' => $axe->id, 'libelle' => 'P', 'poids' => 100]);
        $sp = SousProduit::create(['produit_id' => $produit->id, 'libelle' => 'SP', 'poids' => 100]);
        $activite = Activite::create([
            'sous_produit_id' => $sp->id,
            'libelle' => 'A',
            'date_debut' => now(),
            'date_fin' => now()->addMonth(),
            'poids' => 100,
        ]);

        // Création de 2 tâches avec poids égaux : 100% et 0% → 50%
        Tache::create(['activite_id' => $activite->id, 'libelle' => 'T1', 'poids' => 100, 'taux_execution' => 100]);
        Tache::create(['activite_id' => $activite->id, 'libelle' => 'T2', 'poids' => 100, 'taux_execution' => 0]);

        // Le recalcul est déclenché par l'observer Eloquent (updated/created)
        // On déclenche aussi explicitement pour être sûr
        app(RecalculAvancementService::class)->recalculerPapa($papa->id);

        $axe->refresh();
        $produit->refresh();
        $sp->refresh();
        $activite->refresh();

        $this->assertSame(50.0, (float) $activite->taux_execution);
        $this->assertSame(50.0, (float) $sp->taux_execution);
        $this->assertSame(50.0, (float) $produit->taux_execution);
        $this->assertSame(50.0, (float) $axe->taux_execution);
    }

    public function test_recalcul_avec_poids_differents(): void
    {
        $papa = Papa::factory()->valide()->create();
        $axe = Axe::create(['papa_id' => $papa->id, 'libelle' => 'Axe', 'poids' => 100]);
        $produit = Produit::create(['axe_id' => $axe->id, 'libelle' => 'P', 'poids' => 100]);
        $sp = SousProduit::create(['produit_id' => $produit->id, 'libelle' => 'SP', 'poids' => 100]);
        $activite = Activite::create([
            'sous_produit_id' => $sp->id,
            'libelle' => 'A',
            'date_debut' => now(),
            'date_fin' => now()->addMonth(),
            'poids' => 100,
        ]);

        // T1 (poids 80) à 100% + T2 (poids 20) à 0% → (100*80 + 0*20)/100 = 80
        Tache::create(['activite_id' => $activite->id, 'libelle' => 'T1', 'poids' => 80, 'taux_execution' => 100]);
        Tache::create(['activite_id' => $activite->id, 'libelle' => 'T2', 'poids' => 20, 'taux_execution' => 0]);

        app(RecalculAvancementService::class)->recalculerPapa($papa->id);

        $activite->refresh();
        $this->assertSame(80.0, (float) $activite->taux_execution);
    }

    public function test_codification_via_service_recoder_papa(): void
    {
        $papa = Papa::factory()->valide()->create();
        $axe = Axe::create(['papa_id' => $papa->id, 'libelle' => 'Axe Test']);
        Produit::create(['axe_id' => $axe->id, 'libelle' => 'P1']);
        Produit::create(['axe_id' => $axe->id, 'libelle' => 'P2']);

        $count = app(CodificationService::class)->recoderPapa($papa->id);
        $this->assertSame(3, $count); // 1 axe + 2 produits
    }
}
