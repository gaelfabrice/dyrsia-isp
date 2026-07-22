# Assistants de Configuration - Hotspot & PPPoE

> **Documentation détaillée (commandes RouterOS, ordre d’exécution, dépannage)** : voir [HOTSPOT_PPPOE_MIKROTIK.md](./HOTSPOT_PPPOE_MIKROTIK.md)

Ce document explique le fonctionnement des assistants de configuration Hotspot et PPPoE dans DYRSIA et les options de configuration envoyées vers MikroTik.

## Table des matières

- [Assistants de Configuration - Hotspot & PPPoE](#assistants-de-configuration---hotspot--pppoe)
  - [Table des matières](#table-des-matières)
  - [Hotspot Setup Assistant](#hotspot-setup-assistant)
    - [Vue d'ensemble](#vue-densemble)
    - [Flux de configuration](#flux-de-configuration)
    - [Options de configuration](#options-de-configuration)
    - [Étapes de l'assistant](#étapes-de-lassistant)
    - [Commandes MikroTik envoyées](#commandes-mikrotik-envoyées)
  - [PPPoE Setup Assistant](#pppoe-setup-assistant)
    - [Vue d'ensemble](#vue-densemble-1)
    - [Flux de configuration](#flux-de-configuration-1)
    - [Options de configuration](#options-de-configuration-1)
    - [Commandes MikroTik envoyées](#commandes-mikrotik-envoyées-1)
  - [Comparaison des assistants](#comparaison-des-assistants)
  - [Fichiers impliqués](#fichiers-impliqués)

---

## Hotspot Setup Assistant

### Vue d'ensemble

L'assistant Hotspot Setup configure automatiquement un serveur hotspot MikroTik avec toutes les composantes nécessaires:
- Bridge firewall settings
- Pool IP
- Profil hotspot
- Réseau hotspot
- Serveur hotspot
- Serveur DHCP
- Règles firewall (filter & NAT)
- Walled garden
- Masquerade NAT

### Flux de configuration

1. **Étape 1 - Sélection du routeur**: Choix du routeur MikroTik cible
2. **Étape 2 - Configuration réseau**: Interface, pool IP, adresse locale
3. **Étape 3 - Paramètres hotspot**: Profil, DNS, timeouts, authentification

### Options de configuration

#### Paramètres réseau
- **hotspot_interface**: Interface du bridge hotspot (ex: bridge-hotspot)
- **hotspot_local_address**: Adresse IP locale (ex: 10.0.0.1/24)
- **hotspot_pool_name**: Nom du pool IP (ex: dyrsia-hotspot-pool)
- **hotspot_address_pool**: Plage d'adresses IP (ex: 10.0.0.1-10.0.0.254)
- **hotspot_masquerade**: Activation du NAT masquerade (1/0)

#### Paramètres hotspot
- **hotspot_name**: Nom du serveur hotspot (ex: dyrsia-hotspot)
- **hotspot_profile**: Profil hotspot (ex: default)
- **hotspot_dns_name**: Nom DNS du hotspot (ex: hotspot.monreseau.net)
- **hotspot_dns_server**: Serveur DNS (ex: 8.8.8.8)
- **hotspot_smtp_server**: Serveur SMTP (ex: 0.0.0.0)

#### Paramètres d'authentification
- **hotspot_login_methods**: Méthodes d'authentification (ex: http-chap,mac-cookie)
- **hotspot_use_radius**: Utilisation de RADIUS (1/0)
- **hotspot_radius_secret**: Secret RADIUS
- **hotspot_cookie_lifetime**: Durée de vie du cookie (ex: 1d 00:00:00)
- **hotspot_idle_timeout**: Timeout d'inactivité (ex: 00:10:00)
- **hotspot_address_per_mac**: Adresses par MAC (ex: 1)

#### Paramètres API
- **hotspot_api_url**: URL de l'API backend (ex: https://wifizones.org)

### Étapes de l'assistant

#### Étape 1: Sélection du routeur
- Choix dans la liste des routeurs configurés
- Récupération de la configuration actuelle via API MikroTik
- Suggestion automatique des paramètres basés sur l'état du routeur

#### Étape 2: Configuration réseau
- Sélection de l'interface hotspot
- Configuration du pool IP
- Définition de l'adresse locale
- Aperçu en temps réel de la configuration

#### Étape 3: Paramètres hotspot
- Configuration du profil hotspot
- Paramètres DNS et SMTP
- Méthodes d'authentification
- Timeouts et limitations
- Aperçu du login page

### Commandes MikroTik envoyées

#### 1. Bridge Firewall Settings (CRITIQUE - AVANT création serveur)
```mikrotik
/interface bridge settings set use-ip-firewall=yes allow-fast-path=no
/interface bridge set [bridge-hotspot] fast-forward=no
/interface bridge port set [find bridge=bridge-hotspot] hw=no
```

#### 2. Pool IP
```mikrotik
/ip pool add name=dyrsia-hotspot-pool ranges=10.0.0.1-10.0.0.254
```

#### 3. Adresse IP sur interface
```mikrotik
/ip address add address=10.0.0.1/24 interface=bridge-hotspot
```

#### 4. Profil Hotspot
```mikrotik
/ip hotspot profile add name=default \
  dns-name=hotspot.monreseau.net \
  smtp-server=0.0.0.0 \
  dns-server=8.8.8.8 \
  login-by=http-chap,mac-cookie \
  http-cookie-lifetime=1d 00:00:00 \
  idle-timeout=00:10:00
```

#### 5. Réseau Hotspot
```mikrotik
/ip hotspot network add address=10.0.0.1/24 dns-server=8.8.8.8
```

#### 6. Serveur Hotspot
```mikrotik
/ip hotspot add name=dyrsia-hotspot \
  interface=bridge-hotspot \
  profile=default \
  address-pool=dyrsia-hotspot-pool \
  addresses-per-mac=1 \
  disabled=no
```

#### 7. Serveur DHCP
```mikrotik
/ip dhcp-server add name=dyrsia-hotspot-dhcp \
  interface=bridge-hotspot \
  address-pool=dyrsia-hotspot-pool \
  disabled=no
```

#### 8. Masquerade NAT
```mikrotik
/ip firewall nat add chain=srcnat action=masquerade \
  out-interface=ether1 \
  comment="DYRSIA hotspot masquerade"
```

#### 9. Walled Garden
```mikrotik
/ip hotspot walled-garden ip add dst-host=wifizones.org action=accept
/ip hotspot walled-garden ip add dst-host=www.wifizones.org action=accept
```

#### 10. Login HTML
```mikrotik
/file set hotspot/login.html contents="..."
```

---

## PPPoE Setup Assistant

### Vue d'ensemble

L'assistant PPPoE Setup configure un serveur PPPoE complet sur MikroTik:
- Bridge PPPoE
- Pool IP
- Profils PPP
- Serveur PPPoE
- Scripts d'expiration
- NAT masquerade
- Serveur DNS

### Flux de configuration

1. **Sélection du routeur**: Choix du routeur MikroTik cible
2. **Configuration bridge**: Nom du bridge et ports
3. **Configuration réseau**: Gateway, pool IP, DNS
4. **Configuration serveur**: Service name, profil, MTU/MRU
5. **Configuration NAT**: Interface et masquerade

### Options de configuration

#### Paramètres bridge
- **pppoe_setup_bridge_name**: Nom du bridge (ex: bridge-pppoe)
- **pppoe_setup_bridge_ports**: Ports du bridge (ex: ether2,ether3,ether4,ether5)

#### Paramètres réseau
- **pppoe_setup_gateway**: Adresse gateway (ex: 10.10.10.1/24)
- **pppoe_setup_pool_name**: Nom du pool (ex: pppoe-pool)
- **pppoe_setup_pool_range**: Plage IP (ex: 10.10.10.2-10.10.10.254)
- **pppoe_setup_dns_servers**: Serveurs DNS (ex: 8.8.8.8,1.1.1.1)
- **pppoe_setup_dns_allow_remote**: Autoriser requêtes DNS distantes (1/0)

#### Paramètres serveur PPPoE
- **pppoe_setup_service_name**: Nom du service (ex: internet)
- **pppoe_setup_server_interface**: Interface du serveur (ex: bridge-pppoe)
- **pppoe_setup_profile_default**: Profil par défaut (ex: default)
- **pppoe_setup_one_session**: Une session par hôte (1/0)
- **pppoe_setup_max_mru**: MRU maximum (ex: 1480)
- **pppoe_setup_max_mtu**: MTU maximum (ex: 1480)

#### Paramètres profil expiration
- **pppoe_setup_profile_expire**: Profil expiration (ex: EXPIRE)
- **pppoe_setup_expire_rate_limit**: Rate limit profil expiration (ex: 512k/512k)
- **pppoe_setup_expired_list**: Liste expiration (ex: pppoe-expired)

#### Paramètres NAT
- **pppoe_setup_nat_interface**: Interface NAT (ex: ether1)
- **pppoe_setup_nat_masquerade**: Activation masquerade (1/0)

### Commandes MikroTik envoyées

#### 1. Bridge PPPoE
```mikrotik
/interface bridge add name=bridge-pppoe comment="DYRSIA PPPoE LAN"
/interface bridge port add bridge=bridge-pppoe interface=ether2
/interface bridge port add bridge=bridge-pppoe interface=ether3
/interface bridge port add bridge=bridge-pppoe interface=ether4
/interface bridge port add bridge=bridge-pppoe interface=ether5
```

#### 2. Adresse IP sur bridge
```mikrotik
/ip address add address=10.10.10.1/24 interface=bridge-pppoe
```

#### 3. Pool IP
```mikrotik
/ip pool add name=pppoe-pool ranges=10.10.10.2-10.10.10.254
```

#### 4. Serveur DNS
```mikrotik
/ip dns set servers=8.8.8.8,1.1.1.1 allow-remote-requests=yes
```

#### 5. Profil PPP par défaut
```mikrotik
/ppp profile set default local-address=10.10.10.1 use-compression=yes
```

#### 6. Profil PPP expiration
```mikrotik
/ppp profile add name=EXPIRE rate-limit=512k/512k
```

#### 7. Scripts d'expiration
```mikrotik
/ppp secret add name=pppoe-expired profile=EXPIRE comment="DYRSIA expired placeholder"
/system script add name="pppoe-expired-on-up" source="..."
/system script add name="pppoe-expired-on-down" source="..."
```

#### 8. Serveur PPPoE
```mikrotik
/interface pppoe-server server add \
  service-name=internet \
  interface=bridge-pppoe \
  default-profile=default \
  one-session-per-host=yes \
  max-mru=1480 \
  max-mtu=1480 \
  disabled=no
```

#### 9. Masquerade NAT
```mikrotik
/ip firewall nat add chain=srcnat action=masquerade out-interface=ether1
```

---

## Comparaison des assistants

| Caractéristique | Hotspot Setup | PPPoE Setup |
|----------------|---------------|-------------|
| **Type d'accès** | Captive portal | PPPoE |
| **Authentification** | HTTP/HTTPS/MAC | PPP (CHAP/PAP) |
| **Bridge requis** | Oui | Oui |
| **Pool IP** | Oui | Oui |
| **Serveur DHCP** | Oui | Non |
| **Règles firewall** | Oui (filter + NAT) | Oui (NAT uniquement) |
| **Walled garden** | Oui | Non |
| **Login page** | Oui (HTML personnalisé) | Non |
| **RADIUS support** | Oui | Oui |
| **Bridge firewall** | use-ip-firewall requis | Non requis |
| **Configuration multi-étapes** | 3 étapes | Formulaire unique |

---

## Fichiers impliqués

### Hotspot Setup
- **Backend**: `system/autoload/Mikrotik.php`
  - `fetchHotspotSetupSnapshot()` - Récupère la configuration actuelle
  - `applyHotspotSetupFromConfig()` - Applique la configuration
  - `ensureHotspotBridgeFirewall()` - Configure le bridge firewall
  - `ensureHotspotServerEntry()` - Crée le serveur hotspot
  - `ensureHotspotDhcpServer()` - Crée le serveur DHCP
  - `ensureHotspotInterceptIntegrity()` - Vérifie l'intégrité

- **Frontend**: `ui/ui/scripts/hotspot-wizard.js`
  - Gestion des 3 étapes de l'assistant
  - Validation des champs
  - Aperçu en temps réel

- **Template**: `ui/ui/admin/settings/hotspot.tpl`
  - Interface utilisateur de l'assistant

- **Controller**: `system/controllers/settings.php`
  - Gestion des actions POST (save, send_mikrotik, send_full)

### PPPoE Setup
- **Backend**: `system/autoload/Mikrotik.php`
  - `pppoeSetupDefaults()` - Valeurs par défaut
  - `fetchPppoeSetupSnapshot()` - Récupère la configuration actuelle
  - `applyPppoeSetupFromConfig()` - Applique la configuration
  - `syncPppoePlans()` - Synchronise les forfaits
  - `syncPppoeSecrets()` - Synchronise les secrets

- **Frontend**: `ui/ui/scripts/pppoe-setup.js`
  - Validation des champs
  - Récupération de la configuration routeur

- **Template**: `ui/ui/admin/settings/pppoe-setup.tpl`
  - Interface utilisateur de l'assistant

- **Controller**: `system/controllers/settings.php`
  - Gestion des actions POST (save, send_mikrotik)

---

## Notes importantes

### Hotspot Setup
1. **Ordre critique**: `use-ip-firewall=yes` doit être activé AVANT la création du serveur hotspot pour que MikroTik génère les règles firewall correctement.
2. **Paramètre correct**: Le paramètre MikroTik est `addresses-per-mac` (avec un 's'), pas `address-per-mac`.
3. **Login HTML**: Le fichier login.html est généré côté PHP et envoyé vers le routeur via API ou FTP.
4. **RADIUS**: Si activé, l'assistant configure automatiquement le client RADIUS et le NAS.

### PPPoE Setup
1. **Bridge existant**: Si le bridge existe déjà, l'assistant ne le recrée pas mais ajoute les ports manquants.
2. **Profils**: Le profil EXPIRE est créé pour gérer les utilisateurs expirés avec un rate limit réduit.
3. **Scripts**: Des scripts on-up et on-down sont créés pour gérer les événements de connexion.
4. **Synchronisation**: Après le déploiement, les forfaits et secrets sont synchronisés automatiquement.

### Dépannage
- **Règles firewall absentes**: Vérifiez que `use-ip-firewall=yes` est activé AVANT la création du serveur hotspot.
- **Address per mac incorrect**: Vérifiez que le paramètre est `addresses-per-mac` (avec un 's').
- **DHCP non fonctionnel**: Vérifiez que le serveur DHCP est créé et activé sur la bonne interface.
- **Walled garden non fonctionnel**: Vérifiez que les règles walled-garden sont créées et que le DNS est configuré.

---

## Références

- **MikroTik RouterOS Documentation**: https://help.mikrotik.com/
- **Hotspot Configuration**: https://wiki.mikrotik.com/wiki/Manual:Hotspot
- **PPPoE Server**: https://wiki.mikrotik.com/wiki/Manual:PPPoE_Server
