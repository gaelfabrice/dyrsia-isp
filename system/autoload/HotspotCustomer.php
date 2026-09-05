<?php

/**
 * Hotspot: username généré + mot de passe clair fixe 123456.
 * password ET pppoe_password = 123456 en clair (aucun hash).
 * Même valeur poussée sur MikroTik et renvoyée au portail captif.
 */
class HotspotCustomer
{
    /** Dernière erreur push /ip hotspot user (logs + retry). */
    public static $lastMikrotikSyncError = '';

    /** Login hotspot pour la prochaine recharge (client PPPoE : ne pas écraser username / pppoe). */
    public static $pendingRechargeUsername = '';

    public static function defaultPassword()
    {
        return '123456';
    }

    /** Applique 123456 en clair sur password (+ pppoe_password sauf client PPPoE dédié). */
    public static function applyPlainCredentials($customer, $save = true, $touchPppoeSecret = null)
    {
        if (!$customer) {
            return null;
        }
        $plain = self::defaultPassword();
        $customer->password = $plain;
        if ($touchPppoeSecret === null) {
            $touchPppoeSecret = !self::isPppoePrimaryCustomer($customer);
        }
        if ($touchPppoeSecret) {
            $customer->pppoe_password = $plain;
        }
        if ($save) {
            $customer->save();
        }

        return $customer;
    }

    public static function isPppoePrimaryCustomer($customer): bool
    {
        if (!$customer) {
            return false;
        }
        $service = strtoupper(trim((string) (is_array($customer) ? ($customer['service_type'] ?? '') : ($customer->service_type ?? ''))));
        if ($service === 'PPPOE') {
            return true;
        }
        $pppoeUser = trim((string) (is_array($customer) ? ($customer['pppoe_username'] ?? '') : ($customer->pppoe_username ?? '')));

        return $pppoeUser !== '';
    }

    public static function setPendingRechargeUsername(string $username): void
    {
        self::$pendingRechargeUsername = trim($username);
    }

    public static function consumePendingRechargeUsername(): string
    {
        $login = trim(self::$pendingRechargeUsername);
        self::$pendingRechargeUsername = '';

        return $login;
    }

    public static function networkPassword($customer = null)
    {
        return self::defaultPassword();
    }

    public static function loginPassword($customer = null)
    {
        return self::defaultPassword();
    }

    public static function activationNetworkPassword($customer = null)
    {
        return self::defaultPassword();
    }

    public static function clearActivationNetworkPassword()
    {
    }

    public static function isPhoneLikeUsername($username)
    {
        $username = trim((string) $username);
        if ($username === '') {
            return false;
        }
        if (preg_match('/^\d{9}$/', $username)) {
            return true;
        }

        return (bool) preg_match('/^237\d{9}$/', $username);
    }

    public static function usernameNeedsHotspotLoginCode($username)
    {
        return self::isMacUsername($username) || self::isPhoneLikeUsername($username);
    }

    /**
     * Nouveau login aléatoire (conservé en tbl_customers ; les recharges expirées gardent l'ancien username).
     */
    public static function rotateCustomerUsername($customer, $save = true)
    {
        if (!$customer) {
            return null;
        }
        $customer->username = self::generateUsername(10);
        self::applyPlainCredentials($customer, false);
        if ($save) {
            $customer->save();
        }

        return $customer;
    }

    /**
     * Avant activation CamPay : mot de passe 123456 + login aléatoire si pas de forfait actif sur ce routeur.
     */
    public static function prepareForHotspotActivation($customer, $routerName = '', $save = true)
    {
        if (!$customer) {
            return ['customer' => null, 'password' => ''];
        }

        if ($routerName !== '' && function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name((string) $routerName);
        } else {
            $routerName = trim((string) $routerName);
        }

        if (function_exists('hotspot_cleanup_stale_recharge')) {
            hotspot_cleanup_stale_recharge((int) $customer->id, $routerName);
        }

        $activeRecharge = null;
        if (function_exists('hotspot_customer_has_active_recharge')) {
            $activeRecharge = hotspot_customer_has_active_recharge((int) $customer->id, $routerName);
        }

        if ($activeRecharge) {
            $login = trim((string) $activeRecharge->username);
            if ($login !== '' && self::usernameNeedsHotspotLoginCode($login)) {
                if (self::isPppoePrimaryCustomer($customer)) {
                    $login = self::generateUsername(10);
                    $activeRecharge->username = $login;
                    $activeRecharge->save();
                    self::setPendingRechargeUsername($login);
                } else {
                    $customer = self::rotateCustomerUsername($customer, false);
                    $activeRecharge->username = (string) $customer->username;
                    $activeRecharge->save();
                }
            } elseif ($login !== '' && !self::usernameNeedsHotspotLoginCode($login)) {
                if ((string) $customer->username !== $login && !self::isPppoePrimaryCustomer($customer)) {
                    $customer->username = $login;
                }
                self::setPendingRechargeUsername($login);
            }
        } else {
            if (self::isPppoePrimaryCustomer($customer)) {
                $hotspotLogin = self::generateUsername(10);
                self::setPendingRechargeUsername($hotspotLogin);
            } else {
                $customer = self::rotateCustomerUsername($customer, false);
            }
        }

        $customer = self::applyPlainCredentials($customer, $save);

        return [
            'customer' => $customer,
            'password' => self::defaultPassword(),
        ];
    }

    public static function forceMikrotikHotspotPassword($username, $routerName, $password = null)
    {
        global $_app_stage;

        $username = trim((string) $username);
        $routerName = trim((string) $routerName);
        $password = self::defaultPassword();
        if ($username === '' || $routerName === '') {
            return false;
        }
        if ($_app_stage === 'Demo') {
            return true;
        }

        $router = Mikrotik::resolveRouterRecord($routerName, null);
        if (!$router) {
            $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
        }
        if (!$router) {
            return false;
        }

        try {
            $client = Mikrotik::getClient(
                $router['ip_address'],
                $router['username'],
                $router['password'],
                30,
                true,
                true
            );
            if (!$client) {
                return false;
            }

            $printRequest = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/user/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(\PEAR2\Net\RouterOS\Query::where('name', $username));
            $userId = $client->sendSync($printRequest)->getProperty('.id');
            if ($userId === null || $userId === '') {
                _log('[Hotspot] forceMikrotikHotspotPassword: user not on router ' . $routerName . ' name=' . $username);
                return false;
            }

            $setRequest = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/user/set');
            $setRequest->setArgument('numbers', $userId);
            $setRequest->setArgument('password', $password);
            $client->sendSync($setRequest);

            return true;
        } catch (Throwable $e) {
            _log('[Hotspot] forceMikrotikHotspotPassword failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée ou met à jour l'utilisateur hotspot sur le MikroTik (forfait actif en base).
     */
    public static function pushActiveRechargeToMikrotik($customerId, $routerName, $planId = null)
    {
        global $_app_stage;

        self::$lastMikrotikSyncError = '';
        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            self::$lastMikrotikSyncError = 'customer_id invalid';

            return false;
        }
        if ($_app_stage === 'Demo') {
            return true;
        }

        if ($routerName !== '' && function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name((string) $routerName);
        } else {
            $routerName = trim((string) $routerName);
        }

        $customer = ORM::for_table('tbl_customers')->find_one($customerId);
        if (!$customer) {
            self::$lastMikrotikSyncError = 'customer not found';

            return false;
        }
        self::applyPlainCredentials($customer);

        $rechargeQuery = ORM::for_table('tbl_user_recharges')
            ->where('customer_id', $customerId)
            ->where('status', 'on');
        if ($routerName !== '') {
            $rechargeQuery->where('routers', $routerName);
        }
        $recharge = $rechargeQuery->order_by_desc('id')->find_one();
        if (!$recharge || !Package::isRechargeActive($recharge)) {
            self::$lastMikrotikSyncError = 'no active recharge on router ' . $routerName;

            return false;
        }

        $resolvedPlanId = $planId ? (int) $planId : (int) $recharge->plan_id;
        $plan = ORM::for_table('tbl_plans')->find_one($resolvedPlanId);
        if (!$plan) {
            self::$lastMikrotikSyncError = 'plan not found';

            return false;
        }

        $rechargeLogin = trim((string) $recharge->username);
        [$customerRow, $planRow] = Package::deviceSyncRows(
            $plan,
            $routerName !== '' ? $routerName : (string) ($recharge->routers ?? ''),
            $customer,
            $rechargeLogin
        );

        if (class_exists('Mikrotik') && !Mikrotik::hotspotPlanAllowsSharing($planRow)) {
            $deviceMac = self::resolveHotspotDeviceMacForSync(
                (int) $customer->id,
                $routerName !== '' ? $routerName : (string) ($recharge->routers ?? ''),
                $planRow,
                $rechargeLogin
            );
            if ($deviceMac !== '') {
                $customerRow['mac'] = $deviceMac;
            }
        }

        try {
            $routerRecord = Mikrotik::resolveRouterRecord($routerName, null);
            if ($routerRecord) {
                $apiClient = Mikrotik::getClient(
                    $routerRecord['ip_address'],
                    $routerRecord['username'],
                    Mikrotik::routerPassword($routerRecord['password']),
                    30,
                    true,
                    true
                );
                if ($apiClient) {
                    Mikrotik::syncHotspotPlanProfileFromPlanRow($apiClient, $plan);
                    Mikrotik::syncHotspotServerAddressesPerMac($apiClient, $routerName, '1');
                }
            }
        } catch (Throwable $e) {
            _log('[Hotspot] profile sync before push: ' . $e->getMessage());
        }

        try {
            $dvc = Package::getDevice($planRow);
            if (!is_string($dvc) || $dvc === '' || !file_exists($dvc)) {
                self::$lastMikrotikSyncError = 'device driver missing';

                return false;
            }
            require_once $dvc;
            $deviceClass = Package::resolveDeviceClass($plan);
            (new $deviceClass())->add_customer($customerRow, $planRow);

            $rechargeLogin = trim((string) $recharge->username);
            if ($rechargeLogin !== '' && self::hotspotUserExistsOnRouter($rechargeLogin, $routerName)) {
                return true;
            }

            if ($rechargeLogin !== '') {
                self::$lastMikrotikSyncError = 'user absent on MikroTik after add (login=' . $rechargeLogin . ', router=' . $routerName . ')';
                _log('[Hotspot] ' . self::$lastMikrotikSyncError);
                usleep(300000);
                (new $deviceClass())->add_customer($customerRow, $planRow);
                if (self::hotspotUserExistsOnRouter($rechargeLogin, $routerName)) {
                    return true;
                }

                return false;
            }

            return true;
        } catch (Throwable $e) {
            self::$lastMikrotikSyncError = $e->getMessage();
            _log('[Hotspot] pushActiveRechargeToMikrotik failed: ' . $e->getMessage());

            return false;
        }
    }

    public static function pushActiveRechargeToMikrotikWithRetry($customerId, $routerName, $planId = null, $attempts = 3)
    {
        $attempts = max(1, (int) $attempts);
        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0) {
                usleep(400000 * $i);
            }
            if (self::pushActiveRechargeToMikrotik($customerId, $routerName, $planId)) {
                return true;
            }
            if (Package::$lastDeviceSyncError !== '') {
                self::$lastMikrotikSyncError = Package::$lastDeviceSyncError;
            }
        }

        return false;
    }

    public static function hotspotUserExistsOnRouter($username, $routerName)
    {
        global $_app_stage;

        $username = trim((string) $username);
        $routerName = trim((string) $routerName);
        if ($username === '' || $routerName === '') {
            return false;
        }
        if ($_app_stage === 'Demo') {
            return true;
        }
        if (function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name($routerName);
        }

        $router = Mikrotik::resolveRouterRecord($routerName, null);
        if (!$router) {
            return false;
        }

        try {
            $client = Mikrotik::getClient(
                $router['ip_address'],
                $router['username'],
                Mikrotik::routerPassword($router['password']),
                30,
                true,
                true
            );
            if (!$client) {
                return false;
            }
            $printRequest = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/user/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(\PEAR2\Net\RouterOS\Query::where('name', $username));
            $userId = $client->sendSync($printRequest)->getProperty('.id');

            return $userId !== null && $userId !== '';
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function ensureMikrotikHotspotUserForPayment($trx, $maxAttempts = 1)
    {
        if (!$trx) {
            return false;
        }
        $customer = self::resolveCustomerFromPayment($trx);
        if (!$customer) {
            return false;
        }
        $routerName = function_exists('hotspot_normalize_router_name')
            ? hotspot_normalize_router_name((string) ($trx->router_name ?? ''))
            : trim((string) ($trx->router_name ?? ''));
        $planId = (int) ($trx->plan_id ?? 0);

        $login = trim((string) ($trx->voucher_code ?? ''));
        if ($login === '' || $login === '**********') {
            $recharge = function_exists('hotspot_customer_has_active_recharge')
                ? hotspot_customer_has_active_recharge((int) $customer->id, $routerName)
                : null;
            if ($recharge) {
                $login = trim((string) $recharge->username);
            }
        }
        if ($login !== '' && self::hotspotUserExistsOnRouter($login, $routerName)) {
            self::pushActiveRechargeToMikrotikWithRetry(
                (int) $customer->id,
                $routerName,
                $planId > 0 ? $planId : null,
                1
            );

            return true;
        }

        $pushed = self::pushActiveRechargeToMikrotikWithRetry(
            (int) $customer->id,
            $routerName,
            $planId > 0 ? $planId : null,
            max(1, (int) $maxAttempts)
        );
        if (!$pushed) {
            _log('[Hotspot] ensureMikrotikHotspotUserForPayment failed: ' . self::$lastMikrotikSyncError);
        }

        return $pushed;
    }

    /**
     * Après réponse HTTP au portail : retente la sync MikroTik (évite timeout pendant le poll CamPay).
     */
    public static function schedulePostPaymentMikrotikSync($paymentId)
    {
        $paymentId = (int) $paymentId;
        if ($paymentId <= 0) {
            return;
        }

        register_shutdown_function(static function () use ($paymentId) {
            try {
                $trx = ORM::for_table('tbl_hotspot_payments')->find_one($paymentId);
                if (!$trx || (string) ($trx->transaction_status ?? '') !== 'paid') {
                    return;
                }
                if (self::paymentMikrotikSyncSatisfied($trx)) {
                    return;
                }
                if (function_exists('hotspot_pg_campay_ensure_mikrotik_sync')) {
                    hotspot_pg_campay_ensure_mikrotik_sync($trx);
                } else {
                    self::ensureMikrotikHotspotUserForPayment($trx, 1);
                }
            } catch (Throwable $e) {
                _log('[Hotspot] schedulePostPaymentMikrotikSync: ' . $e->getMessage());
            }
        });
    }

    /**
     * Paiement déjà activé (évite sync API redondantes au poll captif).
     * Critère DB uniquement — jamais d’appel MikroTik ici.
     */
    public static function paymentMikrotikSyncSatisfied($trx): bool
    {
        if (!$trx) {
            return false;
        }
        if ((string) ($trx->transaction_status ?? '') !== 'paid') {
            return false;
        }

        $routerName = function_exists('hotspot_normalize_router_name')
            ? hotspot_normalize_router_name((string) ($trx->router_name ?? ''))
            : trim((string) ($trx->router_name ?? ''));
        $customer = self::resolveCustomerFromPayment($trx);
        if ($customer && function_exists('hotspot_customer_has_active_recharge')) {
            $recharge = hotspot_customer_has_active_recharge((int) $customer->id, $routerName);
            if ($recharge) {
                // Un voucher code seul ne suffit pas : il faut une recharge active associee.
                $login = trim((string) ($recharge->username ?? ''));
                return $login !== '' && $login !== '**********';
            }
        }

        return false;
    }

    /** Message affiché si un forfait shared=1 est déjà lié à un autre appareil. */
    public static function voucherAlreadyUsedMessage(): string
    {
        return 'Voucher déjà utilisé';
    }

    /** MAC factice historique de hotspot_validateMacAddress — ne jamais s’en servir comme verrou. */
    public static function isPlaceholderHotspotMac($mac): bool
    {
        if (!class_exists('Mikrotik')) {
            return false;
        }
        $mac = Mikrotik::normalizeHotspotMacAddress($mac);

        return $mac === '' || $mac === '22:12:59:0C:45:58';
    }

    public static function ensureRechargeDeviceMacColumn(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $db = ORM::get_db();
            $cols = [];
            foreach ($db->query('SHOW COLUMNS FROM tbl_user_recharges') as $row) {
                $cols[] = $row['Field'];
            }
            if (!in_array('device_mac', $cols, true)) {
                $db->exec("ALTER TABLE tbl_user_recharges ADD device_mac VARCHAR(32) NULL DEFAULT NULL AFTER username");
            }
        } catch (Throwable $e) {
        }
    }

    /**
     * MAC déjà verrouillée pour un forfait non partagé (DB → paiement → MikroTik).
     */
    public static function resolveLockedDeviceMac($recharge, $planOrm = null, $routerName = ''): string
    {
        if (!class_exists('Mikrotik')) {
            return '';
        }
        self::ensureRechargeDeviceMacColumn();

        if (is_object($recharge) && method_exists($recharge, 'as_array')) {
            $rechargeArr = $recharge->as_array();
        } elseif (is_array($recharge)) {
            $rechargeArr = $recharge;
        } elseif (is_object($recharge)) {
            $rechargeArr = [
                'id' => $recharge->id ?? 0,
                'username' => $recharge->username ?? '',
                'customer_id' => $recharge->customer_id ?? 0,
                'plan_id' => $recharge->plan_id ?? 0,
                'routers' => $recharge->routers ?? '',
                'device_mac' => $recharge->device_mac ?? '',
                'status' => $recharge->status ?? '',
            ];
        } else {
            return '';
        }

        if ($planOrm === null && !empty($rechargeArr['plan_id'])) {
            $planOrm = ORM::for_table('tbl_plans')->find_one((int) $rechargeArr['plan_id']);
        }
        if ($planOrm && Mikrotik::hotspotPlanAllowsSharing($planOrm)) {
            return '';
        }

        $login = trim((string) ($rechargeArr['username'] ?? ''));
        $routerName = trim((string) $routerName);
        if ($routerName === '') {
            $routerName = trim((string) ($rechargeArr['routers'] ?? ''));
        }
        if ($routerName !== '' && function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name($routerName);
        }

        // Priorite au MAC du dernier paiement (appareil qui a vraiment paye).
        $customerId = (int) ($rechargeArr['customer_id'] ?? 0);
        $planId = (int) ($rechargeArr['plan_id'] ?? 0);
        $paymentQuery = ORM::for_table('tbl_hotspot_payments')
            ->where('transaction_status', 'paid')
            ->order_by_desc('id');
        if ($routerName !== '') {
            $paymentQuery->where('router_name', $routerName);
        }
        if ($login !== '') {
            $paymentQuery->where('voucher_code', $login);
        } elseif ($customerId > 0) {
            $customer = ORM::for_table('tbl_customers')->find_one($customerId);
            if ($customer && trim((string) ($customer->phonenumber ?? '')) !== '') {
                $paymentQuery->where('phone_number', (string) $customer->phonenumber);
            }
        }
        if ($planId > 0) {
            $paymentQuery->where('plan_id', $planId);
        }
        $payment = $paymentQuery->find_one();
        $mac = '';
        if ($payment) {
            $mac = Mikrotik::normalizeHotspotMacAddress((string) ($payment->mac_address ?? ''));
        }

        // Fallback sur le MAC deja enregistre pour cette recharge.
        if ($mac === '' || self::isPlaceholderHotspotMac($mac)) {
            $mac = Mikrotik::normalizeHotspotMacAddress((string) ($rechargeArr['device_mac'] ?? ''));
        }

        if (self::isPlaceholderHotspotMac($mac)) {
            $mac = '';
        }

        if ($mac === '' && $login !== '' && $routerName !== '') {
            $mac = Mikrotik::getHotspotUserBoundMac($routerName, $login);
            if (self::isPlaceholderHotspotMac($mac)) {
                $mac = '';
            }
        }

        if ($mac === '' && $login !== '' && $routerName !== '') {
            $mac = self::firstActiveHotspotMac($routerName, $login);
        }

        return $mac;
    }

    public static function firstActiveHotspotMac($routerName, $username): string
    {
        if (!class_exists('Mikrotik')) {
            return '';
        }
        $routerName = trim((string) $routerName);
        $username = trim((string) $username);
        if ($routerName === '' || $username === '') {
            return '';
        }
        if (function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name($routerName);
        }
        $router = ORM::for_table('tbl_routers')->where('name', $routerName)->find_one();
        if (!$router) {
            return '';
        }
        try {
            $client = Mikrotik::getClient(
                $router['ip_address'],
                $router['username'],
                Mikrotik::routerPassword($router['password']),
                15
            );
            if (!$client) {
                return '';
            }
            foreach ($client->sendSync(
                (new \PEAR2\Net\RouterOS\Request('/ip/hotspot/active/print'))
                    ->setArgument('.proplist', 'mac-address')
                    ->setQuery(\PEAR2\Net\RouterOS\Query::where('user', $username))
            ) as $row) {
                if ($row->getType() === 'trap') {
                    continue;
                }
                $mac = Mikrotik::normalizeHotspotMacAddress((string) $row->getProperty('mac-address'));
                if (!self::isPlaceholderHotspotMac($mac)) {
                    return $mac;
                }
            }
        } catch (Throwable $e) {
        }

        return '';
    }

    /**
     * Persiste le verrou MAC (recharge + paiement) pour shared_users = 1.
     */
    public static function lockDeviceMacOnRecharge($recharge, $mac, $planOrm = null): string
    {
        if (!class_exists('Mikrotik')) {
            return '';
        }
        self::ensureRechargeDeviceMacColumn();
        $mac = Mikrotik::normalizeHotspotMacAddress($mac);
        if (self::isPlaceholderHotspotMac($mac) || !$recharge) {
            return '';
        }
        if ($planOrm && Mikrotik::hotspotPlanAllowsSharing($planOrm)) {
            return '';
        }

        $rechargeId = (int) (is_object($recharge) ? ($recharge->id ?? 0) : ($recharge['id'] ?? 0));
        if ($rechargeId > 0) {
            $row = ORM::for_table('tbl_user_recharges')->find_one($rechargeId);
            if ($row) {
                $existing = Mikrotik::normalizeHotspotMacAddress((string) ($row->device_mac ?? ''));
                // Le paiement en cours est la source de verite : on met a jour avec le nouvel appareil.
                if ($existing !== $mac) {
                    $row->device_mac = $mac;
                    $row->save();
                }
                $login = trim((string) ($row->username ?? ''));
                $routerName = trim((string) ($row->routers ?? ''));
                $planId = (int) ($row->plan_id ?? 0);
                if ($login !== '') {
                    $payments = ORM::for_table('tbl_hotspot_payments')
                        ->where('transaction_status', 'paid')
                        ->where('voucher_code', $login)
                        ->order_by_desc('id')
                        ->limit(3)
                        ->find_many();
                    foreach ($payments as $payment) {
                        if ($routerName !== '' && trim((string) ($payment->router_name ?? '')) !== ''
                            && strcasecmp(trim((string) $payment->router_name), $routerName) !== 0) {
                            continue;
                        }
                        if ($planId > 0 && (int) ($payment->plan_id ?? 0) > 0 && (int) $payment->plan_id !== $planId) {
                            continue;
                        }
                        $payMac = Mikrotik::normalizeHotspotMacAddress((string) ($payment->mac_address ?? ''));
                        if (self::isPlaceholderHotspotMac($payMac) || $payMac === '') {
                            $payment->mac_address = $mac;
                            $payment->save();
                        }
                        break;
                    }
                }
            }
        }

        return $mac;
    }

    /**
     * Autorise ou refuse l’accès pour un forfait shared=1 selon la MAC.
     *
     * @return array{ok: bool, message: string, locked_mac: string, action: string}
     */
    public static function assertSingleDeviceMacAccess($recharge, $planOrm, $requestMac, $routerName = '', $bindOnRouter = true): array
    {
        if (!class_exists('Mikrotik')) {
            return ['ok' => true, 'message' => '', 'locked_mac' => '', 'action' => 'skip'];
        }
        if (Mikrotik::hotspotPlanAllowsSharing($planOrm)) {
            return ['ok' => true, 'message' => '', 'locked_mac' => '', 'action' => 'sharing'];
        }

        $requestMac = Mikrotik::normalizeHotspotMacAddress($requestMac);
        if (self::isPlaceholderHotspotMac($requestMac)) {
            $requestMac = '';
        }

        $locked = self::resolveLockedDeviceMac($recharge, $planOrm, $routerName);
        if ($locked !== '' && $requestMac !== '' && $locked !== $requestMac) {
            return [
                'ok' => false,
                'message' => self::voucherAlreadyUsedMessage(),
                'locked_mac' => $locked,
                'action' => 'denied',
            ];
        }
        if ($locked !== '' && $requestMac === '') {
            return [
                'ok' => false,
                'message' => self::voucherAlreadyUsedMessage(),
                'locked_mac' => $locked,
                'action' => 'denied_no_mac',
            ];
        }

        if ($locked === '' && $requestMac === '') {
            return [
                'ok' => false,
                'message' => self::voucherAlreadyUsedMessage(),
                'locked_mac' => '',
                'action' => 'missing_mac',
            ];
        }

        if ($locked === '' && $requestMac !== '') {
            $locked = self::lockDeviceMacOnRecharge($recharge, $requestMac, $planOrm);
        }

        $login = trim((string) (is_object($recharge) ? ($recharge->username ?? '') : ($recharge['username'] ?? '')));
        if ($routerName === '') {
            $routerName = trim((string) (is_object($recharge) ? ($recharge->routers ?? '') : ($recharge['routers'] ?? '')));
        }
        if ($bindOnRouter && $login !== '' && $locked !== '' && $routerName !== '') {
            $bind = Mikrotik::enforceHotspotSingleDeviceMac($routerName, $login, $planOrm, $locked);
            if (!$bind['ok']) {
                $bindMsg = trim((string) ($bind['message'] ?? ''));
                // Refus MAC réel uniquement ; panne API → on s'appuie sur le verrou DB.
                if ($bindMsg === self::voucherAlreadyUsedMessage()
                    || stripos($bindMsg, 'Voucher déjà') !== false
                    || stripos($bindMsg, 'limited to one device') !== false) {
                    return [
                        'ok' => false,
                        'message' => self::voucherAlreadyUsedMessage(),
                        'locked_mac' => $locked,
                        'action' => 'router_denied',
                    ];
                }
            }
        }

        return [
            'ok' => true,
            'message' => '',
            'locked_mac' => $locked,
            'action' => 'allowed',
        ];
    }

    /**
     * MAC à lier sur MikroTik pour forfaits non partagés (shared_users = 1).
     */
    public static function resolveHotspotDeviceMacForSync($customerId, $routerName, $planOrm, $rechargeLogin = ''): string
    {
        if (!class_exists('Mikrotik') || Mikrotik::hotspotPlanAllowsSharing($planOrm)) {
            return '';
        }

        $customerId = (int) $customerId;
        $routerName = trim((string) $routerName);
        if ($routerName !== '' && function_exists('hotspot_normalize_router_name')) {
            $routerName = hotspot_normalize_router_name($routerName);
        }
        $rechargeLogin = trim((string) $rechargeLogin);

        $rechargeQuery = ORM::for_table('tbl_user_recharges')
            ->where('status', 'on')
            ->order_by_desc('id');
        if ($customerId > 0) {
            $rechargeQuery->where('customer_id', $customerId);
        }
        if ($rechargeLogin !== '') {
            $rechargeQuery->where('username', $rechargeLogin);
        }
        if ($routerName !== '') {
            $rechargeQuery->where('routers', $routerName);
        }
        $recharge = $rechargeQuery->find_one();
        if ($recharge) {
            $mac = self::resolveLockedDeviceMac($recharge, $planOrm, $routerName);
            if ($mac !== '') {
                return $mac;
            }
        }

        return '';
    }

    public static function refreshForDeviceSync($customerId)
    {
        $customer = ORM::for_table('tbl_customers')->where('id', (int) $customerId)->find_one();
        if (!$customer) {
            return null;
        }
        if (trim((string) ($customer->service_type ?? '')) === 'Hotspot') {
            return self::applyPlainCredentials($customer);
        }

        return $customer;
    }

    public static function ensureValidNetworkCredentials($customer, $save = true)
    {
        return self::applyPlainCredentials($customer, $save);
    }

    public static function networkPasswordFromPayment($trx = null, $customer = null)
    {
        return self::defaultPassword();
    }

    public static function storePaymentNetworkPassword($trx, $password = null)
    {
        if (!$trx) {
            return;
        }
        $meta = json_decode((string) ($trx->gateway_response ?? '{}'), true);
        if (!is_array($meta)) {
            $meta = [];
        }
        $meta['network_password'] = self::defaultPassword();
        $trx->gateway_response = json_encode($meta, JSON_UNESCAPED_UNICODE);
    }

    public static function resolveCustomerFromPayment($trx)
    {
        if (!$trx) {
            return null;
        }

        $customer = self::findByPhoneForHotspot($trx->phone_number ?? '');
        if ($customer) {
            return $customer;
        }

        $code = trim((string) ($trx->voucher_code ?? ''));
        if ($code !== '' && $code !== '**********' && !self::isMacUsername($code) && !self::isPhoneLikeUsername($code)) {
            $recharge = ORM::for_table('tbl_user_recharges')
                ->where('username', $code)
                ->where('type', 'Hotspot')
                ->where('status', 'on')
                ->order_by_desc('id')
                ->find_one();
            if ($recharge) {
                $owner = ORM::for_table('tbl_customers')->find_one((int) $recharge->customer_id);
                if ($owner) {
                    return $owner;
                }
            }
            $customer = ORM::for_table('tbl_customers')->where('username', $code)->find_one();
            if ($customer && !self::isPppoePrimaryCustomer($customer)) {
                return $customer;
            }
        }

        return null;
    }

    public static function credentialsFromPayment($trx)
    {
        $voucher = trim((string) ($trx->voucher_code ?? ''));
        if ($voucher !== '' && $voucher !== '**********' && !self::isPhoneLikeUsername($voucher) && !self::isMacUsername($voucher)) {
            return [
                'username' => $voucher,
                'password' => self::defaultPassword(),
            ];
        }

        $customer = self::resolveCustomerFromPayment($trx);
        $username = '';
        $routerName = function_exists('hotspot_normalize_router_name')
            ? hotspot_normalize_router_name((string) ($trx->router_name ?? ''))
            : trim((string) ($trx->router_name ?? ''));

        if ($customer) {
            $recharge = null;
            if (function_exists('hotspot_customer_has_active_recharge')) {
                $recharge = hotspot_customer_has_active_recharge((int) $customer->id, $routerName);
            }
            if ($recharge && trim((string) $recharge->username) !== '') {
                $username = trim((string) $recharge->username);
            } else {
                $username = trim((string) $customer->username);
            }
        }

        if ($username !== '' && self::isPhoneLikeUsername($username)) {
            $username = '';
        }

        return [
            'username' => $username,
            'password' => self::defaultPassword(),
        ];
    }

    public static function isMacUsername($username)
    {
        $username = trim((string) $username);
        if ($username === '') {
            return false;
        }

        return (bool) preg_match('/^([0-9A-Fa-f]{2}[:\-]){5}([0-9A-Fa-f]{2})$/', $username);
    }

    public static function generateUsername($length = 10)
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        do {
            $username = '';
            for ($i = 0; $i < $length; $i++) {
                $username .= $alphabet[random_int(0, $max)];
            }
            $exists = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        } while ($exists);

        return $username;
    }

    public static function ensureValidUsername($customer)
    {
        if (!$customer || !self::usernameNeedsHotspotLoginCode($customer->username)) {
            return $customer;
        }

        $oldUsername = (string) $customer->username;
        $customer->username = self::generateUsername(10);
        self::applyPlainCredentials($customer, false);
        $customer->save();

        foreach (ORM::for_table('tbl_user_recharges')->where('customer_id', $customer->id)->where('username', $oldUsername)->where('status', 'on')->find_many() as $recharge) {
            if (Package::isRechargeActive($recharge)) {
                $recharge->username = $customer->username;
                $recharge->save();
            }
        }

        return $customer;
    }

    public static function fixAllMacUsernames()
    {
        $fixed = 0;
        foreach (ORM::for_table('tbl_customers')->where('service_type', 'Hotspot')->find_many() as $customer) {
            if (self::isMacUsername($customer->username)) {
                self::ensureValidUsername($customer);
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Paiement / récupération hotspot : priorité compte Hotspot (évite le client PPPoE même numéro).
     */
    public static function findByPhoneForHotspot($phone)
    {
        $local = self::phoneLocalDigits($phone);
        if ($local === '') {
            return null;
        }

        $formattedPhone = Lang::phoneFormat($local);
        $candidates = [];
        $seen = [];
        foreach ([$formattedPhone, $local, '237' . $local] as $variant) {
            $variant = trim((string) $variant);
            if ($variant === '') {
                continue;
            }
            $row = ORM::for_table('tbl_customers')->where('phonenumber', $variant)->find_one();
            if ($row && !isset($seen[(int) $row->id])) {
                $seen[(int) $row->id] = true;
                $candidates[] = $row;
            }
        }
        foreach (ORM::for_table('tbl_customers')->where_like('phonenumber', '%' . $local)->find_many() as $row) {
            if (!isset($seen[(int) $row->id])) {
                $seen[(int) $row->id] = true;
                $candidates[] = $row;
            }
        }
        $byUsername = ORM::for_table('tbl_customers')->where('username', $local)->find_one();
        if ($byUsername && !isset($seen[(int) $byUsername->id])) {
            $candidates[] = $byUsername;
        }

        foreach ($candidates as $row) {
            if (strcasecmp(trim((string) ($row->service_type ?? '')), 'Hotspot') === 0) {
                return $row;
            }
        }

        foreach ($candidates as $row) {
            if (self::isPppoePrimaryCustomer($row)) {
                continue;
            }
            if (function_exists('hotspot_customer_has_active_recharge')
                && hotspot_customer_has_active_recharge((int) $row->id, '', false)) {
                return $row;
            }
        }

        // Ne jamais réutiliser un compte PPPoE (ex. « fab ») pour un paiement portail captif.
        return null;
    }

    private static function phoneLocalDigits($phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) === 9) {
            return $digits;
        }
        if (strlen($digits) > 9) {
            return substr($digits, -9);
        }

        return '';
    }

    public static function findByPhone($phone)
    {
        $local = self::phoneLocalDigits($phone);
        if ($local === '') {
            return null;
        }

        $formattedPhone = Lang::phoneFormat($local);
        $customer = null;
        if ($formattedPhone !== '') {
            $customer = ORM::for_table('tbl_customers')->where('phonenumber', $formattedPhone)->find_one();
        }
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where('phonenumber', $local)->find_one();
        }
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where('username', $local)->find_one();
        }
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->where_like('phonenumber', '%' . $local)->find_one();
        }

        return $customer ?: null;
    }

    public static function findOrCreate($phone, $fullname = 'Client Hotspot', $address = 'Hotspot')
    {
        $local = self::phoneLocalDigits($phone);
        $formattedPhone = $local !== '' ? Lang::phoneFormat($local) : Lang::phoneFormat($phone);
        $customer = self::findByPhoneForHotspot($phone);
        $plain = self::defaultPassword();

        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->create();
            $customer->username = self::generateUsername(10);
            $customer->password = $plain;
            $customer->pppoe_password = $plain;
            $customer->fullname = $fullname !== '' ? $fullname : 'Client Hotspot';
            $customer->address = $address !== '' ? $address : 'Hotspot';
            $customer->phonenumber = $formattedPhone;
            $customer->email = '';
            $customer->balance = 0;
            $customer->service_type = 'Hotspot';
            $customer->account_type = 'Personal';
            $customer->status = 'Active';
            $customer->created_at = date('Y-m-d H:i:s');
            $customer->save();
        } else {
            if (self::isPppoePrimaryCustomer($customer)) {
                $customer = ORM::for_table('tbl_customers')->create();
                $customer->username = self::generateUsername(10);
                $customer->password = $plain;
                $customer->pppoe_password = $plain;
                $customer->fullname = $fullname !== '' ? $fullname : 'Client Hotspot';
                $customer->address = $address !== '' ? $address : 'Hotspot';
                $customer->phonenumber = $formattedPhone;
                $customer->email = '';
                $customer->balance = 0;
                $customer->service_type = 'Hotspot';
                $customer->account_type = 'Personal';
                $customer->status = 'Active';
                $customer->created_at = date('Y-m-d H:i:s');
                $customer->save();
            } else {
            if ($fullname !== '' && ($customer->fullname === '' || $customer->fullname === 'Hotspot User')) {
                $customer->fullname = $fullname;
            }
            if ($address !== '' && ($customer->address === '' || $customer->address === 'N/A' || $customer->address === 'Hotspot')) {
                $customer->address = $address;
            }
            if ($formattedPhone !== '' && $customer->phonenumber === '') {
                $customer->phonenumber = $formattedPhone;
            }
            if (strcasecmp(trim((string) ($customer->service_type ?? '')), 'Hotspot') !== 0) {
                $customer->service_type = 'Hotspot';
            }
            self::applyPlainCredentials($customer, true);
            }
        }

        $customer = self::ensureValidUsername($customer);

        return $customer;
    }

    public static function loginUsernameFromPayment($trx)
    {
        $credentials = self::credentialsFromPayment($trx);

        return (string) ($credentials['username'] ?? '');
    }
}
