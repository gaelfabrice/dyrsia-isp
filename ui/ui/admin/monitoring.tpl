{include file="sections/header.tpl"}

<div class="wz-orbit-page">
    <div class="wz-orbit-hero">
        <div class="wz-orbit-hero-copy">
            <span class="wz-orbit-kicker">{Lang::T('Monitoring')}</span>
            <h2>{Lang::T('Network & Customer Monitoring')}</h2>
            <p>{Lang::T('Monitoring_subtitle')}</p>
        </div>
    </div>

    <div class="row wz-orbit-row">
        <div class="col-md-4 col-sm-6 wz-orbit-col">
            <a href="{Text::url('customers/list')}" class="small-box bg-aqua">
                <div class="inner"><h3>{$c_all}</h3><p>CUSTOMER TOTAL</p></div>
                <div class="icon"><i class="ion ion-android-people"></i></div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6 wz-orbit-col">
            <a href="index.php?_route=customers/list&filter_type=Hotspot&filter_status=All" class="small-box bg-green">
                <div class="inner"><h3>{$h_all}</h3><p>TOTAL HOTSPOT</p></div>
                <div class="icon"><i class="ion ion-wifi"></i></div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6 wz-orbit-col">
            <a href="index.php?_route=customers/list&filter_type=PPPoE&filter_status=All" class="small-box bg-purple">
                <div class="inner"><h3>{$p_all}</h3><p>TOTAL PPPOE</p></div>
                <div class="icon"><i class="ion ion-network"></i></div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6 wz-orbit-col">
            <a href="index.php?_route=plugin/hotspot_online_ui" class="small-box bg-teal">
                <div class="inner"><h3>{$h_act}</h3><p>HOTSPOT Online</p></div>
                <div class="icon"><i class="ion ion-ios-pulse-strong"></i></div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6 wz-orbit-col">
            <a href="index.php?_route=plugin/pppoe_online_ui" class="small-box bg-maroon">
                <div class="inner"><h3>{$p_act}</h3><p>PPPOE online</p></div>
                <div class="icon"><i class="ion ion-radio-waves"></i></div>
            </a>
        </div>
    </div>
    <div class="row wz-orbit-row">
        <div class="col-md-12 wz-orbit-col">
            {$monthly_registered_widget}
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
