<?php

class customer_expired
{
    private function scopeRecharges($query, $admin)
    {
        if (!empty($admin) && ($admin['user_type'] ?? '') !== 'SuperAdmin') {
            $query->where('tur.admin_id', (int) $admin['id']);
        }
        return $query;
    }

    private function expiryRoutePath()
    {
        global $routes;
        if (!empty($routes[0]) && $routes[0] === 'monitoring') {
            return (!empty($routes[1]) && $routes[1] === 'expiry') ? 'monitoring/expiry' : 'monitoring';
        }
        return 'dashboard';
    }

    private function buildExpUrl($page)
    {
        $log_page = isset($_GET['log_page']) ? max((int) $_GET['log_page'], 1) : 1;
        return getUrl($this->expiryRoutePath() . '&exp_page=' . max(1, (int) $page) . '&log_page=' . $log_page);
    }

    private function classifyExpiryStatus($expirationAt, $nowTs, $soonTs, $comingTs)
    {
        $expTs = strtotime($expirationAt);
        if ($expTs === false) {
            return 'expired';
        }
        if ($expTs <= $nowTs) {
            return 'expired';
        }
        if ($expTs <= $soonTs) {
            return 'soon';
        }
        if ($expTs <= $comingTs) {
            return 'coming';
        }
        return 'coming';
    }

    private function expirySortKey($row)
    {
        $priority = ['expired' => 0, 'soon' => 1, 'coming' => 2];
        $statusRank = $priority[$row['status']] ?? 3;
        $expTs = strtotime($row['expiration_at']);
        if ($row['status'] === 'expired') {
            return [$statusRank, -$expTs];
        }
        return [$statusRank, $expTs];
    }

    /**
     * Data for monitoring/expiry full page (unified table).
     */
    public function prepareMonitoringPage()
    {
        global $ui, $admin;

        if (DemoShowcase::isActive($admin)) {
            DemoShowcase::assignMonitoringExpiry($ui);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $nowTs = time();
        $soonTs = strtotime('+3 days');
        $comingTs = strtotime('+7 days');
        $windowEnd = date('Y-m-d H:i:s', $comingTs);

        $page = isset($_GET['exp_page']) ? max((int) $_GET['exp_page'], 1) : 1;
        $per_page = 10;

        $selects = [
            'c.id', 'tur.username', 'c.fullname', 'c.phonenumber', 'c.email',
            'tur.expiration', 'tur.time', 'tur.namebp', 'tur.routers',
        ];

        $expiredCountQuery = ORM::for_table('tbl_user_recharges')
            ->table_alias('tur')
            ->innerJoin('tbl_customers', ['tur.customer_id', '=', 'c.id'], 'c')
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) <= ?", [$now]);
        $this->scopeRecharges($expiredCountQuery, $admin);
        $total_expired = (int) $expiredCountQuery->count();

        $comingCountQuery = ORM::for_table('tbl_user_recharges')
            ->table_alias('tur')
            ->innerJoin('tbl_customers', ['tur.customer_id', '=', 'c.id'], 'c')
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) > ?", [$now])
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) <= ?", [$windowEnd]);
        $this->scopeRecharges($comingCountQuery, $admin);
        $total_coming = (int) $comingCountQuery->count();

        $listQuery = ORM::for_table('tbl_user_recharges')
            ->table_alias('tur')
            ->innerJoin('tbl_customers', ['tur.customer_id', '=', 'c.id'], 'c')
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) <= ?", [$windowEnd]);
        $this->scopeRecharges($listQuery, $admin);
        $records = $listQuery->selects($selects)->find_many();

        $rows = [];
        foreach ($records as $record) {
            $expirationAt = trim($record['expiration'] . ' ' . $record['time']);
            $status = $this->classifyExpiryStatus($expirationAt, $nowTs, $soonTs, $comingTs);
            $rows[] = [
                'id' => (int) $record['id'],
                'username' => (string) $record['username'],
                'fullname' => (string) $record['fullname'],
                'phonenumber' => (string) $record['phonenumber'],
                'email' => (string) $record['email'],
                'namebp' => (string) $record['namebp'],
                'routers' => (string) $record['routers'],
                'expiration_at' => $expirationAt,
                'status' => $status,
            ];
        }

        usort($rows, function ($a, $b) {
            $ka = $this->expirySortKey($a);
            $kb = $this->expirySortKey($b);
            return $ka[0] <=> $kb[0] ?: $ka[1] <=> $kb[1];
        });

        $total_entries = count($rows);
        $max_pages = max(1, (int) ceil($total_entries / $per_page));
        if ($page > $max_pages) {
            $page = $max_pages;
        }
        $offset = ($page - 1) * $per_page;
        $page_rows = array_slice($rows, $offset, $per_page);

        $ui->assign('exp_total_expired', $total_expired);
        $ui->assign('exp_total_coming', $total_coming);
        $ui->assign('exp_rows', $page_rows);
        $ui->assign('exp_total_entries', $total_entries);
        $ui->assign('exp_current_page', $page);
        $ui->assign('exp_max_pages', $max_pages);
        $ui->assign('exp_prev_url', $this->buildExpUrl($page - 1));
        $ui->assign('exp_next_url', $this->buildExpUrl($page + 1));
        $ui->assign('exp_today_label', date('d M Y'));
    }

    public function getWidget()
    {
        global $ui, $current_date, $config, $admin;

        if (DemoShowcase::isActive($admin ?? null)) {
            DemoShowcase::assignCustomerExpiryWidget($ui);
            return $ui->fetch('widget/customer_expired.tpl');
        }

        $now = date('Y-m-d H:i:s');
        $three_days_later_dt = date('Y-m-d H:i:s', strtotime('+3 days'));

        $page = isset($_GET['exp_page']) ? max((int) $_GET['exp_page'], 1) : 1;
        $per_page = 5;
        $offset = ($page - 1) * $per_page;

        $already_expired_base = ORM::for_table('tbl_user_recharges')
            ->table_alias('tur')
            ->innerJoin('tbl_customers', ['tur.customer_id', '=', 'c.id'], 'c')
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) <= ?", [$now]);

        $total_already = $already_expired_base->count();

        $already_expired = $already_expired_base
            ->selects(['c.id', 'tur.username', 'c.fullname', 'c.phonenumber', 'c.email', 'tur.expiration', 'tur.time', 'tur.recharged_on', 'tur.recharged_time', 'tur.namebp', 'tur.routers'])
            ->order_by_desc('expiration')
            ->limit($per_page)
            ->offset($offset)
            ->find_many();

        $coming_expired_base = ORM::for_table('tbl_user_recharges')
            ->table_alias('tur')
            ->innerJoin('tbl_customers', ['tur.customer_id', '=', 'c.id'], 'c')
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) > ?", [$now])
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) <= ?", [$three_days_later_dt]);

        $total_coming = $coming_expired_base->count();

        $coming_expired = $coming_expired_base
            ->selects(['c.id', 'tur.username', 'c.fullname', 'c.phonenumber', 'c.email', 'tur.expiration', 'tur.time', 'tur.recharged_on', 'tur.recharged_time', 'tur.namebp', 'tur.routers'])
            ->order_by_asc('expiration')
            ->limit($per_page)
            ->offset($offset)
            ->find_many();

        $total_pages_already = ceil($total_already / $per_page);
        $total_pages_coming = ceil($total_coming / $per_page);
        $max_pages = max($total_pages_already, $total_pages_coming);

        $pagination_limit = 5;
        $start_page = max(1, $page - floor($pagination_limit / 2));
        $end_page = min($max_pages, $start_page + $pagination_limit - 1);

        if ($end_page - $start_page + 1 < $pagination_limit) {
            $start_page = max(1, $end_page - $pagination_limit + 1);
        }

        $ui->assign('already_expired', $already_expired);
        $ui->assign('coming_expired', $coming_expired);
        $ui->assign('total_already', $total_already);
        $ui->assign('total_coming', $total_coming);
        $ui->assign('exp_current_page', $page);
        $ui->assign('max_pages', max(1, (int) $max_pages));
        $ui->assign('start_page', $start_page);
        $ui->assign('end_page', $end_page);
        $ui->assign('exp_prev_url', $this->buildExpUrl($page - 1));
        $ui->assign('exp_next_url', $this->buildExpUrl($page + 1));

        return $ui->fetch('widget/customer_expired.tpl');
    }
}
