(function () {
    'use strict';

    var cfg = window.PLAN_RECHARGE_UI || {};
    var form = document.getElementById('plan-recharge-form');
    var typeInputs = document.querySelectorAll('input[name="type"]');
    var usingSelect = document.getElementById('recharge_using');
    var planTypeInput = document.getElementById('plan_type');
    var help = document.getElementById('recharge_using_help');

    function selectedType() {
        var checked = document.querySelector('input[name="type"]:checked');
        return checked ? checked.value : 'PPPOE';
    }

    function legacyValue(label) {
        return String(label || '').trim().toLowerCase().replace(/\s+/g, '_');
    }

    function rebuildUsingOptions() {
        if (!usingSelect) {
            return;
        }
        var type = selectedType();
        if (planTypeInput) {
            planTypeInput.value = type;
        }

        var options = [];
        if (type === 'PPPOE') {
            options = (cfg.pppoeOptions || []).slice();
            if (help) {
                help.textContent = 'PPPoE : Cash / ' + (cfg.zeroLabel || 'XAF 0') + ' (SuperAdmin) ou Mobile Money selon votre profil.';
            }
        } else {
            (cfg.legacyOptions || []).forEach(function (label) {
                options.push({ value: legacyValue(label), label: label });
            });
            if (cfg.enableBalance) {
                options.push({ value: 'balance', label: cfg.balanceLabel || 'Customer Balance' });
            }
            if (window.PLAN_RECHARGE_UI && window.PLAN_RECHARGE_UI.isSuperAdmin) {
                options.push({ value: 'zero', label: cfg.zeroLabel || 'XAF 0' });
            }
            if (help) {
                help.textContent = '';
            }
        }

        usingSelect.innerHTML = '';
        options.forEach(function (opt, idx) {
            var el = document.createElement('option');
            el.value = opt.value;
            el.textContent = opt.label;
            if (idx === 0) {
                el.selected = true;
            }
            usingSelect.appendChild(el);
        });
    }

    typeInputs.forEach(function (input) {
        input.addEventListener('change', rebuildUsingOptions);
    });

    if (form) {
        form.addEventListener('submit', function () {
            if (planTypeInput) {
                planTypeInput.value = selectedType();
            }
        });
    }

    rebuildUsingOptions();
})();
