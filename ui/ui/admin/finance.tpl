{include file="sections/header.tpl"}

<div class="wz-orbit-page">
    <div class="wz-orbit-hero">
        <div class="wz-orbit-hero-copy">
            <span class="wz-orbit-kicker">{Lang::T('Finance')}</span>
            <h2>{Lang::T('Revenue & Wallet')}</h2>
            <p>{Lang::T('Financial indicators have been moved here to keep the dashboard focused and readable.')}</p>
        </div>
    </div>

    <div class="row wz-orbit-row">
        <div class="col-md-3 col-sm-6 wz-orbit-col">
            <a href="{Text::url('plugin/admin_wallet')}" class="small-box bg-orange">
                <div class="inner"><h3>{$_c['currency_code']} {$w_balance|default:'0.00'}</h3><p>WALLET BALANCE</p></div>
                <div class="icon"><i class="ion ion-cash"></i></div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 wz-orbit-col">
            <a href="{Text::url('plugin/admin_wallet_commission')}" class="small-box bg-purple">
                <div class="inner"><h3>{$_c['currency_code']} {$w_commission|default:'0.00'}</h3><p>WALLET COMMISSION</p></div>
                <div class="icon"><i class="ion ion-stats-bars"></i></div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 wz-orbit-col">
            <a href="{Text::url('reports/by-date')}" class="small-box bg-aqua">
                <div class="inner"><h3>{$_c['currency_code']} {number_format($iday,0)}</h3><p>INCOME DAILY</p></div>
                <div class="icon"><i class="ion ion-clock"></i></div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 wz-orbit-col">
            <a href="{Text::url('reports/by-period')}" class="small-box bg-green">
                <div class="inner"><h3>{$_c['currency_code']} {number_format($imonth,0)}</h3><p>INCOME MONTHLY</p></div>
                <div class="icon"><i class="ion ion-android-calendar"></i></div>
            </a>
        </div>
    </div>
    <div class="row wz-orbit-row">
        <div class="col-md-12 wz-orbit-col">
            {$monthly_sales_widget}
        </div>
    </div>

    {if $_admin['user_type'] eq 'Admin'}
    <div class="row wz-orbit-row" style="margin-top:8px">
        <div class="col-md-6">
            <a href="{Text::url('finance/withdrawals')}" class="small-box bg-green" style="display:block;text-decoration:none;color:#fff">
                <div class="inner">
                    <h3><i class="fa fa-money"></i> Retraits</h3>
                    <p>Demande de retrait — solde passerelle, profil Mobile Money, historique</p>
                </div>
                <div class="icon"><i class="fa fa-arrow-right"></i></div>
                <span class="small-box-footer">Ouvrir Demande de retrait <i class="fa fa-arrow-circle-right"></i></span>
            </a>
        </div>
    </div>
    {/if}
    {if $_admin['user_type'] eq 'SuperAdmin'}
    <div class="row wz-orbit-row" style="margin-top:8px">
        <div class="col-md-6">
            <a href="{Text::url('finance/reversement')}" class="small-box bg-yellow" style="display:block;text-decoration:none;color:#fff">
                <div class="inner">
                    <h3><i class="fa fa-exchange"></i> Reversement{if $withdrawal_pending_count|default:0 > 0} <span class="label label-danger">{$withdrawal_pending_count} en attente</span>{/if}</h3>
                    <p>Valider ou rejeter les demandes de retrait des clients</p>
                </div>
                <div class="icon"><i class="fa fa-bell"></i></div>
                <span class="small-box-footer">Ouvrir Reversement <i class="fa fa-arrow-circle-right"></i></span>
            </a>
        </div>
    </div>
    {/if}
</div>

{include file="sections/footer.tpl"}
