<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

// Langue de l'interface choisie par le visiteur (français par défaut, ou malagasy).
// L'administration travaille en français ; les pages des électeurs sont traduites.
class DefinirLangue
{
    public const LANGUES = ['fr' => 'Français', 'mg' => 'Malagasy'];

    public function handle(Request $request, Closure $next): Response
    {
        $langue = $request->hasSession() ? $request->session()->get('langue', 'fr') : 'fr';
        // les écrans d'administration restent en français, quelle que soit la langue choisie
        if (! array_key_exists($langue, self::LANGUES) || $request->routeIs('admin.*')) {
            $langue = 'fr';
        }
        app()->setLocale($langue);
        Carbon::setLocale($langue);

        return $next($request);
    }
}
