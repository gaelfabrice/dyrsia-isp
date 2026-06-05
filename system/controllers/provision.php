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
        $signupIntent = AdminSubscription::normalizeSignupIntent(_post('signup_intent'));

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
            $result = Tenant::provision($businessName, $slug, $email, $signupIntent);
            $tenant = $result['tenant'];
            Tenant::setCurrent($tenant);
            Tenant::loginAdmin((int) $result['admin']->id());

            if (in_array($signupIntent, ['business', 'pro'], true)) {
                $_SESSION['signup_checkout_plan'] = $signupIntent;
                $redirect = AdminSubscription::subscriptionUrl($signupIntent, true);
                $flash = Lang::T('Environment created successfully. Welcome!')
                    . ' ' . Lang::T('Complete your subscription payment to activate your plan.');
            } else {
                unset($_SESSION['signup_checkout_plan']);
                $redirect = Tenant::dashboardUrl($tenant->slug);
                $flash = Lang::T('Environment created successfully. Welcome!')
                    . ' ' . Lang::T('Username') . ': ' . $result['admin']->username
                    . ' | ' . Lang::T('Password') . ': ' . $result['password']
                    . ' — Mode Démo actif (' . AdminSubscription::demoTrialDays() . ' jours).';
            }

            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'redirect' => $redirect,
                    'username' => $result['admin']->username,
                    'password' => $result['password'],
                    'signup_intent' => $signupIntent,
                ]);
                exit;
            }
            r2($redirect, 's', $flash);
        } catch (InvalidArgumentException $e) {
            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            r2(getUrl('provision&intent=' . urlencode($signupIntent)), 'e', $e->getMessage());
        } catch (RuntimeException $e) {
            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
            r2(getUrl('provision&intent=' . urlencode($signupIntent)), 'e', $e->getMessage());
        } catch (Exception $e) {
            if (_post('ajax') == '1') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => Lang::T('Provisioning failed') . ': ' . $e->getMessage()]);
                exit;
            }
            r2(getUrl('provision&intent=' . urlencode($signupIntent)), 'e', Lang::T('Provisioning failed') . ': ' . $e->getMessage());
        }
        break;

    default:
        $signupIntent = AdminSubscription::normalizeSignupIntent(_get('intent') ?: _get('plan'));
        $ui->assign('_title', Lang::T('Provision Your Instance'));
        $ui->assign('tenant_domain_suffix', Tenant::domainSuffix());
        $ui->assign('signup_intent', $signupIntent);
        $ui->display('customer/provision.tpl');
        break;
}
