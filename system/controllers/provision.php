<?php

/**
 * Public ISP instance provisioning (no authentication required).
 */

Tenant::ensureSchema();

$do = $routes['1'] ?? 'form';

function provision_json_response(array $payload, int $statusCode = 200, bool $exit = true): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($exit) {
        exit;
    }
}

function provision_finish_request(): void
{
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        return;
    }
    ignore_user_abort(true);
    if (ob_get_level() > 0) {
        @ob_end_flush();
    }
    @flush();
}

switch ($do) {
    case 'submit':
        @ini_set('max_execution_time', '120');
        @ini_set('default_socket_timeout', '15');
        @set_time_limit(120);
        $isAjax = _post('ajax') == '1';
        if ($isAjax) {
            @ini_set('display_errors', '0');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                provision_json_response(['status' => 'error', 'message' => Lang::T('Invalid request')], 405);
            }
            r2(getUrl('provision'), 'e', Lang::T('Invalid request'));
        }

        $businessName = trim(_post('business_name'));
        $slug = Tenant::normalizeSlug(_post('subdomain'));
        $email = trim(_post('email'));
        $countryCode = trim(_post('country_code'));
        $signupIntent = AdminSubscription::normalizeSignupIntent(_post('signup_intent'));

        $provisionError = WifiZoneSecurity::verifyProvisionRequest();
        if ($provisionError !== null) {
            if ($isAjax) {
                provision_json_response(['status' => 'error', 'message' => $provisionError]);
            }
            r2(getUrl('provision'), 'e', $provisionError);
        }

        $rateLimitError = WifiZoneSecurity::checkProvisionRateLimit();
        if ($rateLimitError !== null) {
            if ($isAjax) {
                provision_json_response(['status' => 'error', 'message' => $rateLimitError]);
            }
            r2(getUrl('provision'), 'e', $rateLimitError);
        }

        try {
            $result = Tenant::provision(
                $businessName,
                $slug,
                $email,
                $signupIntent,
                $countryCode,
                ['skip_notifications' => $isAjax]
            );
            WifiZoneSecurity::recordProvisionAttempt();
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

            if ($isAjax) {
                provision_json_response([
                    'status' => 'success',
                    'redirect' => $redirect,
                    'username' => $result['admin']->username,
                    'password' => $result['password'],
                    'signup_intent' => $signupIntent,
                ], 200, false);
                provision_finish_request();
                if (!empty($result['notification'])) {
                    Tenant::sendProvisionWelcomeNotifications($result['notification']);
                }
                exit;
            }
            r2($redirect, 's', $flash);
        } catch (InvalidArgumentException $e) {
            if ($isAjax) {
                provision_json_response(['status' => 'error', 'message' => $e->getMessage()]);
            }
            r2(getUrl('provision&intent=' . urlencode($signupIntent)), 'e', $e->getMessage());
        } catch (RuntimeException $e) {
            if ($isAjax) {
                provision_json_response(['status' => 'error', 'message' => $e->getMessage()]);
            }
            r2(getUrl('provision&intent=' . urlencode($signupIntent)), 'e', $e->getMessage());
        } catch (Exception $e) {
            if ($isAjax) {
                provision_json_response([
                    'status' => 'error',
                    'message' => Lang::T('Provisioning failed') . ': ' . $e->getMessage(),
                ]);
            }
            r2(getUrl('provision&intent=' . urlencode($signupIntent)), 'e', Lang::T('Provisioning failed') . ': ' . $e->getMessage());
        }
        break;

    default:
        $signupIntent = AdminSubscription::normalizeSignupIntent(_get('intent') ?: _get('plan'));
        $ui->assign('_title', Lang::T('Provision Your Instance'));
        $ui->assign('tenant_domain_suffix', Tenant::domainSuffix());
        $ui->assign('signup_intent', $signupIntent);
        $ui->assign('provision_countries', MobileMoneyCountry::availableForProvisioning());
        $ui->display('customer/provision.tpl');
        break;
}
