<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cycle budgétaire IPSAS complet :
 *   Engagement → Liquidation → Ordonnancement → Paiement
 *
 * Extension non destructive de `budget_mouvements` :
 *   - chaînage parent_mouvement_id pour suivre le cycle de bout en bout
 *   - séparation ordonnateur / comptable (règle COSO ERM)
 *   - tracé tiers bénéficiaire, mode de paiement, pièce justificative
 *
 * Conforme IPSAS 24 (information budgétaire) et CDC § 6.4 (exécution budgétaire).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_mouvements', function (Blueprint $table) {
            // === Chaînage du cycle (Engagement → Liquidation → Ordonnancement → Paiement) ===
            $table->foreignId('parent_mouvement_id')->nullable()->after('ligne_id')
                ->constrained('budget_mouvements')->nullOnDelete()
                ->comment('Mouvement précédent dans la chaîne IPSAS');

            // === Numéro de pièce comptable institutionnel ===
            $table->string('numero_piece', 64)->nullable()->after('reference')
                ->comment('Numéro de bon de commande / titre / mandat / virement');

            // === Tiers bénéficiaire ===
            $table->string('beneficiaire_nom', 191)->nullable()->after('numero_piece');
            $table->string('beneficiaire_reference', 64)->nullable()->after('beneficiaire_nom')
                ->comment('Identifiant fournisseur / matricule / NIF / RCCM');
            $table->foreignId('partenaire_id')->nullable()->after('beneficiaire_reference')
                ->constrained('partenaires')->nullOnDelete();

            // === Séparation ordonnateur / comptable (COSO ERM) ===
            $table->foreignId('ordonnateur_id')->nullable()->after('partenaire_id')
                ->constrained('users')->nullOnDelete()
                ->comment('Autorisateur (engagement / ordonnancement)');
            $table->foreignId('comptable_id')->nullable()->after('ordonnateur_id')
                ->constrained('users')->nullOnDelete()
                ->comment('Comptable assignataire (paiement)');

            // === Mode de paiement (pour les paiements effectifs) ===
            $table->enum('mode_paiement', ['virement', 'cheque', 'especes', 'mobile_money', 'autre'])
                ->nullable()->after('comptable_id');
            $table->string('compte_bancaire', 64)->nullable()->after('mode_paiement');
            $table->date('date_valeur')->nullable()->after('compte_bancaire')
                ->comment('Date d\'effet bancaire du paiement');

            // === Statut du mouvement ===
            $table->enum('statut_mouvement', ['en_attente', 'valide', 'rejete', 'annule'])
                ->default('valide')->after('date_valeur');
            $table->text('piece_justificative')->nullable()->after('statut_mouvement')
                ->comment('Liens/références aux pièces justificatives stockées en GED');

            $table->index(['ligne_id', 'type', 'statut_mouvement']);
        });
    }

    public function down(): void
    {
        Schema::table('budget_mouvements', function (Blueprint $table) {
            $table->dropIndex(['ligne_id', 'type', 'statut_mouvement']);
            $table->dropConstrainedForeignId('parent_mouvement_id');
            $table->dropConstrainedForeignId('partenaire_id');
            $table->dropConstrainedForeignId('ordonnateur_id');
            $table->dropConstrainedForeignId('comptable_id');
            $table->dropColumn([
                'numero_piece',
                'beneficiaire_nom',
                'beneficiaire_reference',
                'mode_paiement',
                'compte_bancaire',
                'date_valeur',
                'statut_mouvement',
                'piece_justificative',
            ]);
        });
    }
};
