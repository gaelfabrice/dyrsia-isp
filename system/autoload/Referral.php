<?php

/**
 * Système de parrainage (affiliation) DYRSIA ISP
 *
 * Tables gérées :
 *   - referral_codes       : code unique par admin (parrain)
 *   - referral_links       : enregistrement filleul → parrain
 *   - referral_commissions : commissions générées
 *   - referral_withdrawals : demandes de retrait
 *   - referral_notifications : notifications bell pour le parrain
 */
class Referral
{
    const COMMISSION_BUSINESS = 1000.0;
    const COMMISSION_PRO      = 2000.0;
    const WITHDRAWAL_FEE_RATE = 0.10;
    const WITHDRAWAL_MIN      = 2000.0;

    // ─────────────────────────────────────────────
    // SCHÉMA
    // ─────────────────────────────────────────────

    private static $schemaEnsured = false;

    public static function ensureSchema()
    {
        if (self::$schemaEnsured) {
            return;
        }
        self::$schemaEnsured = true;
        try {
            self::_ensureSchemaInternal();
        } catch (Throwable $e) {
            _log('Referral::ensureSchema failed: ' . $e->getMessage(), 'Referral');
        }
    }

    private static function _ensureSchemaInternal()
    {
        $db = ORM::get_db();

        $db->exec("CREATE TABLE IF NOT EXISTS `referral_codes` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id`   INT NOT NULL,
            `code`       VARCHAR(32) NOT NULL,
            `balance`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_admin`  (`admin_id`),
            UNIQUE KEY `uq_code`   (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS `referral_links` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `referrer_id` INT NOT NULL COMMENT 'admin_id du parrain',
            `referee_id`  INT NOT NULL COMMENT 'admin_id du filleul',
            `is_converted`TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = premier abonnement payé',
            `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_referee` (`referee_id`),
            KEY `idx_referrer` (`referrer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS `referral_commissions` (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `referrer_id`  INT NOT NULL,
            `referee_id`   INT NOT NULL,
            `plan_type`    VARCHAR(20) NOT NULL,
            `amount`       DECIMAL(15,2) NOT NULL,
            `invoice_id`   INT DEFAULT NULL,
            `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_referrer` (`referrer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS `referral_withdrawals` (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id`     INT NOT NULL,
            `amount`       DECIMAL(15,2) NOT NULL,
            `fee`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `net_amount`   DECIMAL(15,2) NOT NULL,
            `status`       ENUM('pending','paid','rejected') NOT NULL DEFAULT 'pending',
            `note`         TEXT DEFAULT NULL,
            `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_admin`  (`admin_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS `referral_notifications` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id`   INT NOT NULL COMMENT 'parrain destinataire',
            `message`    VARCHAR(512) NOT NULL,
            `read_at`    DATETIME DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_admin_unread` (`admin_id`, `read_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // ─────────────────────────────────────────────
    // CODE / LIEN DE PARRAINAGE
    // ─────────────────────────────────────────────

    /**
     * Retourne (ou crée) le code de parrainage d'un admin.
     */
    public static function getOrCreateCode($adminId)
    {
        self::ensureSchema();
        $adminId = (int) $adminId;
        $row = ORM::for_table('referral_codes')->where('admin_id', $adminId)->find_one();
        if ($row) {
            return $row;
        }
        $row = ORM::for_table('referral_codes')->create();
        $row->admin_id  = $adminId;
        $row->code      = self::generateCode($adminId);
        $row->balance   = 0.00;
        $row->created_at = date('Y-m-d H:i:s');
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();
        return $row;
    }

    public static function generateCode($adminId)
    {
        return strtoupper(substr(sha1($adminId . 'dyrsia' . microtime(true)), 0, 10));
    }

    /**
     * Retourne l'URL publique du lien de parrainage.
     */
    public static function referralUrl($code)
    {
        return APP_URL . '/?_route=ref/' . urlencode($code);
    }

    /**
     * Trouve l'admin parrain à partir d'un code.
     */
    public static function findReferrerByCode($code)
    {
        self::ensureSchema();
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }
        return ORM::for_table('referral_codes')->where('code', $code)->find_one();
    }

    // ─────────────────────────────────────────────
    // ÉLIGIBILITÉ
    // ─────────────────────────────────────────────

    /**
     * Le parrain est éligible si son abonnement est actif (Business ou Pro).
     */
    public static function isReferrerActive($referrerId)
    {
        try {
            $sub = ORM::for_table('admin_subscriptions')
                ->where('admin_id', (int) $referrerId)
                ->find_one();
            return $sub && $sub->status === 'active';
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Le filleul n'a jamais eu d'abonnement actif avant.
     * Appelé après que le paiement courant a été marqué 'paid', donc on attend count <= 1.
     */
    public static function isFirstSubscription($refereeId)
    {
        try {
            $count = (int) ORM::for_table('admin_subscription_payments')
                ->where('admin_id', (int) $refereeId)
                ->where('status', 'paid')
                ->count();
            return $count <= 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────────
    // ENREGISTREMENT DU FILLEUL
    // ─────────────────────────────────────────────

    /**
     * Sauvegarde en session le code de parrainage visité.
     */
    public static function storeReferralSession($code)
    {
        $code = strtoupper(trim((string) $code));
        if ($code !== '') {
            $_SESSION['referral_code'] = $code;
        }
    }

    /**
     * Lie un filleul à son parrain dès la création du compte (provision).
     * Vérifie que le parrain existe et que le filleul n'est pas déjà lié.
     */
    public static function registerReferee($refereeAdminId, $code)
    {
        self::ensureSchema();
        $code = strtoupper(trim((string) $code));
        $refereeAdminId = (int) $refereeAdminId;
        if ($code === '' || $refereeAdminId < 1) {
            return false;
        }
        $refCode = self::findReferrerByCode($code);
        if (!$refCode) {
            return false;
        }
        $referrerId = (int) $refCode->admin_id;
        if ($referrerId === $refereeAdminId) {
            return false;
        }
        $existing = ORM::for_table('referral_links')->where('referee_id', $refereeAdminId)->find_one();
        if ($existing) {
            return false;
        }
        $link = ORM::for_table('referral_links')->create();
        $link->referrer_id  = $referrerId;
        $link->referee_id   = $refereeAdminId;
        $link->is_converted = 0;
        $link->created_at   = date('Y-m-d H:i:s');
        $link->save();
        return true;
    }

    // ─────────────────────────────────────────────
    // COMMISSION
    // ─────────────────────────────────────────────

    /**
     * Montant de commission selon le forfait.
     */
    public static function commissionAmount($planType)
    {
        return strtolower((string) $planType) === 'pro'
            ? self::COMMISSION_PRO
            : self::COMMISSION_BUSINESS;
    }

    /**
     * À appeler quand un filleul paie son PREMIER abonnement.
     * Vérifie toutes les règles et crédite le parrain si éligible.
     *
     * @param int    $refereeAdminId  admin_id du filleul
     * @param string $planType        'business' ou 'pro'
     * @param int    $invoiceId       id de la facture
     * @return bool  true si commission versée
     */
    public static function processCommission($refereeAdminId, $planType, $invoiceId = null)
    {
        self::ensureSchema();
        $refereeAdminId = (int) $refereeAdminId;

        $link = ORM::for_table('referral_links')->where('referee_id', $refereeAdminId)->find_one();
        if (!$link) {
            return false;
        }
        if ((int) $link->is_converted === 1) {
            return false;
        }
        if (!self::isFirstSubscription($refereeAdminId)) {
            return false;
        }
        $referrerId = (int) $link->referrer_id;
        if (!self::isReferrerActive($referrerId)) {
            return false;
        }

        $amount = self::commissionAmount($planType);

        $commission = ORM::for_table('referral_commissions')->create();
        $commission->referrer_id = $referrerId;
        $commission->referee_id  = $refereeAdminId;
        $commission->plan_type   = strtolower((string) $planType);
        $commission->amount      = $amount;
        $commission->invoice_id  = $invoiceId ? (int) $invoiceId : null;
        $commission->created_at  = date('Y-m-d H:i:s');
        $commission->save();

        $refCode = ORM::for_table('referral_codes')->where('admin_id', $referrerId)->find_one();
        if (!$refCode) {
            $refCode = self::getOrCreateCode($referrerId);
        }
        $refCode->balance = (float) $refCode->balance + $amount;
        $refCode->updated_at = date('Y-m-d H:i:s');
        $refCode->save();

        $link->is_converted = 1;
        $link->save();

        self::notifyReferrer($referrerId, $refereeAdminId, $planType, $amount, (float) $refCode->balance);

        return true;
    }

    // ─────────────────────────────────────────────
    // RETRAIT
    // ─────────────────────────────────────────────

    /**
     * Soumet une demande de retrait.
     */
    public static function requestWithdrawal($adminId, $amount)
    {
        self::ensureSchema();
        $adminId = (int) $adminId;
        $amount  = (float) $amount;

        if ($amount < self::WITHDRAWAL_MIN) {
            throw new RuntimeException('Le montant minimum de retrait est ' . number_format(self::WITHDRAWAL_MIN, 0, ',', ' ') . ' F CFA.');
        }

        $refCode = ORM::for_table('referral_codes')->where('admin_id', $adminId)->find_one();
        if (!$refCode || (float) $refCode->balance < $amount) {
            throw new RuntimeException('Solde insuffisant pour effectuer ce retrait.');
        }

        $fee       = round($amount * self::WITHDRAWAL_FEE_RATE, 2);
        $netAmount = $amount - $fee;

        $refCode->balance    = (float) $refCode->balance - $amount;
        $refCode->updated_at = date('Y-m-d H:i:s');
        $refCode->save();

        $wd = ORM::for_table('referral_withdrawals')->create();
        $wd->admin_id   = $adminId;
        $wd->amount     = $amount;
        $wd->fee        = $fee;
        $wd->net_amount = $netAmount;
        $wd->status     = 'pending';
        $wd->created_at = date('Y-m-d H:i:s');
        $wd->updated_at = date('Y-m-d H:i:s');
        $wd->save();

        return $wd;
    }

    /**
     * Approuve un retrait (SuperAdmin).
     */
    public static function approveWithdrawal($withdrawalId, $note = '')
    {
        self::ensureSchema();
        $wd = ORM::for_table('referral_withdrawals')->find_one((int) $withdrawalId);
        if (!$wd || $wd->status !== 'pending') {
            throw new RuntimeException('Retrait introuvable ou déjà traité.');
        }
        $wd->status     = 'paid';
        $wd->note       = trim((string) $note);
        $wd->updated_at = date('Y-m-d H:i:s');
        $wd->save();
        return $wd;
    }

    /**
     * Rejette un retrait (SuperAdmin) et rembourse le solde.
     */
    public static function rejectWithdrawal($withdrawalId, $note = '')
    {
        self::ensureSchema();
        $wd = ORM::for_table('referral_withdrawals')->find_one((int) $withdrawalId);
        if (!$wd || $wd->status !== 'pending') {
            throw new RuntimeException('Retrait introuvable ou déjà traité.');
        }
        $wd->status     = 'rejected';
        $wd->note       = trim((string) $note);
        $wd->updated_at = date('Y-m-d H:i:s');
        $wd->save();

        $refCode = ORM::for_table('referral_codes')->where('admin_id', (int) $wd->admin_id)->find_one();
        if ($refCode) {
            $refCode->balance    = (float) $refCode->balance + (float) $wd->amount;
            $refCode->updated_at = date('Y-m-d H:i:s');
            $refCode->save();
        }
        return $wd;
    }

    // ─────────────────────────────────────────────
    // NOTIFICATIONS BELL PARRAIN
    // ─────────────────────────────────────────────

    public static function notifyReferrer($referrerId, $refereeAdminId, $planType, $commission, $newBalance)
    {
        self::ensureSchema();
        try {
            $referee = ORM::for_table('tbl_users')->find_one((int) $refereeAdminId);
            $refereeName = $referee ? (string) ($referee->fullname ?: $referee->username) : ('Admin #' . $refereeAdminId);
            $planLabel   = strtolower((string) $planType) === 'pro' ? 'Forfait Pro' : 'Forfait Business';
            $commissionFmt  = number_format($commission, 0, ',', ' ');
            $balanceFmt     = number_format($newBalance,  0, ',', ' ');

            $message = sprintf(
                '🎉 %s vient de souscrire au %s. Commission : +%s F. Solde total : %s F.',
                $refereeName,
                $planLabel,
                $commissionFmt,
                $balanceFmt
            );

            $notif = ORM::for_table('referral_notifications')->create();
            $notif->admin_id   = (int) $referrerId;
            $notif->message    = $message;
            $notif->read_at    = null;
            $notif->created_at = date('Y-m-d H:i:s');
            $notif->save();
        } catch (Throwable $e) {
            _log('Referral notifyReferrer failed: ' . $e->getMessage());
        }
    }

    public static function unreadCount($adminId)
    {
        self::ensureSchema();
        try {
            return (int) ORM::for_table('referral_notifications')
                ->where('admin_id', (int) $adminId)
                ->where_null('read_at')
                ->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function notificationFeed($adminId, $limit = 12)
    {
        self::ensureSchema();
        try {
            return ORM::for_table('referral_notifications')
                ->where('admin_id', (int) $adminId)
                ->order_by_desc('created_at')
                ->limit($limit)
                ->find_many();
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function markAllRead($adminId)
    {
        self::ensureSchema();
        try {
            ORM::raw_execute(
                "UPDATE referral_notifications SET read_at = NOW() WHERE admin_id = ? AND read_at IS NULL",
                [(int) $adminId]
            );
        } catch (Throwable $e) {
        }
    }

    // ─────────────────────────────────────────────
    // STATISTIQUES
    // ─────────────────────────────────────────────

    public static function statsForAdmin($adminId)
    {
        self::ensureSchema();
        $adminId = (int) $adminId;

        $refCode   = ORM::for_table('referral_codes')->where('admin_id', $adminId)->find_one();
        $balance   = $refCode ? (float) $refCode->balance : 0.0;
        $code      = $refCode ? (string) $refCode->code : '';

        $totalFilleuls = (int) ORM::for_table('referral_links')
            ->where('referrer_id', $adminId)
            ->count();

        $convertedFilleuls = (int) ORM::for_table('referral_links')
            ->where('referrer_id', $adminId)
            ->where('is_converted', 1)
            ->count();

        $totalEarned = (float) (ORM::for_table('referral_commissions')
            ->where('referrer_id', $adminId)
            ->sum('amount') ?: 0);

        $totalWithdrawn = (float) (ORM::for_table('referral_withdrawals')
            ->where('admin_id', $adminId)
            ->where('status', 'paid')
            ->sum('net_amount') ?: 0);

        $pendingWithdrawal = (float) (ORM::for_table('referral_withdrawals')
            ->where('admin_id', $adminId)
            ->where('status', 'pending')
            ->sum('amount') ?: 0);

        return [
            'code'               => $code,
            'balance'            => $balance,
            'total_filleuls'     => $totalFilleuls,
            'converted_filleuls' => $convertedFilleuls,
            'total_earned'       => $totalEarned,
            'total_withdrawn'    => $totalWithdrawn,
            'pending_withdrawal' => $pendingWithdrawal,
        ];
    }

    public static function commissionsForAdmin($adminId, $limit = 50)
    {
        self::ensureSchema();
        return ORM::for_table('referral_commissions')
            ->where('referrer_id', (int) $adminId)
            ->order_by_desc('created_at')
            ->limit($limit)
            ->find_many();
    }

    public static function withdrawalsForAdmin($adminId, $limit = 50)
    {
        self::ensureSchema();
        return ORM::for_table('referral_withdrawals')
            ->where('admin_id', (int) $adminId)
            ->order_by_desc('created_at')
            ->limit($limit)
            ->find_many();
    }

    // ─────────────────────────────────────────────
    // SUPERADMIN
    // ─────────────────────────────────────────────

    public static function allPendingWithdrawals()
    {
        self::ensureSchema();
        return ORM::for_table('referral_withdrawals')
            ->table_alias('w')
            ->select('w.*')
            ->select('u.username', 'admin_username')
            ->select('u.fullname', 'admin_fullname')
            ->select('u.phone',    'admin_phone')
            ->join('tbl_users', ['w.admin_id', '=', 'u.id'], 'u')
            ->where('w.status', 'pending')
            ->order_by_asc('w.created_at')
            ->find_many();
    }

    public static function allWithdrawals($limit = 200)
    {
        self::ensureSchema();
        return ORM::for_table('referral_withdrawals')
            ->table_alias('w')
            ->select('w.*')
            ->select('u.username', 'admin_username')
            ->select('u.fullname', 'admin_fullname')
            ->select('u.phone',    'admin_phone')
            ->join('tbl_users', ['w.admin_id', '=', 'u.id'], 'u')
            ->order_by_desc('w.created_at')
            ->limit($limit)
            ->find_many();
    }

    public static function pendingWithdrawalsCount()
    {
        self::ensureSchema();
        try {
            return (int) ORM::for_table('referral_withdrawals')
                ->where('status', 'pending')
                ->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function platformStats()
    {
        self::ensureSchema();
        $totalParrains   = (int) ORM::for_table('referral_codes')->count();
        $totalFilleuls   = (int) ORM::for_table('referral_links')->count();
        $totalConverted  = (int) ORM::for_table('referral_links')->where('is_converted', 1)->count();
        $totalCommission = (float) (ORM::for_table('referral_commissions')->sum('amount') ?: 0);
        $totalPaidOut    = (float) (ORM::for_table('referral_withdrawals')->where('status', 'paid')->sum('net_amount') ?: 0);
        $pendingPayout   = (float) (ORM::for_table('referral_withdrawals')->where('status', 'pending')->sum('amount') ?: 0);
        return [
            'total_parrains'    => $totalParrains,
            'total_filleuls'    => $totalFilleuls,
            'total_converted'   => $totalConverted,
            'total_commission'  => $totalCommission,
            'total_paid_out'    => $totalPaidOut,
            'pending_payout'    => $pendingPayout,
        ];
    }
}
