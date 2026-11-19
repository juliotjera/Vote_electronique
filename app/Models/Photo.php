<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

// Photo de candidat gardée en base (l'hébergeur n'a pas de disque permanent).
// Stockée en base64 : même comportement sous SQLite et PostgreSQL (le type bytea passe mal par PDO).
#[Fillable(['type', 'contenu'])]
class Photo extends Model
{
    const UPDATED_AT = null;

    protected function contenu(): Attribute
    {
        return Attribute::make(
            get: fn (?string $valeur) => $valeur === null ? null : base64_decode($valeur),
            set: fn (?string $valeur) => $valeur === null ? null : base64_encode($valeur),
        );
    }
}
