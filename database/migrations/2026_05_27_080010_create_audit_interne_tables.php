<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Audit interne IGS (Inspection Générale des Services).
 *
 * Conformité :
 *   - IIA — IPPF Standards (International Professional Practices Framework)
 *   - IFACI — Cadre de référence de l'audit interne
 *   - ISO 19011 — Lignes directrices pour l'audit des systèmes de management
 *   - COSO Internal Control - Integrated Framework
 *
 * 5 tables :
 *   audit_plans               → programme annuel d'audit
 *   audit_missions            → missions individuelles
 *   audit_mission_equipe      → équipe par mission (pivot user × rôle)
 *   audit_constats            → constats par mission
 *   audit_recommandations     → recommandations par constat
 *   audit_suivis_recommandations → traçabilité du suivi de mise en œuvre
 */
return new class extends Migration
{
    public function up(): void
    {
        // === Plan d'audit annuel ===
        Schema::create('audit_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->text('orientation_strategique')->nullable()->comment('Axes prioritaires de l\'audit annuel');
            $table->enum('statut', ['projet', 'soumis', 'valide', 'execute', 'cloture', 'archive'])
                ->default('projet')->index();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // === Missions d'audit ===
        Schema::create('audit_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('audit_plans')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('titre');
            $table->text('objectifs')->nullable();
            $table->text('perimetre')->nullable();
            $table->enum('type', [
                'financier', 'conformite', 'performance', 'systeme_information',
                'organisationnel', 'thematique', 'suivi',
            ])->default('conformite')->index();
            $table->enum('priorite', ['critique', 'haute', 'moyenne', 'basse'])
                ->default('moyenne')->index();
            $table->enum('statut', [
                'planifiee', 'lettre_emise', 'en_cours', 'projet_rapport',
                'rapport_definitif', 'cloturee', 'annulee',
            ])->default('planifiee')->index();
            $table->date('date_debut_prevue')->nullable();
            $table->date('date_fin_prevue')->nullable();
            $table->date('date_debut_reelle')->nullable();
            $table->date('date_fin_reelle')->nullable();
            $table->foreignId('chef_mission_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('departement_audite_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->foreignId('direction_auditee_id')->nullable()->constrained('directions')->nullOnDelete();
            $table->text('lettre_mission')->nullable()->comment('Texte ou référence GED');
            $table->text('synthese')->nullable()->comment('Synthèse exécutive du rapport final');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['plan_id', 'statut']);
        });

        // === Équipe de mission (pivot) ===
        Schema::create('audit_mission_equipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('audit_missions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_mission', ['chef', 'auditeur_senior', 'auditeur', 'observateur', 'expert_externe'])
                ->default('auditeur');
            $table->timestamps();

            $table->unique(['mission_id', 'user_id']);
        });

        // === Constats ===
        Schema::create('audit_constats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('audit_missions')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('libelle');
            $table->text('description');
            $table->text('preuves')->nullable()->comment('Pièces justificatives, références GED');
            $table->enum('gravite', ['critique', 'majeur', 'moyen', 'mineur', 'observation'])
                ->default('moyen')->index();
            $table->enum('nature', [
                'non_conformite', 'risque', 'inefficacite', 'inefficience',
                'controle_insuffisant', 'bonne_pratique', 'autre',
            ])->default('non_conformite');
            $table->text('cause_racine')->nullable();
            $table->text('impact')->nullable();
            $table->foreignId('saisi_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mission_id', 'code']);
            $table->index(['mission_id', 'gravite']);
        });

        // === Recommandations ===
        Schema::create('audit_recommandations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constat_id')->constrained('audit_constats')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('libelle');
            $table->text('action_proposee');
            $table->enum('priorite', ['urgente', 'haute', 'moyenne', 'basse'])
                ->default('moyenne')->index();
            $table->foreignId('responsable_mise_en_oeuvre_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->date('date_echeance')->nullable();
            $table->enum('statut', [
                'ouverte', 'planifiee', 'en_cours', 'mise_en_oeuvre',
                'verifiee', 'rejetee', 'abandonnee',
            ])->default('ouverte')->index();
            $table->unsignedTinyInteger('pourcentage_avancement')->default(0);
            $table->foreignId('saisi_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['constat_id', 'code']);
            $table->index(['statut', 'priorite']);
        });

        // === Suivis des recommandations ===
        Schema::create('audit_suivis_recommandations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommandation_id')->constrained('audit_recommandations')->cascadeOnDelete();
            $table->date('date_suivi');
            $table->enum('etat_avancement', ['non_demarre', 'en_cours', 'realise', 'bloque', 'abandonne'])
                ->default('en_cours');
            $table->unsignedTinyInteger('pourcentage')->default(0);
            $table->text('actions_realisees')->nullable();
            $table->text('actions_restantes')->nullable();
            $table->text('blocages')->nullable();
            $table->text('commentaire')->nullable();
            $table->foreignId('suivi_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['recommandation_id', 'date_suivi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_suivis_recommandations');
        Schema::dropIfExists('audit_recommandations');
        Schema::dropIfExists('audit_constats');
        Schema::dropIfExists('audit_mission_equipe');
        Schema::dropIfExists('audit_missions');
        Schema::dropIfExists('audit_plans');
    }
};
