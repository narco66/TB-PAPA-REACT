<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('libelle');
            $table->foreignId('direction_id')->nullable()->constrained('directions')->nullOnDelete();
            $table->foreignId('chef_service_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->integer('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['direction_id', 'actif']);
        });

        Schema::create('organizational_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('libelle');
            $table->enum('type', ['commission', 'presidence', 'vice_presidence', 'secretariat_general', 'departement', 'direction', 'service', 'bureau', 'cellule']);
            $table->foreignId('parent_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->foreignId('direction_id')->nullable()->constrained('directions')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->integer('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'parent_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('direction_id')->constrained('services')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'organizational_unit_id')) {
                $table->foreignId('organizational_unit_id')->nullable()->after('service_id')->constrained('organizational_units')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('telephone');
            }
            if (! Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('bio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'organizational_unit_id')) {
                $table->dropConstrainedForeignId('organizational_unit_id');
            }
            if (Schema::hasColumn('users', 'service_id')) {
                $table->dropConstrainedForeignId('service_id');
            }
            if (Schema::hasColumn('users', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
            if (Schema::hasColumn('users', 'bio')) {
                $table->dropColumn('bio');
            }
        });

        Schema::dropIfExists('organizational_units');
        Schema::dropIfExists('services');
    }
};
