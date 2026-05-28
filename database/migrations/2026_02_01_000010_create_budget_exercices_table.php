<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exercices budgétaires annuels CEEAC (référentiel de tête).
 * Un exercice = une année budgétaire avec totaux consolidés CEEAC-EM + PTF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_exercices', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->enum('statut', ['brouillon', 'importe', 'controle', 'soumis', 'valide', 'rejete', 'cloture', 'archive'])
                ->default('brouillon')->index();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('devise', 8)->default('XAF');
            // Totaux consolidés (recalculés depuis les lignes)
            $table->decimal('total_recettes', 20, 2)->default(0);
            $table->decimal('total_recettes_internes', 20, 2)->default(0);
            $table->decimal('total_recettes_externes', 20, 2)->default(0);
            $table->decimal('total_depenses', 20, 2)->default(0);
            $table->decimal('total_depenses_ceeac_em', 20, 2)->default(0);
            $table->decimal('total_depenses_ptf', 20, 2)->default(0);
            $table->decimal('total_fonctionnement', 20, 2)->default(0);
            $table->decimal('total_investissement', 20, 2)->default(0);
            $table->decimal('total_equipement', 20, 2)->default(0);
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_exercices');
    }
};
