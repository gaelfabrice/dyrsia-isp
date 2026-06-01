<?php

class wifizone_kpi
{
    public function getWidget($data = null)
    {
        global $admin;
        $cacheKey = 'kpi_' . ($admin['id'] ?? 0) . '_' . date('Y-m-d-H');
        $cached = WifiZoneCache::get($cacheKey, 300);
        if ($cached) {
            return $this->render($cached);
        }
        $isAdmin = ($admin['user_type'] != 'SuperAdmin');
        $adminId = (int) ($admin['id'] ?? 0);

        $rechargesQ = ORM::for_table('tbl_user_recharges')->where('status', 'on');
        $salesTodayQ = ORM::for_table('tbl_transactions')->where('recharged_on', date('Y-m-d'));
        $salesMonthQ = ORM::for_table('tbl_transactions')->where_gte('recharged_on', date('Y-m-01'));
        $routersQ = ORM::for_table('tbl_routers')->where('enabled', 1);
        if ($isAdmin) {
            $rechargesQ->where('admin_id', $adminId);
            $salesTodayQ->where('admin_id', $adminId);
            $salesMonthQ->where('admin_id', $adminId);
            $routersQ->where('admin_id', $adminId);
        }

        $kpi = [
            'active_customers' => $rechargesQ->count(),
            'sales_today' => $salesTodayQ->sum('price') ?: 0,
            'sales_month' => $salesMonthQ->sum('price') ?: 0,
            'routers_offline' => 0,
            'open_tickets' => 0,
        ];
        $routers = $routersQ->find_many();
        foreach ($routers as $r) {
            $parts = explode(':', $r->ip_address);
            $host = $parts[0];
            $port = $parts[1] ?? 8728;
            $fp = @fsockopen($host, $port, $errno, $errstr, 1);
            if (!$fp) {
                $kpi['routers_offline']++;
            } else {
                fclose($fp);
            }
        }
        try {
            $ticketsQ = ORM::for_table('tbl_support_tickets')->where('status', 'open');
            if ($isAdmin) {
                $customerIds = ORM::for_table('tbl_customers')
                    ->where('created_by', $adminId)
                    ->select('id')
                    ->find_array();
                $ids = array_column($customerIds, 'id');
                if (empty($ids)) {
                    $kpi['open_tickets'] = 0;
                } else {
                    $kpi['open_tickets'] = $ticketsQ->where_in('userid', $ids)->count();
                }
            } else {
                $kpi['open_tickets'] = $ticketsQ->count();
            }
        } catch (Exception $e) {
        }
        WifiZoneCache::set($cacheKey, $kpi, 300);
        return $this->render($kpi);
    }

    private function render($kpi)
    {
        $cur = WifiZoneCore::config('currency_code', 'USD');
        $box = function ($color, $icon, $label, $value) {
            return '<div class="col-md-3 col-sm-6"><div class="info-box ' . $color . '">'
                . '<span class="info-box-icon"><i class="' . $icon . '"></i></span>'
                . '<div class="info-box-content"><span class="info-box-text">' . htmlspecialchars($label) . '</span>'
                . '<span class="info-box-number">' . htmlspecialchars((string) $value) . '</span></div></div></div>';
        };
        return '<div class="wz-kpi"><div class="row">'
            . $box('bg-aqua', 'ion ion-person', Lang::T('Active_Customers'), (int) $kpi['active_customers'])
            . $box('bg-green', 'ion ion-cash', Lang::T('Sales Today'), $cur . ' ' . number_format($kpi['sales_today'], 2))
            . $box('bg-yellow', 'ion ion-wifi', Lang::T('Routers_Offline'), (int) $kpi['routers_offline'])
            . $box('bg-red', 'ion ion-help-buoy', Lang::T('Open Tickets'), (int) $kpi['open_tickets'])
            . '</div></div>';
    }
}
