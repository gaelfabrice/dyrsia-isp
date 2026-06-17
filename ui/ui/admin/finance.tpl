{include file="sections/header.tpl"}

<style>
{literal}
.fin-page {
    --fin-card: rgba(15, 23, 42, 0.92);
    --fin-text: #e2e8f0;
    --fin-heading: #ffffff;
    --fin-muted: #94a3b8;
    --fin-line: rgba(148, 163, 184, 0.16);
    --fin-brand: #2563eb;
    --fin-green: #22c55e;
    --fin-red: #ef4444;
    --fin-shadow: 0 14px 40px rgba(0, 0, 0, 0.22);
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
    color: var(--fin-text);
    margin: 0 -15px;
    padding: 4px 15px 30px;
}
body.theme-light .fin-page {
    --fin-card: #ffffff;
    --fin-text: #334155;
    --fin-heading: #0f172a;
    --fin-muted: #64748b;
    --fin-line: #e7ebf0;
    --fin-shadow: 0 12px 36px rgba(15, 23, 42, 0.07);
}
.fin-page * { box-sizing: border-box; }
.fin-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.fin-title {
    display: flex;
    align-items: center;
    gap: 10px;
}
.fin-title i { color: #3b82f6; font-size: 22px; }
.fin-title h2 {
    margin: 0;
    font-size: 32px;
    font-weight: 800;
    color: var(--fin-heading);
}
.fin-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.fin-btn {
    border: 1px solid var(--fin-line);
    background: var(--fin-card);
    color: var(--fin-text);
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
}
.fin-btn.fin-btn-primary {
    background: #0f172a;
    color: #fff;
    border-color: transparent;
}
.fin-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 14px;
}
@media (max-width: 1000px) { .fin-kpis { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px) { .fin-kpis { grid-template-columns: 1fr; } }
.fin-kpi {
    background: var(--fin-card);
    border: 1px solid var(--fin-line);
    border-radius: 18px;
    padding: 16px 18px;
    box-shadow: var(--fin-shadow);
}
.fin-kpi-head {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--fin-muted);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.fin-kpi-head i { width: 24px; text-align: center; }
.fin-kpi-value {
    margin-top: 8px;
    font-size: 38px;
    font-weight: 800;
    color: var(--fin-heading);
    line-height: 1;
}
.fin-kpi-sub {
    margin-top: 8px;
    font-size: 13px;
    color: var(--fin-muted);
}
.fin-trend-up { color: var(--fin-green); font-weight: 700; }
.fin-trend-down { color: var(--fin-red); font-weight: 700; }

.fin-chart-card {
    background: var(--fin-card);
    border: 1px solid var(--fin-line);
    border-radius: 18px;
    box-shadow: var(--fin-shadow);
    overflow: hidden;
    margin-bottom: 14px;
}
.fin-chart-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    border-bottom: 1px solid var(--fin-line);
}
.fin-chart-head h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: var(--fin-heading);
}
.fin-range {
    display: inline-flex;
    gap: 8px;
}
.fin-pill {
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--fin-line);
    color: var(--fin-muted);
}
.fin-pill.active {
    background: #0f172a;
    color: #fff;
    border-color: transparent;
}
.fin-chart-body {
    padding: 12px 16px 16px;
    height: 320px;
}

.fin-cta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    background: var(--fin-card);
    border: 1px solid var(--fin-line);
    border-radius: 18px;
    padding: 16px 20px;
    box-shadow: var(--fin-shadow);
}
.fin-cta-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}
.fin-cta-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(59, 130, 246, 0.12);
    color: #60a5fa;
    font-size: 18px;
    flex-shrink: 0;
}
.fin-cta-copy { min-width: 0; }
.fin-cta-title { font-weight: 800; color: var(--fin-heading); font-size: 15px; }
.fin-cta-sub { font-size: 13px; color: var(--fin-muted); margin-top: 3px; }
.fin-cta-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.fin-cta-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(245, 158, 11, 0.14);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.28);
}
body.theme-light .fin-cta-badge {
    background: #fffbeb;
    color: #b45309;
    border-color: #fde68a;
}
.fin-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 12px;
    background: #0f172a;
    color: #fff;
    border: 1px solid transparent;
    text-decoration: none;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 700;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
    white-space: nowrap;
}
body.theme-light .fin-cta-btn {
    background: #0f172a;
    color: #fff;
}
.fin-cta-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
    color: #fff;
    text-decoration: none;
}
{/literal}
</style>

<div class="fin-page">
    <div class="fin-top">
        <div class="fin-title">
            <i class="fa fa-database"></i>
            <h2>{Lang::T('Finance')}</h2>
        </div>
        <div class="fin-actions">
            <span class="fin-btn"><i class="fa fa-calendar"></i> Aujourd'hui {$finance_now_label|default:''}</span>
            <button class="fin-btn" type="button" onclick="window.print()"><i class="fa fa-download"></i> Exporter</button>
            <a href="{Text::url('finance')}&refresh=1" class="fin-btn fin-btn-primary"><i class="fa fa-refresh"></i> Actualiser</a>
        </div>
    </div>

    <div class="fin-kpis">
        <a href="{Text::url('plugin/admin_wallet')}" class="fin-kpi" style="text-decoration:none;color:inherit">
            <div class="fin-kpi-head"><i class="fa fa-credit-card"></i> Wallet Balance</div>
            <div class="fin-kpi-value">{$_c['currency_code']} {number_format($w_balance|default:0,0)}</div>
            <div class="fin-kpi-sub"><span class="{if $w_balance|default:0 >= 0}fin-trend-up{else}fin-trend-down{/if}">Stable</span> &nbsp; solde actuel</div>
        </a>
        <a href="{Text::url('plugin/admin_wallet_commission')}" class="fin-kpi" style="text-decoration:none;color:inherit">
            <div class="fin-kpi-head"><i class="fa fa-money"></i> Wallet Commission</div>
            <div class="fin-kpi-value">{$_c['currency_code']} {number_format($w_commission|default:0,0)}</div>
            <div class="fin-kpi-sub"><span class="fin-trend-up">0%</span> &nbsp; commissions cumulées</div>
        </a>
        <a href="{Text::url('reports/by-date')}" class="fin-kpi" style="text-decoration:none;color:inherit">
            <div class="fin-kpi-head"><i class="fa fa-sun-o"></i> Income Daily</div>
            <div class="fin-kpi-value">{$_c['currency_code']} {number_format($iday|default:0,0)}</div>
            <div class="fin-kpi-sub">
                <span class="{if $growth_daily_pct|default:0 >= 0}fin-trend-up{else}fin-trend-down{/if}">
                    {if $growth_daily_pct|default:0 > 0}+{/if}{$growth_daily_pct|default:0}%
                </span> &nbsp; vs hier
            </div>
        </a>
        <a href="{Text::url('reports/by-period')}" class="fin-kpi" style="text-decoration:none;color:inherit">
            <div class="fin-kpi-head"><i class="fa fa-calendar"></i> Income Monthly</div>
            <div class="fin-kpi-value">{$_c['currency_code']} {number_format($imonth|default:0,0)}</div>
            <div class="fin-kpi-sub">
                <span class="{if $growth_monthly_pct|default:0 >= 0}fin-trend-up{else}fin-trend-down{/if}">
                    {if $growth_monthly_pct|default:0 > 0}+{/if}{$growth_monthly_pct|default:0}%
                </span> &nbsp; vs mois dernier
            </div>
        </a>
    </div>

    <div class="fin-chart-card">
        <div class="fin-chart-head">
            <h3><i class="fa fa-area-chart"></i> Évolution des revenus</h3>
            <div class="fin-range">
                <span class="fin-pill active">12 mois</span>
                <span class="fin-pill">6 mois</span>
                <span class="fin-pill">3 mois</span>
                <span class="fin-pill">Ce mois</span>
            </div>
        </div>
        <div class="fin-chart-body">
            <canvas id="financeRevenueChart"></canvas>
        </div>
    </div>

    {if $_admin['user_type'] eq 'Admin'}
    <div class="fin-cta">
        <div class="fin-cta-left">
            <div class="fin-cta-icon"><i class="fa fa-money"></i></div>
            <div class="fin-cta-copy">
                <div class="fin-cta-title">Retraits</div>
                <div class="fin-cta-sub">Demande de retrait — solde passerelle, profil Mobile Money, historique</div>
            </div>
        </div>
        <div class="fin-cta-actions">
            <a href="{Text::url('finance/withdrawals')}" class="fin-cta-btn"><i class="fa fa-arrow-right"></i> Ouvrir Retraits</a>
        </div>
    </div>
    {/if}
    {if $_admin['user_type'] eq 'SuperAdmin'}
    <div class="fin-cta">
        <div class="fin-cta-left">
            <div class="fin-cta-icon"><i class="fa fa-refresh"></i></div>
            <div class="fin-cta-copy">
                <div class="fin-cta-title">Reversement</div>
                <div class="fin-cta-sub">Valider ou rejeter les demandes de retrait des clients</div>
            </div>
        </div>
        <div class="fin-cta-actions">
            {if $withdrawal_pending_count|default:0 > 0}
            <span class="fin-cta-badge"><i class="fa fa-clock-o"></i> {$withdrawal_pending_count} en attente</span>
            {/if}
            <a href="{Text::url('finance/reversement')}" class="fin-cta-btn"><i class="fa fa-arrow-right"></i> Ouvrir Reversement</a>
        </div>
    </div>
    {/if}
</div>

<script>
var FINANCE_CHART = {
    labels: {$finance_month_labels|@json_encode nofilter},
    revenue: {$finance_month_revenue|@json_encode nofilter},
    commission: {$finance_month_commission|@json_encode nofilter}
};
{literal}
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    var chartEl = document.getElementById('financeRevenueChart');
    if (!chartEl) return;
    var isLight = document.body.classList.contains('theme-light');
    var gridColor = isLight ? 'rgba(15,23,42,0.08)' : 'rgba(148,163,184,0.12)';
    var textColor = isLight ? '#64748b' : '#94a3b8';
    new Chart(chartEl.getContext('2d'), {
        type: 'line',
        data: {
            labels: FINANCE_CHART.labels || [],
            datasets: [
                {
                    label: 'Revenus mensuels',
                    data: FINANCE_CHART.revenue || [],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3.5,
                    pointBackgroundColor: '#2563eb',
                    borderWidth: 2.5
                },
                {
                    label: 'Commissions',
                    data: FINANCE_CHART.commission || [],
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,0.08)',
                    fill: false,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: '#16a34a',
                    borderDash: [5, 4],
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: textColor, font: { size: 12, weight: '600' } } }
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: textColor } },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        callback: function (v) { return v.toLocaleString() + ' XAF'; }
                    }
                }
            }
        }
    });
});
{/literal}
</script>

{include file="sections/footer.tpl"}
