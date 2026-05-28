<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Évolution non destructive du module d'import :
 *  - support multi-feuilles (parent_id + feuille_source)
 *  - mappings réutilisables par utilisateur/exercice
 *  - erreurs structurées (table dédiée) en parallèle du JSON journal existant
 */
return new class extends Migration
{
    public function up(): void
    {
        // Colonnes additionnelles non destructives sur budget_imports
        Schema::table('budget_imports', function (Blueprint $table) {
            $table->foreignId('parent_import_id')->nullable()->after('exercice_id')
                ->constrained('budget_imports')->nullOnDelete()
                ->comment('Pour grouper plusieurs imports issus du même fichier multi-feuilles');
            $table->string('feuille_source', 64)->nullable()->after('parent_import_id')
                ->comment('Nom de la feuille Excel importée ; null pour le record parent');
            $table->string('type_donnees', 32)->nullable()->after('feuille_source')
                ->comment('budget | axes | produits | sous_produits | activites | taches | indicateurs');
            $table->json('options')->nullable()->after('journal')
                ->comment('Options d\'import : mode (insert/upsert/merge), strict, etc.');
        });

        // Mappings de colonnes persistés (par utilisateur + type de données)
        Schema::create('budget_import_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type_donnees', 32);
            $table->string('libelle', 128)->comment('Nom donné par l\'utilisateur au mapping');
            $table->json('mapping')->comment('Tableau associatif {colonne_excel: champ_canonique}');
            $table->boolean('partage')->default(false)->comment('Visible par les autres utilisateurs');
            $table->timestamps();

            $table->index(['user_id', 'type_donnees']);
        });

        // Erreurs structurées (en parallèle du journal JSON pour rétro-compatibilité)
        Schema::create('budget_import_erreurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('budget_imports')->cascadeOnDelete();
            $table->string('feuille', 64)->nullable();
            $table->unsignedInteger('ligne')->nullable();
            $table->string('colonne', 64)->nullable();
            $table->string('valeur_fautive', 255)->nullable();
            $table->enum('gravite', ['critique', 'erreur', 'avertissement', 'info'])->default('erreur');
            $table->enum('regle', [
                'colonne_manquante',
                'valeur_obligatoire',
                'format_invalide',
                'reference_inexistante',
                'doublon',
                'incoherence_hierarchique',
                'depassement_borne',
                'autre',
            ])->default('autre');
            $table->text('message');
            $table->text('correction_suggeree')->nullable();
            $table->enum('statut_traitement', ['ouvert', 'corrige', 'ignore'])->default('ouvert');
            $table->timestamps();

            $table->index(['import_id', 'gravite']);
            $table->index(['import_id', 'feuille', 'ligne']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_import_erreurs');
        Schema::dropIfExists('budget_import_mappings');

        Schema::table('budget_imports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_import_id');
            $table->dropColumn(['feuille_source', 'type_donnees', 'options']);
        });
    }
};
