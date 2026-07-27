# Checkpoint — Hotspot stable (2026-07-27)

**Tag Git :** `hotspot-stable-2026-07-27`

## Statut

| Module | Statut |
|--------|--------|
| Hotspot (multi-admin, déploiement, paiement captif) | **Stable — testé** |
| PPPoE Setup | **Non testé** — ne pas considérer stable sur ce checkpoint |

## Contenu de cette version

- Isolation hotspot par admin (config, login.html, forfaits, routeurs)
- Déploiement MikroTik **asynchrone** (Send login.html / Send complet) — n bloque plus les autres admins
- Proxy NAT captif `10.x.x.1:8080` + résolution API dev/prod
- Paiement captif : URLs API, timeout 60 s, CORS
- Confirmation UI après Send complet réussi
- Nettoyage config hotspot lors du remplacement/suppression d'un routeur
- Reconnexion API MikroTik renforcée sur déploiement complet

## Restauration

```bash
# Revenir exactement à ce checkpoint
git fetch origin
git checkout hotspot-stable-2026-07-27

# Ou créer une branche de secours depuis le tag
git checkout -b restore-hotspot-stable hotspot-stable-2026-07-27
```

## Build dist associé

Après checkout du tag ou du commit tagué :

```bash
./scripts/build-dist.sh
```

Le tarball est généré dans `dist/wifizone-server-YYYYMMDDHHMMSS.tar.gz`.

## Notes prod

- Dev local : `./dev-server.sh` → port **8082**, `HOTSPOT_VPN_API_URL=http://10.0.0.2:8082`
- Prod : `https://wifizones.org`
- Après déploiement hotspot : vérifier proxy NAT et `login.html` sur le MikroTik
