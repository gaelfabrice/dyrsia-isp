# DYRSIA ISP — README Projet

DYRSIA ISP est une plateforme PHP/MySQL de gestion ISP, Hotspot et MikroTik. Elle permet de gérer des clients, routeurs, forfaits, vouchers, paiements, instances administrateur et abonnements.

## Objectif du projet

Le projet fournit une solution complète pour opérateurs ISP et hotspots :

- gestion des clients ;
- gestion des routeurs MikroTik ;
- création de forfaits Hotspot et PPPoE ;
- génération et impression de vouchers ;
- portail client ;
- tableau de bord administrateur ;
- tableau de bord SuperAdmin ;
- isolation des données par instance ;
- création d'instances ISP ;
- abonnement administrateur avec période d'essai ;
- notifications email, WhatsApp, SMS et Telegram selon configuration.

## Architecture

Le projet est une application **client/serveur PHP**.

### Partie serveur

- PHP
- MySQL/MariaDB
- Controllers dans `system/controllers`
- Services dans `system/autoload`
- Widgets dans `system/widgets`
- Jobs cron dans `system/cron_*.php`
- Configuration via `config.php` ou variables d'environnement

### Partie client

- Templates Smarty dans `ui/ui`
- CSS, JavaScript et assets dans `ui/ui/styles`, `ui/ui/scripts`, `ui/ui/images`
- Pages administrateur, client et SuperAdmin

## Base de données

Tables principales :

- `tbl_users`
- `tbl_customers`
- `tbl_plans`
- `tbl_routers`
- `tbl_voucher`
- `tbl_transactions`
- `tbl_tenants`
- `admin_subscriptions`
- `admin_subscription_invoices`
- `admin_subscription_payments`
- `admin_wallet`
- `admin_wallet_logs`

## Fonctionnalités principales

### Administration ISP

- Tableau de bord administrateur
- Statistiques clients, ventes et vouchers
- Gestion clients
- Gestion forfaits
- Gestion routeurs
- Gestion Hotspot
- Gestion PPPoE
- Vouchers et stock de vouchers
- Recharges client
- Transactions
- Portail client

### SuperAdmin

- Gestion des administrateurs
- Gestion des instances ISP
- Vue globale des abonnements
- Statistiques des instances
- Actions rapides sur les abonnements
- Suivi des statuts `trial`, `active`, `grace`, `expired`

### Multi-instance

Chaque instance dispose de ses propres données. Les données sont isolées par administrateur ou tenant :

- plans ;
- vouchers ;
- clients ;
- routeurs ;
- transactions ;
- statistiques ;
- widgets.

### Création d'instance

Lorsqu'une instance est créée :

- un sous-domaine/slug est défini ;
- un utilisateur admin est généré ;
- le username correspond au sous-domaine ;
- un mot de passe sécurisé est généré ;
- un email HTML DYRSIA est envoyé avec les accès ;
- l'instance reçoit une période d'essai.

### Abonnement administrateur

Le système prend en charge :

- période d'essai ;
- abonnement actif ;
- période de grâce ;
- expiration ;
- factures ;
- historique de paiements ;
- plans Business et Pro ;
- restrictions selon le plan.

## Structure du projet

```text
.
├── Dockerfile
├── render.yaml
├── config.sample.php
├── init.php
├── index.php
├── router.php
├── system
│   ├── autoload
│   ├── controllers
│   ├── cron_tenant_cleanup.php
│   ├── widgets
│   ├── uploads
│   └── cache
├── ui
│   ├── ui
│   ├── compiled
│   └── cache
├── scripts
│   ├── build-dist.sh
│   └── deploy-vps.sh
├── dist
├── README_DEPLOYMENT.md
└── README_PROJECT.md
```

## Prérequis

- PHP 7.4 ou supérieur selon environnement
- MySQL ou MariaDB
- Apache avec rewrite activé
- Extensions PHP : `pdo`, `pdo_mysql`, `mysqli`, `gd`, `mbstring`, `zip`, `curl`, `openssl`

## Installation locale

Créer le fichier de configuration :

```bash
cp config.sample.php config.php
```

Configurer la base de données dans `config.php` ou via variables d'environnement.

Exemple :

```text
APP_URL=http://127.0.0.1:8000
APP_STAGE=Dev
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dyrsia
DB_USERNAME=root
DB_PASSWORD=
```

Lancer le serveur PHP intégré :

```bash
php -S 127.0.0.1:8000 router.php
```

Ouvrir :

```text
http://127.0.0.1:8000
```

## Configuration

### Fichier `config.php`

Le fichier `config.php` ne doit pas être versionné. Il contient :

- URL de l'application ;
- environnement ;
- accès base de données ;
- secrets API ;
- tokens cron/health si utilisés.

### Variables d'environnement

```text
APP_URL
APP_STAGE
APP_KEY
CRON_TOKEN
HEALTH_TOKEN
API_SECRET
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

## Déploiement

Voir le guide dédié :

```text
README_DEPLOYMENT.md
```

Il couvre GitHub, Render, Docker, cPanel et génération de l'archive `dist`.

## Générer une archive cPanel

```bash
bash scripts/build-dist.sh
```

L'archive sera créée dans :

```text
dist/dyrsia-server-YYYYMMDDHHMMSS.tar.gz
```

## Cron jobs

Le projet contient un cron pour synchroniser les statuts d'abonnement et nettoyer les tenants orphelins :

```text
system/cron_tenant_cleanup.php
```

Exécution :

```bash
php system/cron_tenant_cleanup.php
```

## Sécurité

Recommandations :

- ne jamais commiter `config.php` ;
- ne jamais commiter `.env` ;
- utiliser HTTPS en production ;
- protéger les accès MySQL ;
- utiliser des mots de passe forts ;
- vérifier les permissions des dossiers upload/cache ;
- limiter l'accès distant MySQL aux IP autorisées.

## Dossiers inscriptibles

Ces dossiers doivent être inscriptibles par PHP :

```text
system/uploads
system/cache
ui/compiled
ui/cache
```

## Santé de l'application

Endpoint prévu pour Render :

```text
/health.php
```

## Branding

Le branding visible de l'application est DYRSIA. Le nom d'entreprise par défaut est :

```text
CompanyName = DYRSIA
```

Certains noms techniques historiques peuvent rester dans le code, les URLs externes ou les fichiers CSS afin de ne pas casser la compatibilité.

## Dépannage

### Erreur 500

Vérifier :

- logs Apache/PHP ;
- permissions cache/uploads ;
- configuration base de données ;
- version PHP ;
- extensions PHP.

### Erreur base de données

Vérifier :

- host ;
- port ;
- utilisateur ;
- mot de passe ;
- nom de base ;
- accès distant si hébergement externe.

### Templates Smarty non mis à jour

Vider les fichiers compilés/cache :

```text
ui/compiled
ui/cache
```

### Emails affichés en code source

Les emails HTML complets doivent être envoyés avec le mode HTML brut de `Message::sendEmail`.

## Licence

Projet basé sur une plateforme PHP/MikroTik billing adaptée et brandée pour DYRSIA.
