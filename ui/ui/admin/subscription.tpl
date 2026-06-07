{include file="sections/header.tpl"}

<link rel="stylesheet" href="{$app_url}/ui/ui/fonts/inter/inter.css">

<style>
{literal}
/* Variables — mode sombre (défaut page) */
.sub-page {
    --sub-bg: #030712;
    --sub-text: #f8fafc;
    --sub-heading: #ffffff;
    --sub-muted: #94a3b8;
    --sub-line: rgba(148, 163, 184, 0.2);
    --sub-brand: #16a34a;
    --sub-brand2: #0891b2;
    --sub-card: rgba(15, 23, 42, 0.92);
    --sub-feature: #cbd5e1;
    --sub-table-th: rgba(0, 0, 0, 0.15);
    --sub-table-td: #e2e8f0;
    --sub-table-empty: transparent;
    --sub-pricing-bg: #030712;
    --sub-btn-outline-bg: rgba(255, 255, 255, 0.08);
    --sub-btn-outline-text: #ffffff;
    --sub-btn-outline-border: rgba(148, 163, 184, 0.35);
    --sub-modal-bg: #111827;
    --sub-modal-input: #020617;
    --sub-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    font-family: Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    color: var(--sub-text);
    margin: -15px -15px 0;
    padding: 0 15px 24px;
    background: transparent;
}
/* Mode soleil (theme-light) */
body.theme-light .sub-page {
    --sub-bg: #f1f5f9;
    --sub-text: #1e293b;
    --sub-heading: #0f172a;
    --sub-muted: #64748b;
    --sub-line: #e2e8f0;
    --sub-brand: #059669;
    --sub-brand2: #0d9488;
    --sub-card: #ffffff;
    --sub-feature: #475569;
    --sub-table-th: #f8fafc;
    --sub-table-td: #334155;
    --sub-table-empty: #ffffff;
    --sub-pricing-bg: #ffffff;
    --sub-btn-outline-bg: #ffffff;
    --sub-btn-outline-text: #0f172a;
    --sub-btn-outline-border: #cbd5e1;
    --sub-modal-bg: #ffffff;
    --sub-modal-input: #f8fafc;
    --sub-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
}
.sub-page * { box-sizing: border-box; }

/* Bandeau statut */
.sub-hero {
    border-radius: 16px;
    padding: 20px 22px;
    margin-bottom: 20px;
    border: 1px solid var(--sub-line);
    background: var(--sub-card);
}
.sub-hero.trial { border-color: rgba(249, 115, 22, 0.35); background: linear-gradient(135deg, rgba(249,115,22,.08), var(--sub-card)); }
.sub-hero.active { border-color: rgba(34, 197, 94, 0.35); background: linear-gradient(135deg, rgba(34,197,94,.08), var(--sub-card)); }
.sub-hero.grace { border-color: rgba(217, 119, 6, 0.35); }
.sub-hero.expired { border-color: rgba(220, 38, 38, 0.35); }
.sub-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.sub-badge.trial { background: #f97316; color: #fff; }
.sub-badge.active { background: #16a34a; color: #fff; }
.sub-badge.grace { background: #d97706; color: #fff; }
.sub-badge.expired { background: #dc2626; color: #fff; }
.sub-hero h3 { margin: 12px 0 6px; font-size: 18px; font-weight: 800; color: var(--sub-heading); }
.sub-hero p { margin: 0; font-size: 14px; line-height: 1.55; color: var(--sub-muted); }
body.theme-light .sub-hero.trial { background: linear-gradient(135deg, #fff7ed, #ffffff); border-color: #fed7aa; }
body.theme-light .sub-hero.active { background: linear-gradient(135deg, #ecfdf5, #ffffff); border-color: #a7f3d0; }
body.theme-light .sub-hero.grace { background: linear-gradient(135deg, #fffbeb, #ffffff); border-color: #fde68a; }
body.theme-light .sub-hero.expired { background: linear-gradient(135deg, #fef2f2, #ffffff); border-color: #fecaca; }
.sub-hero-strong { color: #fde68a; }
.sub-hero-strong--ok { color: #4ade80; }
body.theme-light .sub-hero-strong { color: #c2410c; }
body.theme-light .sub-hero-strong--ok { color: #059669; }
body.theme-light .sub-hero p strong:not(.sub-hero-strong) { color: var(--sub-heading); }

/* Stats */
.sub-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
@media (max-width: 991px) { .sub-stats { grid-template-columns: repeat(2, 1fr); } }
.sub-stat {
    border-radius: 14px;
    padding: 16px 18px;
    background: var(--sub-card);
    border: 1px solid var(--sub-line);
}
.sub-stat h3 { margin: 0; font-size: 26px; font-weight: 900; color: var(--sub-heading); line-height: 1; }
.sub-stat p { margin: 6px 0 0; font-size: 12px; color: var(--sub-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
body.theme-light .sub-stat {
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}

/* Alertes */
.sub-alert {
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 16px;
    font-size: 14px;
    line-height: 1.5;
}
.sub-alert.info { background: rgba(37, 99, 235, 0.12); border: 1px solid rgba(59, 130, 246, 0.35); color: #bfdbfe; }
.sub-alert.warn { background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.35); color: #fde68a; }
.sub-alert.demo { background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.35); color: #fed7aa; display: none; }
.sub-alert.demo.show { display: block; }
.sub-alert.pay { display: none; background: rgba(37, 99, 235, 0.12); border: 1px solid rgba(59, 130, 246, 0.35); color: #bfdbfe; }
.sub-alert.pay.show { display: block; }
body.theme-light .sub-alert.info,
body.theme-light .sub-alert.pay { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
body.theme-light .sub-alert.warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
body.theme-light .sub-alert.demo { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }

/* Section tarifs — même style que l'accueil */
.sub-pricing-wrap {
    border-radius: 20px;
    padding: 28px 24px 32px;
    background:
        radial-gradient(ellipse 70% 50% at 50% 0%, rgba(34, 197, 94, 0.1), transparent),
        var(--sub-pricing-bg);
    border: 1px solid var(--sub-line);
    margin-bottom: 28px;
    overflow: hidden;
    box-shadow: var(--sub-shadow);
}
body.theme-light .sub-pricing-wrap {
    background:
        radial-gradient(ellipse 80% 60% at 50% -10%, rgba(16, 185, 129, 0.08), transparent),
        var(--sub-pricing-bg);
}
.sub-pricing-head { text-align: center; margin-bottom: 28px; }
.sub-pricing-head h2 { margin: 0 0 8px; font-size: 22px; font-weight: 900; color: var(--sub-heading); }
.sub-pricing-head p { margin: 0; color: var(--sub-muted); font-size: 14px; }

.sub-pricing-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    align-items: start;
}
@media (max-width: 767px) {
    .sub-pricing-grid { grid-template-columns: 1fr; max-width: 420px; margin: 0 auto; }
}

.sub-pricing-card {
    position: relative;
    display: block;
    padding: 32px 24px 24px;
    border-radius: 22px;
    background: var(--sub-card);
    border: 1px solid var(--sub-line);
    overflow: visible;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
body.theme-light .sub-pricing-card {
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
}
.sub-pricing-card.featured {
    border: 2px solid var(--sub-brand);
    background: linear-gradient(180deg, rgba(34, 197, 94, 0.08) 0%, var(--sub-card) 100%);
}
body.theme-light .sub-pricing-card.featured {
    background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 100%);
}
.sub-pricing-card.selected {
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.35);
    border-color: var(--sub-brand2);
}

.sub-pricing-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    padding: 6px 16px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    background: linear-gradient(135deg, var(--sub-brand), var(--sub-brand2));
    color: #021014;
    white-space: nowrap;
    z-index: 2;
}

.sub-pricing-card h3 {
    margin: 0 0 14px;
    font-size: 20px;
    font-weight: 800;
    color: var(--sub-heading);
    text-align: center;
}
.sub-pricing-price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 6px;
    padding-bottom: 18px;
    margin-bottom: 18px;
    border-bottom: 1px solid var(--sub-line);
}
.sub-pricing-price .amount {
    font-size: 32px;
    font-weight: 900;
    color: var(--sub-brand);
    line-height: 1;
}
.sub-pricing-price .period { font-size: 14px; color: var(--sub-muted); }
.sub-pricing-note { text-align: center; font-size: 12px; color: var(--sub-muted); font-style: italic; margin: -10px 0 14px; }

.sub-page .sub-pricing-features {
    list-style: none !important;
    list-style-type: none !important;
    padding: 0 !important;
    margin: 0 0 20px !important;
}
.sub-page .sub-pricing-features li {
    display: flex !important;
    align-items: flex-start !important;
    gap: 10px !important;
    padding: 10px 0 !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    color: var(--sub-feature) !important;
    border-bottom: 1px solid var(--sub-line) !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    overflow: visible !important;
}
.sub-page .sub-pricing-features li:last-child { border-bottom: 0 !important; }
.sub-page .sub-pricing-features li span {
    display: block !important;
    color: var(--sub-feature) !important;
    font-size: 14px !important;
    line-height: 1.5 !important;
    visibility: visible !important;
}
.sub-page .sub-pricing-features li .fa {
    color: #22c55e !important;
    margin-top: 3px !important;
    flex-shrink: 0 !important;
    font-size: 14px !important;
}

.sub-page button.sub-btn-pay {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    width: 100% !important;
    margin: 8px 0 0 !important;
    padding: 14px 20px !important;
    border-radius: 999px !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    letter-spacing: 0.02em !important;
    cursor: pointer !important;
    pointer-events: auto !important;
    position: relative !important;
    z-index: 5 !important;
    visibility: visible !important;
    opacity: 1 !important;
    -webkit-appearance: none !important;
    appearance: none !important;
    transition: filter 0.15s, transform 0.15s;
}
.sub-page button.sub-btn-pay:hover { filter: brightness(1.08); transform: translateY(-1px); }
.sub-page button.sub-btn-pay.primary {
    color: #021014 !important;
    background: linear-gradient(135deg, #22c55e, #06b6d4) !important;
    border: none !important;
}
.sub-page button.sub-btn-pay.outline {
    color: var(--sub-btn-outline-text) !important;
    background: var(--sub-btn-outline-bg) !important;
    border: 1px solid var(--sub-btn-outline-border) !important;
}
body.theme-light .sub-page button.sub-btn-pay.outline:hover {
    background: #f8fafc !important;
    border-color: var(--sub-brand) !important;
    color: var(--sub-brand) !important;
}
.sub-page button.sub-btn-pay.is-disabled {
    opacity: 0.55 !important;
    cursor: not-allowed !important;
}

/* Historique */
.sub-history {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}
@media (max-width: 991px) { .sub-history { grid-template-columns: 1fr; } }
.sub-panel {
    border-radius: 16px;
    background: var(--sub-card);
    border: 1px solid var(--sub-line);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}
body.theme-light .sub-panel {
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
}
.sub-panel-head {
    padding: 14px 18px;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--sub-muted);
    border-bottom: 1px solid var(--sub-line);
}
.sub-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.sub-table th {
    padding: 10px 14px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sub-muted);
    border-bottom: 1px solid var(--sub-line);
    background: var(--sub-table-th) !important;
}
.sub-table td {
    padding: 10px 14px;
    color: var(--sub-table-td) !important;
    border-bottom: 1px solid var(--sub-line);
    background: var(--sub-card) !important;
}
.sub-table tbody tr:hover td {
    background: rgba(148, 163, 184, 0.06) !important;
}
body.theme-light .sub-table tbody tr:hover td {
    background: #f8fafc !important;
}
.sub-table tr:last-child td { border-bottom: 0; }
.sub-table .empty {
    text-align: center;
    color: var(--sub-muted) !important;
    padding: 28px !important;
    background: var(--sub-table-empty) !important;
}
.sub-label {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}
.sub-label.paid, .sub-label.success { background: rgba(34,197,94,.2); color: #4ade80; }
.sub-label.unpaid, .sub-label.pending { background: rgba(234,179,8,.15); color: #fbbf24; }
.sub-label.failed, .sub-label.cancelled { background: rgba(239,68,68,.15); color: #f87171; }
body.theme-light .sub-label.paid,
body.theme-light .sub-label.success { background: #d1fae5; color: #047857; }
body.theme-light .sub-label.unpaid,
body.theme-light .sub-label.pending { background: #fef3c7; color: #b45309; }
body.theme-light .sub-label.failed,
body.theme-light .sub-label.cancelled { background: #fee2e2; color: #b91c1c; }

/* Modal CamPay — attaché à <body> pour éviter le décalage AdminLTE (transform/overflow) */
#campayModal.campay-modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    padding: 20px !important;
    z-index: 200000 !important;
    background: rgba(2, 6, 23, 0.85) !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    box-sizing: border-box !important;
}
#campayModal.campay-modal-backdrop.show {
    display: flex !important;
}
#campayModal .campay-modal {
    position: relative !important;
    width: min(92vw, 400px) !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    margin: 0 auto !important;
    flex-shrink: 0 !important;
    background: var(--sub-modal-bg, #111827) !important;
    color: var(--sub-text, #f8fafc) !important;
    border: 1px solid rgba(16, 185, 129, 0.35) !important;
    border-radius: 20px !important;
    padding: 24px !important;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.55) !important;
}
body.theme-light #campayModal.campay-modal-backdrop {
    background: rgba(15, 23, 42, 0.45) !important;
}
body.theme-light #campayModal .campay-modal {
    background: #ffffff !important;
    color: #1e293b !important;
    border-color: #a7f3d0 !important;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.15) !important;
}
body.theme-light #campayModal .campay-modal h4 { color: #0f172a !important; }
body.theme-light #campayModal .campay-modal > p,
body.theme-light #campayModal .campay-field-label { color: #64748b !important; }
body.theme-light #campayModal .campay-phone-prefix,
body.theme-light #campayModal .campay-phone-wrap input,
body.theme-light #campayModal #proRoutersInput {
    background: #f8fafc !important;
    color: #0f172a !important;
    border-color: #e2e8f0 !important;
}
body.theme-light #campayModal .campay-amount { color: #059669 !important; }
.campay-modal h4 { margin: 0 0 6px; font-size: 18px; font-weight: 800; color: #f8fafc; }
.campay-modal > p { margin: 0 0 16px; color: #94a3b8; font-size: 13px; }
.campay-phone-wrap {
    display: flex; align-items: stretch;
    border: 1px solid var(--sub-line, rgba(148, 163, 184, 0.28));
    border-radius: 12px; overflow: hidden; margin-bottom: 14px;
}
.campay-phone-prefix {
    padding: 12px 10px; background: var(--sub-modal-input, #020617); color: var(--sub-muted, #94a3b8);
    font-weight: 700; border-right: 1px solid var(--sub-line, rgba(148, 163, 184, 0.2));
}
.campay-phone-wrap input {
    flex: 1; border: 0; background: var(--sub-modal-input, #020617); color: var(--sub-heading, #fff);
    padding: 12px; font-size: 16px; outline: none;
}
.campay-field-label { display: block; font-size: 11px; font-weight: 700; color: var(--sub-muted, #94a3b8); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
.campay-amount { font-size: 16px; font-weight: 800; color: var(--sub-brand, #4ade80); margin: 0 0 16px; }
.campay-modal-actions { display: flex; gap: 10px; }
.campay-modal-actions button {
    flex: 1; padding: 12px; border: 0; border-radius: 999px; font-weight: 800; cursor: pointer; font-size: 13px;
}
.campay-btn-cancel { background: #374151; color: #fff; }
body.theme-light .campay-btn-cancel { background: #e2e8f0; color: #334155; }
.campay-btn-pay { background: linear-gradient(135deg, #22c55e, #06b6d4); color: #021014; }
#proRoutersInput {
    width: 100%; padding: 11px 12px; border-radius: 10px;
    border: 1px solid var(--sub-line, rgba(148,163,184,.28));
    background: var(--sub-modal-input, #020617); color: var(--sub-heading, #fff); margin-bottom: 14px;
}
/* Isolation AdminLTE — évite fonds/table sombres en mode soleil */
body.theme-light .sub-page .table,
body.theme-light .sub-page .sub-table {
    background: transparent !important;
}
body.theme-light .sub-page .sub-table > thead > tr > th,
body.theme-light .sub-page .sub-table > tbody > tr > td {
    border-color: var(--sub-line) !important;
}
@keyframes subPaySpin {
    to { transform: rotate(360deg); }
}
{/literal}
</style>

<div class="sub-page">

    <div class="sub-hero {$subscription->status}">
        <span class="sub-badge {$subscription->status}">
            {if $subscription->status eq 'trial'}Mode Démo{elseif $subscription->status eq 'active'}Actif{elseif $subscription->status eq 'grace'}Grâce{else}Expiré{/if}
        </span>
        <h3>{Lang::T('Current Subscription')}</h3>
        {if $subscription->status eq 'trial'}
            <p><strong class="sub-hero-strong">Mode Démo</strong> — {$subscription_days_remaining} jour(s) restant(s) sur {$demo_trial_days|default:5} jours. Exploration sans routeur réel. Choisissez un forfait ci-dessous pour activer votre réseau.</p>
        {elseif $subscription->status eq 'active'}
            <p>Abonnement <strong class="sub-hero-strong sub-hero-strong--ok">{if $subscription->plan_type eq 'pro'}Forfait Pro{else}Forfait Business{/if}</strong> actif jusqu'au <strong>{$subscription->subscription_end}</strong>.</p>
        {elseif $subscription->status eq 'grace'}
            <p>Période de grâce — expire le <strong>{$subscription->grace_end}</strong>. Renouvelez pour éviter l'interruption.</p>
        {else}
            <p>Abonnement expiré. Choisissez un forfait et payez pour réactiver votre compte.</p>
        {/if}
    </div>

    <div class="sub-stats">
        <div class="sub-stat"><h3>{$subscription_stats.routers|default:$router_count}</h3><p>{Lang::T('Routers')}</p></div>
        <div class="sub-stat"><h3>{$subscription_stats.router_limit|default:'—'}</h3><p>{Lang::T('Router Limit')}</p></div>
        <div class="sub-stat"><h3>{$subscription_stats.invoice_total|default:0|number_format:0}</h3><p>{Lang::T('Invoices')}</p></div>
        <div class="sub-stat"><h3>{$subscription_stats.paid_total|default:0|number_format:0}</h3><p>{Lang::T('Paid')} (F CFA)</p></div>
    </div>

    {if $checkout_plan ne '' && $auto_checkout}
    <div class="sub-alert info" id="checkoutBanner">
        <strong><i class="fa fa-star"></i> Forfait sélectionné : {if $checkout_plan eq 'pro'}Forfait Pro{else}Forfait Business{/if}</strong>
        <p style="margin:6px 0 0">Entrez votre numéro et validez le paiement, ou annulez pour rester en Mode Démo.</p>
    </div>
    {/if}

    <div id="demoAckBox" class="sub-alert demo{if $smarty.get.demo_ack eq '1'} show{/if}">
        <strong><i class="fa fa-info-circle"></i> Mode Démo conservé</strong>
        <p style="margin:6px 0 0">Votre compte reste en Mode Démo ({$demo_trial_days|default:5} jours). Vous pouvez payer à tout moment.</p>
    </div>

    {if !$campay_configured}
    <div class="sub-alert warn"><i class="fa fa-warning"></i> CamPay non configuré — contactez le SuperAdmin pour activer Mobile Money.</div>
    {/if}

    <div class="sub-pricing-wrap">
        <div class="sub-pricing-head">
            <h2>{if $subscription->status eq 'active' || $subscription->status eq 'grace'}Renouveler votre forfait{else}Choisir un forfait{/if}</h2>
            <p>Paiement sécurisé MTN MoMo / Orange Money via CamPay{if $isp_settings_updated_at} — tarifs SuperAdmin du {$isp_settings_updated_at}{/if}</p>
        </div>

        <div class="sub-pricing-grid">
            <div class="sub-pricing-card featured{if $checkout_plan eq 'business'} selected{/if}" id="plan-card-business">
                <span class="sub-pricing-badge">Le plus populaire</span>
                <h3>Forfait Business</h3>
                <div class="sub-pricing-price">
                    <span class="amount">{$subscription_settings.business_price|number_format:0}</span>
                    <span class="period">F CFA / mois</span>
                </div>
                <ul class="sub-pricing-features">
                    <li><i class="fa fa-check"></i><span>Jusqu'à 3 routeurs inclus</span></li>
                    <li><i class="fa fa-check"></i><span>Commission 10% sur ventes Hotspot et PPPoE</span></li>
                    <li><i class="fa fa-check"></i><span>Configuration simplifiée — nous gérons les encaissements</span></li>
                    <li><i class="fa fa-check"></i><span>Idéal pour les petites installations de quartier</span></li>
                </ul>
                <button type="button" class="sub-btn-pay primary{if !$campay_configured} is-disabled{/if}" data-plan="business" data-campay="{if $campay_configured}1{else}0{/if}">
                    <i class="fa fa-credit-card"></i> Payer — Forfait Business
                </button>
            </div>

            <div class="sub-pricing-card{if $checkout_plan eq 'pro'} selected{/if}" id="plan-card-pro">
                <h3>Forfait Pro</h3>
                <div class="sub-pricing-price">
                    <span class="amount">{$subscription_settings.pro_price_per_router|number_format:0}</span>
                    <span class="period">F CFA / routeur / mois</span>
                </div>
                <p class="sub-pricing-note">Nombre de routeurs défini au moment du paiement</p>
                <ul class="sub-pricing-features">
                    <li><i class="fa fa-check"></i><span>0% de commission sur toutes vos ventes</span></li>
                    <li><i class="fa fa-check"></i><span>Intégration de votre propre API de paiement</span></li>
                    <li><i class="fa fa-check"></i><span>Reversement direct sur votre compte</span></li>
                    <li><i class="fa fa-check"></i><span>Conçu pour FAI locaux et fort volume</span></li>
                </ul>
                <button type="button" class="sub-btn-pay outline{if !$campay_configured} is-disabled{/if}" data-plan="pro" data-campay="{if $campay_configured}1{else}0{/if}">
                    <i class="fa fa-bolt"></i> Payer — Forfait Pro
                </button>
            </div>
        </div>
    </div>

    <div class="sub-history">
        <div class="sub-panel">
            <div class="sub-panel-head">{Lang::T('Invoices')}</div>
            <table class="sub-table">
                <thead><tr><th>#</th><th>Plan</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead>
                <tbody>
                {foreach $subscription_invoices as $invoice}
                    <tr>
                        <td>{$invoice.invoice_no}</td>
                        <td>{if $invoice.plan_type eq 'pro'}Pro{else}Business{/if}</td>
                        <td>{$invoice.amount|number_format:0} F</td>
                        <td><span class="sub-label {if $invoice.status eq 'paid'}paid{elseif $invoice.status eq 'unpaid'}unpaid{else}failed{/if}">{$invoice.status}</span></td>
                        <td>{$invoice.created_at}</td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="5" class="empty">{Lang::T('No data')}</td></tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        <div class="sub-panel">
            <div class="sub-panel-head">{Lang::T('Payment History')}</div>
            <table class="sub-table">
                <thead><tr><th>Montant</th><th>Méthode</th><th>Réf.</th><th>Statut</th><th>Date</th></tr></thead>
                <tbody>
                {foreach $subscription_payments as $payment}
                    <tr>
                        <td>{$payment.amount|number_format:0} F</td>
                        <td>{$payment.method}</td>
                        <td>{$payment.reference|truncate:16}</td>
                        <td><span class="sub-label {if $payment.status eq 'paid'}paid{elseif $payment.status eq 'pending'}pending{else}failed{/if}">{$payment.status}</span></td>
                        <td>{$payment.created_at}</td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="5" class="empty">{Lang::T('No data')}</td></tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="campayModal" class="campay-modal-backdrop" aria-hidden="true">
    <div class="campay-modal">
        <h4 id="campayModalTitle">Paiement Mobile Money</h4>
        <p id="campayModalDesc">MTN MoMo ou Orange Money — confirmez sur votre téléphone</p>
        <form method="post" action="{Text::url('admin/subscription-pay')}" id="campayPayForm">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <input type="hidden" name="plan_type" id="campayPlanType" value="">
            <input type="hidden" name="routers_count" id="campayRoutersCount" value="1">
            <label class="campay-field-label" for="campayPhone">Numéro de téléphone</label>
            <div class="campay-phone-wrap">
                <span class="campay-phone-prefix">+237</span>
                <input type="tel" name="phone" id="campayPhone" inputmode="numeric" autocomplete="tel-national" placeholder="6XX XXX XXX" maxlength="9" value="{$admin_phone_local}" required>
            </div>
            <div id="proRoutersGroup" style="display:none">
                <label class="campay-field-label" for="proRoutersInput">Nombre de routeurs (Pro)</label>
                <input type="number" min="1" id="proRoutersInput" value="{if $router_count > 0}{$router_count}{else}1{/if}">
            </div>
            <p id="campayAmountPreview" class="campay-amount"></p>
            <div class="campay-modal-actions">
                <button type="button" class="campay-btn-cancel" id="campayCancelBtn">Annuler</button>
                <button type="submit" class="campay-btn-pay" id="campaySubmitBtn"><i class="fa fa-mobile"></i> Payer</button>
            </div>
        </form>
    </div>
</div>

<script>
var WZ_SUB = {
    businessPrice: {$subscription_settings.business_price|default:0},
    proPrice: {$subscription_settings.pro_price_per_router|default:0},
    pendingPaymentId: {$pending_payment_id|default:0},
    pendingOperator: '{$pending_operator|escape:'javascript'}',
    pendingUssd: '{$pending_ussd_code|escape:'javascript'}',
    pendingAmount: {$pending_amount|default:0},
    pendingPlanLabel: '{$pending_plan_label|escape:'javascript'}',
    verifyUrl: '{$subscription_verify_url|escape:'javascript'}',
    payUrl: '{$subscription_pay_url|escape:'javascript'}',
    csrfToken: '{$csrf_token|escape:'javascript'}',
    demoAckUrl: '{$subscription_demo_ack_url|escape:'javascript'}',
    autoCheckout: {if $auto_checkout}true{else}false{/if},
    checkoutPlan: '{$checkout_plan|escape:'javascript'}',
    campayOk: {if $campay_configured}true{else}false{/if}
};
{literal}
(function(){
    var businessPrice = WZ_SUB.businessPrice;
    var proPrice = WZ_SUB.proPrice;
    var pendingPaymentId = WZ_SUB.pendingPaymentId;
    var pendingOperator = WZ_SUB.pendingOperator;
    var pendingUssd = WZ_SUB.pendingUssd;
    var pendingAmount = WZ_SUB.pendingAmount;
    var pendingPlanLabel = WZ_SUB.pendingPlanLabel;
    var verifyUrl = WZ_SUB.verifyUrl;
    var payUrl = WZ_SUB.payUrl;
    var csrfToken = WZ_SUB.csrfToken;
    var demoAckUrl = WZ_SUB.demoAckUrl;
    var autoCheckout = WZ_SUB.autoCheckout;
    var checkoutPlan = WZ_SUB.checkoutPlan;
    var PAY_WAIT_SECONDS = 60;

    var modal = document.getElementById('campayModal');
    if (modal && modal.parentNode !== document.body) {
        document.body.appendChild(modal);
    }
    var planInput = document.getElementById('campayPlanType');
    var routersInput = document.getElementById('campayRoutersCount');
    var proGroup = document.getElementById('proRoutersGroup');
    var proRoutersInput = document.getElementById('proRoutersInput');
    var amountPreview = document.getElementById('campayAmountPreview');
    var phoneInput = document.getElementById('campayPhone');
    var payForm = document.getElementById('campayPayForm');
    var payCountdownTimer = null;

    function formatAmount(n){ return (n||0).toLocaleString('fr-FR') + ' F CFA'; }
    function updateAmount(){
        var plan = planInput.value;
        var routers = plan === 'pro' ? Math.max(1, parseInt(proRoutersInput.value,10)||1) : 1;
        routersInput.value = routers;
        amountPreview.textContent = 'Montant à payer : ' + formatAmount(plan === 'pro' ? proPrice * routers : businessPrice);
    }
    function openModal(plan){
        planInput.value = plan;
        proGroup.style.display = plan === 'pro' ? 'block' : 'none';
        document.getElementById('campayModalTitle').textContent = plan === 'pro' ? 'Payer — Forfait Pro' : 'Payer — Forfait Business';
        updateAmount();
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        phoneInput.focus();
    }
    function closeModal(){
        if (modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function buildUssdWaitHtml(planLabel, amount, operator, ussd, secondsLeft) {
        return '<div style="text-align:center;padding:4px 2px">' +
            '<div style="width:58px;height:58px;margin:0 auto 16px;border:4px solid rgba(16,185,129,.18);border-top-color:#10b981;border-radius:50%;animation:subPaySpin 1s linear infinite"></div>' +
            '<p style="font-size:15px;line-height:1.5;margin:0 0 8px"><strong>Validez la transaction sur votre téléphone</strong></p>' +
            '<p style="font-size:14px;color:#64748b;margin:0 0 10px">' + (planLabel || 'Forfait') + ' — <strong>' + formatAmount(amount) + '</strong></p>' +
            '<div style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);border-radius:14px;padding:14px;margin:12px 0;text-align:left;font-size:14px;line-height:1.55">' +
            '📲 Une notification USSD va s\'afficher' + (operator ? ' (<strong>' + operator + '</strong>)' : '') + '.<br>Confirmez avec votre <strong>code PIN</strong>.' +
            (ussd ? '<br><br>Sinon composez : <strong style="color:#10b981;font-size:20px">' + ussd + '</strong>' : '') +
            '</div>' +
            '<p id="subPayCountdown" style="font-size:13px;color:#64748b;margin:12px 0 0">Temps restant : ' + secondsLeft + ' secondes…</p>' +
            '<p style="font-size:12px;color:#94a3b8;margin:8px 0 0">Ne fermez pas cette fenêtre pendant la validation.</p></div>';
    }

    function clearPayCountdown() {
        if (payCountdownTimer) { clearInterval(payCountdownTimer); payCountdownTimer = null; }
    }

    function pollSubscriptionPayment(paymentId, maxSeconds) {
        var deadline = Date.now() + (maxSeconds * 1000);
        return new Promise(function(resolve) {
            function tick() {
                fetch(verifyUrl + '&payment_id=' + paymentId, { credentials: 'same-origin' })
                    .then(function(r){ return r.json(); })
                    .then(function(j){
                        if (j.ok) {
                            resolve({ status: 'paid', message: j.message || 'Abonnement activé avec succès !' });
                            return;
                        }
                        if (!j.pending) {
                            resolve({ status: 'failed', message: j.message || 'Paiement échoué.' });
                            return;
                        }
                        if (Date.now() >= deadline) {
                            resolve({ status: 'timeout', message: 'Délai dépassé. Si vous avez validé sur votre téléphone, attendez 1 minute puis rechargez la page.' });
                            return;
                        }
                        setTimeout(tick, 3000);
                    })
                    .catch(function(){
                        if (Date.now() >= deadline) {
                            resolve({ status: 'timeout', message: 'Impossible de vérifier le paiement. Réessayez dans un instant.' });
                        } else {
                            setTimeout(tick, 3000);
                        }
                    });
            }
            tick();
        });
    }

    function openPaymentWaitPopup(paymentId, planLabel, amount, operator, ussd) {
        if (typeof Swal === 'undefined') return;
        var secondsLeft = PAY_WAIT_SECONDS;
        Swal.fire({
            title: 'Paiement en cours…',
            html: buildUssdWaitHtml(planLabel, amount, operator, ussd, secondsLeft),
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function() {
                payCountdownTimer = setInterval(function() {
                    secondsLeft -= 1;
                    var el = document.getElementById('subPayCountdown');
                    if (el) {
                        el.textContent = secondsLeft > 0
                            ? ('Temps restant : ' + secondsLeft + ' secondes…')
                            : 'Vérification finale…';
                    }
                    if (secondsLeft <= 0) clearPayCountdown();
                }, 1000);
            },
            willClose: clearPayCountdown
        });

        pollSubscriptionPayment(paymentId, PAY_WAIT_SECONDS).then(function(result) {
            clearPayCountdown();
            if (result.status === 'paid') {
                Swal.fire({
                    icon: 'success',
                    title: 'Paiement confirmé',
                    text: result.message,
                    confirmButtonText: 'OK'
                }).then(function(){ window.location.href = window.location.pathname + '?_route=admin/subscription'; });
                return;
            }
            Swal.fire({
                icon: result.status === 'timeout' ? 'warning' : 'error',
                title: result.status === 'timeout' ? 'En attente' : 'Paiement échoué',
                text: result.message
            });
        });
    }

    var campayOk = WZ_SUB.campayOk;
    document.querySelectorAll('[data-plan]').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            if (!campayOk || btn.getAttribute('data-campay') === '0') {
                alert('CamPay n\'est pas configuré. Contactez le SuperAdmin (Paramètres → Passerelle CamPay).');
                return;
            }
            openModal(btn.getAttribute('data-plan'));
        });
    });
    document.getElementById('campayCancelBtn').addEventListener('click', function(){
        closeModal();
        if(autoCheckout && checkoutPlan){
            fetch(demoAckUrl + '&ajax=1', { credentials: 'same-origin' }).catch(function(){});
            var ack = document.getElementById('demoAckBox');
            var banner = document.getElementById('checkoutBanner');
            if(ack) ack.classList.add('show');
            if(banner) banner.style.display = 'none';
            autoCheckout = false;
            checkoutPlan = '';
        }
    });
    modal.addEventListener('click', function(e){
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });
    if(proRoutersInput) proRoutersInput.addEventListener('input', updateAmount);
    if(phoneInput) phoneInput.addEventListener('input', function(){
        phoneInput.value = phoneInput.value.replace(/\D/g,'').slice(0,9);
    });

    if (payForm) {
        payForm.addEventListener('submit', function(e){
            e.preventDefault();
            if(planInput.value === 'pro' && proRoutersInput) {
                routersInput.value = Math.max(1, parseInt(proRoutersInput.value, 10) || 1);
            }
            var phone = (phoneInput.value || '').replace(/\D/g,'').slice(0,9);
            if (phone.length < 9) {
                alert('Entrez un numéro valide (9 chiffres).');
                return;
            }
            var plan = planInput.value;
            var routers = plan === 'pro' ? Math.max(1, parseInt(proRoutersInput.value,10)||1) : 1;
            var amount = plan === 'pro' ? proPrice * routers : businessPrice;
            var planLabel = plan === 'pro' ? 'Forfait Pro' : 'Forfait Business';
            var submitBtn = document.getElementById('campaySubmitBtn');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Envoi…'; }

            var body = new URLSearchParams();
            body.set('ajax', '1');
            body.set('csrf_token', csrfToken);
            body.set('plan_type', plan);
            body.set('routers_count', String(routers));
            body.set('phone', phone);

            fetch(payUrl, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() })
                .then(function(r){ return r.text(); })
                .then(function(text){
                    var res;
                    try { res = JSON.parse(text); } catch(err) { throw new Error('Réponse serveur invalide'); }
                    if (!res.ok) throw new Error(res.message || 'Paiement refusé');
                    closeModal();
                    try {
                        var u = new URL(window.location.href);
                        u.searchParams.set('_route', 'admin/subscription');
                        u.searchParams.set('payment_id', String(res.payment_id));
                        window.history.replaceState({}, '', u.pathname + '?' + u.searchParams.toString());
                    } catch (ignore) {}
                    openPaymentWaitPopup(res.payment_id, res.plan_label || planLabel, res.amount || amount, res.operator || '', res.ussd || '');
                })
                .catch(function(err){
                    alert(err.message || 'Erreur réseau');
                })
                .finally(function(){
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fa fa-mobile"></i> Payer'; }
                });
        });
    }

    if (pendingPaymentId > 0 && typeof Swal !== 'undefined') {
        setTimeout(function(){
            openPaymentWaitPopup(pendingPaymentId, pendingPlanLabel || 'Forfait', pendingAmount, pendingOperator, pendingUssd);
        }, 300);
    }

    if(autoCheckout && checkoutPlan){
        setTimeout(function(){ openModal(checkoutPlan); }, 400);
        var target = document.getElementById('plan-card-' + checkoutPlan);
        if(target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
})();
{/literal}
</script>

{include file="sections/footer.tpl"}
