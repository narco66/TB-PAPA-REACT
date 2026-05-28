<?php

namespace Tests\Unit\Models;

use App\Models\Indicateur;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicateurCmrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_taux_realisation_polarite_positive(): void
    {
        $i = Indicateur::factory()->make([
            'baseline' => 20,
            'cible' => 100,
            'valeur_actuelle' => 60,
            'polarite' => 'positive',
        ]);

        $i->recalculerTauxRealisation();
        // (60 - 20) / (100 - 20) × 100 = 50%
        $this->assertEqualsWithDelta(50.0, $i->taux_realisation, 0.01);
    }

    public function test_taux_realisation_polarite_negative(): void
    {
        // ex. taux de mortalité : baseline 100, cible 20, observé 60 → demi-chemin
        $i = Indicateur::factory()->make([
            'baseline' => 100,
            'cible' => 20,
            'valeur_actuelle' => 60,
            'polarite' => 'negative',
        ]);

        $i->recalculerTauxRealisation();
        // (100 - 60) / (100 - 20) × 100 = 50%
        $this->assertEqualsWithDelta(50.0, $i->taux_realisation, 0.01);
    }

    public function test_taux_realisation_polarite_neutre_atteint_cible(): void
    {
        // ex. ratio cible exact : cible 100, observé 100 → 100%
        $i = Indicateur::factory()->make([
            'baseline' => 0,
            'cible' => 100,
            'valeur_actuelle' => 100,
            'polarite' => 'neutre',
        ]);

        $i->recalculerTauxRealisation();
        $this->assertEqualsWithDelta(100.0, $i->taux_realisation, 0.01);
    }

    public function test_taux_realisation_polarite_neutre_ecart(): void
    {
        // observé 110 vs cible 100 → 90% (10% d'écart)
        $i = Indicateur::factory()->make([
            'baseline' => 0,
            'cible' => 100,
            'valeur_actuelle' => 110,
            'polarite' => 'neutre',
        ]);

        $i->recalculerTauxRealisation();
        $this->assertEqualsWithDelta(90.0, $i->taux_realisation, 0.01);
    }

    public function test_cible_palier_renvoie_la_bonne_valeur_par_trimestre(): void
    {
        $i = Indicateur::factory()->make([
            'palier_t1' => 25,
            'palier_t2' => 50,
            'palier_t3' => 75,
            'palier_t4' => 100,
        ]);

        $this->assertSame(25.0, $i->ciblePalier('T1'));
        $this->assertSame(50.0, $i->ciblePalier('T2'));
        $this->assertSame(75.0, $i->ciblePalier('T3'));
        $this->assertSame(100.0, $i->ciblePalier('T4'));
    }

    public function test_ecart_palier_calcule_la_difference(): void
    {
        $i = Indicateur::factory()->make([
            'palier_t2' => 50,
        ]);

        $this->assertSame(10.0, $i->ecartPalier('T2', 60));
        $this->assertSame(-15.0, $i->ecartPalier('T2', 35));
        $this->assertNull($i->ecartPalier('T1', 100)); // T1 non défini
    }

    public function test_est_hors_plage_quand_valeur_sous_seuil_bas(): void
    {
        $i = Indicateur::factory()->make([
            'valeur_actuelle' => 30,
            'seuil_alerte_bas' => 40,
            'seuil_alerte_haut' => 80,
        ]);

        $this->assertTrue($i->estHorsPlage());
    }

    public function test_est_hors_plage_quand_valeur_au_dessus_seuil_haut(): void
    {
        $i = Indicateur::factory()->make([
            'valeur_actuelle' => 90,
            'seuil_alerte_bas' => 40,
            'seuil_alerte_haut' => 80,
        ]);

        $this->assertTrue($i->estHorsPlage());
    }

    public function test_est_hors_plage_false_dans_la_plage(): void
    {
        $i = Indicateur::factory()->make([
            'valeur_actuelle' => 60,
            'seuil_alerte_bas' => 40,
            'seuil_alerte_haut' => 80,
        ]);

        $this->assertFalse($i->estHorsPlage());
    }

    public function test_dimensions_desagregation_collecte_les_drapeaux_actifs(): void
    {
        $i = Indicateur::factory()->make([
            'desagregation_genre' => true,
            'desagregation_geographique' => true,
            'desagregation_vulnerabilite' => false,
            'desagregation_age' => false,
        ]);

        $this->assertSame(['Genre', 'Géographique'], $i->dimensionsDesagregation());
    }
}
