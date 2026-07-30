<?php

/**
 * Compte SuperAdmin par défaut et durcissement (plus de login « admin »).
 */
class SuperAdminAccount
{
    public const DEFAULT_USERNAME = 'Fab610';

    /**
     * Mot de passe initial SuperAdmin (à changer après la première connexion).
     * Identifiant : Fab610
     */
    public const DEFAULT_INITIAL_PASSWORD = 'Fab610@Dyrsia26';

    /** Hash SHA1 legacy PHPNuxBill « admin ». */
    private const LEGACY_ADMIN_SHA1 = 'd033e22ae348aeb5660fc2140aec35850c4da997';

    /** @var string[] */
    public const FORBIDDEN_USERNAMES = ['admin', 'administrator', 'root', 'superadmin'];

    public static function normalizeUsername(string $username): string
    {
        return trim($username);
    }

    public static function isForbiddenUsername(string $username): bool
    {
        $u = strtolower(self::normalizeUsername($username));

        return in_array($u, self::FORBIDDEN_USERNAMES, true);
    }

    /** Bloque les tentatives sur les identifiants trop prévisibles (brute force). */
    public static function isBlockedLoginUsername(string $username): bool
    {
        return self::isForbiddenUsername($username);
    }

    public static function validateNewSuperAdminUsername(string $username): ?string
    {
        if (self::isForbiddenUsername($username)) {
            return Lang::T('This username is reserved and cannot be used');
        }
        if (Validator::Length($username, 45, 3) === false) {
            return Lang::T('Username should be between 3 to 45 characters');
        }

        return null;
    }

    public static function defaultInitialPassword(): string
    {
        return self::DEFAULT_INITIAL_PASSWORD;
    }

    public static function hashInitialPassword(): string
    {
        return password_hash(self::DEFAULT_INITIAL_PASSWORD, PASSWORD_BCRYPT);
    }

    /** Mot de passe installateur : défaut si champs vides, sinon saisie utilisateur. */
    public static function resolveInstallPassword(string $password, string $confirm): string
    {
        $password = (string) $password;
        $confirm = (string) $confirm;
        if ($password === '' && $confirm === '') {
            return self::DEFAULT_INITIAL_PASSWORD;
        }

        return $password;
    }

    public static function validateInstallPassword(string $password, string $confirm): ?string
    {
        $password = (string) $password;
        $confirm = (string) $confirm;
        if ($password === '' && $confirm === '') {
            return null;
        }
        if (strlen($password) < 10) {
            return 'Le mot de passe SuperAdmin doit contenir au moins 10 caractères.';
        }
        if ($password !== $confirm) {
            return 'Les mots de passe ne correspondent pas.';
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return 'Le mot de passe SuperAdmin doit contenir au moins une lettre et un chiffre.';
        }
        if (in_array(strtolower($password), ['admin', 'password', '1234567890', 'fab610'], true)) {
            return 'Choisissez un mot de passe plus fort (évitez les mots courants).';
        }

        return null;
    }

    public static function createInstallSuperAdmin(PDO $db, string $password, string $fullname = ''): void
    {
        $username = self::DEFAULT_USERNAME;
        $fullname = trim($fullname) !== '' ? trim($fullname) : 'Super Administrateur';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        foreach (self::FORBIDDEN_USERNAMES as $forbidden) {
            $del = $db->prepare("DELETE FROM tbl_users WHERE LOWER(username) = LOWER(?) AND user_type = 'SuperAdmin'");
            $del->execute([$forbidden]);
        }

        $stmt = $db->prepare("
            INSERT INTO tbl_users (username, fullname, password, user_type, status, last_login, creationdate)
            VALUES (?, ?, ?, 'SuperAdmin', 'Active', NOW(), NOW())
        ");
        $stmt->execute([$username, $fullname, $hash]);
    }

    /**
     * Migration automatique une fois : compte legacy « admin » → Fab610.
     */
    public static function maybeMigrateLegacyAdmin(): void
    {
        try {
            $flag = ORM::for_table('tbl_appconfig')->where('setting', 'superadmin_fab610_migrated')->find_one();
            if ($flag && ($flag->value ?? '') === 'yes') {
                return;
            }

            $legacy = ORM::for_table('tbl_users')
                ->where('username', 'admin')
                ->where('user_type', 'SuperAdmin')
                ->find_one();
            if (!$legacy) {
                self::markMigrated();

                return;
            }

            $fab = ORM::for_table('tbl_users')->where('username', self::DEFAULT_USERNAME)->find_one();
            if ($fab) {
                $legacy->status = 'Inactive';
                $legacy->save();
                _log('Legacy SuperAdmin « admin » désactivé (Fab610 déjà présent).', 'System', (int) $legacy->id);
            } else {
                $legacy->username = self::DEFAULT_USERNAME;
                $legacy->save();
                _log('SuperAdmin renommé admin → ' . self::DEFAULT_USERNAME . ' — changez le mot de passe si encore faible.', 'System', (int) $legacy->id);
            }

            self::markMigrated();
            self::maybeApplyInitialDefaultPassword();
        } catch (Throwable $e) {
            error_log('SuperAdminAccount::maybeMigrateLegacyAdmin: ' . $e->getMessage());
        }
    }

    /**
     * Si le SuperAdmin Fab610 a encore le mot de passe legacy « admin », applique le mot de passe initial documenté.
     */
    public static function maybeApplyInitialDefaultPassword(): void
    {
        try {
            $flag = ORM::for_table('tbl_appconfig')->where('setting', 'superadmin_initial_pwd_v1')->find_one();
            if ($flag && ($flag->value ?? '') === 'yes') {
                return;
            }

            $fab = ORM::for_table('tbl_users')
                ->where('username', self::DEFAULT_USERNAME)
                ->where('user_type', 'SuperAdmin')
                ->find_one();
            if (!$fab) {
                self::markInitialPasswordFlag();

                return;
            }

            $stored = (string) $fab->password;
            $needsDefault = false;
            if ($stored === self::LEGACY_ADMIN_SHA1) {
                $needsDefault = true;
            } elseif (Password::_verify('admin', $stored)) {
                $needsDefault = true;
            }

            if ($needsDefault) {
                $fab->password = self::hashInitialPassword();
                $fab->save();
                _log(
                    'Mot de passe SuperAdmin initial défini pour ' . self::DEFAULT_USERNAME
                    . ' — changez-le depuis Paramètres → Changer mot de passe.',
                    'System',
                    (int) $fab->id
                );
            }

            self::markInitialPasswordFlag();
        } catch (Throwable $e) {
            error_log('SuperAdminAccount::maybeApplyInitialDefaultPassword: ' . $e->getMessage());
        }
    }

    public static function applyDefaultPasswordToFab610(bool $force = false): bool
    {
        $fab = ORM::for_table('tbl_users')
            ->where('username', self::DEFAULT_USERNAME)
            ->where('user_type', 'SuperAdmin')
            ->find_one();
        if (!$fab) {
            return false;
        }
        if (!$force && Password::_verify(self::DEFAULT_INITIAL_PASSWORD, (string) $fab->password)) {
            return true;
        }
        $fab->password = self::hashInitialPassword();
        $fab->save();

        return true;
    }

    private static function markInitialPasswordFlag(): void
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', 'superadmin_initial_pwd_v1')->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = 'superadmin_initial_pwd_v1';
        }
        $row->value = 'yes';
        $row->save();
    }

    private static function markMigrated(): void
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', 'superadmin_fab610_migrated')->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = 'superadmin_fab610_migrated';
        }
        $row->value = 'yes';
        $row->save();
    }
}
