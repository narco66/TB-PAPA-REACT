<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Colonnes pour stocker les ventilations directement sur la valeur observée.
        Schema::table('valeurs_indicateurs', function (Blueprint $table) {
            $table->enum('trimestre', ['T1', 'T2', 'T3', 'T4'])->nullable()->after('periode_libelle');
            $table->unsignedSmallInteger('annee')->nullable()->after('trimestre');

            // Désagrégation par genre
            $table->decimal('valeur_hommes', 18, 4)->nullable()->after('valeur');
            $table->decimal('valeur_femmes', 18, 4)->nullable()->after('valeur_hommes');

            // Désagrégation par tranches d'âge (libre format JSON pour souplesse)
            $table->json('desagregation_age')->nullable()->after('valeur_femmes');

            // Désagrégation géographique (libre format JSON : { "ETAT_CD": valeur, ... })
            $table->json('desagregation_geographique')->nullable()->after('desagregation_age');

            // Désagrégation par vulnérabilité (libre format JSON)
            $table->json('desagregation_vulnerabilite')->nullable()->after('desagregation_geographique');

            $table->index(['indicateur_id', 'annee', 'trimestre']);
        });
    }

    public function down(): void
    {
        Schema::table('valeurs_indicateurs', function (Blueprint $table) {
            $table->dropIndex(['indicateur_id', 'annee', 'trimestre']);
            $table->dropColumn([
                'trimestre',
                'annee',
                'valeur_hommes',
                'valeur_femmes',
                'desagregation_age',
                'desagregation_geographique',
                'desagregation_vulnerabilite',
            ]);
        });
    }
};
