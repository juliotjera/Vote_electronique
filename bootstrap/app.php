<?php

use App\Http\Middleware\DefinirLangue;
use App\Http\Middleware\EntetesDeSecurite;
use App\Http\Middleware\EstAdmin;
use App\Http\Middleware\EstElecteur;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EstAdmin::class,
            'electeur' => EstElecteur::class,
        ]);
        $middleware->web(append: [DefinirLangue::class, EntetesDeSecurite::class]);
        $middleware->redirectGuestsTo(fn () => route('connexion'));
        $middleware->redirectUsersTo(fn () => route('espace'));
        // derrière l'hébergeur (Render) : adresse IP réelle et HTTPS
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
