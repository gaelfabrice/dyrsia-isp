<?php

/**
 * Multi-tenant ISP instances (slug + isolated admin scope).
 */
class Tenant
{
    /** @var array|null */
    private static $current = null;

    public static function ensureUserTenantColumn()
    {
        self::ensureSchema();
        try {
            $db = ORM::get_db();
            if (!$db) {
                return;
            }
            $cols = $db->query("SHOW COLUMNS FROM tbl_users LIKE 'tenant_id'")->fetchAll(PDO::FETCH_ASSOC);
            if (count($cols) === 0) {
                ORM::raw_execute('ALTER TABLE tbl_users ADD COLUMN tenant_id INT UNSIGNED NULL DEFAULT NULL AFTER id');
                ORM::raw_execute('ALTER TABLE tbl_users ADD KEY idx_tenant_id (tenant_id)');
            }
        } catch (Exception $e) {
            // ignore
        }
        self::backfillUserTenantIds();
    }

    public static function backfillUserTenantIds()
    {
        try {
            $tenants = ORM::for_table('tbl_tenants')->find_many();
            foreach ($tenants as $tenant) {
                $ownerId = (int) $tenant->admin_user_id;
                if ($ownerId < 1) {
                    continue;
                }
                $user = ORM::for_table('tbl_users')->find_one($ownerId);
                if ($user && empty($user->tenant_id)) {
                    $user->tenant_id = (int) $tenant->id;
                    $user->save();
                }
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    public static function userRequiresTenant($userType)
    {
        return $userType !== 'SuperAdmin';
    }

    /**
     * @return array|null
     */
    public static function findTenantForUserId($userId)
    {
        self::ensureUserTenantColumn();
        $userId = (int) $userId;
        if ($userId < 1) {
            return null;
        }
        $user = ORM::for_table('tbl_users')->find_one($userId);
        if ($user && !empty($user->tenant_id)) {
            $tenant = self::findById((int) $user->tenant_id);
            if ($tenant && $tenant->status === 'active') {
                return $tenant->as_array();
            }
        }
        $ownerTenant = ORM::for_table('tbl_tenants')
            ->where('admin_user_id', $userId)
            ->where('status', 'active')
            ->find_one();
        if ($ownerTenant) {
            return $ownerTenant->as_array();
        }
        return null;
    }

    public static function listForSelect()
    {
        self::ensureSchema();
        return ORM::for_table('tbl_tenants')
            ->where('status', 'active')
            ->order_by_asc('business_name')
            ->find_many();
    }

    /**
     * @return array{ok: bool, msg: string, tenant: ?object}
     */
    public static function resolveTenantForNewUser($tenantId, $userType, $actingAdmin)
    {
        if (!self::userRequiresTenant($userType)) {
            return ['ok' => true, 'msg' => '', 'tenant' => null];
        }

        $actingTenant = self::findTenantForUserId((int) $actingAdmin['id']);
        if ($actingAdmin['user_type'] !== 'SuperAdmin' && $actingTenant) {
            $tenant = self::findById((int) $actingTenant['id']);
            if ($tenant) {
                return ['ok' => true, 'msg' => '', 'tenant' => $tenant];
            }
        }

        $tenantRef = is_string($tenantId) ? trim($tenantId) : $tenantId;
        if ($tenantRef === '' || $tenantRef === null || $tenantRef === 0 || $tenantRef === '0') {
            return ['ok' => false, 'msg' => Lang::T('ISP / Business name is required'), 'tenant' => null];
        }

        if (is_numeric($tenantRef) && (int) $tenantRef > 0) {
            $tenant = self::findById((int) $tenantRef);
        } else {
            $tenant = self::findByBusinessName((string) $tenantRef);
        }

        if (!$tenant || $tenant->status !== 'active') {
            return ['ok' => false, 'msg' => Lang::T('Selected ISP does not exist'), 'tenant' => null];
        }
        return ['ok' => true, 'msg' => '', 'tenant' => $tenant];
    }

    public static function moveCustomerExpiryToMonitoring()
    {
        try {
            ORM::raw_execute("UPDATE tbl_widgets SET enabled = 0 WHERE widget = 'customer_expired'");
        } catch (Exception $e) {
            // ignore
        }
    }

    public static function assignUserTenant($user, $tenant)
    {
        self::ensureUserTenantColumn();
        if (!$tenant) {
            $user->tenant_id = null;
            return;
        }
        $user->tenant_id = (int) $tenant->id;
    }

    public static function applyAdminBranding($admin)
    {
        global $ui, $config;
        if (!$admin || empty($ui)) {
            return;
        }
        self::ensureUserTenantColumn();
        if ($admin['user_type'] === 'SuperAdmin') {
            $tenant = self::findTenantForUserId((int) $admin['id']);
            if (!$tenant) {
                $ui->assign('isp_brand_name', '');
                return;
            }
        } else {
            $tenant = self::findTenantForUserId((int) $admin['id']);
        }
        if (!$tenant) {
            $ui->assign('isp_brand_name', '');
            return;
        }
        $tenantObj = self::findById((int) $tenant['id']);
        if ($tenantObj) {
            self::setCurrent($tenantObj);
        }
        $ui->assign('isp_brand_name', $tenant['business_name']);
        $ui->assign('isp_brand_slug', $tenant['slug']);
        $displayConfig = $config;
        $displayConfig['CompanyName'] = $tenant['business_name'];
        $ui->assign('_c', $displayConfig);
    }

    public static function tenantNamesMap()
    {
        self::ensureSchema();
        $map = [];
        foreach (ORM::for_table('tbl_tenants')->find_many() as $t) {
            $map[(int) $t->id] = $t->business_name;
        }
        return $map;
    }

    /** Carte tenant_id => sous-domaine (slug). */
    public static function tenantSlugsMap()
    {
        self::ensureSchema();
        $map = [];
        foreach (ORM::for_table('tbl_tenants')->find_many() as $t) {
            $map[(int) $t->id] = $t->slug;
        }
        return $map;
    }

    public static function ensureSchema()
    {
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS tbl_tenants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(63) NOT NULL,
            business_name VARCHAR(150) NOT NULL,
            admin_user_id INT UNSIGNED NOT NULL,
            email VARCHAR(150) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_slug (slug),
            KEY idx_admin_user (admin_user_id),
            KEY idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        self::ensureTenantCountryColumns();
        self::ensureTenantIsolationColumns();
    }

    public static function ensureTenantCountryColumns()
    {
        try {
            $db = ORM::get_db();
            if (!$db) {
                return;
            }
            $columns = [
                'country_code' => "VARCHAR(2) NULL DEFAULT NULL AFTER email",
                'timezone' => "VARCHAR(64) NULL DEFAULT NULL AFTER country_code",
                'mobile_gateway' => "VARCHAR(20) NULL DEFAULT NULL AFTER timezone",
            ];
            foreach ($columns as $name => $definition) {
                $exists = $db->query("SHOW COLUMNS FROM tbl_tenants LIKE " . $db->quote($name))->fetchAll(PDO::FETCH_ASSOC);
                if (empty($exists)) {
                    ORM::raw_execute("ALTER TABLE tbl_tenants ADD COLUMN `$name` $definition");
                }
            }
            ORM::raw_execute(
                "UPDATE tbl_tenants SET country_code = 'GA', timezone = 'Africa/Libreville', mobile_gateway = 'mypvit'
                 WHERE country_code IS NULL OR country_code = ''"
            );
        } catch (Exception $e) {
            // ignore
        }
    }

    /**
     * Applique le fuseau horaire et le profil Mobile Money du tenant courant.
     */
    public static function applyLocaleConfig($tenant = null)
    {
        global $config;
        if ($tenant === null) {
            $tenant = self::current();
        }
        if (!$tenant) {
            unset($config['tenant_mobile_gateway'], $config['tenant_country_code'], $config['tenant_country_name']);
            return;
        }

        $row = is_array($tenant) ? $tenant : $tenant->as_array();
        if (!empty($row['timezone'])) {
            $config['timezone'] = $row['timezone'];
            date_default_timezone_set($row['timezone']);
        }

        $country = MobileMoneyCountry::resolve($row['country_code'] ?? '');
        if (!$country) {
            unset($config['tenant_mobile_gateway'], $config['tenant_country_code'], $config['tenant_country_name']);
            return;
        }

        $config['tenant_country_code'] = $country['code'];
        $config['tenant_country_name'] = $country['name'];
        $config['tenant_mobile_gateway'] = $country['gateway'];
        $config['country_code_phone'] = $country['phone_prefix'];
        if ($country['gateway'] === 'mypvit') {
            $config['mypvit_phone_prefix'] = $country['phone_prefix'];
        }
    }

    public static function updateTenantTimezone($tenantId, $timezone)
    {
        self::ensureSchema();
        $timezone = trim((string) $timezone);
        if ($timezone === '') {
            return false;
        }
        $tenant = self::findById((int) $tenantId);
        if (!$tenant) {
            return false;
        }
        $tenant->timezone = $timezone;
        $tenant->updated_at = date('Y-m-d H:i:s');
        $tenant->save();
        if (self::current() && (int) self::current()['id'] === (int) $tenant->id) {
            self::$current['timezone'] = $timezone;
            self::applyLocaleConfig(self::$current);
        }
        return true;
    }

    public static function ensureTenantIsolationColumns()
    {
        $tables = [
            'tbl_customers' => 'admin_id',
            'tbl_plans' => 'admin_id',
            'tbl_routers' => 'admin_id',
            'tbl_user_recharges' => 'admin_id',
            'tbl_voucher' => 'admin_id',
            'tbl_transactions' => 'admin_id',
        ];
        $db = ORM::get_db();
        foreach ($tables as $table => $column) {
            try {
                $exists = $db->query("SHOW TABLES LIKE " . $db->quote($table))->fetchAll(PDO::FETCH_ASSOC);
                if (empty($exists)) {
                    continue;
                }
                $columns = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetchAll(PDO::FETCH_ASSOC);
                if (empty($columns)) {
                    ORM::raw_execute("ALTER TABLE `$table` ADD COLUMN `$column` INT NULL DEFAULT NULL");
                    ORM::raw_execute("ALTER TABLE `$table` ADD KEY `idx_{$column}` (`$column`)");
                }
            } catch (Exception $e) {
            }
        }
    }

    public static function domainSuffix()
    {
        global $config;
        $suffix = trim($config['tenant_domain_suffix'] ?? '');
        if ($suffix === '') {
            global $ui;
            $host = '';
            if (!empty($ui) && method_exists($ui, 'getTemplateVars')) {
                $vars = $ui->getTemplateVars();
                $host = $vars['_domain'] ?? '';
            }
            if ($host === '' && defined('APP_URL')) {
                $host = str_replace('www.', '', parse_url(APP_URL, PHP_URL_HOST) ?: '');
            }
            $suffix = $host !== '' ? '.' . $host : '.local';
        }
        if ($suffix[0] !== '.') {
            $suffix = '.' . $suffix;
        }
        return $suffix;
    }

    public static function normalizeSlug($slug)
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    public static function isValidSlug($slug)
    {
        return $slug !== '' && strlen($slug) <= 63 && preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $slug);
    }

    public static function findBySlug($slug)
    {
        self::ensureSchema();
        return ORM::for_table('tbl_tenants')
            ->where('slug', self::normalizeSlug($slug))
            ->where('status', 'active')
            ->find_one();
    }

    public static function findByBusinessName($name)
    {
        self::ensureSchema();
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $tenant = ORM::for_table('tbl_tenants')
            ->where_raw('LOWER(business_name) = ?', [strtolower($name)])
            ->where('status', 'active')
            ->find_one();
        if ($tenant) {
            return $tenant;
        }
        return self::findBySlug($name);
    }

    public static function findById($id)
    {
        self::ensureSchema();
        return ORM::for_table('tbl_tenants')->find_one(intval($id));
    }

    public static function dashboardUrl($slug)
    {
        return getUrl('dashboard_tenant=' . self::normalizeSlug($slug));
    }

    public static function setCurrent($tenant)
    {
        if (!$tenant) {
            self::$current = null;
            unset($_SESSION['tenant_id'], $_SESSION['tenant_slug']);
            global $config;
            if (!empty($config['timezone'])) {
                date_default_timezone_set($config['timezone']);
            }
            unset($config['tenant_mobile_gateway'], $config['tenant_country_code'], $config['tenant_country_name']);
            return;
        }
        self::$current = $tenant->as_array();
        $_SESSION['tenant_id'] = (int) $tenant->id;
        $_SESSION['tenant_slug'] = $tenant->slug;
        self::applyLocaleConfig(self::$current);
    }

    public static function current()
    {
        if (self::$current !== null) {
            return self::$current;
        }
        if (!empty($_SESSION['tenant_id'])) {
            $t = self::findById($_SESSION['tenant_id']);
            if ($t) {
                self::$current = $t->as_array();
                return self::$current;
            }
            unset($_SESSION['tenant_id'], $_SESSION['tenant_slug']);
        }
        return null;
    }

    public static function bootstrapBySlug($slug)
    {
        $tenant = self::findBySlug($slug);
        if (!$tenant) {
            return false;
        }
        self::setCurrent($tenant);
        return true;
    }

    public static function restoreFromSession()
    {
        if (!empty($_SESSION['tenant_id'])) {
            $t = self::findById($_SESSION['tenant_id']);
            if ($t && $t->status === 'active') {
                self::$current = $t->as_array();
                return true;
            }
            self::setCurrent(null);
        }
        return false;
    }

    /**
     * @param array{skip_notifications?: bool} $options
     * @return array{tenant: object, admin: object, password: string, notification?: array}
     */
    public static function provision($businessName, $slug, $email, $signupIntent = 'demo', $countryCode = '', array $options = [])
    {
        self::ensureSchema();
        global $config;

        $businessName = trim($businessName);
        $slug = self::normalizeSlug($slug);
        $email = trim(strtolower($email));
        $fullName = trim((string) ($options['full_name'] ?? ''));
        $phoneNumber = trim((string) ($options['phone_number'] ?? ''));

        $countryCheck = MobileMoneyCountry::validateForProvision($countryCode);
        if (!$countryCheck['ok']) {
            throw new InvalidArgumentException($countryCheck['message']);
        }
        $country = $countryCheck['country'];

        if ($fullName === '' || strlen($fullName) < 2) {
            throw new InvalidArgumentException(Lang::T('Full name is required'));
        }
        if ($phoneNumber === '') {
            throw new InvalidArgumentException(Lang::T('Phone number is required'));
        }
        if ($businessName === '' || strlen($businessName) < 2) {
            throw new InvalidArgumentException(Lang::T('ISP / Business name is required'));
        }
        if (!self::isValidSlug($slug)) {
            throw new InvalidArgumentException(Lang::T('Subdomain must be 2-63 characters (letters, numbers, hyphens)'));
        }
        if (!Validator::Email($email)) {
            throw new InvalidArgumentException(Lang::T('Valid admin email is required'));
        }

        $existingTenant = self::findBySlug($slug);
        if ($existingTenant) {
            $existingAdmin = ORM::for_table('tbl_users')->find_one((int) $existingTenant->admin_user_id);
            if (!$existingAdmin) {
                self::deleteInstanceBySlug($slug);
                $existingTenant = null;
            }
        }
        if ($existingTenant) {
            throw new RuntimeException(Lang::T('This subdomain is already taken'));
        }

        $emailAdmin = ORM::for_table('tbl_users')->where('email', $email)->find_one();
        if ($emailAdmin) {
            throw new RuntimeException(Lang::T('This email is already registered'));
        }

        $username = $slug;
        if (ORM::for_table('tbl_users')->where('username', $username)->find_one()) {
            throw new RuntimeException(Lang::T('This subdomain username is already registered'));
        }

        $password = self::generatePassword();
        $passwordHash = Password::_crypt($password);
        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set($country['timezone']);
        $now = date('Y-m-d H:i:s');
        date_default_timezone_set($previousTimezone);

        $admin = ORM::for_table('tbl_users')->create();
        $admin->username = $username;
        $admin->fullname = $fullName;
        $admin->password = $passwordHash;
        $admin->user_type = 'Admin';
        $admin->phone = $phoneNumber;
        $admin->email = $email;
        $admin->city = '';
        $admin->subdistrict = '';
        $admin->ward = '';
        $admin->status = 'Active';
        $admin->creationdate = $now;
        $admin->save();
        $signupIntent = AdminSubscription::normalizeSignupIntent($signupIntent);
        AdminSubscription::ensureTrial((int) $admin->id(), $signupIntent);

        self::ensureAdminWallet((int) $admin->id());

        if (class_exists('Referral')) {
            $referralCode = trim((string) ($options['referral_code'] ?? $_SESSION['referral_code'] ?? ''));
            if ($referralCode !== '') {
                try {
                    Referral::registerReferee((int) $admin->id(), $referralCode);
                } catch (Throwable $e) {
                    _log('Referral registerReferee failed: ' . $e->getMessage());
                }
            }
        }

        $tenant = ORM::for_table('tbl_tenants')->create();
        $tenant->slug = $slug;
        $tenant->business_name = $businessName;
        $tenant->admin_user_id = (int) $admin->id();
        $tenant->email = $email;
        $tenant->country_code = $country['code'];
        $tenant->timezone = $country['timezone'];
        $tenant->mobile_gateway = $country['gateway'];
        $tenant->status = 'active';
        $tenant->created_at = $now;
        $tenant->updated_at = $now;
        $tenant->save();

        self::ensureUserTenantColumn();
        self::assignUserTenant($admin, $tenant);
        $admin->save();

        $loginUrl = self::dashboardUrl($slug);
        $notification = [
            'business_name' => $businessName,
            'full_name' => $fullName,
            'phone_number' => $phoneNumber,
            'slug' => $slug,
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'login_url' => $loginUrl,
        ];

        if (empty($options['skip_notifications'])) {
            self::sendProvisionWelcomeNotifications($notification);
        }
        if (class_exists('SuperAdminNotifications')) {
            $alertPayload = [
                'full_name' => $fullName,
                'email' => $email,
                'phone_number' => $phoneNumber,
                'business_name' => $businessName,
                'country_code' => $country['code'],
                'slug' => $slug,
            ];
            $tenantId = (int) $tenant->id();
            $deferTelegram = !empty($options['skip_notifications']);
            SuperAdminNotifications::notifyInstanceCreated($tenantId, $alertPayload, $deferTelegram);
        }

        return [
            'tenant' => $tenant,
            'admin' => $admin,
            'password' => $password,
            'notification' => $notification,
        ];
    }

    /** Email + Telegram après création d'instance (peut être appelé en différé). */
    public static function sendProvisionWelcomeNotifications(array $data): void
    {
        $businessName = (string) ($data['business_name'] ?? '');
        $slug = (string) ($data['slug'] ?? '');
        $email = (string) ($data['email'] ?? '');
        $username = (string) ($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $loginUrl = (string) ($data['login_url'] ?? '');
        if ($email === '' || $username === '') {
            return;
        }

        $brandName = 'ISP DYRSIA';
        $safeBusinessName = htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8');
        $safeSlug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $year = date('Y');
        $body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$brandName} — Instance prête</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(145deg, #0b1120 0%, #111827 100%); font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif; min-height: 100vh; padding: 2rem; color: #f8fafc; }
        .card { max-width: 620px; width: 100%; margin: 0 auto; background: rgba(18, 25, 45, 0.92); border-radius: 2.5rem; border: 1px solid rgba(56, 189, 248, 0.2); box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.5); overflow: hidden; }
        .header-glow { background: linear-gradient(135deg, #0a2b3e 0%, #0b1120 100%); padding: 1.8rem 2rem; border-bottom: 1px solid rgba(56, 189, 248, 0.3); }
        .logo-badge { display: flex; align-items: center; gap: 0.75rem; justify-content: space-between; flex-wrap: wrap; }
        h1 { font-size: 1.9rem; font-weight: 700; color: #ffffff; letter-spacing: -0.3px; }
        .ready-badge { background: rgba(34, 197, 94, 0.12); padding: 0.3rem 1rem; border-radius: 100px; font-size: 0.85rem; font-weight: 500; color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
        .content { padding: 2rem 2rem 1.8rem; }
        .welcome-text { color: #cbd5e6; font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.4; border-left: 3px solid #38bdf8; padding-left: 1rem; }
        .creds-grid { background: #0f172ad9; border-radius: 1.5rem; padding: 1.5rem; margin: 1.5rem 0; border: 1px solid #1e2a47; }
        .cred-row { display: flex; justify-content: space-between; align-items: baseline; padding: 0.75rem 0; border-bottom: 1px dashed #1e2a4a; gap: 1rem; }
        .cred-row:last-child { border-bottom: none; }
        .cred-label { font-weight: 500; color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .cred-value { font-family: 'SF Mono', 'Fira Code', monospace; font-weight: 600; color: #f0f9ff; background: #00000030; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.9rem; word-break: break-all; text-align: right; }
        .btn-dashboard { display: block; text-align: center; background: linear-gradient(95deg, #2563eb, #38bdf8); padding: 0.9rem 1.8rem; border-radius: 60px; text-decoration: none; font-weight: 600; color: white; box-shadow: 0 8px 20px -8px #2563eb80; font-size: 1rem; }
        .secure-note { font-size: 0.75rem; text-align: center; color: #5f7f9e; margin-top: 1.5rem; }
        .footer { background: #030712cc; border-top: 1px solid rgba(56, 189, 248, 0.2); padding: 1.2rem 2rem; text-align: center; font-size: 0.8rem; color: #6c86a3; font-weight: 450; letter-spacing: 0.3px; }
        @media (max-width: 500px) { body { padding: 1rem; } .creds-grid { padding: 1rem; } .cred-row { flex-direction: column; gap: 0.4rem; } .cred-value { text-align: left; } }
    </style>
</head>
<body>
<div class="card">
    <div class="header-glow">
        <div class="logo-badge">
            <h1>{$brandName}</h1>
            <div class="ready-badge">✓ INSTANCE ACTIVE</div>
        </div>
    </div>
    <div class="content">
        <div class="welcome-text">
            ✦ Votre environnement ISP a été créé avec succès.<br>
            Interface ultra-rapide, prête pour la production.
        </div>
        <div class="creds-grid">
            <div class="cred-row"><span class="cred-label">🏢 Business</span><span class="cred-value">{$safeBusinessName}</span></div>
            <div class="cred-row"><span class="cred-label">🌐 Subdomain</span><span class="cred-value">{$safeSlug}</span></div>
            <div class="cred-row"><span class="cred-label">👤 Username</span><span class="cred-value">{$safeUsername}</span></div>
            <div class="cred-row"><span class="cred-label">🔐 Password</span><span class="cred-value">{$safePassword}</span></div>
        </div>
        <a href="{$safeLoginUrl}" class="btn-dashboard">🚀 Accéder au dashboard administrateur</a>
        <div class="secure-note">🔒 Identifiants sécurisés — copiez et conservez ce mot de passe</div>
    </div>
    <div class="footer">© ISP DYRSIA — Powered by Groupe Dyrsia - {$year}</div>
</div>
</body>
</html>
HTML;
        $emailSent = false;
        try {
            $emailSent = (bool) Message::sendEmail($email, 'ISP DYRSIA — Instance prête', $body, null, true);
        } catch (Exception $e) {
            if (function_exists('_log')) {
                _log('Tenant provisioning email failed: ' . $e->getMessage());
            }
        } catch (Throwable $e) {
            if (function_exists('_log')) {
                _log('Tenant provisioning email failed: ' . $e->getMessage());
            }
        }
        if (!$emailSent && function_exists('_log')) {
            _log('Tenant provisioning: email non envoyé à ' . $email . ' (vérifiez SMTP ou mail() sur le serveur)');
        }
        try {
            Message::sendTelegram(
                "Nouvelle instance ISP\nBusiness: {$businessName}\nSlug: {$slug}\nEmail: {$email}\nUsername: {$username}\nPassword: {$password}\nURL: {$loginUrl}"
            );
        } catch (Throwable $e) {
        }
    }

    public static function loginAdmin($adminId)
    {
        $_SESSION['aid'] = (int) $adminId;
        $user = ORM::for_table('tbl_users')->find_one((int) $adminId);
        if ($user) {
            $_SESSION['user_type'] = $user->user_type;
            $user->last_login = date('Y-m-d H:i:s');
            $user->save();
            Admin::setCookie((int) $adminId);
        }
    }

    public static function validateAdminTenant($admin)
    {
        $tenant = self::current();
        if (!$tenant || !$admin) {
            return true;
        }
        if ($admin['user_type'] === 'SuperAdmin') {
            return true;
        }
        $userTenant = self::findTenantForUserId((int) $admin['id']);
        if ($userTenant && (int) $userTenant['id'] === (int) $tenant['id']) {
            return true;
        }
        if ((int) $admin['id'] === (int) $tenant['admin_user_id']) {
            return true;
        }
        Admin::removeCookie();
        unset($_SESSION['aid'], $_SESSION['user_type']);
        r2(getUrl('admin') . '&tenant=' . urlencode($tenant['slug']), 'e', Lang::T('Please sign in with your tenant administrator account'));
        return false;
    }

    public static function deleteInstanceForAdmin($adminId)
    {
        self::ensureSchema();
        self::ensureUserTenantColumn();
        $adminId = (int) $adminId;
        if ($adminId < 1) {
            return false;
        }
        $tenant = ORM::for_table('tbl_tenants')->where('admin_user_id', $adminId)->find_one();
        $tenantId = $tenant ? (int) $tenant->id : 0;
        return self::deleteInstanceRecords($adminId, $tenantId);
    }

    public static function deleteInstanceBySlug($slug)
    {
        self::ensureSchema();
        self::ensureUserTenantColumn();
        $tenant = ORM::for_table('tbl_tenants')->where('slug', self::normalizeSlug($slug))->find_one();
        if (!$tenant) {
            return false;
        }
        return self::deleteInstanceRecords((int) $tenant->admin_user_id, (int) $tenant->id);
    }

    private static function deleteInstanceRecords($adminId, $tenantId)
    {
        $adminId = (int) $adminId;
        $tenantId = (int) $tenantId;
        try {
            if ($tenantId > 0) {
                ORM::raw_execute('DELETE FROM tbl_users WHERE tenant_id = ? AND id <> ?', [$tenantId, $adminId]);
            }
            foreach ([
                ['admin_subscriptions', 'admin_id'],
                ['admin_subscription_invoices', 'admin_id'],
                ['admin_subscription_payments', 'admin_id'],
                ['admin_wallet', 'admin_id'],
                ['admin_wallet_logs', 'admin_id'],
                ['tbl_routers', 'admin_id'],
                ['tbl_customers', 'admin_id'],
                ['tbl_plans', 'admin_id'],
                ['tbl_user_recharges', 'admin_id'],
                ['tbl_voucher', 'admin_id'],
                ['tbl_transactions', 'admin_id'],
                ['api_data_usage', 'admin_id']
            ] as $table) {
                try {
                    ORM::raw_execute('DELETE FROM ' . $table[0] . ' WHERE ' . $table[1] . ' = ?', [$adminId]);
                } catch (Exception $e) {
                }
            }
            if ($tenantId > 0) {
                ORM::raw_execute('DELETE FROM tbl_tenants WHERE id = ?', [$tenantId]);
            }
            return true;
        } catch (Exception $e) {
            if (function_exists('_log')) {
                _log('Tenant instance delete failed for admin #' . $adminId . ', tenant #' . $tenantId . ': ' . $e->getMessage());
            }
            throw $e;
        }
    }

    public static function cleanupOrphanTenants()
    {
        self::ensureSchema();
        self::ensureUserTenantColumn();
        $deleted = 0;
        foreach (ORM::for_table('tbl_tenants')->find_many() as $tenant) {
            $admin = ORM::for_table('tbl_users')->find_one((int) $tenant->admin_user_id);
            if (!$admin) {
                self::deleteInstanceRecords((int) $tenant->admin_user_id, (int) $tenant->id);
                $deleted++;
            }
        }
        return $deleted;
    }

    private static function generatePassword()
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 12);
    }

    private static function ensureAdminWallet($adminId)
    {
        $wallet = ORM::for_table('admin_wallet')->where('admin_id', $adminId)->find_one();
        if (!$wallet) {
            try {
                $wallet = ORM::for_table('admin_wallet')->create();
                $wallet->admin_id = $adminId;
                $wallet->balance = 0;
                $wallet->save();
            } catch (Exception $e) {
                // Table may not exist on older installs
            }
        }
    }

}
