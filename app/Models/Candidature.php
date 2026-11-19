<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['scrutin_id', 'user_id', 'slogan', 'programme', 'photo_id', 'statut', 'motif_rejet', 'decide_par', 'decide_at'])]
class Candidature extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['decide_at' => 'datetime'];
    }

    public function scrutin(): BelongsTo
    {
        return $this->belongsTo(Scrutin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeValidees(Builder $requete): Builder
    {
        return $requete->where('statut', 'validee');
    }

    // Engagements du programme, un par ligne (pour les afficher en liste)
    public function engagements(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $this->programme))));
    }

    public function urlPhoto(): ?string
    {
        return $this->photo_id ? route('photo', $this->photo_id) : null;
    }

    public const LIBELLES = [
        'en_attente' => 'En attente de validation',
        'validee' => 'Validée',
        'rejetee' => 'Refusée',
    ];

    public function libelleStatut(): string
    {
        return __(self::LIBELLES[$this->statut] ?? self::LIBELLES['en_attente']);
    }
}
