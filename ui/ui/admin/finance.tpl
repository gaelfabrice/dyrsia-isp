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
</div>

{include file="sections/footer.tpl"}
