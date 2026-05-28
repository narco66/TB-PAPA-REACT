<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Directions de la CEEAC.
 * - type 'technique' : rattachée à un Département (autorité Commissaire)
 * - type 'appui_soutien' : rattachée au Secrétariat Général
 * Conformément à la Section 1.3 du CDC : distinction Technique vs Appui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('libelle');
            $table->enum('type', ['technique', 'appui_soutien'])->index();
            $table->foreignId('departement_id')
                ->nullable()
                ->constrained('departements')
                ->nullOnDelete();
            $table->foreignId('directeur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'departement_id']);
        });

        // Lien FK depuis users.direction_id désormais possible
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('direction_id')->references('id')->on('directions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['direction_id']);
        });
        Schema::dropIfExists('directions');
    }
};
