<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GED — Gestion Électronique des Documents (Section 13 du CDC).
 * "Pas de document, pas de résultat".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // PAPA, Action, Objectif, Résultat, Activité, Indicateur, Budget
            $table->enum('categorie', [
                'execution',      // rapports d'activités, PV, livrables
                'validation',     // visas, décisions
                'financier',      // états budgétaires, pièces comptables
                'suivi_evaluation', // fiches indicateurs, rapports analytiques
                'autre',
            ])->index();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->string('nom_fichier');
            $table->string('chemin_stockage');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('taille_octets')->default(0);
            $table->string('hash_sha256', 64)->nullable(); // intégrité
            $table->foreignId('uploade_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('confidentiel')->default(false);
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignId('document_parent_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['categorie', 'valide_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
