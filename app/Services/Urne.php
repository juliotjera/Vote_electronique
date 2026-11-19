<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\Emargement;
use App\Models\Scrutin;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// L'urne électronique.
// 1. Un électeur ne vote qu'une fois : contrainte unique (scrutin, électeur) sur l'émargement,
//    vérifiée dans une transaction qui verrouille le scrutin.
// 2. Le vote est secret : l'émargement (qui a voté) et le bulletin (ce qui a été voté) sont écrits
//    dans la même transaction mais ne partagent aucune colonne (UUID aléatoires, pas d'heure).
// 3. Le vote est vérifiable : l'électeur reçoit un reçu (code aléatoire) dont seule l'empreinte est
//    gardée dans le bulletin ; il peut vérifier que son bulletin est dans l'urne sans révéler son choix.
// 4. L'urne est scellée : chaque bulletin contient l'empreinte SHA-256 du précédent. Modifier,
//    ajouter ou retirer un bulletin après coup casse la chaîne (voir verifierIntegrite()).
class Urne
{
    public const GENESE = 'genese';

    /**
     * Enregistre un vote et renvoie le reçu à remettre à l'électeur.
     *
     * @param  array<int>  $choix  identifiants de candidatures validées
     */
    public function voter(User $electeur, Scrutin $scrutin, array $choix, bool $blanc = false): string
    {
        $choix = array_values(array_unique(array_map('intval', $choix)));
        $this->verifierBulletin($electeur, $scrutin, $choix, $blanc);

        try {
            return DB::transaction(function () use ($electeur, $scrutin, $choix, $blanc) {
                // un seul vote à la fois par scrutin : la chaîne d'empreintes reste linéaire
                Scrutin::whereKey($scrutin->id)->lockForUpdate()->first();

                if (Emargement::where('scrutin_id', $scrutin->id)->where('user_id', $electeur->id)->exists()) {
                    throw ValidationException::withMessages(['vote' => __('Vous avez déjà voté à ce scrutin.')]);
                }

                Emargement::create([
                    'scrutin_id' => $scrutin->id,
                    'user_id' => $electeur->id,
                    'date_vote' => now()->toDateString(),
                ]);

                $precedent = Bulletin::where('scrutin_id', $scrutin->id)->orderByDesc('rang')->first();
                $rang = ($precedent?->rang ?? 0) + 1;
                $recu = self::nouveauRecu();
                $recuEmpreinte = self::empreinteRecu($recu);
                sort($choix);

                $bulletin = Bulletin::create([
                    'scrutin_id' => $scrutin->id,
                    'rang' => $rang,
                    'blanc' => $blanc,
                    'recu_empreinte' => $recuEmpreinte,
                    'empreinte' => self::empreinte($scrutin->id, $rang, $blanc, $choix, $recuEmpreinte, $precedent?->empreinte),
                ]);
                $bulletin->choix()->attach($choix);

                // pas d'entrée dans le journal : son heure exacte, rapprochée du rang du bulletin,
                // permettrait de retrouver un vote. L'émargement (date du jour seulement) suffit.

                return $recu;
            });
        } catch (QueryException $e) {
            // deux votes simultanés du même électeur : la contrainte unique a refusé le second
            if (Emargement::where('scrutin_id', $scrutin->id)->where('user_id', $electeur->id)->exists()) {
                throw ValidationException::withMessages(['vote' => __('Vous avez déjà voté à ce scrutin.')]);
            }
            throw $e;
        }
    }

    private function verifierBulletin(User $electeur, Scrutin $scrutin, array $choix, bool $blanc): void
    {
        $refus = fn (string $message) => throw ValidationException::withMessages(['vote' => $message]);

        if (! $scrutin->estOuvert()) {
            $refus(__('Ce scrutin n’est pas ouvert au vote.'));
        }
        if (! $electeur->estElecteurDe($scrutin)) {
            $refus(__('Vous n’êtes pas inscrit sur la liste électorale de ce scrutin.'));
        }
        if ($blanc) {
            if (! $scrutin->vote_blanc) {
                $refus(__('Le vote blanc n’est pas prévu pour ce scrutin.'));
            }
            if ($choix) {
                $refus(__('Un vote blanc ne peut pas contenir de candidat.'));
            }

            return;
        }
        if (! $choix) {
            $refus(__('Choisissez au moins un candidat, ou votez blanc.'));
        }
        if (count($choix) > $scrutin->nombre_choix) {
            $refus(__('Ce scrutin permet au plus :n choix.', ['n' => $scrutin->nombre_choix]));
        }
        $valides = $scrutin->candidatures()->validees()->whereIn('id', $choix)->count();
        if ($valides !== count($choix)) {
            $refus(__('Ce bulletin contient un candidat qui ne se présente pas à ce scrutin.'));
        }
    }

    // Reçu lisible et facile à recopier : 12 caractères sans 0/O ni 1/I
    public static function nouveauRecu(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return implode('-', str_split($code, 4));
    }

    public static function empreinteRecu(string $recu): string
    {
        return hash('sha256', Str::upper(str_replace([' ', '-'], '', trim($recu))));
    }

    public static function empreinte(int $scrutinId, int $rang, bool $blanc, array $choix, string $recuEmpreinte, ?string $precedente): string
    {
        sort($choix);

        return hash('sha256', implode('|', [
            $scrutinId, $rang, $blanc ? 'blanc' : implode(',', $choix), $recuEmpreinte, $precedente ?? self::GENESE,
        ]));
    }

    // Recalcule toute la chaîne : renvoie null si l'urne est intacte, sinon la description du problème
    public function verifierIntegrite(Scrutin $scrutin): ?string
    {
        $precedente = null;
        $attendu = 1;
        $bulletins = $scrutin->bulletins()->with('choix:id')->orderBy('rang')->get();

        foreach ($bulletins as $bulletin) {
            if ($bulletin->rang !== $attendu) {
                return "Bulletin manquant ou ajouté avant la position {$bulletin->rang}.";
            }
            $calcul = self::empreinte($scrutin->id, $bulletin->rang, $bulletin->blanc, $bulletin->choix->pluck('id')->all(), $bulletin->recu_empreinte, $precedente);
            if (! hash_equals($calcul, $bulletin->empreinte)) {
                return "Le bulletin n° {$bulletin->rang} a été modifié après le vote.";
            }
            $precedente = $bulletin->empreinte;
            $attendu++;
        }

        $votants = $scrutin->votants_cloture ?? $scrutin->emargements()->count();
        if ($bulletins->count() !== $votants) {
            return "L'urne contient {$bulletins->count()} bulletins pour {$votants} votants émargés.";
        }

        return null;
    }

    // Vérification d'un reçu par l'électeur : le bulletin est-il dans l'urne ?
    public static function trouverParRecu(string $recu): ?Bulletin
    {
        return Bulletin::where('recu_empreinte', self::empreinteRecu($recu))->first();
    }
}
