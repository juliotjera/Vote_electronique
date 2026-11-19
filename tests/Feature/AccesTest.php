<?php

namespace Tests\Feature;

use App\Models\Candidature;
use App\Models\Classe;
use App\Models\Scrutin;
use App\Models\User;
use App\Services\CycleScrutins;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Qui voit quoi : rôles, liste électorale, résultats avant et après publication
class AccesTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_electeur_n_entre_pas_dans_l_administration(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.tableau'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.scrutins.pdf', Scrutin::factory()->clos()->create()))->assertForbidden();
    }

    public function test_un_visiteur_est_renvoye_vers_la_connexion(): void
    {
        $this->get(route('espace'))->assertRedirect(route('connexion'));
        $this->get(route('admin.tableau'))->assertRedirect(route('connexion'));
    }

    public function test_l_administration_va_vers_son_tableau_de_bord(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('espace'))->assertRedirect(route('admin.tableau'));
    }

    public function test_un_scrutin_d_une_autre_classe_ou_en_brouillon_est_invisible(): void
    {
        $electeur = User::factory()->create();
        $autreClasse = Scrutin::factory()->ouvert()->create();
        $autreClasse->classes()->attach(Classe::factory()->create());
        $brouillon = Scrutin::factory()->create();
        $brouillon->classes()->attach($electeur->classe_id);

        $this->actingAs($electeur)->get(route('scrutin', $autreClasse))->assertNotFound();
        $this->actingAs($electeur)->get(route('scrutin', $brouillon))->assertNotFound();
    }

    public function test_les_resultats_ne_sont_visibles_qu_apres_publication(): void
    {
        $electeur = User::factory()->create();
        $scrutin = Scrutin::factory()->clos()->create(['resultats_publics' => true]);
        $scrutin->classes()->attach($electeur->classe_id);
        Candidature::factory()->for($scrutin)->create();

        $this->get(route('resultats', $scrutin))->assertNotFound();
        $this->actingAs($electeur)->get(route('resultats', $scrutin))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get(route('resultats', $scrutin))->assertOk();

        app(CycleScrutins::class)->publier($scrutin->fresh());
        auth()->logout();
        $this->get(route('resultats', $scrutin))->assertOk()->assertSee('Participation');
    }

    public function test_des_resultats_non_publics_restent_reserves_aux_electeurs_du_scrutin(): void
    {
        $electeur = User::factory()->create();
        $scrutin = Scrutin::factory()->clos()->create(['resultats_publics' => false]);
        $scrutin->classes()->attach($electeur->classe_id);
        app(CycleScrutins::class)->publier($scrutin->fresh());

        $this->get(route('resultats', $scrutin))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('resultats', $scrutin))->assertNotFound();
        $this->actingAs($electeur)->get(route('resultats', $scrutin))->assertOk();
    }

    public function test_les_exports_attendent_la_cloture(): void
    {
        $admin = User::factory()->admin()->create();
        $ouvert = Scrutin::factory()->ouvert()->create();
        $this->actingAs($admin)->get(route('admin.scrutins.csv', $ouvert))->assertStatus(409);

        $clos = Scrutin::factory()->clos()->create();
        $this->actingAs($admin)->get(route('admin.scrutins.csv', $clos))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($admin)->get(route('admin.scrutins.pdf', $clos))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_les_pages_publiques_repondent(): void
    {
        foreach (['accueil', 'connexion', 'verifier', 'resultats.publics', 'confidentialite'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }
}
