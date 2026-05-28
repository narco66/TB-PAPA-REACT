<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cadre de Mesure du Rendement (CMR) — extensions exigées par
 * BAD / Banque mondiale / UE / Union Africaine pour le reporting bailleurs :
 *  - typologie CAD/OCDE (impact / effet / produit / processus)
 *  - paliers trimestriels
 *  - désagrégation (genre / géographique / vulnérabilité / âge)
 *  - méthodologie complète (instrument de collecte, responsable de validation,
 *    hypothèses, risques associés, polarité, seuils d'alerte)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicateurs', function (Blueprint $table) {
            // === Typologie CAD/OCDE ===
            $table->enum('categorie', ['impact', 'effet', 'produit', 'processus'])
                ->default('produit')
                ->after('type');

            // === Polarité : croissance souhaitée ===
            $table->enum('polarite', ['positive', 'negative', 'neutre'])
                ->default('positive')
                ->after('categorie')
                ->comment('positive = plus c\'est élevé, mieux c\'est ; negative = inverse');

            // === Paliers trimestriels (cibles intermédiaires) ===
            $table->decimal('palier_t1', 18, 4)->nullable()->after('cible');
            $table->decimal('palier_t2', 18, 4)->nullable()->after('palier_t1');
            $table->decimal('palier_t3', 18, 4)->nullable()->after('palier_t2');
            $table->decimal('palier_t4', 18, 4)->nullable()->after('palier_t3');

            // === Méthodologie complète ===
            $table->string('instrument_collecte')->nullable()->after('source_donnees')
                ->comment('Enquête, base administrative, observation directe, etc.');
            $table->foreignId('responsable_collecte_id')->nullable()->after('instrument_collecte')
                ->constrained('users')->nullOnDelete();
            // 'responsable_id' existant est conservé et représente le responsable de validation.

            // === Désagrégation ===
            $table->boolean('desagregation_genre')->default(false)->after('responsable_collecte_id');
            $table->boolean('desagregation_geographique')->default(false)->after('desagregation_genre');
            $table->boolean('desagregation_vulnerabilite')->default(false)->after('desagregation_geographique');
            $table->boolean('desagregation_age')->default(false)->after('desagregation_vulnerabilite');

            // === Théorie du changement ===
            $table->text('hypotheses')->nullable()->after('methode_calcul');
            $table->text('risques_associes')->nullable()->after('hypotheses');

            // === Plage acceptable (seuils d'alerte) ===
            $table->decimal('seuil_alerte_bas', 18, 4)->nullable()->after('palier_t4');
            $table->decimal('seuil_alerte_haut', 18, 4)->nullable()->after('seuil_alerte_bas');

            // === Référentiels externes (alignement bailleurs) ===
            $table->json('referentiels_externes')->nullable()->after('seuil_alerte_haut')
                ->comment('Ex: [{"cadre":"ODD","code":"4.1"},{"cadre":"Agenda 2063","code":"1.1"}]');
        });
    }

    public function down(): void
    {
        Schema::table('indicateurs', function (Blueprint $table) {
            $table->dropColumn([
                'categorie',
                'polarite',
                'palier_t1',
                'palier_t2',
                'palier_t3',
                'palier_t4',
                'instrument_collecte',
                'responsable_collecte_id',
                'desagregation_genre',
                'desagregation_geographique',
                'desagregation_vulnerabilite',
                'desagregation_age',
                'hypotheses',
                'risques_associes',
                'seuil_alerte_bas',
                'seuil_alerte_haut',
                'referentiels_externes',
            ]);
        });
    }
};
