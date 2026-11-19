<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Scrutins, classes concernées, candidatures et photos des candidats
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrutins', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('type', 20)->default('uninominal'); // uninominal | plurinominal
            $table->unsignedSmallInteger('nombre_choix')->default(1); // choix maximum sur un bulletin
            $table->unsignedSmallInteger('nombre_elus')->default(1);
            $table->boolean('vote_blanc')->default(true);
            $table->timestamp('candidatures_fin_at')->nullable();
            $table->timestamp('ouverture_at');
            $table->timestamp('cloture_at');
            $table->boolean('resultats_publics')->default(true);
            // cycle de vie : brouillon (programme_at vide) → programmé → ouvert → clos → publié
            $table->timestamp('programme_at')->nullable();
            $table->timestamp('publie_at')->nullable();
            // chiffres figés à la clôture (les listes électorales peuvent changer ensuite)
            $table->unsignedInteger('inscrits_cloture')->nullable();
            $table->unsignedInteger('votants_cloture')->nullable();
            // notifications déjà envoyées (le planificateur ne les renvoie pas)
            $table->timestamp('notif_ouverture_at')->nullable();
            $table->timestamp('notif_rappel_at')->nullable();
            $table->timestamp('notif_cloture_at')->nullable();
            $table->timestamp('purge_at')->nullable(); // données personnelles supprimées (30 jours après la clôture)
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('classe_scrutin', function (Blueprint $table) {
            $table->foreignId('scrutin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->primary(['scrutin_id', 'classe_id']);
        });

        // Photos gardées en base : le disque des hébergeurs gratuits est effacé à chaque redémarrage
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->longText('contenu'); // image JPEG en base64 (voir le modèle Photo)
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('candidatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrutin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slogan', 120);
            $table->text('programme');
            $table->foreignId('photo_id')->nullable()->constrained('photos')->nullOnDelete();
            $table->string('statut', 20)->default('en_attente'); // en_attente | validee | rejetee
            $table->string('motif_rejet')->nullable();
            $table->foreignId('decide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decide_at')->nullable();
            $table->timestamps();
            $table->unique(['scrutin_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures');
        Schema::dropIfExists('photos');
        Schema::dropIfExists('classe_scrutin');
        Schema::dropIfExists('scrutins');
    }
};
