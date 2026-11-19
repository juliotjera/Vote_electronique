<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'description'])]
class Classe extends Model
{
    use HasFactory;

    protected $table = 'classes';

    public function electeurs(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'electeur');
    }

    public function scrutins(): BelongsToMany
    {
        return $this->belongsToMany(Scrutin::class);
    }
}
