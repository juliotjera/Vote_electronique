<?php

namespace App\Livewire;

use App\Models\Candidature;
use App\Models\Scrutin;
use App\Models\User;
use App\Services\ConnexionParCode;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::public')]

class Connexion extends Component
{
    public string $etape = 'identite'; // identite → code

    // choix du rôle (cahier des charges : « page d'accueil : choix du rôle électeur / admin »).
    // Il adapte l'écran ; le rôle réel vient toujours du compte, jamais de ce choix.
    #[Url]
    public string $espace = 'electeur';

    public function updatedEspace(): void
    {
        $this->espace = $this->espace === 'administration' ? 'administration' : 'electeur';
    }

    public string $matricule = '';

    public string $email = '';

    public string $code = '';

    public ?string $codeDeveloppement = null;

    public function demander(ConnexionParCode $connexion)
    {
        // l'administration n'a pas de matricule : l'e-mail suffit (un électeur, lui, doit donner les deux)
        $this->validate([
            'matricule' => $this->espace === 'administration' ? 'nullable|string|max:20' : 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);
        $this->codeDeveloppement = $connexion->demanderCode($this->matricule, $this->email);
        $this->etape = 'code';
        $this->code = '';
    }

    public function verifier(ConnexionParCode $connexion)
    {
        $this->validate(['code' => 'required|string|min:6|max:7'], [], ['code' => __('code')]);
        $user = $connexion->verifierCode($this->email, $this->code);

        return $this->redirect($user->estAdmin() ? route('admin.tableau') : route('espace'), navigate: true);
    }

    public function recommencer(): void
    {
        $this->reset('etape', 'code', 'codeDeveloppement');
    }

    // « Essayer sans compte » : un clic ouvre l'espace d'un rôle, avec les données de démonstration
    public function essayer(string $role, ConnexionParCode $connexion)
    {
        abort_unless(config('vote.comptes_demo'), 404);
        abort_if(RateLimiter::tooManyAttempts('demo:'.request()->ip(), 30), 429);
        RateLimiter::hit('demo:'.request()->ip(), 600);

        $user = match ($role) {
            'admin' => User::where('demo', true)->where('role', 'admin')->first(),
            'candidat' => Candidature::whereHas('user', fn ($q) => $q->where('demo', true))
                ->whereHas('scrutin', fn ($q) => $q->whereNotNull('programme_at')->where('ouverture_at', '>', now()))
                ->first()?->user,
            default => $this->electeurDeDemo(),
        };
        abort_unless($user, 404);
        $connexion->ouvrirSession($user);

        return $this->redirect($user->estAdmin() ? route('admin.tableau') : route('espace'), navigate: true);
    }

    // Un électeur de démonstration qui n'a pas encore voté au scrutin ouvert, pour que chaque visiteur puisse voter
    private function electeurDeDemo(): ?User
    {
        $ouvert = Scrutin::whereNotNull('programme_at')->whereNull('publie_at')
            ->where('ouverture_at', '<=', now())->where('cloture_at', '>', now())->first();
        $requete = User::where('demo', true)->where('role', 'electeur')->where('actif', true)
            ->whereDoesntHave('candidatures');
        if ($ouvert) {
            $libre = (clone $requete)->whereIn('classe_id', $ouvert->classes()->pluck('classes.id'))
                ->whereDoesntHave('emargements', fn ($q) => $q->where('scrutin_id', $ouvert->id))
                ->inRandomOrder()->first();
            if ($libre) {
                return $libre;
            }
        }

        return $requete->inRandomOrder()->first();
    }

    public function render()
    {
        return view('livewire.connexion', ['demo' => config('vote.comptes_demo')])->title(__('Connexion'));
    }
}
