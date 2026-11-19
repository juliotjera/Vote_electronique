<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// L'urne, en deux parties qui ne se rejoignent jamais :
// - la liste d'émargement : QUI a voté (pour empêcher le double vote), sans le choix ;
// - les bulletins : CE qui a été voté, sans personne.
// Aucune colonne ne relie un bulletin à un électeur : identifiants aléatoires (UUID), pas d'heure
// de vote, et l'émargement ne garde que la date du jour. Les deux sont écrits dans la même
// transaction (voir App\Services\Urne).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emargements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('scrutin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date_vote');
            $table->unique(['scrutin_id', 'user_id']); // un électeur, un vote : garanti par la base
        });

        Schema::create('bulletins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('scrutin_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rang'); // position dans la chaîne d'empreintes de l'urne
            $table->boolean('blanc')->default(false);
            $table->string('recu_empreinte', 64)->unique(); // empreinte du reçu remis à l'électeur
            $table->string('empreinte', 64); // SHA-256 du bulletin et de l'empreinte précédente
            $table->unique(['scrutin_id', 'rang']);
        });

        Schema::create('bulletin_choix', function (Blueprint $table) {
            $table->foreignUuid('bulletin_id')->constrained('bulletins')->cascadeOnDelete();
            $table->foreignId('candidature_id')->constrained()->cascadeOnDelete();
            $table->primary(['bulletin_id', 'candidature_id']);
        });

        // Journal d'activité : ce qui s'est passé, jamais le contenu d'un vote
        Schema::create('journal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->json('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('journal');
        Schema::dropIfExists('bulletin_choix');
        Schema::dropIfExists('bulletins');
        Schema::dropIfExists('emargements');
    }
};
