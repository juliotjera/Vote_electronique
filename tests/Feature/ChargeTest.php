<?php

namespace Tests\Feature;

use App\Models\Candidature;
use App\Models\Classe;
use App\Models\Scrutin;
use App\Models\User;
use App\Services\Depouillement;
use App\Services\Urne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Critères de succès du cahier des charges : 500 électeurs gérés, résultats en moins de 2 minutes après la clôture.
// Les 500 votes passent par la vraie urne (transaction, verrou, chaîne d'empreintes), l'un après l'autre.
class ChargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cinq_cents_electeurs_votent_et_le_depouillement_est_immediat(): void
    {
        $classe = Classe::factory()->create(['nom' => 'L2']);
        $electeurs = User::factory()->count(500)->create(['classe_id' => $classe->id]);
        $scrutin = Scrutin::factory()->ouvert()->create();
        $scrutin->classes()->attach($classe);
        $candidats = $electeurs->take(3)->map(fn ($e) => Candidature::factory()->for($scrutin)->create(['user_id' => $e->id]));

        $urne = app(Urne::class);
        $debut = microtime(true);
        foreach ($electeurs as $i => $electeur) {
            $i % 25 === 0 ? $urne->voter($electeur, $scrutin, [], true) : $urne->voter($electeur, $scrutin, [$candidats[$i % 3]->id]);
        }
        $dureeVotes = microtime(true) - $debut;

        $scrutin->update(['cloture_at' => now()->subMinute()]);
        $debut = microtime(true);
        $r = app(Depouillement::class)->resultats($scrutin->fresh());
        $integrite = $urne->verifierIntegrite($scrutin);
        $dureeDepouillement = microtime(true) - $debut;

        $this->assertSame(500, $r['votants']);
        $this->assertSame(20, $r['blancs']);
        $this->assertSame(480, collect($r['candidats'])->sum('voix'));
        $this->assertNull($integrite, 'La chaîne d’empreintes doit rester intacte après 500 votes.');
        $this->assertLessThan(120, $dureeDepouillement, 'Résultats en moins de 2 minutes (cahier des charges).');

        fwrite(STDERR, sprintf("\n[charge] 500 votes : %.1f s (%.0f ms par vote) ; dépouillement et contrôle de l’urne : %.2f s\n",
            $dureeVotes, $dureeVotes / 500 * 1000, $dureeDepouillement));
    }
}
