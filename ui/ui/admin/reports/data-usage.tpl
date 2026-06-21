{include file="sections/header.tpl"}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
{literal}
.du-page {
    --du-bg: transparent;
    --du-card: rgba(15, 23, 42, 0.92);
    --du-text: #f8fafc;
    --du-heading: #ffffff;
    --du-muted: #94a3b8;
    --du-line: rgba(148, 163, 184, 0.18);
    --du-brand: #2563eb;
    --du-brand2: #0891b2;
    --du-success: #10b981;
    --du-warning: #f59e0b;
    --du-danger: #ef4444;
    --du-shadow: 0 12px 40px rgba(0, 0, 0, 0.22);
    font-family: Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    color: var(--du-text);
    margin: 0 -15px 0;
    padding: 0 15px 28px;
}
body.theme-light .du-page {
    --du-card: #ffffff;
    --du-text: #1e293b;
    --du-heading: #0f172a;
    --du-muted: #64748b;
    --du-line: #e2e8f0;
    --du-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
}
.du-page * { box-sizing: border-box; }

.du-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}
.du-hero h1 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: var(--du-heading);
    letter-spacing: -0.02em;
}
.du-hero p { margin: 6px 0 0; color: var(--du-muted); font-size: 14px; }

.du-filters {
    background: var(--du-card);
    border: 1px solid var(--du-line);
    border-radius: 16px;
    padding: 18px;
    margin-bottom: 20px;
    box-shadow: var(--du-shadow);
}
.du-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    align-items: end;
}
.du-field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--du-muted);
    margin-bottom: 6px;
}
.du-input, .du-select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid var(--du-line);
    background: rgba(2, 6, 23, 0.55);
    color: var(--du-text);
    font-size: 14px;
    font-family: inherit;
}
body.theme-light .du-input, body.theme-light .du-select { background: #f8fafc; }
.du-input:focus, .du-select:focus {
    outline: none;
    border-color: var(--du-brand);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
}
.du-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border: 0;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--du-brand), var(--du-brand2));
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    width: 100%;
}
.du-btn:hover { opacity: 0.95; }
.du-btn-sync {
    background: rgba(37, 99, 235, 0.12);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.35);
    text-decoration: none;
}
body.theme-light .du-btn-sync { color: #2563eb; background: rgba(37, 99, 235, 0.08); }
.du-btn-sync:hover { background: rgba(37, 99, 235, 0.2); opacity: 1; }

.du-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.du-kpi {
    background: var(--du-card);
    border: 1px solid var(--du-line);
    border-radius: 16px;
    padding: 18px;
    box-shadow: var(--du-shadow);
    position: relative;
    overflow: hidden;
}
.du-kpi::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 80px; height: 80px;
    border-radius: 50%;
    opacity: 0.12;
    transform: translate(25%, -25%);
}
.du-kpi.dl::after { background: #3b82f6; }
.du-kpi.ul::after { background: #ef4444; }
.du-kpi.combined::after { background: #10b981; }
.du-kpi.peak::after { background: #8b5cf6; }
.du-kpi.clients::after { background: #06b6d4; }
.du-kpi.sat::after { background: #f59e0b; }
.du-kpi-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    margin-bottom: 12px;
}
.du-kpi.dl .du-kpi-icon { background: rgba(59,130,246,.15); color: #60a5fa; }
.du-kpi.ul .du-kpi-icon { background: rgba(239,68,68,.15); color: #f87171; }
.du-kpi.combined .du-kpi-icon { background: rgba(16,185,129,.15); color: #34d399; }
.du-kpi.peak .du-kpi-icon { background: rgba(139,92,246,.15); color: #a78bfa; }
.du-kpi.clients .du-kpi-icon { background: rgba(6,182,212,.15); color: #22d3ee; }
.du-kpi.sat .du-kpi-icon { background: rgba(245,158,11,.15); color: #fbbf24; }
.du-kpi-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--du-heading);
    line-height: 1.1;
}
.du-kpi-label { font-size: 12px; color: var(--du-muted); margin-top: 4px; font-weight: 600; }

.du-grid-main {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
@media (max-width: 1100px) { .du-grid-main { grid-template-columns: 1fr; } }

.du-card {
    background: var(--du-card);
    border: 1px solid var(--du-line);
    border-radius: 16px;
    box-shadow: var(--du-shadow);
    overflow: hidden;
}
.du-card-head {
    padding: 16px 18px;
    border-bottom: 1px solid var(--du-line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.du-card-head h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--du-heading);
}
.du-card-body { padding: 18px; }
.du-chart-wrap {
    position: relative;
    min-height: 340px;
    padding: 4px 0 8px;
}
.du-chart-canvas {
    position: relative;
    height: 300px;
    width: 100%;
}
.du-chart-head-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.du-chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.du-legend-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    border: 1px solid var(--du-line);
    background: rgba(2, 6, 23, 0.25);
}
body.theme-light .du-legend-pill { background: #f8fafc; }
.du-legend-pill i {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    display: inline-block;
}
.du-legend-pill.dl i { background: linear-gradient(135deg, #60a5fa, #2563eb); box-shadow: 0 0 10px rgba(59,130,246,.45); }
.du-legend-pill.ul i { background: linear-gradient(135deg, #fb7185, #e11d48); box-shadow: 0 0 10px rgba(244,63,94,.35); }
.du-chart-period {
    font-size: 11px;
    font-weight: 600;
    color: var(--du-muted);
}
.du-chart-empty {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: var(--du-muted);
    padding: 24px;
}
.du-chart-empty .pulse {
    width: 64px; height: 64px;
    border-radius: 50%;
    border: 3px solid rgba(37,99,235,.15);
    border-top-color: var(--du-brand);
    animation: duSpin 1.2s linear infinite;
    margin-bottom: 14px;
}
@keyframes duSpin { to { transform: rotate(360deg); } }

.du-router-list { display: flex; flex-direction: column; gap: 10px; }
.du-router-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--du-line);
    background: rgba(2, 6, 23, 0.25);
}
body.theme-light .du-router-item { background: #f8fafc; }
.du-router-name { font-weight: 700; color: var(--du-heading); font-size: 14px; }
.du-router-meta { font-size: 12px; color: var(--du-muted); margin-top: 2px; }
.du-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.du-badge.online { background: rgba(16,185,129,.15); color: #34d399; }
.du-badge.warning { background: rgba(245,158,11,.15); color: #fbbf24; }
.du-badge.offline { background: rgba(239,68,68,.15); color: #f87171; }
.du-badge-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

.du-top-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}
@media (max-width: 900px) { .du-top-grid { grid-template-columns: 1fr; } }

.du-rank-list { list-style: none; margin: 0; padding: 0; }
.du-rank-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--du-line);
}
.du-rank-item:last-child { border-bottom: 0; padding-bottom: 0; }
.du-rank-num {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: rgba(37,99,235,.12);
    color: #60a5fa;
    font-size: 12px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.du-rank-info { flex: 1; min-width: 0; }
.du-rank-title { font-weight: 700; color: var(--du-heading); font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.du-rank-sub { font-size: 12px; color: var(--du-muted); }
.du-rank-val { font-weight: 800; color: var(--du-heading); font-size: 13px; white-space: nowrap; }

.du-table-wrap { overflow-x: auto; }
.du-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.du-table th {
    text-align: left;
    padding: 12px 16px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--du-muted);
    border-bottom: 1px solid var(--du-line);
    background: rgba(2, 6, 23, 0.2);
}
body.theme-light .du-table th { background: #f8fafc; }
.du-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--du-line);
    color: var(--du-text);
}
.du-page .du-table th:first-child,
.du-page .du-table td:first-child {
    position: static;
    background: transparent !important;
    color: var(--du-text);
}
.du-page .du-table th:first-child {
    background: rgba(2, 6, 23, 0.2) !important;
}
body.theme-light .du-page .du-table th:first-child {
    background: #f8fafc !important;
}
.du-page .du-table td:first-child strong {
    color: var(--du-heading);
}
.du-page .du-table td:first-child .du-rank-sub {
    color: var(--du-muted);
}
.du-table tr:last-child td { border-bottom: 0; }
.du-status {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}
.du-status.connected { background: rgba(16,185,129,.15); color: #34d399; }
.du-status.disconnected { background: rgba(148,163,184,.15); color: var(--du-muted); }

.du-empty {
    text-align: center;
    padding: 48px 24px;
    color: var(--du-muted);
}
.du-empty i {
    font-size: 42px;
    opacity: 0.35;
    display: block;
    margin-bottom: 12px;
}
.du-empty strong { display: block; color: var(--du-heading); font-size: 16px; margin-bottom: 6px; }

.du-loading { opacity: 0.55; pointer-events: none; }
.du-filter-live {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    color: var(--du-muted);
    min-height: 18px;
}
.du-filter-live.is-busy { color: #60a5fa; }
.du-filter-live.is-busy i { animation: duSpin 0.8s linear infinite; }
{/literal}
</style>

<div class="du-page" id="duPage">

    <div class="du-hero">
        <div>
            <h1><i class="fa fa-area-chart" style="color:#60a5fa;margin-right:8px"></i> {Lang::T('Data Usage')}</h1>
            <p>Consommation bande passante · Hotspot & PPPoE · Sync MikroTik</p>
        </div>
    </div>

    <div class="du-filters">
        <div class="du-filter-grid">
            <div class="du-field">
                <label><i class="fa fa-filter"></i> Usage</label>
                <select id="usage-filter" class="du-select">
                    <option value="">{if $_admin['user_type'] eq 'SuperAdmin'}Tous les usages{else}Tous mes clients{/if}</option>
                    {if $_admin['user_type'] eq 'SuperAdmin'}
                    <optgroup label="Administrateurs">
                        {foreach $admins as $a}
                        <option value="admin:{$a.id}">{$a.fullname|default:$a.username}</option>
                        {/foreach}
                    </optgroup>
                    {/if}
                    <optgroup label="Clients">
                        {foreach $customers as $c}
                        <option value="customer:{$c.id}">{$c.fullname|default:$c.username} · {$c.service_type|default:'—'}</option>
                        {/foreach}
                    </optgroup>
                </select>
            </div>
            <div class="du-field">
                <label><i class="fa fa-server"></i> {Lang::T('Router')}</label>
                <select id="router" class="du-select">
                    <option value="">{Lang::T('All Routers')}</option>
                    {foreach $routers as $r}
                    <option value="{$r.name}">{$r.name}</option>
                    {/foreach}
                </select>
            </div>
            <div class="du-field">
                <label><i class="fa fa-users"></i> Service</label>
                <select id="service-type" class="du-select">
                    <option value="">Tous les services</option>
                    {foreach $service_types as $st}
                    <option value="{$st}">{$st}</option>
                    {/foreach}
                </select>
            </div>
            <div class="du-field">
                <label><i class="fa fa-search"></i> {Lang::T('User')}</label>
                <input type="text" id="q" class="du-input" placeholder="Username, nom, téléphone">
            </div>
            <div class="du-field" style="grid-column: span 2">
                <label><i class="fa fa-calendar"></i> Période</label>
                <input type="text" id="date-range" class="du-input" placeholder="Sélectionner une période">
                <input type="hidden" id="start-date">
                <input type="hidden" id="end-date">
            </div>
            <div class="du-field">
                <label>&nbsp;</label>
                <button type="button" class="du-btn" id="duSearchBtn"><i class="fa fa-refresh"></i> Actualiser</button>
                <div class="du-filter-live" id="duFilterLive"><i class="fa fa-circle"></i> Filtres en temps réel</div>
            </div>
            <div class="du-field">
                <label>&nbsp;</label>
                <a href="{Text::url('reports/data-usage-sync')}" class="du-btn du-btn-sync" onclick="return confirm('Synchroniser la consommation depuis les routeurs MikroTik ?');">
                    <i class="fa fa-cloud-download"></i> Sync MikroTik
                </a>
            </div>
        </div>
    </div>

    <div class="du-kpi-grid">
        <div class="du-kpi dl"><div class="du-kpi-icon"><i class="fa fa-download"></i></div><div class="du-kpi-value" id="kpi-download">—</div><div class="du-kpi-label">{Lang::T('Download')}</div></div>
        <div class="du-kpi ul"><div class="du-kpi-icon"><i class="fa fa-upload"></i></div><div class="du-kpi-value" id="kpi-upload">—</div><div class="du-kpi-label">{Lang::T('Upload')}</div></div>
        <div class="du-kpi combined"><div class="du-kpi-icon"><i class="fa fa-exchange"></i></div><div class="du-kpi-value" id="kpi-combined">—</div><div class="du-kpi-label">{Lang::T('Total')}</div></div>
        <div class="du-kpi peak"><div class="du-kpi-icon"><i class="fa fa-bolt"></i></div><div class="du-kpi-value" id="kpi-peak">—</div><div class="du-kpi-label">Pic max (Mbps)</div></div>
        <div class="du-kpi clients"><div class="du-kpi-icon"><i class="fa fa-wifi"></i></div><div class="du-kpi-value" id="kpi-clients">—</div><div class="du-kpi-label">Clients actifs</div></div>
        <div class="du-kpi sat"><div class="du-kpi-icon"><i class="fa fa-signal"></i></div><div class="du-kpi-value" id="kpi-saturation">—</div><div class="du-kpi-label">Saturation réseau</div></div>
    </div>

    <div class="du-grid-main">
        <div class="du-card">
            <div class="du-card-head">
                <h3><i class="fa fa-line-chart"></i> Download / Upload</h3>
                <div class="du-chart-head-meta">
                    <span class="du-chart-period" id="chartPeriodLabel"></span>
                    <div class="du-chart-legend">
                        <span class="du-legend-pill dl"><i></i> Download</span>
                        <span class="du-legend-pill ul"><i></i> Upload</span>
                    </div>
                </div>
            </div>
            <div class="du-card-body du-chart-wrap">
                <div class="du-chart-empty" id="chartEmpty">
                    <div class="pulse"></div>
                    <strong>Aucune donnée sur cette période</strong>
                    <span>Les graphiques s'afficheront dès la première synchronisation MikroTik.</span>
                </div>
                <div class="du-chart-canvas" id="chartCanvasWrap" style="display:none">
                    <canvas id="usage-chart"></canvas>
                </div>
            </div>
        </div>
        <div class="du-card">
            <div class="du-card-head"><h3><i class="fa fa-hdd-o"></i> État RouterOS API</h3></div>
            <div class="du-card-body">
                <div class="du-router-list" id="router-status-list">
                    <div class="du-empty"><i class="fa fa-spinner fa-spin"></i> Chargement…</div>
                </div>
            </div>
        </div>
    </div>

    <div class="du-top-grid">
        <div class="du-card">
            <div class="du-card-head"><h3>Top 5 utilisateurs</h3><span class="du-badge online"><span class="du-badge-dot"></span> Download</span></div>
            <div class="du-card-body"><ul class="du-rank-list" id="top-users"></ul></div>
        </div>
        <div class="du-card">
            <div class="du-card-head"><h3>Top 5 routeurs</h3><span class="du-badge warning"><span class="du-badge-dot"></span> Trafic</span></div>
            <div class="du-card-body"><ul class="du-rank-list" id="top-routers"></ul></div>
        </div>
        <div class="du-card">
            <div class="du-card-head"><h3>Top 5 services</h3><span class="du-badge online"><span class="du-badge-dot"></span> Volume</span></div>
            <div class="du-card-body"><ul class="du-rank-list" id="top-services"></ul></div>
        </div>
    </div>

    <div class="du-card">
        <div class="du-card-head"><h3><i class="fa fa-users"></i> Consommation par client (PPPoE &amp; Hotspot)</h3><span class="du-badge online"><span class="du-badge-dot"></span> Total par client</span></div>
        <div class="du-table-wrap">
            <table class="du-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>{Lang::T('Username')}</th>
                        <th>Service</th>
                        <th>{Lang::T('Router')}</th>
                        <th>{Lang::T('Download')}</th>
                        <th>{Lang::T('Upload')}</th>
                        <th>{Lang::T('Total')}</th>
                    </tr>
                </thead>
                <tbody id="clients-rows">
                    <tr><td colspan="7"><div class="du-empty"><i class="fa fa-users"></i><strong>Aucune donnée client</strong><span>Sélectionnez une période ou un service (Hotspot / PPPoE).</span></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="du-card">
        <div class="du-card-head"><h3><i class="fa fa-table"></i> {Lang::T('Usage Details')}</h3></div>
        <div class="du-table-wrap">
            <table class="du-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Date')}</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Router')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Download')}</th>
                        <th>{Lang::T('Upload')}</th>
                        <th>{Lang::T('Total')}</th>
                    </tr>
                </thead>
                <tbody id="usage-rows">
                    <tr><td colspan="7"><div class="du-empty"><i class="fa fa-database"></i><strong>Aucune donnée</strong><span>Modifiez les filtres ou lancez le cron de synchronisation.</span></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script>
var WZ_DU = {
    apiUrl: '{$_url}reports/data-usage-api',
    isLight: document.body.classList.contains('theme-light')
};
{literal}
(function(){
    var usageChart = null;
    var page = document.getElementById('duPage');
    var chartCanvas = document.getElementById('usage-chart');
    var chartEmpty = document.getElementById('chartEmpty');
    var chartCanvasWrap = document.getElementById('chartCanvasWrap');
    var chartPeriodLabel = document.getElementById('chartPeriodLabel');
    var filterLive = document.getElementById('duFilterLive');
    var loadTimer = null;

    function setFilterBusy(busy) {
        if (!filterLive) return;
        filterLive.classList.toggle('is-busy', !!busy);
        filterLive.innerHTML = busy
            ? '<i class="fa fa-refresh"></i> Mise à jour…'
            : '<i class="fa fa-check-circle"></i> Données à jour';
    }

    function scheduleLoadUsage(delay) {
        clearTimeout(loadTimer);
        loadTimer = setTimeout(loadUsage, delay || 0);
    }

    function formatShortDate(iso) {
        if (!iso) return '';
        var p = iso.split('-');
        if (p.length !== 3) return iso;
        var months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        return parseInt(p[2], 10) + ' ' + months[parseInt(p[1], 10) - 1];
    }

    function formatMb(value) {
        var n = Number(value) || 0;
        if (n >= 1024) return (n / 1024).toFixed(2) + ' GB';
        return n.toFixed(2) + ' MB';
    }

    function chartGradient(ctx, area, colorTop, colorBottom) {
        var g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
        g.addColorStop(0, colorTop);
        g.addColorStop(1, colorBottom);
        return g;
    }

    function chartHasTraffic(chart) {
        if (!chart || !chart.labels || !chart.labels.length) return false;
        for (var i = 0; i < chart.labels.length; i++) {
            if ((chart.download_mb[i] || 0) > 0 || (chart.upload_mb[i] || 0) > 0) return true;
        }
        return false;
    }

    function renderChart(chart) {
        var hasData = chartHasTraffic(chart);
        chartEmpty.style.display = hasData ? 'none' : 'flex';
        chartCanvasWrap.style.display = hasData ? 'block' : 'none';
        if (usageChart) { usageChart.destroy(); usageChart = null; }
        if (!hasData) {
            chartPeriodLabel.textContent = '';
            return;
        }

        var first = chart.labels[0];
        var last = chart.labels[chart.labels.length - 1];
        chartPeriodLabel.textContent = formatShortDate(first) + ' → ' + formatShortDate(last);

        var labels = chart.labels.map(formatShortDate);
        var spanDays = chart.labels.length;
        var activeDays = 0;
        for (var i = 0; i < spanDays; i++) {
            if ((chart.download_mb[i] || 0) > 0 || (chart.upload_mb[i] || 0) > 0) activeDays++;
        }
        var useBars = spanDays <= 21 || (activeDays <= 10 && spanDays <= 45);

        var gridColor = WZ_DU.isLight ? 'rgba(15,23,42,0.06)' : 'rgba(148,163,184,0.1)';
        var textColor = WZ_DU.isLight ? '#64748b' : '#94a3b8';
        var tooltipBg = WZ_DU.isLight ? 'rgba(15,23,42,0.92)' : 'rgba(2,6,23,0.94)';

        var ctx = chartCanvas.getContext('2d');
        var dlDataset = {
            label: 'Download',
            data: chart.download_mb,
            borderColor: '#3b82f6',
            backgroundColor: useBars ? 'rgba(59,130,246,0.88)' : function(context) {
                var chartArea = context.chart.chartArea;
                if (!chartArea) return 'rgba(59,130,246,0.2)';
                return chartGradient(ctx, chartArea, 'rgba(59,130,246,0.42)', 'rgba(59,130,246,0.02)');
            },
            fill: !useBars,
            tension: 0.42,
            borderWidth: useBars ? 0 : 3,
            pointRadius: function(context) {
                return useBars ? 0 : ((context.raw || 0) > 0 ? 6 : 0);
            },
            pointHoverRadius: 8,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#3b82f6',
            pointBorderWidth: 2,
            borderRadius: useBars ? { topLeft: 8, topRight: 8 } : 0,
            maxBarThickness: useBars ? 28 : undefined,
            barPercentage: useBars ? 0.72 : undefined,
            categoryPercentage: useBars ? 0.82 : undefined
        };
        var ulDataset = {
            label: 'Upload',
            data: chart.upload_mb,
            borderColor: '#f43f5e',
            backgroundColor: useBars ? 'rgba(244,63,94,0.82)' : function(context) {
                var chartArea = context.chart.chartArea;
                if (!chartArea) return 'rgba(244,63,94,0.15)';
                return chartGradient(ctx, chartArea, 'rgba(244,63,94,0.32)', 'rgba(244,63,94,0.02)');
            },
            fill: !useBars,
            tension: 0.42,
            borderWidth: useBars ? 0 : 3,
            pointRadius: function(context) {
                return useBars ? 0 : ((context.raw || 0) > 0 ? 6 : 0);
            },
            pointHoverRadius: 8,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#f43f5e',
            pointBorderWidth: 2,
            borderRadius: useBars ? { topLeft: 8, topRight: 8 } : 0,
            maxBarThickness: useBars ? 28 : undefined,
            barPercentage: useBars ? 0.72 : undefined,
            categoryPercentage: useBars ? 0.82 : undefined
        };

        usageChart = new Chart(ctx, {
            type: useBars ? 'bar' : 'line',
            data: { labels: labels, datasets: [dlDataset, ulDataset] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeOutQuart' },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: '#f8fafc',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(148,163,184,0.25)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 12,
                        displayColors: true,
                        boxPadding: 6,
                        callbacks: {
                            title: function(items) {
                                if (!items.length) return '';
                                var idx = items[0].dataIndex;
                                return chart.labels[idx] || items[0].label;
                            },
                            label: function(item) {
                                return ' ' + item.dataset.label + ' : ' + formatMb(item.raw);
                            },
                            footer: function(items) {
                                if (!items.length) return '';
                                var total = 0;
                                items.forEach(function(it) { total += Number(it.raw) || 0; });
                                return 'Total : ' + formatMb(total);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: {
                            color: textColor,
                            maxTicksLimit: useBars ? 12 : 10,
                            maxRotation: 0,
                            autoSkip: true,
                            font: { size: 11, weight: '600' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            color: textColor,
                            font: { size: 11 },
                            callback: function(v) { return formatMb(v); }
                        }
                    }
                }
            }
        });
    }

    function apiUrl(extra) {
        var url = WZ_DU.apiUrl + extra;
        var usageEl = document.getElementById('usage-filter');
        if (usageEl && usageEl.value) {
            url += '&usage_filter=' + encodeURIComponent(usageEl.value);
        }
        return url;
    }

    function rankList(el, items, titleKey, subKey, valKey, emptyLabel) {
        if (!items || !items.length) {
            el.innerHTML = '<li class="du-empty" style="padding:24px 0"><i class="fa fa-inbox"></i><strong>' + emptyLabel + '</strong></li>';
            return;
        }
        var html = '';
        items.forEach(function(item, i) {
            html += '<li class="du-rank-item"><span class="du-rank-num">' + (i + 1) + '</span><div class="du-rank-info"><div class="du-rank-title">' + (item[titleKey] || '—') + '</div>' +
                (subKey && item[subKey] ? '<div class="du-rank-sub">' + item[subKey] + '</div>' : '') +
                '</div><span class="du-rank-val">' + (item[valKey] || '—') + '</span></li>';
        });
        el.innerHTML = html;
    }

    function renderRouterStatus(list) {
        var el = document.getElementById('router-status-list');
        if (!list || !list.length) {
            el.innerHTML = '<div class="du-empty"><i class="fa fa-server"></i><strong>Aucun routeur configuré</strong></div>';
            return;
        }
        var labels = { online: 'En ligne', warning: 'Retard sync', offline: 'Hors ligne' };
        var html = '';
        list.forEach(function(r) {
            var err = r.error ? '<div class="du-router-meta" style="color:#f87171;margin-top:4px">' + r.error + '</div>' : '';
            html += '<div class="du-router-item"><div><div class="du-router-name"><i class="fa fa-wifi"></i> ' + r.name + '</div><div class="du-router-meta">' + (r.ip || '') + ' · Sync: ' + r.last_sync + '</div>' + err + '</div>' +
                '<span class="du-badge ' + r.status + '"><span class="du-badge-dot"></span> ' + (labels[r.status] || r.status) + '</span></div>';
        });
        el.innerHTML = html;
    }

    function renderTable(rows) {
        var tbody = document.getElementById('usage-rows');
        if (!rows || !rows.length) {
            tbody.innerHTML = '<tr><td colspan="7"><div class="du-empty"><i class="fa fa-database"></i><strong>Aucune donnée</strong><span>Ajustez la période ou les filtres.</span></div></td></tr>';
            return;
        }
        var html = '';
        rows.forEach(function(x) {
            var st = (x.status || '').toLowerCase() === 'connected' ? 'connected' : 'disconnected';
            var dateLabel = formatShortDate(x.date) || x.date;
            html += '<tr><td>' + dateLabel + '</td><td><strong>' + x.username + '</strong></td><td>' + (x.router || '—') + '</td>' +
                '<td><span class="du-status ' + st + '">' + x.status + '</span></td>' +
                '<td>' + x.metrics.download + '</td><td>' + x.metrics.upload + '</td><td><strong>' + x.metrics.total + '</strong></td></tr>';
        });
        tbody.innerHTML = html;
    }

    function serviceBadge(type) {
        var t = (type || '').toLowerCase();
        var cls = 'warning';
        if (t === 'hotspot') cls = 'online';
        else if (t === 'pppoe') cls = 'offline';
        return '<span class="du-badge ' + cls + '"><span class="du-badge-dot"></span> ' + (type || 'Autre') + '</span>';
    }

    function renderClients(rows) {
        var tbody = document.getElementById('clients-rows');
        if (!rows || !rows.length) {
            tbody.innerHTML = '<tr><td colspan="7"><div class="du-empty"><i class="fa fa-users"></i><strong>Aucune donnée client</strong><span>Ajustez la période ou le service.</span></div></td></tr>';
            return;
        }
        var html = '';
        rows.forEach(function(x) {
            var name = x.fullname && x.fullname !== '—'
                ? '<strong>' + x.fullname + '</strong>' + (x.phonenumber ? '<div class="du-rank-sub">' + x.phonenumber + '</div>' : '')
                : '<span style="opacity:.6">—</span>';
            html += '<tr><td>' + name + '</td><td>' + x.username + '</td><td>' + serviceBadge(x.service_type) + '</td>' +
                '<td>' + (x.router || '—') + '</td><td>' + x.download + '</td><td>' + x.upload + '</td><td><strong>' + x.total + '</strong></td></tr>';
        });
        tbody.innerHTML = html;
    }

    function loadUsage() {
        var q = document.getElementById('q').value;
        var router = document.getElementById('router').value;
        var serviceType = document.getElementById('service-type').value;
        var sd = document.getElementById('start-date').value;
        var ed = document.getElementById('end-date').value;
        page.classList.add('du-loading');
        setFilterBusy(true);
        fetch(apiUrl('&q=' + encodeURIComponent(q) + '&router=' + encodeURIComponent(router) + '&service_type=' + encodeURIComponent(serviceType) + '&start_date=' + encodeURIComponent(sd) + '&end_date=' + encodeURIComponent(ed)), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'error') {
                    setFilterBusy(false);
                    if (filterLive) {
                        filterLive.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + (res.message || 'Erreur');
                    }
                    return;
                }
                if (res.status !== 'success') return;
                var s = res.summary || {};
                document.getElementById('kpi-download').textContent = s.download || '0 Bytes';
                document.getElementById('kpi-upload').textContent = s.upload || '0 Bytes';
                document.getElementById('kpi-combined').textContent = s.combined || '0 Bytes';
                document.getElementById('kpi-peak').textContent = (s.peak_mbps || 0) + ' Mbps';
                document.getElementById('kpi-clients').textContent = s.active_clients || 0;
                document.getElementById('kpi-saturation').textContent = (s.saturation_pct || 0) + '%';
                renderChart(res.chart);
                renderRouterStatus(res.routers_status);
                rankList(document.getElementById('top-users'), res.top_users, 'username', 'fullname', 'download_formatted', 'Aucun utilisateur');
                rankList(document.getElementById('top-routers'), res.top_routers, 'name', null, 'traffic_formatted', 'Aucun routeur');
                rankList(document.getElementById('top-services'), res.top_services, 'name', null, 'traffic_formatted', 'Aucun service');
                renderClients(res.clients_breakdown);
                renderTable(res.data);
                setFilterBusy(false);
            })
            .catch(function() {
                if (filterLive) {
                    filterLive.innerHTML = '<i class="fa fa-exclamation-circle"></i> Erreur réseau';
                }
            })
            .finally(function() { page.classList.remove('du-loading'); });
    }

    document.getElementById('duSearchBtn').addEventListener('click', function() { scheduleLoadUsage(0); });
    document.getElementById('q').addEventListener('keydown', function(e) { if (e.key === 'Enter') scheduleLoadUsage(0); });
    document.getElementById('q').addEventListener('input', function() { scheduleLoadUsage(450); });
    ['usage-filter', 'router', 'service-type'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', function() { scheduleLoadUsage(0); });
    });

    document.addEventListener('DOMContentLoaded', function() {
        var today = new Date();
        var start = new Date(today.getFullYear(), 0, 1);
        var sd = start.toISOString().slice(0, 10);
        var ed = today.toISOString().slice(0, 10);
        document.getElementById('start-date').value = sd;
        document.getElementById('end-date').value = ed;
        flatpickr('#date-range', {
            mode: 'range',
            locale: 'fr',
            dateFormat: 'Y-m-d',
            defaultDate: [sd, ed],
            onChange: function(dates) {
                if (dates.length >= 1) document.getElementById('start-date').value = flatpickr.formatDate(dates[0], 'Y-m-d');
                if (dates.length >= 2) document.getElementById('end-date').value = flatpickr.formatDate(dates[1], 'Y-m-d');
                if (dates.length >= 2) scheduleLoadUsage(0);
            }
        });
        scheduleLoadUsage(0);
    });
})();
{/literal}
</script>

{include file="sections/footer.tpl"}
