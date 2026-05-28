<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des rapports PDF générés.
 * Chaque génération crée une entrée avec hash, filtres, dimensions, auteur.
 * Sert d'audit + reprise + téléchargement ultérieur (cache).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_key', 64)->index(); // ex: 'papa_strategique', 'budget_consolide'
            $table->string('categorie', 32)->index(); // strategique, budget, performance, gouvernance, audit, analytique, operationnel, ptf, ged
            $table->string('titre');
            $table->text('description')->nullable();

            // Filtres appliqués (year, exercice_id, papa_id, axe_id, etc.)
            $table->json('filtres')->nullable();

            // Fichier généré
            $table->string('chemin_stockage');
            $table->string('nom_fichier');
            $table->unsignedBigInteger('taille_octets')->default(0);
            $table->string('hash_sha256', 64);
            $table->string('format', 16)->default('pdf'); // pdf, xlsx, csv
            $table->unsignedSmallInteger('nb_pages')->nullable();

            // Sécurité / traçabilité
            $table->string('code_verification', 32)->unique(); // utilisé dans le QR code
            $table->boolean('signe_numeriquement')->default(false);
            $table->string('signature_hash', 128)->nullable();

            // Audit
            $table->foreignId('genere_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('genere_at');
            $table->unsignedInteger('nb_telechargements')->default(0);
            $table->timestamp('dernier_telechargement_at')->nullable();
            $table->foreignId('dernier_telechargement_par_id')->nullable()->constrained('users')->nullOnDelete();

            // Archivage GED
            $table->boolean('archive_ged')->default(false);
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();

            $table->enum('statut', ['en_cours', 'pret', 'echec', 'archive', 'expire'])->default('pret')->index();
            $table->text('erreur')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_key', 'genere_at']);
            $table->index(['categorie', 'genere_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
