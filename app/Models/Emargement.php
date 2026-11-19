<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Liste d'émargement : qui a voté, jamais pour qui
#[Fillable(['scrutin_id', 'user_id', 'date_vote'])]
class Emargement extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['date_vote' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scrutin(): BelongsTo
    {
        return $this->belongsTo(Scrutin::class);
    }
}
