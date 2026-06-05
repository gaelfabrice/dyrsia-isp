<?php

class top_widget
{
    public function getWidget()
    {
        global $ui, $current_date, $start_date, $admin;

        if (DemoShowcase::isActive($admin)) {
            DemoShowcase::applyTopWidgetStats($ui);
            return $ui->fetch('widget/top_widget.tpl');
        }

        // ================= ADMIN FILTER =================

        $isAdmin = ($admin['user_type'] != 'SuperAdmin');
        $adminId = intval($admin['id']);

        // ================= DAILY INCOME =================

        $iday_q = ORM::for_table('tbl_transactions')
            ->where('recharged_on', $current_date)
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator');

        if ($isAdmin) {
            $iday_q->where('admin_id', $adminId);
        }

        $iday = $iday_q->sum('price') ?: '0.00';


        // ================= MONTHLY INCOME =================

        $imonth_q = ORM::for_table('tbl_transactions')
            ->where_not_equal('method', 'Customer - Balance')
            ->where_not_equal('method', 'Recharge Balance - Administrator')
            ->where_gte('recharged_on', $start_date)
            ->where_lte('recharged_on', $current_date);

        if ($isAdmin) {
            $imonth_q->where('admin_id', $adminId);
        }

        $imonth = $imonth_q->sum('price') ?: '0.00';


        // ================= ACTIVE USERS =================

        $u_act_q = ORM::for_table('tbl_user_recharges')
            ->where('status', 'on');

        if ($isAdmin) {
            $u_act_q->where('admin_id', $adminId);
        }

        $u_act = $u_act_q->count();


        // ================= TOTAL USERS =================

        $u_all_q = ORM::for_table('tbl_user_recharges');

        if ($isAdmin) {
            $u_all_q->where('admin_id', $adminId);
        }

        $u_all = $u_all_q->count();


        // ================= TOTAL CUSTOMERS =================

        $c_all_q = ORM::for_table('tbl_customers');

        if ($isAdmin) {
            $c_all_q->where('created_by', $adminId);
        }

        $c_all = $c_all_q->count();


        // ================= TOTAL HOTSPOT =================

        $h_query = ORM::for_table('tbl_user_recharges')
            ->where_raw("LOWER(type) = 'hotspot'")
            ->select('customer_id')
            ->distinct();

        if ($isAdmin) {
            $h_query->where('admin_id', $adminId);
        }

        $h_ids = $h_query->find_array();

        $h_all = count($h_ids);


        // ================= TOTAL PPPOE =================

        $p_query = ORM::for_table('tbl_user_recharges')
            ->where_raw("LOWER(type) = 'pppoe'")
            ->select('customer_id')
            ->distinct();

        if ($isAdmin) {
            $p_query->where('admin_id', $adminId);
        }

        $p_ids = $p_query->find_array();

        $p_all = count($p_ids);


        // ================= HOTSPOT ACTIVE =================

        $h_act_q = ORM::for_table('tbl_user_recharges')
            ->where('status', 'on')
            ->where('type', 'Hotspot');

        if ($isAdmin) {
            $h_act_q->where('admin_id', $adminId);
        }

        $h_act = $h_act_q->count();


        // ================= PPPOE ACTIVE =================

        $p_act_q = ORM::for_table('tbl_user_recharges')
            ->where('status', 'on')
            ->where('type', 'PPPoE');

        if ($isAdmin) {
            $p_act_q->where('admin_id', $adminId);
        }

        $p_act = $p_act_q->count();


        // ================= HOTSPOT EXPIRED =================

        $h_exp_q = ORM::for_table('tbl_user_recharges')
            ->where_not_equal('status', 'on')
            ->where('type', 'Hotspot');

        if ($isAdmin) {
            $h_exp_q->where('admin_id', $adminId);
        }

        $h_exp = $h_exp_q->count();


        // ================= PPPOE EXPIRED =================

        $p_exp_q = ORM::for_table('tbl_user_recharges')
            ->where_not_equal('status', 'on')
            ->where('type', 'PPPoE');

        if ($isAdmin) {
            $p_exp_q->where('admin_id', $adminId);
        }

        $p_exp = $p_exp_q->count();
        
        // ================= TOTAL STAFF COUNT LOGIC =================

$staff_count = 0;

if ($admin['user_type'] == 'SuperAdmin') {
    // সুপার অ্যাডমিন হলে: Admin এবং Sales দুই টাইপ যোগ করে মোট সংখ্যা দেখাবে
    $staff_count = ORM::for_table('tbl_users')
        ->where_in('user_type', ['Admin', 'Sales'])
        ->count();
} else {
    // সাধারণ অ্যাডমিন হলে: শুধুমাত্র Sales টাইপ ইউজারদের সংখ্যা দেখাবে
    $staff_count = ORM::for_table('tbl_users')
        ->where('user_type', 'Sales')
        ->count();
}

// টেম্পলেটে একটি মাত্র ভেরিয়েবল পাঠানো হচ্ছে
$ui->assign('staff_count', $staff_count);

        // ================= WALLET & COMMISSION LOGIC =================

        $w_balance = 0;
        $w_commission = 0;

        // বর্তমানে লগইন করা ইউজারের আইডি এবং টাইপ নিশ্চিত করা
        $current_aid = 0;
        $user_type = '';

        if (isset($admin['id'])) {
            $current_aid = intval($admin['id']);
            $user_type = $admin['user_type'];
        } elseif (isset($_SESSION['aid'])) {
            $current_aid = intval($_SESSION['aid']);
            $curr_user = ORM::for_table('tbl_admins')->find_one($current_aid);
            if ($curr_user) {
                $user_type = $curr_user->user_type;
            }
        }

        if ($current_aid > 0) {
            if ($user_type == 'SuperAdmin') {
                // ১. সুপার অ্যাডমিন হলে সবার ব্যালেন্স ও কমিশন যোগ করে দেখাবে
                $w_balance = ORM::for_table('admin_wallet')->sum('balance') ?: 0;
                $w_commission = ORM::for_table('admin_wallet')->sum('commission_balance') ?: 0;
            } else {
                // ২. সাধারণ অ্যাডমিন হলে শুধু নিজের ব্যালেন্স ও কমিশন দেখাবে
                $wallet = ORM::for_table('admin_wallet')
                    ->where('admin_id', $current_aid)
                    ->find_one();

                if ($wallet) {
                    $w_balance = $wallet->balance;
                    $w_commission = $wallet->commission_balance;
                }
            }
        }

        // ================= ASSIGN TO TEMPLATE =================

        $ui->assign('iday', $iday);
        $ui->assign('imonth', $imonth);

        $ui->assign('u_act', $u_act ?: '0');
        $ui->assign('u_all', $u_all ?: '0');

        $ui->assign('c_all', $c_all ?: '0');

        $ui->assign('h_all', $h_all ?: '0');
        $ui->assign('p_all', $p_all ?: '0');

        $ui->assign('h_act', $h_act ?: '0');
        $ui->assign('p_act', $p_act ?: '0');

        $ui->assign('h_exp', $h_exp ?: '0');
        $ui->assign('p_exp', $p_exp ?: '0');

        // Wallet Assign
        $ui->assign('w_balance', $w_balance);
        $ui->assign('w_commission', $w_commission);


        // ================= LOAD TEMPLATE =================

        return $ui->fetch('widget/top_widget.tpl');
    }
}