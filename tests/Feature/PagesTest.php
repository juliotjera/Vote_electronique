<?php

namespace Tests\Feature;

use App\Models\Candidature;
use App\Models\Scrutin;
use App\Models\User;
use App\Notifications\AvisScrutin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Chaque écran s'affiche sur les données de démonstration (un scrutin à chaque étape)
class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_les_ecrans_de_l_administration(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        foreach (['admin.tableau', 'admin.scrutins', 'admin.scrutins.nouveau', 'admin.candidatures', 'admin.electeurs', 'admin.journal'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (Scrutin::all() as $scrutin) {
            $this->get(route('admin.scrutins.suivi', $scrutin))->assertOk()->assertSee($scrutin->titre);
        }
        $brouillon = Scrutin::whereNull('programme_at')->first();
        $this->get(route('admin.scrutins.modifier', $brouillon))->assertOk();
        $this->get(route('admin.scrutins.modifier', Scrutin::whereNotNull('programme_at')->first()))->assertForbidden();
        $this->get(route('admin.candidatures', ['filtre' => 'toutes']))->assertOk();
        $this->get(route('admin.electeurs', ['q' => 'rabe']))->assertOk();
    }

    public function test_les_ecrans_de_l_electeur_et_du_candidat(): void
    {
        $candidature = Candidature::where('statut', 'en_attente')->whereHas('user', fn ($q) => $q->where('demo', true))->latest('id')->first();
        $this->actingAs($candidature->user);

        $this->get(route('espace'))->assertOk()->assertSee('Élection des délégués L2 2026');
        $this->get(route('notifications'))->assertOk();
        $this->get(route('mes-donnees'))->assertOk()->assertSee($candidature->user->matricule);
        $this->get(route('candidater', $candidature->scrutin))->assertOk()->assertSee($candidature->slogan);
        foreach (Scrutin::visiblesPour($candidature->user)->get() as $scrutin) {
            $this->get(route('scrutin', $scrutin))->assertOk();
        }
    }

    public function test_resultats_publies_et_page_d_accueil(): void
    {
        $publie = Scrutin::whereNotNull('publie_at')->first();
        $this->get(route('accueil'))->assertOk()->assertSee('votes sur')->assertSee('Élection des délégués L2 2026');
        $this->get(route('resultats.publics'))->assertOk()->assertSee($publie->titre);
        $this->get(route('resultats', $publie))->assertOk()->assertSee('Urne intègre')->assertSee('Élu');
    }

    public function test_la_participation_en_direct_suit_le_parametrage_du_scrutin(): void
    {
        // cahier des charges : affichage en temps réel « selon paramétrage » (case « résultats publics »)
        $ouvert = Scrutin::where('titre', 'Élection des délégués L2 2026')->first();
        $ouvert->forceFill(['resultats_publics' => false])->save();

        $this->get(route('accueil'))->assertOk()->assertDontSee('votes sur')->assertDontSee($ouvert->titre)->assertSee('Aucun vote en cours');
    }

    public function test_le_malagasy_pour_les_electeurs_le_francais_pour_l_administration(): void
    {
        $this->get(route('langue', 'mg'))->assertRedirect();
        $this->get(route('accueil'))->assertOk()->assertSee('Mpifidy iray, vato iray')->assertSee('lang="mg"', false);

        $electeur = User::where('role', 'electeur')->where('demo', true)->first();
        $this->actingAs($electeur)->get(route('espace'))->assertOk()->assertSee('Manao ahoana');

        // l'administration reste en français, quelle que soit la langue choisie
        $this->actingAs(User::where('role', 'admin')->first())->get(route('admin.tableau'))->assertOk()->assertSee('Tableau de bord');

        $this->get(route('langue', 'xx'))->assertNotFound();
    }

    public function test_les_notifications_s_affichent_dans_la_langue_choisie(): void
    {
        $electeur = User::where('role', 'electeur')->where('demo', true)->first();
        $scrutin = Scrutin::visiblesPour($electeur)->first();
        $electeur->notify(new AvisScrutin($scrutin, 'ouverture'));

        $this->actingAs($electeur)->get(route('notifications'))->assertSee('Le vote est ouvert');
        $this->get(route('langue', 'mg'));
        $this->actingAs($electeur)->get(route('notifications'))->assertSee('Misokatra ny fifidianana');
    }
}
