# Stack d'intégration DYRSIA

Configuration recommandée pour production (Cameroun / CEMAC).

## Vue d'ensemble

| Couche | Technologie | Rôle |
|---|---|---|
| Hébergement | Render / VPS + Docker | App PHP + cron |
| Base | MySQL (Render / externe) | Données billing |
| Accès MikroTik | WireGuard VPN | API RouterOS depuis le cloud |
| Paiement client | **Campay** (principal) | Mobile Money hotspot / PPPoE / SaaS |
| Paiement backup | MyPVit | Airtel / Moov / cartes |
| Messages clients | **WhatsApp Gateway** | Vouchers, rappels, support |
| Secours messages | SMTP Gmail | Email + rappels si pas de téléphone |
| Alertes ops | **Telegram** | Routeurs, cron, paiements KO |
| ONU / FTTH | GenieACS + cron sync | Monitoring ONU (optionnel) |
| Mobile (futur) | `wifizone_api.php` + JWT | App revendeur / client |

## Variables d'environnement (production)

Voir `.env.example` et `config.sample.php` :

| Variable | Usage |
|---|---|
| `APP_URL` | URL publique HTTPS |
| `APP_STAGE` | `Live` |
| `APP_KEY` | Cookies / signatures |
| `CRON_TOKEN` | Appel HTTP sécurisé de `cron.php` |
| `HEALTH_TOKEN` | `/health.php` monitoring |
| `DB_*` | Connexion MySQL |
| `APP_TIMEZONE` | `Africa/Douala` (GenieACS cron) |

Render : voir `render.yaml` (web + cron `*/5` + tokens générés).

## 1. WireGuard — MikroTik accessible depuis le cloud

Sans VPN, les IP privées (`10.0.0.x`) ne répondent pas depuis Render.

- Déployer WireGuard sur le VPS ou le routeur
- Exemple Apache : `scripts/apache-wifizone-wireguard.conf.example`
- Guide : `scripts/wireguard/README.md`

## 2. WhatsApp Gateway

- Plugin : `system/plugin/WhatsappGateway.php`
- Admin : **Notification → WhatsApp Gateway**
- API compatible : [go-whatsapp-multidevice-rest](https://github.com/dimaskiddo/go-whatsapp-multidevice-rest)
- Doc détaillée : [whatsapp-gateway.md](whatsapp-gateway.md)

Paramètres clés :

- `whatsapp_gateway_url`
- `whatsapp_gateway_secret`
- `whatsapp_country_code_phone` → `237`
- `user_notification_reminder` → `wa`
- `hotspot_message_via` → `both` (SMS d'abord, WhatsApp en secours — la connexion se fait via le portail captive)

## 3. Campay

- Admin : **Settings → Payment Gateway → Campay**
- Webhook HTTPS public obligatoire
- Utilisé par : hotspot, PPPoE, abonnement admin

## 4. Email (secours)

- **Settings → SMTP** (Gmail app password)
- Rappels : `user_notification_reminder` → `email`
- Bug email corrigé dans `Message::sendPackageNotification()`

## 5. Telegram (ops)

- **Settings → App → Telegram**
- `telegram_bot` + `telegram_target_id`
- Alertes auto : `WifiZoneOps` (cron, paiements, routeurs via `RouterMonitor`)

## 6. Cron

```cron
*/5 * * * * php /var/www/html/system/cron_wifizone.php
```

Inclus : paiements, rappels, expirations, GenieACS (si serveurs ACS), monitor clients (si activé), backup jobs.

Health check : `/healthz.php` (Render) ou `/health.php?token=…`

## 7. GenieACS (optionnel FTTH)

- Serveurs dans `tbl_acs_servers`
- Sync : `cron_acs_sync.php` (auto 1×/h via `WifiZoneOps`)
- UI : **GenieACS Devices** (plugin)
- Alertes offline : `WifiZoneNotify::checkGenieacsOffline()`

## 8. Ordre de mise en service

1. DB + `config.sample.php` / `.env`
2. WireGuard → test `scripts/mtik_diag.php`
3. Campay webhook
4. WhatsApp Gateway + test `scripts/test_reminder_send.php`
5. Telegram bot
6. Cron Render / crontab
7. Forfaits + sync MikroTik hotspot
8. Vérifier **Setup wizard** sur le dashboard admin

## Phases implémentées dans le code

| Phase | Contenu |
|---|---|
| **A — Stabiliser** | `WifiZoneOps`, cron unifié, healthz, config `.env`, GenieACS fix, scripts diag déplacés |
| **B — Monetiser** | Rappels J-7/J-3/24h, KPI widget, Telegram ops, lien paiement dans templates |
| **C — Scaler** | Setup wizard widget, provision tenant, doc workflow, WhatsApp plugin restauré |
