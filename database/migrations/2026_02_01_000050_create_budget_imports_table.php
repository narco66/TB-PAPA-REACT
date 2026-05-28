<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des imports budgétaires (audit + reprise sur incident).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->nullable()->constrained('budget_exercices')->nullOnDelete();
            $table->string('fichier_nom');
            $table->string('chemin_stockage')->nullable();
            $table->unsignedBigInteger('taille_octets')->default(0);
            $table->string('hash_sha256', 64)->nullable();
            $table->enum('statut', ['prevu', 'en_cours', 'reussi', 'echec', 'rollback'])->default('prevu');
            $table->unsignedInteger('nb_lignes_lues')->default(0);
            $table->unsignedInteger('nb_lignes_creees')->default(0);
            $table->unsignedInteger('nb_lignes_mises_a_jour')->default(0);
            $table->unsignedInteger('nb_erreurs')->default(0);
            $table->json('journal')->nullable(); // erreurs détaillées
            $table->foreignId('execute_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('execute_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_imports');
    }
};
