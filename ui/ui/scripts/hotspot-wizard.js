(function () {
    'use strict';

    var TOTAL_STEPS = 3;
    var currentStep = 1;
    var routerPools = [];
    var routerProfiles = [];
    var lastSnapshot = null;
    var lastSnapshotAt = 0;
    var lastSnapshotRouter = '';
    var lastPppoeSuggested = null;
    var lastPppoeAt = 0;
    var lastPppoeRouter = '';
    var portPickerClickBound = false;
    var fetchInFlight = null;
    var fetchInFlightRouter = '';
    var fetchAbortController = null;
    var pppoeFetchInFlight = null;
    var pppoeFetchInFlightRouter = '';
    var ROUTER_STORAGE_KEY = 'hs_wizard_router';
    var persistRouterTimer = null;
    var SYNC_CACHE_TTL_MS = 120000;

    function getAllowedRouters() {
        if (window.HS_ALLOWED_ROUTERS && Array.isArray(window.HS_ALLOWED_ROUTERS)) {
            return window.HS_ALLOWED_ROUTERS.filter(function (name) {
                return String(name || '').trim() !== '';
            });
        }
        var select = $('hotspot_login_router');
        if (!select) {
            return [];
        }
        return Array.prototype.slice.call(select.options || [])
            .map(function (opt) { return String(opt.value || '').trim(); })
            .filter(function (name) { return name !== ''; });
    }

    function isAllowedRouter(routerName) {
        routerName = String(routerName || '').trim();
        if (!routerName) {
            return false;
        }
        return getAllowedRouters().indexOf(routerName) !== -1;
    }

    function clearPersistedRouterSelection() {
        syncRouterPersistValue('');
        var select = $('hotspot_login_router');
        if (select) {
            select.value = '';
        }
        try {
            sessionStorage.removeItem(ROUTER_STORAGE_KEY);
        } catch (e) {}
    }

    function persistRouterToServer(routerName, immediate) {
        var url = window.HS_PERSIST_ROUTER_URL || '';
        routerName = String(routerName || '').trim();
        if (!url || !routerName || !isAllowedRouter(routerName)) {
            return;
        }
        clearTimeout(persistRouterTimer);
        var run = function () {
            fetch(url + '&router=' + encodeURIComponent(routerName), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            }).catch(function () {});
        };
        if (immediate) {
            run();
        } else {
            persistRouterTimer = setTimeout(run, 350);
        }
    }

    function syncRouterPersistValue(routerName) {
        routerName = String(routerName || '').trim();
        var persist = $('hotspot_login_router_persist');
        if (persist) {
            persist.value = routerName;
        }
        try {
            if (routerName) {
                sessionStorage.setItem(ROUTER_STORAGE_KEY, routerName);
            } else {
                sessionStorage.removeItem(ROUTER_STORAGE_KEY);
            }
        } catch (e) {}
        window.HS_INITIAL_ROUTER = routerName;
    }

    function ensureRouterOption(select, routerName) {
        if (!select || !routerName || !isAllowedRouter(routerName)) return;
        var exists = Array.prototype.slice.call(select.options || []).some(function (opt) {
            return opt.value === routerName;
        });
        if (!exists) {
            var opt = document.createElement('option');
            opt.value = routerName;
            opt.textContent = routerName;
            select.appendChild(opt);
        }
    }

    function setSelectedRouter(routerName, options) {
        options = options || {};
        routerName = String(routerName || '').trim();
        if (routerName && !isAllowedRouter(routerName)) {
            clearPersistedRouterSelection();
            return;
        }
        var select = $('hotspot_login_router');
        if (select) {
            if (routerName) {
                ensureRouterOption(select, routerName);
                select.value = routerName;
            } else if (!options.keepIfEmpty) {
                select.value = '';
            }
        }
        syncRouterPersistValue(routerName || (select ? select.value : ''));
        if (routerName) {
            persistRouterToServer(routerName);
        }
        if (!options.silent && typeof window.hsUpdatePreviewRouter === 'function') {
            window.hsUpdatePreviewRouter(routerName || val('hotspot_login_router'));
        }
    }

    function getPersistedRouterName() {
        var select = $('hotspot_login_router');
        if (select && select.value && isAllowedRouter(select.value)) {
            return select.value;
        }
        var persist = $('hotspot_login_router_persist');
        if (persist && persist.value && isAllowedRouter(persist.value)) {
            return persist.value;
        }
        if (window.HS_INITIAL_ROUTER && isAllowedRouter(window.HS_INITIAL_ROUTER)) {
            return String(window.HS_INITIAL_ROUTER).trim();
        }
        try {
            var stored = sessionStorage.getItem(ROUTER_STORAGE_KEY) || '';
            if (stored && isAllowedRouter(stored)) {
                return stored;
            }
            if (stored) {
                sessionStorage.removeItem(ROUTER_STORAGE_KEY);
            }
        } catch (e) {}
        return '';
    }

    function restoreRouterSelection() {
        var routerName = getPersistedRouterName();
        if (!routerName) {
            clearPersistedRouterSelection();
            return '';
        }
        setSelectedRouter(routerName, { silent: true, keepIfEmpty: true });
        return routerName;
    }

    var schemes = {
        green: { bg: 'linear-gradient(160deg,#052e16,#16a34a)', accent: '#22c55e', card: 'rgba(6,78,59,.88)' },
        blue: { bg: 'linear-gradient(160deg,#0c1a3d,#2563eb)', accent: '#3b82f6', card: 'rgba(15,23,42,.88)' },
        red: { bg: 'linear-gradient(160deg,#450a0a,#dc2626)', accent: '#ef4444', card: 'rgba(69,10,10,.88)' },
        dark: { bg: 'linear-gradient(160deg,#020617,#1e293b)', accent: '#94a3b8', card: 'rgba(15,23,42,.92)' },
        light: { bg: 'linear-gradient(160deg,#f8fafc,#e2e8f0)', accent: '#0f172a', card: 'rgba(255,255,255,.95)', text: '#0f172a', muted: '#64748b' }
    };

    var shapeRadius = { square: '6px', rounded: '14px', pill: '999px' };

    function $(id) { return document.getElementById(id); }

    function val(name) {
        var el = document.querySelector('[name="' + name + '"]');
        if (!el) return '';
        if (el.type === 'checkbox') return el.checked;
        if (el.type === 'radio') {
            var checked = document.querySelector('[name="' + name + '"]:checked');
            return checked ? checked.value : '';
        }
        return el.value;
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

    function labelForSelect(name) {
        var el = document.querySelector('[name="' + name + '"]');
        if (!el || el.tagName !== 'SELECT') return val(name);
        var opt = el.options[el.selectedIndex];
        return opt ? opt.text : val(name);
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setSyncStatus(kind, message) {
        var box = $('hs-sync-status');
        if (!box) return;
        box.className = 'hs-sync-status' + (kind ? ' ' + kind : '');
        box.textContent = message || '';
        box.style.display = message ? 'block' : 'none';
    }

    var CUSTOM_PICK = '__custom__';

    function setupNamePicker(pickerId, inputId, items, suggestedValue, preserveUserEdits) {
        var picker = $(pickerId);
        var input = $(inputId);
        if (!picker || !input) return;

        var saved = String(input.value || '').trim();
        var suggested = String(suggestedValue || '').trim();
        var value = saved;
        if (!preserveUserEdits && suggested) {
            value = suggested;
        } else if (!value && suggested) {
            value = suggested;
        }

        picker.innerHTML = '';
        if (!items.length) {
            var emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = '— Aucun sur le routeur —';
            picker.appendChild(emptyOpt);
        }

        var inList = false;
        items.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.value;
            opt.textContent = item.label || item.value;
            if (item.value === value) {
                inList = true;
            }
            picker.appendChild(opt);
        });

        var customOpt = document.createElement('option');
        customOpt.value = CUSTOM_PICK;
        customOpt.textContent = '— Saisie manuelle —';
        picker.appendChild(customOpt);

        if (value && inList) {
            picker.value = value;
            input.value = value;
            picker.dataset.lastListValue = value;
        } else if (value) {
            picker.value = CUSTOM_PICK;
            input.value = value;
        } else if (items.length) {
            picker.value = items[0].value;
            input.value = items[0].value;
            picker.dataset.lastListValue = items[0].value;
        } else {
            picker.value = CUSTOM_PICK;
            input.value = '';
        }
    }

    function bindNamePicker(pickerId, inputId, onValueChange) {
        var picker = $(pickerId);
        var input = $(inputId);
        if (!picker || !input) return;

        picker.addEventListener('change', function () {
            if (picker.value === CUSTOM_PICK) {
                input.focus();
                if (onValueChange) onValueChange(input.value.trim());
                return;
            }
            if (picker.value) {
                input.value = picker.value;
                picker.dataset.lastListValue = picker.value;
                if (onValueChange) onValueChange(picker.value);
            }
        });

        function syncPickerFromInput() {
            var v = input.value.trim();
            var matched = false;
            for (var i = 0; i < picker.options.length; i++) {
                var opt = picker.options[i];
                if (opt.value === v && v && opt.value !== CUSTOM_PICK) {
                    picker.value = v;
                    picker.dataset.lastListValue = v;
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                picker.value = v ? CUSTOM_PICK : (picker.options.length > 1 ? picker.options[0].value : CUSTOM_PICK);
            }
            if (onValueChange) onValueChange(v);
        }

        input.addEventListener('input', syncPickerFromInput);
        input.addEventListener('change', syncPickerFromInput);
    }

    function mergePoolsFromHotspots(pools, hotspots) {
        var merged = (pools || []).slice();
        (hotspots || []).forEach(function (hs) {
            var name = String(hs.address_pool || '').trim();
            if (!name) return;
            if (!merged.some(function (p) { return p.name === name; })) {
                merged.push({ name: name, ranges: '' });
            }
        });
        return merged;
    }

    function fillSelect(selectId, items, selectedValue, placeholder) {
        var select = $(selectId);
        if (!select) return;
        var current = selectedValue != null ? String(selectedValue) : select.value;
        select.innerHTML = '';
        if (placeholder) {
            var ph = document.createElement('option');
            ph.value = '';
            ph.textContent = placeholder;
            select.appendChild(ph);
        }
        items.forEach(function (item) {
            var opt = document.createElement('option');
            if (typeof item === 'string') {
                opt.value = item;
                opt.textContent = item;
            } else {
                opt.value = item.value;
                opt.textContent = item.label || item.value;
            }
            if (current && opt.value === current) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
        if (current) {
            select.value = current;
        }
    }

    function applyPoolRangeFromSelection() {
        var poolName = val('hotspot_pool_name');
        var rangeInput = $('hotspot_address_pool');
        var hiddenRange = $('hotspot_pool_range');
        if (!poolName || !rangeInput) return;
        routerPools.forEach(function (pool) {
            if (pool.name === poolName && pool.ranges) {
                rangeInput.value = pool.ranges;
                if (hiddenRange) hiddenRange.value = pool.ranges;
            }
        });
    }

    function applyProfileMeta(profileName) {
        routerProfiles.forEach(function (prof) {
            if (prof.name !== profileName) return;
            if (prof.smtp_server) setVal('hotspot_smtp_server', prof.smtp_server);
            if (prof.dns_server) setVal('hotspot_dns_server', prof.dns_server);
            if (prof.dns_name && !val('hotspot_dns_name')) setVal('hotspot_dns_name', prof.dns_name);
            if (prof.login_by) setLoginMethodsFromRouter(prof.login_by);
            if (prof.http_cookie_lifetime && !val('hotspot_cookie_lifetime')) {
                setVal('hotspot_cookie_lifetime', prof.http_cookie_lifetime);
            }
            if (prof.idle_timeout) setVal('hotspot_idle_timeout', prof.idle_timeout);
        });
    }

    function setLoginMethodsFromRouter(loginBy) {
        var methods = String(loginBy || '').toLowerCase().split(',').map(function (m) {
            m = m.trim();
            if (m === 'cookie') return 'mac-cookie';
            return m;
        }).filter(function (m) {
            return m && m !== 'chap' && m !== 'http-chap';
        });
        if (methods.indexOf('http-pap') === -1) {
            methods.unshift('http-pap');
        }
        if (methods.indexOf('mac-cookie') === -1) {
            methods.push('mac-cookie');
        }
        document.querySelectorAll('input[name="hotspot_login_methods[]"]').forEach(function (cb) {
            cb.checked = methods.indexOf(cb.value) !== -1;
        });
    }

    function applySuggested(suggested, preserveUserEdits) {
        if (!suggested) return;
        var fields = [
            'hotspot_name',
            'hotspot_interface',
            'lan_bridge_name',
            'lan_trunk_bridge_ports',
            'hotspot_vlan_id',
            'hotspot_vlan_interface',
            'hotspot_local_address',
            'hotspot_address_pool',
            'hotspot_smtp_server',
            'hotspot_dns_server',
            'hotspot_dns_name',
            'hotspot_cookie_lifetime',
            'hotspot_idle_timeout',
            'hotspot_address_per_mac'
        ];
        fields.forEach(function (field) {
            var el = $(field);
            if (!el) return;
            var isPortField = field === 'lan_trunk_bridge_ports';
            if (preserveUserEdits && !isPortField && String(el.value || '').trim() !== '') return;
            if (preserveUserEdits && isPortField && String(el.value || '').trim() !== '' && el.dataset.userTouched === '1') return;
            if (suggested[field] != null && suggested[field] !== '') {
                el.value = suggested[field];
            }
        });
        if (!preserveUserEdits || !$('hotspot_masquerade') || !$('hotspot_masquerade').dataset.userTouched) {
            setVal('hotspot_masquerade', suggested.hotspot_masquerade || '1');
        }
        if (!preserveUserEdits || !$('lan_trunk_enabled') || !$('lan_trunk_enabled').dataset.userTouched) {
            setVal('lan_trunk_enabled', suggested.lan_trunk_enabled || '0');
        }
        toggleTrunkFields();
        syncHotspotVlanInterface();
        if (suggested.hotspot_login_methods) {
            setLoginMethodsFromRouter(suggested.hotspot_login_methods);
        }
        var hiddenRange = $('hotspot_pool_range');
        if (hiddenRange && suggested.hotspot_pool_range) {
            hiddenRange.value = suggested.hotspot_pool_range;
        }
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

    function getBridgePortsValue() {
        var el = $('lan_trunk_bridge_ports');
        return el ? parsePortsCsv(el.value) : [];
    }

    function syncPortsInputFromPicker(ports) {
        var el = $('lan_trunk_bridge_ports');
        if (!el) return;
        el.value = (ports || []).join(',');
    }

    function sortPortsNatural(ports) {
        return ports.slice().sort(function (a, b) {
            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        });
    }

    function isWlanPort(port) {
        return /^wlan/i.test(String(port || ''));
    }

    function getTrunkMemberPorts(data) {
        data = data || {};
        if (Array.isArray(data.trunk_member_ports) && data.trunk_member_ports.length) {
            return data.trunk_member_ports.slice();
        }
        var physical = Array.isArray(data.physical_ports) ? data.physical_ports.slice() : [];
        var wireless = Array.isArray(data.wireless_ports) ? data.wireless_ports.slice() : [];
        return physical.concat(wireless);
    }

    function buildPortButtonHtml(portName, isWan, isSelected) {
        var isWlan = isWlanPort(portName);
        var cls = 'hs-port-btn' + (isWan ? ' wan' : '') + (isWlan ? ' wlan' : '') + (isSelected ? ' selected' : '');
        var role = isWan ? 'WAN' : (isWlan ? 'Wi‑Fi' : (isSelected ? 'Bridge' : 'LAN'));
        var title = isWan
            ? 'Port WAN — non ajouté au bridge'
            : ('Cliquer pour ' + (isSelected ? 'retirer du' : 'ajouter au') + ' bridge trunk');
        var disabled = isWan ? ' disabled' : '';
        return '<button type="button" class="' + cls + '" data-port="' + escapeHtml(portName) + '" title="' + escapeHtml(title) + '"' + disabled + '>'
            + '<span class="hs-rj45-stack">'
            + '<span class="hs-rj45-led" aria-hidden="true"></span>'
            + '<span class="hs-rj45-housing" aria-hidden="true">'
            + '<span class="hs-rj45-jack">'
            + '<span class="hs-rj45-clip"></span>'
            + '<span class="hs-rj45-pins"></span>'
            + '</span>'
            + '</span>'
            + '</span>'
            + '<span class="hs-port-label">' + escapeHtml(portName) + '</span>'
            + '<span class="hs-port-role">' + role + '</span>'
            + '</button>';
    }

    function bindPortPickerDelegation() {
        if (portPickerClickBound) return;
        var root = $('hs-router-port-picker');
        if (!root) return;
        root.addEventListener('click', function (event) {
            var btn = event.target.closest('.hs-port-btn');
            if (!btn || btn.disabled || btn.classList.contains('wan')) return;
            event.preventDefault();
            var port = btn.getAttribute('data-port');
            if (!port) return;
            var portsField = $('lan_trunk_bridge_ports');
            if (portsField) {
                portsField.dataset.userTouched = '1';
            }
            var current = getBridgePortsValue();
            if (isPortInList(port, current)) {
                current = current.filter(function (p) { return !portEquals(p, port); });
            } else {
                current.push(port);
            }
            current = sortPortsNatural(current);
            syncPortsInputFromPicker(current);
            renderRouterPortPicker(lastSnapshot);
            renderRouterPortHints(lastSnapshot || { lan_ports: current, lan_port_count: current.length });
        });
        portPickerClickBound = true;
    }

    function renderRouterPortPicker(data) {
        var root = $('hs-router-port-picker');
        var trunk = $('lan_trunk_enabled');
        if (!root || !trunk || !trunk.checked) {
            return;
        }

        bindPortPickerDelegation();
        data = data || lastSnapshot || {};
        var memberPorts = getTrunkMemberPorts(data);
        var wan = String(data.wan_interface || '').trim();

        if (!memberPorts.length) {
            root.innerHTML = '<div class="hs-port-picker-empty" id="hs-port-picker-empty">Synchronisez le routeur à l\'étape 1 pour afficher les ports MikroTik.</div>';
            return;
        }

        var selected = getBridgePortsValue();
        var validLan = memberPorts.filter(function (p) {
            return !wan || !portEquals(p, wan);
        });
        selected = selected.filter(function (p) {
            return validLan.some(function (lp) { return portEquals(lp, p); });
        });
        if (!selected.length && Array.isArray(data.lan_ports) && data.lan_ports.length) {
            selected = sortPortsNatural(data.lan_ports.filter(function (p) {
                return validLan.some(function (lp) { return portEquals(lp, p); });
            }));
        }
        syncPortsInputFromPicker(selected);

        var routerLabel = labelForSelect('hotspot_login_router') || getPersistedRouterName() || 'Routeur';
        var wanPorts = [];
        var lanPorts = [];
        memberPorts.forEach(function (portName) {
            if (wan && portEquals(portName, wan)) {
                wanPorts.push(portName);
            } else {
                lanPorts.push(portName);
            }
        });
        if (!wanPorts.length && memberPorts.length) {
            var first = memberPorts[0];
            if (/^ether1$/i.test(first)) {
                wanPorts.push(first);
                lanPorts = memberPorts.slice(1);
            } else {
                lanPorts = memberPorts.slice();
            }
        }

        var html = '<div class="hs-rb-chassis">';
        html += '<div class="hs-rb-vents" aria-hidden="true"><span></span><span></span><span></span><span></span></div>';
        html += '<div class="hs-rb-top">';
        html += '<div class="hs-rb-brand-wrap"><div class="hs-rb-logo" aria-hidden="true">M</div>';
        html += '<div class="hs-rb-brand-text"><span class="hs-rb-brand">MikroTik</span>';
        html += '<span class="hs-rb-model">' + escapeHtml(routerLabel) + ' · ' + memberPorts.length + ' port(s)</span></div></div>';
        html += '<div class="hs-rb-status"><span class="hs-rb-status-led" aria-hidden="true"></span><span class="hs-rb-status-label">En ligne</span></div>';
        html += '</div>';
        html += '<div class="hs-rb-faceplate"><div class="hs-rb-faceplate-title">Ethernet / Wi‑Fi · cliquez les ports LAN pour le bridge trunk</div>';
        html += '<div class="hs-rb-port-row">';

        if (wanPorts.length) {
            html += '<div class="hs-rb-port-group wan-group">';
            wanPorts.forEach(function (portName) {
                html += buildPortButtonHtml(portName, true, false);
            });
            html += '</div>';
        }

        if (lanPorts.length) {
            html += '<div class="hs-rb-port-group lan-group">';
            lanPorts.forEach(function (portName) {
                html += buildPortButtonHtml(portName, false, isPortInList(portName, selected));
            });
            html += '</div>';
        }

        html += '</div></div></div>';
        root.innerHTML = html;
    }

    function ensureRouterPortsForTrunk() {
        var trunk = $('lan_trunk_enabled');
        if (!trunk || !trunk.checked) return Promise.resolve();
        if (lastSnapshot && getTrunkMemberPorts(lastSnapshot).length) {
            renderRouterPortPicker(lastSnapshot);
            return Promise.resolve();
        }
        var routerName = getPersistedRouterName();
        if (!routerName) return Promise.resolve();
        if (fetchInFlight) {
            return fetchInFlight.then(function () {
                ensureRouterPortsForTrunk();
            });
        }
        return fetchRouterSetup(routerName, true);
    }

    function renderRouterPortHints(data) {
        var box = $('hs-trunk-port-hints');
        if (!box) return;
        var count = parseInt(data && data.lan_port_count, 10) || 0;
        var ports = (data && data.lan_ports) || [];
        var selected = getBridgePortsValue();
        if (selected.length) {
            ports = selected;
            count = selected.length;
        }
        var physical = parseInt(data && data.physical_port_count, 10) || 0;
        var wireless = parseInt(data && data.wireless_port_count, 10) || 0;
        var wan = (data && data.wan_interface) || '';
        if (!count) {
            box.textContent = 'Aucun port détecté — vérifiez la connexion API au routeur.';
            return;
        }
        var typeHint = '';
        if (physical && wireless) {
            typeHint = ' (' + physical + ' Ethernet, ' + wireless + ' Wi‑Fi';
        } else if (physical) {
            typeHint = ' (' + physical + ' Ethernet';
        } else if (wireless) {
            typeHint = ' (' + wireless + ' Wi‑Fi';
        }
        if (typeHint && wan) {
            typeHint += ', WAN « ' + wan + ' » exclu';
        }
        if (typeHint) {
            typeHint += ')';
        }
        box.textContent = count + ' port(s) sélectionné(s) pour le bridge trunk' + typeHint
            + (ports.length ? ' : ' + ports.join(', ') : '');
    }

    function isSyncCacheFresh(at) {
        return !!at && (Date.now() - at) < SYNC_CACHE_TTL_MS;
    }

    function cachePppoeFromPayload(data, routerName) {
        routerName = String(routerName || '').trim();
        var suggested = null;
        if (data && data.pppoe && data.pppoe.suggested) {
            suggested = data.pppoe.suggested;
        } else if (data && data.suggested && (data.suggested.pppoe_setup_pool_name || data.suggested.pppoe_setup_service_name)) {
            suggested = data.suggested;
        }
        if (!suggested || !routerName) return;
        lastPppoeSuggested = suggested;
        lastPppoeAt = Date.now();
        lastPppoeRouter = routerName;
    }

    function applySnapshot(data, preserveUserEdits) {
        lastSnapshot = data;
        lastSnapshotAt = Date.now();
        lastSnapshotRouter = getPersistedRouterName();
        routerPools = mergePoolsFromHotspots(data.pools || [], data.hotspots || []);
        routerProfiles = data.profiles || [];
        var suggested = data.suggested || {};
        cachePppoeFromPayload(data, lastSnapshotRouter);

        var ifaceItems = (data.interfaces || []).map(function (iface) {
            return { value: iface.name, label: iface.label || iface.name };
        });
        fillSelect('hotspot_interface', ifaceItems, preserveUserEdits ? val('hotspot_interface') : suggested.hotspot_interface, ifaceItems.length ? null : '— Aucune interface —');

        var poolItems = routerPools.map(function (pool) {
            var label = pool.name;
            if (pool.ranges) {
                label += ' (' + pool.ranges + ')';
            }
            return { value: pool.name, label: label };
        });
        setupNamePicker(
            'hotspot_pool_name_picker',
            'hotspot_pool_name',
            poolItems,
            suggested.hotspot_pool_name,
            preserveUserEdits
        );

        var profileItems = routerProfiles.map(function (prof) {
            return { value: prof.name, label: prof.name };
        });
        if (!profileItems.some(function (p) { return p.value === 'default'; })) {
            profileItems.unshift({ value: 'default', label: 'default' });
        }
        setupNamePicker(
            'hotspot_profile_picker',
            'hotspot_profile',
            profileItems,
            suggested.hotspot_profile || 'hotspot',
            preserveUserEdits
        );

        applySuggested(suggested, preserveUserEdits);
        renderRouterPortPicker(data);
        renderRouterPortHints(data);
        applyPoolRangeFromSelection();
        applyProfileMeta(val('hotspot_profile'));
    }

    function fetchRouterSetup(routerName, preserveUserEdits) {
        var fetchUrl = window.HS_FETCH_URL || '';
        routerName = String(routerName || '').trim();
        if (!fetchUrl || !routerName) {
            setSyncStatus('', '');
            return Promise.resolve(null);
        }

        if (fetchInFlight && fetchInFlightRouter === routerName) {
            return fetchInFlight;
        }

        if (lastSnapshot && lastSnapshotRouter === routerName && isSyncCacheFresh(lastSnapshotAt)) {
            ensureRouterPortsForTrunk();
            return Promise.resolve(lastSnapshot);
        }

        if (fetchAbortController) {
            try {
                fetchAbortController.abort();
            } catch (e) {}
        }
        fetchAbortController = typeof AbortController !== 'undefined' ? new AbortController() : null;

        setSyncStatus('loading', 'Synchronisation avec « ' + routerName + ' »…');
        window.HS_SYNC_IN_PROGRESS = true;
        fetchInFlightRouter = routerName;
        var qs = '&router=' + encodeURIComponent(routerName)
            + '&hotspot_name=' + encodeURIComponent(val('hotspot_name') || '');
        if (isTrunkMode()) {
            qs += '&include_pppoe=1';
        }
        fetchInFlight = fetch(
            fetchUrl + qs,
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: fetchAbortController ? fetchAbortController.signal : undefined
            }
        )
            .then(function (r) {
                return r.text().then(function (body) {
                    if (!r.ok) {
                        var httpDetail = String(body || '')
                            .replace(/<[^>]*>/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim()
                            .slice(0, 300);
                        throw new Error('HTTP ' + r.status + (httpDetail ? ' : ' + httpDetail : ''));
                    }
                    try {
                        return JSON.parse(body);
                    } catch (e) {
                        var detail = String(body || '')
                            .replace(/<[^>]*>/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim()
                            .slice(0, 300);
                        throw new Error('Réponse invalide du serveur pendant la synchronisation.'
                            + (detail ? ' Détail : ' + detail : ''));
                    }
                });
            })
            .then(function (data) {
                if (!data || !data.ok) {
                    var errMsg = (data && data.message) ? data.message : 'Synchronisation impossible.';
                    setSyncStatus('error', errMsg);
                    restoreRouterSelection();
                    return data;
                }
                applySnapshot(data, !!preserveUserEdits);
                var ifaceCount = (data.interfaces || []).length;
                var poolCount = routerPools.length;
                var profileCount = (data.profiles || []).length;
                var pppoeOk = data.pppoe && data.pppoe.ok;
                setSyncStatus(
                    'ok',
                    'Routeur synchronisé : ' + ifaceCount + ' interface(s), ' + poolCount + ' pool(s), '
                        + profileCount + ' profil(s)'
                        + (pppoeOk ? ', PPPoE inclus' : '') + '.'
                );
                if (pppoeOk && currentStep === 3) {
                    applyPppoeSuggested(data.pppoe.suggested || {}, true);
                    setPppoeSyncStatus('ok', 'Configuration PPPoE déjà synchronisée avec le routeur.');
                }
                return data;
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') {
                    return null;
                }
                var msg = err && err.message ? err.message : 'connexion impossible';
                if (/failed to fetch|network|load/i.test(msg)) {
                    msg = 'Connexion interrompue (serveur occupé ou VPN lent). Le routeur « '
                        + routerName + ' » reste sélectionné — réessayez.';
                }
                setSyncStatus('error', msg);
                restoreRouterSelection();
                return null;
            })
            .finally(function () {
                if (fetchInFlightRouter === routerName) {
                    fetchInFlight = null;
                    fetchInFlightRouter = '';
                    window.HS_SYNC_IN_PROGRESS = false;
                    if (typeof window.hsRefreshRouterAlerts === 'function') {
                        window.hsRefreshRouterAlerts();
                    }
                }
                fetchAbortController = null;
            });

        return fetchInFlight;
    }

    function syncHiddenPoolRange() {
        var range = val('hotspot_address_pool');
        var hidden = $('hotspot_pool_range');
        if (hidden) hidden.value = range;
    }

    function updatePreview() {
        var title = val('hotspot_page_title') || 'yoyo';
        var tagline = val('hotspot_page_tagline') || 'Description / Tagline';
        var scheme = schemes.green;
        var screen = $('hs-preview-screen');
        var bannerEl = $('hs-preview-banner');

        if (screen) {
            screen.style.background = scheme.bg;
            screen.style.color = scheme.text || '#fff';
        }
        var titleEl = $('hs-preview-title');
        var tagEl = $('hs-preview-tagline');
        if (titleEl) titleEl.textContent = title;
        if (tagEl) {
            tagEl.textContent = tagline;
            tagEl.style.color = scheme.muted || 'rgba(255,255,255,.72)';
        }
        document.querySelectorAll('.hs-preview-pkg').forEach(function (card) {
            card.style.borderRadius = '14px';
            card.style.background = scheme.card;
        });
        var btn = $('hs-preview-btn');
        if (btn) btn.style.background = scheme.accent;
        if (bannerEl) {
            bannerEl.style.display = 'none';
        }
    }

    function isTrunkMode() {
        return false;
    }

    function getTotalSteps() {
        return isTrunkMode() ? 4 : 3;
    }

    function getPanelIdForStep(step) {
        if (!isTrunkMode() && step === 3) {
            return 'hs-step-4';
        }
        return 'hs-step-' + step;
    }

    function syncPppoeHiddenFields() {
        var router = getPersistedRouterName();
        var routerField = $('pppoe_setup_router');
        if (routerField) {
            routerField.value = router;
        }
        var bridge = $('lan_bridge_name');
        var bridgeHidden = $('pppoe_setup_bridge_name');
        if (bridge && bridgeHidden) {
            bridgeHidden.value = String(bridge.value || '').trim();
        }
    }

    function syncPppoeVlanInterface() {
        if (!isTrunkMode()) return;
        var vlanIdEl = $('pppoe_setup_vlan_id');
        var vlanIfaceEl = $('pppoe_setup_vlan_interface');
        var serverIfaceEl = $('pppoe_setup_server_interface');
        if (!vlanIdEl) return;
        var vlanId = parseInt(String(vlanIdEl.value || ''), 10);
        var flowVlan = $('hs-ppp-flow-vlan');
        if (flowVlan) flowVlan.textContent = vlanId && vlanId > 0 ? String(vlanId) : '?';
        if (!vlanId || vlanId < 1) return;
        var autoName = 'vlan' + vlanId + '-pppoe';
        if (vlanIfaceEl && String(vlanIfaceEl.value || '').trim() === '') {
            vlanIfaceEl.value = autoName;
        }
        if (serverIfaceEl && String(serverIfaceEl.value || '').trim() === '') {
            serverIfaceEl.value = String(vlanIfaceEl && vlanIfaceEl.value ? vlanIfaceEl.value : autoName).trim() || autoName;
        }
    }

    function setPppoeSyncStatus(kind, message) {
        var box = $('hs-pppoe-sync-status');
        if (!box) return;
        box.className = 'hs-sync-status' + (kind ? ' ' + kind : '');
        box.textContent = message || '';
        box.style.display = message ? 'block' : 'none';
    }

    function applyPppoeSuggested(suggested, preserveEdits) {
        if (!suggested) return;
        var fields = [
            'pppoe_setup_vlan_id', 'pppoe_setup_vlan_interface', 'pppoe_setup_gateway',
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
        ['pppoe_setup_dns_allow_remote', 'pppoe_setup_one_session', 'pppoe_setup_nat_masquerade', 'pppoe_setup_on_hotspot'].forEach(function (key) {
            var el = $(key);
            if (!el) return;
            if (preserveEdits && el.hasAttribute('data-user-touched')) return;
            if (suggested[key] != null) setVal(key, suggested[key]);
        });
        syncPppoeVlanInterface();
    }

    function shortPppoeSyncMessage(msg) {
        msg = String(msg || '');
        if (/timed out|injoignable|Cannot connect|Impossible de se connecter/i.test(msg)) {
            return 'API MikroTik momentanément injoignable. Les valeurs déjà enregistrées sont conservées — vous pouvez continuer et utiliser « Send PPPoE » plus tard.';
        }
        if (msg.length > 180) {
            return msg.slice(0, 180) + '…';
        }
        return msg || 'Sync PPPoE impossible.';
    }

    function fetchPppoeSetup(routerName, preserveEdits, force) {
        var url = window.HS_PPPOE_FETCH_URL || window.HS_FETCH_URL || '';
        routerName = String(routerName || '').trim();
        if (!url || !routerName) {
            return Promise.resolve(null);
        }

        if (!force && lastPppoeSuggested && lastPppoeRouter === routerName && isSyncCacheFresh(lastPppoeAt)) {
            applyPppoeSuggested(lastPppoeSuggested, !!preserveEdits);
            setPppoeSyncStatus('ok', 'Configuration PPPoE (cache) — déjà lue avec le Hotspot.');
            syncPppoeVlanInterface();
            return Promise.resolve({ ok: true, suggested: lastPppoeSuggested, cached: true });
        }

        if (pppoeFetchInFlight && pppoeFetchInFlightRouter === routerName) {
            return pppoeFetchInFlight;
        }

        // Attendre la fin du sync Hotspot (étape 1/2) pour éviter 2 connexions API simultanées
        var waitHotspot = (fetchInFlight && fetchInFlightRouter === routerName)
            ? fetchInFlight.catch(function () { return null; })
            : Promise.resolve(null);

        setPppoeSyncStatus('loading', 'Synchronisation PPPoE depuis « ' + routerName + ' »…');
        pppoeFetchInFlightRouter = routerName;
        pppoeFetchInFlight = waitHotspot.then(function (hotspotData) {
            if (!force && hotspotData && hotspotData.pppoe && hotspotData.pppoe.ok) {
                cachePppoeFromPayload(hotspotData, routerName);
                applyPppoeSuggested(hotspotData.pppoe.suggested || {}, !!preserveEdits);
                setPppoeSyncStatus('ok', 'Configuration PPPoE synchronisée (même connexion).');
                return { ok: true, suggested: hotspotData.pppoe.suggested || {}, fromHotspot: true };
            }
            if (!force && lastPppoeSuggested && lastPppoeRouter === routerName) {
                applyPppoeSuggested(lastPppoeSuggested, !!preserveEdits);
                setPppoeSyncStatus('ok', 'Configuration PPPoE (cache) — déjà lue avec le Hotspot.');
                return { ok: true, suggested: lastPppoeSuggested, cached: true };
            }
            return fetch(url + '&router=' + encodeURIComponent(routerName), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function (data) {
                if (!data || !data.ok) {
                    setPppoeSyncStatus('error', shortPppoeSyncMessage((data && data.message) || ''));
                    return data;
                }
                cachePppoeFromPayload({ pppoe: { suggested: data.suggested || {} } }, routerName);
                applyPppoeSuggested(data.suggested || {}, !!preserveEdits);
                setPppoeSyncStatus('ok', 'Configuration PPPoE synchronisée depuis le routeur.');
                return data;
            });
        })
            .catch(function (err) {
                setPppoeSyncStatus('error', shortPppoeSyncMessage(err && err.message ? err.message : ''));
                return null;
            })
            .finally(function () {
                if (pppoeFetchInFlightRouter === routerName) {
                    pppoeFetchInFlight = null;
                    pppoeFetchInFlightRouter = '';
                }
            });

        return pppoeFetchInFlight;
    }

    function updateTrunkModeUi() {
        /* dual-bridge only — trunk UI removed */
        var panel = document.getElementById('hs-trunk-panel');
        if (panel) panel.style.display = 'none';
        document.querySelectorAll('.hs-trunk-only').forEach(function (el) { el.style.display = 'none'; });
        var indicators = $('hs-step-indicators');
        var finalActions = $('hs-final-actions');
        if (indicators) {
            indicators.classList.remove('trunk-mode');
        }
        if (finalActions) {
            finalActions.classList.remove('trunk-mode');
        }
        TOTAL_STEPS = getTotalSteps();
        if (currentStep > TOTAL_STEPS) {
            currentStep = TOTAL_STEPS;
        }
    }

    function loginMethodsSummary() {
        var selected = [];
        document.querySelectorAll('input[name="hotspot_login_methods[]"]:checked').forEach(function (cb) {
            if (cb.value === 'http-pap') selected.push('HTTP PAP');
            else if (cb.value === 'mac-cookie') selected.push('MAC COOKIE');
            else if (cb.value !== 'http-chap') selected.push(cb.value);
        });
        if (selected.indexOf('HTTP PAP') === -1) {
            selected.unshift('HTTP PAP');
        }
        return selected.length ? selected.join(', ') : 'HTTP PAP, MAC COOKIE';
    }

    function buildSummary() {
        var rows = [
            ['Titre', val('hotspot_page_title')],
            ['Description', val('hotspot_page_tagline')],
            ['Contact', val('hotspot_contact')],
            ['Numéro contact', val('hotspot_contact_phone')],
            ['Routeur', labelForSelect('hotspot_login_router') || getPersistedRouterName() || '—'],
            ['Affichage cartes', labelForSelect('hotspot_card_display')],
            ['Ports Hotspot', val('hotspot_bridge_ports') || 'wlan1,ether3']
        ];
        rows = rows.concat([
            ['Nom Hotspot', val('hotspot_name')],
            ['Interface', labelForSelect('hotspot_interface') || val('hotspot_interface') || 'bridge-hotspot'],
            ['Adresse locale', val('hotspot_local_address')],
            ['Masquerade', val('hotspot_masquerade') ? 'Oui' : 'Non'],
            ['Address Pool', val('hotspot_address_pool')],
            ['Pool', val('hotspot_pool_name')],
            ['SMTP', val('hotspot_smtp_server')],
            ['DNS Server', val('hotspot_dns_server')],
            ['DNS Name', val('hotspot_dns_name') || '(vide)'],
            ['Profil', val('hotspot_profile')],
            ['Login', loginMethodsSummary()],
            ['HTTP Cookie Lifetime', val('hotspot_cookie_lifetime')],
            ['Idle Timeout', val('hotspot_idle_timeout')],
            ['Address Per Mac', val('hotspot_address_per_mac')],
            ['Auth RADIUS', $('hotspot_use_radius') && $('hotspot_use_radius').checked ? 'Oui (use-radius=yes)' : 'Non'],
            ['Serveur RADIUS', (function () {
                var api = val('hotspot_api_url') || '';
                var m = api.match(/^https?:\/\/([^\/:]+)/i);
                return m ? m[1] : '(IP Hotspot API URL)';
            })()],
            ['Secret RADIUS', val('hotspot_radius_secret') ? '•••• (défini)' : 'Auto (NAS)']
        ]);
        if (isTrunkMode()) {
            rows = rows.concat([
                ['VLAN PPPoE', val('pppoe_setup_vlan_id')],
                ['Passerelle PPPoE', val('pppoe_setup_gateway')],
                ['Pool PPPoE', val('pppoe_setup_pool_name') + ' (' + val('pppoe_setup_pool_range') + ')'],
                ['Service PPPoE', val('pppoe_setup_service_name')],
                ['Interface PPPoE', val('pppoe_setup_server_interface') || ('vlan' + val('pppoe_setup_vlan_id') + '-pppoe')],
                ['Profils PPP', val('pppoe_setup_profile_default') + ' + ' + val('pppoe_setup_profile_expire')],
                ['NAT PPPoE', val('pppoe_setup_nat_masquerade') ? ('masquerade → ' + val('pppoe_setup_nat_interface')) : 'désactivé'],
                ['PPPoE sur Hotspot', val('pppoe_setup_on_hotspot') ? 'activé (sites sans VLAN)' : 'désactivé']
            ]);
        }
        var box = $('hs-summary');
        if (!box) return;
        box.innerHTML = rows.map(function (r) {
            return '<div class="hs-summary-row"><span class="hs-summary-label">' + escapeHtml(r[0]) + '</span><span class="hs-summary-value">' + escapeHtml(r[1]) + '</span></div>';
        }).join('');
    }

    function syncWizardStepField() {
        var field = $('hs_wizard_step');
        if (field) {
            field.value = String(currentStep);
        }
        try {
            if (window.history && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('step', String(currentStep));
                window.history.replaceState({}, '', url.toString());
            }
        } catch (e) {}
    }

    function updateStepUi() {
        updateTrunkModeUi();
        syncPppoeHiddenFields();
        var activePanelId = getPanelIdForStep(currentStep);
        ['hs-step-1', 'hs-step-2', 'hs-step-3', 'hs-step-4'].forEach(function (panelId) {
            var panel = document.getElementById(panelId);
            if (panel) {
                panel.style.display = panelId === activePanelId ? 'block' : 'none';
            }
        });
        var indicatorMap = isTrunkMode()
            ? [
                { id: 'hs-indicator-1', step: 1 },
                { id: 'hs-indicator-2', step: 2 },
                { id: 'hs-indicator-3-pppoe', step: 3 },
                { id: 'hs-indicator-4', step: 4 }
            ]
            : [
                { id: 'hs-indicator-1', step: 1 },
                { id: 'hs-indicator-2', step: 2 },
                { id: 'hs-indicator-3', step: 3 }
            ];
        ['hs-indicator-1', 'hs-indicator-2', 'hs-indicator-3', 'hs-indicator-3-pppoe', 'hs-indicator-4'].forEach(function (id) {
            var el = $(id);
            if (el) el.style.display = 'none';
        });
        indicatorMap.forEach(function (item) {
            var el = $(item.id);
            if (!el) return;
            el.style.display = '';
            el.classList.toggle('active', currentStep === item.step);
            el.classList.toggle('done', currentStep > item.step);
        });
        var prevBtn = $('hs-btn-preview');
        var nextBtn = $('hs-btn-next');
        var finalBtns = $('hs-final-actions');
        if (prevBtn) prevBtn.disabled = currentStep <= 1;
        if (nextBtn) nextBtn.style.display = currentStep >= TOTAL_STEPS ? 'none' : 'inline-block';
        if (finalBtns) finalBtns.style.display = currentStep >= TOTAL_STEPS ? 'flex' : 'none';
        if (currentStep === 1 || currentStep === 2) {
            restoreRouterSelection();
            var router = getPersistedRouterName();
            if (router && !fetchInFlight
                && !(lastSnapshot && lastSnapshotRouter === router && isSyncCacheFresh(lastSnapshotAt))) {
                fetchRouterSetup(router, true);
            }
        }
        if (currentStep === 3 && isTrunkMode()) {
            restoreRouterSelection();
            syncPppoeVlanInterface();
            var pppoeRouter = getPersistedRouterName();
            if (pppoeRouter) {
                // Réutilise le cache Hotspot+PPPoE : pas de 2e connexion API sauf si vide.
                fetchPppoeSetup(pppoeRouter, true, false);
            }
        }
        if (currentStep === TOTAL_STEPS) buildSummary();
        syncWizardStepField();
    }

    window.hsWizardGoToStep = function (step) {
        step = parseInt(step, 10);
        TOTAL_STEPS = getTotalSteps();
        if (!step || step < 1 || step > TOTAL_STEPS) return;
        currentStep = step;
        updateStepUi();
    };

    window.hsWizardGoToPanel = function (panelId) {
        panelId = String(panelId || '');
        if (panelId === 'hs-step-1') currentStep = 1;
        else if (panelId === 'hs-step-2') currentStep = 2;
        else if (panelId === 'hs-step-3') currentStep = 3;
        else if (panelId === 'hs-step-4') currentStep = isTrunkMode() ? 4 : 3;
        updateStepUi();
    };

    function bindRouterSync() {
        var routerSelect = $('hotspot_login_router');
        if (!routerSelect) return;
        routerSelect.addEventListener('change', function () {
            lastSnapshot = null;
            lastSnapshotAt = 0;
            lastSnapshotRouter = '';
            lastPppoeSuggested = null;
            lastPppoeAt = 0;
            lastPppoeRouter = '';
            var router = routerSelect.value || '';
            setSelectedRouter(router, { silent: true });
            syncPppoeHiddenFields();
            if (router) {
                fetchRouterSetup(router, false);
            } else {
                setSyncStatus('', '');
            }
        });
    }

    function bindSetupFields() {
        bindNamePicker('hotspot_pool_name_picker', 'hotspot_pool_name', applyPoolRangeFromSelection);
        bindNamePicker('hotspot_profile_picker', 'hotspot_profile', applyProfileMeta);
        var masq = $('hotspot_masquerade');
        if (masq) {
            masq.addEventListener('change', function () {
                masq.dataset.userTouched = '1';
            });
        }
        var addressPool = $('hotspot_address_pool');
        if (addressPool) {
            addressPool.addEventListener('input', syncHiddenPoolRange);
        }
        var form = $('hs-wizard-form');
        if (form) {
            form.addEventListener('submit', function () {
                syncHiddenPoolRange();
                syncWizardStepField();
            });
        }
    }

    function bindPreviewInputs() {
        ['hotspot_page_title', 'hotspot_page_tagline', 'hotspot_contact', 'hotspot_contact_phone', 'hotspot_login_router', 'hotspot_card_display'].forEach(function (name) {
            var el = document.querySelector('[name="' + name + '"]');
            if (!el) return;
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        });
    }

    function syncHotspotVlanInterface() {
        var trunk = $('lan_trunk_enabled');
        var vlanIdEl = $('hotspot_vlan_id');
        var vlanIfaceEl = $('hotspot_vlan_interface');
        var hotspotIface = $('hotspot_interface');
        if (!trunk || !trunk.checked || !vlanIdEl) return;
        var vlanId = parseInt(String(vlanIdEl.value || ''), 10);
        if (!vlanId || vlanId < 1) return;
        var autoName = 'vlan' + vlanId + '-hotspot';
        if (vlanIfaceEl && String(vlanIfaceEl.value || '').trim() === '') {
            vlanIfaceEl.value = autoName;
        }
        if (hotspotIface) {
            var opt = Array.prototype.slice.call(hotspotIface.options || []).some(function (o) {
                return o.value === autoName;
            });
            if (!opt) {
                var newOpt = document.createElement('option');
                newOpt.value = autoName;
                newOpt.textContent = autoName;
                hotspotIface.appendChild(newOpt);
            }
            hotspotIface.value = String(vlanIfaceEl && vlanIfaceEl.value ? vlanIfaceEl.value : autoName).trim() || autoName;
        }
    }

    function toggleTrunkFields() {
        var trunk = $('lan_trunk_enabled');
        var show = trunk && trunk.checked;
        var panel = document.getElementById('hs-trunk-panel');
        if (panel) {
            panel.classList.toggle('active', !!show);
        }
        document.querySelectorAll('.hs-trunk-field').forEach(function (el) {
            el.style.display = show ? '' : 'none';
        });
        var classicGroup = $('hs-classic-interface-group');
        if (classicGroup) {
            classicGroup.style.display = show ? 'none' : '';
        }
        document.querySelectorAll('.hs-classic-if-help').forEach(function (el) {
            el.style.display = show ? 'none' : '';
        });
        document.querySelectorAll('.hs-trunk-if-help').forEach(function (el) {
            el.style.display = show ? '' : 'none';
        });
        if (show) {
            ensureRouterPortsForTrunk();
        }
    }

    function bindTrunkToggle() {
        var trunk = $('lan_trunk_enabled');
        if (!trunk) return;
        var sync = function () {
            trunk.dataset.userTouched = '1';
            updateTrunkModeUi();
            toggleTrunkFields();
            syncHotspotVlanInterface();
            syncPppoeVlanInterface();
            syncPppoeHiddenFields();
            ensureRouterPortsForTrunk();
            if (currentStep > getTotalSteps()) {
                currentStep = getTotalSteps();
            }
            updateStepUi();
        };
        trunk.addEventListener('change', sync);
        ['hotspot_vlan_id', 'hotspot_vlan_interface', 'pppoe_setup_vlan_id', 'pppoe_setup_vlan_interface'].forEach(function (id) {
            var el = $(id);
            if (el) {
                el.addEventListener('input', function () {
                    syncHotspotVlanInterface();
                    syncPppoeVlanInterface();
                });
                el.addEventListener('change', function () {
                    syncHotspotVlanInterface();
                    syncPppoeVlanInterface();
                });
            }
        });
        var bridgeInput = $('lan_bridge_name');
        if (bridgeInput) {
            bridgeInput.addEventListener('input', syncPppoeHiddenFields);
            bridgeInput.addEventListener('change', syncPppoeHiddenFields);
        }
        sync();
    }

    function bindRadiusToggle() {
        var cb = $('hotspot_use_radius');
        var secretGroup = $('hs-radius-secret-group');
        if (!cb || !secretGroup) {
            return;
        }
        var sync = function () {
            secretGroup.style.display = cb.checked ? '' : 'none';
        };
        cb.addEventListener('change', sync);
        sync();
    }

    function init() {
        var prevBtn = $('hs-btn-preview');
        var nextBtn = $('hs-btn-next');
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                if (currentStep > 1) {
                    currentStep--;
                    updateStepUi();
                }
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (currentStep === 1) {
                    restoreRouterSelection();
                    var router = getPersistedRouterName();
                    syncRouterPersistValue(router);
                    syncPppoeHiddenFields();
                    if (router) {
                        persistRouterToServer(router, true);
                    }
                }
                TOTAL_STEPS = getTotalSteps();
                if (currentStep < TOTAL_STEPS) {
                    currentStep++;
                    updateStepUi();
                }
            });
        }
        bindPreviewInputs();
        bindRouterSync();
        bindSetupFields();
        bindPortPickerDelegation();
        bindRadiusToggle();
        bindTrunkToggle();
        var initialStep = parseInt(window.HS_INITIAL_STEP || '1', 10);
        TOTAL_STEPS = getTotalSteps();
        if (initialStep >= 1 && initialStep <= TOTAL_STEPS) {
            currentStep = initialStep;
        } else if (initialStep > TOTAL_STEPS) {
            currentStep = TOTAL_STEPS;
        }
        updatePreview();
        restoreRouterSelection();
        updateStepUi();
    }

    window.hsGetPersistedRouter = getPersistedRouterName;
    window.hsRestoreRouterSelection = restoreRouterSelection;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
