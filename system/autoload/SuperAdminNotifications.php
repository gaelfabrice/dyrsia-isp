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

    public static function notifyInstanceCreated($tenantId, $businessNameOrPayload = '', $slugOrDeferTelegram = false)
    {
        self::ensureSchema();
        $tenantId = (int) $tenantId;
        if ($tenantId <= 0) {
            return;
        }

        $deferTelegram = false;
        if (is_array($businessNameOrPayload)) {
            $payload = $businessNameOrPayload;
            $businessName = trim((string) ($payload['business_name'] ?? ''));
            $slug = trim((string) ($payload['slug'] ?? ''));
            $deferTelegram = (bool) $slugOrDeferTelegram;
        } else {
            $businessName = trim((string) $businessNameOrPayload);
            $slug = is_string($slugOrDeferTelegram) ? trim($slugOrDeferTelegram) : '';
            $payload = [
                'business_name' => $businessName,
                'slug' => $slug,
            ];
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

        if ($deferTelegram) {
            register_shutdown_function(static function () use ($payload) {
                @ignore_user_abort(true);
                ob_start();
                try {
                    self::sendInstanceCreatedTelegram($payload);
                } catch (Throwable $e) {
                    if (function_exists('_log')) {
                        _log('Deferred superadmin telegram failed: ' . $e->getMessage());
                    }
                }
                ob_end_clean();
            });
            return;
        }

        self::sendInstanceCreatedTelegram($payload);
    }

    /** @return array{bot: string, chat_id: string} */
    public static function telegramSettings()
    {
        return [
            'bot' => trim((string) self::getAppConfig('superadmin_telegram_bot')),
            'chat_id' => trim((string) self::getAppConfig('superadmin_telegram_chat_id')),
        ];
    }

    public static function saveTelegramSettings($bot, $chatId)
    {
        self::setAppConfig('superadmin_telegram_bot', trim((string) $bot));
        self::setAppConfig('superadmin_telegram_chat_id', trim((string) $chatId));
    }

    public static function sendTelegramMessage($text, $bot = null, $chatId = null)
    {
        $settings = self::telegramSettings();
        $bot = trim((string) ($bot ?? $settings['bot']));
        $chatId = trim((string) ($chatId ?? $settings['chat_id']));
        if ($bot === '' || $chatId === '') {
            return false;
        }

        try {
            return Http::getData(
                'https://api.telegram.org/bot' . $bot . '/sendMessage?chat_id=' . urlencode($chatId) . '&text=' . urlencode($text)
            );
        } catch (Throwable $e) {
            if (function_exists('_log')) {
                _log('SuperAdmin Telegram send failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function sendInstanceCreatedTelegram(array $payload)
    {
        $message = self::formatInstanceCreatedTelegram($payload);
        if ($message === '') {
            return;
        }
        self::sendTelegramMessage($message);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function formatInstanceCreatedTelegram(array $payload)
    {
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $businessName = trim((string) ($payload['business_name'] ?? ''));
        $phone = trim((string) ($payload['phone_number'] ?? ''));
        $slug = trim((string) ($payload['slug'] ?? ''));
        $countryCode = trim((string) ($payload['country_code'] ?? ''));
        $country = self::countryLabel($countryCode);
        $subdomain = $slug !== '' ? $slug . Tenant::domainSuffix() : '';
        $timestamp = date('d/m/Y - H:i');

        $line = static function ($icon, $label, $value) {
            return $icon . '  ' . str_pad($label, 19, ' ', STR_PAD_RIGHT) . ':  ' . $value;
        };

        return implode("\n", [
            '🔔 ──────────────────────────────',
            '    ✨ NOUVELLE INSTANCE ✨',
            '────────────────────────────────',
            '',
            $line('👤', 'Full Name', $fullName),
            $line('📧', 'Email', $email),
            $line('🏢', 'ISP/Business Name', $businessName),
            $line('🌍', 'Pays', $country),
            $line('📱', 'Phone Number', $phone),
            $line('🔗', 'Desired Subdomain', $subdomain),
            '',
            '────────────────────────────────',
            '🕐  ' . $timestamp,
            '🔔 ──────────────────────────────',
        ]);
    }

    public static function countryLabel($countryCode)
    {
        $code = strtoupper(trim((string) $countryCode));
        if ($code === 'GA') {
            return 'GABON';
        }
        if ($code === 'CM') {
            return 'cameroun';
        }
        $country = MobileMoneyCountry::resolve($code);

        return $country['name'] ?? $code;
    }

    private static function getAppConfig($key)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();

        return $row ? (string) $row->value : '';
    }

    private static function setAppConfig($key, $value)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = $key;
        }
        $row->value = $value;
        $row->save();
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
