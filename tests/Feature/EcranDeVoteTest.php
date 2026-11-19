<?php

namespace Tests\Feature;

use App\Livewire\Electeur\Voter;
use App\Models\Candidature;
use App\Models\Classe;
use App\Models\Scrutin;
use App\Models\User;
use App\Services\Urne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Le parcours de l'électeur à l'écran : choisir, vérifier, déposer, recevoir son reçu
class EcranDeVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_choisir_verifier_deposer_et_recevoir_un_recu(): void
    {
        $classe = Classe::factory()->create();
        $scrutin = Scrutin::factory()->ouvert()->create();
        $scrutin->classes()->attach($classe);
        $candidat = Candidature::factory()->for($scrutin)->create(['user_id' => User::factory()->create(['classe_id' => $classe->id])->id]);
        $electeur = User::factory()->create(['classe_id' => $classe->id]);

        $ecran = Livewire::actingAs($electeur)->test(Voter::class, ['scrutin' => $scrutin])
            ->assertSee($candidat->slogan)
            ->call('verifierBulletin')->assertHasErrors('vote')   // bulletin vide refusé
            ->call('basculer', $candidat->id)->call('verifierBulletin')->assertSet('etape', 'confirmer')
            ->call('deposer')->assertSet('etape', 'recu')->assertSee('A voté');

        $recu = $ecran->get('recu');
        $this->assertNotNull(Urne::trouverParRecu($recu));
        $this->assertTrue($electeur->aVote($scrutin));

        // revenir sur la page : le vote est enregistré, le bulletin n'est plus proposé
        Livewire::actingAs($electeur)->test(Voter::class, ['scrutin' => $scrutin])
            ->assertSee('Vous avez voté')->assertDontSee('Vérifier mon bulletin');
    }

    public function test_en_uninominal_un_nouveau_choix_remplace_le_precedent(): void
    {
        $classe = Classe::factory()->create();
        $scrutin = Scrutin::factory()->ouvert()->create();
        $scrutin->classes()->attach($classe);
        $electeur = User::factory()->create(['classe_id' => $classe->id]);
        [$a, $b] = Candidature::factory()->count(2)->for($scrutin)->create()->all();

        Livewire::actingAs($electeur)->test(Voter::class, ['scrutin' => $scrutin])
            ->call('basculer', $a->id)->call('basculer', $b->id)->assertSet('choix', [$b->id])
            ->call('voterBlanc')->assertSet('choix', [])->assertSet('blanc', true);
    }
}
