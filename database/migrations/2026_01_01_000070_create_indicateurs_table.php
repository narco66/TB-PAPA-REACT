<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indicateurs de résultats (KPI) — Section 5.3.5 / 6.5 du CDC.
 * Normalisés : baseline, cible, méthode, fréquence, source, responsable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sous_produit_id')->constrained('sous_produits')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('libelle');
            $table->text('definition')->nullable();
            $table->enum('type', ['quantitatif', 'qualitatif'])->default('quantitatif');
            $table->string('unite', 32)->nullable();
            $table->decimal('baseline', 18, 4)->nullable();
            $table->decimal('cible', 18, 4)->nullable();
            $table->date('date_baseline')->nullable();
            $table->text('methode_calcul')->nullable();
            $table->enum('frequence_collecte', ['mensuelle', 'trimestrielle', 'semestrielle', 'annuelle'])
                ->default('trimestrielle');
            $table->string('source_donnees')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('valeur_actuelle', 18, 4)->nullable();
            $table->decimal('taux_realisation', 5, 2)->default(0);
            $table->enum('tendance', ['hausse', 'stable', 'baisse', 'inconnue'])->default('inconnue');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sous_produit_id', 'code']);
        });

        Schema::create('valeurs_indicateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicateur_id')->constrained('indicateurs')->cascadeOnDelete();
            $table->date('date_observation');
            $table->string('periode_libelle', 32)->nullable(); // ex: "T1 2026", "Mars 2026"
            $table->decimal('valeur', 18, 4);
            $table->text('commentaire')->nullable();
            $table->string('source_verification')->nullable();
            $table->foreignId('saisi_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['indicateur_id', 'date_observation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valeurs_indicateurs');
        Schema::dropIfExists('indicateurs');
    }
};
