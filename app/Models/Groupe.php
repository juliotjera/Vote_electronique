<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Groupe ciblé en plus des classes : association, club, comité… (un étudiant peut être membre de plusieurs)
#[Fillable(['nom', 'description'])]
class Groupe extends Model
{
    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->where('role', 'electeur');
    }

    public function scrutins(): BelongsToMany
    {
        return $this->belongsToMany(Scrutin::class);
    }
}
