<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rappels personnalisés (l'électeur choisit quand être prévenu) et groupes ciblés
// (associations, clubs, comités) en plus des classes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rappel', 10)->default('veille'); // veille (24 h avant) · jour (3 h avant) · aucun (pas d'e-mail)
            $table->boolean('avis_email')->default(true);    // ouverture et résultats par e-mail
        });

        Schema::table('scrutins', function (Blueprint $table) {
            $table->timestamp('notif_rappel_jour_at')->nullable();
        });

        Schema::create('groupes', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 80)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('groupe_user', function (Blueprint $table) {
            $table->foreignId('groupe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['groupe_id', 'user_id']);
        });

        Schema::create('groupe_scrutin', function (Blueprint $table) {
            $table->foreignId('scrutin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('groupe_id')->constrained()->cascadeOnDelete();
            $table->primary(['scrutin_id', 'groupe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groupe_scrutin');
        Schema::dropIfExists('groupe_user');
        Schema::dropIfExists('groupes');
        Schema::table('scrutins', fn (Blueprint $table) => $table->dropColumn('notif_rappel_jour_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['rappel', 'avis_email']));
    }
};
