<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'empreinte', 'tentatives', 'expire_at', 'utilise_at'])]
class CodeConnexion extends Model
{
    protected $table = 'codes_connexion';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['expire_at' => 'datetime', 'utilise_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
