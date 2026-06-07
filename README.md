# WifiZone / DYRSIA ISP

Plateforme PHP/MySQL de gestion ISP, Hotspot et MikroTik. Elle permet de gérer clients, routeurs, forfaits, vouchers, paiements Mobile Money, instances administrateur et abonnements multi-tenant.

Basée sur [phpwifizones](https://github.com/hotspotbilling/phpwifizones), adaptée et brandée pour **DYRSIA**.

## Fonctionnalités

### Gestion ISP & Hotspot

- Tableau de bord administrateur (KPI, graphiques, actions rapides)
- Clients actifs, vouchers, recharges et forfaits Hotspot / PPPoE / VPN
- Routeurs MikroTik, pools IP, monitoring réseau
- Rapports journaliers, consommation data, export CSV
- Portail client et provisionnement d’instances ISP

### Multi-instance (tenant)

- Isolation des données par administrateur / instance ISP
- Sous-domaines dédiés (`slug.localhost` en dev)
- Création d’instance avec période d’essai et email d’accès
- Abonnements admin : trial, actif, grâce, expiré, facturation

### Paiements

- **CamPay** — Mobile Money (hotspot + abonnements admin, USSD + confirmation)
- **MyPVit** — passerelle Mobile Money additionnelle
- Gestion des gateways depuis l’interface admin

### SuperAdmin

- Gestion des administrateurs (liste, édition, suppression)
- Instances ISP, abonnements, paramètres globaux
- Impersonation de comptes (mode démo / support)

### Interface admin

- Thème sombre / clair
- Pages modernisées : Dashboard, Active Customers, Daily Reports, Data Usage, Administrator Users
- Sidebar restructurée, recherche et pagination sur les listes principales

## Prérequis

- PHP **8.2+** (7.4+ possible selon environnement)
- MySQL ou MariaDB
- Extensions : `pdo`, `pdo_mysql`, `mysqli`, `gd`, `mbstring`, `zip`, `curl`, `openssl`
- Apache avec `mod_rewrite` **ou** serveur PHP intégré + `router.php`

## Installation locale

### 1. Configuration

```bash
cp config.sample.php config.php
# ou
cp .env.example .env
```

Exemple de variables (`.env` ou `config.php`) :

```text
APP_URL=http://127.0.0.1:8080
APP_STAGE=Dev
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wifizones
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Base de données

Créer la base, puis ouvrir l’application : l’installateur web guide la première configuration si `config.php` est absent.

### 3. Lancer le serveur de développement

```bash
bash scripts/dev-server.sh
```

Par défaut : [http://127.0.0.1:8080](http://127.0.0.1:8080)

Admin : `http://127.0.0.1:8080/?_route=admin`

Port alternatif :

```bash
PORT=8000 bash scripts/dev-server.sh
```

## Structure du projet

```text
.
├── index.php              # Point d'entrée web
├── init.php               # Bootstrap, autoload, constantes
├── router.php             # Router pour php -S
├── config.sample.php      # Modèle de configuration
├── system/
│   ├── autoload/          # Classes métier (Tenant, Mikrotik, Package…)
│   ├── controllers/       # Routes admin / client / API
│   ├── devices/           # Drivers Mikrotik, Radius…
│   ├── paymentgateway/    # CamPay, MyPVit…
│   ├── plugin/            # Plugins hotspot
│   ├── widgets/           # Widgets dashboard
│   ├── cron_*.php         # Tâches planifiées
│   ├── cache/             # Cache applicatif
│   └── uploads/           # Fichiers uploadés
├── ui/
│   ├── ui/                # Templates Smarty (admin, customer)
│   ├── ui/styles/         # CSS (admin-command, admin-pages, wifizones…)
│   ├── compiled/          # Templates Smarty compilés (généré)
│   └── cache/             # Cache Smarty (généré)
├── scripts/
│   ├── dev-server.sh      # Serveur local
│   ├── build-dist.sh      # Archive de déploiement
│   └── deploy-vps.sh      # Déploiement VPS
├── README_DEPLOYMENT.md   # Guide GitHub / Render / Docker / cPanel
└── Dockerfile
```

## Rôles utilisateurs

| Rôle        | Accès principal                                      |
|-------------|------------------------------------------------------|
| SuperAdmin  | Toute la plateforme, instances, abonnements          |
| Admin       | Son instance ISP (clients, plans, routeurs…)         |
| Agent       | Comptes rattachés (Report, Sales…)                 |
| Customer    | Portail client                                       |

## Cron jobs

| Script                         | Rôle                                      |
|--------------------------------|-------------------------------------------|
| `system/cron_tenant_cleanup.php` | Nettoyage tenants / abonnements         |
| `system/cron_data_usage.php`     | Synchronisation consommation data       |

Exemple crontab :

```bash
*/15 * * * * php /chemin/vers/projet/system/cron_tenant_cleanup.php
0 * * * * php /chemin/vers/projet/system/cron_data_usage.php
```

## Cache & templates

Après modification des templates Smarty (`.tpl`), vider le cache compilé :

```bash
rm -f ui/compiled/*.php
rm -f ui/cache/*.php
find system/cache -type f ! -name 'index.html' -delete
```

Les dossiers suivants doivent être **inscriptibles** par PHP :

```text
ui/compiled
ui/cache
system/cache
system/uploads
```

## Déploiement

Voir le guide détaillé : [README_DEPLOYMENT.md](README_DEPLOYMENT.md)

- GitHub + Render (`render.yaml`, `Dockerfile`)
- Archive cPanel : `bash scripts/build-dist.sh` → `dist/`
- Health check : `/health.php`

## Sécurité

- Ne **jamais** commiter `config.php`, `.env` ou clés API réelles
- Utiliser HTTPS en production
- Protéger les endpoints cron avec `CRON_TOKEN`
- Les actions sensibles (suppression admin, paiements) vérifient rôle et permissions

## Dépannage

| Problème                    | Piste                                              |
|-----------------------------|----------------------------------------------------|
| Erreur 500                  | Logs PHP, `config.php`, extensions, permissions    |
| UI ancienne après modif     | Vider `ui/compiled` et `ui/cache`                  |
| Connexion DB                | Host, port, utilisateur, droits distants           |
| Hotspot / paiement local    | Vérifier `APP_URL` et port du serveur dev (8080)   |

## Documentation complémentaire

- [README_DEPLOYMENT.md](README_DEPLOYMENT.md) — déploiement production
- [README_PROJECT.md](README_PROJECT.md) — notes architecture détaillées
- [CHANGELOG.md](CHANGELOG.md) — historique des versions

## Licence

GNU General Public License v2 ou ultérieure — voir [LICENSE](LICENSE).

Projet dérivé de [phpwifizones](https://github.com/hotspotbilling/phpwifizones) (hotspotbilling).
