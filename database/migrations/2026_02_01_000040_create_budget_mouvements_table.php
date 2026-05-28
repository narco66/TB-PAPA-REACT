<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mouvements budgétaires (engagement, liquidation, ordonnancement, paiement).
 * Chaque opération financière sur une ligne génère un mouvement tracé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_id')->constrained('budget_lignes')->cascadeOnDelete();
            $table->enum('type', [
                'engagement', 'liquidation', 'ordonnancement', 'paiement',
                'desengagement', 'ajustement', 'transfert',
            ])->index();
            $table->decimal('montant', 20, 2);
            $table->date('date_mouvement');
            $table->string('reference', 128)->nullable();
            $table->text('motif')->nullable();
            $table->foreignId('saisi_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ligne_id', 'date_mouvement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_mouvements');
    }
};
