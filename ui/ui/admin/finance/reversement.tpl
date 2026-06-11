{include file="sections/header.tpl"}

<style>
{literal}
.rv-page {
    --rv-card: #ffffff;
    --rv-text: #1e293b;
    --rv-heading: #0f172a;
    --rv-muted: #64748b;
    --rv-line: #e2e8f0;
    --rv-brand: #2563eb;
    --rv-brand-soft: rgba(37, 99, 235, 0.1);
    --rv-success: #059669;
    --rv-success-soft: rgba(16, 185, 129, 0.12);
    --rv-warn: #d97706;
    --rv-warn-soft: rgba(245, 158, 11, 0.15);
    --rv-danger: #dc2626;
    --rv-danger-soft: rgba(239, 68, 68, 0.12);
    --rv-shadow: 0 10px 40px rgba(15, 23, 42, 0.06);
    --rv-input-bg: #f8fafc;
    --rv-code-bg: #f1f5f9;
    font-family: Inter, system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    color: var(--rv-text);
    margin: 0 -15px 0;
    padding: 0 15px 28px;
}
body.theme-dark .rv-page,
body.dark-mode .rv-page {
    --rv-card: rgba(15, 23, 42, 0.92);
    --rv-text: #f8fafc;
    --rv-heading: #ffffff;
    --rv-muted: #94a3b8;
    --rv-line: rgba(148, 163, 184, 0.18);
    --rv-brand-soft: rgba(59, 130, 246, 0.15);
    --rv-success-soft: rgba(16, 185, 129, 0.15);
    --rv-warn-soft: rgba(245, 158, 11, 0.18);
    --rv-danger-soft: rgba(239, 68, 68, 0.15);
    --rv-shadow: 0 12px 40px rgba(0, 0, 0, 0.22);
    --rv-input-bg: rgba(2, 6, 23, 0.55);
    --rv-code-bg: rgba(2, 6, 23, 0.65);
}
.rv-page * { box-sizing: border-box; }

.rv-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}
.rv-hero h1 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: var(--rv-heading);
    letter-spacing: -0.02em;
}
.rv-hero p {
    margin: 6px 0 0;
    color: var(--rv-muted);
    font-size: 14px;
    max-width: 560px;
    line-height: 1.5;
}
.rv-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 999px;
    border: 1px solid var(--rv-line);
    background: var(--rv-card);
    color: var(--rv-heading);
    font-size: 13px;
    font-weight: 700;
    box-shadow: var(--rv-shadow);
    white-space: nowrap;
}

.rv-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 900px) { .rv-kpi-grid { grid-template-columns: 1fr; } }

.rv-kpi {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    padding: 22px 22px 20px;
    color: #fff;
    box-shadow: var(--rv-shadow);
    min-height: 118px;
}
.rv-kpi::after {
    content: '';
    position: absolute;
    top: -30%;
    right: -10%;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.12);
    pointer-events: none;
}
.rv-kpi-value {
    position: relative;
    z-index: 1;
    font-size: 26px;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
}
.rv-kpi-label {
    position: relative;
    z-index: 1;
    margin-top: 6px;
    font-size: 12px;
    font-weight: 600;
    opacity: 0.92;
    line-height: 1.35;
}
.rv-kpi-icon {
    position: absolute;
    top: 18px;
    right: 18px;
    font-size: 22px;
    opacity: 0.35;
}
.rv-kpi-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.rv-kpi-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.rv-kpi-teal { background: linear-gradient(135deg, #14b8a6, #0f766e); }

.rv-card {
    background: var(--rv-card);
    border: 1px solid var(--rv-line);
    border-radius: 16px;
    box-shadow: var(--rv-shadow);
    overflow: hidden;
    margin-bottom: 20px;
}
.rv-card-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--rv-line);
}
.rv-card-head h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: var(--rv-heading);
    display: flex;
    align-items: center;
    gap: 10px;
}
.rv-card-head h2 i { color: var(--rv-brand); opacity: 0.9; }
.rv-card-body { padding: 0; }
.rv-card-body.pad { padding: 18px 20px; }

.rv-table-wrap { overflow-x: auto; }
.rv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 860px;
}
.rv-table th {
    text-align: left;
    padding: 13px 16px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--rv-muted);
    border-bottom: 1px solid var(--rv-line);
    background: rgba(248, 250, 252, 0.85);
    white-space: nowrap;
}
body.theme-dark .rv-table th,
body.dark-mode .rv-table th {
    background: rgba(2, 6, 23, 0.35);
}
.rv-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--rv-line);
    color: var(--rv-text);
    vertical-align: middle;
}
.rv-table th:first-child,
.rv-table td:first-child {
    position: static !important;
    background: transparent !important;
    background-color: transparent !important;
}
.rv-table tr:last-child td { border-bottom: 0; }
.rv-table tr:hover td { background: rgba(37, 99, 235, 0.04); }
body.theme-dark .rv-table tr:hover td,
body.dark-mode .rv-table tr:hover td {
    background: rgba(37, 99, 235, 0.08);
}
.rv-table-compact { min-width: 640px; }

.rv-amount {
    font-weight: 800;
    color: var(--rv-heading);
    white-space: nowrap;
}
.rv-phone {
    display: block;
    margin-top: 3px;
    font-size: 12px;
    color: var(--rv-muted);
}
.rv-ref {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 6px;
    background: var(--rv-code-bg);
    color: var(--rv-muted);
}

.rv-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}
.rv-badge.pending { background: var(--rv-warn-soft); color: #b45309; }
body.theme-dark .rv-badge.pending,
body.dark-mode .rv-badge.pending { color: #fbbf24; }
.rv-badge.approved { background: var(--rv-success-soft); color: #047857; }
body.theme-dark .rv-badge.approved,
body.dark-mode .rv-badge.approved { color: #34d399; }
.rv-badge.expired { background: var(--rv-danger-soft); color: #b91c1c; }
body.theme-dark .rv-badge.expired,
body.dark-mode .rv-badge.expired { color: #f87171; }
.rv-badge.rejected { background: rgba(148, 163, 184, 0.15); color: var(--rv-muted); }

.rv-countdown { font-weight: 700; color: var(--rv-warn); }
.rv-countdown.expired { color: var(--rv-danger); }

.rv-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.rv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 14px;
    border: 0;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    transition: opacity 0.15s;
}
.rv-btn:hover { opacity: 0.92; }
.rv-btn-approve { background: var(--rv-success); color: #fff !important; }
.rv-btn-reject { background: var(--rv-danger); color: #fff !important; }
.rv-btn-save {
    background: var(--rv-warn);
    color: #fff !important;
    width: 100%;
    margin-top: 10px;
    padding: 10px 14px;
}
.rv-btn-search {
    background: var(--rv-brand);
    color: #fff !important;
    border: 0;
    border-radius: 0 10px 10px 0;
    padding: 0 16px;
    cursor: pointer;
    font-weight: 700;
}

.rv-empty {
    text-align: center;
    padding: 44px 24px;
    color: var(--rv-muted);
}
.rv-empty i {
    font-size: 36px;
    opacity: 0.3;
    display: block;
    margin-bottom: 10px;
}

.rv-split {
    display: grid;
    grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
    gap: 20px;
    align-items: start;
}
@media (max-width: 1100px) { .rv-split { grid-template-columns: 1fr; } }

.rv-search {
    display: flex;
    align-items: stretch;
    border: 1px solid var(--rv-line);
    border-radius: 12px;
    overflow: hidden;
    background: var(--rv-input-bg);
    margin-bottom: 16px;
}
.rv-search input {
    flex: 1;
    border: 0;
    padding: 12px 14px;
    background: transparent;
    color: var(--rv-text);
    font-size: 14px;
    font-family: inherit;
}
.rv-search input:focus { outline: none; }
.rv-search input::placeholder { color: var(--rv-muted); }

.rv-profile {
    border: 1px solid var(--rv-line);
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
    background: rgba(148, 163, 184, 0.04);
}
body.theme-dark .rv-profile,
body.dark-mode .rv-profile {
    background: rgba(2, 6, 23, 0.35);
}
.rv-profile-name {
    font-size: 15px;
    font-weight: 800;
    color: var(--rv-heading);
}
.rv-profile-user {
    font-size: 12px;
    color: var(--rv-muted);
    margin-top: 2px;
}
.rv-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 12px;
}
@media (max-width: 520px) { .rv-form-grid { grid-template-columns: 1fr; } }
.rv-field input,
.rv-field select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid var(--rv-line);
    background: var(--rv-input-bg);
    color: var(--rv-text);
    font-size: 13px;
    font-family: inherit;
}
.rv-field input:focus,
.rv-field select:focus {
    outline: none;
    border-color: var(--rv-brand);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.rv-note {
    font-size: 12px;
    color: var(--rv-muted);
    line-height: 1.45;
}
{/literal}
</style>

<div class="rv-page">

    <div class="rv-hero">
        <div>
            <h1>Reversement</h1>
            <p>Validez les demandes de retrait Mobile Money, consultez les soldes plateforme et mettez à jour les profils de paiement des administrateurs.</p>
        </div>
        <div class="rv-hero-badge">
            <i class="fa fa-shield"></i> Finance SuperAdmin
        </div>
    </div>

    <div class="rv-kpi-grid">
        <div class="rv-kpi rv-kpi-blue">
            <i class="fa fa-line-chart rv-kpi-icon"></i>
            <div class="rv-kpi-value">{number_format($withdrawal_stats.gross_revenue,0,',',' ')} <small style="font-size:14px;font-weight:600">Fcfa</small></div>
            <div class="rv-kpi-label">Chiffre d'affaires brut (ventes passerelle)</div>
        </div>
        <div class="rv-kpi rv-kpi-purple">
            <i class="fa fa-percent rv-kpi-icon"></i>
            <div class="rv-kpi-value">{number_format($withdrawal_stats.platform_commission,0,',',' ')} <small style="font-size:14px;font-weight:600">Fcfa</small></div>
            <div class="rv-kpi-label">Total commissions plateforme</div>
        </div>
        <div class="rv-kpi rv-kpi-teal">
            <i class="fa fa-users rv-kpi-icon"></i>
            <div class="rv-kpi-value">{number_format($withdrawal_stats.due_to_clients,0,',',' ')} <small style="font-size:14px;font-weight:600">Fcfa</small></div>
            <div class="rv-kpi-label">Total dû aux clients (soldes restants)</div>
        </div>
    </div>

    <div class="rv-card">
        <div class="rv-card-head">
            <h2><i class="fa fa-clock-o"></i> Demandes en traitement</h2>
            {if $withdrawal_pending|@count > 0}
            <span class="rv-badge pending"><i class="fa fa-hourglass-half"></i> {$withdrawal_pending|@count} en attente</span>
            {/if}
        </div>
        <div class="rv-card-body">
            <div class="rv-table-wrap">
                <table class="rv-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Méthode</th>
                            <th>Montant</th>
                            <th>Compte à rebours</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $withdrawal_pending|@count > 0}
                            {foreach $withdrawal_pending as $p}
                            <tr>
                                <td>{$p->created_at|date_format:"%d/%m/%Y %H:%M"}</td>
                                <td>
                                    <strong style="color:var(--rv-heading)">{$p->admin_fullname|default:$p->beneficiary_name|escape}</strong>
                                    <span class="rv-phone">ID #{$p->admin_id}</span>
                                </td>
                                <td>
                                    {foreach $withdrawal_operators as $k => $label}{if $p->operator eq $k}{$label}{/if}{/foreach}
                                    <span class="rv-phone">+237 {$p->beneficiary_phone|escape}</span>
                                </td>
                                <td><span class="rv-amount">{number_format($p->amount,0,',',' ')} Fcfa</span></td>
                                <td><span class="rv-countdown" id="rv-countdown-{$p->id}" data-expires="{$p->expires_at}">—</span></td>
                                <td><span class="rv-badge pending">En traitement</span></td>
                                <td>
                                    <div class="rv-actions">
                                        <button type="button" class="rv-btn rv-btn-approve" onclick="rvApprove({$p->id}, '{$p->reference|escape:'javascript'}', '{number_format($p->amount,0,',',' ')}')">
                                            <i class="fa fa-check"></i> Valider
                                        </button>
                                        <button type="button" class="rv-btn rv-btn-reject" onclick="rvReject({$p->id})">
                                            <i class="fa fa-times"></i> Rejeter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="7">
                                    <div class="rv-empty">
                                        <i class="fa fa-inbox"></i>
                                        Aucune demande en attente pour le moment.
                                    </div>
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rv-split">
        <div class="rv-card">
            <div class="rv-card-head">
                <h2><i class="fa fa-edit"></i> Profils de retrait</h2>
            </div>
            <div class="rv-card-body pad">
                <p class="rv-note" style="margin:0 0 14px">Recherchez un administrateur pour corriger son compte Mobile Money de reversement.</p>
                <form method="get" action="{$app_url}/">
                    <input type="hidden" name="_route" value="finance/reversement">
                    <div class="rv-search">
                        <input type="text" name="q" placeholder="Nom, username, téléphone…" value="{$withdrawal_search_term|escape}" autocomplete="off">
                        <button type="submit" class="rv-btn-search" aria-label="Rechercher"><i class="fa fa-search"></i></button>
                    </div>
                </form>
                {if $withdrawal_search_term}
                    {if $withdrawal_search_items|@count > 0}
                        {foreach $withdrawal_search_items as $item}
                        <div class="rv-profile">
                            <div class="rv-profile-name">{$item.admin->fullname|escape}</div>
                            <div class="rv-profile-user">@{$item.admin->username|escape}</div>
                            <form method="post" action="{Text::url('finance/reversement-profile-post')}">
                                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                <input type="hidden" name="admin_id" value="{$item.admin->id}">
                                <input type="hidden" name="search_term" value="{$withdrawal_search_term|escape}">
                                <div class="rv-form-grid">
                                    <div class="rv-field"><input type="text" name="first_name" placeholder="Prénom" value="{if $item.profile}{$item.profile->first_name|escape}{/if}" required></div>
                                    <div class="rv-field"><input type="text" name="last_name" placeholder="Nom" value="{if $item.profile}{$item.profile->last_name|escape}{/if}" required></div>
                                    <div class="rv-field"><input type="text" name="phone" placeholder="Téléphone" value="{if $item.profile}{$item.profile->phone|escape}{/if}" required></div>
                                    <div class="rv-field">
                                        <select name="operator">
                                            {foreach $withdrawal_operators as $k => $label}
                                            <option value="{$k}" {if $item.profile && $item.profile->operator eq $k}selected{/if}>{$label}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="rv-btn rv-btn-save"><i class="fa fa-save"></i> Forcer la mise à jour</button>
                            </form>
                        </div>
                        {/foreach}
                    {else}
                        <div class="rv-empty" style="padding:28px 12px">
                            <i class="fa fa-user-times"></i>
                            Aucun client trouvé pour « {$withdrawal_search_term|escape} ».
                        </div>
                    {/if}
                {/if}
            </div>
        </div>

        <div class="rv-card">
            <div class="rv-card-head">
                <h2><i class="fa fa-history"></i> Historique des demandes</h2>
            </div>
            <div class="rv-card-body">
                <div class="rv-table-wrap">
                    <table class="rv-table rv-table-compact">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Réf.</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if $withdrawal_history|@count > 0}
                            {foreach $withdrawal_history as $h}
                            <tr>
                                <td>{$h->created_at|date_format:"%d/%m/%Y"}</td>
                                <td><span class="rv-ref">{$h->reference|escape}</span></td>
                                <td>{$h->admin_fullname|default:$h->beneficiary_name|escape}</td>
                                <td><span class="rv-amount">{number_format($h->amount,0,',',' ')}</span></td>
                                <td>
                                    {if $h->status eq 'approved'}<span class="rv-badge approved"><i class="fa fa-check"></i> Validée</span>
                                    {elseif $h->status eq 'pending'}<span class="rv-badge pending">En traitement</span>
                                    {elseif $h->status eq 'expired'}<span class="rv-badge expired">Expirée</span>
                                    {else}<span class="rv-badge rejected">Rejetée</span>{/if}
                                </td>
                                <td><span class="rv-note">{$h->admin_comment|default:'—'|escape}</span></td>
                            </tr>
                            {/foreach}
                            {else}
                            <tr>
                                <td colspan="6">
                                    <div class="rv-empty">
                                        <i class="fa fa-history"></i>
                                        Aucun historique disponible.
                                    </div>
                                </td>
                            </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="rvApproveForm" method="post" action="{Text::url('finance/reversement-approve-post')}" style="display:none">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="request_id" id="rvApproveId">
    <input type="hidden" name="transaction_id" id="rvApproveTx">
    <input type="hidden" name="comment" id="rvApproveComment">
</form>
<form id="rvRejectForm" method="post" action="{Text::url('finance/reversement-reject-post')}" style="display:none">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="request_id" id="rvRejectId">
    <input type="hidden" name="comment" id="rvRejectComment">
</form>

{literal}
<script>
function rvApprove(id, ref, amount) {
    var tx = prompt('Collez l\'ID de transaction opérateur pour ' + ref + ' (' + amount + ' Fcfa) :');
    if (tx === null) return;
    tx = (tx || '').trim();
    if (!tx) { alert('L\'ID de transaction est obligatoire.'); return; }
    document.getElementById('rvApproveId').value = id;
    document.getElementById('rvApproveTx').value = tx;
    document.getElementById('rvApproveComment').value = tx;
    document.getElementById('rvApproveForm').submit();
}
function rvReject(id) {
    var c = prompt('Motif du rejet (optionnel) :');
    if (c === null) return;
    document.getElementById('rvRejectId').value = id;
    document.getElementById('rvRejectComment').value = c;
    document.getElementById('rvRejectForm').submit();
}
(function () {
    document.querySelectorAll('[id^="rv-countdown-"]').forEach(function (el) {
        var exp = new Date((el.getAttribute('data-expires') || '').replace(' ', 'T')).getTime();
        function tick() {
            var left = exp - Date.now();
            if (left <= 0) {
                el.innerHTML = '<i class="fa fa-exclamation-circle"></i> Expiré';
                el.classList.add('expired');
                return;
            }
            var h = Math.floor(left / 3600000);
            var m = Math.floor((left % 3600000) / 60000);
            el.innerHTML = '<i class="fa fa-clock-o"></i> ' + h + 'h ' + String(m).padStart(2, '0') + 'm';
        }
        tick();
        setInterval(tick, 60000);
    });
})();
</script>
{/literal}

{include file="sections/footer.tpl"}
