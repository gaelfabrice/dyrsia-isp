<?php

/**
 * SuperAdmin header bell: withdrawals, registration requests, new ISP instances.
 */
class SuperAdminNotifications
{
    public static function ensureSchema()
    {
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS wifizone_superadmin_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alert_type VARCHAR(32) NOT NULL,
            reference_id INT NOT NULL DEFAULT 0,
            message VARCHAR(512) NOT NULL,
            target_url VARCHAR(512) NOT NULL DEFAULT '',
            read_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_type_ref (alert_type, reference_id),
            KEY idx_unread (read_at, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function registrationPendingCount()
    {
        try {
            return (int) ORM::for_table('tbl_customer_registration_requests')
                ->where('status', 'pending_approval')
                ->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function instanceUnreadCount()
    {
        self::ensureSchema();

        return (int) ORM::for_table('wifizone_superadmin_alerts')
            ->where('alert_type', 'instance')
            ->where_null('read_at')
            ->count();
    }

    public static function totalPendingCount()
    {
        if (!class_exists('Withdrawal')) {
            return self::registrationPendingCount() + self::instanceUnreadCount();
        }

        return (int) Withdrawal::pendingCount()
            + self::registrationPendingCount()
            + self::instanceUnreadCount();
    }

    /**
     * @return list<array{type: string, id: int|string, message: string, url: string, created_at: string}>
     */
    public static function feed($limit = 12)
    {
        $items = [];

        if (class_exists('Withdrawal')) {
            foreach (Withdrawal::pendingNotifications($limit) as $row) {
                $items[] = [
                    'type' => 'withdrawal',
                    'id' => (int) $row->id,
                    'message' => (string) $row->message,
                    'url' => getUrl('finance/reversement') . '&notification=' . (int) $row->id,
                    'created_at' => (string) ($row->created_at ?? date('Y-m-d H:i:s')),
                ];
            }
        }

        try {
            $registrations = ORM::for_table('tbl_customer_registration_requests')
                ->where('status', 'pending_approval')
                ->order_by_desc('created_at')
                ->limit($limit)
                ->find_many();
            foreach ($registrations as $req) {
                $name = trim((string) $req->first_name . ' ' . (string) $req->last_name);
                $instance = trim((string) $req->instance_name);
                $items[] = [
                    'type' => 'registration',
                    'id' => (int) $req->id,
                    'message' => 'Demande d\'inscription'
                        . ($instance !== '' ? ' · ' . $instance : '')
                        . ($name !== '' ? ' · ' . $name : ''),
                    'url' => getUrl('registration_requests'),
                    'created_at' => (string) ($req->created_at ?? date('Y-m-d H:i:s')),
                ];
            }
        } catch (Throwable $e) {
        }

        self::ensureSchema();
        foreach (ORM::for_table('wifizone_superadmin_alerts')
            ->where_null('read_at')
            ->order_by_desc('created_at')
            ->limit($limit)
            ->find_many() as $alert) {
            $items[] = [
                'type' => (string) $alert->alert_type,
                'id' => (int) $alert->id,
                'message' => (string) $alert->message,
                'url' => (string) ($alert->target_url ?: getUrl('superadmin/instances')),
                'created_at' => (string) ($alert->created_at ?? date('Y-m-d H:i:s')),
            ];
        }

        usort($items, static function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return array_slice($items, 0, (int) $limit);
    }

    public static function notifyInstanceCreated($tenantId, $businessName, $slug)
    {
        self::ensureSchema();
        $tenantId = (int) $tenantId;
        $businessName = trim((string) $businessName);
        $slug = trim((string) $slug);
        if ($tenantId <= 0) {
            return;
        }

        $exists = ORM::for_table('wifizone_superadmin_alerts')
            ->where('alert_type', 'instance')
            ->where('reference_id', $tenantId)
            ->find_one();
        if ($exists) {
            return;
        }

        $alert = ORM::for_table('wifizone_superadmin_alerts')->create();
        $alert->alert_type = 'instance';
        $alert->reference_id = $tenantId;
        $alert->message = 'Nouvelle instance · ' . ($businessName !== '' ? $businessName : $slug)
            . ($slug !== '' ? ' (' . $slug . ')' : '');
        $alert->target_url = getUrl('superadmin/instances');
        $alert->created_at = date('Y-m-d H:i:s');
        $alert->save();
    }

    public static function markInstanceAlertsRead()
    {
        self::ensureSchema();
        ORM::for_table('wifizone_superadmin_alerts')
            ->where('alert_type', 'instance')
            ->where_null('read_at')
            ->find_result_set()
            ->set('read_at', date('Y-m-d H:i:s'))
            ->save();
    }

    public static function markAlertRead($alertId, $alertType = '')
    {
        self::ensureSchema();
        $query = ORM::for_table('wifizone_superadmin_alerts')->where('id', (int) $alertId);
        if ($alertType !== '') {
            $query->where('alert_type', $alertType);
        }
        $alert = $query->find_one();
        if ($alert && empty($alert->read_at)) {
            $alert->read_at = date('Y-m-d H:i:s');
            $alert->save();
        }
    }
}
