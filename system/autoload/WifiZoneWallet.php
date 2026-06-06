<?php

class WifiZoneWallet
{
    public static function ensureSchema()
    {
        try {
            $db = ORM::get_db();
            if (!$db) {
                return;
            }
            $db->exec("CREATE TABLE IF NOT EXISTS admin_wallet (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                balance DECIMAL(15,2) DEFAULT 0,
                commission_balance DECIMAL(15,2) DEFAULT 0,
                commission_rate DECIMAL(5,2) DEFAULT 10.00,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_admin_id (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->exec("CREATE TABLE IF NOT EXISTS admin_wallet_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                old_balance DECIMAL(15,2),
                amount DECIMAL(15,2),
                total_balance DECIMAL(15,2),
                note TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_admin_id (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            // ignore
        }
    }

    public static function commissionLimitForRole($role)
    {
        if ($role === 'Agent') {
            return (float) WifiZoneCore::config('wifizone_commission_limit_agent', 50000);
        }
        if ($role === 'Sales') {
            return (float) WifiZoneCore::config('wifizone_commission_limit_sales', 25000);
        }
        return PHP_FLOAT_MAX;
    }

    public static function transferCommission($admin, $targetAdminId = null)
    {
        $targetAdminId = $targetAdminId ?: (int) $admin['id'];
        if ($admin['user_type'] !== 'SuperAdmin' && $targetAdminId != $admin['id']) {
            return ['ok' => false, 'msg' => 'Access denied'];
        }
        $wallet = ORM::for_table('admin_wallet')->where('admin_id', $targetAdminId)->find_one();
        if (!$wallet || $wallet->commission_balance <= 0) {
            return ['ok' => false, 'msg' => 'No commission balance available to transfer.'];
        }
        $amount = (float) $wallet->commission_balance;
        $limit = self::commissionLimitForRole($admin['user_type']);
        if ($amount > $limit && $admin['user_type'] !== 'SuperAdmin') {
            return ['ok' => false, 'msg' => 'Commission transfer exceeds role limit'];
        }
        $threshold = (float) WifiZoneCore::config('wifizone_transfer_approval_threshold', 10000);
        if ($amount > $threshold && $admin['user_type'] !== 'SuperAdmin') {
            $pending = ORM::for_table('wifizone_wallet_transfers')->create();
            $pending->admin_id = $targetAdminId;
            $pending->amount = $amount;
            $pending->status = 'pending';
            $pending->save();
            WifiZoneAudit::log('wallet_transfer_pending', 'admin_wallet', $targetAdminId, ['amount' => $amount]);
            return ['ok' => true, 'msg' => 'Transfer pending SuperAdmin approval', 'pending' => true];
        }
        $wallet->balance += $amount;
        $wallet->commission_balance = 0;
        $wallet->updated_at = date('Y-m-d H:i:s');
        $wallet->save();
        $transfer = ORM::for_table('wifizone_wallet_transfers')->create();
        $transfer->admin_id = $targetAdminId;
        $transfer->amount = $amount;
        $transfer->status = 'completed';
        $transfer->approved_by = $admin['id'];
        $transfer->save();
        WifiZoneAudit::log('wallet_transfer', 'admin_wallet', $targetAdminId, ['amount' => $amount]);
        return ['ok' => true, 'msg' => 'Commission balance successfully transferred to main wallet!'];
    }

    public static function exportTransfersCsv()
    {
        $rows = ORM::for_table('wifizone_wallet_transfers')->order_by_desc('id')->limit(5000)->find_array();
        $out = "id,admin_id,amount,status,approved_by,created_at\n";
        foreach ($rows as $r) {
            $out .= implode(',', array_map(function ($v) {
                return '"' . str_replace('"', '""', (string) $v) . '"';
            }, $r)) . "\n";
        }
        return $out;
    }
}
