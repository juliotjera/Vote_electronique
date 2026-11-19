<?php

namespace App\Models;

use App\Enums\EtatScrutin;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'titre', 'description', 'type', 'nombre_choix', 'nombre_elus', 'vote_blanc',
    'candidatures_fin_at', 'ouverture_at', 'cloture_at', 'resultats_publics', 'cree_par',
])]
class Scrutin extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'vote_blanc' => 'boolean',
            'resultats_publics' => 'boolean',
            'candidatures_fin_at' => 'datetime',
            'ouverture_at' => 'datetime',
            'cloture_at' => 'datetime',
            'programme_at' => 'datetime',
            'publie_at' => 'datetime',
            'notif_ouverture_at' => 'datetime',
            'notif_rappel_at' => 'datetime',
            'notif_cloture_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class);
    }

    public function groupes(): BelongsToMany
    {
        return $this->belongsToMany(Groupe::class);
    }

    // À qui s'adresse le scrutin, en clair : « L2, Club informatique »
    public function publicCible(): string
    {
        return $this->classes->pluck('nom')->merge($this->groupes->pluck('nom'))->implode(', ');
    }

    public function candidatures(): HasMany
    {
        return $this->hasMany(Candidature::class);
    }

    public function emargements(): HasMany
    {
        return $this->hasMany(Emargement::class);
    }

    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function etat(): EtatScrutin
    {
        $maintenant = now();

        return match (true) {
            $this->programme_at === null => EtatScrutin::Brouillon,
            $this->publie_at !== null => EtatScrutin::Publie,
            $maintenant->lt($this->ouverture_at) => EtatScrutin::Programme,
            $maintenant->lt($this->cloture_at) => EtatScrutin::Ouvert,
            default => EtatScrutin::Clos,
        };
    }

    public function estOuvert(): bool
    {
        return $this->etat() === EtatScrutin::Ouvert;
    }

    // Dépôt des candidatures : scrutin annoncé, avant la date limite (ou avant l'ouverture)
    public function candidaturesOuvertes(): bool
    {
        return $this->etat() === EtatScrutin::Programme
            && now()->lt($this->candidatures_fin_at ?? $this->ouverture_at);
    }

    public function estModifiable(): bool
    {
        return $this->etat() === EtatScrutin::Brouillon;
    }

    // Électeurs inscrits : actifs, dans une des classes concernées ou membres d'un des groupes concernés
    public function electeursInscrits(): Builder
    {
        $classes = $this->classes()->pluck('classes.id');
        $groupes = $this->groupes()->pluck('groupes.id');

        return User::query()->electeurs()->where('actif', true)
            ->where(fn (Builder $q) => $q->whereIn('classe_id', $classes)
                ->orWhereHas('groupes', fn (Builder $g) => $g->whereIn('groupes.id', $groupes)));
    }

    public function nombreInscrits(): int
    {
        return $this->inscrits_cloture ?? $this->electeursInscrits()->count();
    }

    public function nombreVotants(): int
    {
        return $this->votants_cloture ?? $this->emargements()->count();
    }

    public function tauxParticipation(): float
    {
        $inscrits = $this->nombreInscrits();

        return $inscrits ? round($this->nombreVotants() / $inscrits * 100, 1) : 0.0;
    }

    // Scrutins visibles par un électeur : annoncés, et ouverts à sa classe ou à un de ses groupes
    public function scopeVisiblesPour(Builder $requete, User $user): Builder
    {
        $groupes = $user->groupes()->pluck('groupes.id');

        return $requete->whereNotNull('programme_at')
            ->where(fn (Builder $q) => $q->whereHas('classes', fn (Builder $c) => $c->where('classes.id', $user->classe_id))
                ->orWhereHas('groupes', fn (Builder $g) => $g->whereIn('groupes.id', $groupes)));
    }

    public function libelleType(): string
    {
        return $this->type === 'plurinominal'
            ? __('Plurinominal : jusqu’à :n choix', ['n' => $this->nombre_choix])
            : __('Uninominal : un seul choix');
    }
}
