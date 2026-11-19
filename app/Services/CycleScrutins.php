<?php

namespace App\Services;

use App\Enums\EtatScrutin;
use App\Models\CodeConnexion;
use App\Models\Journal;
use App\Models\Scrutin;
use App\Notifications\AvisScrutin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

// Ce qui arrive aux scrutins avec le temps (lancé chaque minute par le planificateur, voir
// routes/console.php). L'état d'un scrutin se déduit de ses dates : si le planificateur
// s'arrête, le vote ouvre et ferme quand même à l'heure ; seuls les e-mails attendent.
class CycleScrutins
{
    public const JOURS_CONSERVATION = 30;

    // colonne du scrutin => [heures avant la clôture, préférences des électeurs concernés]
    public const RAPPELS = [
        'notif_rappel_at' => [24, ['veille', 'aucun']],
        'notif_rappel_jour_at' => [3, ['jour']],
    ];

    public function avancer(): array
    {
        $bilan = ['ouvertures' => 0, 'rappels' => 0, 'clotures' => 0, 'purges' => 0];

        foreach (Scrutin::whereNotNull('programme_at')->whereNull('purge_at')->with(['classes', 'groupes'])->get() as $scrutin) {
            $etat = $scrutin->etat();

            if ($etat === EtatScrutin::Ouvert && ! $scrutin->notif_ouverture_at) {
                $this->avertir($scrutin, $scrutin->electeursInscrits()->get(), 'ouverture');
                $scrutin->forceFill(['notif_ouverture_at' => now()])->save();
                $bilan['ouvertures']++;
            }

            // rappels avant la clôture, aux seuls électeurs qui n'ont pas voté, au moment que chacun a choisi :
            // la veille (24 h avant, par défaut) ou le jour même (3 h avant) ; « aucun » = avis dans l'application seulement
            foreach (self::RAPPELS as $colonne => [$heures, $preferences]) {
                if ($etat === EtatScrutin::Ouvert && ! $scrutin->{$colonne} && now()->diffInHours($scrutin->cloture_at, false) <= $heures
                    && $scrutin->ouverture_at->diffInHours($scrutin->cloture_at) > $heures) {
                    $absents = $scrutin->electeursInscrits()->whereIn('rappel', $preferences)
                        ->whereDoesntHave('emargements', fn ($q) => $q->where('scrutin_id', $scrutin->id))->get();
                    $this->avertir($scrutin, $absents, 'rappel');
                    $scrutin->forceFill([$colonne => now()])->save();
                    $bilan['rappels']++;
                }
            }

            if (in_array($etat, [EtatScrutin::Clos, EtatScrutin::Publie], true) && $scrutin->inscrits_cloture === null) {
                $this->figer($scrutin);
                $bilan['clotures']++;
            }

            if ($scrutin->cloture_at->lt(now()->subDays(self::JOURS_CONSERVATION))) {
                $this->purger($scrutin);
                $bilan['purges']++;
            }
        }

        return $bilan;
    }

    // À la clôture : fige le nombre d'inscrits et de votants (les listes peuvent changer ensuite)
    public function figer(Scrutin $scrutin): void
    {
        if ($scrutin->inscrits_cloture !== null) {
            return;
        }
        $scrutin->forceFill([
            'inscrits_cloture' => $scrutin->electeursInscrits()->count(),
            'votants_cloture' => $scrutin->emargements()->count(),
        ])->save();
        Journal::noter('cloture', ['scrutin' => $scrutin->id]);
    }

    public function publier(Scrutin $scrutin): void
    {
        if ($scrutin->etat() !== EtatScrutin::Clos) {
            throw ValidationException::withMessages(['scrutin' => 'Les résultats se publient une fois le vote clos.']);
        }
        $this->figer($scrutin);
        $scrutin->forceFill(['publie_at' => now(), 'notif_cloture_at' => now()])->save();
        $this->avertir($scrutin, $scrutin->electeursInscrits()->get(), 'resultats');
        Journal::noter('publication_resultats', ['scrutin' => $scrutin->id]);
    }

    // 30 jours après la clôture : la liste d'émargement (qui a voté) est supprimée, comme
    // annoncé dans la politique de confidentialité. Les bulletins anonymes et les résultats restent.
    public function purger(Scrutin $scrutin): void
    {
        DB::transaction(function () use ($scrutin) {
            $this->figer($scrutin);
            $scrutin->emargements()->delete();
            $scrutin->forceFill(['purge_at' => now()])->save();
        });
        CodeConnexion::where('expire_at', '<', now()->subDay())->delete();
        Journal::noter('purge_donnees', ['scrutin' => $scrutin->id]);
    }

    private function avertir(Scrutin $scrutin, $electeurs, string $moment): void
    {
        $electeurs->chunk(200)->each(fn ($lot) => Notification::send($lot, new AvisScrutin($scrutin, $moment)));
    }

    // Programmer : le scrutin quitte l'état de brouillon et devient visible des électeurs concernés
    public function programmer(Scrutin $scrutin): void
    {
        $erreurs = [];
        if ($scrutin->classes()->count() === 0 && $scrutin->groupes()->count() === 0) {
            $erreurs[] = 'Choisissez au moins une classe ou un groupe.';
        }
        if ($scrutin->cloture_at->lte($scrutin->ouverture_at)) {
            $erreurs[] = 'La clôture doit suivre l’ouverture.';
        }
        if ($scrutin->ouverture_at->isPast()) {
            $erreurs[] = 'L’ouverture doit être dans le futur.';
        }
        if ($erreurs) {
            throw ValidationException::withMessages(['scrutin' => implode(' ', $erreurs)]);
        }
        $scrutin->forceFill(['programme_at' => now()])->save();
        Journal::noter('programmation', ['scrutin' => $scrutin->id]);
    }
}
