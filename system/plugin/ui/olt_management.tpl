{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ion ion-network"></i> {Lang::T('OLT_Management')}</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="info-box bg-aqua">
                            <span class="info-box-icon"><i class="ion ion-network"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">OLT</span>
                                <span class="info-box-number">{$olts|@count}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-sitemap"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">ODC</span>
                                <span class="info-box-number">{$odc_count}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="info-box bg-yellow">
                            <span class="info-box-icon"><i class="fa fa-map-marker"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">ODP</span>
                                <span class="info-box-number">{$odp_count}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="help-block">
                    {Lang::T('Manage fiber network from the map and ODP module. Add OLT records via database or future OLT CRUD.')}
                </p>
                <a href="{$maps_url}" class="btn btn-primary"><i class="fa fa-map"></i> {Lang::T('Network map / ODP')}</a>
            </div>
        </div>
        {if $olts|@count > 0}
        <div class="box box-default">
            <div class="box-header"><h3 class="box-title">OLT</h3></div>
            <table class="table table-bordered table-striped">
                <thead><tr><th>ID</th><th>{Lang::T('Name')}</th><th>IP</th></tr></thead>
                <tbody>
                    {foreach $olts as $olt}
                    <tr>
                        <td>{$olt.id}</td>
                        <td>{$olt.name|default:'—'}</td>
                        <td>{$olt.ip_address|default:'—'}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {/if}
    </div>
</div>

{include file="sections/footer.tpl"}
