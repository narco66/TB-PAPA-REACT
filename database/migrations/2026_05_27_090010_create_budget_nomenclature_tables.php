<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_chapitres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercice_id')->constrained('budget_exercices')->cascadeOnDelete();
            $table->string('code', 8);
            $table->string('libelle');
            $table->enum('statut', ['brouillon', 'actif', 'archive'])->default('actif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['exercice_id', 'code']);
        });

        Schema::create('budget_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapitre_id')->constrained('budget_chapitres')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('budget_exercices')->cascadeOnDelete();
            $table->string('code', 8);
            $table->string('libelle');
            $table->enum('statut', ['brouillon', 'actif', 'archive'])->default('actif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['exercice_id', 'code']);
            $table->index(['chapitre_id', 'code']);
        });

        Schema::create('budget_paragraphes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('budget_articles')->cascadeOnDelete();
            $table->foreignId('exercice_id')->constrained('budget_exercices')->cascadeOnDelete();
            $table->string('code', 8);
            $table->string('libelle');
            $table->enum('statut', ['brouillon', 'actif', 'archive'])->default('actif')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['exercice_id', 'code']);
            $table->index(['article_id', 'code']);
        });

        Schema::table('budget_lignes', function (Blueprint $table) {
            $table->foreignId('budget_chapitre_id')->nullable()->after('exercice_id')
                ->constrained('budget_chapitres')->nullOnDelete();
            $table->foreignId('budget_article_id')->nullable()->after('budget_chapitre_id')
                ->constrained('budget_articles')->nullOnDelete();
            $table->foreignId('budget_paragraphe_id')->nullable()->after('budget_article_id')
                ->constrained('budget_paragraphes')->nullOnDelete();
            $table->string('budget_ligne_code', 16)->nullable()->after('paragraphe_code');
            $table->string('devise', 3)->default('XAF')->after('montant_ptf');
            $table->foreignId('validated_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->unsignedInteger('version')->default(1)->after('validated_at');

            $table->unique(['exercice_id', 'budget_ligne_code']);
            $table->index(['budget_chapitre_id', 'budget_article_id', 'budget_paragraphe_id'], 'budget_lignes_nomenclature_idx');
        });
    }

    public function down(): void
    {
        Schema::table('budget_lignes', function (Blueprint $table) {
            $table->dropIndex('budget_lignes_nomenclature_idx');
            $table->dropUnique(['exercice_id', 'budget_ligne_code']);
            $table->dropConstrainedForeignId('budget_chapitre_id');
            $table->dropConstrainedForeignId('budget_article_id');
            $table->dropConstrainedForeignId('budget_paragraphe_id');
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['budget_ligne_code', 'devise', 'validated_at', 'version']);
        });

        Schema::dropIfExists('budget_paragraphes');
        Schema::dropIfExists('budget_articles');
        Schema::dropIfExists('budget_chapitres');
    }
};
