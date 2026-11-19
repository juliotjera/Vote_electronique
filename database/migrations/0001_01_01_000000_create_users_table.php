<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Comptes : électeurs (inscrits par l'administration, souvent par import CSV) et administrateurs.
// Pas de mot de passe : la connexion se fait par matricule + e-mail + code à usage unique.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 50)->unique(); // L1, L2, M1…
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 20)->nullable()->unique();
            $table->string('nom', 80);
            $table->string('prenom', 80);
            $table->string('email')->unique();
            $table->string('role', 20)->default('electeur'); // electeur | admin
            $table->foreignId('classe_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->boolean('demo')->default(false); // comptes ouverts par « Essayer sans compte »
            $table->timestamp('derniere_connexion_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Codes de connexion à usage unique (seule leur empreinte est gardée)
        Schema::create('codes_connexion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('empreinte', 64);
            $table->unsignedTinyInteger('tentatives')->default(0);
            $table->timestamp('expire_at');
            $table->timestamp('utilise_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('codes_connexion');
        Schema::dropIfExists('users');
        Schema::dropIfExists('classes');
    }
};
