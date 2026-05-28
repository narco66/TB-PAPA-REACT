<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workflow de validation (Section 4 RACI et 5.4 du CDC).
 * Traçabilité complète de toutes les validations / rejets / arbitrages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validations', function (Blueprint $table) {
            $table->id();
            $table->morphs('validable');
            $table->enum('etape', [
                'soumission',
                'revue_technique',
                'validation_directeur',
                'validation_commissaire',
                'validation_sg',
                'validation_presidence',
                'cloture',
            ]);
            $table->enum('decision', ['en_attente', 'approuve', 'rejete', 'renvoye'])->default('en_attente');
            $table->foreignId('demandeur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valideur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decide_at')->nullable();
            $table->text('commentaire')->nullable();
            $table->json('donnees_avant')->nullable(); // snapshot pour audit
            $table->json('donnees_apres')->nullable();
            $table->timestamps();

            $table->index(['validable_type', 'validable_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validations');
    }
};
