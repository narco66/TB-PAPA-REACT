<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PAPA — Plan d'Action Prioritaire Annuel (entité racine du système).
 * Section 5.3.1 du CDC.
 * Statuts : brouillon → en_validation → valide → revise → cloture → archive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papas', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee');
            $table->string('version', 16)->default('1.0');
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->text('perimetre_institutionnel')->nullable();
            $table->enum('statut', [
                'brouillon',
                'en_validation',
                'valide',
                'revise',
                'cloture',
                'archive',
            ])->default('brouillon')->index();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamp('date_validation')->nullable();
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cloture_le')->nullable();
            $table->foreignId('cloture_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('verrouille')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Un seul PAPA actif par année + version
            $table->unique(['annee', 'version']);
            $table->index(['annee', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('papas');
    }
};
