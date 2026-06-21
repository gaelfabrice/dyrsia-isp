# WireGuard — accès MikroTik depuis DYRSIA (cloud)

Les routeurs en IP privée (`10.0.0.1`, `10.0.0.3`, …) ne sont **pas** joignables depuis Render sans tunnel.

## Architecture cible

```
[Render DYRSIA] ──WireGuard──► [VPS ou MikroTik] ──LAN──► [Routeur hotspot 10.0.0.x:8728]
```

## Étapes

1. Installer WireGuard sur un VPS proche du site ou directement sur le routeur principal.
2. Créer une paire de clés pour le serveur PHP (peer « dyrsia-cloud »).
3. Autoriser le trafic vers `10.0.0.0/24` et le port API `8728/tcp`.
4. Sur Render : installer le client WireGuard dans l’image Docker ou utiliser un sidecar VPS qui proxy l’API (alternative).

## Test après connexion VPN

```bash
php scripts/mtik_diag.php
php scripts/check_login.php
```

## Fichiers liés

- `scripts/apache-wifizone-wireguard.conf.example` — virtual host si Apache local
- `system/autoload/Mikrotik.php` — connexion API RouterOS

## Sécurité

- Limiter les peers WireGuard par clé publique
- Ne pas exposer le port Winbox/API sur Internet sans filtrage IP
- Utiliser un utilisateur API MikroTik dédié (pas `admin` complet)
