<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        // <x-layouts::public> et <x-layouts::app> pour les pages Blade qui ne sont pas des composants Livewire
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
        // relations chargées à la demande signalées en développement (requêtes N+1)
        Model::preventLazyLoading(! $this->app->isProduction());
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
