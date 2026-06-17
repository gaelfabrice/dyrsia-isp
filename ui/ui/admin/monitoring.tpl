{include file="sections/header.tpl"}

<style>
{literal}
.mon-page {
    --mon-card: rgba(15, 23, 42, 0.92);
    --mon-text: #e2e8f0;
    --mon-heading: #ffffff;
    --mon-muted: #94a3b8;
    --mon-line: rgba(148, 163, 184, 0.16);
    --mon-brand: #2563eb;
    --mon-green: #10b981;
    --mon-blue: #3b82f6;
    --mon-red: #ef4444;
    --mon-shadow: 0 14px 40px rgba(0, 0, 0, 0.22);
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
    color: var(--mon-text);
    margin: 0 -15px;
    padding: 4px 15px 30px;
}
body.theme-light .mon-page {
    --mon-card: #ffffff;
    --mon-text: #334155;
    --mon-heading: #0f172a;
    --mon-muted: #64748b;
    --mon-line: #e7ebf0;
    --mon-shadow: 0 12px 36px rgba(15, 23, 42, 0.07);
}
.mon-page * { box-sizing: border-box; }

/* Header */
.mon-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}
.mon-header-left { display: flex; align-items: center; gap: 14px; }
.mon-header-ic {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff; font-size: 22px;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
    flex-shrink: 0;
}
.mon-header h1 { margin: 0; font-size: 24px; font-weight: 800; color: var(--mon-heading); letter-spacing: -0.02em; }
.mon-header p { margin: 4px 0 0; font-size: 13.5px; color: var(--mon-muted); }
.mon-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.mon-select, .mon-btn {
    padding: 10px 14px; border-radius: 12px; font-size: 13.5px; font-weight: 600;
    border: 1px solid var(--mon-line); background: var(--mon-card); color: var(--mon-text);
    cursor: pointer; transition: all .15s ease;
}
body.theme-light .mon-select { background: #f8fafc; }
.mon-select:focus, .mon-btn:focus { outline: none; border-color: var(--mon-brand); }
.mon-btn-pdf {
    background: var(--mon-heading); color: #fff; border-color: transparent;
    display: inline-flex; align-items: center; gap: 8px;
}
body.theme-light .mon-btn-pdf { background: #0f172a; }

/* KPI grid */
.mon-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 18px;
}
@media (max-width: 1100px) { .mon-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px) { .mon-kpi-grid { grid-template-columns: 1fr; } }

.mon-kpi {
    background: var(--mon-card);
    border: 1px solid var(--mon-line);
    border-radius: 18px;
    padding: 18px 20px;
    box-shadow: var(--mon-shadow);
    position: relative;
    overflow: hidden;
}
.mon-kpi-label {
    font-size: 11px; font-weight: 800; letter-spacing: .06em;
    text-transform: uppercase; color: var(--mon-muted); margin-bottom: 8px;
}
.mon-kpi-value { font-size: 32px; font-weight: 800; color: var(--mon-heading); line-height: 1; }
.mon-kpi-trend {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 12px; font-weight: 700; margin-top: 8px;
}
.mon-kpi-trend.up { color: var(--mon-green); }
.mon-kpi-trend.down { color: var(--mon-red); }
.mon-kpi-trend.flat { color: var(--mon-muted); }
.mon-kpi-spark {
    position: absolute; right: 16px; bottom: 14px;
    width: 80px; height: 36px; opacity: .85;
}
.mon-kpi.ok .mon-kpi-value { font-size: 28px; }
.mon-kpi-ok { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 13px; font-weight: 600; color: var(--mon-green); }

/* Status bars */
.mon-status-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 18px;
}
@media (max-width: 768px) { .mon-status-grid { grid-template-columns: 1fr; } }

.mon-status-card {
    background: var(--mon-card);
    border: 1px solid var(--mon-line);
    border-radius: 18px;
    padding: 18px 20px;
    box-shadow: var(--mon-shadow);
}
.mon-status-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; font-weight: 700; font-size: 14px; color: var(--mon-heading);
}
.mon-status-head i { margin-right: 8px; }
.mon-status-head.hotspot i { color: var(--mon-green); }
.mon-status-head.pppoe i { color: var(--mon-blue); }
.mon-bar-wrap {
    height: 10px; border-radius: 999px; background: var(--mon-line);
    overflow: hidden; margin-bottom: 10px;
}
.mon-bar-fill {
    height: 100%; border-radius: 999px; transition: width .4s ease;
}
.mon-bar-fill.hotspot { background: linear-gradient(90deg, #10b981, #34d399); }
.mon-bar-fill.pppoe { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.mon-status-legend { display: flex; gap: 18px; font-size: 12.5px; color: var(--mon-muted); }
.mon-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
.mon-dot.green { background: var(--mon-green); }
.mon-dot.blue { background: var(--mon-blue); }
.mon-dot.grey { background: #cbd5e1; }

/* Cards */
.mon-card {
    background: var(--mon-card);
    border: 1px solid var(--mon-line);
    border-radius: 18px;
    box-shadow: var(--mon-shadow);
    overflow: hidden;
}
.mon-card-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--mon-line);
}
.mon-card-head h3 { margin: 0; font-size: 15px; font-weight: 800; color: var(--mon-heading); }
.mon-card-body { padding: 16px 20px; }
.mon-chart-wrap { height: 280px; position: relative; }
.mon-legend { display: flex; gap: 16px; font-size: 12px; font-weight: 600; color: var(--mon-muted); }
.mon-legend span { display: inline-flex; align-items: center; gap: 6px; }
.mon-legend .dot { width: 10px; height: 10px; border-radius: 50%; }
.mon-legend .dot.hs { background: var(--mon-green); }
.mon-legend .dot.pp { background: var(--mon-blue); }

/* Bottom grid */
.mon-bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 18px;
}
@media (max-width: 900px) { .mon-bottom-grid { grid-template-columns: 1fr; } }

.mon-feed { list-style: none; margin: 0; padding: 0; }
.mon-feed li {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 0; border-bottom: 1px solid var(--mon-line);
    font-size: 13.5px;
}
.mon-feed li:last-child { border-bottom: 0; }
.mon-feed-time { color: var(--mon-muted); font-size: 12.5px; font-weight: 600; white-space: nowrap; margin-left: 12px; }

.mon-top-list { list-style: none; margin: 0; padding: 0; }
.mon-top-list li {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 0; border-bottom: 1px solid var(--mon-line);
    font-size: 14px; font-weight: 600; color: var(--mon-heading);
}
.mon-top-list li:last-child { border-bottom: 0; }
.mon-top-list .pin { color: var(--mon-red); margin-right: 10px; }
.mon-top-list .count { color: var(--mon-muted); font-weight: 700; font-size: 13px; }

.mon-empty { text-align: center; padding: 28px 12px; color: var(--mon-muted); font-size: 13px; }
.mon-empty i { display: block; font-size: 28px; margin-bottom: 8px; opacity: .45; }

@media print {
    .mon-header-actions, .main-sidebar, .main-header, .main-footer { display: none !important; }
    .mon-page { margin: 0; padding: 0; }
    .mon-card, .mon-kpi, .mon-status-card { box-shadow: none !important; break-inside: avoid; }
}
{/literal}
</style>

<div class="mon-page" id="monPage">
    <div class="mon-header">
        <div class="mon-header-left">
            <div class="mon-header-ic"><i class="fa fa-wifi"></i></div>
            <div>
                <h1>{Lang::T('Network & Customer Monitoring')}</h1>
                <p>Vue d'ensemble &middot; 12 derniers mois</p>
            </div>
        </div>
        <div class="mon-header-actions">
            <select class="mon-select" id="monPeriod" disabled title="Période fixe">
                <option>12 derniers mois</option>
            </select>
            <select class="mon-select" id="monRouter">
                <option value="">{Lang::T('All Routers')}</option>
                {foreach $routers as $r}
                <option value="{$r.name|escape}"{if $router_filter eq $r.name} selected{/if}>{$r.name}</option>
                {/foreach}
            </select>
            <button type="button" class="mon-btn mon-btn-pdf" id="monExportPdf"><i class="fa fa-download"></i> Exporter PDF</button>
        </div>
    </div>

    <div class="mon-kpi-grid">
        <div class="mon-kpi">
            <div class="mon-kpi-label">Clients total</div>
            <div class="mon-kpi-value">{$mon.c_all}</div>
            {if $mon.trends.customers > 0}
                <div class="mon-kpi-trend up"><i class="fa fa-arrow-up"></i> +{$mon.trends.customers}% vs mois dernier</div>
            {elseif $mon.trends.customers < 0}
                <div class="mon-kpi-trend down"><i class="fa fa-arrow-down"></i> {$mon.trends.customers}% vs mois dernier</div>
            {else}
                <div class="mon-kpi-trend flat">+0% vs mois dernier</div>
            {/if}
            <svg class="mon-kpi-spark" viewBox="0 0 80 36" preserveAspectRatio="none" data-spark="{$mon.sparklines.customers|json_encode|escape}"></svg>
        </div>
        <div class="mon-kpi">
            <div class="mon-kpi-label">Total Hotspot</div>
            <div class="mon-kpi-value">{$mon.h_all}</div>
            {if $mon.trends.hotspot > 0}
                <div class="mon-kpi-trend up"><i class="fa fa-arrow-up"></i> +{$mon.trends.hotspot}% vs mois dernier</div>
            {elseif $mon.trends.hotspot < 0}
                <div class="mon-kpi-trend down"><i class="fa fa-arrow-down"></i> {$mon.trends.hotspot}% vs mois dernier</div>
            {else}
                <div class="mon-kpi-trend flat">Stable</div>
            {/if}
            <svg class="mon-kpi-spark" viewBox="0 0 80 36" preserveAspectRatio="none" data-spark="{$mon.sparklines.hotspot|json_encode|escape}"></svg>
        </div>
        <div class="mon-kpi">
            <div class="mon-kpi-label">Total PPPoE</div>
            <div class="mon-kpi-value">{$mon.p_all}</div>
            {if $mon.trends.pppoe != 0}
                <div class="mon-kpi-trend {if $mon.trends.pppoe > 0}up{else}down{/if}"><i class="fa fa-arrow-{if $mon.trends.pppoe > 0}up{else}down{/if}"></i> {$mon.trends.pppoe}% vs mois dernier</div>
            {else}
                <div class="mon-kpi-trend flat">Stable</div>
            {/if}
            <svg class="mon-kpi-spark" viewBox="0 0 80 36" preserveAspectRatio="none" data-spark="{$mon.sparklines.pppoe|json_encode|escape}"></svg>
        </div>
        <div class="mon-kpi ok">
            <div class="mon-kpi-label">Alertes réseau</div>
            <div class="mon-kpi-value">{$mon.alerts}</div>
            {if $mon.alerts == 0}
                <div class="mon-kpi-ok"><i class="fa fa-check-circle"></i> Tout est OK</div>
            {else}
                <div class="mon-kpi-trend down"><i class="fa fa-exclamation-triangle"></i> Routeur(s) hors ligne</div>
            {/if}
        </div>
    </div>

    <div class="mon-status-grid">
        <div class="mon-status-card">
            <div class="mon-status-head hotspot"><span><i class="fa fa-wifi"></i> Hotspot</span><span>{$mon.h_act} actifs / {$mon.h_total} totaux</span></div>
            <div class="mon-bar-wrap">
                <div class="mon-bar-fill hotspot" style="width:{$mon.h_pct}%"></div>
            </div>
            <div class="mon-status-legend">
                <span><span class="mon-dot green"></span>{$mon.h_act} en ligne</span>
                <span><span class="mon-dot grey"></span>{$mon.h_off} hors ligne</span>
            </div>
        </div>
        <div class="mon-status-card">
            <div class="mon-status-head pppoe"><span><i class="fa fa-sitemap"></i> PPPoE</span><span>{$mon.p_act} actifs / {$mon.p_total} totaux</span></div>
            <div class="mon-bar-wrap">
                <div class="mon-bar-fill pppoe" style="width:{$mon.p_pct}%"></div>
            </div>
            <div class="mon-status-legend">
                <span><span class="mon-dot blue"></span>{$mon.p_act} en ligne</span>
                <span><span class="mon-dot grey"></span>{$mon.p_off} hors ligne</span>
            </div>
        </div>
    </div>

    <div class="mon-card">
        <div class="mon-card-head">
            <h3>Évolution mensuelle</h3>
            <div class="mon-legend">
                <span><span class="dot hs"></span> Hotspot</span>
                <span><span class="dot pp"></span> PPPoE</span>
            </div>
        </div>
        <div class="mon-card-body">
            <div class="mon-chart-wrap">
                <canvas id="monChart"></canvas>
            </div>
        </div>
    </div>

    <div class="mon-bottom-grid">
        <div class="mon-card">
            <div class="mon-card-head"><h3>Dernières connexions</h3></div>
            <div class="mon-card-body">
                {if $mon.recent|@count > 0}
                <ul class="mon-feed">
                    {foreach $mon.recent as $ev}
                    <li>
                        <span>{$ev.text}</span>
                        <span class="mon-feed-time">{$ev.time}</span>
                    </li>
                    {/foreach}
                </ul>
                {else}
                <div class="mon-empty"><i class="fa fa-clock-o"></i>Aucune connexion récente</div>
                {/if}
            </div>
        </div>
        <div class="mon-card">
            <div class="mon-card-head"><h3>Top hotspots</h3></div>
            <div class="mon-card-body">
                {if $mon.top_hotspots|@count > 0}
                <ul class="mon-top-list">
                    {foreach $mon.top_hotspots as $site}
                    <li>
                        <span><i class="fa fa-map-marker pin"></i>{$site.name}</span>
                        <span class="count">{$site.clients} clients</span>
                    </li>
                    {/foreach}
                </ul>
                {else}
                <div class="mon-empty"><i class="fa fa-map-marker"></i>Aucun site hotspot</div>
                {/if}
            </div>
        </div>
    </div>
</div>

<script>
var MON_CHART = {$mon.chart|json_encode nofilter};
var MON_BASE_URL = '{$_url}monitoring';
{literal}
(function () {
    function drawSparklines() {
        document.querySelectorAll('.mon-kpi-spark').forEach(function (svg) {
            var raw = svg.getAttribute('data-spark');
            if (!raw) return;
            var data;
            try { data = JSON.parse(raw); } catch (e) { return; }
            if (!data || !data.length) return;
            var max = Math.max.apply(null, data.concat([1]));
            var pts = data.map(function (v, i) {
                var x = (i / (data.length - 1 || 1)) * 80;
                var y = 34 - (v / max) * 28;
                return x + ',' + y;
            }).join(' ');
            var color = svg.closest('.mon-kpi').querySelector('.mon-kpi-label').textContent.indexOf('Hotspot') >= 0 ? '#10b981'
                : svg.closest('.mon-kpi').querySelector('.mon-kpi-label').textContent.indexOf('PPPoE') >= 0 ? '#3b82f6' : '#6366f1';
            svg.innerHTML = '<polyline fill="none" stroke="' + color + '" stroke-width="2" points="' + pts + '"/>';
        });
    }

    function renderChart() {
        var canvas = document.getElementById('monChart');
        if (!canvas || typeof Chart === 'undefined' || !MON_CHART) return;
        var isLight = document.body.classList.contains('theme-light');
        var gridColor = isLight ? 'rgba(15,23,42,0.08)' : 'rgba(148,163,184,0.12)';
        var textColor = isLight ? '#64748b' : '#94a3b8';
        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: MON_CHART.labels || [],
                datasets: [
                    {
                        label: 'Hotspot',
                        data: MON_CHART.hotspot || [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981',
                        borderWidth: 2.5
                    },
                    {
                        label: 'PPPoE',
                        data: MON_CHART.pppoe || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'transparent',
                        borderDash: [6, 4],
                        fill: false,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#3b82f6',
                        borderWidth: 2.5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor, maxTicksLimit: 12 } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 } }
                }
            }
        });
    }

    document.getElementById('monRouter').addEventListener('change', function () {
        var v = this.value;
        window.location.href = MON_BASE_URL + (v ? '?router=' + encodeURIComponent(v) : '');
    });

    document.getElementById('monExportPdf').addEventListener('click', function () {
        window.print();
    });

    drawSparklines();
    renderChart();
})();
{/literal}
</script>

{include file="sections/footer.tpl"}
