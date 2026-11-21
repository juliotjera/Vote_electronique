# Image de production : FrankenPHP (serveur web et PHP 8.4 en un seul programme) + planificateur + file d'attente.
# docker build -t vote-electronique .   ·   docker run -p 8080:8080 --env-file .env.production vote-electronique

# 1) Dépendances PHP (sans les outils de développement)
FROM dunglas/frankenphp:1-php8.4 AS base
RUN install-php-extensions pdo_pgsql pdo_sqlite gd intl zip opcache pcntl
# Le binaire FrankenPHP porte une capacité Linux (ports < 1024) que l'hébergeur refuse à un utilisateur
# non-root (« Operation not permitted ») ; inutile ici puisque l'application écoute sur le port 8080.
RUN apt-get update && apt-get install -y --no-install-recommends libcap2-bin \
    && setcap -r /usr/local/bin/frankenphp \
    && apt-get purge -y libcap2-bin && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# 2) CSS et JavaScript compilés (Tailwind lit aussi les vues de pagination de vendor/)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=base /app/vendor ./vendor
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# 3) Image finale
FROM base
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr PORT=8080
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && rm -f public/hot \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy \
    && cp docker/php.ini "$PHP_INI_DIR/conf.d/zz-vote.ini" \
    && chmod +x docker/demarrer.sh
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s CMD curl -fsS "http://127.0.0.1:${PORT}/up" || exit 1
CMD ["docker/demarrer.sh"]
