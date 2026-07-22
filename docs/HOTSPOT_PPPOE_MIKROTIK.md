# Configuration Hotspot & PPPoE — Commandes MikroTik

Ce document décrit comment DYRSIA configure le **Hotspot** et le **PPPoE** sur un routeur MikroTik : pages admin, paramètres stockés, ordre d’exécution et commandes RouterOS envoyées via l’API (port **8728**).

> Source principale : `system/autoload/Mikrotik.php`  
> Contrôleurs : `system/controllers/settings.php`

---

## Prérequis

1. **Routeur enregistré** dans **Réseau → Routeurs** (`tbl_routers`) avec IP, utilisateur et mot de passe API.
2. **Connectivité API** : le serveur PHP doit joindre le MikroTik sur le port **8728** (souvent via VPN WireGuard).
3. **Hotspot API URL** : adresse du serveur DYRSIA joignable **depuis le routeur** (ex. `https://wifizones.org` ou `http://10.0.0.1`), **pas** l’IP du MikroTik.
4. Les paramètres sont stockés dans `tbl_appconfig` (clés `hotspot_*` et `pppoe_setup_*`).

---

## Accès admin

| Service | Menu | Route |
|---------|------|-------|
| Hotspot | Paramètres → Hotspot | `?_route=settings/hotspot` |
| PPPoE | Paramètres → PPPoE Setup | `?_route=settings/pppoe-setup` |

### Boutons Hotspot

| Action | Effet |
|--------|-------|
| **Enregistrer** | Sauvegarde la config en base uniquement |
| **Envoyer vers MikroTik** | Envoie **login.html** seulement (si hotspot déjà installé sur le routeur) |
| **Send complet** | Déploiement complet : login.html + pool + profils + serveur + firewall + NAT + forfaits |

### Bouton PPPoE

| Action | Effet |
|--------|-------|
| **Enregistrer** | Sauvegarde la config en base |
| **Envoyer vers MikroTik** | Déploiement complet PPPoE + sync forfaits + sync clients + portail expirés |

---

# Hotspot

## Vue d’ensemble

L’assistant Hotspot (3 étapes dans `hotspot-wizard.js`) configure un portail captif MikroTik :

- Pool IP et adresse locale
- Profil hotspot (DNS, login-by, cookies, RADIUS optionnel)
- Réseau hotspot
- Bridge firewall (`use-ip-firewall`) **avant** le serveur hotspot
- Serveur `/ip hotspot`
- Intégrité d’interception (DHCP, walled-garden, firewall)
- NAT masquerade
- Page `login.html` personnalisée

Fonction centrale : `Mikrotik::applyHotspotSetupFromConfig()`

## Paramètres (`tbl_appconfig`)

### Réseau

| Clé | Exemple | Description |
|-----|---------|-------------|
| `hotspot_login_router` | `MK` | Nom du routeur cible |
| `hotspot_interface` | `bridge-hotspot` | Interface du hotspot |
| `hotspot_local_address` | `10.0.0.1/24` | IP locale sur l’interface |
| `hotspot_pool_name` | `dyrsia-hotspot-pool` | Nom du pool |
| `hotspot_address_pool` | `10.0.0.2-10.0.0.254` | Plage du pool |
| `hotspot_masquerade` | `1` | Active le NAT masquerade |

### Hotspot

| Clé | Exemple | Description |
|-----|---------|-------------|
| `hotspot_name` | `dyrsia-hotspot` | Nom du serveur (auto : `dyrsia-{interface}`) |
| `hotspot_profile` | `default` | Profil hotspot |
| `hotspot_dns_name` | `hotspot.monreseau.net` | DNS name du profil |
| `hotspot_dns_server` | `8.8.8.8` | DNS clients |
| `hotspot_smtp_server` | `0.0.0.0` | SMTP du profil |
| `hotspot_login_methods` | `http-chap,mac-cookie` | Méthodes `login-by` |
| `hotspot_cookie_lifetime` | `1d 00:00:00` | Durée cookie MAC |
| `hotspot_idle_timeout` | `00:10:00` | Timeout inactivité |
| `hotspot_address_per_mac` | `1` | Paramètre MikroTik : `addresses-per-mac` |
| `hotspot_api_url` | `https://wifizones.org` | URL backend (captive + RADIUS) |

### RADIUS (optionnel)

| Clé | Description |
|-----|-------------|
| `hotspot_use_radius` | `1` pour activer |
| `hotspot_radius_secret` | Secret (généré auto si vide) |

## Ordre d’exécution (Send complet)

L’ordre ci-dessous est **critique** — le bridge firewall doit être configuré **avant** la création du serveur hotspot.

```
1.  Pool IP
2.  Adresse IP sur interface
3.  [Si RADIUS] Client RADIUS + entrée NAS FreeRADIUS + purge sessions
4.  Profil hotspot
5.  Réseau hotspot (/ip hotspot network)
6.  Bridge firewall (use-ip-firewall, fast-forward, hw-offload)  ← AVANT serveur
7.  Serveur hotspot (/ip hotspot add|set)
8.  Intégrité interception (voir sous-étapes)
9.  NAT masquerade (si activé)
10. login.html (avant ou pendant send selon mode)
11. Push des forfaits hotspot vers le routeur
```

## Commandes MikroTik envoyées

### 1. Pool IP

```routeros
/ip pool add name=dyrsia-hotspot-pool ranges=10.0.0.2-10.0.0.254
# ou /ip pool set si le pool existe déjà
```

### 2. Adresse IP

```routeros
/ip address add address=10.0.0.1/24 interface=bridge-hotspot
# idempotent : ne recrée pas si déjà présente
```

### 3. RADIUS (si `hotspot_use_radius=1`)

**Sur le MikroTik :**

```routeros
/radius add address=10.0.0.1 secret="..." service=hotspot timeout=3s
```

**Sur FreeRADIUS (base `radius`) :**

- Entrée `nas` : IP du routeur + secret + nom routeur
- Secret généré automatiquement si absent, puis sauvegardé dans `hotspot_radius_secret`

**Purge sessions :**

```routeros
/ip hotspot active remove [find]
/ip hotspot cookie remove [find]
```

### 4. Profil hotspot

```routeros
/ip hotspot profile add name=default
/ip hotspot profile set [find name=default] \
  html-directory=hotspot \
  login-by=http-chap,mac-cookie \
  smtp-server=0.0.0.0 \
  dns-server=8.8.8.8 \
  dns-name=hotspot.monreseau.net \
  idle-timeout=00:10:00 \
  use-radius=no \
  radius-accounting=no
# + http-cookie-lifetime si mac-cookie activé
```

Avec RADIUS : `use-radius=yes`, `radius-accounting=yes`, `login-by` adapté.

### 5. Réseau hotspot

```routeros
/ip hotspot network add address=10.0.0.0/24 gateway=10.0.0.1 profile=default \
  dns-server=8.8.8.8 comment="DYRSIA hotspot"
```

### 6. Bridge firewall (CRITIQUE)

Uniquement si l’interface est un **bridge** :

```routeros
/interface bridge settings set use-ip-firewall=yes allow-fast-path=no
/interface bridge set bridge-hotspot fast-forward=no
/interface bridge port set [find bridge=bridge-hotspot] hw=no
```

Script one-shot `dyrsia_bridge_hs` exécuté en complément.  
Script de boot persistant pour réappliquer au redémarrage.

### 7. Serveur hotspot

```routeros
/ip hotspot add name=dyrsia-hotspot interface=bridge-hotspot profile=default \
  address-pool=dyrsia-hotspot-pool addresses-per-mac=1 disabled=no
```

> Paramètre correct : **`addresses-per-mac`** (avec un « s »).

### 8. Intégrité interception (`ensureHotspotInterceptIntegrity`)

Sous-étapes exécutées dans cet ordre :

#### 8a. Bridge firewall (rappel)
Même logique que l’étape 6.

#### 8b. Walled-garden DHCP

```routeros
/ip hotspot walled-garden ip add action=accept protocol=udp dst-port=67 comment="DYRSIA hotspot DHCP"
/ip hotspot walled-garden ip add action=accept protocol=udp dst-port=68 comment="DYRSIA hotspot DHCP"
```

#### 8c. Ancres firewall hotspot
Vérifie que les chaînes dynamiques hotspot existent.

#### 8d. Serveur DHCP

```routeros
/ip dhcp-server add name=dyrsia-hotspot-dhcp interface=bridge-hotspot \
  address-pool=dyrsia-hotspot-pool lease-time=30m disabled=no

/ip dhcp-server network add address=10.0.0.0/24 gateway=10.0.0.1 dns-server=10.0.0.1
```

- Désactive les autres serveurs DHCP actifs sur la même interface
- Aligne `address-pool` du serveur hotspot sur le pool DHCP

#### 8e. Suppression règles bypass
Retire les règles forward et IP bindings qui contournent le hotspot.

### 9. NAT masquerade

```routeros
/ip firewall nat add chain=srcnat action=masquerade \
  out-interface=ether1 src-address=10.0.0.0/24 \
  comment="DYRSIA hotspot masquerade"
```

L’interface WAN est détectée automatiquement (`resolveWanOutInterface`).

### 10. Walled-garden portail captif

Hôtes autorisés (backend + CDN) :

```routeros
/ip hotspot walled-garden ip add action=accept dst-host=wifizones.org comment="DYRSIA hotspot captive"
/ip hotspot walled-garden ip add action=accept dst-host=www.wifizones.org comment="DYRSIA hotspot captive"
/ip hotspot walled-garden ip add action=accept dst-host=cdn.jsdelivr.net comment="DYRSIA hotspot captive"
# + hôte extrait de hotspot_api_url
```

### 11. login.html

- Généré côté PHP depuis le template admin
- Déployé via API (`/file set`) ou fetch HTTP si fichier > 4 Ko
- Chemin routeur : `hotspot/login.html`
- Profil : `html-directory=hotspot`

**Mode léger** (hotspot déjà installé) : envoi login.html uniquement + éventuel proxy NAT API captif.

**Mode Send complet** : tout le déploiement ci-dessus + login.html.

### 12. Sync forfaits

Après déploiement, les profils utilisateurs hotspot (`/ip hotspot user profile`) sont synchronisés depuis les forfaits DYRSIA.

---

# PPPoE

## Vue d’ensemble

L’assistant PPPoE Setup configure un serveur PPPoE complet :

- Bridge dédié + ports LAN
- Passerelle et pool IP
- Profils PPP (`default` + `EXPIRE`)
- Scripts on-up/on-down pour clients expirés
- Serveur PPPoE
- DNS routeur
- NAT masquerade
- Firewall anti-contournement bridge
- Sync forfaits + secrets clients

Fonction centrale : `Mikrotik::applyPppoeSetupFromConfig()`

## Valeurs par défaut (`pppoeSetupDefaults()`)

| Clé | Défaut |
|-----|--------|
| `pppoe_setup_bridge_name` | `bridge-pppoe` |
| `pppoe_setup_bridge_ports` | `ether2,ether3,ether4,ether5` |
| `pppoe_setup_gateway` | `10.10.10.1/24` |
| `pppoe_setup_pool_name` | `pppoe-pool` |
| `pppoe_setup_pool_range` | `10.10.10.2-10.10.10.254` |
| `pppoe_setup_profile_default` | `default` |
| `pppoe_setup_profile_expire` | `EXPIRE` |
| `pppoe_setup_dns_servers` | `8.8.8.8,1.1.1.1` |
| `pppoe_setup_service_name` | `internet` |
| `pppoe_setup_server_interface` | `bridge-pppoe` |
| `pppoe_setup_one_session` | `1` |
| `pppoe_setup_max_mru` / `max_mtu` | `1480` |
| `pppoe_setup_expired_list` | `pppoe-expired` |
| `pppoe_setup_nat_interface` | `ether1` |
| `pppoe_setup_nat_masquerade` | `1` |

## Ordre d’exécution (Envoyer vers MikroTik)

```
1.  Bridge PPPoE (+ ports)
2.  Adresse passerelle sur le bridge
3.  Pool IP
4.  Profils PPP (default + EXPIRE)
5.  DNS routeur
6.  Address-list pppoe-expired
7.  Serveur PPPoE
8.  NAT masquerade
9.  Firewall anti-contournement bridge
─── post-déploiement ───
10. syncPppoePlans()      → profils rate-limit par forfait
11. syncPppoeSecrets()    → /ppp secret pour chaque client
12. ensurePppoeExpiredCaptive() → walled-garden portail expirés
13. syncExpiredPppoeSuspensions() → bascule clients expirés vers EXPIRE
```

## Commandes MikroTik envoyées

### 1. Bridge

```routeros
/interface bridge add name=bridge-pppoe comment="DYRSIA PPPoE LAN"
/interface bridge port add bridge=bridge-pppoe interface=ether2
/interface bridge port add bridge=bridge-pppoe interface=ether3
/interface bridge port add bridge=bridge-pppoe interface=ether4
/interface bridge port add bridge=bridge-pppoe interface=ether5
```

Le bridge n’est pas recréé s’il existe ; seuls les ports manquants sont ajoutés.

### 2. Passerelle

```routeros
/ip address add address=10.10.10.1/24 interface=bridge-pppoe
```

### 3. Pool IP

```routeros
/ip pool add name=pppoe-pool ranges=10.10.10.2-10.10.10.254
```

### 4. Profils PPP

**Profil default :**

```routeros
/ppp profile set [find name=default] \
  local-address=10.10.10.1 \
  remote-address=pppoe-pool \
  dns-server=8.8.8.8,1.1.1.1
```

**Profil EXPIRE (clients expirés) :**

```routeros
/ppp profile add name=EXPIRE \
  local-address=10.10.10.1 \
  remote-address=pppoe-pool \
  rate-limit=512k/512k \
  dns-server=8.8.8.8,1.1.1.1 \
  on-up=":if (\$remote-address!=\"\") do={ /ip firewall address-list add list=\"pppoe-expired\" address=\$remote-address comment=\$user }" \
  on-down=":if (\$remote-address!=\"\") do={ /ip firewall address-list remove [find list=\"pppoe-expired\" address=\$remote-address] }"
```

Le rate-limit EXPIRE est résolu depuis les forfaits DYRSIA (`resolvePppoeExpireRateLimit`).

### 5. DNS routeur

```routeros
/ip dns set servers=8.8.8.8,1.1.1.1 allow-remote-requests=yes
```

### 6. Address-list expiration

```routeros
/ip firewall address-list add list=pppoe-expired comment="DYRSIA clients PPPoE expires"
```

Les adresses sont ajoutées/supprimées dynamiquement par les scripts `on-up`/`on-down` du profil EXPIRE.

### 7. Serveur PPPoE

```routeros
/interface pppoe-server server add \
  service-name=internet \
  interface=bridge-pppoe \
  default-profile=default \
  disabled=no \
  one-session-per-host=yes \
  max-mru=1480 \
  max-mtu=1480 \
  comment="DYRSIA PPPoE server"
```

### 8. NAT masquerade

```routeros
/ip firewall nat add chain=srcnat action=masquerade \
  out-interface=ether1 comment="DYRSIA PPPoE NAT"
```

### 9. Firewall anti-contournement

Empêche le trafic IP direct sur le bridge LAN sans session PPPoE :

```routeros
/ip firewall filter add chain=forward in-interface=bridge-pppoe action=drop \
  comment="DYRSIA: block IP bypass PPPoE"
```

### 10. Post-déploiement — Sync forfaits

Pour chaque forfait PPPoE actif, création/mise à jour d’un profil `/ppp profile` avec `rate-limit` et `session-timeout` correspondants.

### 11. Post-déploiement — Sync secrets

```routeros
/ppp secret add name=client1 password=*** profile=forfait-1 service=pppoe
# ou /ppp secret set si le secret existe
```

### 12. Post-déploiement — Portail clients expirés

Firewall + walled-garden pour rediriger les clients de la liste `pppoe-expired` vers le portail DYRSIA.

---

## Comparaison Hotspot vs PPPoE

| | Hotspot | PPPoE |
|---|---------|-------|
| **Accès** | Portail captif (HTTP) | Session PPP (CHAP/PAP) |
| **DHCP** | Oui (`dyrsia-hotspot-dhcp`) | Non |
| **Bridge firewall** | `use-ip-firewall=yes` requis | Non requis |
| **login.html** | Oui | Non |
| **Walled-garden** | Oui (captive + DHCP) | Oui (clients expirés) |
| **RADIUS** | Optionnel | Via sync forfaits |
| **Anti-bypass** | Suppression règles forward bypass | Drop forward sur bridge LAN |
| **Profil expiré** | Déconnexion hotspot | Profil `EXPIRE` + address-list |

---

## Fichiers source

| Rôle | Fichier |
|------|---------|
| Logique MikroTik | `system/autoload/Mikrotik.php` |
| Contrôleur | `system/controllers/settings.php` |
| UI Hotspot | `ui/ui/admin/settings/hotspot.tpl` |
| UI PPPoE | `ui/ui/admin/settings/pppoe-setup.tpl` |
| Wizard JS Hotspot | `ui/ui/scripts/hotspot-wizard.js` |
| JS PPPoE | `ui/ui/scripts/pppoe-setup.js` |
| Devices | `system/devices/MikrotikHotspot.php`, `MikrotikPppoe.php` |

### Fonctions clés Hotspot

- `fetchHotspotSetupSnapshot()` — lecture état routeur
- `applyHotspotSetupFromConfig()` — déploiement complet
- `ensureHotspotBridgeFirewall()` — bridge + hw-offload
- `ensureHotspotInterceptIntegrity()` — DHCP, walled-garden, firewall
- `deployHotspotLoginHtml()` — envoi login.html
- `applyHotspotRadiusSetup()` — RADIUS

### Fonctions clés PPPoE

- `pppoeSetupDefaults()` — valeurs par défaut
- `fetchPppoeSetupSnapshot()` — lecture état routeur
- `applyPppoeSetupFromConfig()` — déploiement complet
- `ensurePppoeBridgeForwardBlock()` — anti-contournement
- `syncPppoePlans()` / `syncPppoeSecrets()` — sync forfaits et clients
- `ensurePppoeExpiredCaptive()` — portail expirés

---

## Dépannage

### Hotspot

| Problème | Cause probable | Solution |
|----------|----------------|----------|
| Pas de redirection captive | `use-ip-firewall=no` | Send complet (étape 6 avant serveur) |
| DHCP ne fonctionne pas | Walled-garden UDP 67/68 absent | Send complet → étape 8b |
| login.html vide | Fichier > 4 Ko sans fetch URL | Corriger `hotspot_api_url` |
| API URL rejetée | localhost ou IP routeur | Mettre l’IP/URL du serveur DYRSIA |
| Routeur fantôme dans la liste | Config orpheline | Ajouter le routeur dans Réseau → Routeurs |

### PPPoE

| Problème | Cause probable | Solution |
|----------|----------------|----------|
| Client obtient IP sans auth | Pas de règle anti-bypass | Re-envoyer → étape 9 |
| Client expiré toujours à plein débit | Profil EXPIRE non appliqué | Vérifier sync suspensions |
| Pas de connexion PPPoE | Bridge/ports incorrects | Vérifier `pppoe_setup_bridge_ports` |

### Connectivité API

```bash
# Depuis le serveur PHP / VPS
nc -zv 10.0.0.3 8728
```

Si timeout depuis le Mac mais OK depuis le VPS : vérifier le forwarding WireGuard (`wg0 ↔ wg0`).

---

## Références MikroTik

- [Manual:Hotspot](https://wiki.mikrotik.com/wiki/Manual:Hotspot)
- [Manual:PPPoE Server](https://wiki.mikrotik.com/wiki/Manual:PPPoE_Server)
- [Bridge use-ip-firewall](https://help.mikrotik.com/docs/display/ROS/Bridging+and+Switching)
