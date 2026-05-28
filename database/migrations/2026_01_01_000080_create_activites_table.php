<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activités — niveau 4 de la chaîne RBM CEEAC.
 * Codification : « ACT.1.1.1.1 » (ACT.{ordre_axe}.{produit}.{sp}.{activite}).
 * Supporte le diagramme de Gantt (Section 12 du CDC) avec dépendances et jalons.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sous_produit_id')->constrained('sous_produits')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->enum('statut', [
                'brouillon', 'soumis', 'en_validation', 'valide', 'rejete',
                'planifiee', 'en_cours', 'realisee', 'suspendue', 'annulee', 'archive',
            ])->default('planifiee')->index();
            $table->integer('ordre')->default(0);
            $table->decimal('poids', 5, 2)->default(100);
            $table->decimal('taux_execution', 5, 2)->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->date('date_debut_reelle')->nullable();
            $table->date('date_fin_reelle')->nullable();
            $table->enum('niveau_risque', ['faible', 'moyen', 'eleve', 'critique'])->default('faible');
            $table->boolean('est_jalon')->default(false);
            $table->foreignId('direction_id')->nullable()->constrained('directions')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('point_focal_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sous_produit_id', 'code']);
            $table->index(['date_debut', 'date_fin']);
        });

        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activite_id')->constrained('activites')->cascadeOnDelete();
            $table->foreignId('tache_parente_id')->nullable()->constrained('taches')->nullOnDelete();
            $table->string('code', 32);
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->enum('statut', [
                'brouillon', 'soumis', 'en_validation', 'valide', 'rejete',
                'planifiee', 'en_cours', 'realisee', 'suspendue', 'archive',
            ])->default('planifiee');
            $table->integer('ordre')->default(0);
            $table->decimal('poids', 5, 2)->default(100);
            $table->decimal('taux_execution', 5, 2)->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigne_a_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['activite_id', 'code']);
        });

        Schema::create('activite_dependances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activite_id')->constrained('activites')->cascadeOnDelete();
            $table->foreignId('depend_de_id')->constrained('activites')->cascadeOnDelete();
            $table->enum('type', ['fin_debut', 'debut_debut', 'fin_fin', 'debut_fin'])->default('fin_debut');
            $table->integer('decalage_jours')->default(0);
            $table->timestamps();

            $table->unique(['activite_id', 'depend_de_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activite_dependances');
        Schema::dropIfExists('taches');
        Schema::dropIfExists('activites');
    }
};
