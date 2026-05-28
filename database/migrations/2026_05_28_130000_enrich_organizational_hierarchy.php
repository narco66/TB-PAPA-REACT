<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Enrichissement non destructif de l'architecture organisationnelle :
 *   COMMISSION → DÉPARTEMENT → DIRECTION → SERVICE
 *
 * Ajouts :
 *   - uuid sur 4 tables (departements, directions, services, organizational_units)
 *   - ordre_affichage explicite + statut enum sur services et organizational_units
 *   - departement_id sur services (relation hiérarchique directe, en plus de direction_id)
 *
 * Aucune donnée existante n'est supprimée. Les nouvelles colonnes sont nullable
 * et back-fillées automatiquement à partir des données existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajout des UUID sur les tables hiérarchiques
        foreach (['departements', 'directions', 'services', 'organizational_units'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->uuid('uuid')->nullable()->after('id');
                });

                // Back-fill UUIDs sur les lignes existantes
                DB::table($table)->whereNull('uuid')->orderBy('id')->each(function ($row) use ($table) {
                    DB::table($table)->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
                });

                Schema::table($table, function (Blueprint $t) {
                    $t->uuid('uuid')->nullable(false)->change();
                    $t->unique('uuid');
                });
            }
        }

        // 2. Departement : enrichissement
        Schema::table('departements', function (Blueprint $table) {
            if (! Schema::hasColumn('departements', 'type')) {
                $table->enum('type', ['presidence', 'vice_presidence', 'secretariat_general', 'departement_technique', 'organe_consultatif'])
                    ->default('departement_technique')->after('libelle')->index();
            }
        });

        // 3. Direction : enrichissement (le type existe déjà : technique/appui_soutien)
        Schema::table('directions', function (Blueprint $table) {
            if (! Schema::hasColumn('directions', 'ordre')) {
                $table->integer('ordre')->default(0)->after('description');
            }
        });

        // 4. Services : ajout du lien direct au département (pour navigation transversale)
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'departement_id')) {
                $table->foreignId('departement_id')->nullable()
                    ->after('direction_id')->constrained('departements')->nullOnDelete();
            }
            if (! Schema::hasColumn('services', 'statut')) {
                $table->enum('statut', ['actif', 'suspendu', 'archive'])
                    ->default('actif')->after('actif')->index();
            }
        });

        // 5. Back-fill du departement_id sur services depuis direction.departement_id (portable MySQL/SQLite)
        if (Schema::hasColumn('services', 'departement_id')) {
            DB::table('services')
                ->whereNotNull('direction_id')
                ->whereNull('departement_id')
                ->orderBy('id')
                ->get()
                ->each(function ($svc) {
                    $depId = DB::table('directions')->where('id', $svc->direction_id)->value('departement_id');
                    if ($depId) {
                        DB::table('services')->where('id', $svc->id)->update(['departement_id' => $depId]);
                    }
                });
        }

        // 6. Statut sur organizational_units si pas déjà là
        Schema::table('organizational_units', function (Blueprint $table) {
            if (! Schema::hasColumn('organizational_units', 'niveau')) {
                $table->integer('niveau')->default(0)->after('type')
                    ->comment('Profondeur dans la hiérarchie (0=racine)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizational_units', function (Blueprint $table) {
            if (Schema::hasColumn('organizational_units', 'niveau')) {
                $table->dropColumn('niveau');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'departement_id')) {
                $table->dropConstrainedForeignId('departement_id');
            }
            if (Schema::hasColumn('services', 'statut')) {
                $table->dropColumn('statut');
            }
        });

        Schema::table('directions', function (Blueprint $table) {
            if (Schema::hasColumn('directions', 'ordre')) {
                $table->dropColumn('ordre');
            }
        });

        Schema::table('departements', function (Blueprint $table) {
            if (Schema::hasColumn('departements', 'type')) {
                $table->dropColumn('type');
            }
        });

        foreach (['departements', 'directions', 'services', 'organizational_units'] as $t) {
            if (Schema::hasColumn($t, 'uuid')) {
                Schema::table($t, function (Blueprint $tbl) {
                    $tbl->dropUnique([$tbl->getTable() . '_uuid_unique']);
                    $tbl->dropColumn('uuid');
                });
            }
        }
    }
};
