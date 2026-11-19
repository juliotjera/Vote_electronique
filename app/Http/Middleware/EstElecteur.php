<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// L'administration n'a pas d'espace électeur : elle est redirigée vers son tableau de bord
class EstElecteur
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->estAdmin()) {
            return redirect()->route('admin.tableau');
        }
        // électeur désactivé pendant sa session : il est déconnecté
        if ($request->user() && ! $request->user()->actif) {
            auth()->logout();

            return redirect()->route('connexion')->with('erreur', 'Votre compte a été désactivé par l’administration.');
        }

        return $next($request);
    }
}
