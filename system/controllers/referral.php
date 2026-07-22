<?php

/**
 * Dashboard parrainage pour les admins (user_type = Admin).
 * Routes :
 *   referral            → vue principale (solde, liens, historique)
 *   referral/withdraw   → soumettre une demande de retrait
 *   referral/mark-read  → marquer les notifs comme lues (AJAX)
 */

_admin();
if (!in_array($admin['user_type'], ['Admin', 'SuperAdmin'], true)) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

Referral::ensureSchema();

$action = $routes['1'] ?? '';

switch ($action) {

    case 'withdraw':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            r2(getUrl('referral'), 'e', Lang::T('Invalid request'));
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('referral'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        $amount = (float) str_replace([' ', ','], '', (string) _post('amount'));
        try {
            $wd = Referral::requestWithdrawal((int) $admin['id'], $amount);
            $fee = number_format((float) $wd->fee, 0, ',', ' ');
            $net = number_format((float) $wd->net_amount, 0, ',', ' ');
            Csrf::generateAndStoreToken();
            r2(getUrl('referral'), 's', sprintf(
                'Demande de retrait de %s F envoyée. Frais : %s F · Montant net : %s F.',
                number_format($amount, 0, ',', ' '),
                $fee,
                $net
            ));
        } catch (RuntimeException $e) {
            r2(getUrl('referral'), 'e', $e->getMessage());
        }
        break;

    case 'mark-read':
        Referral::markAllRead((int) $admin['id']);
        if (_get('ajax') == '1') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
        r2(getUrl('referral'), 'i', 'Notifications marquées comme lues.');
        break;

    default:
        $refCode = Referral::getOrCreateCode((int) $admin['id']);
        $stats   = Referral::statsForAdmin((int) $admin['id']);
        $commissions = Referral::commissionsForAdmin((int) $admin['id'], 50);
        $withdrawals = Referral::withdrawalsForAdmin((int) $admin['id'], 50);
        $notifications = Referral::notificationFeed((int) $admin['id'], 20);

        $referralUrl = Referral::referralUrl($refCode->code);

        $ui->assign('_title', Lang::T('Parrainage'));
        $ui->assign('_system_menu', 'referral');
        $ui->assign('referral_code', $refCode->code);
        $ui->assign('referral_url', $referralUrl);
        $ui->assign('referral_stats', $stats);
        $ui->assign('referral_commissions', $commissions);
        $ui->assign('referral_withdrawals', $withdrawals);
        $ui->assign('referral_notifications', $notifications);
        $ui->assign('withdrawal_min', Referral::WITHDRAWAL_MIN);
        $ui->assign('csrf_token', Csrf::getToken());
        $ui->display('admin/referral.tpl');
        break;
}
