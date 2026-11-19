<?php

use App\Models\Scrutin;
use App\Models\User;
use App\Services\CycleScrutins;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Avis d'ouverture, rappels, chiffres figés à la clôture, suppression des données après 30 jours
Artisan::command('scrutins:avancer', function (CycleScrutins $cycle) {
    $bilan = $cycle->avancer();
    $this->info(collect($bilan)->map(fn ($n, $cle) => "{$cle} : {$n}")->implode(' · '));
})->purpose('Fait avancer les scrutins dans le temps (notifications, clôture, purge)');

Schedule::command('scrutins:avancer')->everyMinute()->withoutOverlapping();

// Démonstration en ligne : données fictives au premier démarrage, puis recréées dès que le vote de démonstration est clos
Artisan::command('demo:preparer {--rafraichir : recrée les données si plus aucun vote n’est ouvert}', function () {
    if (User::count() === 0) {
        $this->call('db:seed', ['--force' => true]);

        return $this->info('Données de démonstration créées.');
    }
    $ouvert = Scrutin::whereNotNull('programme_at')->whereNull('publie_at')
        ->where('ouverture_at', '<=', now())->where('cloture_at', '>', now())->exists();
    if ($this->option('rafraichir') && config('vote.demo_reinitialisation') && ! $ouvert) {
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        return $this->info('Données de démonstration recréées.');
    }
    $this->info('Rien à faire.');
})->purpose('Prépare les données du site de démonstration');

if (config('vote.demo_reinitialisation')) {
    Schedule::command('demo:preparer --rafraichir')->hourly()->withoutOverlapping();
}
