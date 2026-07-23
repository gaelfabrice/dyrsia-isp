{include file="sections/header.tpl"}

<style>
{literal}
.ho-page {
    --ho-bg: transparent;
    --ho-card: rgba(15, 23, 42, 0.94);
    --ho-text: #e2e8f0;
    --ho-heading: #ffffff;
    --ho-muted: #94a3b8;
    --ho-line: rgba(148, 163, 184, 0.18);
    --ho-shadow: 0 14px 40px rgba(0, 0, 0, 0.24);
    --ho-input-bg: rgba(2, 6, 23, 0.55);
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
    color: var(--ho-text);
    margin: 0 -15px;
    padding: 4px 15px 32px;
}
body.theme-light .ho-page {
    --ho-card: #ffffff;
    --ho-text: #334155;
    --ho-heading: #0f172a;
    --ho-muted: #64748b;
    --ho-line: #e2e8f0;
    --ho-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
    --ho-input-bg: #f8fafc;
}
.ho-page * { box-sizing: border-box; }

.ho-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin: 6px 0 22px;
}
.ho-hero h1 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: var(--ho-heading);
    letter-spacing: -0.02em;
}
.ho-hero p { margin: 4px 0 0; color: var(--ho-muted); font-size: 14px; }
.ho-hero-actions { display: flex; flex-wrap: wrap; gap: 8px; }

.ho-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid var(--ho-line);
    background: var(--ho-input-bg);
    color: var(--ho-text);
    text-decoration: none !important;
    cursor: pointer;
}
.ho-btn-primary { background: #2563eb; border-color: #2563eb; color: #fff !important; }
.ho-btn-danger { background: #dc2626; border-color: #dc2626; color: #fff !important; }

.ho-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.ho-stat {
    background: var(--ho-card);
    border: 1px solid var(--ho-line);
    border-radius: 16px;
    padding: 16px 18px;
    box-shadow: var(--ho-shadow);
    border-left: 4px solid var(--ho-accent, #2563eb);
}
.ho-stat-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.ho-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    background: rgba(255, 255, 255, 0.06);
}
body.theme-light .ho-stat-icon { background: #f1f5f9; }
.ho-stat-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ho-muted);
}
.ho-stat-value {
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    color: var(--ho-heading);
}

.ho-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.ho-card {
    background: var(--ho-card);
    border: 1px solid var(--ho-line);
    border-radius: 18px;
    box-shadow: var(--ho-shadow);
    overflow: hidden;
}
.ho-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--ho-line);
}
.ho-card-head h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--ho-heading);
    display: flex;
    align-items: center;
    gap: 8px;
}
.ho-card-body { padding: 16px 18px 18px; }
.ho-chart { position: relative; height: 220px; }
.ho-chart-sm { height: 200px; }

.ho-voucher-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
}
.ho-voucher-center strong {
    display: block;
    font-size: 24px;
    font-weight: 800;
    color: var(--ho-heading);
}
.ho-voucher-center small {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--ho-muted);
}
.ho-voucher-legend {
    display: flex;
    justify-content: space-around;
    gap: 8px;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid var(--ho-line);
    flex-wrap: wrap;
}
.ho-voucher-legend span { font-size: 13px; font-weight: 700; }
.ho-voucher-legend small {
    display: block;
    font-size: 10px;
    text-transform: uppercase;
    color: var(--ho-muted);
    margin-bottom: 2px;
}

.ho-table-wrap {
    background: var(--ho-card);
    border: 1px solid var(--ho-line);
    border-radius: 18px;
    box-shadow: var(--ho-shadow);
    overflow: visible;
}
.ho-table-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--ho-line);
}
.ho-table-head h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--ho-heading);
}
.ho-table-body { padding: 0 12px 12px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.ho-table-body table.ho-history-table { min-width: 960px; width: 100%; }

.ho-page table.ho-history-table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    color: var(--ho-text);
    background: transparent;
}
.ho-page table.ho-history-table thead th {
    background: rgba(255, 255, 255, 0.04);
    color: var(--ho-muted);
    border-bottom: 1px solid var(--ho-line);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 12px 10px;
    white-space: nowrap;
    text-align: left;
}
body.theme-light .ho-page table.ho-history-table thead th {
    background: #f8fafc;
    color: #475569;
}
.ho-page table.ho-history-table tbody td {
    border-bottom: 1px solid var(--ho-line);
    padding: 10px;
    vertical-align: middle;
    font-size: 13px;
    color: var(--ho-text);
    background: transparent !important;
    white-space: normal;
    word-break: break-word;
}
.ho-page table.ho-history-table thead th.ho-col-ref,
.ho-page table.ho-history-table tbody td.ho-td-ref {
    min-width: 160px;
    background: transparent !important;
}
.ho-page table.ho-history-table thead th.ho-col-check,
.ho-page table.ho-history-table tbody td.ho-col-check {
    width: 42px;
    max-width: 42px;
    text-align: center;
    background: transparent !important;
}
.ho-page table.ho-history-table tbody td.ho-td-ref {
    white-space: nowrap;
    color: #f87171 !important;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    font-weight: 700;
}
body.theme-light .ho-page table.ho-history-table tbody td.ho-td-ref {
    color: #dc2626 !important;
}
.ho-page table.ho-history-table tbody tr:nth-child(odd) td {
    background: rgba(255, 255, 255, 0.02) !important;
}
body.theme-light .ho-page table.ho-history-table tbody tr:nth-child(odd) td {
    background: #f8fafc !important;
}
.ho-page table.ho-history-table tbody tr:hover td {
    background: rgba(37, 99, 235, 0.08) !important;
}

.ho-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
}
.ho-badge-paid { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.ho-badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.ho-badge-failed { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.ho-fail-reason { font-size: 11px; color: #94a3b8; margin-top: 4px; line-height: 1.35; max-width: 220px; }
.ho-badge-cancelled { background: rgba(236, 72, 153, 0.15); color: #ec4899; }

.ho-alert {
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 16px;
    border: 1px solid var(--ho-line);
}
.ho-alert-success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.ho-alert-danger { background: rgba(239, 68, 68, 0.12); color: #ef4444; }

.ho-page .dropdown-menu {
    background: var(--ho-card);
    border: 1px solid var(--ho-line);
    border-radius: 12px;
    box-shadow: var(--ho-shadow);
    padding: 6px 0;
}
.ho-page .dropdown-menu > li > a {
    color: var(--ho-text);
    padding: 8px 16px;
}
.ho-page .dropdown-menu > li > a:hover,
.ho-page .dropdown-menu > li > a:focus {
    background: rgba(37, 99, 235, 0.12);
    color: var(--ho-heading);
}
.ho-page code {
    background: var(--ho-input-bg);
    color: #93c5fd;
    border: 1px solid var(--ho-line);
    border-radius: 6px;
    padding: 2px 6px;
    font-size: 12px;
}
body.theme-light .ho-page code { color: #2563eb; }
{/literal}
</style>

<div class="ho-page">
    {if isset($message)}
    <div class="ho-alert ho-alert-{if $notify_t == 's'}success{else}danger{/if}">
        {$message}
    </div>
    {/if}

    <div class="ho-hero">
        <div>
            <h1><i class="fa fa-wifi" style="color:#60a5fa;margin-right:8px"></i> {Lang::T('Advanced Hotspot System')}</h1>
            <p>Paiements, ventes et historique des transactions hotspot</p>
        </div>
        <div class="ho-hero-actions">
            <a href="{Text::url('plugin/hotspot_overview')}{Text::isQA()}refresh=1" class="ho-btn ho-btn-primary">
                <i class="fa fa-refresh"></i> {Lang::T('Refresh')}
            </a>
        </div>
    </div>

    <div class="ho-stats">
        <div class="ho-stat" style="--ho-accent:#10b981">
            <div class="ho-stat-top">
                <div class="ho-stat-icon" style="color:#10b981"><i class="fa fa-check-circle"></i></div>
                <div class="ho-stat-label">{Lang::T('Successful')}</div>
            </div>
            <div class="ho-stat-value">{$successfulPayments|default:0}</div>
        </div>
        <div class="ho-stat" style="--ho-accent:#ef4444">
            <div class="ho-stat-top">
                <div class="ho-stat-icon" style="color:#ef4444"><i class="fa fa-times-circle"></i></div>
                <div class="ho-stat-label">{Lang::T('Failed')}</div>
            </div>
            <div class="ho-stat-value">{$failedPayments|default:0}</div>
        </div>
        <div class="ho-stat" style="--ho-accent:#f59e0b">
            <div class="ho-stat-top">
                <div class="ho-stat-icon" style="color:#f59e0b"><i class="fa fa-clock-o"></i></div>
                <div class="ho-stat-label">{Lang::T('Pending')}</div>
            </div>
            <div class="ho-stat-value">{$pendingPayments|default:0}</div>
        </div>
        <div class="ho-stat" style="--ho-accent:#ec4899">
            <div class="ho-stat-top">
                <div class="ho-stat-icon" style="color:#ec4899"><i class="fa fa-minus-circle"></i></div>
                <div class="ho-stat-label">{Lang::T('Cancelled')}</div>
            </div>
            <div class="ho-stat-value">{$cancelledPayments|default:0}</div>
        </div>
    </div>

    <div class="ho-grid">
        <div class="ho-card">
            <div class="ho-card-head">
                <h3><i class="fa fa-bar-chart" style="color:#fbbf24"></i> {Lang::T('Daily Sales')}</h3>
            </div>
            <div class="ho-card-body"><div class="ho-chart"><canvas id="dailySalesChart"></canvas></div></div>
        </div>
        <div class="ho-card">
            <div class="ho-card-head">
                <h3><i class="fa fa-line-chart" style="color:#10b981"></i> {Lang::T('Weekly_Sales')}</h3>
            </div>
            <div class="ho-card-body"><div class="ho-chart"><canvas id="weekly-sales-chart"></canvas></div></div>
        </div>
        <div class="ho-card">
            <div class="ho-card-head">
                <h3><i class="fa fa-pie-chart" style="color:#3b82f6"></i> {Lang::T('Monthly Sales')}</h3>
            </div>
            <div class="ho-card-body"><div class="ho-chart"><canvas id="monthlySalesChart"></canvas></div></div>
        </div>
        <div class="ho-card">
            <div class="ho-card-head">
                <h3><i class="fa fa-ticket" style="color:#ec4899"></i> {Lang::T('Hotspot Vouchers Overview')}</h3>
            </div>
            <div class="ho-card-body">
                <div class="ho-chart-sm" style="position:relative">
                    <div class="ho-voucher-center">
                        <strong>{$total_vouchers|default:0}</strong>
                        <small>Vouchers</small>
                    </div>
                    <canvas id="voucherOverviewChart"></canvas>
                </div>
                <div class="ho-voucher-legend">
                    <div><small>Available</small><span style="color:#10b981">{$available_vouchers|default:0}</span></div>
                    <div><small>Used</small><span style="color:#f59e0b">{$used_vouchers|default:0}</span></div>
                    <div><small>Expired</small><span style="color:#ef4444">{$expired_vouchers|default:0}</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="ho-table-wrap">
        <div class="ho-table-head">
            <h3><i class="fa fa-database" style="color:#ec4899;margin-right:6px"></i> {Lang::T('Transaction History')}</h3>
            <div class="ho-hero-actions">
                <div class="btn-group">
                    <button type="button" class="ho-btn dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-filter"></i> {Lang::T('Filter Status')} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" id="statusFilterMenu">
                        <li><a href="#" data-status="">All</a></li>
                        <li><a href="#" data-status="paid">Success</a></li>
                        <li><a href="#" data-status="pending">Pending</a></li>
                        <li><a href="#" data-status="failed">Failed</a></li>
                        <li><a href="#" data-status="cancelled">Cancelled</a></li>
                    </ul>
                </div>
                {if $can_delete_hotspot_history}
                <button type="button" id="deleteSelected" class="ho-btn ho-btn-danger">
                    <i class="fa fa-trash"></i> {Lang::T('Delete Selected')}
                </button>
                <button type="button" id="clearAllHistory" class="ho-btn ho-btn-danger">
                    <i class="fa fa-eraser"></i> Vider l'historique
                </button>
                {/if}
            </div>
        </div>
        <div class="ho-table-body">
            <table id="historyTable" class="ho-history-table">
                <thead>
                    <tr>
                        {if $can_delete_hotspot_history}<th class="ho-col-check"><input type="checkbox" id="selectAll"></th>{/if}
                        <th class="ho-col-ref">{Lang::T('Ref')}</th>
                        <th>{Lang::T('Router')}</th>
                        <th>{Lang::T('Plan')}</th>
                        <th>{Lang::T('Voucher')}</th>
                        <th>{Lang::T('Amount')}</th>
                        <th>{Lang::T('Phone')}</th>
                        <th>{Lang::T('MAC')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Gateway')}</th>
                        <th>{Lang::T('Date')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $payments as $payment}
                    <tr data-status="{$payment.transaction_status|escape}">
                        {if $can_delete_hotspot_history}<td class="ho-col-check"><input type="checkbox" class="row-check" value="{$payment.id}"></td>{/if}
                        <td class="ho-td-ref">{$payment.transaction_ref|escape}</td>
                        <td>{$payment.router_name|escape}</td>
                        <td>{$payment.plan_name|escape}</td>
                        <td><code>{$payment.voucher_code|escape}</code></td>
                        <td>{$payment.amount|escape}</td>
                        <td>{$payment.phone_number|escape}</td>
                        <td><small>{$payment.mac_address|escape}</small></td>
                        <td>
                            {if $payment.transaction_status == 'paid'}
                            <span class="ho-badge ho-badge-paid">paid</span>
                            {elseif $payment.transaction_status == 'pending'}
                            <span class="ho-badge ho-badge-pending">pending</span>
                            {elseif $payment.transaction_status == 'cancelled'}
                            <span class="ho-badge ho-badge-cancelled">cancelled</span>
                            {else}
                            <span class="ho-badge ho-badge-failed">{$payment.transaction_status|escape}</span>
                            {if $payment.transaction_status == 'failed'}
                            {assign var="failReason" value={hotspot_payment_failure_reason payment=$payment}}
                            {if $failReason}
                            <div class="ho-fail-reason" title="{$failReason|escape}">{$failReason|escape|truncate:80}</div>
                            {/if}
                            {/if}
                            {/if}
                        </td>
                        <td>{$payment.payment_gateway|escape}</td>
                        <td>{if $payment.payment_date}{$payment.payment_date|escape}{else}{$payment.created_date|escape}{/if}</td>
                    </tr>
                    {foreachelse}
                    <tr><td colspan="{if $can_delete_hotspot_history}11{else}10{/if}" class="text-center" style="padding:24px;color:var(--ho-muted)">{Lang::T('No Data')}</td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    var chartText = '#94a3b8';
    var chartGrid = 'rgba(148,163,184,0.15)';
    if (document.body.classList.contains('theme-light')) {
        chartText = '#64748b';
        chartGrid = 'rgba(100,116,139,0.2)';
    }

    var monthlyData = {$monthlyData|default:'{}'};
    var dailySalesData = {$dailySalesData|default:'{}'};
    var weeklyChartData = {$chartData|default:'{"labels":[],"data":[]}'};

    function chartDefaults() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: chartText } } },
            scales: {
                x: { ticks: { color: chartText }, grid: { color: chartGrid } },
                y: { ticks: { color: chartText }, grid: { color: chartGrid }, beginAtZero: true }
            }
        };
    }

    var dailyRows = [];
    for (var d in dailySalesData) {
        if (Object.prototype.hasOwnProperty.call(dailySalesData, d)) {
            dailyRows.push({ date: d, total: dailySalesData[d] });
        }
    }
    dailyRows.sort(function (a, b) { return new Date(a.date) - new Date(b.date); });
    new Chart(document.getElementById('dailySalesChart'), {
        type: 'bar',
        data: {
            labels: dailyRows.map(function (r) { return r.date; }),
            datasets: [{
                label: '{Lang::T('Daily Sales')}',
                data: dailyRows.map(function (r) { return r.total; }),
                backgroundColor: 'rgba(37, 99, 235, 0.75)',
                borderRadius: 8
            }]
        },
        options: chartDefaults()
    });

    new Chart(document.getElementById('weekly-sales-chart'), {
        type: 'bar',
        data: {
            labels: weeklyChartData.labels || [],
            datasets: [{
                label: '{Lang::T('Weekly_Sales')}',
                data: weeklyChartData.data || [],
                backgroundColor: 'rgba(16, 185, 129, 0.75)',
                borderRadius: 8
            }]
        },
        options: chartDefaults()
    });

    new Chart(document.getElementById('monthlySalesChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(monthlyData),
            datasets: [{
                data: Object.values(monthlyData),
                backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#06b6d4']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: chartText, boxWidth: 12 } } }
        }
    });

    new Chart(document.getElementById('voucherOverviewChart'), {
        type: 'doughnut',
        data: {
            labels: ['Available', 'Used', 'Expired'],
            datasets: [{
                data: [
                    {$available_vouchers|default:0},
                    {$used_vouchers|default:0},
                    {$expired_vouchers|default:0}
                ],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: { legend: { display: false } }
        }
    });

    jQuery(function ($) {
        var selectedIds = [];

        function syncSelectedIds() {
            selectedIds = [];
            $('#historyTable tbody tr:visible .row-check:checked').each(function () {
                selectedIds.push($(this).val());
            });
        }

        {if $can_delete_hotspot_history}
        $('#selectAll').on('click', function () {
            $('#historyTable tbody tr:visible .row-check').prop('checked', this.checked);
            syncSelectedIds();
        });

        $('#historyTable tbody').on('change', '.row-check', syncSelectedIds);
        {/if}

        $('#statusFilterMenu a').on('click', function (e) {
            e.preventDefault();
            var status = String($(this).data('status') || '');
            $('#historyTable tbody tr').each(function () {
                var rowStatus = String($(this).data('status') || '');
                $(this).toggle(!status || rowStatus === status);
            });
            syncSelectedIds();
        });

        {if $can_delete_hotspot_history}
        $('#deleteSelected').on('click', function () {
            if (selectedIds.length === 0) {
                alert('{Lang::T('No Data')}');
                return;
            }
            if (!confirm('{Lang::T('Delete')} ?')) return;
            $.post('{Text::url('plugin/hotspot_delete_transactions')}', { ids: selectedIds }, function (res) {
                if (String(res).trim() === 'OK') {
                    location.reload();
                } else {
                    alert(res);
                }
            });
        });

        $('#clearAllHistory').on('click', function () {
            if (!confirm('Supprimer toutes les transactions ? Cette action est irréversible.')) return;
            $.post('{Text::url('plugin/hotspot_delete_transactions')}', { all: 1 }, function (res) {
                if (String(res).trim() === 'OK') {
                    location.reload();
                } else {
                    alert(res);
                }
            });
        });
        {/if}

        var hoSnapshot = {
            pending: {$pendingPayments|default:0},
            paid: {$successfulPayments|default:0},
            latestId: {$hotspot_latest_payment_id|default:0}
        };
        setInterval(function () {
            if (document.hidden) return;
            $.getJSON('{Text::url('plugin/hotspot_overview_ping')}', function (data) {
                if (!data) return;
                if (data.pending !== hoSnapshot.pending
                    || data.paid !== hoSnapshot.paid
                    || data.latest_id !== hoSnapshot.latestId) {
                    window.location.reload();
                }
            });
        }, 30000);
    });
})();
</script>

{include file="sections/footer.tpl"}
