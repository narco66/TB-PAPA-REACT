<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel des sources de financement (CEEAC-EM, BAD, UE, BM, AUDA-NEPAD, etc.).
 * Lié à la table partenaires pour les bailleurs extérieurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_sources_financement', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('libelle');
            $table->enum('type', ['interne', 'externe'])->index(); // interne=CEEAC-EM, externe=PTF
            $table->enum('categorie', ['etat_membre', 'don_projet', 'multilateral', 'bilateral', 'autre'])
                ->default('autre');
            $table->foreignId('partenaire_id')->nullable()->constrained('partenaires')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_sources_financement');
    }
};
