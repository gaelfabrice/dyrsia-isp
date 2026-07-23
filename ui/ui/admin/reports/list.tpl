{include file="sections/header.tpl"}

<style>
{literal}
.ra-page {
    --ra-bg: transparent;
    --ra-card: rgba(15, 23, 42, 0.92);
    --ra-text: #f8fafc;
    --ra-heading: #ffffff;
    --ra-muted: #94a3b8;
    --ra-line: rgba(148, 163, 184, 0.18);
    --ra-brand: #2563eb;
    --ra-brand2: #0891b2;
    --ra-success: #10b981;
    --ra-shadow: 0 12px 40px rgba(0, 0, 0, 0.22);
    font-family: Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    color: var(--ra-text);
    margin: 0 -15px 0;
    padding: 0 15px 28px;
}
body.theme-light .ra-page {
    --ra-card: #ffffff;
    --ra-text: #1e293b;
    --ra-heading: #0f172a;
    --ra-muted: #64748b;
    --ra-line: #e2e8f0;
    --ra-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
}
.ra-page * { box-sizing: border-box; }

.ra-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}
.ra-hero h1 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: var(--ra-heading);
    letter-spacing: -0.02em;
}
.ra-hero p { margin: 6px 0 0; color: var(--ra-muted); font-size: 14px; }
.ra-period-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 999px;
    border: 1px solid var(--ra-line);
    background: var(--ra-card);
    color: var(--ra-heading);
    font-size: 13px;
    font-weight: 700;
    box-shadow: var(--ra-shadow);
    white-space: nowrap;
}

.ra-filters {
    background: var(--ra-card);
    border: 1px solid var(--ra-line);
    border-radius: 16px;
    padding: 18px;
    margin-bottom: 20px;
    box-shadow: var(--ra-shadow);
}
.ra-filter-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    align-items: end;
}
.ra-filter-grid.row2 {
    margin-top: 14px;
    grid-template-columns: 1fr 1fr 1fr 1.4fr;
}
@media (max-width: 1100px) {
    .ra-filter-grid, .ra-filter-grid.row2 { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .ra-filter-grid, .ra-filter-grid.row2 { grid-template-columns: 1fr; }
}
.ra-field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ra-muted);
    margin-bottom: 6px;
}
.ra-input, .ra-select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid var(--ra-line);
    background: rgba(2, 6, 23, 0.55);
    color: var(--ra-text);
    font-size: 14px;
    font-family: inherit;
}
body.theme-light .ra-input, body.theme-light .ra-select { background: #f8fafc; }
.ra-input:focus, .ra-select:focus {
    outline: none;
    border-color: var(--ra-brand);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
}
.ra-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    height: 100%;
    padding-top: 22px;
}
.ra-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border: 0;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--ra-brand), var(--ra-brand2));
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
}
.ra-btn:hover { opacity: 0.95; color: #fff; }
.ra-btn-outline {
    background: transparent;
    border: 1px solid var(--ra-line);
    color: var(--ra-text);
}
.ra-btn-outline:hover { background: rgba(148,163,184,0.08); color: var(--ra-heading); }

.ra-charts {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 1100px) { .ra-charts { grid-template-columns: 1fr; } }

.ra-card {
    background: var(--ra-card);
    border: 1px solid var(--ra-line);
    border-radius: 16px;
    box-shadow: var(--ra-shadow);
    overflow: hidden;
}
.ra-card-head {
    padding: 16px 18px;
    border-bottom: 1px solid var(--ra-line);
}
.ra-card-head h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--ra-heading);
}
.ra-card-body { padding: 18px; }
.ra-chart-wrap {
    position: relative;
    min-height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ra-chart-wrap canvas { max-height: 220px; }
.ra-chart-wrap.wide { min-height: 260px; }
.ra-chart-wrap.wide canvas { max-height: 240px; width: 100% !important; }

.ra-table-section { margin-bottom: 20px; }
.ra-table-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--ra-line);
}
.ra-table-head-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.ra-table-head h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--ra-heading);
}
.ra-count-badge {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(148,163,184,0.15);
    color: var(--ra-muted);
    font-size: 12px;
    font-weight: 700;
}
.ra-table-tools {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.ra-search {
    min-width: 220px;
    padding: 9px 14px 9px 36px;
    border-radius: 999px;
    border: 1px solid var(--ra-line);
    background: rgba(2, 6, 23, 0.55);
    color: var(--ra-text);
    font-size: 13px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.242 1.156a5 5 0 1 1 0-10 5 5 0 0 1 0 10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 12px center;
}
body.theme-light .ra-search { background-color: #f8fafc; }

.ra-table-wrap { overflow-x: auto; }
.ra-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 1100px;
}
.ra-table th {
    text-align: left;
    padding: 12px 14px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--ra-muted);
    border-bottom: 1px solid var(--ra-line);
    background: rgba(2, 6, 23, 0.2);
    white-space: nowrap;
}
body.theme-light .ra-table th { background: #f8fafc; }
.ra-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--ra-line);
    color: var(--ra-text);
    vertical-align: middle;
}
.ra-table td:first-child,
.ra-table th:first-child {
    position: sticky;
    left: 0;
    z-index: 2;
    background: var(--ra-card) !important;
    color: var(--ra-text) !important;
    box-shadow: 4px 0 8px rgba(2, 6, 23, 0.08);
}
.ra-table td:first-child strong,
.ra-table th:first-child strong {
    color: var(--ra-heading) !important;
}
body.theme-light .ra-table td:first-child,
body.theme-light .ra-table th:first-child {
    box-shadow: 4px 0 8px rgba(15, 23, 42, 0.06);
}
.ra-table tr:last-child td { border-bottom: 0; }
.ra-table tr:hover td { background: rgba(37,99,235,0.04); }

.ra-pill {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(148,163,184,0.15);
    color: var(--ra-muted);
}
.ra-cell-icon {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.ra-cell-icon i {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    background: rgba(37,99,235,0.12);
    color: #60a5fa;
}
.ra-empty {
    text-align: center;
    padding: 40px 24px;
    color: var(--ra-muted);
}
.ra-empty i { font-size: 36px; opacity: 0.35; display: block; margin-bottom: 10px; }

.ra-pagination {
    padding: 16px 18px;
    border-top: 1px solid var(--ra-line);
}
.ra-pagination .pagination {
    margin: 0;
    display: flex;
    justify-content: center;
    gap: 4px;
}
.ra-pagination .pagination > li > a,
.ra-pagination .pagination > li > span {
    border-radius: 8px !important;
    border: 1px solid var(--ra-line) !important;
    background: rgba(2, 6, 23, 0.35) !important;
    color: var(--ra-text) !important;
    font-weight: 700;
    font-size: 13px;
    padding: 6px 12px;
}
body.theme-light .ra-pagination .pagination > li > a,
body.theme-light .ra-pagination .pagination > li > span { background: #f8fafc !important; }
.ra-pagination .pagination > .active > a {
    background: linear-gradient(135deg, var(--ra-brand), var(--ra-brand2)) !important;
    border-color: transparent !important;
    color: #fff !important;
}
.ra-pagination .pagination > .disabled > a { opacity: 0.45; }

.ra-total-row td {
    font-weight: 800;
    color: var(--ra-heading);
    background: rgba(16,185,129,0.06) !important;
}
{/literal}
</style>

<div class="ra-page" id="raPage">

    <div class="ra-hero">
        <div>
            <h1><i class="fa fa-line-chart" style="color:#60a5fa;margin-right:8px"></i> {Lang::T('Reports_Analytics')}</h1>
            <p>{Lang::T('Reports_Analytics_subtitle')}</p>
        </div>
        <div class="ra-period-badge">
            <i class="fa fa-calendar"></i>
            {Lang::dateFormat($sd)} – {Lang::dateFormat($ed)}
        </div>
    </div>

    <form method="get" class="ra-filters" id="raFilterForm">
        <input type="hidden" name="_route" value="reports">
        <div class="ra-filter-grid">
            <div class="ra-field">
                <label>{Lang::T('Start Date')}</label>
                <input type="date" class="ra-input" name="sd" value="{$sd|date_format:'%Y-%m-%d'}">
            </div>
            <div class="ra-field">
                <label>{Lang::T('Start time')}</label>
                <input type="time" class="ra-input" name="ts" value="{$ts|substr:0:5}">
            </div>
            <div class="ra-field">
                <label>{Lang::T('End Date')}</label>
                <input type="date" class="ra-input" name="ed" value="{$ed|date_format:'%Y-%m-%d'}">
            </div>
            <div class="ra-field">
                <label>{Lang::T('End Time')}</label>
                <input type="time" class="ra-input" name="te" value="{$te|substr:0:5}">
            </div>
        </div>
        <div class="ra-filter-grid row2">
            <div class="ra-field">
                <label>{Lang::T('Type')}</label>
                <select class="ra-select" name="tp">
                    <option value="">{Lang::T('All_Types')}</option>
                    {foreach $types as $type}
                    <option value="{$type}" {if $tp_sel eq $type}selected{/if}>{$type}</option>
                    {/foreach}
                </select>
            </div>
            <div class="ra-field">
                <label>{Lang::T('Routers')}</label>
                <select class="ra-select" name="rt">
                    <option value="">{Lang::T('All_Routers')}</option>
                    {foreach $routers as $router}
                    <option value="{$router}" {if $rt_sel eq $router}selected{/if}>{$router}</option>
                    {/foreach}
                </select>
            </div>
            <div class="ra-field">
                <label>{Lang::T('Methods')} / {Lang::T('Processed_By')}</label>
                <select class="ra-select" name="mt">
                    <option value="">{Lang::T('All_Methods')}</option>
                    {foreach $methods as $method}
                    <option value="{$method}" {if $mt_sel eq $method}selected{/if}>{$method}</option>
                    {/foreach}
                </select>
            </div>
            <div class="ra-actions">
                <button type="submit" class="ra-btn"><i class="fa fa-bar-chart"></i> {Lang::T('Show_Chart')}</button>
                <a href="{Text::url('reports')}" class="ra-btn ra-btn-outline"><i class="fa fa-refresh"></i> {Lang::T('Reset')}</a>
            </div>
        </div>
    </form>

    <div class="ra-charts" id="raCharts">
        <div class="ra-card">
            <div class="ra-card-head"><h3><i class="fa fa-pie-chart"></i> {Lang::T('Internet_Plans_Distribution')}</h3></div>
            <div class="ra-card-body">
                <div class="ra-chart-wrap"><canvas id="raChartPlan"></canvas></div>
            </div>
        </div>
        <div class="ra-card">
            <div class="ra-card-head"><h3><i class="fa fa-pie-chart"></i> {Lang::T('Payment_Methods_Distribution')}</h3></div>
            <div class="ra-card-body">
                <div class="ra-chart-wrap"><canvas id="raChartMethod"></canvas></div>
            </div>
        </div>
        <div class="ra-card">
            <div class="ra-card-head"><h3><i class="fa fa-line-chart"></i> {Lang::T('Revenue_Trend')} ({Lang::dateFormat($sd)} – {Lang::dateFormat($ed)})</h3></div>
            <div class="ra-card-body">
                <div class="ra-chart-wrap wide"><canvas id="raChartRevenue"></canvas></div>
            </div>
        </div>
    </div>

    <div class="ra-card ra-table-section">
        <div class="ra-table-head">
            <div class="ra-table-head-left">
                <h3><i class="fa fa-list"></i> {Lang::T('Transactions_History')}</h3>
                <span class="ra-count-badge">{$total_transactions} {Lang::T('transactions_count')}</span>
            </div>
            <div class="ra-table-tools">
                <form method="get" id="raSearchForm" style="margin:0;display:flex;gap:10px;align-items:center;">
                    <input type="hidden" name="_route" value="reports">
                    <input type="hidden" name="sd" value="{$sd|date_format:'%Y-%m-%d'}">
                    <input type="hidden" name="ed" value="{$ed|date_format:'%Y-%m-%d'}">
                    <input type="hidden" name="ts" value="{$ts|substr:0:5}">
                    <input type="hidden" name="te" value="{$te|substr:0:5}">
                    {if $tp_sel}<input type="hidden" name="tp" value="{$tp_sel}">{/if}
                    {if $rt_sel}<input type="hidden" name="rt" value="{$rt_sel}">{/if}
                    {if $mt_sel}<input type="hidden" name="mt" value="{$mt_sel}">{/if}
                    <input type="search" class="ra-search" name="q" value="{$q|escape:'html'}" placeholder="{Lang::T('Search_transactions_placeholder')}" id="raSearchInput">
                    <a href="{Text::url('reports/csv&')}{$filter}" class="ra-btn ra-btn-outline"><i class="fa fa-download"></i> {Lang::T('Export_CSV')}</a>
                </form>
            </div>
        </div>
        <div class="ra-table-wrap">
            <table class="ra-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Full Name')}</th>
                        <th>{Lang::T('Address')}</th>
                        <th>{Lang::T('Phone Number')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th>{Lang::T('Plan Price')}</th>
                        <th>{Lang::T('Created On')}</th>
                        <th>{Lang::T('Expires On')}</th>
                        <th>{Lang::T('Method')}</th>
                        <th>{Lang::T('Routers')}</th>
                    </tr>
                </thead>
                <tbody>
                    {if $d|@count gt 0}
                    {foreach $d as $ds}
                    <tr>
                        <td><strong>{$ds['username']|default:''|escape}</strong></td>
                        <td>{if $ds.fullname}{$ds.fullname}{else}—{/if}</td>
                        <td>{if $ds.address}{$ds.address}{else}—{/if}</td>
                        <td>{if $ds.phonenumber}{$ds.phonenumber}{else}—{/if}</td>
                        <td><span class="ra-pill">{$ds.type}</span></td>
                        <td>{$ds.plan_name}</td>
                        <td>{Lang::moneyFormat($ds.price)}</td>
                        <td>{Lang::dateAndTimeFormat($ds.recharged_on,$ds.recharged_time)}</td>
                        <td>{Lang::dateAndTimeFormat($ds.expiration,$ds.time)}</td>
                        <td><span class="ra-cell-icon"><i class="fa fa-credit-card"></i> {$ds.method}</span></td>
                        <td><span class="ra-cell-icon"><i class="fa fa-server"></i> {$ds.routers}</span></td>
                    </tr>
                    {/foreach}
                    <tr class="ra-total-row">
                        <td colspan="6"><strong>{Lang::T('Total')}</strong></td>
                        <td><strong>{Lang::moneyFormat($dr)}</strong></td>
                        <td colspan="4"></td>
                    </tr>
                    {else}
                    <tr>
                        <td colspan="11">
                            <div class="ra-empty">
                                <i class="fa fa-inbox"></i>
                                {Lang::T('No_transactions_found')}
                            </div>
                        </td>
                    </tr>
                    {/if}
                </tbody>
            </table>
        </div>
        <div class="ra-pagination">
            {include file="pagination.tpl"}
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.5.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-autocolors"></script>

{literal}
<script>
(function () {
    var currency = {/literal}"{$currency|escape:'javascript'}"{literal};
    var pieOptions = {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '58%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 12, padding: 14, font: { size: 11, weight: '600' } }
            }
        }
    };

    function makePie(canvasId, url) {
        var el = document.getElementById(canvasId);
        if (!el) return;
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.labels || !data.labels.length) return;
                new Chart(el, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{ data: data.datas, borderWidth: 1 }]
                    },
                    options: pieOptions
                });
            })
            .catch(function () {});
    }

    function makeRevenue(url) {
        var el = document.getElementById('raChartRevenue');
        if (!el) return;
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.labels || !data.labels.length) return;
                new Chart(el, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: currency + ' Revenue',
                            data: data.data,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.12)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointBackgroundColor: '#10b981'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(148,163,184,0.12)' },
                                ticks: { color: '#94a3b8', font: { size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(148,163,184,0.12)' },
                                ticks: { color: '#94a3b8', font: { size: 11 } }
                            }
                        }
                    }
                });
            })
            .catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window['chartjs-plugin-autocolors']) {
            Chart.register(window['chartjs-plugin-autocolors']);
        }
        makePie('raChartPlan', "{/literal}{Text::url('reports/ajax/plan&')}{$filter|escape:'javascript'}{literal}");
        makePie('raChartMethod', "{/literal}{Text::url('reports/ajax/method&')}{$filter|escape:'javascript'}{literal}");
        makeRevenue("{/literal}{Text::url('reports/ajax/revenue&')}{$filter|escape:'javascript'}{literal}");

        var searchInput = document.getElementById('raSearchInput');
        var searchForm = document.getElementById('raSearchForm');
        var searchTimer;
        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () { searchForm.submit(); }, 500);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchForm.submit();
                }
            });
        }
    });
})();
</script>
{/literal}

{include file="sections/footer.tpl"}
