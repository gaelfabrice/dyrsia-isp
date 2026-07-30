<?php

_admin();
$ui->assign('_admin', $admin);

$action = $routes['1'] ?? 'index';

switch ($action) {
    case 'exit':
        if (!Impersonate::isActive()) {
            r2(getUrl('dashboard'));
        }
        Impersonate::exitToSuperAdmin();
        r2(getUrl('dashboard'), 's', Lang::T('Returned to your Super Admin account'));
        break;

    case 'admin':
        if (Impersonate::isActive()) {
            r2(getUrl('dashboard'), 'e', Lang::T('Use Exit impersonation when finished.'));
        }
        Impersonate::requireSuperAdmin($admin);
        $id = (int) ($routes['2'] ?? 0);
        $token = _req('token');
        if (!Csrf::check($token)) {
            r2(getUrl('impersonate'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            Impersonate::startAsAdmin($admin, $id);
            r2(getUrl('dashboard'), 's', Lang::T('You are now logged in as this administrator'));
        } catch (RuntimeException $e) {
            r2(getUrl('impersonate'), 'e', $e->getMessage());
        }
        break;

    case 'customer':
        if (Impersonate::isActive()) {
            r2(getUrl('dashboard'), 'e', Lang::T('Use Exit impersonation when finished.'));
        }
        Impersonate::requireSuperAdmin($admin);
        $id = (int) ($routes['2'] ?? 0);
        $token = _req('token');
        if (!Csrf::check($token)) {
            r2(getUrl('impersonate'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        try {
            Impersonate::startAsCustomer($admin, $id);
            _alert(
                Lang::T('You are logged in as this customer') . '. ' . Lang::T('Use Exit impersonation when finished.'),
                'info',
                'home',
                8
            );
        } catch (RuntimeException $e) {
            r2(getUrl('impersonate'), 'e', $e->getMessage());
        }
        break;

    case 'search':
        if (Impersonate::isActive()) {
            header('HTTP/1.0 403 Forbidden');
            echo json_encode(['error' => 'impersonating']);
            die();
        }
        Impersonate::requireSuperAdmin($admin);
        header('Content-Type: application/json; charset=utf-8');
        $q = trim(_get('q') ?? '');
        echo json_encode(Impersonate::searchTargets($q));
        die();

    default:
        if (Impersonate::isActive()) {
            r2(getUrl('dashboard'), 'e', Lang::T('Use Exit impersonation when finished.'));
        }
        Impersonate::requireSuperAdmin($admin);
        $ui->assign('_title', Lang::T('Login as user'));
        $ui->assign('_system_menu', 'customers');
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $lists = Impersonate::searchTargets('');
        $ui->assign('impersonate_admins', $lists['admins']);
        $ui->assign('impersonate_customers', $lists['customers']);
        $ui->display('admin/impersonate.tpl');
        break;
}
