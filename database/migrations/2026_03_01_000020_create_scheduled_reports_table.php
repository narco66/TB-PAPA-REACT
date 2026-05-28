<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planification de rapports récurrents (mensuel, trimestriel, semestriel, annuel,
 * sur événement : après validation PAPA, après clôture exercice, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('report_key', 64)->index();
            $table->json('filtres_par_defaut')->nullable();
            $table->enum('frequence', [
                'manuel', 'quotidien', 'hebdomadaire', 'mensuel',
                'trimestriel', 'semestriel', 'annuel',
                'sur_validation_papa', 'sur_cloture_exercice', 'sur_validation_axe',
            ])->default('manuel');
            $table->string('cron_expression', 64)->nullable(); // pour les fréquences personnalisées
            $table->json('destinataires_emails')->nullable();
            $table->boolean('archiver_ged')->default(true);
            $table->boolean('actif')->default(true);
            $table->timestamp('prochaine_execution_at')->nullable();
            $table->timestamp('derniere_execution_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
