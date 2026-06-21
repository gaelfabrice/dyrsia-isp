# Sauvegarde cloud — Cloud Upload & Google Drive

Ce guide explique comment envoyer automatiquement les sauvegardes SQL de la base de données vers **Google Drive** depuis DYRSIA / WifiZone.

---

## Accès à la page

1. Connectez-vous en **Admin** ou **SuperAdmin**.
2. Ouvrez le menu **Settings** → **Backup/Restore DB**.
3. URL directe (exemple local) :

```
http://localhost:8080/?_route=plugin/backup_list
```

Descendez jusqu’à la section **Backup Settings**.

---

## Prérequis

| Élément | Détail |
|--------|--------|
| Rôle | Admin ou SuperAdmin |
| Mode | Ne fonctionne pas en mode **Demo** (`APP_STAGE=Demo`) |
| Serveur | Extension PHP **curl** activée |
| Réseau | Accès HTTPS sortant vers `oauth2.googleapis.com` et `www.googleapis.com` |
| Sauvegarde locale | `mysqldump` disponible (pour créer le fichier `.sql`) |

---

## Étape 1 — Activer Cloud Upload

1. Dans **Backup Settings**, activez le switch **Cloud Upload**.
2. Les options **Dropbox**, **Google Drive** et **Telegram** apparaissent.
3. Sans **Cloud Upload**, aucun envoi cloud n’est effectué, même si Google Drive est configuré.

> **Cloud Upload** = interrupteur principal.  
> **Google Drive Upload** = envoi vers Google Drive lors de chaque sauvegarde.

---

## Étape 2 — Créer le projet Google Cloud

1. Allez sur [Google Cloud Console](https://console.cloud.google.com/).
2. Créez un projet (ex. `wifizone-backup`) ou sélectionnez-en un existant.
3. Menu **APIs & Services** → **Library**.
4. Recherchez **Google Drive API** → **Enable**.

---

## Étape 3 — Créer les identifiants OAuth 2.0

1. **APIs & Services** → **Credentials** → **Create Credentials** → **OAuth client ID**.
2. Si demandé, configurez l’**OAuth consent screen** :
   - Type : **External** (ou Internal si Google Workspace)
   - Renseignez nom de l’app, e-mail support
   - Ajoutez votre adresse Gmail dans **Test users** (mode test)
3. Type d’application : **Desktop app** (recommandé) ou **Web application**.
4. Notez :
   - **Client ID** → `123456789-xxxx.apps.googleusercontent.com`
   - **Client Secret** → `GOCSPX-xxxxxxxx`

---

## Étape 4 — Obtenir le Refresh Token

Le refresh token permet au serveur d’envoyer les sauvegardes sans reconnexion manuelle.

### Via OAuth Playground (méthode simple)

1. Ouvrez [Google OAuth 2.0 Playground](https://developers.google.com/oauthplayground/).
2. Cliquez sur l’icône **engrenage** (OAuth 2.0 configuration) :
   - Cochez **Use your own OAuth credentials**
   - Collez votre **Client ID** et **Client Secret**
3. Dans la liste des scopes, saisissez manuellement :

```
https://www.googleapis.com/auth/drive.file
```

4. Cliquez **Authorize APIs** → connectez le compte Google qui recevra les backups.
5. Cliquez **Exchange authorization code for tokens**.
6. Copiez le **Refresh token** (commence souvent par `1//`).

> Scope `drive.file` : l’application ne voit que les fichiers qu’elle crée (recommandé pour la sécurité).

---

## Étape 5 — Dossier Google Drive (optionnel)

Par défaut, les fichiers sont déposés à la **racine de Mon Drive**.

Pour utiliser un dossier dédié :

1. Créez un dossier dans Google Drive (ex. `WifiZone Backups`).
2. Ouvrez-le dans le navigateur. L’URL ressemble à :

```
https://drive.google.com/drive/folders/1abcDEFghiJKLmnOPQrsTUVwxyz
```

3. Copiez l’ID après `/folders/` → `1abcDEFghiJKLmnOPQrsTUVwxyz`

---

## Étape 6 — Configurer DYRSIA

Dans **Backup Settings** :

| Champ | Valeur |
|-------|--------|
| **Cloud Upload** | Activé |
| **Google Drive Upload** | Activé |
| **Google Client ID** | Votre Client ID |
| **Google Client Secret** | Votre Client Secret |
| **Google Refresh Token** | Token obtenu à l’étape 4 |
| **Google Drive Folder ID** | ID du dossier (ou laisser vide) |

Cliquez **Save Changes**.

---

## Étape 7 — Tester

1. En haut de la page, cliquez **Create Backup**.
2. Un fichier `backup_YYYY-MM-DD_HH-MM-SS.sql` est créé localement dans `system/uploads/backup/`.
3. Vérifiez Google Drive (racine ou dossier configuré).
4. En cas d’échec cloud, la sauvegarde locale est quand même créée ; un message d’avertissement s’affiche.

---

## Sauvegarde automatique (cron)

Pour envoyer aussi les backups planifiés vers Google Drive :

1. Activez **Auto Backup** et choisissez la fréquence (Everyday / Everyweek / Everymonth).
2. Gardez **Cloud Upload** et **Google Drive Upload** activés.
3. Assurez-vous que le **cron** de l’application tourne (tâche planifiée serveur).

Les backups automatiques utilisent la même configuration Google Drive.

---

## Combiner plusieurs destinations

Vous pouvez activer en parallèle :

- Google Drive
- Dropbox
- Telegram

Chaque destination activée reçoit le fichier lors d’une sauvegarde.

---

## Dépannage

### « Google Drive credentials cannot be empty »

Remplissez **Client ID**, **Client Secret** et **Refresh Token** avant d’enregistrer.

### « Google Drive token error (HTTP 400) »

- Refresh token invalide ou révoqué → regénérez-le via OAuth Playground.
- Client ID / Secret incorrects.
- Compte Google retiré des **Test users** (app en mode test).

### « Google Drive upload failed (HTTP 403) »

- API Google Drive non activée dans le projet Google Cloud.
- Scope incorrect (utilisez `https://www.googleapis.com/auth/drive.file`).
- Dossier cible inaccessible : vérifiez l’**Folder ID** ou laissez le champ vide.

### « Cloud backup upload failed » mais backup local OK

- Vérifiez curl et la connectivité HTTPS du serveur.
- Consultez les logs applicatifs (`system/uploads/` ou journal admin).

### Rien ne part vers Google Drive

- **Cloud Upload** est-il activé ?
- **Google Drive Upload** est-il activé ?
- Êtes-vous en mode **Demo** ? (upload cloud désactivé)

---

## Sécurité

- Ne partagez jamais le **Client Secret** ni le **Refresh Token**.
- Limitez l’accès admin à la page Backup.
- Préférez un dossier Drive dédié aux sauvegardes.
- En production, restreignez les IP sortantes si possible et surveillez les logs.

---

## Résumé rapide

```
Cloud Upload          → ON
Google Drive Upload   → ON
Client ID             → OAuth Google Cloud
Client Secret         → OAuth Google Cloud
Refresh Token         → OAuth Playground (scope drive.file)
Folder ID             → optionnel
Save Changes          → Create Backup pour tester
```
