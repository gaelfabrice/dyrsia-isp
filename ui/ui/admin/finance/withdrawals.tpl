{include file="sections/header.tpl"}

<link rel="stylesheet" href="{$app_url}/ui/ui/fonts/inter/inter.css">

<style>
{literal}
.rx-page {
    --rx-bg: #f4f6fb;
    --rx-surface: #ffffff;
    --rx-border: #e8edf5;
    --rx-text: #0f172a;
    --rx-muted: #64748b;
    --rx-accent: #4f46e5;
    --rx-accent-soft: #eef2ff;
    --rx-success: #059669;
    --rx-success-soft: #ecfdf5;
    --rx-warn: #d97706;
    --rx-warn-soft: #fffbeb;
    --rx-radius: 16px;
    --rx-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 32px rgba(15, 23, 42, 0.06);
    font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
    color: var(--rx-text);
    margin: -10px -10px 0;
    padding: 0 6px 32px;
}
.rx-page * { box-sizing: border-box; }

/* Hero */
.rx-hero {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4338ca 100%);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: var(--rx-shadow);
}
.rx-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -8%;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(255,255,255,.12) 0%, transparent 70%);
    pointer-events: none;
}
.rx-hero-top {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    position: relative;
    z-index: 1;
}
.rx-hero-kicker {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    opacity: .75;
    margin-bottom: 6px;
}
.rx-hero h1 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -.02em;
}
.rx-hero-desc {
    margin: 8px 0 0;
    font-size: 14px;
    line-height: 1.5;
    opacity: .85;
    max-width: 480px;
}
.rx-hero-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
    position: relative;
    z-index: 1;
}
.rx-stat-pill {
    background: rgba(255,255,255,.1);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 14px;
    padding: 14px 18px;
    min-width: 160px;
}
.rx-stat-pill strong {
    display: block;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -.02em;
    line-height: 1.1;
}
.rx-stat-pill span {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: .75;
}
.rx-stat-pill small {
    display: block;
    margin-top: 6px;
    font-size: 11px;
    opacity: .65;
}

/* Steps */
.rx-steps {
    display: flex;
    gap: 0;
    margin-bottom: 22px;
    background: var(--rx-surface);
    border: 1px solid var(--rx-border);
    border-radius: var(--rx-radius);
    padding: 6px;
    box-shadow: var(--rx-shadow);
}
.rx-step {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--rx-muted);
    transition: background .2s, color .2s;
}
.rx-step.active {
    background: var(--rx-accent-soft);
    color: var(--rx-accent);
}
.rx-step.done { color: var(--rx-success); }
.rx-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    background: #f1f5f9;
    color: var(--rx-muted);
    flex-shrink: 0;
}
.rx-step.active .rx-step-num { background: var(--rx-accent); color: #fff; }
.rx-step.done .rx-step-num { background: var(--rx-success-soft); color: var(--rx-success); }

/* Layout */
.rx-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
    gap: 22px;
    align-items: start;
}
@media (max-width: 991px) {
    .rx-grid { grid-template-columns: 1fr; }
}

/* Panels */
.rx-panel {
    background: var(--rx-surface);
    border: 1px solid var(--rx-border);
    border-radius: var(--rx-radius);
    box-shadow: var(--rx-shadow);
    overflow: hidden;
}
.rx-panel-head {
    padding: 18px 22px;
    border-bottom: 1px solid var(--rx-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.rx-panel-head h2 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}
.rx-panel-head h2 i { color: var(--rx-accent); font-size: 14px; }
.rx-panel-body { padding: 22px; }

/* Identity card */
.rx-identity {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-radius: 14px;
    border: 1px solid var(--rx-border);
    margin-bottom: 16px;
}
.rx-avatar {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: var(--rx-accent);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 800;
    flex-shrink: 0;
}
.rx-identity-info strong { display: block; font-size: 15px; font-weight: 700; }
.rx-identity-info span { font-size: 13px; color: var(--rx-muted); }
.rx-op-tag {
    display: inline-block;
    margin-top: 6px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: var(--rx-accent-soft);
    color: var(--rx-accent);
}

.rx-notice {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.5;
    margin-bottom: 16px;
}
.rx-notice.lock { background: var(--rx-warn-soft); border: 1px solid #fde68a; color: #92400e; }
.rx-notice.info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.rx-notice.warn { background: var(--rx-warn-soft); border: 1px solid #fcd34d; color: #92400e; }
.rx-notice.danger { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

/* Form */
.rx-field { margin-bottom: 16px; }
.rx-field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--rx-muted);
    margin-bottom: 6px;
}
.rx-field .form-control {
    border-radius: 10px;
    border: 1px solid var(--rx-border);
    box-shadow: none;
    font-size: 14px;
    height: 42px;
    transition: border-color .15s, box-shadow .15s;
}
.rx-field .form-control:focus {
    border-color: var(--rx-accent);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
}
.rx-field textarea.form-control { height: auto; min-height: 88px; resize: vertical; }
.rx-field .input-group-addon {
    border-radius: 0 10px 10px 0;
    background: #f8fafc;
    border-color: var(--rx-border);
    color: var(--rx-muted);
    font-weight: 600;
}
.rx-field .input-group .form-control { border-radius: 10px 0 0 10px; }

.rx-op-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}
@media (max-width: 480px) { .rx-op-grid { grid-template-columns: 1fr; } }
.rx-op-grid input { position: absolute; opacity: 0; pointer-events: none; }
.rx-op-card {
    display: block;
    text-align: center;
    padding: 12px 8px;
    border: 2px solid var(--rx-border);
    border-radius: 12px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: var(--rx-muted);
    transition: all .15s;
}
.rx-op-grid input:checked + .rx-op-card {
    border-color: var(--rx-accent);
    background: var(--rx-accent-soft);
    color: var(--rx-accent);
}

.rx-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px 20px;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .1s, box-shadow .15s, opacity .15s;
}
.rx-btn:active { transform: scale(.98); }
.rx-btn-primary { background: var(--rx-accent); color: #fff; box-shadow: 0 4px 14px rgba(79, 70, 229, .35); }
.rx-btn-primary:hover:not(:disabled) { background: #4338ca; color: #fff; }
.rx-btn-primary:disabled { opacity: .4; cursor: not-allowed; box-shadow: none; }
.rx-btn-outline { background: #fff; color: var(--rx-accent); border: 1px solid var(--rx-border); }
.rx-btn-outline:hover { background: var(--rx-accent-soft); }

/* Amount section */
.rx-amount-wrap {
    background: #f8fafc;
    border: 1px solid var(--rx-border);
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 16px;
}
.rx-amount-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--rx-muted);
    margin-bottom: 8px;
}
.rx-amount-input {
    display: flex;
    align-items: center;
    gap: 8px;
}
.rx-amount-input input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 32px;
    font-weight: 800;
    letter-spacing: -.03em;
    color: var(--rx-text);
    outline: none;
    min-width: 0;
}
.rx-amount-input input:disabled { opacity: .5; }
.rx-amount-suffix {
    font-size: 14px;
    font-weight: 700;
    color: var(--rx-muted);
    white-space: nowrap;
}
.rx-quick-amt {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}
.rx-quick-amt button {
    padding: 6px 14px;
    border-radius: 999px;
    border: 1px solid var(--rx-border);
    background: #fff;
    font-size: 12px;
    font-weight: 600;
    color: var(--rx-muted);
    cursor: pointer;
    transition: all .15s;
}
.rx-quick-amt button:hover:not(:disabled) {
    border-color: var(--rx-accent);
    color: var(--rx-accent);
    background: var(--rx-accent-soft);
}
.rx-quick-amt button:disabled { opacity: .4; cursor: not-allowed; }
.rx-hints {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
}
.rx-hint {
    flex: 1;
    min-width: 140px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
}
.rx-hint.ok { background: var(--rx-success-soft); color: var(--rx-success); }
.rx-hint.min { background: var(--rx-warn-soft); color: var(--rx-warn); }

/* History — timeline list */
.rx-history-empty {
    text-align: center;
    padding: 48px 24px;
    color: var(--rx-muted);
}
.rx-history-empty i { font-size: 40px; opacity: .25; margin-bottom: 12px; display: block; }
.rx-history-list { display: flex; flex-direction: column; gap: 10px; }
.rx-history-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 14px;
    align-items: center;
    padding: 14px 16px;
    border: 1px solid var(--rx-border);
    border-radius: 14px;
    transition: border-color .15s, box-shadow .15s;
}
.rx-history-item:hover {
    border-color: #c7d2fe;
    box-shadow: 0 4px 12px rgba(79, 70, 229, .06);
}
.rx-history-date {
    text-align: center;
    min-width: 52px;
}
.rx-history-date b { display: block; font-size: 18px; font-weight: 800; color: var(--rx-accent); line-height: 1; }
.rx-history-date small { font-size: 10px; font-weight: 600; color: var(--rx-muted); text-transform: uppercase; }
.rx-history-main h3 { margin: 0 0 4px; font-size: 15px; font-weight: 700; }
.rx-history-main p { margin: 0; font-size: 12px; color: var(--rx-muted); line-height: 1.45; }
.rx-history-ref {
    font-family: ui-monospace, monospace;
    font-size: 11px;
    color: var(--rx-muted);
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 6px;
    margin-top: 6px;
    display: inline-block;
}
.rx-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.rx-badge.pending { background: #fef3c7; color: #b45309; }
.rx-badge.approved { background: #d1fae5; color: #047857; }
.rx-badge.expired { background: #fee2e2; color: #b91c1c; }
.rx-badge.rejected { background: #f1f5f9; color: #64748b; }

.rx-foot {
    font-size: 12px;
    color: var(--rx-muted);
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
{/literal}
</style>

<div class="rx-page">
    <div class="rx-hero">
        <div class="rx-hero-top">
            <div>
                <div class="rx-hero-kicker">Finance · Retraits</div>
                <h1>Vos reversements</h1>
                <p class="rx-hero-desc">Encaissez vos ventes Hotspot et PPPoE via Mobile Money. Les commissions plateforme sont déjà déduites de votre solde.</p>
            </div>
        </div>
        <div class="rx-hero-stats">
            <div class="rx-stat-pill">
                <strong>{number_format($withdrawal_available,0,',',' ')} <small style="font-size:14px;font-weight:600">Fcfa</small></strong>
                <span>Solde disponible</span>
                <small>{$withdrawal_commission_label} déduits</small>
            </div>
            <div class="rx-stat-pill">
                <strong>{number_format($withdrawal_approved_total,0,',',' ')} <small style="font-size:14px;font-weight:600">Fcfa</small></strong>
                <span>Déjà versés</span>
                <small>Demandes validées</small>
            </div>
            <div class="rx-stat-pill">
                <strong>{number_format($withdrawal_sales.gross|default:0,0,',',' ')}</strong>
                <span>Ventes passerelle</span>
                <small>Brut cumulé</small>
            </div>
        </div>
    </div>

    <div class="rx-steps">
        <div class="rx-step {if $withdrawal_profile && $withdrawal_profile->locked}done{else}active{/if}">
            <span class="rx-step-num">{if $withdrawal_profile && $withdrawal_profile->locked}<i class="fa fa-check"></i>{else}1{/if}</span>
            Profil de paiement
        </div>
        <div class="rx-step {if $withdrawal_profile && $withdrawal_profile->locked}active{/if}">
            <span class="rx-step-num">2</span>
            Demande de retrait
        </div>
    </div>

    <div class="rx-grid">
        <div class="rx-col-left">
            <!-- Profil -->
            <div class="rx-panel" style="margin-bottom:22px">
                <div class="rx-panel-head">
                    <h2><i class="fa fa-id-card-o"></i> Profil bénéficiaire</h2>
                    {if $withdrawal_profile && $withdrawal_profile->locked}
                        <span class="rx-badge approved"><i class="fa fa-lock"></i> Verrouillé</span>
                    {/if}
                </div>
                <div class="rx-panel-body">
                    {if $withdrawal_profile && $withdrawal_profile->locked}
                        <div class="rx-identity">
                            <div class="rx-avatar" id="rxAvatarInitials"><i class="fa fa-user"></i></div>
                            <div class="rx-identity-info">
                                <strong>{$withdrawal_profile->first_name|escape} {$withdrawal_profile->last_name|escape}</strong>
                                <span>+237 {$withdrawal_profile->phone|escape}</span>
                                {foreach $withdrawal_operators as $k => $label}{if $withdrawal_profile->operator eq $k}<span class="rx-op-tag">{$label}</span>{/if}{/foreach}
                            </div>
                        </div>
                        <div class="rx-notice lock">
                            <i class="fa fa-shield"></i>
                            <span>Pour modifier ce profil, contactez le <strong>service client</strong>. Cette mesure protège vos fonds.</span>
                        </div>
                    {else}
                        <div class="rx-notice info">
                            <i class="fa fa-info-circle"></i>
                            <span>Configurez une seule fois le compte Mobile Money qui recevra vos reversements.</span>
                        </div>
                        <form method="post" action="{Text::url('finance/withdrawals-profile-post')}" class="rx-form">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="rx-field">
                                        <label>Prénom</label>
                                        <input type="text" name="first_name" class="form-control" required maxlength="64" placeholder="Jean" value="{if $withdrawal_profile}{$withdrawal_profile->first_name|escape}{/if}">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="rx-field">
                                        <label>Nom</label>
                                        <input type="text" name="last_name" class="form-control" required maxlength="64" placeholder="Dupont" value="{if $withdrawal_profile}{$withdrawal_profile->last_name|escape}{/if}">
                                    </div>
                                </div>
                            </div>
                            <div class="rx-field">
                                <label>Numéro Mobile Money</label>
                                <div class="input-group">
                                    <span class="input-group-addon">+237</span>
                                    <input type="tel" name="phone" class="form-control" required maxlength="12" placeholder="6XX XXX XXX" value="{if $withdrawal_profile}{$withdrawal_profile->phone|escape}{/if}">
                                </div>
                            </div>
                            <div class="rx-field">
                                <label>Opérateur</label>
                                <div class="rx-op-grid">
                                    {foreach $withdrawal_operators as $k => $label}
                                    <label>
                                        <input type="radio" name="operator" value="{$k}" {if ($withdrawal_profile && $withdrawal_profile->operator eq $k) || (!$withdrawal_profile && $k eq 'orange_momo')}checked{/if}>
                                        <span class="rx-op-card">{$label}</span>
                                    </label>
                                    {/foreach}
                                </div>
                            </div>
                            <button type="submit" class="rx-btn rx-btn-primary"><i class="fa fa-check"></i> Enregistrer et verrouiller</button>
                        </form>
                    {/if}
                </div>
            </div>

            <!-- Demande -->
            <div class="rx-panel">
                <div class="rx-panel-head">
                    <h2><i class="fa fa-paper-plane-o"></i> Nouvelle demande</h2>
                </div>
                <div class="rx-panel-body">
                    <div class="rx-hints">
                        <div class="rx-hint ok"><i class="fa fa-wallet"></i> {number_format($withdrawal_available,0,',',' ')} Fcfa dispo</div>
                        <div class="rx-hint min"><i class="fa fa-sliders"></i> Min. {number_format($withdrawal_min,0,',',' ')} Fcfa</div>
                    </div>

                    {if !$withdrawal_profile || !$withdrawal_profile->locked}
                        <div class="rx-notice warn"><i class="fa fa-arrow-up"></i> Complétez d'abord votre profil bénéficiaire (étape 1).</div>
                    {elseif $withdrawal_available < $withdrawal_min}
                        <div class="rx-notice warn"><i class="fa fa-hourglass-half"></i> Solde insuffisant. Continuez à vendre via la passerelle pour atteindre le seuil de {number_format($withdrawal_min,0,',',' ')} Fcfa.</div>
                    {/if}

                    <form method="post" action="{Text::url('finance/withdrawals-request-post')}" id="rxRequestForm">
                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <input type="hidden" name="amount" id="rxAmountHidden" value="">

                        <div class="rx-amount-wrap">
                            <div class="rx-amount-label">Montant à retirer</div>
                            <div class="rx-amount-input">
                                <input type="text" inputmode="numeric" id="rxAmountDisplay" placeholder="0" autocomplete="off" {if !$withdrawal_can_submit}disabled{/if}>
                                <span class="rx-amount-suffix">Fcfa</span>
                            </div>
                            <div class="rx-quick-amt">
                                <button type="button" data-pct="0.25" {if !$withdrawal_can_submit}disabled{/if}>25%</button>
                                <button type="button" data-pct="0.5" {if !$withdrawal_can_submit}disabled{/if}>50%</button>
                                <button type="button" data-pct="0.75" {if !$withdrawal_can_submit}disabled{/if}>75%</button>
                                <button type="button" data-pct="1" {if !$withdrawal_can_submit}disabled{/if}>Tout</button>
                            </div>
                        </div>

                        <div class="rx-field">
                            <label>Note interne <span style="font-weight:400">(optionnel)</span></label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Ex. reversement mensuel…" {if !$withdrawal_can_submit}disabled{/if}></textarea>
                        </div>

                        {if $withdrawal_profile && $withdrawal_profile->locked}
                        <p class="rx-foot"><i class="fa fa-lock"></i> Versement vers +237{$withdrawal_profile->phone|escape}</p>
                        {/if}

                        <button type="submit" class="rx-btn rx-btn-primary" id="rxSubmitBtn" {if !$withdrawal_can_submit}disabled{/if}>
                            <i class="fa fa-send"></i> Envoyer la demande
                        </button>
                        <p class="rx-foot" style="margin-top:14px;justify-content:center;text-align:center">
                            Le montant sera bloqué 24h le temps de validation par la plateforme.
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Historique -->
        <div class="rx-col-right">
            <div class="rx-panel">
                <div class="rx-panel-head">
                    <h2><i class="fa fa-history"></i> Historique</h2>
                    <span style="font-size:12px;color:var(--rx-muted);font-weight:600">{$withdrawal_requests|@count} demande(s)</span>
                </div>
                <div class="rx-panel-body" style="padding:16px">
                    {if $withdrawal_requests|@count > 0}
                    <div class="rx-history-list">
                        {foreach $withdrawal_requests as $r}
                        <div class="rx-history-item">
                            <div class="rx-history-date">
                                <b>{$r->created_at|date_format:"%d"}</b>
                                <small>{$r->created_at|date_format:"%b %Y"}</small>
                            </div>
                            <div class="rx-history-main">
                                <h3>{number_format($r->amount,0,',',' ')} Fcfa</h3>
                                <p>
                                    {$r->beneficiary_name|escape} · +237{$r->beneficiary_phone|escape}
                                    {if $r->client_note}<br>{$r->client_note|escape}{/if}
                                    {if $r->admin_comment}<br><em style="color:#94a3b8">{$r->admin_comment|escape}</em>{/if}
                                </p>
                                <span class="rx-history-ref">{$r->reference|escape}</span>
                            </div>
                            <div>
                                {if $r->status eq 'pending'}<span class="rx-badge pending"><i class="fa fa-clock-o"></i> En cours</span>
                                {elseif $r->status eq 'approved'}<span class="rx-badge approved"><i class="fa fa-check"></i> Versé</span>
                                {elseif $r->status eq 'expired'}<span class="rx-badge expired"><i class="fa fa-ban"></i> Expiré</span>
                                {else}<span class="rx-badge rejected">Rejeté</span>{/if}
                            </div>
                        </div>
                        {/foreach}
                    </div>
                    {else}
                    <div class="rx-history-empty">
                        <i class="fa fa-inbox"></i>
                        <p><strong>Aucune demande pour l'instant</strong><br>Vos retraits apparaîtront ici après soumission.</p>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var min = {$withdrawal_min|default:2000};
    var max = {$withdrawal_available|default:0};
    var form = document.getElementById('rxRequestForm');
    var display = document.getElementById('rxAmountDisplay');
    var hidden = document.getElementById('rxAmountHidden');
    var btn = document.getElementById('rxSubmitBtn');
    if (!form || !display || !btn) return;

    function parseVal() {
        return parseInt(String(display.value).replace(/\D/g, ''), 10) || 0;
    }
    function formatNum(n) {
        return n > 0 ? n.toLocaleString('fr-FR') : '';
    }
    function syncHidden() {
        var v = parseVal();
        hidden.value = v > 0 ? v : '';
        var ok = max >= min && v >= min && v <= max;
        btn.disabled = !ok || display.disabled;
        display.style.color = (v > 0 && v < min) ? '#d97706' : (v > max ? '#dc2626' : '');
    }
    display.addEventListener('input', function () {
        var raw = parseVal();
        display.value = raw > 0 ? formatNum(raw) : '';
        syncHidden();
    });
    document.querySelectorAll('.rx-quick-amt button').forEach(function (b) {
        b.addEventListener('click', function () {
            if (b.disabled) return;
            var pct = parseFloat(b.getAttribute('data-pct')) || 0;
            var amt = Math.floor(max * pct);
            if (pct === 1) amt = Math.floor(max);
            display.value = amt > 0 ? formatNum(amt) : '';
            syncHidden();
        });
    });
    form.addEventListener('submit', function (e) {
        var v = parseVal();
        if (v < min) {
            e.preventDefault();
            alert('Minimum : ' + min.toLocaleString('fr-FR') + ' Fcfa.');
            return;
        }
        if (v > max) {
            e.preventDefault();
            alert('Maximum disponible : ' + max.toLocaleString('fr-FR') + ' Fcfa.');
            return;
        }
        hidden.value = v;
    });
    syncHidden();
    var av = document.getElementById('rxAvatarInitials');
    if (av) {
        var fn = {if $withdrawal_profile}'{$withdrawal_profile->first_name|escape:'javascript'}'{else}''{/if};
        var ln = {if $withdrawal_profile}'{$withdrawal_profile->last_name|escape:'javascript'}'{else}''{/if};
        if (fn || ln) av.textContent = (fn.charAt(0) + ln.charAt(0)).toUpperCase();
    }
})();
</script>

{include file="sections/footer.tpl"}
