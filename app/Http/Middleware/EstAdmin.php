<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EstAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->estAdmin(), 403, 'Espace réservé à l’administration des élections.');

        return $next($request);
    }
}
