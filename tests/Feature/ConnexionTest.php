<?php

namespace Tests\Feature;

use App\Livewire\Connexion;
use App\Models\CodeConnexion;
use App\Models\User;
use App\Notifications\CodeDeConnexion;
use App\Services\ConnexionParCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

// Connexion sans mot de passe : matricule + e-mail, puis code à usage unique
class ConnexionTest extends TestCase
{
    use RefreshDatabase;

    private function codeEnvoye(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, CodeDeConnexion::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $code;
    }

    public function test_le_code_recu_par_e_mail_ouvre_la_session(): void
    {
        Notification::fake();
        $user = User::factory()->create(['matricule' => '20230001', 'email' => 'jean.rabe@univ-mg.edu']);

        Livewire::test(Connexion::class)
            ->set('matricule', '20230001')->set('email', 'Jean.Rabe@univ-mg.edu')
            ->call('demander')->assertSet('etape', 'code');
        $code = $this->codeEnvoye($user);

        // seule l'empreinte du code est gardée en base
        $this->assertNotSame($code, CodeConnexion::first()->empreinte);

        Livewire::test(Connexion::class)
            ->set('email', 'jean.rabe@univ-mg.edu')->set('code', $code)
            ->call('verifier')->assertRedirect(route('espace'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_matricule_et_e_mail_doivent_designer_le_meme_compte(): void
    {
        Notification::fake();
        User::factory()->create(['matricule' => '20230001', 'email' => 'jean@univ-mg.edu']);

        Livewire::test(Connexion::class)
            ->set('matricule', '20239999')->set('email', 'jean@univ-mg.edu')
            ->call('demander')->assertHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_un_compte_desactive_ne_recoit_pas_de_code(): void
    {
        Notification::fake();
        User::factory()->inactif()->create(['matricule' => '20230001', 'email' => 'jean@univ-mg.edu']);

        Livewire::test(Connexion::class)
            ->set('matricule', '20230001')->set('email', 'jean@univ-mg.edu')
            ->call('demander')->assertHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_code_faux_limite_a_cinq_essais_et_code_expire(): void
    {
        Notification::fake();
        $user = User::factory()->create(['matricule' => '20230001', 'email' => 'jean@univ-mg.edu']);
        $service = app(ConnexionParCode::class);
        $service->demanderCode('20230001', 'jean@univ-mg.edu');
        $bon = $this->codeEnvoye($user);

        for ($i = 0; $i < ConnexionParCode::ESSAIS_MAX; $i++) {
            try {
                $service->verifierCode('jean@univ-mg.edu', '000000');
            } catch (ValidationException) {
            }
        }
        $this->expectException(ValidationException::class);
        $service->verifierCode('jean@univ-mg.edu', $bon); // même le bon code est refusé après 5 essais
    }

    public function test_un_code_expire_est_refuse(): void
    {
        Notification::fake();
        $user = User::factory()->create(['matricule' => '20230001', 'email' => 'jean@univ-mg.edu']);
        $service = app(ConnexionParCode::class);
        $service->demanderCode('20230001', 'jean@univ-mg.edu');
        $code = $this->codeEnvoye($user);
        $this->travel(11)->minutes();

        $this->expectException(ValidationException::class);
        $service->verifierCode('jean@univ-mg.edu', $code);
    }

    public function test_un_code_ne_sert_qu_une_fois(): void
    {
        Notification::fake();
        $user = User::factory()->create(['matricule' => '20230001', 'email' => 'jean@univ-mg.edu']);
        $service = app(ConnexionParCode::class);
        $service->demanderCode('20230001', 'jean@univ-mg.edu');
        $code = $this->codeEnvoye($user);
        $service->verifierCode('jean@univ-mg.edu', $code);

        $this->expectException(ValidationException::class);
        $service->verifierCode('jean@univ-mg.edu', $code);
    }

    public function test_essayer_sans_compte_seulement_si_la_demonstration_est_active(): void
    {
        $admin = User::factory()->admin()->create(['demo' => true]);

        config(['vote.comptes_demo' => false]);
        Livewire::test(Connexion::class)->call('essayer', 'admin')->assertNotFound();
        $this->assertGuest();

        config(['vote.comptes_demo' => true]);
        Livewire::test(Connexion::class)->call('essayer', 'admin')->assertRedirect(route('admin.tableau'));
        $this->assertAuthenticatedAs($admin);
    }
}
