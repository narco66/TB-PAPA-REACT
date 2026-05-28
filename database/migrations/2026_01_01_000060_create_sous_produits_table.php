<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sous-Produit — niveau 3 de la chaîne RBM CEEAC (sous-livrable détaillé).
 * Codification : « SP.1.1.1 » (SP.{ordre_axe}.{ordre_produit}.{ordre_sp}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sous_produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->enum('statut', ['brouillon', 'soumis', 'en_validation', 'valide', 'rejete', 'archive'])
                ->default('brouillon')->index();
            $table->integer('ordre')->default(0);
            $table->decimal('poids', 5, 2)->default(100);
            $table->decimal('taux_execution', 5, 2)->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('direction_id')->nullable()->constrained('directions')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['produit_id', 'code']);
            $table->index(['produit_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sous_produits');
    }
};
