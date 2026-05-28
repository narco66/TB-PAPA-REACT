<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes budgétaires institutionnelles CEEAC.
 *
 * Structure conforme au budget réel CEEAC 2026 :
 *   Titre → Chapitre → Article → Paragraphe → Code action/projet
 *
 * Chaque ligne distingue :
 *   - Montant total (= CEEAC-EM + PTF)
 *   - Part CEEAC-EM (États Membres)
 *   - Part PTF (Partenaires Techniques et Financiers)
 *
 * Et est optionnellement rattachée à la chaîne RBM (Axe/Produit/SP/Activité/Tâche).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('budget_exercices')->cascadeOnDelete();

            // Hiérarchie native (parent/enfant) pour l'arborescence du budget
            $table->foreignId('parent_id')->nullable()->constrained('budget_lignes')->nullOnDelete();
            $table->unsignedTinyInteger('niveau')->default(0); // 0=racine, 1=titre, 2=chapitre, etc.

            // Nomenclature budgétaire officielle (alignée sur le PDF de la CEEAC)
            $table->string('titre_code', 16)->nullable()->index();      // ex: "Titre 1", "Titre 2"
            $table->string('chapitre_code', 16)->nullable()->index();   // ex: "66", "67", "60"
            $table->string('article_code', 16)->nullable()->index();    // ex: "661", "671"
            $table->string('paragraphe_code', 16)->nullable()->index(); // ex: "6610", "6611"
            $table->string('code_action', 32)->nullable()->index();     // ex: "66101", "201131"
            $table->string('code_projet', 32)->nullable();

            $table->string('libelle');
            $table->text('description')->nullable();

            // Catégorisation économique (Recettes / Dépenses)
            $table->enum('nature', ['recette', 'depense'])->default('depense')->index();
            $table->enum('type_budget', [
                'recette_interne', 'recette_externe',
                'fonctionnement', 'investissement', 'equipement',
                'dette', 'dotation', 'transfert', 'autre',
            ])->default('fonctionnement')->index();

            // Pilier (pour les dépenses du Plan Annuel de Performance)
            $table->unsignedTinyInteger('pilier')->nullable()->index();

            // Montants exercice courant
            $table->decimal('montant_total', 20, 2)->default(0);
            $table->decimal('montant_ceeac_em', 20, 2)->default(0);
            $table->decimal('montant_ptf', 20, 2)->default(0);

            // Comparaison avec exercice précédent
            $table->decimal('budget_annee_precedente', 20, 2)->default(0);
            $table->decimal('realisation_annee_precedente', 20, 2)->default(0);
            $table->decimal('taux_realisation_precedent', 5, 2)->nullable();
            $table->decimal('variation', 7, 2)->nullable(); // en %

            // Exécution courante (alimentée par les mouvements)
            $table->decimal('montant_engage', 20, 2)->default(0);
            $table->decimal('montant_liquide', 20, 2)->default(0);
            $table->decimal('montant_ordonnance', 20, 2)->default(0);
            $table->decimal('montant_paye', 20, 2)->default(0);
            $table->decimal('montant_disponible', 20, 2)->default(0);
            $table->decimal('taux_consommation', 5, 2)->default(0);

            // Source de financement
            $table->foreignId('source_financement_id')->nullable()
                ->constrained('budget_sources_financement')->nullOnDelete();
            $table->foreignId('partenaire_id')->nullable()->constrained('partenaires')->nullOnDelete();

            // Rattachement à la chaîne RBM (CEEAC : Axe → Produit → SousProduit → Activité → Tâche)
            $table->foreignId('axe_id')->nullable()->constrained('axes')->nullOnDelete();
            $table->foreignId('produit_id')->nullable()->constrained('produits')->nullOnDelete();
            $table->foreignId('sous_produit_id')->nullable()->constrained('sous_produits')->nullOnDelete();
            $table->foreignId('activite_id')->nullable()->constrained('activites')->nullOnDelete();
            $table->foreignId('tache_id')->nullable()->constrained('taches')->nullOnDelete();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->foreignId('direction_id')->nullable()->constrained('directions')->nullOnDelete();

            // Workflow
            $table->enum('statut', ['brouillon', 'importe', 'controle', 'soumis', 'valide', 'rejete', 'cloture', 'archive'])
                ->default('brouillon')->index();
            $table->integer('ordre')->default(0);
            $table->text('observations')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exercice_id', 'nature']);
            $table->index(['exercice_id', 'type_budget']);
            $table->index(['exercice_id', 'pilier']);
            $table->index(['exercice_id', 'parent_id']);
            $table->index(['exercice_id', 'code_action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lignes');
    }
};
