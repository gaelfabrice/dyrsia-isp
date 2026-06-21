# WhatsApp Gateway — configuration DYRSIA / WifiZone

Ce guide décrit l’intégration du plugin **Whatsapp Gateway** déjà présent dans le projet (`system/plugin/WhatsappGateway.php`).

## Architecture

```
cron_wifizone.php
    → WifiZoneNotify::processRenewalReminders()
        → Message::sendWhatsapp()
            → hook send_whatsapp
                → whatsappGateway_hook_send_whatsapp()
                    → API Gateway (VPS)
                        → WhatsApp client (numéro émetteur)
                            → client final (677… / 33…)
```

Le même canal sert aux rappels d’expiration, aux codes voucher hotspot et aux alertes routeur.

## Prérequis

1. Un **serveur VPS** (Linux) accessible depuis votre application PHP
2. Une **API WhatsApp Gateway** compatible avec le plugin (format ibnux / PHPNuxBill)
3. Un **numéro WhatsApp** dédié à l’envoi (SIM ou eSIM, pas votre usage personnel)

## API attendue par le plugin

| Endpoint | Méthode | Auth | Corps / réponse |
|---|---|---|---|
| `/auth` | GET ou POST | Basic `numéro:secret` | `{ "status": 200, "data": { "token": "JWT…" } }` |
| `/login` | POST | Bearer JWT | Pair / QR pour connecter le numéro |
| `/send/text` | POST | Bearer JWT | `msisdn=33761951914&message=Hello` |

Le JWT est stocké localement dans :

```
system/uploads/whatsapp/<numero>.nux
```

Exemple de contenu :

```json
{"jwt":"eyJhbGciOiJIUzI1NiIs…"}
```

## Configuration dans l’admin

### 1. Plugin Whatsapp Gateway

Menu : **Whatsapp Gateway → Configuration**

| Champ | Exemple | Description |
|---|---|---|
| **Gateway URL** | `https://wa.votredomaine.com` | URL de base de l’API (sans slash final) |
| **Secret** | `votre_secret_fort` | Clé partagée avec l’API |
| **Country code** | `237` | Indicatif sans `+` (Cameroun). `33` pour la France |

### 2. Connecter le numéro émetteur

1. **Whatsapp Gateway → Add Phone** — saisir le numéro émetteur (ex. `237677123456`)
2. **Login** — scanner le QR ou saisir le code pair
3. Vérifier qu’un fichier `.nux` apparaît dans `system/uploads/whatsapp/`

### 3. Paramètres application

Menu : **Settings → App**

- **Reminder Notification** → `By WhatsApp`
- Cocher **7 Days**, **3 Days**, **24 hours**

Menu : **Settings → Notifications**

Personnaliser les messages `reminder_7_day`, `reminder_3_day`, `reminder_24h`.

Placeholders utiles :

| Placeholder | Contenu |
|---|---|
| `[[name]]` | Nom du client |
| `[[package]]` | Nom du forfait |
| `[[expired_date]]` | Date/heure d’expiration |
| `[[payment_link]]` | Lien de renouvellement (token 24 h) |
| `[[price]]` | Prix du forfait |

### 4. Cron

```cron
*/5 * * * * php /var/www/wifizone/system/cron_wifizone.php
```

Les rappels sont dédoublonnés via la table `wifizone_renewal_reminders`.

## Test rapide

### Depuis l’admin

**Settings → App → Test WhatsApp** avec le numéro cible (ex. `33761951914`).

### Depuis le serveur (CLI)

```bash
# Aperçu des 3 messages sans envoi
php scripts/test_reminder_send.php --dry-run

# Envoi réel WhatsApp
php scripts/test_reminder_send.php --interval=24h --phone=33761951914 --via=wa --send

# Abonnements éligibles maintenant
php scripts/test_reminder_send.php --check-eligible
```

## Alternative : URL HTTP directe (`wa_url`)

Si vous n’utilisez pas le plugin Gateway, configurez dans **Settings → App → WhatsApp Notification** :

```
https://votre-api.com/send?phone=[number]&text=[text]
```

Le plugin Gateway a priorité via le hook `send_whatsapp` lorsqu’il est configuré.

## Email en secours

Si WhatsApp est indisponible, configurez :

- **Settings → App → Reminder Notification** → `By Email`
- SMTP déjà paramétré (`smtp_host`, etc.)

Test :

```bash
php scripts/test_reminder_send.php --interval=24h --email=client@example.com --via=email --send
```

## Dépannage

| Symptôme | Cause probable | Action |
|---|---|---|
| Aucun message | `whatsapp_gateway_url` vide | Configurer le plugin |
| `Not Connected` | JWT expiré / session WA déconnectée | Re-login dans Whatsapp Gateway |
| Message NULL | Pas de `.nux` ou gateway down | Vérifier VPS + fichier `.nux` |
| Doublons | Cron absent / table dedup | Vérifier `cron_wifizone.php` + table `wifizone_renewal_reminders` |
| Numéro FR (+33) non reçu | Format `msisdn` | Tester avec et sans `+` selon l’API |

## Sécurité

- Ne pas committer les fichiers `.nux` (JWT WhatsApp)
- Restreindre l’accès API Gateway par IP (IP de votre serveur DYRSIA)
- Utiliser HTTPS sur l’API Gateway
- Secret fort pour `whatsapp_gateway_secret`

## Où obtenir une API Gateway

Le plugin est conçu pour les gateways de l’écosystème **PHPNuxBill / ibnux**. Options courantes :

1. **Gateway fournie par votre revendeur** de billing (shop.stcncrm.xyz, etc.)
2. **Self-hosted** : déployer un service go-whatsapp compatible sur VPS
3. **`wa_url`** : tout fournisseur HTTP qui accepte `[number]` et `[text]`

Remplacez `https://wa.votredomaine.com` par l’URL réelle une fois le service déployé.
