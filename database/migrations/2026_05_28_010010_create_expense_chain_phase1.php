<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Chaîne de la dépense TB-PAPA-CEEAC
 *
 * Tables créées (non destructif) :
 *   - suppliers              → Fournisseurs (distincts des partenaires PTF financiers)
 *   - expense_requests       → Expression du besoin (amont du cycle IPSAS)
 *   - commitment_lines       → Multi-imputation d'un engagement (1 mouvement = N lignes budgétaires)
 *
 * Extensions de budget_mouvements (non destructives) :
 *   - expense_request_id (lien vers l'expression du besoin source)
 *   - supplier_id        (fournisseur retenu, en plus de beneficiaire_nom existant)
 *   - type_engagement    (parmi les 18 types métiers)
 *
 * Conformité :
 *   - RGCP — chaîne de la dépense publique (Règlement Général Comptabilité Publique)
 *   - OHADA — système comptable Afrique centrale
 *   - COSO ERM — contrôle interne, séparation des fonctions
 */
return new class extends Migration
{
    public function up(): void
    {
        // === Fournisseurs ===
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('libelle', 191);
            $table->enum('type', ['personne_physique', 'personne_morale', 'administration', 'organisme_public', 'autre'])
                ->default('personne_morale')->index();
            $table->string('nif', 32)->nullable()->comment('Numéro d\'Identification Fiscale');
            $table->string('rccm', 64)->nullable()->comment('Registre du Commerce et du Crédit Mobilier');
            $table->string('contact_principal', 191)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('telephone', 32)->nullable();
            $table->text('adresse')->nullable();
            $table->string('pays', 64)->nullable();
            $table->string('compte_bancaire', 64)->nullable();
            $table->string('banque', 191)->nullable();
            $table->enum('statut', ['actif', 'suspendu', 'archive'])->default('actif')->index();
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // === Expression du besoin ===
        Schema::create('expense_requests', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 32)->unique()->comment('Numéro institutionnel EB-YYYY-NNNNN');
            $table->foreignId('exercice_id')->constrained('budget_exercices')->restrictOnDelete();
            $table->foreignId('demandeur_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->foreignId('direction_id')->nullable()->constrained('directions')->nullOnDelete();
            $table->foreignId('activite_id')->nullable()->constrained('activites')->nullOnDelete();
            $table->foreignId('tache_id')->nullable()->constrained('taches')->nullOnDelete();

            // 18 types d'engagements (alignés sur la nomenclature CEEAC)
            $table->enum('type_engagement', [
                'achat_biens', 'prestation_services', 'travaux', 'mission_officielle',
                'formation_atelier', 'contrat_convention', 'subvention', 'appui_institutionnel',
                'fonctionnement', 'investissement', 'frais_personnel', 'regularisation',
                'complementaire', 'modificatif', 'pluriannuel',
                'financement_ceeac', 'financement_ptf', 'financement_mixte',
            ])->index();

            $table->string('objet', 255);
            $table->text('justification');
            $table->text('description_detaillee')->nullable();

            $table->decimal('montant_estime', 18, 2);
            $table->decimal('montant_estime_ceeac', 18, 2)->default(0);
            $table->decimal('montant_estime_ptf', 18, 2)->default(0);
            $table->string('devise', 8)->default('XAF');

            $table->foreignId('source_financement_id')->nullable()
                ->constrained('budget_sources_financement')->nullOnDelete();
            $table->foreignId('supplier_pressenti_id')->nullable()
                ->constrained('suppliers')->nullOnDelete()->comment('Fournisseur pressenti à ce stade (modifiable)');

            $table->date('date_besoin_prevu')->nullable();
            $table->date('date_livraison_souhaitee')->nullable();

            // Workflow
            $table->enum('statut', [
                'brouillon', 'soumis', 'en_validation_hierarchique', 'retourne_correction',
                'rejete', 'valide', 'engage', 'annule',
            ])->default('brouillon')->index();

            $table->foreignId('valideur_hierarchique_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->text('motif_decision')->nullable()->comment('Motif validation/rejet/retour');

            // Lien vers l'engagement créé après validation (BudgetMouvement type=engagement)
            $table->foreignId('budget_mouvement_engagement_id')->nullable()
                ->constrained('budget_mouvements')->nullOnDelete()
                ->comment('Engagement créé après validation');

            $table->integer('priorite')->default(3)->comment('1=urgente, 2=haute, 3=normale, 4=basse');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'exercice_id']);
            $table->index(['demandeur_id', 'statut']);
        });

        // === Lignes d'engagement (multi-imputation) ===
        Schema::create('commitment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_mouvement_id')->constrained('budget_mouvements')->cascadeOnDelete()
                ->comment('Mouvement budgétaire racine (engagement)');
            $table->foreignId('budget_ligne_id')->constrained('budget_lignes')->restrictOnDelete();
            $table->string('libelle', 255);
            $table->decimal('montant', 18, 2);
            $table->decimal('montant_ceeac', 18, 2)->default(0);
            $table->decimal('montant_ptf', 18, 2)->default(0);
            $table->foreignId('source_financement_id')->nullable()
                ->constrained('budget_sources_financement')->nullOnDelete();
            $table->string('imputation_analytique', 64)->nullable()
                ->comment('Centre de coût analytique optionnel');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['budget_mouvement_id', 'budget_ligne_id']);
        });

        // === Extensions non destructives de budget_mouvements ===
        Schema::table('budget_mouvements', function (Blueprint $table) {
            $table->foreignId('expense_request_id')->nullable()->after('ligne_id')
                ->constrained('expense_requests')->nullOnDelete()
                ->comment('Lien vers l\'expression du besoin source');

            $table->foreignId('supplier_id')->nullable()->after('partenaire_id')
                ->constrained('suppliers')->nullOnDelete()
                ->comment('Fournisseur retenu');

            $table->enum('type_engagement', [
                'achat_biens', 'prestation_services', 'travaux', 'mission_officielle',
                'formation_atelier', 'contrat_convention', 'subvention', 'appui_institutionnel',
                'fonctionnement', 'investissement', 'frais_personnel', 'regularisation',
                'complementaire', 'modificatif', 'pluriannuel',
                'financement_ceeac', 'financement_ptf', 'financement_mixte',
            ])->nullable()->after('type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('budget_mouvements', function (Blueprint $table) {
            $table->dropForeign(['expense_request_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['expense_request_id', 'supplier_id', 'type_engagement']);
        });

        Schema::dropIfExists('commitment_lines');
        Schema::dropIfExists('expense_requests');
        Schema::dropIfExists('suppliers');
    }
};
