<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Budgets — Section 5.3.8 / 6.7 du CDC.
 * Distingue obligatoirement budget CEEAC vs Partenaires.
 * Phases : prévision → engagement → consommation → reste à engager.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('libelle');
            $table->string('type')->nullable(); // PTF, bilatéral, multilatéral
            $table->string('contact_principal')->nullable();
            $table->string('email')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            // Morph : un budget peut être rattaché à une Action ou une Activité
            $table->morphs('budgetable');
            $table->foreignId('papa_id')->constrained('papas')->cascadeOnDelete();
            $table->enum('source', ['ceeac', 'partenaire'])->index();
            $table->foreignId('partenaire_id')->nullable()->constrained('partenaires')->nullOnDelete();
            $table->string('devise', 8)->default('XAF');
            $table->decimal('prevision', 18, 2)->default(0);
            $table->decimal('engagement', 18, 2)->default(0);
            $table->decimal('consommation', 18, 2)->default(0);
            $table->decimal('reste_a_engager', 18, 2)->default(0);
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['papa_id', 'source']);
        });

        Schema::create('mouvements_budgetaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->enum('type', ['engagement', 'consommation', 'desengagement', 'ajustement']);
            $table->decimal('montant', 18, 2);
            $table->date('date_mouvement');
            $table->string('reference')->nullable(); // bon de commande, mandat, etc.
            $table->text('motif')->nullable();
            $table->foreignId('saisi_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['budget_id', 'date_mouvement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_budgetaires');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('partenaires');
    }
};
