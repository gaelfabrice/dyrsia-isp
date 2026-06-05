{include file="sections/header.tpl"}

<style>
.rv-page { padding: 0 4px 24px; }
.rv-kpi { border-radius: 12px; padding: 20px; color: #fff; margin-bottom: 18px; min-height: 100px; }
.rv-kpi h3 { margin: 0 0 6px; font-size: 28px; font-weight: 700; }
.rv-kpi p { margin: 0; opacity: .9; font-size: 13px; }
.rv-kpi-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.rv-kpi-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.rv-kpi-teal { background: linear-gradient(135deg, #14b8a6, #0f766e); }
.rv-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
.rv-card h4 { margin: 0 0 16px; font-weight: 700; }
.rv-actions .btn { margin-right: 4px; margin-bottom: 4px; }
.rv-profile-search { margin-bottom: 16px; }
</style>

<div class="rv-page">
    <section class="content-header">
        <h1>Reversement <small>Finance SuperAdmin</small></h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-4">
                <div class="rv-kpi rv-kpi-blue">
                    <h3>{number_format($withdrawal_stats.gross_revenue,0,',',' ')} Fcfa</h3>
                    <p>Chiffre d'Affaires Brut (ventes passerelle)</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="rv-kpi rv-kpi-purple">
                    <h3>{number_format($withdrawal_stats.platform_commission,0,',',' ')} Fcfa</h3>
                    <p>Total Commissions Plateforme</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="rv-kpi rv-kpi-teal">
                    <h3>{number_format($withdrawal_stats.due_to_clients,0,',',' ')} Fcfa</h3>
                    <p>Total Dû aux clients (soldes restants)</p>
                </div>
            </div>
        </div>

        <div class="rv-card">
            <h4><i class="fa fa-clock-o text-warning"></i> Demandes en traitement</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>ID Client</th>
                            <th>Nom Vendeur</th>
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
                                <td>#{$p->admin_id}</td>
                                <td>{$p->admin_fullname|default:$p->beneficiary_name|escape}</td>
                                <td>
                                    {foreach $withdrawal_operators as $k => $label}{if $p->operator eq $k}{$label}{/if}{/foreach}
                                    <br><small>+237{$p->beneficiary_phone|escape}</small>
                                </td>
                                <td><strong>{number_format($p->amount,0,',',' ')} Fcfa</strong></td>
                                <td id="rv-countdown-{$p->id}" data-expires="{$p->expires_at}">—</td>
                                <td><span class="label label-warning">En traitement</span></td>
                                <td class="rv-actions">
                                    <button type="button" class="btn btn-success btn-sm" onclick="rvApprove({$p->id}, '{$p->reference|escape:'javascript'}', '{number_format($p->amount,0,',',' ')}')"><i class="fa fa-check"></i> Valider</button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="rvReject({$p->id})"><i class="fa fa-times"></i> Rejeter</button>
                                </td>
                            </tr>
                            {/foreach}
                        {else}
                            <tr><td colspan="8" class="text-center text-muted">Aucune demande en attente.</td></tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="rv-card">
                    <h4><i class="fa fa-edit"></i> Modification des profils de retrait</h4>
                    <form method="get" action="{$app_url}/" class="rv-profile-search form-inline">
                        <input type="hidden" name="_route" value="finance/reversement">
                        <div class="input-group" style="width:100%">
                            <input type="text" name="q" class="form-control" placeholder="Rechercher un client (nom, username, téléphone)..." value="{$withdrawal_search_term|escape}" autocomplete="off">
                            <span class="input-group-btn"><button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button></span>
                        </div>
                    </form>
                    {if $withdrawal_search_term}
                        {if $withdrawal_search_items|@count > 0}
                            {foreach $withdrawal_search_items as $item}
                                <div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:12px">
                                    <strong>{$item.admin->fullname|escape}</strong> <small class="text-muted">@{$item.admin->username|escape}</small>
                                    <form method="post" action="{Text::url('finance/reversement-profile-post')}" style="margin-top:10px">
                                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                        <input type="hidden" name="admin_id" value="{$item.admin->id}">
                                        <input type="hidden" name="search_term" value="{$withdrawal_search_term|escape}">
                                        <div class="row">
                                            <div class="col-sm-6"><input type="text" name="first_name" class="form-control input-sm" placeholder="Prénom" value="{if $item.profile}{$item.profile->first_name|escape}{/if}" required></div>
                                            <div class="col-sm-6"><input type="text" name="last_name" class="form-control input-sm" placeholder="Nom" value="{if $item.profile}{$item.profile->last_name|escape}{/if}" required></div>
                                        </div>
                                        <div class="row" style="margin-top:8px">
                                            <div class="col-sm-6"><input type="text" name="phone" class="form-control input-sm" placeholder="Téléphone" value="{if $item.profile}{$item.profile->phone|escape}{/if}" required></div>
                                            <div class="col-sm-6">
                                                <select name="operator" class="form-control input-sm">
                                                    {foreach $withdrawal_operators as $k => $label}
                                                        <option value="{$k}" {if $item.profile && $item.profile->operator eq $k}selected{/if}>{$label}</option>
                                                    {/foreach}
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-warning btn-sm btn-block" style="margin-top:8px"><i class="fa fa-save"></i> Forcer la mise à jour</button>
                                    </form>
                                </div>
                            {/foreach}
                        {else}
                            <p class="text-muted">Aucun client trouvé.</p>
                        {/if}
                    {/if}
                </div>
            </div>
            <div class="col-md-7">
                <div class="rv-card">
                    <h4><i class="fa fa-history"></i> Historique des demandes</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Réf</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $withdrawal_history as $h}
                                <tr>
                                    <td>{$h->created_at|date_format:"%d/%m/%Y"}</td>
                                    <td><code>{$h->reference|escape}</code></td>
                                    <td>{$h->admin_fullname|default:$h->beneficiary_name|escape}</td>
                                    <td>{number_format($h->amount,0,',',' ')}</td>
                                    <td>
                                        {if $h->status eq 'approved'}<span class="label label-success">Validée</span>
                                        {elseif $h->status eq 'pending'}<span class="label label-warning">En traitement</span>
                                        {elseif $h->status eq 'expired'}<span class="label label-danger">Expirée</span>
                                        {else}<span class="label label-default">Rejetée</span>{/if}
                                    </td>
                                    <td><small>{$h->admin_comment|default:'—'|escape}</small></td>
                                </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
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
            if (left <= 0) { el.innerHTML = '<span class="text-danger">Expiré</span>'; return; }
            var h = Math.floor(left / 3600000);
            var m = Math.floor((left % 3600000) / 60000);
            el.innerHTML = '<span class="text-warning"><i class="fa fa-clock-o"></i> ' + h + 'h ' + String(m).padStart(2, '0') + 'm</span>';
        }
        tick();
        setInterval(tick, 60000);
    });
})();
</script>

{include file="sections/footer.tpl"}
