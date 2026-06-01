# Déploiement Wifizone

## Objectif

Cette version permet de déployer l'application PHP/MySQL :

- sur GitHub comme dépôt source ;
- sur Render via Docker ;
- sur un VPS via Docker Compose ;
- sous forme d'archive `dist` prête à envoyer sur le VPS.

## 1. Préparer GitHub

```bash
git init
git add .
git commit -m "Prepare server deployment"
git branch -M main
git remote add origin git@github.com:YOUR_USER/YOUR_REPO.git
git push -u origin main
```

Les fichiers sensibles ne doivent pas être commités :

```text
config.php
.env
.env.*
```

## 2. Variables d'environnement

Créer un fichier `.env` depuis l'exemple :

```bash
cp .env.example .env
```

Exemple pour le VPS :

```text
APP_URL=http://146.59.10.164:8000
APP_STAGE=Live
APP_PORT=8000
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wifizones
DB_USERNAME=wifizones
DB_PASSWORD=change-me
```

## 3. Déploiement Render

Render utilise `render.yaml` et le `Dockerfile`.

Dans Render :

1. Créer un nouveau Blueprint ou Web Service Docker.
2. Connecter le dépôt GitHub.
3. Définir les variables :

```text
APP_URL=https://ton-service.onrender.com
APP_STAGE=Live
DB_HOST=host-mysql
DB_PORT=3306
DB_DATABASE=wifizones
DB_USERNAME=wifizones
DB_PASSWORD=mot-de-passe
```

Render ne fournit pas MySQL natif gratuitement comme PostgreSQL. Utiliser une base MySQL externe ou la base MySQL du VPS si elle est exposée de manière sécurisée.

## 4. Déploiement VPS par archive dist

Depuis ton Mac :

```bash
scripts/build-dist.sh
```

Cela crée une archive dans :

```text
dist/wifizone-server-YYYYMMDDHHMMSS.tar.gz
```

Pour envoyer et lancer automatiquement sur ton VPS :

```bash
scripts/deploy-vps.sh root@146.59.10.164 /opt/wifizone
```

Puis sur le VPS, modifier `/opt/wifizone/.env` :

```bash
nano /opt/wifizone/.env
```

Relancer :

```bash
cd /opt/wifizone
docker compose -f docker-compose.server.yml up -d --build
```

## 5. Déploiement VPS depuis GitHub

Sur le VPS :

```bash
git clone https://github.com/YOUR_USER/YOUR_REPO.git /opt/wifizone
cd /opt/wifizone
cp .env.example .env
nano .env
docker compose -f docker-compose.server.yml up -d --build
```

## 6. Configuration Hotspot API URL

Dans l'admin Wifizone :

```text
Settings > Hotspot > Hotspot API URL
```

Mettre l'URL publique joignable par les téléphones captifs :

```text
http://146.59.10.164:8000
```

ou mieux avec domaine HTTPS :

```text
https://portal.ton-domaine.com
```

Puis cliquer sur :

```text
Send to Mikrotik
```

## 7. Walled garden MikroTik

Pour l'IP publique VPS en HTTP port 8000 :

```mikrotik
/ip hotspot walled-garden ip add dst-address=146.59.10.164 protocol=tcp dst-port=8000 action=accept comment="WifiZone API VPS"
```

Tester depuis un téléphone non authentifié :

```text
http://146.59.10.164:8000/index.php?_route=plugin/hotspot_plan
```

Si cette URL répond, le workflow voucher peut appeler l'API et créer automatiquement le compte random.

## 8. Santé du service

URL de healthcheck :

```text
/health.php
```
