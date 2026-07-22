(function () {
    'use strict';

    var POLL_MS = 5 * 60 * 1000;
    var alertsUrl = window.ROUTER_ALERTS_URL || '';
    var dismissUrl = window.ROUTER_DISMISS_URL || '';
    var routersUrl = window.ROUTER_LIST_URL || '';
    var shownIds = {};

    function ensureContainer() {
        var el = document.getElementById('router-alert-toasts');
        if (!el) {
            el = document.createElement('div');
            el.id = 'router-alert-toasts';
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        return el;
    }

    function renderToast(alert) {
        if (shownIds[alert.id]) {
            return;
        }
        shownIds[alert.id] = true;

        var box = document.createElement('div');
        box.className = 'router-alert-toast';
        box.dataset.alertId = alert.id;

        var text = document.createElement('div');
        text.className = 'router-alert-toast-body';
        text.innerHTML = '<strong>' + escapeHtml(alert.message) + '</strong>'
            + '<br><small>Dernier check : ' + escapeHtml(alert.last_check) + '</small>';

        var actions = document.createElement('div');
        actions.className = 'router-alert-toast-actions';

        var detail = document.createElement('a');
        detail.href = routersUrl;
        detail.className = 'router-alert-link';
        detail.textContent = 'Voir les routeurs';

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'router-alert-close';
        closeBtn.setAttribute('aria-label', 'Fermer');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function () {
            dismissAlert(alert.id, box);
        });

        actions.appendChild(detail);
        actions.appendChild(closeBtn);
        box.appendChild(text);
        box.appendChild(actions);

        text.addEventListener('click', function () {
            window.location.href = routersUrl;
        });

        ensureContainer().appendChild(box);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function dismissAlert(id, el) {
        if (el) {
            el.classList.add('router-alert-toast-hide');
            setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 280);
        }

        if (!dismissUrl) {
            return;
        }

        var body = new URLSearchParams();
        body.append('id', id);

        fetch(dismissUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
            credentials: 'same-origin'
        }).catch(function () {});
    }

    function fetchAlerts() {
        if (!alertsUrl) {
            return;
        }
        if (window.HS_SYNC_IN_PROGRESS) {
            return;
        }
        fetch(alertsUrl, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var alerts = (data && data.alerts) ? data.alerts : [];
                var activeIds = {};
                alerts.forEach(function (a) {
                    activeIds[a.id] = true;
                    renderToast(a);
                });
                document.querySelectorAll('.router-alert-toast').forEach(function (node) {
                    var id = node.dataset.alertId;
                    if (id && !activeIds[id]) {
                        dismissAlert(id, node);
                        delete shownIds[id];
                    }
                });
            })
            .catch(function () {});
    }

    if (alertsUrl) {
        window.hsRefreshRouterAlerts = fetchAlerts;
        fetchAlerts();
        setInterval(fetchAlerts, POLL_MS);
    }
})();
