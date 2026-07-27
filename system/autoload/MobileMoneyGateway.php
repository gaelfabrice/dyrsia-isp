<?php

/**
 * CamPay et MyPVit : une seule passerelle mobile active à la fois.
 */
class MobileMoneyGateway
    {
        public const MOBILE_GATEWAYS = ['campay', 'mypvit'];

        public static function activeList()
        {
            global $config;
            $raw = explode(',', (string) ($config['payment_gateway'] ?? ''));
            return array_values(array_filter(array_map('trim', $raw)));
        }

        public static function activeMobile()
        {
            global $config;
            if (!empty($config['tenant_mobile_gateway'])) {
                return (string) $config['tenant_mobile_gateway'];
            }
            foreach (self::activeList() as $gateway) {
                if (in_array($gateway, self::MOBILE_GATEWAYS, true)) {
                    return $gateway;
                }
            }
            return '';
        }

        public static function normalizeActives(array $selected)
        {
            $selected = array_values(array_unique(array_filter(array_map('trim', $selected))));
            $mobile = array_values(array_intersect($selected, self::MOBILE_GATEWAYS));
            $others = array_values(array_diff($selected, self::MOBILE_GATEWAYS));
            if (count($mobile) > 1) {
                $mobile = [end($mobile)];
            }
            return array_values(array_unique(array_merge($others, $mobile)));
        }

        public static function saveActives(array $selected)
        {
            $normalized = self::normalizeActives($selected);
            $value = implode(',', $normalized);
            $row = ORM::for_table('tbl_appconfig')->where('setting', 'payment_gateway')->find_one();
            if ($row) {
                $row->value = $value;
                $row->save();
            } else {
                $row = ORM::for_table('tbl_appconfig')->create();
                $row->setting = 'payment_gateway';
                $row->value = $value;
                $row->save();
            }
            global $config;
            $config['payment_gateway'] = $value;
            return $normalized;
        }

        public static function deactivateOtherMobile($keep)
        {
            if (!in_array($keep, self::MOBILE_GATEWAYS, true)) {
                return;
            }
            $actives = self::activeList();
            $changed = false;
            foreach (self::MOBILE_GATEWAYS as $gateway) {
                if ($gateway !== $keep && in_array($gateway, $actives, true)) {
                    $actives = array_values(array_diff($actives, [$gateway]));
                    $changed = true;
                }
            }
            if (!in_array($keep, $actives, true)) {
                $actives[] = $keep;
                $changed = true;
            }
            if ($changed) {
                self::saveActives($actives);
            }
        }

        public static function isSuccessfulGatewayStatus($status)
        {
            $status = strtoupper(trim((string) $status));

            return in_array($status, ['SUCCESSFUL', 'SUCCESS', 'COMPLETED', 'PAID', 'APPROVED'], true);
        }

        public static function isFailedGatewayStatus($status)
        {
            $status = strtoupper(trim((string) $status));

            return in_array($status, ['FAILED', 'CANCELLED', 'CANCELED', 'REJECTED', 'DECLINED'], true);
        }

        public static function isConfigured($gateway = null)
        {
            global $config;
            $gateway = $gateway ?: self::activeMobile();
            if ($gateway === 'campay') {
                return !empty($config['campay_username']) && !empty($config['campay_password']);
            }
            if ($gateway === 'mypvit') {
                return !empty($config['mypvit_code_url'])
                    && !empty($config['mypvit_secret_key'])
                    && !empty($config['mypvit_operation_account_code'])
                    && !empty($config['mypvit_callback_url_code']);
            }
            return false;
        }

        public static function requireFile($gateway = null)
        {
            global $PAYMENTGATEWAY_PATH;
            $gateway = $gateway ?: self::activeMobile();
            if ($gateway === '') {
                return false;
            }
            $file = $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $gateway . '.php';
            if (!file_exists($file)) {
                return false;
            }
            require_once $file;
            return true;
        }

        public static function adminSubscriptionCollect($ctx, $admin, $phone)
        {
            $gateway = self::activeMobile();
            if ($gateway === '' || !self::requireFile($gateway)) {
                r2(getUrl('admin/subscription'), 'e', Lang::T('Payment gateway not configured. Please contact admin'));
            }
            $fn = $gateway . '_admin_subscription_collect';
            if (!function_exists($fn)) {
                r2(getUrl('admin/subscription'), 'e', Lang::T('Payment gateway not configured. Please contact admin'));
            }
            call_user_func($fn, $ctx, $admin, $phone);
        }

        /** Lance le collect Mobile Money et retourne un tableau (mode AJAX). */
        public static function adminSubscriptionCollectData($ctx, $admin, $phone)
        {
            $gateway = self::activeMobile();
            if ($gateway === '' || !self::requireFile($gateway)) {
                return ['ok' => false, 'message' => Lang::T('Payment gateway not configured. Please contact admin')];
            }
            $fn = $gateway . '_admin_subscription_collect_data';
            if (!function_exists($fn)) {
                return ['ok' => false, 'message' => Lang::T('Payment gateway not configured. Please contact admin')];
            }
            return call_user_func($fn, $ctx, $admin, $phone);
        }

        public static function adminSubscriptionCheckStatus($paymentId, $adminId)
        {
            $gateway = self::activeMobile();
            if ($gateway === '' || !self::requireFile($gateway)) {
                return ['ok' => false, 'message' => Lang::T('Payment gateway not configured')];
            }
            $fn = $gateway . '_admin_subscription_check_status';
            if (!function_exists($fn)) {
                return ['ok' => false, 'message' => Lang::T('Payment gateway not configured')];
            }
            return call_user_func($fn, $paymentId, $adminId);
        }

        public static function adminSubscriptionWebhook($paymentId, $reference, $status, $data, $gateway)
        {
            if (!self::requireFile($gateway)) {
                return;
            }
            $fn = $gateway . '_admin_subscription_webhook';
            if (function_exists($fn)) {
                call_user_func($fn, $paymentId, $reference, $status, $data);
            }
        }

        /**
         * Profil UI page captive (indicatif, validation, opérateurs) selon la passerelle mobile active.
         */
        public static function hotspotPaymentProfile($gateway = null)
        {
            global $config;
            $gateway = $gateway ?: self::activeMobile();
            if ($gateway === '' || !in_array($gateway, self::MOBILE_GATEWAYS, true)) {
                $gateway = 'campay';
            }

            if ($gateway === 'mypvit') {
                $prefix = preg_replace('/\D/', '', (string) ($config['mypvit_phone_prefix'] ?? '241'));
                if ($prefix === '') {
                    $prefix = '241';
                }
                if ($prefix === '237') {
                    return self::hotspotCameroonProfile('mypvit', $prefix);
                }

                return self::hotspotGabonProfile($prefix);
            }

            return self::hotspotCameroonProfile('campay', '237');
        }

        private static function hotspotCameroonProfile($gateway, $prefix)
        {
            return [
                'gateway' => $gateway,
                'prefix' => $prefix,
                'prefixDisplay' => '',
                'country' => 'Cameroun',
                'badge' => 'Mobile Money · Cameroun',
                'subtitle' => 'MTN MoMo ou Orange Money',
                'placeholder' => '677123456',
                'localLength' => 9,
                'localPattern' => '^[26]',
                'errors' => [
                    'length' => 'Entrez 9 chiffres (ex: 677123456)',
                    'format' => 'Numéro incorrect (doit commencer par 6 ou 2)',
                ],
                'detect' => [
                    ['pattern' => '^(67|68)', 'name' => 'MTN', 'ussd' => '*126#'],
                    ['pattern' => '^(69|65[5-9])', 'name' => 'Orange', 'ussd' => '#150*50#'],
                ],
            ];
        }

        private static function hotspotGabonProfile($prefix)
        {
            return [
                'gateway' => 'mypvit',
                'prefix' => $prefix,
                'prefixDisplay' => '+' . $prefix,
                'country' => 'Gabon',
                'badge' => 'Mobile Money · Gabon',
                'subtitle' => 'Airtel Money ou Moov Money',
                'placeholder' => '7X XX XX XX',
                'localLength' => 9,
                'localPattern' => '^[62][0-9]|^7[0-9]',
                'errors' => [
                    'length' => 'Entrez 9 chiffres après +' . $prefix . ' (ex: 741234567)',
                    'format' => 'Numéro gabonais invalide (Airtel 07/74/77, Moov 06/65/66)',
                ],
                'operators' => [
                    ['label' => 'Airtel Money', 'class' => 'airtel'],
                    ['label' => 'Moov Money', 'class' => 'moov'],
                ],
                'detect' => [
                    ['pattern' => '^(07|74|77|62)', 'name' => 'Airtel', 'ussd' => '#150*1#'],
                    ['pattern' => '^(06|65|66)', 'name' => 'Moov', 'ussd' => '*555#'],
                ],
            ];
        }

        /**
         * Opérateur + code USSD déduits du numéro, selon la passerelle active.
         * @return array{operator: string, ussd: string}
         */
        public static function operatorInfoForPhone($phone, $gateway = null)
        {
            $profile = self::hotspotPaymentProfile($gateway);
            $digits = preg_replace('/\D/', '', (string) $phone);
            $prefix = (string) ($profile['prefix'] ?? '');
            if ($prefix !== '' && strpos($digits, $prefix) === 0) {
                $digits = substr($digits, strlen($prefix));
            }
            $digits = ltrim($digits, '0');
            $local = substr($digits, 0, (int) ($profile['localLength'] ?? 9));
            foreach (($profile['detect'] ?? []) as $rule) {
                if (@preg_match('/' . $rule['pattern'] . '/', $local)) {
                    return ['operator' => $rule['name'], 'ussd' => $rule['ussd']];
                }
            }
            return ['operator' => 'Mobile Money', 'ussd' => ''];
        }

        /** Mémorise le code USSD/opérateur d'un paiement d'abonnement (affiché après redirection). */
        public static function rememberSubscriptionUssd($paymentId, $operator, $ussd)
        {
            $_SESSION['admin_sub_ussd'] = [
                'payment_id' => (int) $paymentId,
                'operator' => (string) $operator,
                'ussd' => (string) $ussd,
            ];
        }

        /** Récupère et efface le code USSD mémorisé pour ce paiement. */
        public static function takeSubscriptionUssd($paymentId)
        {
            $info = $_SESSION['admin_sub_ussd'] ?? null;
            unset($_SESSION['admin_sub_ussd']);
            if (is_array($info) && (int) ($info['payment_id'] ?? 0) === (int) $paymentId) {
                return ['operator' => (string) $info['operator'], 'ussd' => (string) $info['ussd']];
            }
            return ['operator' => '', 'ussd' => ''];
        }

        public static function buildHotspotPaymentJsBlock($gateway = null)
        {
            $profile = self::hotspotPaymentProfile($gateway);
            $profileJson = json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return 'const HOTSPOT_PAYMENT_GATEWAY = ' . json_encode($profile['gateway']) . ";\n"
                . 'const HOTSPOT_PAYMENT_PROFILE = ' . $profileJson . ";\n"
                . <<<'JS'
        const CAMPAY_WAIT_SECONDS = 120;
        const CAMPAY_POLL_START_DELAY_MS = 10000;
        function parsePaymentUrl(paymentUrl) {
            try {
                const url = new URL(paymentUrl, APP_URL + '/');
                return { planid: url.searchParams.get('planid') || '', routername: url.searchParams.get('routername') || '', amount: url.searchParams.get('amount') || '' };
            } catch (e) { return { planid: '', routername: '', amount: '' }; }
        }
        function normalizeLocalPhone(value) {
            let digits = String(value || '').replace(/\D/g, '');
            const prefix = String(HOTSPOT_PAYMENT_PROFILE.prefix || '').replace(/\D/g, '');
            const localLen = HOTSPOT_PAYMENT_PROFILE.localLength || 9;
            if (prefix && digits.indexOf(prefix) === 0) digits = digits.slice(prefix.length);
            digits = digits.replace(/^0+/, '');
            return digits.slice(0, localLen);
        }
        function hotspotPhoneInputAttrs() {
            const localLen = HOTSPOT_PAYMENT_PROFILE.localLength || 9;
            return {
                maxlength: localLen,
                minlength: localLen,
                inputmode: 'numeric',
                pattern: '[0-9]*',
                autocomplete: 'off'
            };
        }
        function bindHotspotPhoneField(input) {
            if (!input) return;
            const trimPhone = function () {
                input.value = normalizeLocalPhone(input.value);
            };
            input.addEventListener('input', trimPhone);
            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                input.value = normalizeLocalPhone(text);
            });
        }
        function toCampayMsisdn(localDigits) {
            const prefix = String(HOTSPOT_PAYMENT_PROFILE.prefix || '237').replace(/\D/g, '');
            const localLen = HOTSPOT_PAYMENT_PROFILE.localLength || 9;
            let digits = String(localDigits || '').replace(/\D/g, '');
            if (prefix && digits.indexOf(prefix) === 0) digits = digits.slice(prefix.length);
            digits = digits.replace(/^0+/, '').slice(0, localLen);
            if (digits.length !== localLen) return '';
            return prefix + digits;
        }
        function formatDisplayPhone(phone) {
            return normalizeLocalPhone(phone);
        }
        function validateHotspotPhone(phone) {
            const p = HOTSPOT_PAYMENT_PROFILE;
            const local = normalizeLocalPhone(phone);
            if (local.length !== (p.localLength || 9)) return p.errors.length;
            if (!new RegExp(p.localPattern).test(local)) return p.errors.format;
            if (!toCampayMsisdn(phone)) return p.errors.length;
            return null;
        }
        function detectMobileOperator(phone, apiOperator, apiUssd) {
            if (apiOperator && apiUssd) return { name: apiOperator, ussd: apiUssd };
            const local = normalizeLocalPhone(phone);
            const rules = HOTSPOT_PAYMENT_PROFILE.detect || [];
            for (let i = 0; i < rules.length; i++) {
                if (new RegExp(rules[i].pattern).test(local)) {
                    return { name: rules[i].name, ussd: rules[i].ussd };
                }
            }
            return { name: 'Mobile Money', ussd: '' };
        }
        function buildUssdWaitHtml(phone, operator, secondsLeft, planName, price, currency) {
            return '<div style="text-align:center;padding:6px 2px"><div style="width:58px;height:58px;margin:0 auto 18px;border:4px solid rgba(16,185,129,.18);border-top-color:#10b981;border-radius:50%;animation:campaySpin 1s linear infinite"></div><style>@keyframes campaySpin{to{transform:rotate(360deg)}}</style><p style="font-size:16px;line-height:1.5;margin:0 0 10px"><strong>Validez la transaction sur votre téléphone</strong></p><p style="font-size:14px;color:#94a3b8;margin:0 0 8px">' + escapeHtml(planName) + ' — <strong>' + escapeHtml(String(price)) + ' ' + escapeHtml(currency || 'Fcfa') + '</strong></p><p style="font-size:14px;margin:0 0 12px">Numéro: <strong>' + escapeHtml(formatDisplayPhone(phone)) + '</strong> (' + escapeHtml(operator.name) + ')</p><div style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);border-radius:16px;padding:14px;margin:12px 0;text-align:left"><p style="margin:0 0 8px;font-size:14px">📲 Une notification USSD va s\'afficher.<br>Confirmez avec votre <strong>code PIN</strong>.</p><p style="margin:0;font-size:14px">Sinon composez: <strong style="color:#10b981;font-size:20px">' + escapeHtml(operator.ussd) + '</strong></p></div><p id="campayCountdown" style="font-size:13px;color:#64748b;margin:14px 0 0">Temps restant: ' + secondsLeft + ' secondes…</p><p style="font-size:12px;color:#64748b;margin:8px 0 0">Ne fermez pas cette page pendant la validation.</p></div>';
        }
        async function pollPaymentStatus(reference, maxSeconds) {
            const deadline = Date.now() + (maxSeconds * 1000);
            await new Promise(function (r) { setTimeout(r, CAMPAY_POLL_START_DELAY_MS); });
            while (Date.now() < deadline) {
                try {
                    const res = await fetchHotspotEndpoint('hotspot_verify&reference=' + encodeURIComponent(reference) + '&format=json', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) {
                        await new Promise(function (r) { setTimeout(r, 3000); });
                        continue;
                    }
                    const data = await res.json();
                    if (data.status === 'paid' || data.status === 'failed' || data.status === 'cancelled') return data;
                } catch (e) {}
                await new Promise(function (r) { setTimeout(r, 3000); });
            }
            return { status: 'timeout', message: 'Délai dépassé. Vérifiez votre téléphone puis réessayez.' };
        }
        let campayCountdownTimer = null;
        function openUssdWaitModal(phone, operator, planName, price, currency) {
            let secondsLeft = CAMPAY_WAIT_SECONDS;
            return Swal.fire({ title: 'Paiement en cours…', html: buildUssdWaitHtml(phone, operator, secondsLeft, planName, price, currency), allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false,
                didOpen: function () {
                    campayCountdownTimer = setInterval(function () {
                        secondsLeft -= 1;
                        const el = document.getElementById('campayCountdown');
                        if (el) el.textContent = secondsLeft > 0 ? ('Temps restant: ' + secondsLeft + ' secondes…') : 'Vérification finale…';
                        if (secondsLeft <= 0 && campayCountdownTimer) { clearInterval(campayCountdownTimer); campayCountdownTimer = null; }
                    }, 1000);
                },
                willClose: function () { if (campayCountdownTimer) { clearInterval(campayCountdownTimer); campayCountdownTimer = null; } }
            });
        }
        function isHotspotCaptivePortal() {
            try {
                const origin = window.location.origin || '';
                if (!origin || window.location.protocol === 'file:') return false;
                const host = new URL(origin).hostname;
                return /^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/.test(host);
            } catch (e) {
                return false;
            }
        }
        async function initiateCampayPayment(params) {
            params = params || {};
            const localPhone = normalizeLocalPhone(params.msisdn || params.phonenumber || params.phone || '');
            const msisdn = toCampayMsisdn(localPhone);
            const query = new URLSearchParams();
            ['pay', 'type', 'payment_gateway', 'routername', 'planid', 'amount', 'plan_name', 'mac_address', 'ip_address', 'fullname', 'address'].forEach(function (k) {
                let val = params[k];
                if (k === 'ajax') val = '1';
                if (val !== undefined && val !== null && String(val) !== '') query.set(k, String(val));
            });
            query.set('pay', '1');
            query.set('type', params.type || 'gateways');
            query.set('payment_gateway', params.payment_gateway || HOTSPOT_PAYMENT_GATEWAY);
            query.set('ajax', '1');
            if (localPhone) query.set('n', localPhone);
            if (msisdn) {
                query.set('hmobile', msisdn);
                query.set('phone', msisdn);
                query.set('phonenumber', msisdn);
                try { query.set('pd', btoa(msisdn)); } catch (e) {}
                query.set('msisdn', msisdn);
            }
            const route = query.toString() ? ('hotspot_pay&' + query.toString()) : 'hotspot_pay';
            const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            const onCaptive = isHotspotCaptivePortal();
            const fetchOptions = {
                method: 'POST',
                headers: Object.assign({}, headers, { 'Content-Type': 'application/x-www-form-urlencoded' }),
                body: query.toString()
            };
            const res = await fetchHotspotEndpoint(route, fetchOptions);
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Réponse serveur invalide');
            }
        }
        function buildPaymentModalHtml(planName, price, currency, validity) {
            const p = HOTSPOT_PAYMENT_PROFILE;
            const phoneLen = p.localLength || 9;
            const prefixLabel = String(p.prefixDisplay || '').trim();
            const prefixHtml = prefixLabel ? '<span class="campay-phone-prefix">' + escapeHtml(prefixLabel) + '</span>' : '';
            const phoneHint = escapeHtml((p.errors && p.errors.length) ? p.errors.length : ('Entrez exactement ' + phoneLen + ' chiffres (ex: 677123456)'));
            return '<div class="campay-pay-modal"><div class="campay-pay-header"><div class="campay-pay-badge">📱 ' + escapeHtml(p.badge) + '</div><h4>Paiement sécurisé</h4><p>' + escapeHtml(p.subtitle) + '</p></div><div class="campay-pay-body"><div class="campay-pay-plan"><div><span class="campay-pay-plan-name">' + escapeHtml(planName) + '</span><span class="campay-pay-plan-meta">⏱️ ' + escapeHtml(validity || '—') + '</span></div><div class="campay-pay-price">' + escapeHtml(String(price)) + ' <small>' + escapeHtml(currency || 'XAF') + '</small></div></div><label class="campay-pay-label" for="campayPhoneInput">Numéro Mobile Money (' + phoneLen + ' chiffres)</label><div class="campay-phone-wrap">' + prefixHtml + '<input id="campayPhoneInput" type="tel" inputmode="numeric" autocomplete="tel-national" placeholder="' + escapeHtml(p.placeholder) + '" maxlength="' + phoneLen + '" minlength="' + phoneLen + '" /></div><p class="campay-pay-hint" style="margin:8px 0 0;font-size:12px;color:#94a3b8">' + phoneHint + '</p></div></div>';
        }
        function bindPaymentPhoneInput() {
            bindHotspotPhoneField(document.getElementById('campayPhoneInput'));
            const input = document.getElementById('campayPhoneInput');
            if (input) setTimeout(function () { input.focus(); }, 80);
        }
        function buildPaymentSuccessHtml(planName, validity) {
            return '<div class="hotspot-pay-success">' +
                '<div class="hotspot-pay-success-ring"><svg viewBox="0 0 48 48"><path d="M12 24l8 8 16-16"/></svg></div>' +
                '<h3>Paiement confirmé</h3>' +
                '<p class="hotspot-pay-success-plan">' + escapeHtml(planName) + (validity ? (' · ' + escapeHtml(validity)) : '') + '</p>' +
                '<p class="hotspot-pay-success-sub">Connexion automatique en cours…</p>' +
                '<div class="hotspot-pay-success-bar"><span></span></div></div>';
        }
        function normalizeHotspotLoginPassword(password) {
            password = String(password || '').trim();
            if (!password || password.indexOf('$2y$') === 0 || password.indexOf('$2a$') === 0 || password.indexOf('$2b$') === 0) {
                return '123456';
            }
            return password;
        }
        function connectAfterPayment(code, password, planName, validity) {
            const loginPassword = normalizeHotspotLoginPassword(password || '123456');
            if (!loginPassword) {
                Swal.fire('Erreur', 'Mot de passe non reçu du serveur.', 'error');
                return;
            }
            Swal.fire({
                html: buildPaymentSuccessHtml(planName, validity),
                showConfirmButton: false,
                showCloseButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: { popup: 'campay-swal-popup hotspot-pay-success-popup' },
                didOpen: function () {
                    setTimeout(function () {
                        if (typeof fillAndSubmitHotspotLogin === 'function') {
                            fillAndSubmitHotspotLogin(code, loginPassword);
                        } else if (typeof fillAndSubmitLogin === 'function') {
                            fillAndSubmitLogin(code, loginPassword);
                        } else if (typeof submitHotspotLogin === 'function') {
                            const userEl = document.getElementById('user') || document.getElementById('loginUsername');
                            const passEl = document.getElementById('pass') || document.getElementById('loginPassword');
                            if (userEl && code) userEl.value = code;
                            if (passEl) {
                                passEl.value = loginPassword;
                                delete passEl.dataset.chapDone;
                            }
                            submitHotspotLogin();
                        } else {
                            const userField = document.getElementById('user') || document.getElementById('loginUsername');
                            const pwdField = document.getElementById('pass') || document.getElementById('loginPassword');
                            const form = document.getElementById('loginForm');
                            if (userField && code) userField.value = code;
                            if (pwdField) pwdField.value = loginPassword;
                            if (form && typeof prepareMikrotikLogin === 'function' && prepareMikrotikLogin(form)) form.submit();
                        }
                    }, 1200);
                },
                timer: 2500,
                timerProgressBar: false
            });
        }
        async function handlePlanPayment(planName, price, currency, validity, paymentUrl) {
            const meta = parsePaymentUrl(paymentUrl);
            const result = await Swal.fire({ title: '', html: buildPaymentModalHtml(planName, price, currency, validity), showCancelButton: true, confirmButtonText: 'Payer maintenant', cancelButtonText: 'Annuler', allowOutsideClick: false, focusConfirm: false, customClass: { popup: 'campay-swal-popup', confirmButton: 'campay-swal-confirm', cancelButton: 'campay-swal-cancel' }, didOpen: bindPaymentPhoneInput, preConfirm: function () { const input = document.getElementById('campayPhoneInput'); const phone = normalizeLocalPhone(input ? input.value : ''); const err = validateHotspotPhone(phone); if (err) { Swal.showValidationMessage(err); return false; } return phone; } });
            if (!result.isConfirmed) return;
            const phone = normalizeLocalPhone(result.value || '');
            let initResult;
            try { initResult = await initiateCampayPayment({ pay: '1', type: 'gateways', payment_gateway: HOTSPOT_PAYMENT_GATEWAY, msisdn: toCampayMsisdn(phone), phonenumber: toCampayMsisdn(phone), routername: meta.routername || (typeof HOTSPOT_ROUTER_NAME !== 'undefined' ? HOTSPOT_ROUTER_NAME : '$(identity)'), planid: meta.planid, amount: meta.amount || price, plan_name: planName, mac_address: CLIENT_MAC || '$(mac)', ip_address: '$(ip)', fullname: 'Client Hotspot', address: 'Hotspot' }); }
            catch (e) { await Swal.fire({ title: 'Erreur réseau', text: (e && e.message) ? e.message : 'Impossible de contacter le serveur.', icon: 'error' }); return; }
            if (!initResult.ok) { await Swal.fire({ title: 'Paiement refusé', text: initResult.message || 'Erreur', icon: 'error' }); return; }
            const operator = detectMobileOperator(phone, initResult.operator, initResult.ussd_code);
            await openUssdWaitModal(phone, operator, planName, price, currency);
            const paymentResult = await pollPaymentStatus(initResult.reference, CAMPAY_WAIT_SECONDS);
            if (typeof Swal !== 'undefined') await Swal.close();
            if (paymentResult.status === 'paid') {
                const loginUser = paymentResult.username || paymentResult.voucher_code || '';
                const loginPass = paymentResult.password || '123456';
                connectAfterPayment(loginUser, loginPass, planName, validity);
                return;
            }
            if (paymentResult.status === 'failed') { await Swal.fire({ title: 'Paiement échoué', text: paymentResult.message || 'Transaction refusée.', icon: 'error' }); return; }
            await Swal.fire({ title: 'En attente', html: 'Confirmation non reçue. Si vous avez validé, attendez 1 minute puis réessayez.', icon: 'warning' });
        }
    JS;
        }

        public static function buildHotspotPlansJsBlock()
        {
            return <<<'JS'
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function (m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    function renderHotspotPlans(plans) {
        const container = document.getElementById('packagesList');
        if (!container) return;
        if (!plans || !plans.length) {
            container.innerHTML = '<div class="package-item" style="text-align:center;">Aucun forfait actif pour ce routeur.</div>';
            return;
        }
        const routerName = resolveHotspotRouterName();
        container.innerHTML = plans.map(function (pkg) {
            const planId = pkg.planid || pkg.planId || '';
            const paymentLink = pkg.paymentlink || (APP_URL + '/index.php?_route=plugin/hotspot_pay&routername=' + encodeURIComponent(routerName) + '&planid=' + encodeURIComponent(planId) + '&amount=' + encodeURIComponent(pkg.price || ''));
            const url = paymentLink + (paymentLink.indexOf('?') >= 0 ? '&' : '?') + 'mac=$(mac)&ip=$(ip)';
            return '<a class="package-item" href="' + url + '" target="_self" data-plan-name="' + escapeHtml(pkg.planname || pkg.name || '') + '" data-plan-price="' + (pkg.price || '') + '" data-plan-currency="' + (pkg.currency || 'Fcfa') + '" data-plan-validity="' + escapeHtml(pkg.validity || '') + '" data-payment-url="' + url + '">' +
                '<div class="package-name"><b>' + escapeHtml(pkg.planname || pkg.name || '') + '</b><span class="package-price">' + (pkg.price || '') + ' ' + (pkg.currency || 'Fcfa') + '</span></div>' +
                '<div class="package-desc"><span>⏱️ Validité: ' + escapeHtml(pkg.validity || '—') + '</span><span class="badge-unlimited">♾️ ILLIMITÉ</span></div></a>';
        }).join('');

        // Add click handlers via event delegation
        container.querySelectorAll('.package-item').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                const nom = this.dataset.planName;
                const prix = this.dataset.planPrice;
                const currency = this.dataset.planCurrency;
                const validite = this.dataset.planValidity;
                const url = this.dataset.paymentUrl;
                handlePlanPayment(nom, prix, currency, validite, url);
            });
        });
    }
    async function loadPlans() {
        const container = document.getElementById('packagesList');
        if (!container) return;
        if (typeof HOTSPOT_EMBEDDED_PLANS !== 'undefined' && HOTSPOT_EMBEDDED_PLANS.length) {
            renderHotspotPlans(HOTSPOT_EMBEDDED_PLANS);
            return;
        }
        container.innerHTML = '<div style="text-align:center; padding:0.8rem;">⏳ Chargement des offres...</div>';
        const routerName = resolveHotspotRouterName();
        if (!routerName) {
            container.innerHTML = '<div class="package-item" style="text-align:center; color:#ef4444;">⚠️ Routeur hotspot non configuré. Enregistrez les paramètres Hotspot puis renvoyez login.html au MikroTik.</div>';
            return;
        }
        try {
            const response = await fetchHotspotEndpoint('hotspot_plan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'routername=' + encodeURIComponent(routerName)
            });
            const data = await response.json();
            if (data.ResultCode === '200' && data.data && data.data.length) {
                renderHotspotPlans(data.data);
            } else {
                const msg = data.message || 'Aucun forfait actif pour ce routeur.';
                container.innerHTML = '<div class="package-item" style="text-align:center; color:#ef4444;">⚠️ ' + escapeHtml(msg) + '</div>';
            }
        } catch (error) {
            console.warn('Erreur chargement forfaits:', error);
            if (!(typeof HOTSPOT_EMBEDDED_PLANS !== 'undefined' && HOTSPOT_EMBEDDED_PLANS.length)) {
                container.innerHTML = '<div class="package-item" style="text-align:center; color:#ef4444;">⚠️ API injoignable. Vérifiez Settings → Hotspot → URL API puis renvoyez login.html au MikroTik.</div>';
            }
        }
    }
    JS;
        }

        public static function buildPrepareMikrotikLoginJs()
        {
            return <<<'JS'
    function resetHotspotPasswordChap() {
        const passEl = document.getElementById('pass') || document.getElementById('loginPassword');
        if (passEl) delete passEl.dataset.chapDone;
    }
    function normalizeHotspotPlainPassword(value) {
        var p = String(value || '').replace(/^\s+|\s+$/g, '');
        if (!p || p.indexOf('$2y$') === 0 || p.indexOf('$2a$') === 0 || p.indexOf('$2b$') === 0) return '123456';
        if (/^[a-f0-9]{32}$/i.test(p)) return '123456';
        return p;
    }
    function prepareMikrotikLogin(form) {
        if (!form) return false;
        const passwordInput = form.querySelector('input[name="password"]');
        if (passwordInput) {
            // PAP uniquement : mot de passe clair (pas de CHAP / MD5).
            passwordInput.value = normalizeHotspotPlainPassword(passwordInput.value);
            delete passwordInput.dataset.chapDone;
        }
        return true;
    }
    function submitHotspotLogin() {
        const f = document.getElementById('loginForm');
        if (!f) return;
        if (!prepareMikrotikLogin(f)) {
            return;
        }
        f.submit();
    }
    function fillAndSubmitHotspotLogin(username, password) {
        const form = document.getElementById('loginForm');
        const userEl = document.getElementById('user') || document.getElementById('loginUsername');
        const passEl = document.getElementById('pass') || document.getElementById('loginPassword');
        if (userEl) userEl.value = username || '';
        if (passEl) {
            delete passEl.dataset.chapDone;
            passEl.value = normalizeHotspotPlainPassword(password || '123456');
        }
        if (!form) return;
        if (typeof Swal !== 'undefined' && Swal.close) Swal.close();
        submitHotspotLogin();
    }
    function fillAndSubmitLogin(username, password) {
        fillAndSubmitHotspotLogin(username, password);
    }
    JS;
        }

        public static function hotspotMd5JsContents()
        {
            $md5File = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'md5.js';
            if (!is_file($md5File)) {
                return '';
            }
            $md5 = file_get_contents($md5File);

            return is_string($md5) ? trim($md5) : '';
        }

        public static function injectHotspotMd5Js($html)
        {
            $md5 = self::hotspotMd5JsContents();
            if ($md5 === '') {
                return $html;
            }
            if (strpos($html, '/* HOTSPOT_MD5_INJECT */') !== false) {
                return str_replace('/* HOTSPOT_MD5_INJECT */', $md5, $html);
            }
            if (preg_match('/\/\/ Chap pour MikroTik[\s\S]*?(?=<\/script>)/', $html)) {
                return preg_replace('/\/\/ Chap pour MikroTik[\s\S]*?(?=<\/script>)/', $md5, $html, 1);
            }
            if (strpos($html, 'function hexMD5') === false && strpos($html, 'window.HOTSPOT_INLINE_MD5') !== false) {
                return preg_replace('/(<\/script>)/', $md5 . "\n$1", $html, 1);
            }

            return $html;
        }

        public static function replaceHotspotPrepareMikrotikLogin($html)
        {
            $replacement = trim(self::buildPrepareMikrotikLoginJs());
            if ($replacement === '') {
                return $html;
            }

            if (preg_match('/function resetHotspotPasswordChap\s*\([\s\S]*?function fillAndSubmitHotspotLogin\s*\([^)]*\)\s*\{[\s\S]*?\n\}/', $html)) {
                return preg_replace(
                    '/function resetHotspotPasswordChap\s*\([\s\S]*?function fillAndSubmitHotspotLogin\s*\([^)]*\)\s*\{[\s\S]*?\n\}/',
                    $replacement,
                    $html,
                    1
                ) ?? $html;
            }

            if (strpos($html, 'function prepareMikrotikLogin') !== false) {
                return preg_replace(
                    '/function prepareMikrotikLogin\s*\(form\)\s*\{[\s\S]*?\n\}/',
                    trim(preg_replace('/^[\s\S]*(?=function prepareMikrotikLogin)/', '', $replacement)),
                    $html,
                    1
                ) ?? $html;
            }

            return $html;
        }

        public static function isModernSelfContainedHotspotLogin($html)
        {
            return strpos($html, 'window.HOTSPOT_INLINE_MD5') !== false
                && strpos($html, 'function hotspotModalFire') !== false
                && strpos($html, 'function handlePlanTap') !== false
                && strpos($html, 'id="plansList"') !== false;
        }

        public static function patchModernHotspotChapLogin($html)
        {
            if (strpos($html, 'window.HOTSPOT_INLINE_MD5') === false) {
                return $html;
            }

            // Template moderne (modal inline + handlePlanTap) : ne pas re-patcher (corrompt le JS).
            if (self::isModernSelfContainedHotspotLogin($html)) {
                return $html;
            }

            $html = self::injectHotspotMd5Js($html);
            $html = self::replaceHotspotPrepareMikrotikLogin($html);

            if (strpos($html, 'function prepareMikrotikLogin') === false) {
                $html = preg_replace(
                    '/(?=function connecter\s*\()/',
                    self::buildPrepareMikrotikLoginJs() . "\n",
                    $html,
                    1
                );
            }

            if (strpos($html, 'function submitHotspotLogin') === false && strpos($html, 'function connecter') !== false) {
                $html = preg_replace(
                    '/(?=function connecter\s*\()/',
                    "function submitHotspotLogin(){const f=document.getElementById('loginForm');if(!f)return;prepareMikrotikLogin(f);f.submit();}\n",
                    $html,
                    1
                );
            }

            $html = preg_replace(
                '/function connecter\(user,pass,nom\)\{[\s\S]*?setTimeout\(\(\)=>\{[\s\S]*?\},600\);\s*\}/',
                "function connecter(user,pass,nom){var loginPass=String(pass||'123456').trim();if(!loginPass||loginPass.indexOf('$2y$')===0||loginPass.indexOf('$2a$')===0||loginPass.indexOf('$2b$')===0){loginPass='123456';}Swal.fire({html:'<b>✅ '+escHtml(nom)+'</b><br>Connexion Internet en cours…<br><small>Un SMS de rappel peut suivre (optionnel)</small>',showConfirmButton:false,timer:1500});setTimeout(function(){if(typeof fillAndSubmitHotspotLogin==='function'){fillAndSubmitHotspotLogin(user,loginPass);}else{const userEl=document.getElementById('user')||document.getElementById('loginUsername');const passEl=document.getElementById('pass')||document.getElementById('loginPassword');if(userEl)userEl.value=user||'';if(passEl){passEl.value=loginPass;delete passEl.dataset.chapDone;}if(typeof Swal!=='undefined'&&Swal.close)Swal.close();if(typeof submitHotspotLogin==='function')submitHotspotLogin();else{const f=document.getElementById('loginForm');if(f){prepareMikrotikLogin(f);f.submit();}}}},500);}",
                $html,
                1
            );

            $html = preg_replace(
                '/const voucherBtn=document\.createElement\(\'button\'\);[\s\S]*?insertAdjacentElement\(\'afterbegin\', voucherBtn\);\s*/',
                '',
                $html
            ) ?? $html;
            $html = preg_replace(
                '/const recoverBtn=document\.createElement\(\'button\'\);[\s\S]*?insertAdjacentElement\(\'afterbegin\', voucherBtn\);\s*/',
                '',
                $html
            ) ?? $html;

            if (strpos($html, 'id="voucherBtn"') !== false && strpos($html, "getElementById('voucherBtn')") === false) {
                $html = preg_replace(
                    '/(document\.getElementById\(\'loginBtn\'\)\.addEventListener\([\s\S]*?\}\);\s*)/',
                    "$1document.getElementById('voucherBtn').addEventListener('click',function(e){e.preventDefault();utiliserVoucher();});\n" .
                    "document.getElementById('recoverBtn').addEventListener('click',function(e){e.preventDefault();recupererForfait();});\n",
                    $html,
                    1
                );
            }

            if (strpos($html, 'id="voucherBtn"') === false && strpos($html, 'utiliserVoucher') !== false) {
                $html = preg_replace(
                    '/(<div class="divider">[\s\S]*?<\/div>\s*)\n<form action="\$\(link-login-only\)"/',
                    "$1\n<button type=\"button\" id=\"voucherBtn\" class=\"btn btn-secondary\" style=\"margin-bottom:10px\">🎫 J'ai un code</button>\n" .
                    "<button type=\"button\" id=\"recoverBtn\" class=\"btn btn-secondary\" style=\"margin-bottom:10px\">🔑 Récupérer mon forfait</button>\n\n<form action=\"\$(link-login-only)\"",
                    $html,
                    1
                );
                $html = preg_replace(
                    '/(document\.getElementById\(\'loginBtn\'\)\.addEventListener\([\s\S]*?\}\);\s*)/',
                    "$1document.getElementById('voucherBtn').addEventListener('click',function(e){e.preventDefault();utiliserVoucher();});\n" .
                    "document.getElementById('recoverBtn').addEventListener('click',function(e){e.preventDefault();recupererForfait();});\n",
                    $html,
                    1
                );
            }

            if (strpos($html, "loginFormEl.addEventListener('submit'") === false && strpos($html, 'id="loginForm"') !== false) {
                $html = preg_replace(
                    '/(document\.getElementById\(\'year\'\)\.innerText=new Date\(\)\.getFullYear\(\);\s*loadPlans\(\);)/',
                    "$1\nconst loginFormEl=document.getElementById('loginForm');\nif(loginFormEl){loginFormEl.addEventListener('submit',function(e){if(!prepareMikrotikLogin(loginFormEl))e.preventDefault();});}",
                    $html,
                    1
                );
            }

            return $html;
        }

        public static function stripHotspotLightTheme($html)
        {
            $html = preg_replace('/<div\s+class="theme-switch"[\s\S]*?<\/div>\s*<\/div>/', '', $html, 1) ?? $html;
            $html = preg_replace('/\/\*\s*=====+\s*TH[EÈ]?ME\s*=====+\s*\*\/[\s\S]*?(?=\/\*\s*=====+)/u', '', $html) ?? $html;
            $html = preg_replace('/function\s+toggleTheme\s*\(\)\s*\{[\s\S]*?\}\s*/', '', $html, 1) ?? $html;
            $html = preg_replace('/\(function\s+initTheme\s*\(\)\s*\{[\s\S]*?\}\)\(\)\s*;?/', '', $html, 1) ?? $html;
            $html = preg_replace(
                '/\}\s*else\s*\{\s*document\.body\.classList\.add\(\'light\'\);\s*localStorage\.setItem\(\'theme\',\s*\'light\'\);\s*\}\s*\}/',
                '',
                $html
            ) ?? $html;
            $html = preg_replace('/<body([^>]*)\sclass="[^"]*\blight\b[^"]*"/i', '<body$1', $html) ?? $html;

            $html = preg_replace_callback(
                '/<style([^>]*)>([\s\S]*?)<\/style>/i',
                static function ($match) {
                    $css = $match[2];
                    $css = preg_replace('/\/\*\s*=====+\s*TH[EÈ]ME CLAIR\s*=====+\s*\*\//u', '', $css) ?? $css;
                    $css = preg_replace('/body\.light[^{]*\{[^}]*\}/', '', $css) ?? $css;
                    $css = preg_replace('/\.theme-switch(?:-inner)?(?:::after)?\s*\{[^}]*\}/', '', $css) ?? $css;

                    return '<style' . $match[1] . '>' . $css . '</style>';
                },
                $html
            ) ?? $html;

            return $html;
        }

        public static function repairHotspotLoginHtml($html)
        {
            if (self::isModernSelfContainedHotspotLogin($html)) {
                return self::stripHotspotLightTheme($html);
            }

            if (strpos($html, '/* ===== CONNEXION ===== */') === false && strpos($html, 'function showVoucherError') !== false) {
                $html = preg_replace(
                    '/(?=function showVoucherError\s*\()/',
                    '/* ===== CONNEXION ===== */' . "\n",
                    $html,
                    1
                );
            }

            if (strpos($html, '/* ===== CHARGEMENT DES FORFAITS (API hotspot_plan) ===== */') !== false) {
                $html = preg_replace(
                    '/\/\* ===== CHARGEMENT DES FORFAITS \(API hotspot_plan\) ===== \*\/[\s\S]*?(?=\/\* ===== GESTION PAIEMENT ===== \*\/)/',
                    '/* ===== CHARGEMENT DES FORFAITS (API hotspot_plan) ===== */' . "\n" . self::buildHotspotPlansJsBlock() . "\n",
                    $html,
                    1
                );
            }

            $html = self::patchHotspotLoginPaymentBlock($html);

            $html = preg_replace(
                '/\n    \}\n\}\nreturn true;\n\}\n(?=\/\* ===== CONNEXION ===== \*\/)/',
                "\n    }\n",
                $html,
                1
            );
            $html = preg_replace(
                '/\n\}\nreturn true;\n\}\n(?=\/\* ===== CONNEXION ===== \*\/)/',
                "\n",
                $html,
                1
            );

            if (strpos($html, 'function prepareMikrotikLogin') === false) {
                if (strpos($html, '/* ===== CONNEXION ===== */') !== false) {
                    $html = str_replace(
                        '/* ===== CONNEXION ===== */',
                        '/* ===== CONNEXION ===== */' . "\n" . self::buildPrepareMikrotikLoginJs(),
                        $html
                    );
                } elseif (strpos($html, 'function showVoucherError') !== false) {
                    $html = preg_replace(
                        '/(?=function showVoucherError\s*\()/',
                        '/* ===== CONNEXION ===== */' . "\n" . self::buildPrepareMikrotikLoginJs() . "\n",
                        $html,
                        1
                    );
                }
            }

            $html = self::patchHotspotApiBases($html);

            if (strpos($html, 'function fillAndSubmitHotspotLogin') === false && strpos($html, 'function prepareMikrotikLogin') !== false) {
                $html = preg_replace(
                    '/(?=function prepareMikrotikLogin\s*\()/',
                    "function fillAndSubmitHotspotLogin(username, password) {\n    const form = document.getElementById('loginForm');\n    const userEl = document.getElementById('user') || document.getElementById('loginUsername');\n    const passEl = document.getElementById('pass') || document.getElementById('loginPassword');\n    if (userEl) userEl.value = username || '';\n    if (passEl) { passEl.value = normalizeHotspotPlainPassword(password || '123456'); delete passEl.dataset.chapDone; }\n    if (!form) return;\n    if (typeof Swal !== 'undefined' && Swal.close) Swal.close();\n    prepareMikrotikLogin(form);\n    form.submit();\n}\nfunction fillAndSubmitLogin(username, password) { fillAndSubmitHotspotLogin(username, password); }\n",
                    $html,
                    1
                );
            } elseif (preg_match('/function fillAndSubmitLogin\(username, password\) \{[\s\S]*?if \(form\) form\.submit\(\);\s*\}/', $html)) {
                $html = preg_replace(
                    '/function fillAndSubmitLogin\(username, password\) \{[\s\S]*?if \(form\) form\.submit\(\);\s*\}/',
                    'function fillAndSubmitLogin(username, password) { fillAndSubmitHotspotLogin(username, password); }',
                    $html,
                    1
                );
            }

            $html = self::patchModernHotspotChapLogin($html);

            return self::stripHotspotLightTheme($html);
        }

        public static function patchHotspotApiBases($html)
        {
            $replacement = <<<'JS'
    function hotspotApiBases() {
            const bases = [];
            const appBase = APP_URL ? String(APP_URL).replace(/\/$/, '') : '';
            const dnsName = (typeof HOTSPOT_DNS_NAME !== 'undefined' && HOTSPOT_DNS_NAME) ? String(HOTSPOT_DNS_NAME).trim() : '';
            const origin = (window.location.protocol === 'http:' || window.location.protocol === 'https:') ? window.location.origin : '';
            const isFilePreview = window.location.protocol === 'file:' || origin === 'null' || !origin;
            const isLocalPreview = /localhost|127\.0\.0\.1|:8080|ngrok/i.test(origin || '');
            function isPrivateHost(hostname) {
                return /^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/.test(hostname || '');
            }
            function isCaptivePortal() {
                if (!origin) return false;
                try { return isPrivateHost(new URL(origin).hostname); } catch (e) { return false; }
            }
            const onCaptive = isCaptivePortal();
            if (isLocalPreview && origin) bases.push(origin);
            // APP_URL (endpoint public) en priorité.
            if (appBase && !bases.includes(appBase)) bases.push(appBase);
            // Repli proxy local (VPN) uniquement si APP_URL échoue.
            if (onCaptive && origin) {
                const routerHost = (function () { try { return new URL(origin).hostname; } catch (e) { return ''; } })();
                const proxyBase = routerHost ? ('http://' + routerHost + ':8080') : '';
                if (proxyBase && !bases.includes(proxyBase)) bases.push(proxyBase);
            }
            if (dnsName && !isFilePreview && !onCaptive) {
                let scheme = 'http';
                let port = '';
                try {
                    const u = new URL(appBase || 'http://' + dnsName);
                    scheme = u.protocol.replace(':', '');
                    if (u.port && u.port !== '80' && u.port !== '443') port = ':' + u.port;
                } catch (e) {}
                const dnsBase = scheme + '://' + dnsName + port;
                if (dnsBase !== appBase && !bases.includes(dnsBase)) bases.push(dnsBase);
            }
            return [...new Set(bases.filter(Boolean))];
        }
        async function fetchHotspotEndpoint(route, options) {
            options = options || {};
            let lastError = null;
            const bases = hotspotApiBases();
            for (const base of bases) {
                try {
                    const response = await fetch(base + '/index.php?_route=plugin/' + route, options);
                    const contentType = (response.headers.get('content-type') || '').toLowerCase();
                    if (response.ok || contentType.indexOf('application/json') !== -1) return response;
                    lastError = new Error('HTTP ' + response.status + ' via ' + base);
                } catch (error) {
                    lastError = error;
                }
            }
            const hint = bases.length ? bases.join(' ou ') : (APP_URL || 'Hotspot API URL');
            throw lastError || new Error('API injoignable depuis le hotspot (' + hint + ')');
        }
    JS;

            $dnsLine = '';
            if (preg_match('/const HOTSPOT_DNS_NAME\s*=\s*[^;]+;/', $html, $dnsMatch)) {
                $dnsLine = '    ' . trim($dnsMatch[0]) . "\n";
            }

            $insert = "let CLIENT_MAC = '';\n"
                . $dnsLine
                . '    ' . trim($replacement) . "\n";

            // Remplace tout le bloc API (y compris fragments corrompus) jusqu'au commentaire MAC.
            if (preg_match('/let CLIENT_MAC\s*=\s*\'\'\s*;/', $html)) {
                $patched = preg_replace(
                    '/let CLIENT_MAC\s*=\s*\'\'\s*;[\s\S]*?(?=\/\/ Récupération MAC)/',
                    $insert,
                    $html,
                    1
                );
                if (is_string($patched) && $patched !== '') {
                    $html = $patched;
                }
            } elseif (strpos($html, 'const MAC_REGEX') !== false) {
                $patched = preg_replace(
                    '/(?:function hotspotApiBases|async function fetchHotspotEndpoint|const headers = Object\.assign)[\s\S]*?(?=const MAC_REGEX)/',
                    $insert,
                    $html,
                    1
                );
                if (is_string($patched) && $patched !== '') {
                    $html = $patched;
                }
            }

            $html = preg_replace(
                '/\s*if\s*\(\s*!isLocalPreview\s*&&\s*origin\s*&&\s*origin\s*!==\s*appBase\s*\)\s*bases\.push\(origin\)\s*;/',
                '',
                $html
            ) ?? $html;

            return $html;
        }

        public static function patchHotspotLoginPaymentBlock($html)
        {
            if (strpos($html, '/* ===== GESTION PAIEMENT ===== */') === false) {
                return $html;
            }

            $block = '/* ===== GESTION PAIEMENT ===== */' . "\n" . self::buildHotspotPaymentJsBlock();

            if (strpos($html, '/* ===== CONNEXION ===== */') !== false) {
                $pattern = '/\/\* ===== GESTION PAIEMENT ===== \*\/[\s\S]*?(?=\/\* ===== CONNEXION ===== \*\/)/';
                $replaced = preg_replace($pattern, $block . "\n", $html, 1);
                if (is_string($replaced) && $replaced !== $html) {
                    return $replaced;
                }
            }

            return $html;
        }

        public static function syncHotspotCaptivePaymentUi($adminId = null)
        {
            global $UPLOAD_PATH;
            if (empty($UPLOAD_PATH)) {
                return false;
            }

            $syncFile = static function ($loginFile) {
                if (!is_file($loginFile)) {
                    return false;
                }
                $html = file_get_contents($loginFile);
                if ($html === false) {
                    return false;
                }
                $patched = self::repairHotspotLoginHtml($html);
                if ($patched === $html) {
                    return false;
                }

                return file_put_contents($loginFile, $patched) !== false;
            };

            if ($adminId !== null && (int) $adminId > 0) {
                return $syncFile(WifiZoneHotspot::hotspotLoginHtmlPath((int) $adminId, $UPLOAD_PATH));
            }

            $synced = false;
            foreach (glob($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot' . DIRECTORY_SEPARATOR . 'admin_*' . DIRECTORY_SEPARATOR . 'login.html') ?: [] as $loginFile) {
                if ($syncFile($loginFile)) {
                    $synced = true;
                }
            }
            $legacyFile = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'mikrotik_hotspot' . DIRECTORY_SEPARATOR . 'login.html';
            if ($syncFile($legacyFile)) {
                $synced = true;
            }

            return $synced;
        }
    }
