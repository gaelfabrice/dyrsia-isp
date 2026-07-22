<?php

/**
 * Checklist onboarding admin (Phase C).
 */
class wifizone_setup
{
    public function getWidget()
    {
        global $admin, $config, $UPLOAD_PATH;

        if (($admin['user_type'] ?? '') === 'SuperAdmin') {
            return '';
        }

        $checks = [
            'router' => ORM::for_table('tbl_routers')->where('enabled', 1)->count() > 0,
            'plan' => ORM::for_table('tbl_plans')->where('enabled', 1)->count() > 0,
            'payment' => !empty($config['payment_gateway']),
            'whatsapp' => !empty($config['whatsapp_gateway_url']) && count(glob($UPLOAD_PATH . '/whatsapp/*.nux') ?: []) > 0,
            'cron' => WifiZoneOps::isCronHeartbeatFresh(900),
            'reminder' => ($config['user_notification_reminder'] ?? '') !== '' && ($config['user_notification_reminder'] ?? '') !== 'none',
        ];

        $done = count(array_filter($checks));
        $total = count($checks);
        if ($done >= $total) {
            return '';
        }

        $labels = [
            'router' => Lang::T('Add a MikroTik router'),
            'plan' => Lang::T('Create an internet plan'),
            'payment' => Lang::T('Configure payment gateway'),
            'whatsapp' => Lang::T('Connect WhatsApp Gateway'),
            'cron' => Lang::T('Verify cron is running'),
            'reminder' => Lang::T('Enable expiration reminders'),
        ];
        $links = [
            'router' => getUrl('routers/add'),
            'plan' => getUrl('services/add'),
            'payment' => getUrl('paymentgateway'),
            'whatsapp' => getUrl('plugin/whatsappGateway'),
            'cron' => getUrl('settings/app'),
            'reminder' => getUrl('settings/app'),
        ];

        $html = '<div class="box box-warning"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-magic"></i> '
            . Lang::T('Setup wizard') . ' (' . $done . '/' . $total . ')</h3></div><div class="box-body"><ul class="list-unstyled">';
        foreach ($checks as $key => $ok) {
            $icon = $ok ? 'fa-check text-green' : 'fa-circle-o text-muted';
            $html .= '<li style="margin-bottom:8px"><i class="fa ' . $icon . '"></i> ';
            if (!$ok && isset($links[$key])) {
                $html .= '<a href="' . htmlspecialchars($links[$key]) . '">' . htmlspecialchars($labels[$key]) . '</a>';
            } else {
                $html .= htmlspecialchars($labels[$key]);
            }
            $html .= '</li>';
        }
        $html .= '</ul></div></div>';
        return $html;
    }
}
