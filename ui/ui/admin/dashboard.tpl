{include file="sections/header.tpl"}

<div class="wz-cc">
    <div class="wz-cc-top">
        <div>
            <h1>{Lang::T('Command Center')}</h1>
            <p>{Lang::T('A clear, fast and ergonomic control room for customers, services, network and business activity.')}</p>
            <div class="wz-cc-actions">
                <a href="{Text::url('customers/add')}" class="btn btn-primary btn-sm"><i class="fa fa-user-plus"></i> {Lang::T('New Customer')}</a>
                <a href="{Text::url('plan/recharge')}" class="btn btn-default btn-sm"><i class="fa fa-bolt"></i> {Lang::T('Recharge')}</a>
                {if in_array($_admin['user_type'], ['Admin', 'SuperAdmin'])}
                <a href="{Text::url('referral')}" class="btn btn-default btn-sm"><i class="fa fa-users"></i> Parrainage</a>
                {/if}
                {if !$admin_subscription || $admin_subscription->status neq 'trial'}
                <a href="{Text::url('routers/add')}" class="btn btn-default btn-sm"><i class="fa fa-server"></i> {Lang::T('Add Router')}</a>
                {/if}
            </div>
        </div>
        <div class="wz-cc-date">
            <i class="fa fa-calendar"></i>
            <span>{$start_date|date_format:"%d %b %Y"} — {$end_date|date_format:"%d %b %Y"}</span>
        </div>
    </div>

    {if $demo_showcase_active|default:false}
    <div class="alert alert-info" style="border-radius:12px;margin-bottom:16px">
        <strong><i class="fa fa-eye"></i> Mode démonstration</strong> — Données fictives pour la présentation.
    </div>
    {/if}

    {if $cron_stale|default:false}
    <div class="wz-cc-warn">
        <i class="fa fa-exclamation-triangle"></i>
        {Lang::T('Cron has not run for over 1 hour. Please check your setup.')}
    </div>
    {/if}

    {if $admin_subscription}
        {if $admin_subscription->status eq 'trial'}
        <div class="wz-cc-demo-bar" id="wzDemoBar"
            data-expires="{$admin_subscription_expires_at|escape:'html'}"
            data-expires-ts="{$admin_subscription_expires_ts|default:0}">
            <div class="wz-cc-demo-bar-inner">
                <div class="wz-cc-demo-bar-left">
                    <span class="wz-cc-demo-icon" aria-hidden="true"><i class="fa fa-gift"></i></span>
                    <div class="wz-cc-demo-text">
                        <strong class="wz-cc-demo-title">{Lang::T('Demo_Mode')}</strong>
                        <span class="wz-cc-demo-countdown" id="wzDemoCountdown" aria-live="polite">—</span>
                    </div>
                </div>
                <a href="{Text::url('admin/subscription')}" class="wz-cc-demo-cta">
                    <i class="fa fa-credit-card"></i> {Lang::T('Pay_Subscribe')}
                </a>
            </div>
        </div>
        {else}
        <div class="wz-cc-sub-bar {if $admin_subscription->status eq 'active'}is-active{else}is-expired{/if}">
            <div>
                <strong>{if $admin_subscription->status eq 'active'}{Lang::T('Active_subscription')}{else}{Lang::T('Subscription')}{/if}</strong>
                {if $admin_subscription->status eq 'active' && $admin_subscription->subscription_end}
                — {Lang::T('Valid_until')} {$admin_subscription->subscription_end|date_format:"%d/%m/%Y"}
                {/if}
            </div>
            <a href="{Text::url('admin/subscription')}" class="btn btn-primary btn-sm"><i class="fa fa-credit-card"></i> {Lang::T('Pay_Subscribe')}</a>
        </div>
        {/if}
    {/if}

    <div class="wz-kpi-grid">
        <a href="{Text::url('settings/users')}" class="wz-kpi-card wz-kpi-link">
            <div class="wz-kpi-head"><span class="wz-kpi-label">{Lang::T('Administrators')}</span><div class="wz-kpi-icon"><i class="fa fa-user-secret"></i></div></div>
            <div class="wz-kpi-value">{$total_admin|default:0}</div>
            <div class="wz-kpi-sub">{Lang::T('Total administrators')}</div>
        </a>
        <a href="{Text::url('plan/list')}" class="wz-kpi-card wz-kpi-link">
            <div class="wz-kpi-head"><span class="wz-kpi-label">{Lang::T('Active Customers')}</span><div class="wz-kpi-icon"><i class="fa fa-user-check"></i></div></div>
            <div class="wz-kpi-value">{$active_customers|default:0}</div>
            <div class="wz-kpi-sub">
                {if ($customer_growth|default:0) >= 0}
                <span class="wz-trend-up"><i class="fa fa-arrow-up"></i> +{$customer_growth|default:0} vs last month</span>
                {else}
                <span class="wz-trend-down"><i class="fa fa-arrow-down"></i> {$customer_growth|default:0} vs last month</span>
                {/if}
            </div>
        </a>
        <a href="{Text::url('finance')}" class="wz-kpi-card wz-kpi-link">
            <div class="wz-kpi-head"><span class="wz-kpi-label">{Lang::T('Sales Today')}</span><div class="wz-kpi-icon"><i class="fa fa-shopping-cart"></i></div></div>
            <div class="wz-kpi-value">{$currency|default:'XAF'} {$sales_today|default:0|number_format:0}</div>
            <div class="wz-kpi-sub">{Lang::T('Daily revenue')}</div>
        </a>
        <a href="{Text::url('routers')}" class="wz-kpi-card wz-kpi-link">
            <div class="wz-kpi-head"><span class="wz-kpi-label">{Lang::T('Network Status')}</span><div class="wz-kpi-icon"><i class="fa fa-server"></i></div></div>
            <div class="wz-kpi-value">{$offline_routers|default:0}</div>
            <div class="wz-kpi-sub">
                {Lang::T('Routers offline')} ·
                {if ($offline_routers|default:0) == 0}{Lang::T('All operational')}{else}{Lang::T('Check connection')}{/if}
            </div>
        </a>
    </div>

    <div class="wz-cc-grid-2">
        <div class="wz-cc-card">
            <div class="wz-cc-card-title"><i class="fa fa-wifi"></i> Services Status</div>
            <div class="wz-service-row">
                <span><i class="fa fa-wifi"></i> Hotspot</span>
                <span>
                    <span class="wz-badge-active">Active: {$hotspot_active|default:0}</span>
                    <span class="wz-badge-expired">Expired: {$hotspot_expired|default:0}</span>
                </span>
            </div>
            <div class="wz-service-row">
                <span><i class="fa fa-sitemap"></i> PPPoE</span>
                <span>
                    <span class="wz-badge-active">Active: {$pppoe_active|default:0}</span>
                    <span class="wz-badge-expired">Expired: {$pppoe_expired|default:0}</span>
                </span>
            </div>
            <div class="wz-service-row">
                <span><i class="fa fa-bar-chart"></i> Total Clients</span>
                <span>
                    <span class="wz-badge-active">Active: {$total_active|default:0}</span>
                    <span class="wz-badge-expired">Expired: {$total_expired|default:0}</span>
                </span>
            </div>
            <div class="wz-progress"><div class="wz-progress-fill" style="width:{$network_usage|default:0}%"></div></div>
            <div class="wz-kpi-sub" style="margin-top:8px">{Lang::T('Network usage')}: {$network_usage|default:0}% {Lang::T('of capacity')}</div>
        </div>

        {if $_c['disable_voucher'] != 'yes'}
        <div class="wz-cc-card">
            <div class="wz-cc-card-title"><i class="fa fa-ticket"></i> {Lang::T('Vouchers')} Stock</div>
            {if $voucher_stock|@count > 0}
            <table class="wz-voucher-table">
                <thead><tr><th>{Lang::T('Package Name')}</th><th>Unused</th><th>Used</th><th>Total</th></tr></thead>
                <tbody>
                    {foreach $voucher_stock as $v}
                    <tr>
                        <td>{$v.package|escape}</td>
                        <td>{$v.unused}</td>
                        <td>{$v.used}</td>
                        <td>{$v.unused + $v.used}</td>
                    </tr>
                    {/foreach}
                    <tr class="wz-voucher-total">
                        <td><strong>{Lang::T('Total Stock')}</strong></td>
                        <td><strong>{$total_unused|default:0}</strong></td>
                        <td><strong>{$total_used|default:0}</strong></td>
                        <td><strong>{($total_unused|default:0)+($total_used|default:0)}</strong></td>
                    </tr>
                </tbody>
            </table>
            {else}
            <p class="wz-cc-empty">{Lang::T('No vouchers available')}</p>
            {/if}
        </div>
        {/if}
    </div>

    <div class="wz-cc-grid-2">
        <div class="wz-cc-card wz-du-card">
            <div class="wz-cc-card-title">
                <span><i class="fa fa-area-chart"></i> {Lang::T('Data Usage')}</span>
                <a href="{Text::url('reports/data-usage')}" class="wz-du-more">{Lang::T('View report')} <i class="fa fa-arrow-right"></i></a>
            </div>
            <div class="wz-du-kpis">
                <div class="wz-du-kpi">
                    <span class="wz-du-kpi-label"><i class="fa fa-download"></i> {Lang::T('Download')}</span>
                    <strong>{$data_usage.download_mb|default:0} MB</strong>
                </div>
                <div class="wz-du-kpi">
                    <span class="wz-du-kpi-label"><i class="fa fa-upload"></i> {Lang::T('Upload')}</span>
                    <strong>{$data_usage.upload_mb|default:0} MB</strong>
                </div>
                <div class="wz-du-kpi">
                    <span class="wz-du-kpi-label"><i class="fa fa-exchange"></i> {Lang::T('Total')}</span>
                    <strong>{$data_usage.combined_mb|default:0} MB</strong>
                </div>
            </div>
            <div class="wz-du-chart"><canvas id="dataUsageChart"></canvas></div>
            <div class="wz-kpi-sub" style="margin-top:8px;text-align:center">7 derniers jours · Hotspot &amp; PPPoE</div>
        </div>

        <div class="wz-cc-card">
            <div class="wz-cc-card-title"><i class="fa fa-sitemap"></i> {Lang::T('Network')}</div>
            <div class="wz-service-row">{Lang::T('Primary Router')}: <span class="wz-badge-active">{$network_summary.primary_router|default:'—'|escape}</span></div>
            <div class="wz-service-row">{Lang::T('Routers')}: <span class="wz-badge-active">{$network_summary.routers_label|default:'0 / 0 online'|escape}</span></div>
            <div class="wz-service-row">DNS: <span class="wz-badge-active">{$network_summary.dns_servers|default:'8.8.8.8 / 1.1.1.1'|escape}</span></div>
            <div class="wz-service-row">{Lang::T('Gateway Status')}:
                {if $network_summary.gateway_online|default:false}
                <span class="wz-badge-success"><i class="fa fa-circle"></i> Online</span>
                {else}
                <span class="wz-badge-warning"><i class="fa fa-circle"></i> {Lang::T('Check connection')}</span>
                {/if}
            </div>
        </div>
    </div>

    <div class="wz-cc-grid-2">
        <div class="wz-cc-card">
            <div class="wz-cc-card-title">
                <span><i class="fa fa-money"></i> {Lang::T('Recent Payments')}</span>
                <a href="{Text::url('reports')}" class="wz-du-more">{Lang::T('View_report')} <i class="fa fa-arrow-right"></i></a>
            </div>
            {if $recent_payments|@count > 0}
            <table class="wz-voucher-table wz-payments-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Customer')}</th>
                        <th>{Lang::T('Plan')}</th>
                        <th>{Lang::T('Payment')}</th>
                        <th>{Lang::T('Amount')}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $recent_payments as $pay}
                    <tr>
                        <td>
                            <strong>{$pay.username|escape}</strong>
                            <div class="wz-kpi-sub">{$pay.time_ago|escape}</div>
                        </td>
                        <td>{$pay.plan_name|escape}</td>
                        <td><span class="wz-badge-active">{$pay.method|escape}</span></td>
                        <td><strong>{$currency|default:'XAF'} {$pay.price|number_format:0}</strong></td>
                        <td class="text-right">
                            {if $pay.url && $pay.url neq '#'}
                            <a href="{$pay.url|escape}" class="btn btn-default btn-xs"><i class="fa fa-file-text-o"></i></a>
                            {/if}
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
            {else}
            <p class="wz-cc-empty">{Lang::T('No payments recorded yet')}</p>
            {/if}
        </div>

        {if $_admin['user_type'] eq 'SuperAdmin'}
        <div class="wz-cc-card">
            <div class="wz-cc-card-title"><i class="fa fa-bell"></i> {Lang::T('Notification')}</div>
            <div class="wz-service-row">{Lang::T('Email Notification')}: <span class="wz-badge-{if $notification_status.email|default:false}active{else}warning{/if}">{if $notification_status.email|default:false}{Lang::T('Enabled')}{else}{Lang::T('Not configured')}{/if}</span></div>
            <div class="wz-service-row">{Lang::T('Sms notification')}: <span class="wz-badge-{if $notification_status.sms|default:false}active{else}warning{/if}">{if $notification_status.sms|default:false}{Lang::T('Enabled')}{else}{Lang::T('Not configured')}{/if}</span></div>
            <div class="wz-service-row">{Lang::T('Telegram notification')}: <span class="wz-badge-{if $notification_status.telegram|default:false}active{else}expired{/if}">{if $notification_status.telegram|default:false}{Lang::T('Enabled')}{else}{Lang::T('Disabled')}{/if}</span></div>
            <a href="{Text::url('settings/notifications')}" class="wz-cc-btn wz-cc-btn-muted" style="width:100%;margin-top:12px;text-align:center;display:block;text-decoration:none"><i class="fa fa-cog"></i> {Lang::T('Configure Alerts')}</a>
        </div>
        {/if}

        <div class="wz-cc-card">
            <div class="wz-cc-card-title"><i class="fa fa-sitemap"></i> {Lang::T('ISP Reseller Plan')}</div>
            <div class="wz-service-row">{Lang::T('Reseller Plan')}: <span class="wz-badge-active">{$reseller_plan|default:'Standard'|escape}</span></div>
            <div class="wz-service-row">{Lang::T('Commission Rate')}: <span class="wz-badge-active">{$commission_rate|default:10|string_format:"%.0f"}%</span></div>
            <div class="wz-service-row">{Lang::T('Active Resellers')}: <span class="wz-badge-active">{$active_resellers|default:0}</span></div>
            <div class="wz-service-row">{Lang::T('Total Commission')}: <span class="wz-badge-active">{$currency|default:'XAF'} {$total_commission|default:0|number_format:0}</span></div>
        </div>
    </div>

    <div class="wz-cc-grid-2">
        <div class="wz-cc-card">
            <div class="wz-cc-card-title"><i class="fa fa-area-chart"></i> Network Traffic (7 jours)</div>
            <div class="wz-chart-box"><canvas id="trafficChart"></canvas></div>
        </div>
        <div class="wz-cc-card">
            <div class="wz-cc-card-title"><i class="fa fa-pie-chart"></i> Service Distribution</div>
            <div class="wz-chart-box"><canvas id="serviceChart"></canvas></div>
        </div>
    </div>

    {if $_admin['user_type'] eq 'SuperAdmin'}
    <div class="wz-cc-card wz-visitor-card">
        <div class="wz-cc-card-title">
            <span><i class="fa fa-globe"></i> {Lang::T('Visitor Logs')}</span>
            <span class="wz-visitor-stats">
                {Lang::T('Visitors today')}: <strong>{$visitor_stats.today|default:0}</strong>
                · {Lang::T('Humans')}: <span class="wz-badge-active">{$visitor_stats.humans_today|default:0}</span>
                · {Lang::T('Bots')}: <span class="wz-badge-expired">{$visitor_stats.bots_today|default:0}</span>
            </span>
        </div>
        {if $visitor_logs|@count > 0}
        <div class="wz-visitor-table-wrap">
            <table class="wz-voucher-table wz-visitor-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Time')}</th>
                        <th>IP</th>
                        <th>{Lang::T('Country')}</th>
                        <th>{Lang::T('City')}</th>
                        <th>{Lang::T('Visited link')}</th>
                        <th>{Lang::T('Type')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $visitor_logs as $visit}
                    <tr>
                        <td><span class="wz-kpi-sub">{$visit.time_ago|escape}</span></td>
                        <td><code>{$visit.ip|escape}</code></td>
                        <td>{$visit.country|default:'—'|escape}</td>
                        <td>{$visit.city|default:'—'|escape}</td>
                        <td class="wz-visitor-path" title="{$visit.visited_path|escape}">{$visit.visited_route|escape}</td>
                        <td>
                            {if $visit.visitor_type eq 'bot'}
                            <span class="wz-badge-expired">{Lang::T('Bot')}</span>
                            {else}
                            <span class="wz-badge-active">{Lang::T('Visitor')}</span>
                            {/if}
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <div class="wz-pagination">
            {if $visitor_current_page > 1}<a class="wz-page-btn" href="{$dashboard_visitor_base_url}&visitor_page={$visitor_prev_page}&log_page={$dashboard_log_page|default:1}"><i class="fa fa-chevron-left"></i> Prev</a>{/if}
            {foreach $visitor_pagination_pages as $page}
            <a class="wz-page-btn {if $page.active}active{/if}" href="{$dashboard_visitor_base_url}&visitor_page={$page.num}&log_page={$dashboard_log_page|default:1}">{$page.num}</a>
            {/foreach}
            {if $visitor_current_page < $visitor_total_pages}<a class="wz-page-btn" href="{$dashboard_visitor_base_url}&visitor_page={$visitor_next_page}&log_page={$dashboard_log_page|default:1}">Next <i class="fa fa-chevron-right"></i></a>{/if}
        </div>
        <div class="wz-log-meta">
            {$visitor_total_entries|default:0} entries · Page {$visitor_current_page|default:1} / {$visitor_total_pages|default:1}
        </div>
        {else}
        <p class="wz-cc-empty">{Lang::T('No visitor logs yet')}</p>
        {/if}
    </div>
    {/if}

    <div class="wz-cc-card">
        <div class="wz-cc-card-title"><i class="fa fa-history"></i> Activity Log</div>
        {if $activity_logs|@count > 0}
            {foreach $activity_logs as $log}
            <div class="wz-timeline-item">
                <div class="wz-timeline-time"><i class="fa fa-clock-o"></i> {$log.time_ago|escape}</div>
                <div class="wz-timeline-icon">
                    <i class="fa {if $log.type eq 'login'}fa-sign-in{elseif $log.type eq 'logout'}fa-sign-out{elseif $log.type eq 'error'}fa-exclamation-triangle{else}fa-info{/if}"></i>
                </div>
                <div class="wz-timeline-text">
                    <div class="wz-log-line">
                        <strong class="wz-log-user">{$log.username|escape}</strong>
                        <span class="wz-log-message" title="{$log.message|escape}">{$log.message|escape}</span>
                    </div>
                    {if $log.sid}<span class="wz-log-ip-badge">{$log.sid|escape}</span>{/if}
                </div>
            </div>
            {/foreach}
            <div class="wz-pagination">
                {if $current_page > 1}<a class="wz-page-btn" href="{$dashboard_log_base_url}&log_page={$prev_page}&visitor_page={$dashboard_visitor_page|default:1}"><i class="fa fa-chevron-left"></i> Prev</a>{/if}
                {foreach $pagination_pages as $page}
                <a class="wz-page-btn {if $page.active}active{/if}" href="{$dashboard_log_base_url}&log_page={$page.num}&visitor_page={$dashboard_visitor_page|default:1}">{$page.num}</a>
                {/foreach}
                {if $current_page < $total_pages}<a class="wz-page-btn" href="{$dashboard_log_base_url}&log_page={$next_page}&visitor_page={$dashboard_visitor_page|default:1}">Next <i class="fa fa-chevron-right"></i></a>{/if}
            </div>
            <div class="wz-log-meta">
                {$total_entries|default:0} entries · Page {$current_page|default:1} / {$total_pages|default:1}
            </div>
        {else}
            <p class="wz-cc-empty">{Lang::T('No activity logs found')}</p>
        {/if}
    </div>

    <div class="wz-cc-footer">
        DYRSIA Powered by Dyrsia{if $app_version|default:''} · Version: {$app_version|escape}{/if}
    </div>
</div>

<script>
var WZ_CC = {
    csrf: '{$csrf_token|escape:'javascript'}',
    traffic: {
        labels: {$traffic_labels|@json_encode nofilter},
        download: {$traffic_download|@json_encode nofilter},
        upload: {$traffic_upload|@json_encode nofilter}
    },
    dataUsage: {
        labels: {$data_usage.labels|@json_encode nofilter},
        download: {$data_usage.download|@json_encode nofilter},
        upload: {$data_usage.upload|@json_encode nofilter}
    },
    services: {
        hotspot_active: {$hotspot_active|default:0},
        hotspot_expired: {$hotspot_expired|default:0},
        pppoe_active: {$pppoe_active|default:0},
        pppoe_expired: {$pppoe_expired|default:0}
    }
};
window.WZ_DEMO_EXPIRED = '{Lang::T('Demo_trial_expired')|escape:'javascript'}';
</script>
<script>
{literal}
function wzInitDemoCountdown() {
    var bar = document.getElementById('wzDemoBar');
    var el = document.getElementById('wzDemoCountdown');
    if (!bar || !el) return;

    var exp = parseInt(bar.getAttribute('data-expires-ts') || '0', 10) * 1000;
    if (!exp || isNaN(exp)) {
        var raw = (bar.getAttribute('data-expires') || '').trim();
        if (raw) {
            exp = new Date(raw.replace(' ', 'T')).getTime();
        }
    }
    if (!exp || isNaN(exp)) {
        el.textContent = '—';
        return;
    }

    function pad(n) { return String(n).padStart(2, '0'); }
    function tick() {
        var left = exp - Date.now();
        if (left <= 0) {
            el.innerHTML = '<span class="wz-cc-demo-expired">' + (window.WZ_DEMO_EXPIRED || 'Trial expired') + '</span>';
            return;
        }
        var d = Math.floor(left / 86400000);
        var h = Math.floor((left % 86400000) / 3600000);
        var m = Math.floor((left % 3600000) / 60000);
        var s = Math.floor((left % 60000) / 1000);
        el.innerHTML = d + '<small>d</small> ' + h + '<small>h</small> ' + pad(m) + '<small>m</small> ' + pad(s) + '<small>s</small>';
    }
    tick();
    setInterval(tick, 1000);
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', wzInitDemoCountdown);
} else {
    wzInitDemoCountdown();
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        var gridColor = document.body.classList.contains('theme-light') ? 'rgba(15,23,42,0.08)' : 'rgba(148,163,184,0.12)';
        var textColor = document.body.classList.contains('theme-light') ? '#64748b' : '#94a3b8';
        var tc = document.getElementById('trafficChart');
        if (tc) {
            new Chart(tc.getContext('2d'), {
                type: 'line',
                data: {
                    labels: WZ_CC.traffic.labels,
                    datasets: [
                        { label: 'Download (MB)', data: WZ_CC.traffic.download, borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.15)', fill: true, tension: 0.35, pointBackgroundColor: '#3B82F6' },
                        { label: 'Upload (MB)', data: WZ_CC.traffic.upload, borderColor: '#8B5CF6', backgroundColor: 'rgba(139,92,246,0.12)', fill: true, tension: 0.35, pointBackgroundColor: '#8B5CF6' }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor } } }, scales: { x: { grid: { color: gridColor }, ticks: { color: textColor } }, y: { grid: { color: gridColor }, ticks: { color: textColor }, beginAtZero: true } } }
            });
        }
        var sc = document.getElementById('serviceChart');
        if (sc) {
            new Chart(sc.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Hotspot Active', 'Hotspot Expired', 'PPPoE Active', 'PPPoE Expired'],
                    datasets: [{ data: [WZ_CC.services.hotspot_active, WZ_CC.services.hotspot_expired, WZ_CC.services.pppoe_active, WZ_CC.services.pppoe_expired], backgroundColor: ['#22C55E', '#EF4444', '#3B82F6', '#64748B'], borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: textColor, font: { size: 11 } } } } }
            });
        }
        var duc = document.getElementById('dataUsageChart');
        if (duc && WZ_CC.dataUsage) {
            new Chart(duc.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: WZ_CC.dataUsage.labels,
                    datasets: [
                        { label: 'Download (MB)', data: WZ_CC.dataUsage.download, backgroundColor: 'rgba(59,130,246,0.85)', borderRadius: 6, maxBarThickness: 22 },
                        { label: 'Upload (MB)', data: WZ_CC.dataUsage.upload, backgroundColor: 'rgba(139,92,246,0.75)', borderRadius: 6, maxBarThickness: 22 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: textColor, font: { size: 11 } } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }
                    }
                }
            });
        }
    }
});
{/literal}
</script>

{include file="sections/footer.tpl"}
