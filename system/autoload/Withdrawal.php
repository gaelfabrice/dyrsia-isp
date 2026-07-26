<?php

/**
 * Gestion des retraits (Mode Démo / reversement SuperAdmin).
 */
class Withdrawal
{
    const MIN_AMOUNT = 2000;
    const EXPIRY_HOURS = 24;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REJECTED = 'rejected';

    const OPERATORS = [
        'orange_momo' => 'Orange Money',
        'mtn_momo' => 'MTN MoMo',
    ];

    private static $manualGateways = [
        'Voucher',
        'Customer',
        'Recharge Balance',
        'Recharge Zero',
        'Cash',
        'Balance',
        'Zero',
        'Administrator',
        'MobileAPI',
    ];

    public static function ensureSchema()
    {
        $db = ORM::get_db();
        $queries = [
            "CREATE TABLE IF NOT EXISTS wifizone_withdrawal_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL UNIQUE,
                first_name VARCHAR(64) NOT NULL DEFAULT '',
                last_name VARCHAR(64) NOT NULL DEFAULT '',
                phone VARCHAR(32) NOT NULL DEFAULT '',
                operator VARCHAR(32) NOT NULL DEFAULT 'orange_momo',
                locked TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_withdrawal_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                reference VARCHAR(32) NOT NULL UNIQUE,
                amount DECIMAL(15,2) NOT NULL,
                status ENUM('pending','approved','expired','rejected') NOT NULL DEFAULT 'pending',
                beneficiary_name VARCHAR(128) NOT NULL DEFAULT '',
                beneficiary_phone VARCHAR(32) NOT NULL DEFAULT '',
                operator VARCHAR(32) NOT NULL DEFAULT '',
                client_note TEXT,
                admin_comment TEXT,
                transaction_id VARCHAR(128) DEFAULT NULL,
                expires_at DATETIME NOT NULL,
                approved_by INT DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin (admin_id),
                INDEX idx_status (status),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS wifizone_withdrawal_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id INT NOT NULL,
                admin_id INT NOT NULL,
                message VARCHAR(512) NOT NULL,
                read_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_read (read_at),
                INDEX idx_request (request_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($queries as $sql) {
            try {
                $db->exec($sql);
            } catch (Exception $e) {
                if (class_exists('WifiZoneLogger')) {
                    WifiZoneLogger::logPluginError('withdrawal_schema', $e);
                }
            }
        }
    }

    public static function minAmount()
    {
        return self::MIN_AMOUNT;
    }

    public static function commissionRate($type)
    {
        global $config;
        $type = strtolower((string) $type);
        if ($type === 'hotspot') {
            return (float) ($config['wifizone_withdraw_commission_hotspot'] ?? 10);
        }
        if ($type === 'pppoe') {
            return (float) ($config['wifizone_withdraw_commission_pppoe'] ?? 10);
        }

        return (float) ($config['wifizone_withdraw_commission_default'] ?? 10);
    }

    public static function commissionLabel()
    {
        $h = self::commissionRate('Hotspot');
        $p = self::commissionRate('PPPOE');

        return 'Hotspot ' . rtrim(rtrim(number_format($h, 2, '.', ''), '0'), '.') . '% / PPPoE ' . rtrim(rtrim(number_format($p, 2, '.', ''), '0'), '.') . '%';
    }

    /** Vente éligible au retrait : passerelle automatisée Hotspot/PPPoE uniquement. */
    public static function isWithdrawableSale($trx)
    {
        $row = class_exists('WifiZoneSales')
            ? WifiZoneSales::normalizeSaleRow($trx)
            : (is_array($trx) ? $trx : (array) $trx);
        if (!in_array($row['type'] ?? '', ['Hotspot', 'PPPOE'], true)) {
            return false;
        }
        $method = (string) ($row['method'] ?? '');
        if ($method === '' || stripos($method, 'Customer - Balance') !== false) {
            return false;
        }
        if (stripos($method, 'Recharge Balance - Administrator') !== false) {
            return false;
        }
        if (preg_match('/hotspot_payment:(\d+)/', (string) ($row['note'] ?? ''), $paymentMatch)) {
            $payment = ORM::for_table('tbl_hotspot_payments')->find_one((int) $paymentMatch[1]);
            if ($payment && (string) ($payment->transaction_status ?? '') === 'paid') {
                return true;
            }
        }
        if (!empty($row['invoice'])) {
            $pg = ORM::for_table('tbl_payment_gateway')
                ->where('trx_invoice', $row['invoice'])
                ->where('status', 2)
                ->find_one();
            if ($pg) {
                return true;
            }
        }
        $gateway = trim(explode(' - ', $method, 2)[0]);
        if (in_array($gateway, self::$manualGateways, true)) {
            return false;
        }
        if (stripos($gateway, 'Recharge') === 0) {
            return false;
        }
        if (stripos($method, 'CamPay') !== false || stripos($method, 'MyPVit') !== false) {
            return true;
        }

        return $gateway !== '';
    }

    public static function salesBreakdown($adminId = null)
    {
        self::ensureSchema();
        $query = ORM::for_table('tbl_transactions')
            ->where_in('type', ['Hotspot', 'PPPOE'])
            ->where_not_equal('method', 'Customer - Balance');
        if ($adminId !== null) {
            AdminScope::applyTransactionsQueryByAdminId($query, (int) $adminId);
        }
        $rows = $query->find_many();
        $gross = 0.0;
        $commission = 0.0;
        $eligible = [];
        foreach ($rows as $t) {
            if (!self::isWithdrawableSale($t)) {
                continue;
            }
            $eligible[] = $t;
        }
        if (class_exists('WifiZoneSales')) {
            $eligible = WifiZoneSales::dedupeSaleRows($eligible);
        }
        foreach ($eligible as $t) {
            $row = class_exists('WifiZoneSales')
                ? WifiZoneSales::normalizeSaleRow($t)
                : (is_array($t) ? $t : (array) $t);
            $price = class_exists('WifiZoneSales')
                ? WifiZoneSales::rowSaleAmount($row)
                : (float) ($row['price'] ?? 0);
            $gross += $price;
            $commission += $price * (self::commissionRate($row['type'] ?? '') / 100);
        }

        if (class_exists('WifiZoneSales') && $adminId !== null) {
            $admin = ORM::for_table('tbl_users')->find_one((int) $adminId);
            if ($admin) {
                $orphanHotspot = WifiZoneSales::sumHotspotPaymentsIncome(
                    $admin->as_array(),
                    '1970-01-01',
                    class_exists('WifiZoneTime')
                        ? WifiZoneTime::now(['admin_id' => (int) $adminId])->format('Y-m-d')
                        : date('Y-m-d'),
                    true
                );
                if ($orphanHotspot > 0) {
                    $gross += $orphanHotspot;
                    $commission += $orphanHotspot * (self::commissionRate('Hotspot') / 100);
                }
            }
        }

        return [
            'gross' => round($gross, 2),
            'commission' => round($commission, 2),
            'net' => round($gross - $commission, 2),
        ];
    }

    public static function sumApproved($adminId)
    {
        self::ensureSchema();

        return (float) (ORM::for_table('wifizone_withdrawal_requests')
            ->where('admin_id', (int) $adminId)
            ->where('status', self::STATUS_APPROVED)
            ->sum('amount') ?: 0);
    }

    public static function sumBlocked($adminId)
    {
        self::ensureSchema();

        return (float) (ORM::for_table('wifizone_withdrawal_requests')
            ->where('admin_id', (int) $adminId)
            ->where('status', self::STATUS_PENDING)
            ->sum('amount') ?: 0);
    }

    /** Solde disponible = (ventes passerelle - commissions) - retraits validés - montants bloqués. */
    public static function availableBalance($adminId)
    {
        $sales = self::salesBreakdown((int) $adminId);

        return max(0, round($sales['net'] - self::sumApproved((int) $adminId) - self::sumBlocked((int) $adminId), 2));
    }

    public static function platformStats()
    {
        self::ensureSchema();
        $sales = self::salesBreakdown(null);
        $approvedTotal = (float) (ORM::for_table('wifizone_withdrawal_requests')
            ->where('status', self::STATUS_APPROVED)
            ->sum('amount') ?: 0);
        $pendingTotal = (float) (ORM::for_table('wifizone_withdrawal_requests')
            ->where('status', self::STATUS_PENDING)
            ->sum('amount') ?: 0);
        $dueToClients = 0.0;
        $admins = ORM::for_table('tbl_users')->where('user_type', 'Admin')->find_many();
        foreach ($admins as $a) {
            $dueToClients += self::availableBalance((int) $a->id);
        }

        return [
            'gross_revenue' => $sales['gross'],
            'platform_commission' => $sales['commission'],
            'due_to_clients' => round($dueToClients, 2),
            'approved_total' => $approvedTotal,
            'pending_total' => $pendingTotal,
        ];
    }

    public static function getProfile($adminId)
    {
        self::ensureSchema();

        return ORM::for_table('wifizone_withdrawal_profiles')
            ->where('admin_id', (int) $adminId)
            ->find_one();
    }

    public static function setProfileLock($adminId, $locked)
    {
        self::ensureSchema();
        $adminId = (int) $adminId;
        $profile = self::getProfile($adminId);
        if (!$profile) {
            throw new Exception('Aucun profil bénéficiaire pour cet administrateur.');
        }
        $profile->locked = $locked ? 1 : 0;
        $profile->updated_at = date('Y-m-d H:i:s');
        $profile->save();

        return $profile;
    }

    public static function saveProfile($adminId, $data, $forceUnlock = false)
    {
        self::ensureSchema();
        $adminId = (int) $adminId;
        $profile = self::getProfile($adminId);
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $phone = self::normalizePhone($data['phone'] ?? '');
        $operator = (string) ($data['operator'] ?? 'orange_momo');
        if ($firstName === '' || $lastName === '' || $phone === '') {
            throw new Exception('Nom, prénom et numéro de téléphone sont obligatoires.');
        }
        if (!isset(self::OPERATORS[$operator])) {
            throw new Exception('Opérateur invalide.');
        }
        if ($profile && (int) $profile->locked === 1 && !$forceUnlock) {
            throw new Exception('Profil verrouillé. Contactez le SERVICE CLIENT.');
        }
        if (!$profile) {
            $profile = ORM::for_table('wifizone_withdrawal_profiles')->create();
            $profile->admin_id = $adminId;
            $profile->created_at = date('Y-m-d H:i:s');
        }
        $profile->first_name = $firstName;
        $profile->last_name = $lastName;
        $profile->phone = $phone;
        $profile->operator = $operator;
        $profile->locked = 1;
        $profile->updated_at = date('Y-m-d H:i:s');
        $profile->save();

        return $profile;
    }

    public static function beneficiaryName($profile)
    {
        if (!$profile) {
            return '';
        }
        $row = is_array($profile) ? $profile : $profile->as_array();

        return trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    }

    public static function operatorLabel($key)
    {
        return self::OPERATORS[$key] ?? $key;
    }

    public static function requestsForAdmin($adminId, $limit = 50)
    {
        self::ensureSchema();

        return ORM::for_table('wifizone_withdrawal_requests')
            ->where('admin_id', (int) $adminId)
            ->order_by_desc('id')
            ->limit($limit)
            ->find_many();
    }

    public static function profilesForSuperAdmin($limit = 100)
    {
        self::ensureSchema();

        return ORM::for_table('wifizone_withdrawal_profiles')
            ->table_alias('wp')
            ->select('wp.*')
            ->select('u.fullname', 'admin_fullname')
            ->select('u.username', 'admin_username')
            ->left_outer_join('tbl_users', ['wp.admin_id', '=', 'u.id'], 'u')
            ->where_raw('(u.id IS NULL OR u.user_type = ?)', ['Admin'])
            ->order_by_desc('wp.updated_at')
            ->limit($limit)
            ->find_many();
    }

    public static function pendingForSuperAdmin()
    {
        self::ensureSchema();
        self::expireStaleRequests(false);

        return ORM::for_table('wifizone_withdrawal_requests')
            ->table_alias('r')
            ->select('r.*')
            ->select('u.fullname', 'admin_fullname')
            ->select('u.username', 'admin_username')
            ->select('wp.first_name', 'profile_first_name')
            ->select('wp.last_name', 'profile_last_name')
            ->select('wp.phone', 'profile_phone')
            ->select('wp.locked', 'profile_locked')
            ->left_outer_join('tbl_users', ['r.admin_id', '=', 'u.id'], 'u')
            ->left_outer_join('wifizone_withdrawal_profiles', ['r.admin_id', '=', 'wp.admin_id'], 'wp')
            ->where('r.status', self::STATUS_PENDING)
            ->order_by_asc('r.expires_at')
            ->find_many();
    }

    public static function allRequestsForSuperAdmin($status = null, $limit = 200)
    {
        self::ensureSchema();
        $query = ORM::for_table('wifizone_withdrawal_requests')
            ->table_alias('r')
            ->select('r.*')
            ->select('u.fullname', 'admin_fullname')
            ->select('u.username', 'admin_username')
            ->left_outer_join('tbl_users', ['r.admin_id', '=', 'u.id'], 'u')
            ->order_by_desc('r.id')
            ->limit($limit);
        if ($status !== null && $status !== '') {
            $query->where('r.status', $status);
        }

        return $query->find_many();
    }

    public static function submitRequest($adminId, $amount, $note = '')
    {
        self::ensureSchema();
        $adminId = (int) $adminId;
        $amount = round((float) $amount, 2);
        $profile = self::getProfile($adminId);
        if (!$profile) {
            throw new Exception('Configurez d\'abord votre profil de retrait.');
        }
        if ($amount < self::MIN_AMOUNT) {
            throw new Exception('Le montant minimum de retrait est de ' . number_format(self::MIN_AMOUNT, 0, ',', ' ') . ' Fcfa.');
        }
        $available = self::availableBalance($adminId);
        if ($amount > $available) {
            throw new Exception('Montant supérieur au solde disponible (' . number_format($available, 0, ',', ' ') . ' Fcfa).');
        }
        $pending = ORM::for_table('wifizone_withdrawal_requests')
            ->where('admin_id', $adminId)
            ->where('status', self::STATUS_PENDING)
            ->find_one();
        if ($pending) {
            throw new Exception('Une demande est déjà en cours de traitement.');
        }
        $now = date('Y-m-d H:i:s');
        $ref = 'WR-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $req = ORM::for_table('wifizone_withdrawal_requests')->create();
        $req->admin_id = $adminId;
        $req->reference = $ref;
        $req->amount = $amount;
        $req->status = self::STATUS_PENDING;
        $req->beneficiary_name = self::beneficiaryName($profile);
        $req->beneficiary_phone = $profile->phone;
        $req->operator = $profile->operator;
        $req->client_note = trim((string) $note);
        $req->expires_at = date('Y-m-d H:i:s', strtotime('+' . self::EXPIRY_HOURS . ' hours'));
        $req->created_at = $now;
        $req->updated_at = $now;
        $req->save();

        self::notifyNewRequest($req, $profile);
        if (class_exists('WifiZoneAudit')) {
            WifiZoneAudit::log('withdrawal_request', 'admin', $adminId, ['ref' => $ref, 'amount' => $amount]);
        }

        return $req;
    }

    public static function approveRequest($requestId, $superAdminId, $transactionId, $comment = '')
    {
        self::ensureSchema();
        $req = ORM::for_table('wifizone_withdrawal_requests')->find_one((int) $requestId);
        if (!$req || $req->status !== self::STATUS_PENDING) {
            throw new Exception('Demande introuvable ou déjà traitée.');
        }
        $req->status = self::STATUS_APPROVED;
        $req->transaction_id = trim((string) $transactionId);
        $req->admin_comment = trim((string) $comment);
        if ($req->admin_comment === '' && $req->transaction_id !== '') {
            $req->admin_comment = $req->transaction_id;
        }
        $req->approved_by = (int) $superAdminId;
        $req->approved_at = date('Y-m-d H:i:s');
        $req->updated_at = date('Y-m-d H:i:s');
        $req->save();
        self::markNotificationsRead((int) $req->id);
        if (class_exists('WifiZoneAudit')) {
            WifiZoneAudit::log('withdrawal_approved', 'withdrawal', (int) $req->id, ['by' => $superAdminId]);
        }

        return $req;
    }

    public static function rejectRequest($requestId, $superAdminId, $comment = '')
    {
        self::ensureSchema();
        $req = ORM::for_table('wifizone_withdrawal_requests')->find_one((int) $requestId);
        if (!$req || $req->status !== self::STATUS_PENDING) {
            throw new Exception('Demande introuvable ou déjà traitée.');
        }
        $req->status = self::STATUS_REJECTED;
        $req->admin_comment = trim((string) $comment) ?: 'Demande rejetée par le SuperAdmin.';
        $req->approved_by = (int) $superAdminId;
        $req->approved_at = date('Y-m-d H:i:s');
        $req->updated_at = date('Y-m-d H:i:s');
        $req->save();
        self::markNotificationsRead((int) $req->id);
        if (class_exists('WifiZoneAudit')) {
            WifiZoneAudit::log('withdrawal_rejected', 'withdrawal', (int) $req->id, ['by' => $superAdminId]);
        }

        return $req;
    }

    public static function expireStaleRequests($notify = true)
    {
        self::ensureSchema();
        $rows = ORM::for_table('wifizone_withdrawal_requests')
            ->where('status', self::STATUS_PENDING)
            ->where_lt('expires_at', date('Y-m-d H:i:s'))
            ->find_many();
        $count = 0;
        foreach ($rows as $req) {
            $req->status = self::STATUS_EXPIRED;
            $req->admin_comment = 'Expirée automatiquement (délai 24h dépassé)';
            $req->updated_at = date('Y-m-d H:i:s');
            $req->save();
            self::markNotificationsRead((int) $req->id);
            $count++;
        }

        return $count;
    }

    public static function pendingCount()
    {
        self::ensureSchema();
        self::expireStaleRequests(false);

        return (int) ORM::for_table('wifizone_withdrawal_requests')
            ->where('status', self::STATUS_PENDING)
            ->count();
    }

    public static function pendingNotifications($limit = 10)
    {
        self::ensureSchema();
        self::expireStaleRequests(false);

        return ORM::for_table('wifizone_withdrawal_notifications')
            ->table_alias('n')
            ->select('n.*')
            ->select('r.amount', 'amount')
            ->select('r.reference', 'reference')
            ->select('r.expires_at', 'expires_at')
            ->left_outer_join('wifizone_withdrawal_requests', ['n.request_id', '=', 'r.id'], 'r')
            ->where_null('n.read_at')
            ->order_by_desc('n.id')
            ->limit($limit)
            ->find_many();
    }

    public static function markNotificationsRead($requestId = null)
    {
        self::ensureSchema();
        if ($requestId) {
            ORM::for_table('wifizone_withdrawal_notifications')
                ->where('request_id', (int) $requestId)
                ->find_result_set()
                ->set('read_at', date('Y-m-d H:i:s'))
                ->save();
        }
    }

    public static function markNotificationRead($notificationId)
    {
        self::ensureSchema();
        $n = ORM::for_table('wifizone_withdrawal_notifications')->find_one((int) $notificationId);
        if ($n && empty($n->read_at)) {
            $n->read_at = date('Y-m-d H:i:s');
            $n->save();
        }
    }

    public static function statusLabel($status)
    {
        $map = [
            self::STATUS_PENDING => 'En traitement',
            self::STATUS_APPROVED => 'Validée',
            self::STATUS_EXPIRED => 'Expirée',
            self::STATUS_REJECTED => 'Rejetée',
        ];

        return $map[$status] ?? $status;
    }

    public static function statusBadgeClass($status)
    {
        $map = [
            self::STATUS_PENDING => 'label-warning',
            self::STATUS_APPROVED => 'label-success',
            self::STATUS_EXPIRED => 'label-danger',
            self::STATUS_REJECTED => 'label-default',
        ];

        return $map[$status] ?? 'label-default';
    }

    public static function countdownHtml($expiresAt)
    {
        $remaining = strtotime($expiresAt) - time();
        if ($remaining <= 0) {
            return '<span class="text-danger">Expiré</span>';
        }
        $h = floor($remaining / 3600);
        $m = floor(($remaining % 3600) / 60);

        return '<span class="text-warning"><i class="fa fa-clock-o"></i> ' . $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm</span>';
    }

    public static function searchAdmins($term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return [];
        }
        $term = str_replace(['%', '_'], ['\\%', '\\_'], $term);
        $like = '%' . $term . '%';

        return ORM::for_table('tbl_users')
            ->where('user_type', 'Admin')
            ->where_raw('(fullname LIKE ? OR username LIKE ? OR phone LIKE ? OR email LIKE ?)', [$like, $like, $like, $like])
            ->limit(20)
            ->find_many();
    }

    private static function normalizePhone($phone)
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        if (strpos($phone, '237') === 0) {
            $phone = substr($phone, 3);
        }

        return $phone;
    }

    private static function notifyNewRequest($req, $profile)
    {
        $admin = ORM::for_table('tbl_users')->find_one((int) $req->admin_id);
        $name = $admin ? $admin->fullname : self::beneficiaryName($profile);
        $amount = number_format((float) $req->amount, 0, ',', ' ');
        $operator = self::operatorLabel($req->operator);
        $deadline = date('d/m/Y H:i', strtotime($req->expires_at));
        $msg = "🔴 Alerte Retrait : {$name} a demandé {$amount} Fcfa (Il reste 24h)";

        $n = ORM::for_table('wifizone_withdrawal_notifications')->create();
        $n->request_id = (int) $req->id;
        $n->admin_id = (int) $req->admin_id;
        $n->message = $msg;
        $n->created_at = date('Y-m-d H:i:s');
        $n->save();

        $telegram = "🚨 NOUVELLE DEMANDE DE RETRAIT 🚨\n"
            . "----------------------------------\n"
            . "👤 Administrateur : {$name}\n"
            . "💰 Montant : {$amount} FCFA\n"
            . "📱 Opérateur : {$operator}\n"
            . "📞 Numéro : {$req->beneficiary_phone}\n"
            . "⏱ Heure de limite : {$deadline}\n\n"
            . "👉 Connectez-vous sur le panel pour valider le reversement.";
        if (class_exists('Message')) {
            Message::sendTelegram($telegram);
            self::emailSuperAdmins($name, $amount, $operator, $req->beneficiary_phone, $deadline);
        }
    }

    private static function emailSuperAdmins($adminName, $amount, $operator, $phone, $deadline)
    {
        $subject = '[ALERTE FINANCE] - Nouvelle demande de reversement en attente';
        $body = '<div style="font-family:Arial,sans-serif;max-width:600px">'
            . '<h2 style="color:#c0392b">Nouvelle demande de retrait</h2>'
            . '<table style="width:100%;border-collapse:collapse" cellpadding="8">'
            . '<tr><td style="border:1px solid #ddd"><strong>Administrateur</strong></td><td style="border:1px solid #ddd">' . htmlspecialchars($adminName) . '</td></tr>'
            . '<tr><td style="border:1px solid #ddd"><strong>Montant</strong></td><td style="border:1px solid #ddd">' . htmlspecialchars($amount) . ' FCFA</td></tr>'
            . '<tr><td style="border:1px solid #ddd"><strong>Opérateur</strong></td><td style="border:1px solid #ddd">' . htmlspecialchars($operator) . '</td></tr>'
            . '<tr><td style="border:1px solid #ddd"><strong>Numéro</strong></td><td style="border:1px solid #ddd">' . htmlspecialchars($phone) . '</td></tr>'
            . '<tr><td style="border:1px solid #ddd"><strong>Heure limite</strong></td><td style="border:1px solid #ddd">' . htmlspecialchars($deadline) . '</td></tr>'
            . '</table>'
            . '<p style="margin-top:16px">Connectez-vous sur le panel pour valider le reversement.</p>'
            . '</div>';
        $superAdmins = ORM::for_table('tbl_users')->where('user_type', 'SuperAdmin')->find_many();
        foreach ($superAdmins as $sa) {
            if (!empty($sa->email)) {
                Message::sendEmail($sa->email, $subject, $body);
            }
        }
    }
}
