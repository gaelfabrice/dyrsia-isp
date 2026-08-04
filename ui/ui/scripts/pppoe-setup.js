(function () {
    'use strict';

    var lastSnapshot = null;
    var lastSnapshotAt = 0;
    var lastSnapshotRouter = '';
    var portPickerBound = false;

    function guessRouterModelLabel(portCount) {
        if (portCount >= 10) return 'RB2011UiAS';
        if (portCount >= 8) return 'L009UiGS-RM';
        if (portCount >= 5) return 'hEX / RB750Gr3';
        return 'RouterBOARD';
    }

    function getPppoeBridgePortsOnRouter(data, pppoeBridge) {
        data = data || {};
        var ports = data.bridge_ports && data.bridge_ports[pppoeBridge];
        return Array.isArray(ports) ? ports.slice() : [];
    }

    function getHotspotBridgeName(data) {
        data = data || {};
        if (data.suggested && data.suggested.hotspot_interface) {
            var name = String(data.suggested.hotspot_interface || '').trim();
            if (name !== '') {
                return name;
            }
        }
        return 'bridge-hotspot';
    }

    function getHotspotBridgePortsOnRouter(data, hotspotBridge) {
        data = data || {};
        hotspotBridge = hotspotBridge || getHotspotBridgeName(data);
        var ports = data.bridge_ports && data.bridge_ports[hotspotBridge];
        if (Array.isArray(ports) && ports.length) {
            return ports.slice();
        }
        if (data.suggested && data.suggested.hotspot_bridge_ports) {
            return parsePortsCsv(data.suggested.hotspot_bridge_ports);
        }
        return [];
    }

    function isHotspotReservedPort(portName, portBridgeMap, pppoeBridge, hotspotBridge, hotspotPorts) {
        if ((hotspotPorts || []).some(function (p) { return portEquals(p, portName); })) {
            return true;
        }
        var bridge = getPortBridge(portName, portBridgeMap, pppoeBridge);
        return bridge !== '' && portEquals(bridge, hotspotBridge);
    }

    function filterPortsForPppoeSelection(ports, data) {
        data = data || lastSnapshot || {};
        var pppoeBridge = getPppoeBridgeName();
        var portBridgeMap = buildPortBridgeMap(data);
        var hotspotBridge = getHotspotBridgeName(data);
        var hotspotPorts = getHotspotBridgePortsOnRouter(data, hotspotBridge);
        return (ports || []).filter(function (p) {
            return !isWanPort(p)
                && !isHotspotReservedPort(p, portBridgeMap, pppoeBridge, hotspotBridge, hotspotPorts);
        });
    }

    function resolvePortVisualState(portName, selected, portBridgeMap, pppoeBridge, routerPppoePorts, hotspotBridge, hotspotPorts) {
        if (isWanPort(portName)) {
            return {
                state: 'wan',
                role: 'WAN',
                title: 'Port 1 (ether1) — WAN Internet, non modifiable'
            };
        }
        if (isHotspotReservedPort(portName, portBridgeMap, pppoeBridge, hotspotBridge, hotspotPorts)) {
            return {
                state: 'hotspot',
                role: 'Hotspot',
                title: 'Port sur « ' + hotspotBridge + ' » (Hotspot) — non disponible pour PPPoE'
            };
        }
        var isSelected = isPortInList(portName, selected);
        var onRouter = (routerPppoePorts || []).some(function (p) { return portEquals(p, portName); });
        if (isSelected || onRouter) {
            return {
                state: 'configured',
                role: 'PPPoE',
                title: onRouter && !isSelected
                    ? 'Déjà sur bridge PPPoE — cliquer pour retirer de la sélection'
                    : (isSelected ? 'Cliquer pour retirer du bridge PPPoE' : 'Configuré sur le routeur')
            };
        }
        var otherBridge = getPortBridge(portName, portBridgeMap, pppoeBridge);
        var hint = otherBridge ? (' (actuellement « ' + otherBridge + ' »)') : '';
        return {
            state: 'free',
            role: 'Libre',
            title: 'Port libre' + hint + ' — cliquer pour ajouter au bridge PPPoE'
        };
    }

    function buildPortButtonHtml(portName, visual) {
        visual = visual || {};
        var state = visual.state || 'free';
        var cls = 'ps-port-btn ' + state + (isWlanPort(portName) ? ' wlan' : '');
        var disabled = (state === 'wan' || state === 'hotspot') ? ' disabled="disabled" aria-disabled="true"' : '';
        return '<button type="button" class="' + cls + '" data-port="' + escapeHtml(portName) + '" data-state="' + state + '" title="' + escapeHtml(visual.title || '') + '"' + disabled + '>'
            + '<span class="ps-port-led" aria-hidden="true"></span>'
            + '<span class="ps-port-jack-wrap" aria-hidden="true">'
            + '<span class="ps-port-jack">'
            + '<span class="ps-port-jack-tab"></span>'
            + '<span class="ps-port-jack-hole"></span>'
            + '<span class="ps-port-jack-pins"></span>'
            + '</span></span>'
            + '<span class="ps-port-label">' + escapeHtml(portName) + '</span>'
            + '<span class="ps-port-role">' + escapeHtml(visual.role || '') + '</span>'
            + '</button>';
    }

    function handlePortPickerClick(event) {
        var root = $('ps-router-port-picker');
        if (!root || !root.contains(event.target)) return;

        var btn = event.target.closest('.ps-port-btn');
        if (!btn || btn.classList.contains('wan') || btn.classList.contains('hotspot')
            || btn.getAttribute('data-state') === 'wan' || btn.getAttribute('data-state') === 'hotspot') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        var port = btn.getAttribute('data-port');
        if (!port) return;

        var portsField = $('pppoe_setup_bridge_ports');
        if (portsField) {
            portsField.dataset.userTouched = '1';
        }

        var current = filterPortsForPppoeSelection(getBridgePortsValue(), lastSnapshot);
        if (isPortInList(port, current)) {
            current = current.filter(function (p) { return !portEquals(p, port); });
        } else {
            current.push(port);
        }
        current = sortPortsNatural(current);
        syncPortsInputFromPicker(current);

        var snap = lastSnapshot || { physical_port_count: Math.max(5, current.length + 1) };
        renderRouterPortPicker(snap);
        renderPortHints(snap);
        updateSummary();
    }

    function ensurePortPickerBinding() {
        if (portPickerBound) return;
        document.addEventListener('click', handlePortPickerClick, true);
        portPickerBound = true;
    }
    var fetchInFlight = null;
    var fetchAbortController = null;
    var deployInFlight = false;
    var SYNC_CACHE_TTL_MS = 120000;
    var SYNC_FETCH_TIMEOUT_MS = 40000;
    var DEPLOY_TIMEOUT_MS = 600000;
    var DEPLOY_VERIFY_TIMEOUT_MS = 90000;
    var DEPLOY_START_TIMEOUT_MS = 90000;
    var DEPLOY_POLL_INTERVAL_MS = 2000;
    var DEPLOY_POLL_MAX_MS = 1200000;
    var PPPOE_DEPLOY_JOB_STORAGE_KEY = 'dyrsia_pppoe_deploy_job';

    var psDeployUi = {
        overlay: null,
        statusEl: null,
        barEl: null,
        elapsedEl: null,
        titleEl: null,
        timer: null,
        startedAt: 0,
        lastPct: 0
    };

    function psDeployFormatElapsed(startedAt) {
        var secs = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
        var mins = Math.floor(secs / 60);
        var rem = secs % 60;
        return mins > 0 ? (mins + ' min ' + rem + ' s') : (secs + ' s');
    }

    function psDeployProgressFromMessage(message) {
        var m = String(message || '').toLowerCase();
        if (m.indexOf('hotspot') >= 0 || m.indexOf('dhcp') >= 0) {
            return 28;
        }
        if (m.indexOf('forfait') >= 0 || m.indexOf('profil') >= 0) {
            return 72;
        }
        if (m.indexOf('portail') >= 0 || m.indexOf('expir') >= 0) {
            return 84;
        }
        if (m.indexOf('vpn') >= 0 || m.indexOf('démarr') >= 0 || m.indexOf('demarr') >= 0 || m.indexOf('connexion') >= 0) {
            return 8;
        }
        if (m.indexOf('en cours') >= 0) {
            return null;
        }
        return null;
    }

    function psDeployEnsureUi() {
        if (!psDeployUi.overlay) {
            psDeployUi.overlay = document.getElementById('ps-deploy-overlay');
            psDeployUi.statusEl = document.getElementById('ps-deploy-status');
            psDeployUi.barEl = document.getElementById('ps-deploy-progress-bar');
            psDeployUi.elapsedEl = document.getElementById('ps-deploy-elapsed');
            psDeployUi.titleEl = document.getElementById('ps-deploy-title');
        }
    }

    function psUpdateDeployProgressBar(pct, indeterminate) {
        psDeployEnsureUi();
        if (!psDeployUi.barEl) {
            return;
        }
        if (indeterminate) {
            psDeployUi.barEl.classList.add('ps-deploy-indeterminate');
            psDeployUi.barEl.style.width = '';
            return;
        }
        psDeployUi.barEl.classList.remove('ps-deploy-indeterminate');
        var next = Math.max(psDeployUi.lastPct, Math.min(99, pct));
        psDeployUi.lastPct = next;
        psDeployUi.barEl.style.width = next + '%';
    }

    function psUpdateDeployProgress(message, pct) {
        psDeployEnsureUi();
        if (psDeployUi.statusEl && message) {
            psDeployUi.statusEl.textContent = message;
        }
        if (typeof pct === 'number') {
            psUpdateDeployProgressBar(pct, false);
        }
        if (psDeployUi.elapsedEl && psDeployUi.startedAt) {
            psDeployUi.elapsedEl.textContent = 'Durée : ' + psDeployFormatElapsed(psDeployUi.startedAt);
        }
    }

    function psDeployProgressTick() {
        if (!psDeployUi.startedAt) {
            return;
        }
        var elapsedMs = Date.now() - psDeployUi.startedAt;
        var maxMs = DEPLOY_POLL_MAX_MS;
        var timePct = 4 + (elapsedMs / maxMs) * 78;
        if (timePct > psDeployUi.lastPct) {
            psUpdateDeployProgressBar(timePct, false);
        }
        if (psDeployUi.elapsedEl) {
            psDeployUi.elapsedEl.textContent = 'Durée : ' + psDeployFormatElapsed(psDeployUi.startedAt);
        }
    }

    function psShowDeployProgress(message) {
        psDeployEnsureUi();
        psDeployUi.startedAt = Date.now();
        psDeployUi.lastPct = 0;
        if (psDeployUi.titleEl) {
            psDeployUi.titleEl.textContent = 'Déploiement PPPoE en cours';
        }
        if (psDeployUi.overlay) {
            psDeployUi.overlay.hidden = false;
            psDeployUi.overlay.setAttribute('aria-busy', 'true');
        }
        psUpdateDeployProgress(message || 'Connexion au routeur via VPN…', null);
        psUpdateDeployProgressBar(0, true);
        if (psDeployUi.timer) {
            clearInterval(psDeployUi.timer);
        }
        psDeployUi.timer = setInterval(psDeployProgressTick, 1000);
    }

    function psHideDeployProgress() {
        psDeployEnsureUi();
        if (psDeployUi.timer) {
            clearInterval(psDeployUi.timer);
            psDeployUi.timer = null;
        }
        if (psDeployUi.overlay) {
            psDeployUi.overlay.hidden = true;
            psDeployUi.overlay.setAttribute('aria-busy', 'false');
        }
        psDeployUi.startedAt = 0;
        psDeployUi.lastPct = 0;
        if (psDeployUi.barEl) {
            psDeployUi.barEl.classList.remove('ps-deploy-indeterminate');
            psDeployUi.barEl.style.width = '0';
        }
    }

    function httpErrorMessage(status) {
        if (status === 403) {
            return 'Session expirée ou jeton CSRF invalide — rechargez la page puis réessayez.';
        }
        if (status === 502 || status === 503 || status === 504) {
            return 'HTTP ' + status + ' — délai serveur dépassé (aaPanel/Nginx). Le déploiement peut quand même continuer sur le routeur : resynchronisez dans 1–2 min.';
        }
        return 'HTTP ' + status;
    }

    function postPppoeForm(setupForm, extraFields, timeoutMs) {
        var fd = new FormData(setupForm);
        fd.set('send_mikrotik', '1');
        Object.keys(extraFields || {}).forEach(function (key) {
            fd.set(key, extraFields[key]);
        });
        var csrfEl = setupForm.querySelector('input[name="csrf_token"]');
        var csrfVal = csrfEl ? String(csrfEl.value || '').trim() : '';
        if (csrfVal) {
            fd.set('csrf_token', csrfVal);
        }
        var fetchHeaders = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        if (csrfVal) {
            fetchHeaders['X-CSRF-Token'] = csrfVal;
        }
        var abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var abortTimer = abortController && timeoutMs ? setTimeout(function () {
            try { abortController.abort(); } catch (err) {}
        }, timeoutMs) : null;

        return fetch(setupForm.action, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: fetchHeaders,
            signal: abortController ? abortController.signal : undefined
        }).then(function (r) {
            return r.text().then(function (body) {
                if (!r.ok) {
                    throw new Error(httpErrorMessage(r.status));
                }
                try {
                    return JSON.parse(body);
                } catch (e) {
                    throw new Error('Réponse serveur invalide pendant le déploiement.');
                }
            });
        }).finally(function () {
            if (abortTimer) clearTimeout(abortTimer);
        });
    }

    function pollPppoeDeployJob(setupForm, jobId, startedAt) {
        var deadline = Date.now() + DEPLOY_POLL_MAX_MS;
        return new Promise(function (resolve, reject) {
            function tick() {
                if (Date.now() > deadline) {
                    reject(new Error('Délai dépassé (~20 min) en attendant la fin du déploiement PPPoE. '
                        + 'Vérifiez WireGuard puis relancez l\'envoi.'));
                    return;
                }
                postPppoeForm(setupForm, { ajax_deploy: 'status', job_id: jobId }, 30000)
                    .then(function (data) {
                        if (!data) {
                            reject(new Error('Réponse serveur vide.'));
                            return;
                        }
                        if (data.running) {
                            var elapsedLabel = psDeployFormatElapsed(startedAt);
                            var statusLine = (data.message || 'Déploiement PPPoE en cours…') + ' (' + elapsedLabel + ')';
                            var msgPct = psDeployProgressFromMessage(data.message);
                            psUpdateDeployProgress(statusLine, msgPct);
                            setSyncStatus('loading', statusLine);
                            setTimeout(tick, DEPLOY_POLL_INTERVAL_MS);
                            return;
                        }
                        if (!data.ok) {
                            reject(new Error(data.message || 'Déploiement PPPoE échoué.'));
                            return;
                        }
                        resolve(data);
                    })
                    .catch(reject);
            }
            tick();
        });
    }

    function abortSyncIfRunning() {
        if (fetchAbortController) {
            try {
                fetchAbortController.abort();
            } catch (err) {}
            fetchAbortController = null;
        }
        fetchInFlight = null;
    }

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

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function findActivePppoeServer(data, bridgeName, iface) {
        data = data || {};
        if (!Array.isArray(data.servers)) {
            return null;
        }
        var bridgeLc = String(bridgeName || '').toLowerCase();
        var ifaceLc = String(iface || bridgeName || '').toLowerCase();
        return data.servers.find(function (s) {
            if (!s || s.disabled) {
                return false;
            }
            var sIface = String(s.interface || '').toLowerCase();
            var sSvc = String(s.service_name || s['service-name'] || '').trim();
            if (!sIface || !sSvc) {
                return false;
            }
            return sIface === bridgeLc || sIface === ifaceLc;
        }) || null;
    }

    function renderPppoeServerStatus(data) {
        var box = $('ps-pppoe-server-status');
        if (!box) return;

        var bridge = val('pppoe_setup_bridge_name') || 'bridge-pppoe';
        var iface = val('pppoe_setup_server_interface') || bridge;
        var service = val('pppoe_setup_service_name') || 'internet';
        var active = findActivePppoeServer(data, bridge, iface);

        if (active) {
            box.className = 'ps-pppoe-server-status ok';
            box.innerHTML = '<i class="fa fa-check-circle"></i><span>Serveur PPPoE actif sur le routeur : '
                + '<strong>' + escapeHtml(active.service_name || service) + '</strong> @ '
                + '<strong>' + escapeHtml(active.interface || iface) + '</strong>'
                + ' (profil ' + escapeHtml(active.default_profile || 'default') + ')</span>';
            return;
        }

        box.className = 'ps-pppoe-server-status warn';
        box.innerHTML = '<i class="fa fa-info-circle"></i><span>Aucun serveur PPPoE actif sur '
            + '<strong>' + escapeHtml(iface) + '</strong>. Cliquez « Envoyer vers MikroTik » pour le créer.'
            + ' <em>(Le serveur n’apparaît pas dans Interfaces — voir PPP → PPPoE Server dans Winbox.)</em></span>';
    }

    function setSyncStatus(kind, message) {
        var box = $('ps-sync-status');
        if (!box) return;
        box.className = 'ps-sync-status' + (kind ? ' ' + kind : '');
        var icon = kind === 'loading' ? 'fa-spinner fa-spin' : (kind === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle');
        box.innerHTML = message ? '<i class="fa ' + icon + '"></i><span>' + message + '</span>' : '';
        box.style.display = message ? 'flex' : 'none';
    }

    function parsePortsCsv(value) {
        return String(value || '').split(',').map(function (p) { return p.trim(); }).filter(Boolean);
    }

    function portEquals(a, b) {
        return String(a || '').toLowerCase() === String(b || '').toLowerCase();
    }

    function isPortInList(port, list) {
        return (list || []).some(function (p) { return portEquals(p, port); });
    }

    function sortPortsNatural(ports) {
        return ports.slice().sort(function (a, b) {
            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        });
    }

    function isWlanPort(port) {
        return /^wlan/i.test(String(port || ''));
    }

    function isWanPort(portName) {
        return /^ether1$/i.test(String(portName || ''));
    }

    function getFallbackMemberPorts(data) {
        data = data || {};
        var count = parseInt(data.physical_port_count, 10) || 5;
        var ports = [];
        for (var i = 1; i <= Math.max(2, count); i++) {
            ports.push('ether' + i);
        }
        return ports;
    }

    function getBridgePortsValue() {
        var el = $('pppoe_setup_bridge_ports');
        return el ? parsePortsCsv(el.value) : [];
    }

    function getConfiguredHotspotPorts() {
        return parsePortsCsv(window.PPPOE_HOTSPOT_PORTS || '');
    }

    function getConfiguredManagementPorts() {
        return parsePortsCsv(window.PPPOE_MANAGEMENT_PORTS || 'ether2');
    }

    function syncPortsInputFromPicker(ports) {
        var el = $('pppoe_setup_bridge_ports');
        if (!el) return;
        el.value = (ports || []).join(',');
    }

    function getPppoeBridgeName() {
        return val('pppoe_setup_bridge_name') || 'bridge-pppoe';
    }

    function getMemberPorts(data) {
        data = data || {};
        var physical = Array.isArray(data.physical_ports) ? data.physical_ports.slice() : [];
        var wireless = Array.isArray(data.wireless_ports) ? data.wireless_ports.slice() : [];
        if (physical.length || wireless.length) {
            return sortPortsNatural(physical.concat(wireless));
        }
        if (Array.isArray(data.trunk_member_ports) && data.trunk_member_ports.length) {
            return data.trunk_member_ports.slice();
        }
        return [];
    }

    function buildPortBridgeMap(data) {
        data = data || {};
        var map = {};
        if (data.port_bridge_map && typeof data.port_bridge_map === 'object') {
            Object.keys(data.port_bridge_map).forEach(function (key) {
                map[String(key).toLowerCase()] = data.port_bridge_map[key];
            });
            return map;
        }
        var bridgePorts = data.bridge_ports || {};
        Object.keys(bridgePorts).forEach(function (bridgeName) {
            (bridgePorts[bridgeName] || []).forEach(function (portName) {
                map[String(portName).toLowerCase()] = bridgeName;
            });
        });
        return map;
    }

    function getPortBridge(portName, portBridgeMap, pppoeBridge) {
        var key = String(portName || '').toLowerCase();
        var bridge = portBridgeMap[key] || '';
        if (bridge && pppoeBridge && portEquals(bridge, pppoeBridge)) {
            return '';
        }
        return bridge;
    }

    function labelForSelect(id) {
        var el = $(id);
        if (!el || el.selectedIndex < 0) return '';
        return String(el.options[el.selectedIndex].text || '').split('—')[0].trim();
    }

    function renderRouterPortPicker(data) {
        var root = $('ps-router-port-picker');
        var legend = $('ps-port-legend');
        if (!root) return;

        ensurePortPickerBinding();
        data = data || lastSnapshot || {};
        var memberPorts = getMemberPorts(data);
        if (!memberPorts.length && val('pppoe_setup_router')) {
            memberPorts = getFallbackMemberPorts(data);
        }
        var pppoeBridge = getPppoeBridgeName();
        var portBridgeMap = buildPortBridgeMap(data);
        var routerPppoePorts = getPppoeBridgePortsOnRouter(data, pppoeBridge);
        var hotspotBridge = getHotspotBridgeName(data);
        var hotspotPorts = getHotspotBridgePortsOnRouter(data, hotspotBridge);

        function portAllowedForPppoe(portName) {
            return !isWanPort(portName)
                && !isHotspotReservedPort(portName, portBridgeMap, pppoeBridge, hotspotBridge, hotspotPorts);
        }

        if (!memberPorts.length) {
            root.innerHTML = '<div class="ps-port-picker-empty">Sélectionnez un routeur pour afficher les ports.</div>';
            if (legend) legend.style.display = 'none';
            renderPortConflict(data);
            return;
        }

        if (legend) legend.style.display = 'flex';

        var portsField = $('pppoe_setup_bridge_ports');
        var userTouched = portsField && portsField.dataset.userTouched === '1';
        var selected = getBridgePortsValue().filter(portAllowedForPppoe);

        if (!userTouched) {
            if (!selected.length && routerPppoePorts.length) {
                selected = sortPortsNatural(routerPppoePorts.filter(portAllowedForPppoe));
            }
            if (!selected.length && data.suggested && data.suggested.pppoe_setup_bridge_ports) {
                selected = parsePortsCsv(data.suggested.pppoe_setup_bridge_ports).filter(portAllowedForPppoe);
            }
            selected = sortPortsNatural(selected);
            syncPortsInputFromPicker(selected);
        }

        var routerLabel = labelForSelect('pppoe_setup_router') || 'Routeur';
        var portCount = data.physical_port_count || memberPorts.filter(function (p) { return !isWlanPort(p); }).length;
        var modelName = guessRouterModelLabel(portCount);
        var wanPorts = [];
        var lanPorts = [];

        memberPorts.forEach(function (portName) {
            if (isWanPort(portName)) {
                wanPorts.push(portName);
            } else {
                lanPorts.push(portName);
            }
        });
        if (!wanPorts.length) {
            wanPorts.push('ether1');
            lanPorts = lanPorts.filter(function (p) { return !isWanPort(p); });
        }

        var html = '<div class="ps-mtk-unit" role="group" aria-label="Ports Ethernet routeur">';
        html += '<div class="ps-mtk-rail" aria-hidden="true"></div>';
        html += '<div class="ps-mtk-body">';
        html += '<div class="ps-mtk-left">';
        html += '<div class="ps-mtk-logo" aria-hidden="true"></div>';
        html += '<div class="ps-mtk-meta">';
        html += '<span class="ps-mtk-brand">MikroTik</span>';
        html += '<span class="ps-mtk-model">' + escapeHtml(modelName) + '<br>' + escapeHtml(routerLabel) + '</span>';
        html += '</div>';
        html += '<div class="ps-mtk-led-row"><span class="ps-mtk-led" aria-hidden="true"></span><span class="ps-mtk-led sys" aria-hidden="true"></span><span class="ps-mtk-led-txt">Pwr · Act</span></div>';
        html += '<div class="ps-mtk-reset" aria-hidden="true" title="Reset"></div>';
        html += '</div>';
        html += '<div class="ps-mtk-ports-wrap">';
        html += '<div class="ps-mtk-ports-title">Ethernet · RB2011 / L009 style</div>';
        html += '<div class="ps-mtk-port-strip">';

        html += '<div class="ps-mtk-port-group wan-group">';
        html += '<span class="ps-mtk-group-label">WAN</span>';
        wanPorts.forEach(function (portName) {
            html += buildPortButtonHtml(portName, resolvePortVisualState(
                portName, selected, portBridgeMap, pppoeBridge, routerPppoePorts, hotspotBridge, hotspotPorts
            ));
        });
        html += '</div>';

        html += '<div class="ps-mtk-port-group lan-group">';
        html += '<span class="ps-mtk-group-label">LAN</span>';
        lanPorts.forEach(function (portName) {
            html += buildPortButtonHtml(portName, resolvePortVisualState(
                portName, selected, portBridgeMap, pppoeBridge, routerPppoePorts, hotspotBridge, hotspotPorts
            ));
        });
        html += '</div>';

        html += '</div></div></div></div>';
        root.innerHTML = html;
        renderPortConflict(data);
    }

    function renderPortHints(snapshot) {
        var box = $('ps-port-hints');
        if (!box) return;
        snapshot = snapshot || {};
        var selected = getBridgePortsValue();
        var physicalCount = parseInt(snapshot.physical_port_count, 10) || 0;
        var modelName = guessRouterModelLabel(physicalCount || 5);
        var hotspotBridge = getHotspotBridgeName(snapshot);
        var hotspotPorts = getHotspotBridgePortsOnRouter(snapshot, hotspotBridge);
        var hotspotHint = hotspotPorts.length
            ? (' · Hotspot bloqués : ' + hotspotPorts.join(', '))
            : (' · Ports « ' + hotspotBridge + ' » bloqués');
        if (physicalCount > 0) {
            box.textContent = modelName + ' · ' + physicalCount + ' port(s)'
                + ' · Vert = PPPoE · Orange = libre · ether1 WAN bloqué'
                + hotspotHint
                + (selected.length ? ' · Sélection : ' + selected.join(', ') : '');
            return;
        }
        if (val('pppoe_setup_router')) {
            box.textContent = 'Cliquez les ports orange (libres) ou vert (PPPoE) · ether1 WAN et ports Hotspot bloqués'
                + hotspotHint
                + (selected.length ? ' · ' + selected.join(', ') : '');
            return;
        }
        if (selected.length) {
            box.textContent = selected.length + ' port(s) sélectionné(s) : ' + selected.join(', ');
            return;
        }
        box.textContent = '';
    }

    function getCurrentPortConflictState(snapshot) {
        snapshot = snapshot || lastSnapshot || {};
        var selected = getBridgePortsValue();
        var hotspotBridge = getHotspotBridgeName(snapshot) || window.PPPOE_HOTSPOT_BRIDGE || 'bridge-hotspot';
        var hotspotPorts = getHotspotBridgePortsOnRouter(snapshot, hotspotBridge);
        if (!hotspotPorts.length) {
            hotspotPorts = getConfiguredHotspotPorts();
        }
        var managementPorts = getConfiguredManagementPorts();
        var overlapHotspot = selected.filter(function (port) {
            return isPortInList(port, hotspotPorts);
        });
        var overlapManagement = selected.filter(function (port) {
            return isPortInList(port, managementPorts);
        });
        return {
            hotspotBridge: hotspotBridge,
            hotspotPorts: hotspotPorts,
            managementPorts: managementPorts,
            overlapHotspot: overlapHotspot,
            overlapManagement: overlapManagement,
            hasConflict: overlapHotspot.length > 0 || overlapManagement.length > 0
        };
    }

    function renderPortConflict(snapshot) {
        var box = $('ps-port-conflict');
        if (!box) return;
        var state = getCurrentPortConflictState(snapshot);
        if (!state.hasConflict) {
            box.innerHTML = '';
            box.style.display = 'none';
            return;
        }

        var details = [];
        if (state.overlapHotspot.length) {
            details.push('Hotspot : ' + state.overlapHotspot.join(', ') + ' (bridge ' + state.hotspotBridge + ')');
        }
        if (state.overlapManagement.length) {
            details.push('Management : ' + state.overlapManagement.join(', '));
        }

        box.className = 'ps-sync-status error';
        box.innerHTML = '<i class="fa fa-exclamation-triangle"></i><span>Conflit de ports détecté. '
            + escapeHtml(details.join(' · '))
            + '. Retirez ces ports du bridge PPPoE avant l\'envoi.</span>';
        box.style.display = 'flex';
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
        renderPortConflict(lastSnapshot);
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
            var isPortField = key === 'pppoe_setup_bridge_ports';
            if (preserveEdits && !isPortField && String(el.value || '').trim() !== '') return;
            if (preserveEdits && isPortField && String(el.value || '').trim() !== '' && el.dataset.userTouched === '1') return;
            if (suggested[key] != null && String(suggested[key]).trim() !== '') {
                var value = suggested[key];
                if (key === 'pppoe_setup_bridge_name' || key === 'pppoe_setup_server_interface') {
                    var normalized = String(value).trim().toLowerCase();
                    if (normalized === 'bridge-hotspot' || normalized === 'bridge-lan') {
                        value = 'bridge-pppoe';
                    }
                }
                setVal(key, value);
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

    function isSyncCacheFresh(at) {
        return !!at && (Date.now() - at) < SYNC_CACHE_TTL_MS;
    }

    function fetchSnapshot(preserveEdits, forceFull) {
        var routerEl = $('pppoe_setup_router');
        var fetchUrl = window.PPPOE_FETCH_URL || '';
        if (!routerEl || !fetchUrl) return Promise.resolve();
        var router = String(routerEl.value || '').trim();
        if (!router) {
            setSyncStatus('', '');
            return Promise.resolve();
        }

        if (fetchInFlight) {
            return fetchInFlight;
        }

        if (!forceFull && lastSnapshot && lastSnapshotRouter === router && isSyncCacheFresh(lastSnapshotAt)) {
            applySuggested(lastSnapshot.suggested || {}, preserveEdits);
            renderRouterPortPicker(lastSnapshot);
            renderPortHints(lastSnapshot);
            renderPppoeServerStatus(lastSnapshot);
            setSyncStatus('ok', 'Synchronisé avec le routeur (cache).');
            return Promise.resolve(lastSnapshot);
        }

        if (fetchAbortController) {
            try {
                fetchAbortController.abort();
            } catch (e) {}
        }
        fetchAbortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var abortTimer = fetchAbortController ? setTimeout(function () {
            try {
                fetchAbortController.abort();
            } catch (e) {}
        }, SYNC_FETCH_TIMEOUT_MS) : null;

        setSyncStatus('loading', 'Synchronisation automatique avec ' + router + '…');
        var url = fetchUrl + (fetchUrl.indexOf('?') >= 0 ? '&' : '?') + 'router=' + encodeURIComponent(router);
        if (forceFull) {
            url += '&full_sync=1';
        }

        fetchInFlight = fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            signal: fetchAbortController ? fetchAbortController.signal : undefined
        })
            .then(function (r) {
                return r.text().then(function (body) {
                    if (!r.ok) {
                        throw new Error('HTTP ' + r.status);
                    }
                    try {
                        return JSON.parse(body);
                    } catch (e) {
                        throw new Error('Réponse serveur invalide pendant la synchronisation.');
                    }
                });
            })
            .then(function (data) {
                if (!data || data.ok === false) {
                    throw new Error((data && data.message) || 'Synchronisation impossible.');
                }
                lastSnapshot = data;
                lastSnapshotAt = Date.now();
                lastSnapshotRouter = router;
                applySuggested(data.suggested || {}, preserveEdits);
                renderRouterPortPicker(data);
                renderPortHints(data);
                renderPppoeServerStatus(data);
                var errCount = (data.errors || []).length;
                var activeServer = findActivePppoeServer(data, val('pppoe_setup_bridge_name'), val('pppoe_setup_server_interface'));
                var syncMsg = 'Synchronisé avec le routeur' + (errCount ? ' (' + errCount + ' avert.)' : '') + '.';
                if (activeServer) {
                    syncMsg += ' Serveur PPPoE : ' + (activeServer.service_name || 'internet')
                        + ' sur ' + (activeServer.interface || val('pppoe_setup_server_interface') || 'bridge-pppoe') + '.';
                }
                setSyncStatus('ok', syncMsg);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') {
                    setSyncStatus('error', 'Synchronisation interrompue (délai dépassé). Sélectionnez les ports manuellement ou réessayez.');
                } else {
                    setSyncStatus('error', (err.message || 'Erreur réseau.') + ' — sélection manuelle des ports possible.');
                }
                renderRouterPortPicker({ physical_port_count: 5 });
                renderPortHints({});
            })
            .finally(function () {
                if (abortTimer) {
                    clearTimeout(abortTimer);
                }
                fetchInFlight = null;
            });

        return fetchInFlight;
    }

    function autoSyncRouter(preserveEdits) {
        var routerEl = $('pppoe_setup_router');
        if (!routerEl) return;

        var router = String(routerEl.value || '').trim();
        if (!router && routerEl.options.length === 2) {
            routerEl.selectedIndex = 1;
            router = String(routerEl.value || '').trim();
            preserveEdits = false;
        }

        if (router) {
            updateSummary();
            fetchSnapshot(preserveEdits);
        }
    }

    function verifyPppoeServerOnRouter() {
        var fetchUrl = window.PPPOE_FETCH_URL || '';
        var router = val('pppoe_setup_router');
        if (!fetchUrl || !router) {
            return Promise.resolve({ ok: false });
        }
        var url = fetchUrl + (fetchUrl.indexOf('?') >= 0 ? '&' : '?') + 'router=' + encodeURIComponent(router);
        var abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var abortTimer = abortController ? setTimeout(function () {
            try { abortController.abort(); } catch (err) {}
        }, DEPLOY_VERIFY_TIMEOUT_MS) : null;

        return fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            signal: abortController ? abortController.signal : undefined
        })
            .then(function (r) {
                return r.text().then(function (body) {
                    if (!r.ok) {
                        throw new Error('HTTP ' + r.status);
                    }
                    try {
                        return JSON.parse(body);
                    } catch (e) {
                        throw new Error('Réponse serveur invalide.');
                    }
                });
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.servers)) {
                    return { ok: false, data: data };
                }
                var bridge = val('pppoe_setup_bridge_name') || 'bridge-pppoe';
                var iface = val('pppoe_setup_server_interface') || bridge;
                var found = !!findActivePppoeServer(data, bridge, iface);
                return { ok: found, data: data };
            })
            .catch(function () {
                return { ok: false };
            })
            .finally(function () {
                if (abortTimer) clearTimeout(abortTimer);
            });
    }

    function resetSendButton(sendBtn) {
        if (!sendBtn) return;
        sendBtn.disabled = false;
        var label = sendBtn.querySelector('.ps-send-label');
        if (label) {
            label.textContent = 'Envoyer vers MikroTik';
        }
    }

    function deployToMikrotik(setupForm, sendBtn) {
        if (!setupForm || !sendBtn || deployInFlight) return;
        if (!val('pppoe_setup_router')) {
            setSyncStatus('error', 'Sélectionnez un routeur avant l\'envoi.');
            return;
        }
        var ports = getBridgePortsValue();
        if (!ports.length) {
            setSyncStatus('error', 'Sélectionnez au moins un port pour le bridge PPPoE.');
            return;
        }
        var conflictState = getCurrentPortConflictState(lastSnapshot);
        if (conflictState.hasConflict) {
            renderPortConflict(lastSnapshot);
            setSyncStatus(
                'error',
                'Conflit de ports détecté : retirez les ports Hotspot/Management du bridge PPPoE avant l\'envoi.'
            );
            return;
        }
        syncPortsInputFromPicker(ports);
        abortSyncIfRunning();
        var bridgeName = val('pppoe_setup_bridge_name') || 'bridge-pppoe';
        var serverIface = $('pppoe_setup_server_interface');
        if (serverIface && (!String(serverIface.value || '').trim() || String(serverIface.value).toLowerCase() === 'bridge-lan')) {
            serverIface.value = bridgeName;
        }

        deployInFlight = true;
        var label = sendBtn.querySelector('.ps-send-label');
        sendBtn.disabled = true;
        if (label) {
            label.textContent = 'Déploiement en cours…';
        }

        var started = Date.now();
        psShowDeployProgress('Enregistrement de la configuration et connexion au routeur…');
        setSyncStatus('loading', 'Déploiement PPPoE démarré…');

        function finishSuccess(data) {
            try {
                sessionStorage.removeItem(PPPOE_DEPLOY_JOB_STORAGE_KEY);
            } catch (storageErr) {}
            psUpdateDeployProgress('Déploiement terminé avec succès.', 100);
            setSyncStatus('ok', (data && data.message) || 'Serveur PPPoE déployé.');
            lastSnapshot = null;
            lastSnapshotAt = 0;
            fetchSnapshot(true).then(function (snap) {
                if (snap) {
                    renderPppoeServerStatus(snap);
                }
            });
        }

        postPppoeForm(setupForm, { ajax_deploy: '1' }, DEPLOY_START_TIMEOUT_MS)
            .then(function (data) {
                if (!data || !data.ok) {
                    throw new Error((data && data.message) || 'Déploiement PPPoE échoué.');
                }
                if (data.async && data.job_id) {
                    try {
                        sessionStorage.setItem(PPPOE_DEPLOY_JOB_STORAGE_KEY, data.job_id);
                    } catch (storageErr) {}
                    psUpdateDeployProgress(
                        (data.message || 'Tâche PPPoE démarrée.') + ' Suivi en cours…',
                        10
                    );
                    setSyncStatus('loading', data.message || 'Déploiement PPPoE démarré…');
                    return pollPppoeDeployJob(setupForm, data.job_id, started).then(finishSuccess);
                }
                finishSuccess(data);
            })
            .catch(function (err) {
                var recoveredJobId = null;
                try {
                    recoveredJobId = sessionStorage.getItem(PPPOE_DEPLOY_JOB_STORAGE_KEY);
                } catch (storageErr) {}
                if (err && err.name === 'AbortError' && recoveredJobId) {
                    psUpdateDeployProgress(
                        'Réponse serveur lente — suivi de la tâche PPPoE en arrière-plan…',
                        12
                    );
                    setSyncStatus('loading', 'Déploiement PPPoE en cours (worker)…');
                    return pollPppoeDeployJob(setupForm, recoveredJobId, started).then(finishSuccess);
                }
                if (err && err.name === 'AbortError') {
                    psUpdateDeployProgress('Délai dépassé — vérification sur le routeur…', null);
                    setSyncStatus('loading', 'Délai dépassé — vérification du serveur PPPoE sur le routeur…');
                    return verifyPppoeServerOnRouter().then(function (check) {
                        if (check.ok) {
                            psUpdateDeployProgress('Serveur PPPoE confirmé sur le routeur.', 100);
                            setSyncStatus('ok', 'Serveur PPPoE confirmé sur le routeur (réponse lente via VPN). Resynchronisation…');
                            lastSnapshot = null;
                            lastSnapshotAt = 0;
                            return fetchSnapshot(true);
                        }
                        setSyncStatus(
                            'error',
                            'Déploiement interrompu (délai de démarrage). Rechargez la page (Ctrl+F5) et réessayez. '
                            + 'Si le problème persiste : VPN WireGuard et system/cache/pppoe_deploy_worker.log.'
                        );
                    });
                }
                try {
                    sessionStorage.removeItem(PPPOE_DEPLOY_JOB_STORAGE_KEY);
                } catch (storageErr2) {}
                setSyncStatus('error', err.message || 'Erreur réseau pendant le déploiement.');
            })
            .finally(function () {
                psHideDeployProgress();
                deployInFlight = false;
                resetSendButton(sendBtn);
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        ensurePortPickerBinding();
        document.querySelectorAll('.ps-live-check').forEach(function (el) {
            el.addEventListener('change', function () {
                el.setAttribute('data-user-touched', '1');
                updateSummary();
            });
        });

        document.querySelectorAll('.ps-live, #pppoe_setup_router').forEach(function (el) {
            el.addEventListener('input', function () {
                if (el.id === 'pppoe_setup_bridge_name') {
                    renderRouterPortPicker(lastSnapshot);
                    renderPortHints(lastSnapshot);
                    renderPppoeServerStatus(lastSnapshot);
                }
                updateSummary();
            });
            el.addEventListener('change', function () {
                if (el.id === 'pppoe_setup_bridge_name') {
                    renderRouterPortPicker(lastSnapshot);
                    renderPortHints(lastSnapshot);
                    renderPppoeServerStatus(lastSnapshot);
                }
                updateSummary();
            });
        });

        var routerEl = $('pppoe_setup_router');
        if (routerEl) {
            routerEl.addEventListener('change', function () {
                lastSnapshot = null;
                lastSnapshotAt = 0;
                lastSnapshotRouter = '';
                autoSyncRouter(false);
            });
        }

        var syncBtn = $('ps-sync-btn');
        if (syncBtn) {
            syncBtn.addEventListener('click', function () {
                lastSnapshotAt = 0;
                fetchSnapshot(true, true);
            });
        }

        var sendBtn = $('ps-send-mikrotik');
        var setupForm = $('pppoe-setup-form');
        if (setupForm && sendBtn) {
            function handleSendClick(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                deployToMikrotik(setupForm, sendBtn);
            }
            sendBtn.addEventListener('click', handleSendClick);
            setupForm.addEventListener('submit', function (e) {
                var submitter = e.submitter;
                if (!submitter || submitter.name !== 'send_mikrotik') {
                    return;
                }
                e.preventDefault();
                deployToMikrotik(setupForm, sendBtn);
            });
        }

        updateSummary();
        if (val('pppoe_setup_router')) {
            renderRouterPortPicker({ physical_port_count: 5 });
            renderPortHints({});
        }
        autoSyncRouter(true);
    });
})();
