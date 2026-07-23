(function () {
    'use strict';

    var cfg = window.PLAN_RECHARGE_CONFIRM || {};
    if (!cfg.isMobileMoney) {
        return;
    }

    var form = document.getElementById('plan-recharge-confirm-form');
    var submitBtn = document.getElementById('plan-recharge-submit-btn');
    var backdrop = document.getElementById('planMomoBackdrop');
    var phoneInput = document.getElementById('planMomoPhone');
    var amountEl = document.getElementById('planMomoAmount');
    var hintEl = document.getElementById('planMomoHint');
    var payBtn = document.getElementById('planMomoPay');
    var cancelBtn = document.getElementById('planMomoCancel');
    var PAY_WAIT_SECONDS = 120;
    var PAY_POLL_START_DELAY_MS = 10000;
    var payCountdownTimer = null;
    var waitingForPayment = false;

    function formatAmount(value) {
        var n = Number(value || 0);
        try {
            return new Intl.NumberFormat('fr-FR').format(n) + ' ' + (cfg.currency || 'XAF');
        } catch (e) {
            return n + ' ' + (cfg.currency || 'XAF');
        }
    }

    function normalizePhone(raw) {
        return String(raw || '').replace(/\D/g, '').slice(-9);
    }

    function defaultLocalPhone() {
        var raw = normalizePhone(cfg.defaultPhone || '');
        if (raw.length >= 9) {
            return raw.slice(-9);
        }
        return raw;
    }

    function showModal() {
        if (waitingForPayment) {
            return;
        }
        if (amountEl) {
            amountEl.textContent = formatAmount(cfg.amount);
        }
        if (phoneInput) {
            phoneInput.value = defaultLocalPhone();
            setTimeout(function () { phoneInput.focus(); }, 80);
        }
        if (hintEl) {
            hintEl.textContent = (cfg.planName || 'PPPoE') + ' · ' + (cfg.gatewayLabel || 'Mobile Money');
        }
        if (backdrop) {
            backdrop.classList.add('show');
            backdrop.setAttribute('aria-hidden', 'false');
        }
        document.body.style.overflow = 'hidden';
    }

    function hideModal() {
        if (waitingForPayment) {
            return;
        }
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.setAttribute('aria-hidden', 'true');
        }
        document.body.style.overflow = '';
    }

    function clearPayCountdown() {
        if (payCountdownTimer) {
            clearInterval(payCountdownTimer);
            payCountdownTimer = null;
        }
    }

    function buildWaitHtml(planLabel, amount, operator, ussd, secondsLeft) {
        return '<div style="text-align:center;padding:4px 2px">' +
            '<div style="width:58px;height:58px;margin:0 auto 16px;border:4px solid rgba(16,185,129,.18);border-top-color:#10b981;border-radius:50%;animation:planPaySpin 1s linear infinite"></div>' +
            '<p style="font-size:15px;line-height:1.5;margin:0 0 8px"><strong>En attente de confirmation CamPay</strong></p>' +
            '<p style="font-size:14px;color:#64748b;margin:0 0 10px">' + (planLabel || 'Forfait PPPoE') + ' — <strong>' + formatAmount(amount) + '</strong></p>' +
            '<div style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);border-radius:14px;padding:14px;margin:12px 0;text-align:left;font-size:14px;line-height:1.55">' +
            '📲 Validez la transaction sur le téléphone' + (operator ? ' (<strong>' + operator + '</strong>)' : '') + '.<br>Entrez votre <strong>code PIN</strong> Mobile Money.' +
            (ussd ? '<br><br>Sinon composez : <strong style="color:#10b981;font-size:20px">' + ussd + '</strong>' : '') +
            '</div>' +
            '<p id="planPayCountdown" style="font-size:13px;color:#64748b;margin:12px 0 0">Temps restant : ' + secondsLeft + ' secondes…</p>' +
            '<p style="font-size:12px;color:#94a3b8;margin:8px 0 0"><strong>Ne fermez pas cette fenêtre</strong> tant que le paiement n\'est pas confirmé.</p></div>';
    }

    function injectSpinnerStyle() {
        if (document.getElementById('planPaySpinStyle')) {
            return;
        }
        var style = document.createElement('style');
        style.id = 'planPaySpinStyle';
        style.textContent = '@keyframes planPaySpin{to{transform:rotate(360deg)}}';
        document.head.appendChild(style);
    }

    function pollPayment(paymentId, maxSeconds) {
        var deadline = Date.now() + (maxSeconds * 1000);
        var started = false;

        return new Promise(function (resolve) {
            function scheduleNext(delayMs) {
                setTimeout(tick, delayMs);
            }

            function tick() {
                if (!started) {
                    started = true;
                    scheduleNext(PAY_POLL_START_DELAY_MS);
                    return;
                }
                fetch(cfg.statusUrl + '&payment_id=' + encodeURIComponent(paymentId), {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.ok && data.status === 'paid') {
                            resolve({ status: 'paid', data: data });
                            return;
                        }
                        if (data.pending || data.status === 'pending') {
                            if (Date.now() >= deadline) {
                                resolve({ status: 'timeout', data: data });
                            } else {
                                scheduleNext(2500);
                            }
                            return;
                        }
                        if (data.status === 'failed') {
                            resolve({ status: 'failed', data: data });
                            return;
                        }
                        if (Date.now() >= deadline) {
                            resolve({ status: 'timeout', data: data });
                        } else {
                            scheduleNext(2500);
                        }
                    })
                    .catch(function () {
                        if (Date.now() >= deadline) {
                            resolve({ status: 'timeout', data: {} });
                        } else {
                            scheduleNext(2500);
                        }
                    });
            }
            tick();
        });
    }

    function openPaymentWaitPopup(paymentId, planLabel, amount, operator, ussd) {
        injectSpinnerStyle();
        waitingForPayment = true;
        hideModal();

        if (typeof Swal === 'undefined') {
            waitingForPayment = false;
            alert('Validez le paiement sur votre téléphone. Cette page va vérifier automatiquement.');
            pollPayment(paymentId, PAY_WAIT_SECONDS).then(handlePollResult);
            return;
        }

        var secondsLeft = PAY_WAIT_SECONDS;
        Swal.fire({
            title: 'Paiement en cours…',
            html: buildWaitHtml(planLabel, amount, operator, ussd, secondsLeft),
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () {
                payCountdownTimer = setInterval(function () {
                    secondsLeft -= 1;
                    var el = document.getElementById('planPayCountdown');
                    if (el) {
                        el.textContent = secondsLeft > 0
                            ? ('Temps restant : ' + secondsLeft + ' secondes…')
                            : 'Vérification finale auprès de CamPay…';
                    }
                    if (secondsLeft <= 0) {
                        clearPayCountdown();
                    }
                }, 1000);
                pollPayment(paymentId, PAY_WAIT_SECONDS).then(function (result) {
                    clearPayCountdown();
                    waitingForPayment = false;
                    handlePollResult(result);
                });
            },
            willClose: clearPayCountdown
        });
    }

    function handlePollResult(result) {
        var data = result.data || {};
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        if (result.status === 'paid') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Paiement confirmé',
                    text: data.message || 'Forfait activé avec succès.',
                    confirmButtonText: 'OK'
                }).then(function () {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                });
            } else if (data.redirect) {
                window.location.href = data.redirect;
            }
            return;
        }

        var message = data.message || (result.status === 'timeout'
            ? 'Délai dépassé. Si vous avez validé sur votre téléphone, rechargez cette page ou contactez le support avec votre référence de paiement.'
            : 'Paiement échoué. Le forfait n\'a pas été activé.');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: result.status === 'timeout' ? 'warning' : 'error',
                title: result.status === 'timeout' ? 'En attente' : 'Échec du paiement',
                text: message
            });
        } else {
            alert(message);
        }
    }

    function setBusy(busy) {
        if (payBtn) {
            payBtn.disabled = !!busy;
            payBtn.innerHTML = busy ? '<i class="fa fa-spinner fa-spin"></i> En cours…' : '<i class="fa fa-mobile"></i> Payer';
        }
        if (submitBtn) {
            submitBtn.disabled = !!busy;
        }
    }

    function collectPayment() {
        var phone = normalizePhone(phoneInput ? phoneInput.value : '');
        if (phone.length !== 9) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Numéro invalide', 'Entrez un numéro Mobile Money valide (9 chiffres).', 'warning');
            } else {
                alert('Numéro invalide');
            }
            return;
        }

        setBusy(true);
        var body = new URLSearchParams();
        body.set('csrf_token', cfg.csrfToken || '');
        body.set('id_customer', String(cfg.customerId || ''));
        body.set('plan', String(cfg.planId || ''));
        body.set('server', cfg.server || '');
        body.set('phone', phone);

        fetch(cfg.collectUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                setBusy(false);
                if (data.ok && data.status === 'paid') {
                    handlePollResult({ status: 'paid', data: data });
                    return;
                }
                if (!data.ok) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Échec', data.message || 'Impossible d\'initier le paiement.', 'error');
                    } else {
                        alert(data.message || 'Échec');
                    }
                    return;
                }
                openPaymentWaitPopup(
                    data.payment_id,
                    data.plan_label || cfg.planName,
                    data.amount || cfg.amount,
                    data.operator || '',
                    data.ussd || ''
                );
            })
            .catch(function () {
                setBusy(false);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Erreur', 'Connexion impossible. Réessayez.', 'error');
                } else {
                    alert('Connexion impossible');
                }
            });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            showModal();
        });
    }

    if (payBtn) {
        payBtn.addEventListener('click', collectPayment);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideModal);
    }
    if (backdrop) {
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop && !waitingForPayment) {
                hideModal();
            }
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && backdrop && backdrop.classList.contains('show') && !waitingForPayment) {
            hideModal();
        }
    });
})();
