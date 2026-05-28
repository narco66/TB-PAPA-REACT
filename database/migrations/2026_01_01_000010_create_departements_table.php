<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Départements techniques placés sous l'autorité d'un Commissaire.
 * Conformément à la gouvernance institutionnelle CEEAC (Section 1.2 du CDC).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->foreignId('commissaire_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->integer('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departements');
    }
};
