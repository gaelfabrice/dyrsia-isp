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
        $current_date = date('Y-m-d');
        $start_date = date('Y-m-01');
        $isAdmin = ($admin['user_type'] != 'SuperAdmin');

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
            $dailyQuery->where('admin_id', $adminId);
            $monthlyQuery->where('admin_id', $adminId);
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

        $ui->assign('iday', $dailyQuery->sum('price') ?: 0);
        $ui->assign('imonth', $monthlyQuery->sum('price') ?: 0);
        $ui->assign('w_balance', $w_balance);
        $ui->assign('w_commission', $w_commission);
        require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . 'graph_monthly_sales.php';
        $ui->assign('monthly_sales_widget', (new graph_monthly_sales())->getWidget());
        $ui->display('admin/finance.tpl');
        break;
}
