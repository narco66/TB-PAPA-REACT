<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Note : on n'utilise PAS WithoutModelEvents ici car les Observers RBM
    // (codification automatique + propagation du taux d'exécution) doivent
    // s'exécuter pendant le seed.

    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            InstitutionSeeder::class,
            PapaDemoSeeder::class,
            BudgetSeeder::class,
        ]);
    }
}
