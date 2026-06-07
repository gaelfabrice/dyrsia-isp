{include file="sections/header.tpl"}

<link rel="stylesheet" href="{$app_url}/ui/ui/fonts/inter/inter.css">

<style>
{literal}
.isp-page {
    --isp-bg: #030712;
    --isp-text: #f8fafc;
    --isp-heading: #ffffff;
    --isp-muted: #94a3b8;
    --isp-line: rgba(148, 163, 184, 0.2);
    --isp-brand: #16a34a;
    --isp-brand2: #0891b2;
    --isp-card: rgba(15, 23, 42, 0.92);
    --isp-feature: #cbd5e1;
    --isp-pricing-bg: #030712;
    --isp-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    font-family: Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    color: var(--isp-text);
    margin: -15px -15px 0;
    padding: 0 15px 24px;
}
body.theme-light .isp-page {
    --isp-bg: #f1f5f9;
    --isp-text: #1e293b;
    --isp-heading: #0f172a;
    --isp-muted: #64748b;
    --isp-line: #e2e8f0;
    --isp-brand: #059669;
    --isp-brand2: #0d9488;
    --isp-card: #ffffff;
    --isp-feature: #475569;
    --isp-pricing-bg: #ffffff;
    --isp-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
}
.isp-page * { box-sizing: border-box; }

.isp-hero {
    border-radius: 16px;
    padding: 20px 22px;
    margin-bottom: 20px;
    border: 1px solid var(--isp-line);
    background: var(--isp-card);
}
.isp-hero h2 { margin: 0 0 6px; font-size: 22px; font-weight: 900; color: var(--isp-heading); }
.isp-hero p { margin: 0; font-size: 14px; line-height: 1.55; color: var(--isp-muted); }

.isp-pricing-wrap {
    border-radius: 20px;
    padding: 28px 24px 32px;
    background:
        radial-gradient(ellipse 70% 50% at 50% 0%, rgba(34, 197, 94, 0.1), transparent),
        var(--isp-pricing-bg);
    border: 1px solid var(--isp-line);
    margin-bottom: 28px;
    box-shadow: var(--isp-shadow);
}
body.theme-light .isp-pricing-wrap {
    background:
        radial-gradient(ellipse 80% 60% at 50% -10%, rgba(16, 185, 129, 0.08), transparent),
        var(--isp-pricing-bg);
}
.isp-pricing-head { text-align: center; margin-bottom: 28px; }
.isp-pricing-head h3 { margin: 0 0 8px; font-size: 18px; font-weight: 900; color: var(--isp-heading); }
.isp-pricing-head p { margin: 0; color: var(--isp-muted); font-size: 14px; }

.isp-pricing-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    align-items: start;
}
@media (max-width: 767px) {
    .isp-pricing-grid { grid-template-columns: 1fr; max-width: 420px; margin: 0 auto; }
}

.isp-pricing-card {
    position: relative;
    padding: 32px 24px 24px;
    border-radius: 22px;
    background: var(--isp-card);
    border: 1px solid var(--isp-line);
}
.isp-pricing-card.featured {
    border: 2px solid var(--isp-brand);
    background: linear-gradient(180deg, rgba(34, 197, 94, 0.08) 0%, var(--isp-card) 100%);
}
body.theme-light .isp-pricing-card.featured {
    background: linear-gradient(180deg, #ecfdf5 0%, #ffffff 100%);
}
.isp-pricing-badge {
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
    background: var(--isp-brand);
    color: #fff;
}
.isp-pricing-card h4 {
    margin: 0 0 12px;
    font-size: 20px;
    font-weight: 900;
    color: var(--isp-heading);
    text-align: center;
}
.isp-price-field {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 8px;
}
.isp-price-field input {
    width: 140px;
    text-align: center;
    font-size: 28px;
    font-weight: 900;
    color: var(--isp-heading);
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid var(--isp-line);
    border-radius: 12px;
    padding: 8px 10px;
}
body.theme-light .isp-price-field input {
    background: #f8fafc;
}
.isp-price-field input:focus {
    outline: none;
    border-color: var(--isp-brand);
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
}
.isp-price-suffix {
    font-size: 13px;
    color: var(--isp-muted);
    text-align: center;
    margin-bottom: 16px;
}
.isp-pricing-features {
    list-style: none;
    margin: 0 0 20px;
    padding: 0;
}
.isp-pricing-features li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 13px;
    color: var(--isp-feature);
    line-height: 1.45;
}
.isp-pricing-features li i {
    color: var(--isp-brand);
    margin-top: 3px;
}

.isp-actions {
    text-align: center;
    margin-top: 8px;
}
.isp-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, var(--isp-brand), var(--isp-brand2));
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(22, 163, 74, 0.25);
}
.isp-btn-save:hover { opacity: 0.92; color: #fff; }

.isp-history {
    border-radius: 16px;
    border: 1px solid var(--isp-line);
    background: var(--isp-card);
    overflow: hidden;
    box-shadow: var(--isp-shadow);
}
.isp-history-head {
    padding: 16px 20px;
    border-bottom: 1px solid var(--isp-line);
    font-size: 16px;
    font-weight: 800;
    color: var(--isp-heading);
}
.isp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.isp-table th,
.isp-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--isp-line);
}
.isp-table th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--isp-muted);
    font-weight: 700;
}
.isp-table td { color: var(--isp-text); }
.isp-table tr:last-child td { border-bottom: none; }
.isp-table-empty td {
    text-align: center;
    color: var(--isp-muted);
    padding: 24px;
}
{/literal}
</style>

<div class="isp-page">
    <div class="isp-hero">
        <h2>{Lang::T('ISP Settings')}</h2>
        <p>Modifiez les tarifs puis cliquez <strong>Enregistrer</strong>. Rechargez ensuite la page admin (F5) pour voir les nouveaux montants.</p>
        {if $isp_settings_updated_at}
        <p style="margin-top:8px;font-size:13px;color:var(--isp-muted)">Dernière modification en base : {$isp_settings_updated_at}</p>
        {/if}
    </div>

    <form method="post" action="{Text::url('superadmin/isp-settings-post')}">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">

        <div class="isp-pricing-wrap">
            <div class="isp-pricing-head">
                <h3>Tarifs des forfaits ISP</h3>
                <p>Montants en F CFA — visibles par tous les administrateurs</p>
            </div>

            <div class="isp-pricing-grid">
                <div class="isp-pricing-card featured">
                    <span class="isp-pricing-badge">Le plus populaire</span>
                    <h4>Forfait Business</h4>
                    <div class="isp-price-field">
                        <input type="number" step="1" min="0" name="business_price" value="{$isp_settings.business_price|default:0|round:0}" required>
                        <span style="font-size:14px;color:var(--isp-muted);font-weight:700">F CFA</span>
                    </div>
                    <p class="isp-price-suffix">/ mois — jusqu'à 3 routeurs inclus</p>
                    <ul class="isp-pricing-features">
                        <li><i class="fa fa-check"></i><span>Commission 10% sur ventes Hotspot et PPPoE</span></li>
                        <li><i class="fa fa-check"></i><span>Configuration simplifiée — encaissements gérés</span></li>
                        <li><i class="fa fa-check"></i><span>Idéal pour les petites installations de quartier</span></li>
                    </ul>
                </div>

                <div class="isp-pricing-card">
                    <h4>Forfait Pro</h4>
                    <div class="isp-price-field">
                        <input type="number" step="1" min="0" name="pro_price_per_router" value="{$isp_settings.pro_price_per_router|default:0|round:0}" required>
                        <span style="font-size:14px;color:var(--isp-muted);font-weight:700">F CFA</span>
                    </div>
                    <p class="isp-price-suffix">/ routeur / mois</p>
                    <ul class="isp-pricing-features">
                        <li><i class="fa fa-check"></i><span>0% de commission sur toutes vos ventes</span></li>
                        <li><i class="fa fa-check"></i><span>Intégration de votre propre API de paiement</span></li>
                        <li><i class="fa fa-check"></i><span>Conçu pour FAI locaux et fort volume</span></li>
                    </ul>
                </div>
            </div>

            <div class="isp-actions">
                <button type="submit" class="isp-btn-save"><i class="fa fa-save"></i> {Lang::T('Save')}</button>
            </div>
        </div>
    </form>

    <div class="isp-history">
        <div class="isp-history-head"><i class="fa fa-history"></i> {Lang::T('Modification History')}</div>
        <table class="isp-table">
            <thead>
                <tr>
                    <th>Forfait</th>
                    <th>Montant (F CFA)</th>
                    <th>Modifié par</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            {if $settings_rows|@count gt 0}
                {foreach $settings_rows as $row}
                <tr>
                    <td>{$setting_labels[$row.setting_key]|default:$row.setting_key}</td>
                    <td>{$row.setting_value|number_format:0}</td>
                    <td>{if $row.updated_by}{$row.updated_by}{else}—{/if}</td>
                    <td>{$row.updated_at}</td>
                </tr>
                {/foreach}
            {else}
                <tr class="isp-table-empty"><td colspan="4">Aucune modification enregistrée.</td></tr>
            {/if}
            </tbody>
        </table>
    </div>
</div>

{include file="sections/footer.tpl"}
