<?php

namespace App\Enums;

// Étape du cycle de vie d'un scrutin, déduite de ses dates : l'ouverture et la clôture
// ne dépendent pas d'une tâche planifiée qui pourrait ne pas tourner.
enum EtatScrutin: string
{
    case Brouillon = 'brouillon';     // en préparation, invisible pour les électeurs
    case Programme = 'programme';     // annoncé : candidatures, puis attente de l'ouverture
    case Ouvert = 'ouvert';           // vote en cours
    case Clos = 'clos';               // vote terminé, résultats pas encore publiés
    case Publie = 'publie';           // résultats publiés

    public function libelle(): string
    {
        return __(match ($this) {
            self::Brouillon => 'Brouillon',
            self::Programme => 'À venir',
            self::Ouvert => 'Vote ouvert',
            self::Clos => 'Dépouillement',
            self::Publie => 'Résultats publiés',
        });
    }
}
