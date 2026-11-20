<?php

namespace App\Services;

use App\Models\Scrutin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Dépouillement automatique : voix par candidat, votes blancs, élus et égalités
class Depouillement
{
    /**
     * @return array{
     *     inscrits: int, votants: int, participation: float, blancs: int, exprimes: int,
     *     candidats: Collection, egalite: bool
     * }
     */
    public function resultats(Scrutin $scrutin): array
    {
        // colonne nommée explicitement : SQLite l'appelle « count(*) », PostgreSQL « count »
        $voix = DB::table('bulletin_choix')
            ->join('bulletins', 'bulletins.id', '=', 'bulletin_choix.bulletin_id')
            ->where('bulletins.scrutin_id', $scrutin->id)
            ->groupBy('bulletin_choix.candidature_id')
            ->selectRaw('bulletin_choix.candidature_id, count(*) as voix')
            ->pluck('voix', 'candidature_id');

        $blancs = $scrutin->bulletins()->where('blanc', true)->count();
        $votants = $scrutin->nombreVotants();
        $exprimes = $votants - $blancs; // bulletins portant au moins un candidat

        $candidats = $scrutin->candidatures()->validees()->with('user')->get()
            ->map(function ($candidature) use ($voix, $exprimes) {
                $candidature->voix = (int) ($voix[$candidature->id] ?? 0);
                $candidature->pourcentage = $exprimes ? round($candidature->voix / $exprimes * 100, 1) : 0.0;

                return $candidature;
            })
            ->sortByDesc('voix')
            ->values();

        // élus : les N premiers ; égalité si le dernier siège se joue entre ex aequo
        $sieges = min($scrutin->nombre_elus, $candidats->count());
        $egalite = false;
        if ($sieges > 0 && $candidats->count() > $sieges) {
            $egalite = $candidats[$sieges - 1]->voix === $candidats[$sieges]->voix && $candidats[$sieges]->voix > 0;
        }
        $seuil = $sieges > 0 ? $candidats[$sieges - 1]->voix ?? 0 : 0;
        $candidats->each(function ($c, $i) use ($sieges, $egalite, $seuil) {
            $c->elu = ! $egalite && $i < $sieges && $c->voix > 0;
            $c->exAequo = $egalite && $c->voix === $seuil;
        });

        return [
            'inscrits' => $scrutin->nombreInscrits(),
            'votants' => $votants,
            'participation' => $scrutin->tauxParticipation(),
            'blancs' => $blancs,
            'exprimes' => $exprimes,
            'candidats' => $candidats,
            'egalite' => $egalite,
        ];
    }
}
