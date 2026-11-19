<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// En-têtes de sécurité sur chaque réponse : pas d'affichage dans un cadre (clic détourné),
// pas de devinette du type de fichier, adresse d'origine limitée, HTTPS imposé au navigateur en production.
class EntetesDeSecurite
{
    public function handle(Request $request, Closure $next): Response
    {
        $reponse = $next($request);

        $reponse->headers->set('X-Frame-Options', 'DENY');
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');
        $reponse->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $reponse->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if (app()->isProduction()) {
            $reponse->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $reponse;
    }
}
