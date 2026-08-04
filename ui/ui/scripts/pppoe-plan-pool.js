(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    var poolsByName = {};
    var $routerSelect = null;
    var $picker = null;
    var $poolNameNew = null;
    var $poolRange = null;
    var $poolLocalIp = null;
    var $sectionExisting = null;
    var $sectionNew = null;
    var $statusEl = null;
    var $syncBtn = null;
    var $form = null;

    function poolMode() {
        var checked = $('input[name="pool_mode"]:checked').val();
        return checked === 'new' ? 'new' : 'existing';
    }

    function setStatus(text, isError) {
        if (!$statusEl || !$statusEl.length) {
            return;
        }
        $statusEl.text(text || '');
        $statusEl.toggleClass('text-danger', !!isError);
        $statusEl.toggleClass('text-muted', !isError);
    }

    function applyModeUi() {
        var isNew = poolMode() === 'new';
        $sectionExisting.toggle(!isNew);
        $sectionNew.toggle(isNew);
        $picker.prop('required', !isNew);
        $poolNameNew.prop('required', isNew);
        $poolRange.prop('required', isNew);
        $poolLocalIp.prop('required', false);
        if ($syncBtn && $syncBtn.length) {
            $syncBtn.toggle(!isNew);
        }
    }

    function fillPicker(pools, placeholder) {
        poolsByName = {};
        var html = '<option value="">' + (placeholder || '—') + '</option>';
        (pools || []).forEach(function (pool) {
            if (!pool || !pool.name) {
                return;
            }
            poolsByName[pool.name] = pool;
            var label = pool.name;
            if (pool.ranges) {
                label += ' (' + pool.ranges + ')';
            }
            html += '<option value="' + $('<div>').text(pool.name).html() + '">' + $('<div>').text(label).html() + '</option>';
        });
        $picker.html(html);
        if ($picker.hasClass('select2-hidden-accessible')) {
            $picker.trigger('change.select2');
        }
    }

    function buildFetchUrl(routerName) {
        var base = window.PPPOE_POOL_FETCH_URL || '';
        if (!base) {
            return '';
        }
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        return base + sep + 'router=' + encodeURIComponent(routerName || '');
    }

    function loadRouterPools(routerName, manual) {
        routerName = (routerName || '').trim();
        $picker.val('');
        if (!routerName) {
            fillPicker([], '— ' + (window.LangSelectPool || 'Choisir un pool') + ' —');
            setStatus('');
            return;
        }

        var fetchUrl = buildFetchUrl(routerName);
        if (!fetchUrl) {
            setStatus('URL de synchronisation manquante', true);
            return;
        }

        if ($syncBtn && $syncBtn.length) {
            $syncBtn.prop('disabled', true);
        }
        setStatus(window.LangSyncingPools || 'Synchronisation des pools depuis le MikroTik…');

        $.ajax({
            url: fetchUrl,
            method: 'GET',
            dataType: 'json',
            cache: false,
            timeout: 30000
        }).done(function (data) {
            if (!data || !data.ok) {
                fillPicker([], '— ' + (window.LangSelectPool || 'Choisir un pool') + ' —');
                setStatus((data && data.message) || (window.LangPoolSyncFailed || 'Échec de la synchronisation des pools'), true);
                return;
            }
            fillPicker(data.pools || [], '— ' + (window.LangSelectPool || 'Choisir un pool') + ' —');
            var count = (data.pools || []).length;
            if (count > 0) {
                setStatus(count + ' pool(s) disponible(s) sur « ' + routerName + ' ».');
            } else {
                setStatus('Aucun pool sur ce routeur — basculez sur « Nouveau pool » pour en créer un.');
            }
        }).fail(function (xhr) {
            fillPicker([], '— ' + (window.LangSelectPool || 'Choisir un pool') + ' —');
            var msg = window.LangPoolSyncFailed || 'Échec de la synchronisation des pools';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            setStatus(msg, true);
        }).always(function () {
            if ($syncBtn && $syncBtn.length) {
                $syncBtn.prop('disabled', false);
            }
        });
    }

    function loadRadiusPools() {
        var radiusUrl = window.PPPOE_POOL_RADIUS_URL || '';
        if (!radiusUrl) {
            return;
        }
        setStatus(window.LangSyncingPools || 'Synchronisation des pools…');
        $.ajax({
            url: radiusUrl,
            data: { routers: 'radius' },
            cache: false
        }).done(function (html) {
            var pools = [];
            var $wrap = $('<div>').html(html);
            $wrap.find('option').each(function () {
                var value = $(this).val();
                if (value) {
                    pools.push({ name: value, ranges: '', local_ip: '' });
                }
            });
            fillPicker(pools, '— ' + (window.LangSelectPool || 'Choisir un pool') + ' —');
            setStatus(pools.length + ' pool(s) Radius trouvé(s).');
        }).fail(function () {
            setStatus(window.LangPoolSyncFailed || 'Échec de la synchronisation des pools', true);
        });
    }

    function validateBeforeSubmit() {
        if (poolMode() === 'new') {
            if (!$poolNameNew.val().trim()) {
                setStatus('Indiquez le nom du nouveau pool.', true);
                $poolNameNew.focus();
                return false;
            }
            if (!$poolRange.val().trim()) {
                setStatus('Indiquez la plage IP du nouveau pool.', true);
                $poolRange.focus();
                return false;
            }
        } else if (!$picker.val()) {
            setStatus('Sélectionnez un pool existant.', true);
            $picker.focus();
            return false;
        }
        return true;
    }

    function syncExistingPoolForCurrentRouter() {
        if (!$routerSelect || !$routerSelect.length || poolMode() !== 'existing') {
            return;
        }
        loadRouterPools($routerSelect.val(), false);
    }

    function bindEvents() {
        $('input[name="pool_mode"]').off('change.pppoePool').on('change.pppoePool', function () {
            applyModeUi();
            syncExistingPoolForCurrentRouter();
        });

        $routerSelect
            .off('change.pppoePool input.pppoePool select2:select.pppoePool select2:clear.pppoePool select2:close.pppoePool')
            .on('change.pppoePool input.pppoePool select2:select.pppoePool select2:clear.pppoePool select2:close.pppoePool', function () {
                syncExistingPoolForCurrentRouter();
            });

        var routerNode = $routerSelect.get(0);
        if (routerNode) {
            if (routerNode._pppoePoolNativeHandler) {
                routerNode.removeEventListener('change', routerNode._pppoePoolNativeHandler);
            }
            routerNode._pppoePoolNativeHandler = function () {
                syncExistingPoolForCurrentRouter();
            };
            routerNode.addEventListener('change', routerNode._pppoePoolNativeHandler);
        }

        if ($syncBtn && $syncBtn.length) {
            $syncBtn.off('click.pppoePool').on('click.pppoePool', function (e) {
                e.preventDefault();
                loadRouterPools($routerSelect.val(), true);
            });
        }

        if ($form && $form.length) {
            $form.off('submit.pppoePool').on('submit.pppoePool', function (e) {
                if (!validateBeforeSubmit()) {
                    e.preventDefault();
                }
            });
        }
    }

    function initPppoePlanPool() {
        $routerSelect = $('#routers');
        $picker = $('#pool_picker');
        $poolNameNew = $('#pool_name_new');
        $poolRange = $('#pool_range');
        $poolLocalIp = $('#pool_local_ip');
        $sectionExisting = $('#pool_section_existing');
        $sectionNew = $('#pool_section_new');
        $statusEl = $('#pool_sync_status');
        $syncBtn = $('#pool_sync_btn');
        $form = $picker.closest('form');

        if (!$picker.length || !$sectionExisting.length || !$sectionNew.length || !$routerSelect.length) {
            return;
        }

        applyModeUi();
        bindEvents();

        window.pppoePlanPoolLoadRouter = function (routerName) {
            applyModeUi();
            loadRouterPools(routerName, true);
        };
        window.pppoePlanPoolLoadRadius = function () {
            applyModeUi();
            loadRadiusPools();
        };

        if ($routerSelect.val() && poolMode() === 'existing') {
            loadRouterPools($routerSelect.val(), false);
        }
    }

    $(initPppoePlanPool);
})(window.jQuery);
