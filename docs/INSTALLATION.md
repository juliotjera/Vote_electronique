# Guide d’installation — Vote électronique

## 1. Prérequis

| Outil | Version | Rôle |
| --- | --- | --- |
| PHP | 8.4.1 ou plus, 8.5 testé (extensions `pdo_sqlite` ou `pdo_pgsql`, `mbstring`, `gd`) | application |
| Composer | 2.x | dépendances PHP |
| Node.js | 20 ou plus | compilation du CSS et du JavaScript (Vite) |
| Base de données | SQLite (développement) ou PostgreSQL 15+ (production) | données |

## 2. Installation en local

```bash
git clone <adresse-du-dépôt> Vote_Electronique
cd Vote_Electronique
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # crée database/database.sqlite et les données de démonstration
```

Lancer l’application (serveur, Vite, file d’attente et journaux en une commande) :

```bash
composer run dev
```

Ouvrir <http://localhost:8000>. Sur **Se connecter**, les boutons « Essayer sans compte » ouvrent l’espace d’un électeur, d’un candidat ou de l’administration. En développement, le code de connexion s’affiche à l’écran et les e-mails sont écrits dans `storage/logs/laravel.log`.

Si les pages s’affichent sans style après un arrêt brutal de `composer run dev`, supprimer le fichier `public/hot` ou relancer la commande.

### Repartir de données neuves

Le scrutin de démonstration « L2 » se ferme deux jours après la création des données :

```bash
php artisan migrate:fresh --seed
```

## 3. Tests

```bash
php artisan test
```

54 tests (268 vérifications), dont un test de charge de 500 électeurs. Détail : [`RAPPORT_DE_TESTS.md`](RAPPORT_DE_TESTS.md).

## 4. Mise en production (Render + Neon, ou tout hébergeur PHP)

### Variables d’environnement

| Variable | Valeur |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | générée par `php artisan key:generate --show` |
| `APP_URL` | adresse publique en `https://` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | chaîne de connexion PostgreSQL (Neon : `?sslmode=require`) |
| `SESSION_ENCRYPT` | `true` |
| `SESSION_SECURE_COOKIE` | `true` |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | serveur d’envoi des codes et des avis |
| `COMPTES_DEMO` | `true` pour une démonstration publique, `false` pour un vrai scrutin |

### Commandes de déploiement

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Tâche planifiée (obligatoire)

Le planificateur envoie les avis d’ouverture, les rappels, fige les chiffres à la clôture et supprime les listes d’émargement à 30 jours. L’ouverture et la clôture du vote, elles, ne dépendent pas de lui.

```
* * * * * cd /chemin/vers/Vote_Electronique && php artisan schedule:run >> /dev/null 2>&1
```

Sur un hébergeur sans cron, lancer `php artisan schedule:work` comme processus permanent.

### Sécurité en production

- HTTPS : l’application force les adresses en `https://` et envoie l’en-tête `Strict-Transport-Security` quand `APP_ENV=production`. Derrière un proxy (Render), l’adresse IP réelle et le protocole sont lus grâce à `trustProxies`.
- En-têtes envoyés sur chaque page : `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`.
- Données au repos : utiliser une base dont le stockage est chiffré (c’est le cas de Neon) ; les codes de connexion et les reçus ne sont stockés que sous forme d’empreinte SHA-256.
- Comptes de démonstration : `COMPTES_DEMO=false` pour un vrai scrutin.
