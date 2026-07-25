<?php

_admin();
$ui->assign('_system_menu', 'finance');
$ui->assign('_admin', $admin);

Withdrawal::ensureSchema();

$action = $routes['1'] ?? '';
$adminId = (int) $admin['id'];
$isSuperAdmin = ($admin['user_type'] === 'SuperAdmin');

switch ($action) {
    case 'withdrawals':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'], true)) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        if ($isSuperAdmin && (_req('preview') !== '1')) {
            r2(getUrl('finance/reversement'), 'i', 'Les retraits clients se gèrent via Reversement (SuperAdmin).');
        }
        if (DemoShowcase::isActive($admin)) {
            $demoStats = DemoShowcase::stats();
            $csrf_token = Csrf::generateAndStoreToken();
            $ui->assign('_title', 'Retraits');
            $ui->assign('withdrawal_profile', null);
            $ui->assign('withdrawal_available', (float) $demoStats['w_balance']);
            $ui->assign('withdrawal_approved_total', (float) $demoStats['withdrawals_approved']);
            $ui->assign('withdrawal_sales', ['hotspot' => $demoStats['iday'], 'pppoe' => (int) ($demoStats['imonth'] / 30)]);
            $ui->assign('withdrawal_requests', DemoShowcase::dataset()['withdrawals'] ?? []);
            $ui->assign('withdrawal_min', Withdrawal::minAmount());
            $ui->assign('withdrawal_operators', Withdrawal::OPERATORS);
            $ui->assign('withdrawal_commission_label', Withdrawal::commissionLabel());
            $ui->assign('withdrawal_can_submit', false);
            $ui->assign('csrf_token', $csrf_token);
            $ui->display('admin/finance/withdrawals.tpl');
            break;
        }
        $profile = Withdrawal::getProfile($adminId);
        $available = Withdrawal::availableBalance($adminId);
        $approvedTotal = Withdrawal::sumApproved($adminId);
        $sales = Withdrawal::salesBreakdown($adminId);
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('_title', 'Retraits');
        $ui->assign('withdrawal_profile', $profile);
        $ui->assign('withdrawal_available', $available);
        $ui->assign('withdrawal_approved_total', $approvedTotal);
        $ui->assign('withdrawal_sales', $sales);
        $ui->assign('withdrawal_requests', Withdrawal::requestsForAdmin($adminId));
        $ui->assign('withdrawal_min', Withdrawal::minAmount());
        $ui->assign('withdrawal_operators', Withdrawal::OPERATORS);
        $ui->assign('withdrawal_commission_label', Withdrawal::commissionLabel());
        $ui->assign('withdrawal_can_submit', $available >= Withdrawal::minAmount() && $profile && (int) ($profile->locked ?? 0) === 1);
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/finance/withdrawals.tpl');
        break;

    case 'withdrawals-profile-post':
        if ($admin['user_type'] !== 'Admin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('finance/withdrawals'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            Withdrawal::saveProfile($adminId, [
                'first_name' => _post('first_name'),
                'last_name' => _post('last_name'),
                'phone' => _post('phone'),
                'operator' => _post('operator'),
            ]);
            r2(getUrl('finance/withdrawals'), 's', 'Profil de retrait enregistré et verrouillé.');
        } catch (Exception $e) {
            r2(getUrl('finance/withdrawals'), 'e', $e->getMessage());
        }
        break;

    case 'withdrawals-request-post':
        if ($admin['user_type'] !== 'Admin') {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('finance/withdrawals'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            Withdrawal::submitRequest($adminId, _post('amount'), _post('note'));
            r2(getUrl('finance/withdrawals'), 's', 'Demande de retrait soumise. Le montant a été bloqué en attendant validation.');
        } catch (Exception $e) {
            r2(getUrl('finance/withdrawals'), 'e', $e->getMessage());
        }
        break;

    case 'reversement':
        if (!$isSuperAdmin) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        if ((int) _req('notification') > 0) {
            Withdrawal::markNotificationRead((int) _req('notification'));
        }
        Withdrawal::expireStaleRequests(false);
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('_title', 'Reversement');
        $ui->assign('withdrawal_stats', Withdrawal::platformStats());
        $ui->assign('withdrawal_profiles', Withdrawal::profilesForSuperAdmin());
        $ui->assign('withdrawal_pending', Withdrawal::pendingForSuperAdmin());
        $ui->assign('withdrawal_history', Withdrawal::allRequestsForSuperAdmin(null, 100));
        $ui->assign('withdrawal_operators', Withdrawal::OPERATORS);
        $searchTerm = trim((string) _req('q'));
        $searchItems = [];
        foreach (Withdrawal::searchAdmins($searchTerm) as $sa) {
            $searchItems[] = [
                'admin' => $sa,
                'profile' => Withdrawal::getProfile((int) $sa->id),
            ];
        }
        $ui->assign('withdrawal_search_term', $searchTerm);
        $ui->assign('withdrawal_search_items', $searchItems);
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/finance/reversement.tpl');
        break;

    case 'reversement-approve-post':
        if (!$isSuperAdmin) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('finance/reversement'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            Withdrawal::approveRequest((int) _post('request_id'), $adminId, _post('transaction_id'), _post('comment'));
            r2(getUrl('finance/reversement'), 's', 'Reversement validé.');
        } catch (Exception $e) {
            r2(getUrl('finance/reversement'), 'e', $e->getMessage());
        }
        break;

    case 'reversement-reject-post':
        if (!$isSuperAdmin) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('finance/reversement'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            Withdrawal::rejectRequest((int) _post('request_id'), $adminId, _post('comment'));
            r2(getUrl('finance/reversement'), 's', 'Demande rejetée. Le montant a été recrédité sur le solde disponible.');
        } catch (Exception $e) {
            r2(getUrl('finance/reversement'), 'e', $e->getMessage());
        }
        break;

    case 'reversement-lock-post':
        if (!$isSuperAdmin) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('finance/reversement'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            $targetAdminId = (int) _post('admin_id');
            $locked = (int) _post('locked') === 1;
            Withdrawal::setProfileLock($targetAdminId, $locked);
            $msg = $locked
                ? 'Profil bénéficiaire verrouillé (ON).'
                : 'Profil déverrouillé (OFF). Le client peut mettre à jour son bénéficiaire.';
            r2(getUrl('finance/reversement'), 's', $msg);
        } catch (Exception $e) {
            r2(getUrl('finance/reversement'), 'e', $e->getMessage());
        }
        break;

    case 'reversement-profile-post':
        if (!$isSuperAdmin) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('finance/reversement'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            Withdrawal::saveProfile((int) _post('admin_id'), [
                'first_name' => _post('first_name'),
                'last_name' => _post('last_name'),
                'phone' => _post('phone'),
                'operator' => _post('operator'),
            ], true);
            r2(getUrl('finance/reversement') . '&q=' . urlencode((string) _post('search_term')), 's', 'Profil de retrait mis à jour.');
        } catch (Exception $e) {
            r2(getUrl('finance/reversement'), 'e', $e->getMessage());
        }
        break;

    case 'notification-read':
        if (!$isSuperAdmin) {
            header('HTTP/1.0 403 Forbidden');
            die('Forbidden');
        }
        Withdrawal::markNotificationRead((int) _req('id'));
        r2(getUrl('finance/reversement'));
        break;

    default:
        $ui->assign('_title', Lang::T('Finance'));
        if (DemoShowcase::isActive($admin)) {
            DemoShowcase::applyFinance($ui);
            require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . 'graph_monthly_sales.php';
            $ui->assign('monthly_sales_widget', (new graph_monthly_sales())->getWidget());
            $ui->display('admin/finance.tpl');
            break;
        }
        $current_date = date('Y-m-d');
        $start_date = date('Y-m-01');
        $isAdmin = ($admin['user_type'] != 'SuperAdmin');

        if (class_exists('WifiZoneSales')) {
            $iday = WifiZoneSales::sumIncomeForDay($admin, $current_date);
            $imonth = WifiZoneSales::sumIncomeForPeriod($admin, $start_date, $current_date);
            $incomeYesterday = WifiZoneSales::sumIncomeForDay($admin, date('Y-m-d', strtotime('-1 day')));
            $incomePrevMonth = WifiZoneSales::sumIncomeForPeriod(
                $admin,
                date('Y-m-01', strtotime('-1 month')),
                date('Y-m-t', strtotime('-1 month'))
            );
        } else {
            $dailyQuery = ORM::for_table('tbl_transactions')
                ->where('recharged_on', $current_date)
                ->where_not_equal('method', 'Customer - Balance')
                ->where_not_equal('method', 'Recharge Balance - Administrator');
            $monthlyQuery = ORM::for_table('tbl_transactions')
                ->where_not_equal('method', 'Customer - Balance')
                ->where_not_equal('method', 'Recharge Balance - Administrator')
                ->where_gte('recharged_on', $start_date)
                ->where_lte('recharged_on', $current_date);
            if ($isAdmin) {
                AdminScope::applyTransactionsQueryByAdminId($dailyQuery, $adminId);
                AdminScope::applyTransactionsQueryByAdminId($monthlyQuery, $adminId);
            }
            $iday = (float) ($dailyQuery->sum('price') ?: 0);
            $imonth = (float) ($monthlyQuery->sum('price') ?: 0);

            $yesterdayQuery = ORM::for_table('tbl_transactions')
                ->where('recharged_on', date('Y-m-d', strtotime('-1 day')))
                ->where_not_equal('method', 'Customer - Balance')
                ->where_not_equal('method', 'Recharge Balance - Administrator');
            $prevMonthQuery = ORM::for_table('tbl_transactions')
                ->where_not_equal('method', 'Customer - Balance')
                ->where_not_equal('method', 'Recharge Balance - Administrator')
                ->where_gte('recharged_on', date('Y-m-01', strtotime('-1 month')))
                ->where_lte('recharged_on', date('Y-m-t', strtotime('-1 month')));
            if ($isAdmin) {
                AdminScope::applyTransactionsQueryByAdminId($yesterdayQuery, $adminId);
                AdminScope::applyTransactionsQueryByAdminId($prevMonthQuery, $adminId);
            }
            $incomeYesterday = (float) ($yesterdayQuery->sum('price') ?: 0);
            $incomePrevMonth = (float) ($prevMonthQuery->sum('price') ?: 0);
        }

        $w_balance = 0;
        $w_commission = 0;
        try {
            if ($isSuperAdmin) {
                $w_balance = ORM::for_table('admin_wallet')->sum('balance') ?: 0;
                $w_commission = ORM::for_table('admin_wallet')->sum('commission_balance') ?: 0;
            } else {
                $wallet = ORM::for_table('admin_wallet')->where('admin_id', $adminId)->find_one();
                if ($wallet) {
                    $w_balance = $wallet->balance;
                    $w_commission = $wallet->commission_balance;
                }
            }
        } catch (Exception $e) {
        }

        $growthDaily = $incomeYesterday > 0 ? round((($iday - $incomeYesterday) / $incomeYesterday) * 100) : ($iday > 0 ? 100 : 0);
        $growthMonthly = $incomePrevMonth > 0 ? round((($imonth - $incomePrevMonth) / $incomePrevMonth) * 100) : ($imonth > 0 ? 100 : 0);

        $db = ORM::get_db();
        $monthlyRevenue = array_fill(0, 12, 0.0);
        $monthlyCommission = array_fill(0, 12, 0.0);
        $revSql = "SELECT MONTH(recharged_on) AS m, SUM(price) AS total
            FROM tbl_transactions
            WHERE YEAR(recharged_on) = YEAR(CURRENT_DATE())
              AND method <> 'Customer - Balance'
              AND method <> 'Recharge Balance - Administrator'";
        $revParams = [];
        if ($isAdmin) {
            [$revScopeSql, $revScopeParams] = AdminScope::transactionsSqlFilter($adminId);
            $revSql .= ' AND ' . $revScopeSql;
            $revParams = $revScopeParams;
        }
        $revSql .= " GROUP BY MONTH(recharged_on)";
        $revStmt = $db->prepare($revSql);
        $revStmt->execute($revParams);
        foreach ($revStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $idx = max(1, (int) ($row['m'] ?? 0)) - 1;
            if ($idx >= 0 && $idx < 12) {
                $monthlyRevenue[$idx] = round((float) ($row['total'] ?? 0), 2);
            }
        }
        $monthlyRevenue[(int) date('n') - 1] = round((float) $imonth, 2);

        $comSql = "SELECT MONTH(recharged_on) AS m, SUM(price) AS total
            FROM tbl_transactions
            WHERE YEAR(recharged_on) = YEAR(CURRENT_DATE())
              AND LOWER(method) LIKE '%commission%'";
        $comParams = [];
        if ($isAdmin) {
            [$comScopeSql, $comScopeParams] = AdminScope::transactionsSqlFilter($adminId);
            $comSql .= ' AND ' . $comScopeSql;
            $comParams = $comScopeParams;
        }
        $comSql .= " GROUP BY MONTH(recharged_on)";
        $comStmt = $db->prepare($comSql);
        $comStmt->execute($comParams);
        foreach ($comStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $idx = max(1, (int) ($row['m'] ?? 0)) - 1;
            if ($idx >= 0 && $idx < 12) {
                $monthlyCommission[$idx] = round((float) ($row['total'] ?? 0), 2);
            }
        }

        $ui->assign('iday', $iday);
        $ui->assign('imonth', $imonth);
        $ui->assign('w_balance', $w_balance);
        $ui->assign('w_commission', $w_commission);
        $ui->assign('income_yesterday', $incomeYesterday);
        $ui->assign('income_prev_month', $incomePrevMonth);
        $ui->assign('growth_daily_pct', $growthDaily);
        $ui->assign('growth_monthly_pct', $growthMonthly);
        $ui->assign('finance_month_labels', ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']);
        $ui->assign('finance_month_revenue', $monthlyRevenue);
        $ui->assign('finance_month_commission', $monthlyCommission);
        $ui->assign('finance_now_label', date('H:i'));
        require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . 'graph_monthly_sales.php';
        $ui->assign('monthly_sales_widget', (new graph_monthly_sales())->getWidget());
        $ui->display('admin/finance.tpl');
        break;
}
