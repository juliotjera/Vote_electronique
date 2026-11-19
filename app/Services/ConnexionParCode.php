<?php

namespace App\Services;

use App\Models\CodeConnexion;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\CodeDeConnexion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

// Connexion sans mot de passe : matricule + e-mail, puis code à 6 chiffres reçu par e-mail.
// Le code expire après 10 minutes, ne sert qu'une fois et n'accepte que 5 essais.
class ConnexionParCode
{
    public const DUREE_MINUTES = 10;

    public const ESSAIS_MAX = 5;

    // Étape 1 : envoie un code si le matricule et l'e-mail désignent le même compte actif.
    // Renvoie le code en clair uniquement en développement (affiché à l'écran pour tester).
    public function demanderCode(string $matricule, string $email): ?string
    {
        $cle = 'code-connexion:'.request()->ip();
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            Journal::noter('connexion_bloquee', ['motif' => 'trop_de_demandes']);
            $secondes = RateLimiter::availableIn($cle);
            throw ValidationException::withMessages(['email' => __('Trop de demandes. Réessayez dans :n secondes.', ['n' => $secondes])]);
        }
        RateLimiter::hit($cle, 600);

        $user = User::where('email', mb_strtolower(trim($email)))->first();
        $matriculeOk = $user && ($user->matricule === null || strcasecmp($user->matricule, trim($matricule)) === 0);

        // même message que le compte existe ou non : on ne révèle pas qui est inscrit
        if (! $user || ! $matriculeOk || ! $user->actif) {
            // au journal seulement : l'écran garde le même message pour ne pas révéler qui est inscrit
            Journal::noter('connexion_refusee', ['motif' => ! $user || ! $matriculeOk ? 'identifiants_inconnus' : 'compte_desactive'], $user && $matriculeOk ? $user->id : null);
            throw ValidationException::withMessages(['email' => __('Matricule ou e-mail introuvable sur la liste électorale.')]);
        }

        $code = (string) random_int(100000, 999999);
        CodeConnexion::where('user_id', $user->id)->whereNull('utilise_at')->delete();
        CodeConnexion::create([
            'user_id' => $user->id,
            'empreinte' => hash('sha256', $code),
            'expire_at' => now()->addMinutes(self::DUREE_MINUTES),
        ]);
        $user->notify(new CodeDeConnexion($code));

        return app()->environment('local') ? $code : null;
    }

    // Étape 2 : vérifie le code et ouvre la session
    public function verifierCode(string $email, string $code): User
    {
        $user = User::where('email', mb_strtolower(trim($email)))->first();
        $demande = $user ? CodeConnexion::where('user_id', $user->id)->whereNull('utilise_at')->latest('id')->first() : null;

        if (! $demande || $demande->expire_at->isPast()) {
            Journal::noter('code_refuse', ['motif' => 'expire'], $user?->id);
            throw ValidationException::withMessages(['code' => __('Ce code a expiré. Demandez-en un nouveau.')]);
        }
        if ($demande->tentatives >= self::ESSAIS_MAX) {
            Journal::noter('code_refuse', ['motif' => 'trop_d_essais'], $user->id);
            throw ValidationException::withMessages(['code' => __('Trop d’essais. Demandez un nouveau code.')]);
        }
        if (! hash_equals($demande->empreinte, hash('sha256', preg_replace('/\D/', '', $code)))) {
            $demande->increment('tentatives');
            Journal::noter('code_refuse', ['motif' => 'incorrect', 'essai' => $demande->tentatives], $user->id);
            $restants = self::ESSAIS_MAX - $demande->tentatives;
            throw ValidationException::withMessages(['code' => __('Code incorrect. Il reste :n essai(s).', ['n' => $restants])]);
        }

        $demande->update(['utilise_at' => now()]);
        $this->ouvrirSession($user);

        return $user;
    }

    public function ouvrirSession(User $user): void
    {
        Auth::login($user);
        // nouvel identifiant de session à la connexion (contre la fixation de session)
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }
        $user->forceFill(['derniere_connexion_at' => now()])->save();
        Journal::noter('connexion', $user->demo ? ['demo' => true] : []);
    }
}
