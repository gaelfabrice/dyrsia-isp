<?php

/**
 * Public ISP instance provisioning (no authentication required).
 */

Tenant::ensureSchema();

$do = $routes['1'] ?? 'form';

switch ($do) {
    case 'submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            r2(getUrl('provision'), 'e', Lang::T('Invalid request'));
        }

        $businessName = trim(_post('business_name'));
        $slug = Tenant::normalizeSlug(_post('subdomain'));
        $email = trim(_post('email'));

        $provisionError = WifiZoneSecurity::verifyProvisionRequest();
        if ($provisionError !== null) {
            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $provisionError]);
                exit;
            }
            r2(getUrl('provision'), 'e', $provisionError);
        }

        try {
            $result = Tenant::provision($businessName, $slug, $email);
            $tenant = $result['tenant'];
            Tenant::setCurrent($tenant);
            Tenant::loginAdmin((int) $result['admin']->id());
            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'redirect' => Tenant::dashboardUrl($tenant->slug),
                    'username' => $result['admin']->username,
                    'password' => $result['password']
                ]);
                exit;
            }
            r2(
                Tenant::dashboardUrl($tenant->slug),
                's',
                Lang::T('Environment created successfully. Welcome!') . ' ' . Lang::T('Username') . ': ' . $result['admin']->username . ' | ' . Lang::T('Password') . ': ' . $result['password']
            );
        } catch (InvalidArgumentException $e) {
            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            r2(getUrl('provision'), 'e', $e->getMessage());
        } catch (RuntimeException $e) {
            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            r2(getUrl('provision'), 'e', $e->getMessage());
        } catch (Exception $e) {
            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => Lang::T('Provisioning failed') . ': ' . $e->getMessage()]);
                exit;
            }
            r2(getUrl('provision'), 'e', Lang::T('Provisioning failed') . ': ' . $e->getMessage());
        }
        break;

    default:
        $ui->assign('_title', Lang::T('Provision Your Instance'));
        $ui->assign('tenant_domain_suffix', Tenant::domainSuffix());
        $ui->display('customer/provision.tpl');
        break;
}
