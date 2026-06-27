(function () {
    'use strict';

    var TOTAL_STEPS = 3;
    var currentStep = 1;
    var routerPools = [];
    var routerProfiles = [];
    var lastSnapshot = null;

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
            if (m === 'chap') return 'http-chap';
            if (m === 'cookie') return 'mac-cookie';
            return m;
        });
        document.querySelectorAll('input[name="hotspot_login_methods[]"]').forEach(function (cb) {
            cb.checked = methods.indexOf(cb.value) !== -1;
        });
    }

    function applySuggested(suggested, preserveUserEdits) {
        if (!suggested) return;
        var fields = [
            'hotspot_name',
            'hotspot_interface',
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
            if (preserveUserEdits && String(el.value || '').trim() !== '') return;
            if (suggested[field] != null && suggested[field] !== '') {
                el.value = suggested[field];
            }
        });
        if (!preserveUserEdits || !$('hotspot_masquerade') || !$('hotspot_masquerade').dataset.userTouched) {
            setVal('hotspot_masquerade', suggested.hotspot_masquerade || '1');
        }
        if (suggested.hotspot_login_methods) {
            setLoginMethodsFromRouter(suggested.hotspot_login_methods);
        }
        var hiddenRange = $('hotspot_pool_range');
        if (hiddenRange && suggested.hotspot_pool_range) {
            hiddenRange.value = suggested.hotspot_pool_range;
        }
    }

    function applySnapshot(data, preserveUserEdits) {
        lastSnapshot = data;
        routerPools = mergePoolsFromHotspots(data.pools || [], data.hotspots || []);
        routerProfiles = data.profiles || [];
        var suggested = data.suggested || {};

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
        if (suggested.hotspot_pool_name && !poolItems.some(function (p) { return p.value === suggested.hotspot_pool_name; })) {
            poolItems.push({ value: suggested.hotspot_pool_name, label: suggested.hotspot_pool_name });
        }
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
        var suggestedProfile = suggested.hotspot_profile || 'default';
        if (suggestedProfile && !profileItems.some(function (p) { return p.value === suggestedProfile; })) {
            profileItems.push({ value: suggestedProfile, label: suggestedProfile });
        }
        setupNamePicker(
            'hotspot_profile_picker',
            'hotspot_profile',
            profileItems,
            suggested.hotspot_profile || 'default',
            preserveUserEdits
        );

        applySuggested(suggested, preserveUserEdits);
        applyPoolRangeFromSelection();
        applyProfileMeta(val('hotspot_profile'));
    }

    function fetchRouterSetup(routerName, preserveUserEdits) {
        var fetchUrl = window.HS_FETCH_URL || '';
        if (!fetchUrl || !routerName) {
            setSyncStatus('', '');
            return Promise.resolve(null);
        }
        setSyncStatus('loading', 'Synchronisation avec « ' + routerName + ' »…');
        return fetch(fetchUrl + '&router=' + encodeURIComponent(routerName) + '&hotspot_name=' + encodeURIComponent(val('hotspot_name') || ''), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) {
                    setSyncStatus('error', (data && data.message) ? data.message : 'Synchronisation impossible.');
                    return data;
                }
                applySnapshot(data, !!preserveUserEdits);
                var ifaceCount = (data.interfaces || []).length;
                var poolCount = routerPools.length;
                var profileCount = (data.profiles || []).length;
                setSyncStatus('ok', 'Routeur synchronisé : ' + ifaceCount + ' interface(s), ' + poolCount + ' pool(s), ' + profileCount + ' profil(s).');
                return data;
            })
            .catch(function (err) {
                setSyncStatus('error', 'Erreur réseau : ' + (err.message || 'connexion impossible'));
                return null;
            });
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

    function loginMethodsSummary() {
        var selected = [];
        document.querySelectorAll('input[name="hotspot_login_methods[]"]:checked').forEach(function (cb) {
            if (cb.value === 'http-chap') selected.push('HTTP CHAP');
            else if (cb.value === 'http-pap') selected.push('HTTP PAP');
            else if (cb.value === 'mac-cookie') selected.push('MAC COOKIE');
            else selected.push(cb.value);
        });
        return selected.length ? selected.join(', ') : '(aucune)';
    }

    function buildSummary() {
        var rows = [
            ['Titre', val('hotspot_page_title')],
            ['Description', val('hotspot_page_tagline')],
            ['Routeur', labelForSelect('hotspot_login_router')],
            ['Affichage cartes', labelForSelect('hotspot_card_display')],
            ['Nom Hotspot', val('hotspot_name')],
            ['Interface', labelForSelect('hotspot_interface')],
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
        ];
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
        for (var s = 1; s <= TOTAL_STEPS; s++) {
            var panel = $('hs-step-' + s);
            if (panel) panel.style.display = s === currentStep ? 'block' : 'none';
            var indicator = $('hs-indicator-' + s);
            if (indicator) {
                indicator.classList.toggle('active', s === currentStep);
                indicator.classList.toggle('done', s < currentStep);
            }
        }
        var prevBtn = $('hs-btn-preview');
        var nextBtn = $('hs-btn-next');
        var finalBtns = $('hs-final-actions');
        if (prevBtn) prevBtn.disabled = currentStep <= 1;
        if (nextBtn) nextBtn.style.display = currentStep >= TOTAL_STEPS ? 'none' : 'inline-block';
        if (finalBtns) finalBtns.style.display = currentStep >= TOTAL_STEPS ? 'flex' : 'none';
        if (currentStep === 2) {
            var router = val('hotspot_login_router');
            if (router && !lastSnapshot) {
                fetchRouterSetup(router, true);
            }
        }
        if (currentStep === TOTAL_STEPS) buildSummary();
        syncWizardStepField();
    }

    window.hsWizardGoToStep = function (step) {
        step = parseInt(step, 10);
        if (!step || step < 1 || step > TOTAL_STEPS) return;
        currentStep = step;
        updateStepUi();
    };

    function bindRouterSync() {
        var routerSelect = $('hotspot_login_router');
        if (!routerSelect) return;
        routerSelect.addEventListener('change', function () {
            lastSnapshot = null;
            var router = routerSelect.value || '';
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
        ['hotspot_page_title', 'hotspot_page_tagline', 'hotspot_login_router', 'hotspot_card_display'].forEach(function (name) {
            var el = document.querySelector('[name="' + name + '"]');
            if (!el) return;
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        });
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
                if (currentStep < TOTAL_STEPS) {
                    currentStep++;
                    updateStepUi();
                }
            });
        }
        bindPreviewInputs();
        bindRouterSync();
        bindSetupFields();
        bindRadiusToggle();
        var initialStep = parseInt(window.HS_INITIAL_STEP || '1', 10);
        if (initialStep >= 1 && initialStep <= TOTAL_STEPS) {
            currentStep = initialStep;
        }
        updatePreview();
        updateStepUi();

        var initialRouter = window.HS_INITIAL_ROUTER || val('hotspot_login_router');
        if (initialRouter && currentStep === 2) {
            fetchRouterSetup(initialRouter, true);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
