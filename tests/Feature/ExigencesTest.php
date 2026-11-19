<?php

namespace Tests\Feature;

use App\Livewire\Admin\Groupes;
use App\Livewire\Connexion;
use App\Livewire\Electeur\MesDonnees;
use App\Livewire\Electeur\Voter;
use App\Models\Candidature;
use App\Models\Classe;
use App\Models\Groupe;
use App\Models\Journal;
use App\Models\Photo;
use App\Models\Scrutin;
use App\Models\User;
use App\Notifications\AvisScrutin;
use App\Notifications\CodeDeConnexion;
use App\Services\ConnexionParCode;
use App\Services\CycleScrutins;
use App\Services\Urne;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

// Exigences du cahier des charges de L1 ajoutées après coup : journal des tentatives refusées,
// exports, liste d'émargement, rappels personnalisés, groupes ciblés, choix du rôle, en-têtes de sécurité
class ExigencesTest extends TestCase
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

        return $scrutin->load(['classes', 'groupes']);
    }

    public function test_les_tentatives_refusees_sont_journalisees_avec_une_ip_tronquee(): void
    {
        Notification::fake();
        $user = User::factory()->create(['classe_id' => $this->classe->id]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.57']);
        $connexion = app(ConnexionParCode::class);

        try {
            $connexion->demanderCode('99999999', 'inconnu@exemple.mg');
        } catch (ValidationException) {
        }
        $connexion->demanderCode($user->matricule, $user->email);
        try {
            $connexion->verifierCode($user->email, '000000');
        } catch (ValidationException) {
        }

        $inconnu = Journal::where('action', 'connexion_refusee')->first();
        $this->assertNull($inconnu->user_id);
        $this->assertSame('identifiants_inconnus', $inconnu->details['motif']);
        $this->assertSame($user->id, Journal::where('action', 'code_refuse')->first()->user_id);
        $this->assertSame('192.168.1.0', Journal::anonymiser('192.168.1.57'));
        $this->assertSame('2001:db8:1::', Journal::anonymiser('2001:db8:1:2:3:4:5:6'));
    }

    public function test_un_second_vote_est_refuse_et_journalise_sans_rien_dire_du_choix(): void
    {
        $scrutin = $this->scrutinOuvert();
        $candidat = Candidature::factory()->for($scrutin)->create(['user_id' => User::factory()->create(['classe_id' => $this->classe->id])->id]);
        $electeur = User::factory()->create(['classe_id' => $this->classe->id]);
        app(Urne::class)->voter($electeur, $scrutin, [$candidat->id]);

        Livewire::actingAs($electeur)->test(Voter::class, ['scrutin' => $scrutin])
            ->call('basculer', $candidat->id)->call('deposer')->assertHasErrors('vote');

        $refus = Journal::where('action', 'vote_refuse')->sole();
        $this->assertSame($electeur->id, $refus->user_id);
        $this->assertArrayNotHasKey('candidature', $refus->details);
        $this->assertSame(1, $scrutin->bulletins()->count());
    }

    public function test_export_de_la_liste_electorale_au_format_d_import(): void
    {
        User::factory()->create(['classe_id' => $this->classe->id, 'nom' => 'RABE', 'matricule' => '20230001']);
        $admin = User::factory()->admin()->create();

        $this->actingAs(User::factory()->create())->get(route('admin.electeurs.csv'))->assertForbidden();
        $csv = $this->actingAs($admin)->get(route('admin.electeurs.csv'))->assertOk()->streamedContent();

        $this->assertStringContainsString('Matricule;Nom;Prénom;Email;Statut;Classe', $csv);
        $this->assertStringContainsString('20230001;RABE;', $csv);
    }

    public function test_la_liste_d_emargement_dit_qui_a_vote_jamais_quoi_et_disparait_apres_la_purge(): void
    {
        $scrutin = $this->scrutinOuvert();
        $candidat = Candidature::factory()->for($scrutin)->create(['user_id' => User::factory()->create(['classe_id' => $this->classe->id])->id]);
        $zoe = User::factory()->create(['classe_id' => $this->classe->id, 'nom' => 'ZOARIVELO']);
        $aina = User::factory()->create(['classe_id' => $this->classe->id, 'nom' => 'ANDRIANINA']);
        app(Urne::class)->voter($zoe, $scrutin, [$candidat->id]);
        app(Urne::class)->voter($aina, $scrutin, [$candidat->id]);
        $admin = User::factory()->admin()->create();

        $csv = $this->actingAs($admin)->get(route('admin.scrutins.emargement', $scrutin))->assertOk()->streamedContent();
        // trié par nom : Aina (deuxième votant) avant Zoé (première), l'ordre des votes ne transparaît pas
        $this->assertLessThan(strpos($csv, 'ZOARIVELO'), strpos($csv, 'ANDRIANINA'));
        $this->assertStringNotContainsString($candidat->user->nom, $csv);

        $scrutin->forceFill(['purge_at' => now()])->save();
        $this->actingAs($admin)->get(route('admin.scrutins.emargement', $scrutin))->assertStatus(410);
    }

    public function test_les_rappels_suivent_le_choix_de_chaque_electeur(): void
    {
        Notification::fake();
        $veille = User::factory()->create(['classe_id' => $this->classe->id]);
        $jour = User::factory()->create(['classe_id' => $this->classe->id, 'rappel' => 'jour']);
        $aucun = User::factory()->create(['classe_id' => $this->classe->id, 'rappel' => 'aucun']);
        $scrutin = $this->scrutinOuvert(['ouverture_at' => now()->subDays(2), 'cloture_at' => now()->addHours(10)]);
        $scrutin->forceFill(['notif_ouverture_at' => now()])->save();
        $cycle = app(CycleScrutins::class);

        $cycle->avancer(); // 10 h avant la clôture : rappel « la veille » seulement
        Notification::assertSentTo($veille, AvisScrutin::class, fn ($n, $canaux) => $n->moment === 'rappel' && in_array('mail', $canaux));
        Notification::assertSentTo($aucun, AvisScrutin::class, fn ($n, $canaux) => $canaux === ['database']);
        Notification::assertNotSentTo($jour, AvisScrutin::class);

        $this->travel(8)->hours(); // 2 h avant la clôture : rappel « le jour même »
        $cycle->avancer();
        Notification::assertSentTo($jour, AvisScrutin::class, fn ($n) => $n->moment === 'rappel');
        Notification::assertSentToTimes($veille, AvisScrutin::class, 1);
    }

    public function test_l_electeur_choisit_ses_rappels(): void
    {
        $electeur = User::factory()->create(['classe_id' => $this->classe->id]);

        Livewire::actingAs($electeur)->test(MesDonnees::class)
            ->set('rappel', 'jour')->set('avisEmail', false)->call('enregistrerRappels')->assertHasNoErrors()
            ->set('rappel', 'demain')->call('enregistrerRappels')->assertHasErrors('rappel');

        $this->assertSame('jour', $electeur->fresh()->rappel);
        $this->assertFalse((bool) $electeur->fresh()->avis_email);
    }

    public function test_un_groupe_ouvre_le_vote_a_des_membres_de_plusieurs_classes(): void
    {
        $autreClasse = Classe::factory()->create(['nom' => 'M1']);
        $membre = User::factory()->create(['classe_id' => $autreClasse->id, 'matricule' => '20240042']);
        $horsGroupe = User::factory()->create(['classe_id' => $autreClasse->id]);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(Groupes::class)
            ->set('nom', 'Club informatique')->call('creer')
            ->set('matricules', "20240042\n00000000")->call('enregistrerMembres')
            ->assertSet('bilan.membres', 1)->assertSet('bilan.inconnus', ['00000000']);

        $scrutin = Scrutin::factory()->ouvert()->create();
        $scrutin->groupes()->attach(Groupe::where('nom', 'Club informatique')->sole());
        $scrutin->load(['classes', 'groupes']);

        $this->assertTrue($membre->estElecteurDe($scrutin));
        $this->assertFalse($horsGroupe->estElecteurDe($scrutin));
        $this->assertSame(1, $scrutin->electeursInscrits()->count());
        $this->assertTrue(Scrutin::visiblesPour($membre)->whereKey($scrutin->id)->exists());
        $this->assertSame('Club informatique', $scrutin->publicCible());
    }

    public function test_choix_du_role_et_en_tetes_de_securite(): void
    {
        $this->get(route('accueil'))->assertOk()
            ->assertSee(route('connexion', ['espace' => 'administration']), false)
            ->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('connexion', ['espace' => 'administration']))->assertOk()->assertSee('Espace administration');
        $this->get(route('connexion'))->assertOk()->assertSee('Espace électeur');
    }

    public function test_pages_d_erreur_a_la_charte(): void
    {
        $this->get('/page-qui-n-existe-pas')->assertNotFound()->assertSee('Page introuvable')->assertSee('Retour à l’accueil');
        $this->get(route('langue', 'mg'));
        $this->get('/page-qui-n-existe-pas')->assertNotFound()->assertSee('Tsy hita ny pejy');
    }

    public function test_une_photo_traverse_la_base_intacte(): void
    {
        // octets nuls et non UTF-8 : ce que PostgreSQL refuse dans une colonne texte sans encodage
        $octets = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01".random_bytes(64);
        $photo = Photo::create(['type' => 'image/jpeg', 'contenu' => $octets]);

        $this->assertSame($octets, $photo->fresh()->contenu);
        $this->assertSame(base64_encode($octets), DB::table('photos')->where('id', $photo->id)->value('contenu'));
        $this->get(route('photo', $photo))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_les_avis_partent_en_file_d_attente_le_code_de_connexion_tout_de_suite(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new AvisScrutin($this->scrutinOuvert(), 'ouverture'));
        $this->assertNotInstanceOf(ShouldQueue::class, new CodeDeConnexion('123456'));
    }

    public function test_le_guide_d_utilisation_en_page_et_en_pdf(): void
    {
        $this->get(route('aide'))->assertOk()->assertSee('Guide d’utilisation')->assertSee('Organiser un scrutin');
        $this->get(route('aide.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_l_administration_se_connecte_par_e_mail_un_electeur_doit_donner_son_matricule(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create(['matricule' => null]);
        $electeur = User::factory()->create(['classe_id' => $this->classe->id]);

        Livewire::test(Connexion::class, ['espace' => 'administration'])
            ->set('email', $admin->email)->call('demander')->assertHasNoErrors()->assertSet('etape', 'code');
        Livewire::test(Connexion::class, ['espace' => 'administration'])
            ->set('email', $electeur->email)->call('demander')->assertHasErrors('email');
        Livewire::test(Connexion::class)
            ->set('email', $electeur->email)->call('demander')->assertHasErrors('matricule');
    }
}
