<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE budget_imports MODIFY statut ENUM('brouillon','analyse','valide','importe','echec','annule','a_corriger','prevu','en_cours','reussi','rollback') NOT NULL DEFAULT 'brouillon'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE budget_imports MODIFY statut ENUM('prevu','en_cours','reussi','echec','rollback') NOT NULL DEFAULT 'prevu'");
    }
};
