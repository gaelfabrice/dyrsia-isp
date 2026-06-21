(function () {
    'use strict';

    function $(id) {
        return document.getElementById(id);
    }

    function val(id) {
        var el = $(id);
        if (!el) return '';
        if (el.type === 'checkbox') return el.checked;
        return String(el.value || '').trim();
    }

    function setVal(id, value) {
        var el = $(id);
        if (!el) return;
        if (el.type === 'checkbox') {
            el.checked = value === '1' || value === 1 || value === true;
            return;
        }
        el.value = value == null ? '' : String(value);
    }

    function setText(id, text) {
        var el = $(id);
        if (el) el.textContent = text || '—';
    }

    function setSyncStatus(kind, message) {
        var box = $('ps-sync-status');
        if (!box) return;
        box.className = 'ps-sync-status' + (kind ? ' ' + kind : '');
        var icon = kind === 'loading' ? 'fa-spinner fa-spin' : (kind === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle');
        box.innerHTML = message ? '<i class="fa ' + icon + '"></i><span>' + message + '</span>' : '';
        box.style.display = message ? 'flex' : 'none';
    }

    function updateSummary() {
        var router = val('pppoe_setup_router');
        var routerEl = $('pppoe_setup_router');
        var routerLabel = router;
        if (routerEl && routerEl.selectedIndex >= 0) {
            routerLabel = routerEl.options[routerEl.selectedIndex].text || router;
        }

        var bridge = val('pppoe_setup_bridge_name') || 'bridge-pppoe';
        var ports = val('pppoe_setup_bridge_ports') || '—';
        var gateway = val('pppoe_setup_gateway') || '—';
        var poolName = val('pppoe_setup_pool_name') || '—';
        var poolRange = val('pppoe_setup_pool_range') || '—';
        var service = val('pppoe_setup_service_name') || 'internet';
        var profDef = val('pppoe_setup_profile_default') || 'default';
        var profExp = val('pppoe_setup_profile_expire') || 'EXPIRE';
        var natIface = val('pppoe_setup_nat_interface') || 'ether1';
        var natOn = val('pppoe_setup_nat_masquerade');

        setText('ps-sum-router', routerLabel || '—');
        setText('ps-sum-bridge', bridge + ' · ' + gateway);
        setText('ps-sum-pool', poolName + ' (' + poolRange + ')');
        setText('ps-sum-service', service + ' @ ' + (val('pppoe_setup_server_interface') || bridge));
        setText('ps-sum-profiles', profDef + ' + ' + profExp);
        setText('ps-sum-nat', natOn ? ('masquerade → ' + natIface) : 'désactivé');

        setText('ps-diag-ports', ports.replace(/,/g, ', '));
        setText('ps-diag-bridge', bridge + ' · ' + gateway);
        setText('ps-diag-pool', poolRange);
        setText('ps-diag-wan', natIface + ' → Internet');
    }

    function applySuggested(suggested, preserveEdits) {
        if (!suggested) return;
        var fields = [
            'pppoe_setup_bridge_name', 'pppoe_setup_bridge_ports', 'pppoe_setup_gateway',
            'pppoe_setup_pool_name', 'pppoe_setup_pool_range', 'pppoe_setup_profile_default',
            'pppoe_setup_profile_expire', 'pppoe_setup_expire_rate_limit', 'pppoe_setup_dns_servers',
            'pppoe_setup_service_name', 'pppoe_setup_server_interface', 'pppoe_setup_max_mru',
            'pppoe_setup_max_mtu', 'pppoe_setup_expired_list', 'pppoe_setup_nat_interface'
        ];

        fields.forEach(function (key) {
            var el = $(key);
            if (!el) return;
            if (preserveEdits && String(el.value || '').trim() !== '') return;
            if (suggested[key] != null && String(suggested[key]).trim() !== '') {
                setVal(key, suggested[key]);
            }
        });

        ['pppoe_setup_dns_allow_remote', 'pppoe_setup_one_session', 'pppoe_setup_nat_masquerade'].forEach(function (key) {
            var el = $(key);
            if (!el) return;
            if (preserveEdits && el.hasAttribute('data-user-touched')) return;
            if (suggested[key] != null) setVal(key, suggested[key]);
        });

        updateSummary();
    }

    function renderPortHints(interfaces) {
        var box = $('ps-port-hints');
        if (!box) return;
        if (!interfaces || !interfaces.length) {
            box.textContent = '';
            return;
        }
        var ethers = interfaces.filter(function (i) {
            return i.type === 'ether' || i.type === 'sfp';
        }).map(function (i) { return i.name; });
        box.textContent = ethers.length
            ? 'Interfaces physiques détectées : ' + ethers.join(', ')
            : '';
    }

    function fetchSnapshot(preserveEdits) {
        var routerEl = $('pppoe_setup_router');
        var fetchUrl = window.PPPOE_FETCH_URL || '';
        if (!routerEl || !fetchUrl) return Promise.resolve();
        var router = String(routerEl.value || '').trim();
        if (!router) {
            setSyncStatus('error', 'Sélectionnez un routeur.');
            return Promise.resolve();
        }

        setSyncStatus('loading', 'Lecture PPPoE sur ' + router + '…');
        var url = fetchUrl + (fetchUrl.indexOf('?') >= 0 ? '&' : '?') + 'router=' + encodeURIComponent(router);

        return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.ok === false) {
                    throw new Error((data && data.message) || 'Synchronisation impossible.');
                }
                applySuggested(data.suggested || {}, preserveEdits);
                renderPortHints(data.interfaces || []);
                var errCount = (data.errors || []).length;
                setSyncStatus('ok', 'Config lue depuis le routeur' + (errCount ? ' (' + errCount + ' avert.)' : '') + '.');
            })
            .catch(function (err) {
                setSyncStatus('error', err.message || 'Erreur réseau.');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.ps-live-check').forEach(function (el) {
            el.addEventListener('change', function () {
                el.setAttribute('data-user-touched', '1');
                updateSummary();
            });
        });

        document.querySelectorAll('.ps-live, #pppoe_setup_router').forEach(function (el) {
            el.addEventListener('input', updateSummary);
            el.addEventListener('change', updateSummary);
        });

        var routerEl = $('pppoe_setup_router');
        if (routerEl) {
            routerEl.addEventListener('change', function () {
                updateSummary();
                fetchSnapshot(false);
            });
        }

        var syncBtn = $('ps-sync-btn');
        if (syncBtn) {
            syncBtn.addEventListener('click', function () {
                fetchSnapshot(true);
            });
        }

        var sendBtn = $('ps-send-mikrotik');
        if (sendBtn) {
            sendBtn.addEventListener('click', function (e) {
                if (!val('pppoe_setup_router')) {
                    e.preventDefault();
                    setSyncStatus('error', 'Sélectionnez un routeur avant l\'envoi.');
                }
            });
        }

        updateSummary();

        var initial = window.PPPOE_INITIAL_ROUTER || '';
        if (initial && routerEl && String(routerEl.value || '') === initial) {
            fetchSnapshot(true);
        }
    });
})();
