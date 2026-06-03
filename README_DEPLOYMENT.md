# Déploiement DYRSIA ISP

Ce guide explique comment publier le projet DYRSIA ISP sur GitHub puis le déployer sur Render.

## Prérequis

- Un compte GitHub
- Un compte Render
- Une base de données MySQL/MariaDB accessible publiquement ou depuis Render
- Git installé sur votre machine
- Les fichiers suivants présents dans le projet :
  - `Dockerfile`
  - `render.yaml`
  - `.env.example`
  - `config.sample.php`

## 1. Préparer le dépôt GitHub

Créer un nouveau dépôt vide sur GitHub, par exemple :

```text
dyrsia-isp
```

Depuis le dossier du projet :

```bash
git init
git add .
git commit -m "Initial DYRSIA ISP deployment"
git branch -M main
git remote add origin https://github.com/VOTRE_USER/dyrsia-isp.git
git push -u origin main
```

Si le dépôt Git est déjà initialisé, ajouter seulement le remote :

```bash
git remote add origin https://github.com/VOTRE_USER/dyrsia-isp.git
git push -u origin main
```

## 2. Fichiers sensibles à ne pas pousser

Les fichiers suivants ne doivent pas être envoyés sur GitHub :

```text
config.php
.env
.env.*
dist/
ui/cache/*.php
ui/compiled/*.php
system/cache/*
system/uploads/*
```

Ils sont ignorés via `.gitignore`.

## 3. Variables d'environnement

Render utilisera les variables suivantes :

```text
APP_URL=https://votre-service.onrender.com
APP_STAGE=Live
DB_HOST=votre-host-mysql
DB_PORT=3306
DB_DATABASE=dyrsia
DB_USERNAME=dyrsia
DB_PASSWORD=votre-mot-de-passe
```

Exemple local basé sur `.env.example` :

```bash
cp .env.example .env
```

Puis modifier `.env` selon votre environnement.

## 4. Déploiement sur Render avec Blueprint

Le projet contient :

```text
render.yaml
```

Render peut utiliser ce fichier pour créer automatiquement le service.

Étapes :

1. Aller sur Render
2. Cliquer sur **New**
3. Choisir **Blueprint**
4. Connecter le dépôt GitHub
5. Sélectionner le dépôt `dyrsia-isp`
6. Vérifier que Render détecte `render.yaml`
7. Ajouter les variables d'environnement demandées
8. Lancer le déploiement

## 5. Déploiement Render comme Web Service Docker

Si vous ne voulez pas utiliser Blueprint :

1. Render > **New** > **Web Service**
2. Connecter le dépôt GitHub
3. Runtime : **Docker**
4. Branch : `main`
5. Dockerfile : `Dockerfile`
6. Health Check Path :

```text
/healthz.php
```

(`/health.php` reste disponible pour un diagnostic complet avec `HEALTH_TOKEN`.)

7. Ajouter les variables d'environnement
8. Cliquer sur **Deploy Web Service**

## 6. Base MySQL

Render ne fournit pas MySQL gratuitement par défaut.

Vous devez utiliser une base externe :

- MySQL cPanel avec accès distant activé
- VPS MySQL
- Service MySQL cloud compatible

Si la base est sur cPanel :

1. Ouvrir cPanel
2. Aller dans **Remote MySQL**
3. Autoriser l'IP sortante de Render si nécessaire
4. Vérifier les identifiants DB

## 7. URL publique

Après déploiement, Render donnera une URL comme :

```text
https://dyrsia-isp.onrender.com
```

Mettre cette URL dans :

```text
APP_URL=https://dyrsia-isp.onrender.com
```

Puis redéployer si nécessaire.

## 8. Vérifier le service

Tester :

```text
https://votre-service.onrender.com/healthz.php
```

Réponse attendue : `{"status":"ok",...}`

Pour un diagnostic base de données et permissions :

```text
https://votre-service.onrender.com/health.php?token=VOTRE_HEALTH_TOKEN
```

## 9. Déploiement cPanel avec archive dist

Pour générer une archive uploadable :

```bash
bash scripts/build-dist.sh
```

L'archive sera générée dans :

```text
dist/dyrsia-server-YYYYMMDDHHMMSS.tar.gz
```

Sur cPanel :

1. Uploader l'archive dans `public_html` ou le dossier du domaine
2. Extraire l'archive
3. Copier `config.sample.php` vers `config.php`
4. Modifier `config.php` avec les informations MySQL cPanel
5. Vérifier les permissions des dossiers cache/uploads

## 10. Permissions recommandées cPanel

Dossiers à rendre inscriptibles par PHP :

```text
system/uploads
system/cache
ui/compiled
ui/cache
```

Permissions usuelles :

```text
755 ou 775 selon l'hébergeur
```

## 11. Dépannage

### Erreur base de données

Vérifier :

- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- accès distant MySQL

### Erreur 500

Vérifier :

- version PHP
- extensions PHP
- logs Render ou cPanel
- droits d'écriture sur cache/uploads

### Page blanche

Passer temporairement en mode debug local uniquement :

```text
APP_STAGE=Dev
```

Ne pas laisser `Dev` en production.
