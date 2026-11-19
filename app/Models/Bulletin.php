<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Bulletin anonyme : aucune colonne ne le relie à un électeur
#[Fillable(['scrutin_id', 'rang', 'blanc', 'recu_empreinte', 'empreinte'])]
class Bulletin extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['blanc' => 'boolean'];
    }

    public function choix(): BelongsToMany
    {
        return $this->belongsToMany(Candidature::class, 'bulletin_choix');
    }
}
