<?php

namespace Tests\Feature;

use App\Models\Bulletin;
use App\Models\Candidature;
use App\Models\Classe;
use App\Models\Scrutin;
use App\Models\User;
use App\Services\Urne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

// Les garanties de l'urne : un vote par électeur, secret, vérifiable, scellé
class UrneTest extends TestCase
{
    use RefreshDatabase;

    private Classe $classe;

    private Scrutin $scrutin;

    private Candidature $alice;

    private Candidature $bruno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classe = Classe::factory()->create(['nom' => 'L2']);
        $this->scrutin = Scrutin::factory()->ouvert()->create();
        $this->scrutin->classes()->attach($this->classe);
        $this->alice = Candidature::factory()->for($this->scrutin)->create(['user_id' => $this->electeur()->id]);
        $this->bruno = Candidature::factory()->for($this->scrutin)->create(['user_id' => $this->electeur()->id]);
        $this->scrutin->load('classes');
    }

    private function electeur(): User
    {
        return User::factory()->create(['classe_id' => $this->classe->id]);
    }

    private function refus(callable $action, string $message): void
    {
        try {
            $action();
            $this->fail('Le vote aurait dû être refusé.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString($message, $e->errors()['vote'][0]);
        }
    }

    public function test_un_electeur_vote_et_recoit_un_recu(): void
    {
        $recu = app(Urne::class)->voter($this->electeur(), $this->scrutin, [$this->alice->id]);

        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $recu);
        $this->assertSame(1, $this->scrutin->emargements()->count());
        $this->assertSame(1, $this->scrutin->bulletins()->count());
        $this->assertNotNull(Urne::trouverParRecu($recu));
        $this->assertNull(Urne::trouverParRecu('AAAA-BBBB-CCCC'));
    }

    public function test_un_electeur_ne_vote_qu_une_fois(): void
    {
        $electeur = $this->electeur();
        app(Urne::class)->voter($electeur, $this->scrutin, [$this->alice->id]);

        $this->refus(fn () => app(Urne::class)->voter($electeur, $this->scrutin, [$this->bruno->id]), 'déjà voté');
        $this->assertSame(1, $this->scrutin->bulletins()->count());
    }

    public function test_aucune_colonne_ne_relie_un_bulletin_a_un_electeur(): void
    {
        app(Urne::class)->voter($this->electeur(), $this->scrutin, [$this->alice->id]);

        $colonnes = array_merge(Schema::getColumnListing('bulletins'), Schema::getColumnListing('bulletin_choix'));
        foreach (['user_id', 'electeur_id', 'created_at', 'updated_at', 'ip', 'ip_address'] as $interdite) {
            $this->assertNotContains($interdite, $colonnes, "La colonne {$interdite} permettrait de retrouver l'électeur.");
        }
        // l'émargement ne garde que le jour, sans heure ni choix
        $this->assertEqualsCanonicalizing(['id', 'scrutin_id', 'user_id', 'date_vote'], Schema::getColumnListing('emargements'));
        // aucun horodatage de vote : rapproché du rang des bulletins, il trahirait le choix
        $this->assertSame(0, DB::table('journal')->where('action', 'vote')->count());
    }

    public function test_refuse_un_electeur_d_une_autre_classe_ou_inactif(): void
    {
        $autre = User::factory()->create();
        $this->refus(fn () => app(Urne::class)->voter($autre, $this->scrutin, [$this->alice->id]), 'liste électorale');

        $inactif = User::factory()->inactif()->create(['classe_id' => $this->classe->id]);
        $this->refus(fn () => app(Urne::class)->voter($inactif, $this->scrutin, [$this->alice->id]), 'liste électorale');
    }

    public function test_refuse_le_vote_hors_periode(): void
    {
        $this->scrutin->update(['ouverture_at' => now()->addHour(), 'cloture_at' => now()->addDay()]);
        $this->refus(fn () => app(Urne::class)->voter($this->electeur(), $this->scrutin, [$this->alice->id]), 'pas ouvert');

        $this->scrutin->update(['ouverture_at' => now()->subDays(2), 'cloture_at' => now()->subMinute()]);
        $this->refus(fn () => app(Urne::class)->voter($this->electeur(), $this->scrutin, [$this->alice->id]), 'pas ouvert');
    }

    public function test_respecte_le_nombre_de_choix_et_les_candidatures_validees(): void
    {
        $this->refus(fn () => app(Urne::class)->voter($this->electeur(), $this->scrutin, [$this->alice->id, $this->bruno->id]), 'au plus 1');
        $this->refus(fn () => app(Urne::class)->voter($this->electeur(), $this->scrutin, []), 'au moins un candidat');

        $refusee = Candidature::factory()->for($this->scrutin)->create(['statut' => 'rejetee', 'user_id' => $this->electeur()->id]);
        $this->refus(fn () => app(Urne::class)->voter($this->electeur(), $this->scrutin, [$refusee->id]), 'ne se présente pas');

        $ailleurs = Candidature::factory()->create();
        $this->refus(fn () => app(Urne::class)->voter($this->electeur(), $this->scrutin, [$ailleurs->id]), 'ne se présente pas');
    }

    public function test_vote_blanc_et_plurinominal(): void
    {
        app(Urne::class)->voter($this->electeur(), $this->scrutin, [], blanc: true);
        $this->assertTrue(Bulletin::first()->blanc);
        $this->refus(fn () => app(Urne::class)->voter($this->electeur(), $this->scrutin, [$this->alice->id], blanc: true), 'blanc');

        $this->scrutin->update(['type' => 'plurinominal', 'nombre_choix' => 2]);
        app(Urne::class)->voter($this->electeur(), $this->scrutin, [$this->alice->id, $this->bruno->id]);
        $this->assertSame(2, DB::table('bulletin_choix')->count());
    }

    public function test_l_urne_est_scellee_toute_modification_est_detectee(): void
    {
        $urne = app(Urne::class);
        foreach (range(1, 4) as $i) {
            $urne->voter($this->electeur(), $this->scrutin, [$i % 2 ? $this->alice->id : $this->bruno->id]);
        }
        $this->assertNull($urne->verifierIntegrite($this->scrutin));

        // quelqu'un change le choix du bulletin n° 2 directement en base
        $deuxieme = Bulletin::where('rang', 2)->first();
        $actuel = $deuxieme->choix()->first()->id;
        DB::table('bulletin_choix')->where('bulletin_id', $deuxieme->id)
            ->update(['candidature_id' => $actuel === $this->alice->id ? $this->bruno->id : $this->alice->id]);
        $this->assertStringContainsString('n° 2 a été modifié', $urne->verifierIntegrite($this->scrutin));
    }

    public function test_un_bulletin_retire_est_detecte(): void
    {
        $urne = app(Urne::class);
        foreach (range(1, 3) as $i) {
            $urne->voter($this->electeur(), $this->scrutin, [$this->alice->id]);
        }
        Bulletin::where('rang', 3)->delete();

        $this->assertStringContainsString('3 votants', $urne->verifierIntegrite($this->scrutin));
    }
}
