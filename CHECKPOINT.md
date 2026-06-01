# Checkpoint — WifiZone / wifizones

**Dernière sauvegarde :** 18 mai 2026  
**Marque :** **wifizones** (remplace PHPNuxBill / NuxBill dans l’UI et `config.php` → base `wifizones`)  
**Emplacement :** `/Users/oz/Downloads/dev/wifizone/Projet`  
**Git :** non initialisé (fichiers sur disque uniquement)

---

## Modifications enregistrées (sessions récentes)

### Localisation française
- `system/lan/french.json` — traduction partielle (relancer `python3 scripts/build_french_locale.py`)
- `scripts/build_french_locale.py` — génération / reprise incrémentale
- `Lang::translateLog()`, `Lang::translateLogRows()`, `Lang::isFrench()` — `system/autoload/Lang.php`
- `system/lan/french_log_phrases.json` — phrases de journaux
- `_log()` traduit si langue = french — `init.php`
- Logs admin + widget : `system/controllers/logs.php`, `system/widgets/activity_log.php`, `ui/ui/widget/activity_log.tpl`

### Paramètres généraux (scroll + tests)
- `ui/ui/admin/settings/app.tpl` — mémorisation panneau ouvert, tests avec ancre `#`
- `system/controllers/settings.php` — redirections avec hash après tests / sauvegarde

### Hotspot System Settings
- `system/plugin/ui/hotspot_config.tpl` — include notifications
- `system/plugin/ui/hotspot_config_notifications.tpl` — Telegram, SMS, WhatsApp, Email, Notification utilisateur (état + tests)
- `system/plugin/ui/hotspot_config_notifications_js.tpl` — tests + scroll après rechargement
- `system/plugin/hotspot.php` — handlers `testTg`, `testSms`, `testWa`, `testEmail`

### Multi-connexion (diagnostic)
- `scripts/diagnostic_multi_login.php` — vérifie `shared_users`, MikroTik, UI plans
- **Conclusion :** support natif via `tbl_plans.shared_users` + profil MikroTik `shared-users`

### Module WifiZone & corrections (sessions antérieures)
- Voir `README-CORRECTIONS.md`, `README-AMELIORATIONS.md`

---

## À faire plus tard (optionnel)

1. Terminer `french.json` : `python3 scripts/build_french_locale.py`
2. Vider cache Smarty si templates inchangés en UI : `ui/compiled/`
3. Remplir **Paramètres → Paramètres généraux** (Telegram, SMS, WA, SMTP) puis tester depuis Hotspot Settings
4. Créer plans Hotspot avec **Utilisateurs partagés** > 1 si multi-appareils souhaité
5. Initialiser Git si versionnement souhaité :
   ```bash
   cd /Users/oz/Downloads/dev/wifizone/Projet
   git init && git add . && git commit -m "WifiZone: corrections, i18n FR, hotspot notifications, diagnostic multi-login"
   ```

---

## Commandes utiles

```bash
cd /Users/oz/Downloads/dev/wifizone/Projet
php scripts/wifizone_selftest.php
php scripts/diagnostic_multi_login.php
python3 scripts/build_french_locale.py
```
