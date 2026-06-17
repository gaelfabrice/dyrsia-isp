{include file="sections/header.tpl"}

<link rel="stylesheet" href="{$app_url}/ui/ui/styles/monitoring-expiry.css?2026.06.17">

<div class="mon-page" id="monExpiryPage">
    <div class="mon-header">
        <div class="mon-header-left">
            <a href="{Text::url('monitoring')}" class="mon-btn mon-btn-back">
                <i class="fa fa-arrow-left"></i> {Lang::T('Back')}
            </a>
            <div class="mon-header-ic"><i class="fa fa-user-times"></i></div>
            <div>
                <h1>{Lang::T('Customer Expiry Status')}</h1>
                <p>{Lang::T('Monitoring')}</p>
            </div>
        </div>
        <div class="mon-header-actions">
            <span class="mon-date-pill">
                <i class="fa fa-calendar-o"></i>
                {Lang::T('Today')}, {$exp_today_label|escape}
            </span>
        </div>
    </div>

    <div class="mon-exp-stats">
        <div class="mon-kpi expired">
            <div>
                <div class="mon-kpi-label"><span class="dot"></span> {Lang::T('Total Expired')}</div>
                <div class="mon-kpi-value">{$exp_total_expired|escape}</div>
            </div>
            <div class="mon-kpi-icon"><i class="fa fa-clock-o"></i></div>
        </div>
        <div class="mon-kpi coming">
            <div>
                <div class="mon-kpi-label"><span class="dot"></span> {Lang::T('Total Coming')}</div>
                <div class="mon-kpi-value">{$exp_total_coming|escape}</div>
            </div>
            <div class="mon-kpi-icon"><i class="fa fa-calendar-plus-o"></i></div>
        </div>
    </div>

    <div class="mon-card">
        <div class="mon-exp-toolbar">
            <div class="mon-exp-search-wrap">
                <i class="fa fa-search"></i>
                <input type="text" id="exp-search" class="mon-exp-search" placeholder="{Lang::T('Search customers...')}" autocomplete="off">
            </div>
            <div class="mon-exp-filter-wrap">
                <button type="button" class="mon-exp-filter-btn" id="exp-filter-toggle">
                    <i class="fa fa-sliders"></i> {Lang::T('Filter')}
                </button>
                <div class="mon-exp-filter-menu" id="exp-filter-menu">
                    <button type="button" class="active" data-filter="username">{Lang::T('Username')}</button>
                    <button type="button" data-filter="fullname">{Lang::T('Full Name')}</button>
                    <button type="button" data-filter="phone">{Lang::T('Phone')}</button>
                    <button type="button" data-filter="email">{Lang::T('Email')}</button>
                </div>
            </div>
        </div>

        <div class="mon-exp-table-wrap">
            <table class="mon-exp-table" id="exp-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Package')}</th>
                        <th>{Lang::T('Router / Location')}</th>
                        <th>{Lang::T('Expiry')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $exp_rows as $row}
                    <tr class="exp-row"
                        data-username="{$row.username|lower|escape}"
                        data-fullname="{$row.fullname|lower|escape}"
                        data-phone="{$row.phonenumber|lower|escape}"
                        data-email="{$row.email|lower|escape}">
                        <td>
                            <a href="{Text::url('customers/view/', $row.id)}" class="mon-exp-user">
                                <span class="mon-exp-user-avatar"><i class="fa fa-user"></i></span>
                                {$row.username|escape}
                            </a>
                        </td>
                        <td>
                            {if $row.status == 'expired'}
                            <span class="mon-exp-badge expired"><span class="dot"></span> {Lang::T('Already Expired')}</span>
                            {elseif $row.status == 'soon'}
                            <span class="mon-exp-badge soon"><span class="dot"></span> {Lang::T('Soon')}</span>
                            {else}
                            <span class="mon-exp-badge coming"><span class="dot"></span> {Lang::T('Coming')}</span>
                            {/if}
                        </td>
                        <td>{$row.namebp|escape}</td>
                        <td>
                            <span class="mon-exp-router">
                                <i class="fa fa-map-marker"></i>
                                {$row.routers|escape}
                            </span>
                        </td>
                        <td>
                            <span class="mon-exp-time {$row.status|escape} status-time"
                                  data-expiration="{$row.expiration_at|escape}"
                                  data-status="{$row.status|escape}"></span>
                        </td>
                    </tr>
                    {foreachelse}
                    <tr>
                        <td colspan="5">
                            <div class="mon-empty">{Lang::T('No data available')}</div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        <div class="mon-exp-footer">
            <div class="mon-exp-footer-meta" id="exp-showing-meta">
                {Lang::T('Showing')} {$exp_rows|@count} {Lang::T('of')} {$exp_total_entries|escape} {Lang::T('entries')}
            </div>
            <div class="mon-exp-pagination">
                <a href="{$exp_prev_url|escape:'url'}"
                   class="mon-exp-page-btn {if $exp_current_page <= 1}disabled{/if}"
                   {if $exp_current_page <= 1}onclick="return false;"{/if}>
                    {Lang::T('Prev')}
                </a>
                <span class="mon-exp-page-btn active">{$exp_current_page|escape}</span>
                <a href="{$exp_next_url|escape:'url'}"
                   class="mon-exp-page-btn {if $exp_current_page >= $exp_max_pages}disabled{/if}"
                   {if $exp_current_page >= $exp_max_pages}onclick="return false;"{/if}>
                    {Lang::T('Next')}
                </a>
            </div>
        </div>
    </div>
</div>

<script>
{literal}
(function () {
    let activeFilter = 'username';
    const searchInput = document.getElementById('exp-search');
    const filterToggle = document.getElementById('exp-filter-toggle');
    const filterMenu = document.getElementById('exp-filter-menu');
    const rows = Array.from(document.querySelectorAll('.exp-row'));
    const showingMeta = document.getElementById('exp-showing-meta');
    const showingPrefix = showingMeta ? showingMeta.textContent.split(/\d+/)[0].trim() : 'Showing';
    const showingSuffix = showingMeta ? showingMeta.textContent.replace(/^.*?(\d+\s)/, '').replace(/^\d+\s*/, '') : '';

    function applyFilter() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visible = 0;
        rows.forEach(function (tr) {
            const haystack = (tr.getAttribute('data-' + activeFilter) || '').toLowerCase();
            const show = query === '' || haystack.indexOf(query) !== -1;
            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (showingMeta && rows.length) {
            const total = showingMeta.textContent.match(/of\s+(\d+)/);
            const totalNum = total ? total[1] : rows.length;
            showingMeta.textContent = showingPrefix + ' ' + visible + ' of ' + totalNum + ' ' + (showingSuffix || 'entries');
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);

    if (filterToggle && filterMenu) {
        filterToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            filterMenu.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            filterMenu.classList.remove('open');
        });
        filterMenu.querySelectorAll('button[data-filter]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                activeFilter = btn.getAttribute('data-filter');
                filterMenu.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                filterMenu.classList.remove('open');
                applyFilter();
            });
        });
    }

    function updateTimes() {
        const now = new Date();
        document.querySelectorAll('.status-time').forEach(function (el) {
            const raw = el.getAttribute('data-expiration');
            const status = el.getAttribute('data-status');
            const exp = new Date(raw.replace(' ', 'T'));
            if (!raw || isNaN(exp.getTime())) {
                el.textContent = '—';
                return;
            }
            const diffMs = status === 'expired' ? (now - exp) : (exp - now);
            if (diffMs < 0 && status !== 'expired') {
                el.textContent = '—';
                return;
            }
            const totalSec = Math.floor(Math.abs(diffMs) / 1000);
            const d = Math.floor(totalSec / 86400);
            const h = Math.floor((totalSec % 86400) / 3600);
            const m = Math.floor((totalSec % 3600) / 60);
            if (status === 'expired') {
                el.textContent = 'Expired ' + d + 'd ' + h + 'h ' + m + 'm ago';
            } else {
                el.textContent = 'Expires in ' + d + 'd ' + h + 'h';
            }
        });
    }

    updateTimes();
    setInterval(updateTimes, 1000);
})();
{/literal}
</script>

{include file="sections/footer.tpl"}
