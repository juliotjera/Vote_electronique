<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['matricule', 'nom', 'prenom', 'email', 'role', 'classe_id', 'actif', 'demo', 'rappel', 'avis_email'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'demo' => 'boolean',
            'derniere_connexion_at' => 'datetime',
        ];
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function groupes(): BelongsToMany
    {
        return $this->belongsToMany(Groupe::class);
    }

    public function candidatures(): HasMany
    {
        return $this->hasMany(Candidature::class);
    }

    public function emargements(): HasMany
    {
        return $this->hasMany(Emargement::class);
    }

    public function estAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function nomComplet(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function initiales(): string
    {
        return mb_strtoupper(mb_substr($this->prenom, 0, 1).mb_substr($this->nom, 0, 1));
    }

    // Peut voter à ce scrutin : électeur actif d'une des classes concernées, ou membre d'un des groupes concernés
    public function estElecteurDe(Scrutin $scrutin): bool
    {
        if ($this->role !== 'electeur' || ! $this->actif) {
            return false;
        }

        return ($this->classe_id !== null && $scrutin->classes->contains('id', $this->classe_id))
            || $scrutin->groupes()->whereIn('groupes.id', $this->groupes()->pluck('groupes.id'))->exists();
    }

    public function aVote(Scrutin $scrutin): bool
    {
        return $this->emargements()->where('scrutin_id', $scrutin->id)->exists();
    }

    public function scopeElecteurs(Builder $requete): Builder
    {
        return $requete->where('role', 'electeur');
    }
}
