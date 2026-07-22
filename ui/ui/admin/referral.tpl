{include file="admin/header.tpl"}

<section class="content-header">
    <h1><i class="fa fa-users text-success"></i> Parrainage <small>Gagnez des commissions en invitant d'autres ISP</small></h1>
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

<!-- RÉSUMÉ DU SOLDE -->
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fa fa-wallet"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Solde disponible</span>
                <span class="info-box-number">{$referral_stats.balance|number_format:0:",":" "} F</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-blue">
            <span class="info-box-icon"><i class="fa fa-user-plus"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Filleuls inscrits</span>
                <span class="info-box-number">{$referral_stats.total_filleuls}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-yellow">
            <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Filleuls convertis</span>
                <span class="info-box-number">{$referral_stats.converted_filleuls}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-purple">
            <span class="info-box-icon"><i class="fa fa-money"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total gagné</span>
                <span class="info-box-number">{$referral_stats.total_earned|number_format:0:",":" "} F</span>
            </div>
        </div>
    </div>
</div>

<div class="row">

    <!-- LIEN DE PARRAINAGE -->
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-link"></i> Votre lien de parrainage</h3>
            </div>
            <div class="box-body">
                <div class="alert alert-info" style="font-size:13px;margin-bottom:10px;">
                    <i class="fa fa-info-circle"></i>
                    <strong>Conditions :</strong> Votre lien est actif uniquement si votre abonnement est actif (Business ou Pro).
                    La commission est versée <strong>une seule fois</strong> lors du premier abonnement du filleul.
                </div>
                <div class="input-group">
                    <input type="text" id="referralUrl" class="form-control" value="{$referral_url|escape}" readonly>
                    <span class="input-group-btn">
                        <button class="btn btn-success" onclick="copyReferralUrl()" type="button">
                            <i class="fa fa-copy"></i> Copier
                        </button>
                    </span>
                </div>
                <div style="margin-top:8px;">
                    <small class="text-muted">Code : <strong>{$referral_code}</strong></small>
                </div>
                <div style="margin-top:12px;">
                    <table class="table table-condensed table-bordered" style="font-size:13px;">
                        <thead><tr class="bg-gray">
                            <th>Forfait filleul</th>
                            <th>Votre commission</th>
                        </tr></thead>
                        <tbody>
                        <tr><td>Business (2 500 F)</td><td class="text-success"><strong>+1 000 F</strong></td></tr>
                        <tr><td>Pro (10 000 F)</td><td class="text-success"><strong>+2 000 F</strong></td></tr>
                        </tbody>
                    </table>
                    <small class="text-muted"><i class="fa fa-exclamation-triangle"></i> Commission unique sur le premier abonnement uniquement. Pas de commission sur les renouvellements.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- RETRAIT -->
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-bank"></i> Demander un retrait</h3>
            </div>
            <div class="box-body">
                <div class="row" style="margin-bottom:10px;">
                    <div class="col-xs-6"><strong>Solde disponible :</strong></div>
                    <div class="col-xs-6 text-right text-success"><strong>{$referral_stats.balance|number_format:0:",":" "} F</strong></div>
                </div>
                {if $referral_stats.pending_withdrawal > 0}
                <div class="alert alert-warning" style="font-size:13px;">
                    <i class="fa fa-clock-o"></i> Retrait en attente : <strong>{$referral_stats.pending_withdrawal|number_format:0:",":" "} F</strong>
                </div>
                {/if}
                {if $referral_stats.balance >= $withdrawal_min}
                <form method="POST" action="{Text::url('referral/withdraw')}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="form-group">
                        <label>Montant à retirer (min. {$withdrawal_min|number_format:0:",":" "} F)</label>
                        <input type="number" name="amount" class="form-control"
                               min="{$withdrawal_min}" max="{$referral_stats.balance}"
                               step="100" placeholder="{$withdrawal_min}" required>
                    </div>
                    <div class="alert alert-info" style="font-size:12px;padding:8px 12px;">
                        <i class="fa fa-info-circle"></i> Frais de retrait : <strong>10%</strong> déduits automatiquement.
                        <br>Ex : 2 000 F demandés → 200 F de frais → <strong>1 800 F</strong> reçus.
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-paper-plane"></i> Envoyer la demande
                    </button>
                </form>
                {else}
                <div class="alert alert-warning">
                    <i class="fa fa-lock"></i> Solde insuffisant. Le minimum de retrait est de
                    <strong>{$withdrawal_min|number_format:0:",":" "} F</strong>.
                    Il vous manque <strong>{($withdrawal_min - $referral_stats.balance)|number_format:0:",":" "} F</strong>.
                </div>
                {/if}
            </div>
        </div>
    </div>
</div>

<!-- HISTORIQUE COMMISSIONS -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-history"></i> Historique des commissions</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Filleul (Admin ID)</th>
                            <th>Forfait</th>
                            <th class="text-right">Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                    {if $referral_commissions|@count > 0}
                    {foreach $referral_commissions as $c}
                    <tr>
                        <td>{$c.created_at}</td>
                        <td>#{$c.referee_id}</td>
                        <td>
                            {if $c.plan_type eq 'pro'}
                            <span class="label label-primary">Forfait Pro</span>
                            {else}
                            <span class="label label-success">Forfait Business</span>
                            {/if}
                        </td>
                        <td class="text-right text-success"><strong>+{$c.amount|number_format:0:",":" "} F</strong></td>
                    </tr>
                    {/foreach}
                    {else}
                    <tr><td colspan="4" class="text-center text-muted">Aucune commission pour le moment.</td></tr>
                    {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- HISTORIQUE RETRAITS -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-bank"></i> Historique des retraits</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Montant</th>
                            <th class="text-right">Frais (10%)</th>
                            <th class="text-right">Net perçu</th>
                            <th>Statut</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                    {if $referral_withdrawals|@count > 0}
                    {foreach $referral_withdrawals as $w}
                    <tr>
                        <td>{$w.created_at}</td>
                        <td class="text-right">{$w.amount|number_format:0:",":" "} F</td>
                        <td class="text-right text-danger">-{$w.fee|number_format:0:",":" "} F</td>
                        <td class="text-right text-success"><strong>{$w.net_amount|number_format:0:",":" "} F</strong></td>
                        <td>
                            {if $w.status eq 'paid'}
                            <span class="label label-success">Payé</span>
                            {elseif $w.status eq 'pending'}
                            <span class="label label-warning">En attente</span>
                            {else}
                            <span class="label label-danger">Rejeté</span>
                            {/if}
                        </td>
                        <td>{if $w.note}<small class="text-muted">{$w.note|escape}</small>{/if}</td>
                    </tr>
                    {/foreach}
                    {else}
                    <tr><td colspan="6" class="text-center text-muted">Aucun retrait effectué.</td></tr>
                    {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</section>

<script>
function copyReferralUrl() {
    var input = document.getElementById('referralUrl');
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        document.execCommand('copy');
        if (window.Swal) {
            Swal.fire({ icon: 'success', title: 'Copié !', text: 'Lien de parrainage copié dans le presse-papiers.', timer: 2000, showConfirmButton: false });
        } else {
            alert('Lien copié !');
        }
    } catch(e) {
        if (window.Swal) {
            Swal.fire({ icon: 'info', title: 'Lien', text: input.value });
        }
    }
}
</script>

{include file="admin/footer.tpl"}
