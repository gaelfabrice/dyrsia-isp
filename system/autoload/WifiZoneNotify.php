<?php

class WifiZoneNotify
{
    public static function ensureSchema()
    {
        try {
            ORM::raw_execute("CREATE TABLE IF NOT EXISTS wifizone_renewal_reminders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recharge_id INT NOT NULL,
                reminder_key VARCHAR(8) NOT NULL,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_recharge_reminder (recharge_id, reminder_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            error_log('wifizone renewal reminders schema: ' . $e->getMessage());
        }
    }

    /**
     * Rappels d'expiration : J-7, J-3 et 24 h avant (datetime expiration + time).
     * Appelé par system/cron_wifizone.php (toutes les 5 min) avec dédoublonnage.
     */
    public static function processRenewalReminders()
    {
        global $config;

        self::ensureSchema();

        $via = trim((string) ($config['user_notification_reminder'] ?? 'sms'));
        if ($via === '' || $via === 'none') {
            return;
        }

        $intervals = [
            '7d' => [
                'enabled' => self::isReminderEnabled('notification_reminder_7days', 'notification_reminder_7day'),
                'template' => 'reminder_7_day',
                'match' => static function ($r) {
                    return (string) $r->expiration === date('Y-m-d', strtotime('+7 days'));
                },
            ],
            '3d' => [
                'enabled' => self::isReminderEnabled('notification_reminder_3days', 'notification_reminder_3day'),
                'template' => 'reminder_3_day',
                'match' => static function ($r) {
                    return (string) $r->expiration === date('Y-m-d', strtotime('+3 days'));
                },
            ],
            '24h' => [
                'enabled' => self::isReminderEnabled('notification_reminder_24h', 'notification_reminder_1day'),
                'template' => 'reminder_24h',
                'match' => static function ($r) {
                    $expiryTs = strtotime(trim((string) $r->expiration . ' ' . (string) $r->time));
                    if ($expiryTs === false) {
                        return false;
                    }
                    $secondsLeft = $expiryTs - time();
                    // Fenêtre 23 h – 25 h avant expiration (cron toutes les 5 min).
                    return $secondsLeft > 0 && $secondsLeft >= 23 * 3600 && $secondsLeft <= 25 * 3600;
                },
            ],
        ];

        $recharges = ORM::for_table('tbl_user_recharges')
            ->where('status', 'on')
            ->where_not_equal('customer_id', 0)
            ->find_many();

        foreach ($recharges as $r) {
            foreach ($intervals as $key => $spec) {
                if (!$spec['enabled'] || !$spec['match']($r)) {
                    continue;
                }
                if (self::wasReminderSent((int) $r->id, $key)) {
                    continue;
                }
                if (self::sendRenewalReminder($r, $spec['template'], $via)) {
                    self::markReminderSent((int) $r->id, $key);
                }
            }
        }
    }

    private static function isReminderEnabled($primaryKey, $legacyKey = null)
    {
        global $config;
        foreach (array_filter([$primaryKey, $legacyKey]) as $key) {
            if (isset($config[$key]) && $config[$key] === 'no') {
                return false;
            }
        }
        return true;
    }

    private static function wasReminderSent($rechargeId, $reminderKey)
    {
        return ORM::for_table('wifizone_renewal_reminders')
            ->where('recharge_id', $rechargeId)
            ->where('reminder_key', $reminderKey)
            ->find_one() !== false;
    }

    private static function markReminderSent($rechargeId, $reminderKey)
    {
        try {
            $row = ORM::for_table('wifizone_renewal_reminders')->create();
            $row->recharge_id = $rechargeId;
            $row->reminder_key = $reminderKey;
            $row->sent_at = date('Y-m-d H:i:s');
            $row->save();
        } catch (Throwable $e) {
            // Unique constraint — already sent by a parallel cron run.
        }
    }

    private static function sendRenewalReminder($recharge, $templateKey, $via)
    {
        global $config, $ds;

        $customer = ORM::for_table('tbl_customers')->find_one($recharge->customer_id);
        if (!$customer) {
            return false;
        }

        $plan = ORM::for_table('tbl_plans')->find_one($recharge->plan_id);
        if (!$plan) {
            return false;
        }

        if ($plan['validity_unit'] === 'Period') {
            $addInv = User::getAttribute('Invoice', $recharge->customer_id);
            $price = (empty($addInv) || $addInv == 0) ? $plan['price'] : $addInv;
        } else {
            $price = $plan['price'];
        }

        $message = Lang::getNotifText($templateKey);
        if ($message === '' && $templateKey === 'reminder_24h') {
            $message = Lang::getNotifText('reminder_1_day');
        }
        if ($message === '') {
            return false;
        }

        $ds = $recharge->as_array();
        try {
            Message::sendPackageNotification(
                $customer->as_array(),
                $plan['name_plan'],
                $price,
                Message::getMessageType($plan['type'], $message),
                $via
            );
            return true;
        } catch (Throwable $e) {
            WifiZoneLogger::note('renewal reminder for ' . $recharge->username, $e);
            if (function_exists('sendTelegram')) {
                sendTelegram('Renewal reminder failed for ' . $recharge->username . ': ' . $e->getMessage());
            }
            return false;
        }
    }

    public static function notifyCustomer($customer, $message)
    {
        try {
            if (class_exists('Message')) {
                if (!empty($customer->phonenumber)) {
                    Message::sendSMS($customer->phonenumber, $message);
                    if (method_exists('Message', 'sendWhatsapp')) {
                        Message::sendWhatsapp($customer->phonenumber, $message);
                    }
                }
                if (!empty($customer->email) && method_exists('Message', 'sendEmail')) {
                    Message::sendEmail($customer->email, Lang::T('Package reminder'), $message);
                }
            }
        } catch (Throwable $e) {
            WifiZoneLogger::note('customer notification' . (isset($customer->id) ? ' for customer ' . $customer->id : ''), $e);
        }
    }

    public static function checkGenieacsOffline()
    {
        try {
            $minutes = (int) WifiZoneCore::config('wifizone_genieacs_alert_minutes', 30);
            if ($minutes <= 0) {
                return;
            }
            $threshold = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));
            $devices = ORM::for_table('tbl_acs_devices')
                ->where_raw('(COALESCE(last_inform, last_sync) IS NULL OR COALESCE(last_inform, last_sync) < ?)', [$threshold])
                ->limit(50)
                ->find_many();
            foreach ($devices as $d) {
                $msg = "ONU offline > {$minutes}min: " . ($d->device_id ?? $d->id);
                if (class_exists('Message')) {
                    Message::sendTelegram($msg);
                }
            }
        } catch (Exception $e) {
            // GenieACS tables optional
        }
    }

    public static function linkCustomerToDevice($customerId, $mac = null, $acsDeviceId = null)
    {
        if ($mac) {
            $mac = strtolower(preg_replace('/[^a-fA-F0-9:]/', '', $mac));
        }
        $row = ORM::for_table('wifizone_customer_devices')->create();
        $row->customer_id = $customerId;
        $row->mac = $mac;
        $row->acs_device_id = $acsDeviceId;
        $row->save();
        WifiZoneAudit::log('link_device', 'customer', $customerId, ['mac' => $mac, 'acs' => $acsDeviceId]);
        return $row->id;
    }
}
