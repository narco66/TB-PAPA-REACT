<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Axe stratégique — niveau 1 de la chaîne RBM/GAR officielle CEEAC.
 * Codification : « AXE 1 », « AXE 2 », etc.
 * Un Axe est rattaché à un PAPA annuel et porté par un Département / Commissaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('papa_id')->constrained('papas')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->enum('statut', ['brouillon', 'soumis', 'en_validation', 'valide', 'rejete', 'archive'])
                ->default('brouillon')->index();
            $table->integer('ordre')->default(0);
            $table->decimal('poids', 5, 2)->default(100); // pondération par défaut
            $table->decimal('taux_execution', 5, 2)->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['papa_id', 'code']);
            $table->index(['papa_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('axes');
    }
};
