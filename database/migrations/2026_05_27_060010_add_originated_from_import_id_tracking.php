<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Tracking de l'origine d'import pour permettre le rollback.
 * Ajout non destructif d'une colonne FK nullable sur chaque entité RBM
 * qui peut être créée via import multi-feuilles.
 */
return new class extends Migration
{
    /** Tables à instrumenter avec la colonne d'origine d'import. */
    protected array $tables = ['axes', 'produits', 'sous_produits', 'activites', 'taches', 'indicateurs', 'budget_lignes'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'originated_from_import_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('originated_from_import_id')->nullable()
                        ->after('id')
                        ->constrained('budget_imports')->nullOnDelete()
                        ->comment('Import multi-feuilles ayant créé cette ressource (pour rollback)');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'originated_from_import_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropConstrainedForeignId('originated_from_import_id');
                });
            }
        }
    }
};
