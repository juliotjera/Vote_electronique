<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

// Titre et texte d'une notification, dans la langue de l'interface au moment de l'affichage
class TexteNotification
{
    /** @return array{0: string, 1: string} */
    public static function pour(DatabaseNotification $notification): array
    {
        $d = $notification->data;
        if (isset($d['moment'], $d['scrutin'])) {
            $fin = isset($d['cloture']) ? Carbon::parse($d['cloture'])->translatedFormat('l j F, H\hi') : '';

            return match ($d['moment']) {
                'ouverture' => [__('Le vote est ouvert'), __('Le scrutin « :titre » est ouvert jusqu’au :date.', ['titre' => $d['scrutin'], 'date' => $fin])],
                'rappel' => [__('Dernier rappel'), __('Le scrutin « :titre » se termine le :date. Vous n’avez pas encore voté.', ['titre' => $d['scrutin'], 'date' => $fin])],
                default => [__('Résultats publiés'), __('Les résultats du scrutin « :titre » sont disponibles.', ['titre' => $d['scrutin']])],
            };
        }
        if (isset($d['statut'], $d['scrutin'])) {
            return $d['statut'] === 'validee'
                ? [__('Candidature validée'), __('Votre candidature au scrutin « :titre » est validée : elle sera présentée aux électeurs.', ['titre' => $d['scrutin']])]
                : [__('Candidature refusée'), __('Votre candidature au scrutin « :titre » n’a pas été retenue. Motif : :motif', ['titre' => $d['scrutin'], 'motif' => $d['motif'] ?? ''])];
        }

        return [$d['titre'] ?? __('Notification'), $d['texte'] ?? ''];
    }
}
