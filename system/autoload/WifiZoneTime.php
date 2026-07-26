<?php

/**
 * Fuseau horaire métier : pays / tenant sélectionné à la création de l'instance.
 */
class WifiZoneTime
{
    public static function bootstrapFromPrimaryTenant(): void
    {
        if (!class_exists('Tenant')) {
            return;
        }

        Tenant::ensureSchema();
        $tenant = ORM::for_table('tbl_tenants')
            ->where('status', 'active')
            ->order_by_asc('id')
            ->find_one();
        if (!$tenant || empty($tenant->timezone)) {
            return;
        }

        global $config;
        $timezone = trim((string) $tenant->timezone);
        if ($timezone === '') {
            return;
        }

        $config['timezone'] = $timezone;
        date_default_timezone_set($timezone);
    }

    /**
     * @param array{timezone?:string,admin_id?:int,admin?:array,country_code?:string} $context
     */
    public static function resolveIdentifier(array $context = []): string
    {
        global $config, $admin;

        $explicit = trim((string) ($context['timezone'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        if (class_exists('Tenant')) {
            $current = Tenant::current();
            if (is_array($current) && !empty($current['timezone'])) {
                return trim((string) $current['timezone']);
            }
        }

        $adminId = (int) ($context['admin_id'] ?? 0);
        if ($adminId <= 0 && !empty($context['admin']['id'])) {
            $adminId = (int) $context['admin']['id'];
        }
        if ($adminId <= 0 && is_array($admin) && !empty($admin['id'])) {
            $adminId = (int) $admin['id'];
        }

        if ($adminId > 0) {
            $tenantTz = self::timezoneForAdminId($adminId);
            if ($tenantTz !== '') {
                return $tenantTz;
            }
        }

        $countryCode = trim((string) ($context['country_code'] ?? ''));
        if ($countryCode === '' && is_array($config ?? null)) {
            $countryCode = trim((string) ($config['tenant_country_code'] ?? ''));
        }
        if ($countryCode !== '' && class_exists('MobileMoneyCountry')) {
            $country = MobileMoneyCountry::resolve($countryCode);
            if (is_array($country) && !empty($country['timezone'])) {
                return trim((string) $country['timezone']);
            }
        }

        $tz = trim((string) ($config['timezone'] ?? ''));
        if ($tz !== '') {
            return $tz;
        }

        return date_default_timezone_get() ?: 'UTC';
    }

    public static function timezoneForAdminId(int $adminId): string
    {
        if ($adminId <= 0) {
            return '';
        }

        if (class_exists('Tenant')) {
            Tenant::ensureUserTenantColumn();
            $user = ORM::for_table('tbl_users')->find_one($adminId);
            if ($user && !empty($user->tenant_id)) {
                $tenant = Tenant::findById((int) $user->tenant_id);
                if ($tenant && !empty($tenant->timezone)) {
                    return trim((string) $tenant->timezone);
                }
            }

            $tenant = ORM::for_table('tbl_tenants')->where('admin_user_id', $adminId)->find_one();
            if ($tenant && !empty($tenant->timezone)) {
                return trim((string) $tenant->timezone);
            }
        }

        return '';
    }

    /**
     * @param array{timezone?:string,admin_id?:int,admin?:array,country_code?:string} $context
     */
    public static function zone(array $context = []): DateTimeZone
    {
        $identifier = self::resolveIdentifier($context);
        try {
            return new DateTimeZone($identifier);
        } catch (Exception $e) {
            return new DateTimeZone('UTC');
        }
    }

    /**
     * Applique le fuseau PHP + $config['timezone'] pour la requête en cours.
     *
     * @param array{timezone?:string,admin_id?:int,admin?:array,country_code?:string} $context
     */
    public static function apply(array $context = []): string
    {
        global $config;

        $identifier = self::resolveIdentifier($context);
        date_default_timezone_set($identifier);
        if (is_array($config)) {
            $config['timezone'] = $identifier;
        }

        return $identifier;
    }

    public static function applyForRecharge(string $routerName = '', $plan = null, int $adminId = 0): string
    {
        if ($adminId <= 0 && is_array($plan) && !empty($plan['admin_id'])) {
            $adminId = (int) $plan['admin_id'];
        }
        if ($adminId <= 0 && is_object($plan) && !empty($plan->admin_id)) {
            $adminId = (int) $plan->admin_id;
        }
        if ($adminId <= 0 && $routerName !== '' && class_exists('WifiZoneHotspot')) {
            $adminId = (int) WifiZoneHotspot::routerAdminId($routerName);
        }

        return self::apply(['admin_id' => $adminId]);
    }

    /**
     * @param array{timezone?:string,admin_id?:int,admin?:array,country_code?:string} $context
     */
    public static function now(array $context = []): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::zone($context));
    }

    /**
     * Interprète une date/heure stockées en base comme heure locale instance (pas UTC serveur).
     *
     * @param array{timezone?:string,admin_id?:int,admin?:array,country_code?:string} $context
     */
    public static function parseLocalDateTime(string $date, $time, array $context = []): int
    {
        $date = trim($date);
        if ($date === '' || $date === '0000-00-00') {
            return 0;
        }

        $time = class_exists('Package') ? Package::normalizeRechargeTime($time) : trim((string) $time);
        $zone = self::zone($context);
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time, $zone);
        if ($dt instanceof DateTimeImmutable) {
            return $dt->getTimestamp();
        }

        $previous = date_default_timezone_get();
        self::apply($context);
        $ts = strtotime($date . ' ' . $time);
        date_default_timezone_set($previous);

        return $ts !== false ? $ts : 0;
    }

    /**
     * @param array{timezone?:string,admin_id?:int,admin?:array,country_code?:string} $context
     */
    public static function nowTimestamp(array $context = []): int
    {
        return self::now($context)->getTimestamp();
    }
}
