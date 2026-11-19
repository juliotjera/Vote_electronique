<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'action', 'details', 'ip'])]
class Journal extends Model
{
    protected $table = 'journal';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Trace une action (jamais le contenu d'un bulletin). $userId : compte concerné quand personne n'est connecté
    public static function noter(string $action, array $details = [], ?int $userId = null): void
    {
        static::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'details' => $details ?: null,
            'ip' => self::anonymiser(request()?->ip()),
        ]);
    }

    // Donnée personnelle minimisée : on garde le réseau, pas la machine (192.168.1.0, 2001:db8:1::)
    public static function anonymiser(?string $ip): ?string
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        $masque = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? str_repeat(chr(255), 3).chr(0) : str_repeat(chr(255), 6).str_repeat(chr(0), 10);

        return inet_ntop(inet_pton($ip) & $masque);
    }
}
