<?php

namespace Tests\Feature;

use App\Models\Candidature;
use App\Models\Classe;
use App\Models\Scrutin;
use App\Models\User;
use App\Notifications\AvisScrutin;
use App\Services\CycleScrutins;
use App\Services\Depouillement;
use App\Services\ImportElecteurs;
use App\Services\Urne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// Le temps qui passe (avis, clôture, purge), le dépouillement et l'import de la liste électorale
class CycleEtDepouillementTest extends TestCase
{
    use RefreshDatabase;

    private Classe $classe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classe = Classe::factory()->create(['nom' => 'L2']);
    }

    private function scrutinOuvert(array $attributs = []): Scrutin
    {
        $scrutin = Scrutin::factory()->ouvert()->create($attributs);
        $scrutin->classes()->attach($this->classe);

        return $scrutin->load('classes');
    }

    private function electeurs(int $n)
    {
        return User::factory()->count($n)->create(['classe_id' => $this->classe->id]);
    }

    public function test_avis_d_ouverture_une_seule_fois_et_rappel_aux_seuls_abstentionnistes(): void
    {
        Notification::fake();
        [$a, $b, $c] = $this->electeurs(3)->all();
        $scrutin = $this->scrutinOuvert(['ouverture_at' => now()->subDays(2), 'cloture_at' => now()->addHours(10)]);
        $candidat = Candidature::factory()->for($scrutin)->create(['user_id' => $a->id]);
        app(Urne::class)->voter($a, $scrutin, [$candidat->id]);

        $cycle = app(CycleScrutins::class);
        $cycle->avancer();
        $cycle->avancer(); // une deuxième minute ne renvoie rien

        Notification::assertSentToTimes($b, AvisScrutin::class, 2); // ouverture + rappel
        Notification::assertSentToTimes($a, AvisScrutin::class, 1); // ouverture seulement : il a voté
        Notification::assertSentTo($c, AvisScrutin::class, fn ($n) => $n->moment === 'rappel');
    }

    public function test_publication_fige_les_chiffres_et_purge_a_trente_jours(): void
    {
        Notification::fake();
        $electeurs = $this->electeurs(4);
        $scrutin = $this->scrutinOuvert();
        $candidat = Candidature::factory()->for($scrutin)->create(['user_id' => $electeurs[0]->id]);
        foreach ($electeurs->take(3) as $e) {
            app(Urne::class)->voter($e, $scrutin, [$candidat->id]);
        }
        $scrutin->update(['cloture_at' => now()->subMinute()]);

        app(CycleScrutins::class)->publier($scrutin->fresh());
        $scrutin->refresh();
        $this->assertSame(4, $scrutin->inscrits_cloture);
        $this->assertSame(3, $scrutin->votants_cloture);

        // 30 jours plus tard : la liste d'émargement disparaît, les résultats et l'intégrité restent
        $this->travel(31)->days();
        app(CycleScrutins::class)->avancer();
        $scrutin->refresh();
        $this->assertNotNull($scrutin->purge_at);
        $this->assertSame(0, $scrutin->emargements()->count());
        $this->assertSame(3, $scrutin->bulletins()->count());
        $this->assertSame(75.0, $scrutin->tauxParticipation());
        $this->assertNull(app(Urne::class)->verifierIntegrite($scrutin));
    }

    public function test_depouillement_elus_blancs_et_egalite(): void
    {
        $electeurs = $this->electeurs(6);
        $scrutin = $this->scrutinOuvert();
        $alice = Candidature::factory()->for($scrutin)->create(['user_id' => $electeurs[0]->id]);
        $bruno = Candidature::factory()->for($scrutin)->create(['user_id' => $electeurs[1]->id]);
        $urne = app(Urne::class);
        $urne->voter($electeurs[0], $scrutin, [$alice->id]);
        $urne->voter($electeurs[1], $scrutin, [$alice->id]);
        $urne->voter($electeurs[2], $scrutin, [$bruno->id]);
        $urne->voter($electeurs[3], $scrutin, [], blanc: true);

        $r = app(Depouillement::class)->resultats($scrutin);
        $this->assertSame([4, 1, 3], [$r['votants'], $r['blancs'], $r['exprimes']]);
        $this->assertSame([$alice->id, $bruno->id], $r['candidats']->pluck('id')->all());
        $this->assertTrue($r['candidats'][0]->elu);
        $this->assertSame(66.7, $r['candidats'][0]->pourcentage);
        $this->assertFalse($r['egalite']);

        // Bruno rattrape Alice : égalité pour le siège, personne n'est déclaré élu
        $urne->voter($electeurs[4], $scrutin, [$bruno->id]);
        $r = app(Depouillement::class)->resultats($scrutin);
        $this->assertTrue($r['egalite']);
        $this->assertFalse($r['candidats']->contains('elu', true));
        $this->assertSame(2, $r['candidats']->where('exAequo', true)->count());
    }

    public function test_plurinominal_plusieurs_elus(): void
    {
        $electeurs = $this->electeurs(5);
        $scrutin = $this->scrutinOuvert(['type' => 'plurinominal', 'nombre_choix' => 2, 'nombre_elus' => 2]);
        [$a, $b, $c] = collect(range(0, 2))->map(fn ($i) => Candidature::factory()->for($scrutin)->create(['user_id' => $electeurs[$i]->id]))->all();
        $urne = app(Urne::class);
        $urne->voter($electeurs[0], $scrutin, [$a->id, $b->id]);
        $urne->voter($electeurs[1], $scrutin, [$a->id, $c->id]);
        $urne->voter($electeurs[2], $scrutin, [$a->id, $b->id]);

        $r = app(Depouillement::class)->resultats($scrutin);
        $this->assertSame([$a->id, $b->id], $r['candidats']->where('elu', true)->pluck('id')->all());
    }

    public function test_import_csv_de_la_scolarite(): void
    {
        User::factory()->create(['matricule' => '20230001', 'email' => 'ancien@univ-mg.edu', 'classe_id' => $this->classe->id]);
        $fichier = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($fichier, "\xEF\xBB\xBFMatricule;Nom;Prénom;Email;Statut;Classe\n"
            ."20230001;rabe;Jean;jean.rabe@univ-mg.edu;actif;L2\n"
            ."20230002;RAZAFI;Nomena;nomena.razafi@univ-mg.edu;actif;L2\n"
            ."20230003;RANDRIA;Sarah;sarah.randria@univ-mg.edu;inactif;L3\n"
            ."20230004;ANDRIAM;Tojo;pas-un-email;actif;M1\n");

        $bilan = app(ImportElecteurs::class)->depuisFichier($fichier);

        $this->assertSame(2, $bilan['crees']);
        $this->assertSame(1, $bilan['mis_a_jour']);
        $this->assertCount(1, $bilan['erreurs']);
        $this->assertStringContainsString('Ligne 5', $bilan['erreurs'][0]);
        $this->assertSame('RABE', User::where('matricule', '20230001')->value('nom'));
        $this->assertFalse(User::where('matricule', '20230003')->first()->actif);
        $this->assertTrue(Classe::where('nom', 'L3')->exists()); // classe créée à la volée
    }
}
