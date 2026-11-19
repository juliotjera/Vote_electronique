<?php

namespace App\Livewire;

use App\Models\Scrutin;
use App\Services\Urne;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

// Un électeur vérifie que son bulletin est bien dans l'urne, grâce au reçu remis après son vote.
// La page ne montre jamais le contenu du bulletin.
#[Layout('layouts::public')]

class VerifierRecu extends Component
{
    public string $recu = '';

    public ?array $resultat = null;

    public function verifier(Urne $urne): void
    {
        $this->validate(['recu' => ['required', 'string', 'regex:/^[A-Za-z2-9\s-]{12,16}$/']], [
            'recu.regex' => __('Un reçu ressemble à ABCD-EFGH-JKLM (12 caractères).'),
        ], ['recu' => __('reçu')]);

        $cle = 'verifier-recu:'.request()->ip();
        abort_if(RateLimiter::tooManyAttempts($cle, 20), 429, __('Trop de vérifications, réessayez dans une minute.'));
        RateLimiter::hit($cle, 60);

        $bulletin = Urne::trouverParRecu($this->recu);
        if (! $bulletin) {
            $this->resultat = ['trouve' => false];

            return;
        }
        $scrutin = Scrutin::find($bulletin->scrutin_id);
        $this->resultat = [
            'trouve' => true,
            'scrutin' => $scrutin->titre,
            'etat' => $scrutin->etat()->libelle(),
            'integre' => $urne->verifierIntegrite($scrutin) === null,
        ];
    }

    public function render()
    {
        return view('livewire.verifier-recu')->title(__('Vérifier un reçu'));
    }
}
