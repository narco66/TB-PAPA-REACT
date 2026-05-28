<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertes, risques et escalades — Section 5.3.9 / 6.10 du CDC.
 * Détection automatique : retards, dérives budgétaires, sous-performance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table) {
            $table->id();
            $table->morphs('alertable'); // PAPA, Action, Activité, Indicateur, Budget
            $table->enum('niveau', ['info', 'attention', 'critique'])->index();
            $table->enum('categorie', [
                'retard',
                'derive_budgetaire',
                'sous_performance',
                'incoherence',
                'risque',
                'autre',
            ])->index();
            $table->string('titre');
            $table->text('message');
            $table->json('contexte')->nullable(); // données contextuelles
            $table->boolean('automatique')->default(true);
            $table->enum('statut', ['ouverte', 'en_traitement', 'resolue', 'ignoree'])->default('ouverte')->index();
            $table->foreignId('assignee_a_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolue_at')->nullable();
            $table->foreignId('resolue_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('action_corrective')->nullable();
            $table->timestamps();

            $table->index(['statut', 'niveau']);
        });

        Schema::create('alerte_destinataires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerte_id')->constrained('alertes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notifie_at')->nullable();
            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            $table->unique(['alerte_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerte_destinataires');
        Schema::dropIfExists('alertes');
    }
};
