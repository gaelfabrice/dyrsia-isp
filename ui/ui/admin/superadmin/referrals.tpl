{include file="admin/header.tpl"}

<section class="content-header">
    <h1><i class="fa fa-users text-success"></i> Parrainage — Gestion des retraits <small>SuperAdmin</small></h1>
    <ol class="breadcrumb">
        <li><a href="{Text::url('dashboard')}"><i class="fa fa-home"></i> Dashboard</a></li>
        <li class="active">Parrainage</li>
    </ol>
</section>

<section class="content">

{if isset($notify)}
<div class="alert alert-{if $notify_t eq 's'}success{elseif $notify_t eq 'i'}info{else}danger{/if} alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {$notify}
</div>
{/if}

<!-- STATS PLATEFORME -->
<div class="row">
    <div class="col-md-2 col-sm-4">
        <div class="info-box bg-blue">
            <span class="info-box-icon"><i class="fa fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Parrains actifs</span>
                <span class="info-box-number">{$referral_platform_stats.total_parrains}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fa fa-user-plus"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Filleuls convertis</span>
                <span class="info-box-number">{$referral_platform_stats.total_converted} / {$referral_platform_stats.total_filleuls}</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="info-box bg-yellow">
            <span class="info-box-icon"><i class="fa fa-money"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total commissions</span>
                <span class="info-box-number">{number_format($referral_platform_stats.total_commission,0,',',' ')} F</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="info-box bg-purple">
            <span class="info-box-icon"><i class="fa fa-bank"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Reversé</span>
                <span class="info-box-number">{number_format($referral_platform_stats.total_paid_out,0,',',' ')} F</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="info-box bg-red">
            <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">En attente</span>
                <span class="info-box-number">{number_format($referral_platform_stats.pending_payout,0,',',' ')} F</span>
            </div>
        </div>
    </div>
</div>

<!-- RETRAITS EN ATTENTE -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-clock-o"></i> Retraits en attente
                    {if $referral_pending_withdrawals|@count > 0}
                    <span class="label label-danger">{$referral_pending_withdrawals|@count}</span>
                    {/if}
                </h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Admin</th>
                            <th>Téléphone</th>
                            <th class="text-right">Montant</th>
                            <th class="text-right">Frais (10%)</th>
                            <th class="text-right">Net à verser</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    {if $referral_pending_withdrawals|@count > 0}
                    {foreach $referral_pending_withdrawals as $w}
                    <tr>
                        <td>{$w.id}</td>
                        <td>
                            <strong>{$w.admin_fullname|default:$w.admin_username|escape}</strong>
                            <br><small class="text-muted">{$w.admin_username|escape}</small>
                        </td>
                        <td>{$w.admin_phone|escape}</td>
                        <td class="text-right">{number_format($w.amount,0,',',' ')} F</td>
                        <td class="text-right text-danger">-{number_format($w.fee,0,',',' ')} F</td>
                        <td class="text-right text-success"><strong>{number_format($w.net_amount,0,',',' ')} F</strong></td>
                        <td><small>{$w.created_at}</small></td>
                        <td>
                            <button class="btn btn-xs btn-success" onclick="confirmAction({$w.id}, 'approve', '{$w.admin_fullname|default:$w.admin_username|escape}', {$w.net_amount})">
                                <i class="fa fa-check"></i> Approuver
                            </button>
                            <button class="btn btn-xs btn-danger" onclick="confirmAction({$w.id}, 'reject', '{$w.admin_fullname|default:$w.admin_username|escape}', {$w.amount})">
                                <i class="fa fa-times"></i> Rejeter
                            </button>
                        </td>
                    </tr>
                    {/foreach}
                    {else}
                    <tr><td colspan="8" class="text-center text-muted">Aucun retrait en attente.</td></tr>
                    {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- HISTORIQUE DE TOUS LES RETRAITS -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-history"></i> Historique des retraits</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Admin</th>
                            <th class="text-right">Montant</th>
                            <th class="text-right">Frais</th>
                            <th class="text-right">Net</th>
                            <th>Statut</th>
                            <th>Note</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    {if $referral_all_withdrawals|@count > 0}
                    {foreach $referral_all_withdrawals as $w}
                    <tr>
                        <td>{$w.id}</td>
                        <td>{$w.admin_fullname|default:$w.admin_username|escape}</td>
                        <td class="text-right">{number_format($w.amount,0,',',' ')} F</td>
                        <td class="text-right text-danger">-{number_format($w.fee,0,',',' ')} F</td>
                        <td class="text-right text-success">{number_format($w.net_amount,0,',',' ')} F</td>
                        <td>
                            {if $w.status eq 'paid'}
                            <span class="label label-success">Payé</span>
                            {elseif $w.status eq 'pending'}
                            <span class="label label-warning">En attente</span>
                            {else}
                            <span class="label label-danger">Rejeté</span>
                            {/if}
                        </td>
                        <td><small class="text-muted">{if $w.note}{$w.note|escape}{/if}</small></td>
                        <td><small>{$w.created_at}</small></td>
                    </tr>
                    {/foreach}
                    {else}
                    <tr><td colspan="8" class="text-center text-muted">Aucun retrait enregistré.</td></tr>
                    {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation action -->
<div class="modal fade" id="actionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="actionModalTitle">Confirmer</h4>
            </div>
            <div class="modal-body">
                <p id="actionModalText"></p>
                <div class="form-group">
                    <label for="actionNote">Note (optionnelle)</label>
                    <input type="text" id="actionNote" class="form-control" placeholder="Référence de paiement, commentaire...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                <form id="actionForm" method="POST" action="{Text::url('superadmin/referral-action')}" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <input type="hidden" name="withdrawal_id" id="actionWithdrawalId" value="">
                    <input type="hidden" name="do" id="actionDo" value="">
                    <input type="hidden" name="note" id="actionNoteHidden" value="">
                    <button type="submit" class="btn btn-primary" id="actionSubmitBtn">Confirmer</button>
                </form>
            </div>
        </div>
    </div>
</div>

</section>

<script>
function confirmAction(wdId, doAction, adminName, amount) {
    document.getElementById('actionWithdrawalId').value = wdId;
    document.getElementById('actionDo').value = doAction;
    document.getElementById('actionNote').value = '';
    document.getElementById('actionNoteHidden').value = '';

    var fmtAmt = new Intl.NumberFormat('fr-FR').format(amount);

    if (doAction === 'approve') {
        document.getElementById('actionModalTitle').innerHTML = '<i class="fa fa-check text-success"></i> Approuver le retrait';
        document.getElementById('actionModalText').innerHTML =
            'Vous allez approuver le retrait de <strong>' + adminName + '</strong>.<br>' +
            'Montant net à verser : <strong class="text-success">' + fmtAmt + ' F</strong>';
        document.getElementById('actionSubmitBtn').className = 'btn btn-success';
        document.getElementById('actionSubmitBtn').textContent = 'Approuver';
    } else {
        document.getElementById('actionModalTitle').innerHTML = '<i class="fa fa-times text-danger"></i> Rejeter le retrait';
        document.getElementById('actionModalText').innerHTML =
            'Vous allez rejeter le retrait de <strong>' + adminName + '</strong>.<br>' +
            'Montant <strong>' + fmtAmt + ' F</strong> sera recrédité sur son solde.';
        document.getElementById('actionSubmitBtn').className = 'btn btn-danger';
        document.getElementById('actionSubmitBtn').textContent = 'Rejeter';
    }

    document.getElementById('actionNote').addEventListener('input', function() {
        document.getElementById('actionNoteHidden').value = this.value;
    });

    $('#actionModal').modal('show');
}
</script>

{include file="admin/footer.tpl"}
