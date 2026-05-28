<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Chaîne de la dépense TB-PAPA-CEEAC
 *
 * Tables créées (non destructif) :
 *   - service_done_certificates → Certificats de service fait (constatation de l'exécution)
 *   - receptions                → Procès-verbaux de réception (biens, services, travaux)
 *   - liquidation_details       → Détails de liquidation (retenues, pénalités, avances)
 *
 * Conformité :
 *   - RGCP — service fait obligatoire avant liquidation
 *   - OHADA — réception et constatation
 *   - COSO ERM — séparation des fonctions (commission de réception ≠ ordonnateur)
 */
return new class extends Migration
{
    public function up(): void
    {
        // === Certificats de service fait ===
        Schema::create('service_done_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_mouvement_id')->constrained('budget_mouvements')->cascadeOnDelete()
                ->comment('Mouvement engagement source');
            $table->string('reference', 64)->unique()->comment('SF-YYYY-NNNNN');
            $table->date('date_constatation');

            $table->text('description');
            $table->decimal('montant_constate', 18, 2)->comment('Montant des prestations effectivement réalisées');

            $table->enum('conformite_qualitative', ['conforme', 'partiellement_conforme', 'non_conforme'])
                ->default('conforme');
            $table->enum('conformite_quantitative', ['conforme', 'partiellement_conforme', 'non_conforme'])
                ->default('conforme');
            $table->text('observations')->nullable();

            $table->foreignId('constate_par_id')->constrained('users')->restrictOnDelete()
                ->comment('Constateur du service fait (souvent chef de service ou directeur)');
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->enum('statut', ['projet', 'valide', 'rejete'])->default('projet')->index();
            $table->text('motif_rejet')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['budget_mouvement_id', 'statut']);
        });

        // === Procès-verbaux de réception ===
        Schema::create('receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_mouvement_id')->constrained('budget_mouvements')->cascadeOnDelete();
            $table->foreignId('service_done_certificate_id')->nullable()
                ->constrained('service_done_certificates')->nullOnDelete()
                ->comment('Lien optionnel vers le certificat de service fait associé');
            $table->string('reference', 64)->unique()->comment('PV-YYYY-NNNNN');
            $table->date('date_reception');

            $table->enum('type_reception', ['provisoire', 'definitive', 'partielle', 'refus'])
                ->default('definitive')->index();
            $table->enum('nature', ['biens', 'services', 'travaux'])->index();

            $table->decimal('quantite_recue', 18, 3)->nullable();
            $table->string('unite_mesure', 32)->nullable();
            $table->decimal('montant_recu', 18, 2);

            $table->enum('conformite', ['conforme', 'reserves', 'non_conforme'])
                ->default('conforme')->index();
            $table->text('reserves')->nullable();
            $table->text('observations')->nullable();

            // Commission de réception (au minimum 3 membres COSO)
            $table->foreignId('president_commission_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('membre1_commission_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('membre2_commission_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('saisi_par_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();

            $table->enum('statut', ['projet', 'valide', 'rejete', 'cloture'])->default('projet')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // === Détails de liquidation (retenues, pénalités, avances) ===
        Schema::create('liquidation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_mouvement_id')->constrained('budget_mouvements')->cascadeOnDelete()
                ->comment('Mouvement liquidation parent');

            $table->decimal('montant_brut', 18, 2)->comment('Montant avant retenues');
            $table->decimal('avance_deduite', 18, 2)->default(0)->comment('Avance précédemment versée');
            $table->decimal('retenue_garantie', 18, 2)->default(0)->comment('Retenue de garantie (% du marché)');
            $table->decimal('retenue_fiscale', 18, 2)->default(0)->comment('IRPP, TVA, etc.');
            $table->decimal('autres_retenues', 18, 2)->default(0);
            $table->decimal('penalites_retard', 18, 2)->default(0)->comment('Pénalités de retard de livraison');
            $table->decimal('autres_penalites', 18, 2)->default(0);

            // Calculé : montant_brut - avance - retenues - pénalités
            $table->decimal('montant_net_a_payer', 18, 2);

            $table->text('justificatifs')->nullable()->comment('Liste des pièces justificatives');
            $table->text('observations')->nullable();

            $table->foreignId('liquide_par_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidation_details');
        Schema::dropIfExists('receptions');
        Schema::dropIfExists('service_done_certificates');
    }
};
