<?php

namespace Tests\Feature\Demo;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Budget\BudgetLigne;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TbpapaSeedDemoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_demo_genere_un_jeu_coherent_et_rollbackable(): void
    {
        $this->artisan('tbpapa:seed-demo --fresh --year=2026 --with-audit --with-imports --with-notifications --with-kpi-history')
            ->assertExitCode(0);

        $this->assertSame(30, User::where('email', 'like', 'demo.%@ceeac.int')->count());
        $this->assertGreaterThanOrEqual(10, Axe::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(30, Produit::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(60, SousProduit::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(150, Activite::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(500, Tache::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(100, BudgetLigne::where('observations', 'like', '%DEMO%')->count());

        $this->assertSame(0, Produit::whereDoesntHave('axe')->count());
        $this->assertSame(0, SousProduit::whereDoesntHave('produit')->count());
        $this->assertSame(0, Activite::whereDoesntHave('sousProduit')->count());
        $this->assertSame(0, Tache::whereDoesntHave('activite')->count());
        $this->assertSame(0, BudgetLigne::whereRaw('ABS(montant_total - (montant_ceeac_em + montant_ptf)) > 0.01')->count());

        $this->artisan('tbpapa:seed-demo --rollback --year=2026')
            ->assertExitCode(0);

        $this->assertSame(0, User::where('email', 'like', 'demo.%@ceeac.int')->count());
        $this->assertSame(0, Axe::where('code', 'like', 'DEMO-%')->count());
        $this->assertSame(0, BudgetLigne::where('observations', 'like', '%DEMO%')->count());
    }
}
