#!/bin/sh
# Démarrage du conteneur : configuration mise en cache, base à jour, puis trois processus :
# le planificateur (avis, rappels, clôture, purge), la file d'attente (e-mails) et le serveur web.
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan migrate --force

# site de démonstration : données fictives au premier démarrage, recréées au réveil si le vote de démo est clos
# (l’offre gratuite endort le serveur : le rafraîchissement horaire ne suffit pas)
if [ "$COMPTES_DEMO" = "true" ]; then
    php artisan demo:preparer --rafraichir
fi

# un processus qui s'arrête est relancé (le serveur web, lui, est le processus principal)
( while true; do php artisan schedule:work; sleep 5; done ) &
( while true; do php artisan queue:work --tries=3 --backoff=30 --max-time=3600; sleep 5; done ) &

exec frankenphp php-server --root public/ --listen ":${PORT:-8080}"
