<?php

class customer_expired
{
    public function getWidget()
    {
        global $ui, $current_date, $config;

        $now = date('Y-m-d H:i:s');
        $three_days_later_dt = date('Y-m-d H:i:s', strtotime('+3 days'));
        
        // Pagination variables
        $page = isset($_GET['exp_page']) ? max((int)$_GET['exp_page'],1) : 1;
        
        // আপনার চাহিদা অনুযায়ী প্রতি পেজে ৫ জন
        $per_page = 5; 
        $offset = ($page - 1) * $per_page;

        // --- Already Expired Query ---
        $already_expired_base = ORM::for_table('tbl_user_recharges')
            ->table_alias('tur')
            ->innerJoin('tbl_customers', ['tur.customer_id', '=', 'c.id'], 'c')
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) <= ?", [$now]);

        // টোটাল কাউন্ট (এটি ১০০ পেজ হলেও সঠিক সংখ্যা দিবে)
        $total_already = $already_expired_base->count();

        // পেজ অনুযায়ী ৫ জন ডাটা
        $already_expired = $already_expired_base
            ->selects(['c.id','tur.username','c.fullname','c.phonenumber','c.email','tur.expiration','tur.time','tur.recharged_on','tur.recharged_time','tur.namebp','tur.routers'])
            ->order_by_desc('expiration')
            ->limit($per_page)
            ->offset($offset)
            ->find_many();

        // --- Coming Expired Query ---
        $coming_expired_base = ORM::for_table('tbl_user_recharges')
            ->table_alias('tur')
            ->innerJoin('tbl_customers', ['tur.customer_id', '=', 'c.id'], 'c')
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) > ?", [$now])
            ->where_raw("CONCAT(tur.expiration,' ',tur.time) <= ?", [$three_days_later_dt]);

        // টোটাল কাউন্ট
        $total_coming = $coming_expired_base->count();

        // পেজ অনুযায়ী ৫ জন ডাটা
        $coming_expired = $coming_expired_base
            ->selects(['c.id','tur.username','c.fullname','c.phonenumber','c.email','tur.expiration','tur.time','tur.recharged_on','tur.recharged_time','tur.namebp','tur.routers'])
            ->order_by_asc('expiration')
            ->limit($per_page)
            ->offset($offset)
            ->find_many();

        // ক্যালকুলেশন
        $total_pages_already = ceil($total_already / $per_page);
        $total_pages_coming = ceil($total_coming / $per_page);
        $max_pages = max($total_pages_already, $total_pages_coming);

        // Pagination UI Logic (৫টি পেজ নাম্বার দেখানোর জন্য)
        $pagination_limit = 5;
        $start_page = max(1, $page - floor($pagination_limit / 2));
        $end_page = min($max_pages, $start_page + $pagination_limit - 1);

        if ($end_page - $start_page + 1 < $pagination_limit) {
            $start_page = max(1, $end_page - $pagination_limit + 1);
        }

        // Smarty তে ডাটা পাঠানো
        $ui->assign('already_expired', $already_expired);
        $ui->assign('coming_expired', $coming_expired);
        
        // টোটাল কাউন্ট (এই ভেরিয়েবলগুলো বক্সে ব্যবহার করবেন)
        $ui->assign('total_already', $total_already);
        $ui->assign('total_coming', $total_coming);
        
        $ui->assign('exp_current_page', $page);
        $ui->assign('max_pages', max(1, (int) $max_pages));
        $ui->assign('start_page', $start_page);
        $ui->assign('end_page', $end_page);

        global $routes;
        $routePath = 'dashboard';
        if (!empty($routes[0]) && $routes[0] === 'monitoring') {
            $routePath = (!empty($routes[1]) && $routes[1] === 'expiry') ? 'monitoring/expiry' : 'monitoring';
        }
        $log_page = isset($_GET['log_page']) ? max((int) $_GET['log_page'], 1) : 1;
        $buildExpUrl = function ($expPage) use ($routePath, $log_page) {
            $expPage = max(1, (int) $expPage);
            return getUrl($routePath . '&exp_page=' . $expPage . '&log_page=' . $log_page);
        };
        $ui->assign('exp_prev_url', $buildExpUrl($page - 1));
        $ui->assign('exp_next_url', $buildExpUrl($page + 1));

        return $ui->fetch('widget/customer_expired.tpl');
    }
}