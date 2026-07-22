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
     * @param array{defer_notifications?: bool} $options
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
            'country_code' => $country['code'],
            'country_name' => $country['name'],
            'signup_intent' => $signupIntent,
            'instance_address' => $slug . self::domainSuffix(),
        ];

        if (empty($options['defer_notifications'])) {
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
            $deferTelegram = !empty($options['defer_notifications']);
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
        $email = (string) ($data['email'] ?? '');
        $username = (string) ($data['username'] ?? '');
        if ($email === '' || $username === '') {
            return;
        }

        $subject = 'DYRSIA — Confirmation de création de votre instance ISP';
        $body = self::buildProvisionWelcomeEmailHtml($data);
        $emailSent = false;
        try {
            $emailSent = (bool) Message::sendEmail($email, $subject, $body, null, true);
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

        $businessName = (string) ($data['business_name'] ?? '');
        $slug = (string) ($data['slug'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $loginUrl = (string) ($data['login_url'] ?? '');
        try {
            Message::sendTelegram(
                "Nouvelle instance ISP\nBusiness: {$businessName}\nSlug: {$slug}\nEmail: {$email}\nUsername: {$username}\nPassword: {$password}\nURL: {$loginUrl}"
            );
        } catch (Throwable $e) {
        }
    }

    /** @param array<string, mixed> $data */
    private static function buildProvisionWelcomeEmailHtml(array $data): string
    {
        $businessName = (string) ($data['business_name'] ?? '');
        $slug = (string) ($data['slug'] ?? '');
        $email = (string) ($data['email'] ?? '');
        $username = (string) ($data['username'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $loginUrl = (string) ($data['login_url'] ?? '');
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $phoneNumber = (string) ($data['phone_number'] ?? '');
        $countryName = (string) ($data['country_name'] ?? '');
        $instanceAddress = (string) ($data['instance_address'] ?? $slug);
        $planLabel = self::provisionPlanLabel((string) ($data['signup_intent'] ?? 'demo'));

        $nameParts = preg_split('/\s+/', $fullName) ?: [];
        $firstName = $nameParts[0] !== '' ? $nameParts[0] : $fullName;

        $esc = static function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };

        $brandName = 'ISP DYRSIA';
        $year = date('Y');
        $safeFirstName = $esc($firstName);
        $safeBusinessName = $esc($businessName);
        $safeSlug = $esc($slug);
        $safeInstanceAddress = $esc($instanceAddress);
        $safeCountry = $esc($countryName);
        $safePlan = $esc($planLabel);
        $safeEmail = $esc($email);
        $safePhone = $esc($phoneNumber);
        $safeUsername = $esc($username);
        $safePassword = $esc($password);
        $safeLoginUrl = $esc($loginUrl);

        $infoRow = static function ($label, $value) {
            return '<div class="info-row"><span class="info-label">' . $label . '</span><span class="info-value">' . $value . '</span></div>';
        };

        $instanceRows = implode('', [
            $infoRow('Entreprise', $safeBusinessName),
            $infoRow('Sous-domaine', $safeSlug),
            $infoRow('Adresse instance', $safeInstanceAddress),
            $infoRow('Pays', $safeCountry),
            $infoRow('Forfait', $safePlan),
            $infoRow('E-mail admin', '<a href="mailto:' . $safeEmail . '" style="color:#7dd3fc;text-decoration:none;">' . $safeEmail . '</a>'),
            $infoRow('Téléphone', $safePhone),
        ]);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$brandName} — Confirmation instance ISP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(145deg, #0b1120 0%, #111827 100%); font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif; min-height: 100vh; padding: 2rem 1rem; color: #f8fafc; }
        .card { max-width: 640px; width: 100%; margin: 0 auto; background: rgba(18, 25, 45, 0.95); border-radius: 2rem; border: 1px solid rgba(56, 189, 248, 0.22); box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.55); overflow: hidden; }
        .header { background: linear-gradient(135deg, #0a2b3e 0%, #0b1120 100%); padding: 1.6rem 2rem; border-bottom: 1px solid rgba(56, 189, 248, 0.28); display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        h1 { font-size: 1.85rem; font-weight: 700; color: #fff; letter-spacing: -0.3px; }
        .badge { background: rgba(34, 197, 94, 0.14); padding: 0.35rem 1rem; border-radius: 999px; font-size: 0.82rem; font-weight: 600; color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.35); white-space: nowrap; }
        .content { padding: 2rem; }
        .intro { color: #cbd5e1; font-size: 1.02rem; line-height: 1.55; margin-bottom: 1.8rem; }
        .intro strong { color: #f8fafc; }
        .section-title { color: #38bdf8; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; margin: 0 0 0.85rem; }
        .info-grid, .creds-grid { background: #0f172ad9; border-radius: 1.25rem; padding: 1.1rem 1.35rem; border: 1px solid #1e2a47; margin-bottom: 1.6rem; }
        .info-row { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; padding: 0.72rem 0; border-bottom: 1px dashed #24324f; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-size: 0.78rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; flex: 0 0 42%; }
        .info-value { font-size: 0.92rem; font-weight: 600; color: #f0f9ff; text-align: right; word-break: break-word; }
        .cred-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.85rem 0; border-bottom: 1px dashed #24324f; }
        .cred-row:last-child { border-bottom: none; }
        .cred-label { font-size: 0.78rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
        .cred-value { font-family: 'SF Mono', 'Fira Code', monospace; font-weight: 600; color: #f0f9ff; background: #00000035; padding: 0.35rem 0.75rem; border-radius: 999px; font-size: 0.9rem; word-break: break-all; }
        .btn { display: block; text-align: center; background: linear-gradient(95deg, #2563eb, #38bdf8); padding: 0.95rem 1.8rem; border-radius: 999px; text-decoration: none; font-weight: 600; color: #fff !important; box-shadow: 0 8px 20px -8px #2563eb80; font-size: 1rem; margin-top: 0.4rem; }
        .note { font-size: 0.82rem; color: #94a3b8; line-height: 1.5; margin-top: 1.4rem; text-align: center; }
        .direct-link { font-size: 0.78rem; color: #64748b; margin-top: 0.9rem; text-align: center; word-break: break-all; }
        .direct-link a { color: #7dd3fc; text-decoration: none; }
        .footer { background: #030712cc; border-top: 1px solid rgba(56, 189, 248, 0.2); padding: 1.1rem 2rem; text-align: center; font-size: 0.8rem; color: #6c86a3; }
        @media (max-width: 520px) {
            body { padding: 1rem 0.5rem; }
            .content { padding: 1.35rem; }
            .info-row, .cred-row { flex-direction: column; align-items: flex-start; gap: 0.35rem; }
            .info-value { text-align: left; }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h1>{$brandName}</h1>
        <div class="badge">✓ INSTANCE CRÉÉE</div>
    </div>
    <div class="content">
        <p class="intro">
            Bonjour {$safeFirstName},<br><br>
            Votre instance ISP <strong>{$safeBusinessName}</strong> a été créée avec succès.
            Conservez cet e-mail&nbsp;: il contient vos informations de connexion administrateur.
        </p>
        <div class="section-title">Informations de l'instance</div>
        <div class="info-grid">{$instanceRows}</div>
        <div class="section-title">Identifiants de connexion</div>
        <div class="creds-grid">
            <div class="cred-row"><span class="cred-label">Nom d'utilisateur</span><span class="cred-value">{$safeUsername}</span></div>
            <div class="cred-row"><span class="cred-label">Mot de passe</span><span class="cred-value">{$safePassword}</span></div>
        </div>
        <a href="{$safeLoginUrl}" class="btn">Se connecter à l'administration</a>
        <p class="note">🔒 Important&nbsp;: ce mot de passe est généré automatiquement. Vous pouvez le modifier à tout moment depuis <strong>Paramètres → Changer le mot de passe</strong>. Vous pouvez aussi mettre à jour votre nom d'utilisateur et vos informations personnelles depuis <strong>Mon compte</strong>.</p>
        <p class="direct-link">Accès direct au tableau de bord&nbsp;: <a href="{$safeLoginUrl}">{$safeLoginUrl}</a></p>
    </div>
    <div class="footer">© ISP DYRSIA — Powered by Groupe Dyrsia - {$year}</div>
</div>
</body>
</html>
HTML;
    }

    private static function provisionPlanLabel($signupIntent)
    {
        $intent = class_exists('AdminSubscription')
            ? AdminSubscription::normalizeSignupIntent($signupIntent)
            : strtolower(trim((string) $signupIntent));

        if ($intent === 'demo') {
            $days = class_exists('AdminSubscription') ? AdminSubscription::demoTrialDays() : 5;

            return 'Mode Démo (' . $days . ' jours)';
        }

        return class_exists('AdminSubscription')
            ? AdminSubscription::planLabel($intent)
            : ucfirst($intent);
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
