(function () {
    'use strict';

    var TOTAL_STEPS = 3;
    var currentStep = 1;

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

    function checkedMethods() {
        var boxes = document.querySelectorAll('[name="hotspot_login_methods[]"]:checked');
        return Array.prototype.map.call(boxes, function (b) { return b.value; });
    }

    function labelForSelect(name) {
        var el = document.querySelector('[name="' + name + '"]');
        if (!el || el.tagName !== 'SELECT') return val(name);
        var opt = el.options[el.selectedIndex];
        return opt ? opt.text : val(name);
    }

    function updatePreview() {
        var title = val('hotspot_page_title') || 'yoyo';
        var tagline = val('hotspot_page_tagline') || 'Description / Tagline';
        var color = val('hotspot_login_color') || 'green';
        var shape = val('hotspot_card_shape') || 'rounded';
        var banner = (val('hotspot_banner_text') || '').trim();
        var chat = val('hotspot_chat_service') || 'disabled';
        var planOrder = labelForSelect('hotspot_plan_order');

        var scheme = schemes[color] || schemes.green;
        var screen = $('hs-preview-screen');
        var bannerEl = $('hs-preview-banner');
        var chatEl = $('hs-preview-chat');

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

        var cards = document.querySelectorAll('.hs-preview-pkg');
        var radius = shapeRadius[shape] || '14px';
        cards.forEach(function (card) {
            card.style.borderRadius = radius;
            card.style.background = scheme.card;
            card.style.borderColor = 'rgba(255,255,255,.14)';
        });

        var btn = $('hs-preview-btn');
        if (btn) btn.style.background = scheme.accent;

        var orderEl = $('hs-preview-order');
        if (orderEl) orderEl.textContent = planOrder;

        if (bannerEl) {
            bannerEl.style.display = banner ? 'block' : 'none';
            var text = bannerEl.querySelector('.hs-banner-text');
            if (text) text.textContent = banner;
        }

        if (chatEl) {
            var chatOn = chat === 'enabled' || chat === 'whatsapp' || chat === 'telegram';
            chatEl.style.display = chatOn ? 'flex' : 'none';
        }

        var pkgData = $('hs-preview-pkg-data');
        var pkgUnlim = $('hs-preview-pkg-unlim');
        var order = val('hotspot_plan_order');
        if (pkgData && pkgUnlim) {
            pkgData.style.order = '';
            pkgUnlim.style.order = '';
            if (order === 'price_desc') {
                pkgData.style.order = '2';
                pkgUnlim.style.order = '1';
            }
            var parent = pkgData.parentNode;
            if (parent && !parent.classList.contains('hs-preview-packages')) {
                parent.classList.add('hs-preview-packages');
            }
        }
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

        if (currentStep === TOTAL_STEPS) buildSummary();
    }

    function buildSummary() {
        var methods = checkedMethods();
        var methodsLabel = methods.length ? methods.map(function (m) {
            if (m === 'chap') return 'HTTP CHAP';
            if (m === 'pap') return 'HTTP PAP';
            if (m === 'cookie') return 'MAC Cookie';
            return m;
        }).join(', ') : '—';

        var poolMode = val('hotspot_pool_mode') === 'existing' ? 'Pool existant' : 'Nouveau pool';
        var chatLabel = val('hotspot_chat_service') === 'enabled' ? 'Activé' : 'Désactivé';

        var rows = [
            ['Titre', val('hotspot_page_title')],
            ['Description', val('hotspot_page_tagline')],
            ['Routeur', labelForSelect('hotspot_login_router')],
            ['Couleur', labelForSelect('hotspot_login_color')],
            ['Forme des cartes', labelForSelect('hotspot_card_shape')],
            ['Affichage cartes', labelForSelect('hotspot_card_display')],
            ['Ordre forfaits', labelForSelect('hotspot_plan_order')],
            ['Bandeau', val('hotspot_banner_text') || '(vide)'],
            ['Chat', chatLabel],
            ['Nom Hotspot', val('hotspot_name')],
            ['Interface', labelForSelect('hotspot_interface')],
            ['Profil', val('hotspot_profile')],
            ['DNS', val('hotspot_dns_name')],
            ['Login', methodsLabel],
            ['Cookie Lifetime', val('hotspot_cookie_lifetime')],
            ['Idle Timeout', val('hotspot_idle_timeout')],
            ['Pool', poolMode],
            ['Nom pool', val('hotspot_pool_name')],
            ['Plage IP', val('hotspot_pool_range')],
            ['Keepalive', val('hotspot_keepalive_timeout')],
            ['SMTP', val('hotspot_smtp_server')]
        ];

        var box = $('hs-summary');
        if (!box) return;
        box.innerHTML = rows.map(function (r) {
            return '<div class="hs-summary-row"><span class="hs-summary-label">' + escapeHtml(r[0]) + '</span><span class="hs-summary-value">' + escapeHtml(r[1]) + '</span></div>';
        }).join('');
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function bindPreviewInputs() {
        var names = [
            'hotspot_page_title', 'hotspot_page_tagline', 'hotspot_login_router',
            'hotspot_login_color', 'hotspot_card_shape', 'hotspot_card_display',
            'hotspot_plan_order', 'hotspot_banner_text', 'hotspot_chat_service'
        ];
        names.forEach(function (name) {
            var el = document.querySelector('[name="' + name + '"]');
            if (!el) return;
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        });
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
        updatePreview();
        updateStepUi();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
