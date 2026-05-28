<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produit — niveau 2 de la chaîne RBM CEEAC (livrable institutionnel).
 * Codification : « P.1.1 » (P.{ordre_axe}.{ordre_produit}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('axe_id')->constrained('axes')->cascadeOnDelete();
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

            $table->unique(['axe_id', 'code']);
            $table->index(['axe_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
