{include file="sections/header.tpl"}

<div class="row" style="margin-bottom: 15px;">
    <div class="col-md-8">
        <form id="site-search" method="get" action="{Text::url('maps/customer')}">
            <input type="hidden" name="_route" value="maps/customer">
            <div class="input-group">
                <div class="input-group-addon">
                    <span class="fa fa-search"></span>
                </div>
                <input type="text" name="search" class="form-control" value="{$search|escape}" placeholder="{Lang::T('Search')}...">
                <div class="input-group-btn">
                    <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
                    {if $search}
                        <a class="btn btn-default" href="{Text::url('maps/customer')}">{Lang::T('Cancel')}</a>
                    {/if}
                </div>
            </div>
        </form>
    </div>
    <div class="col-md-4">
        <div class="well well-sm" style="margin-bottom: 0;">
            <strong>{$map_stats.mapped}</strong> {Lang::T('Map_Customers_On_Map')}
            &bull;
            <strong>{$map_stats.missing}</strong> {Lang::T('Map_Customers_Missing_Coords')}
        </div>
    </div>
</div>

{if $map_stats.mapped == 0}
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        {Lang::T('Map_Customers_Empty_Help')}
    </div>
{/if}

<div id="map" class="well" style="width: 100%; height: 70vh; margin: 0 auto 20px;"></div>

{if $customers_without_coords|@count > 0}
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">{Lang::T('Map_Customers_Without_Coordinates')}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{Lang::T('Full Name')}</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Address')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $customers_without_coords as $customer}
                        <tr>
                            <td>{$customer.name|escape}</td>
                            <td>{$customer.username|escape}</td>
                            <td>{if $customer.address}{$customer.address|escape}{else}-{/if}</td>
                            <td>{$customer.status|escape}</td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="{Text::url('customers/edit/', $customer.id)}">
                                    {Lang::T('Map_Set_Coordinates')}
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}

{literal}
<script>
(function () {
    var mapPoints = {/literal}{$map_points|json_encode}{literal};
    var mapCenter = {/literal}{$map_center|json_encode}{literal};
    var customerViewUrl = '{/literal}{Text::url('customers/view/')}{literal}';
    var customerEditUrl = '{/literal}{Text::url('customers/edit/')}{literal}';

    function initMap() {
        var map = L.map('map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var group = L.featureGroup().addTo(map);

        mapPoints.forEach(function (customer) {
            var lat = parseFloat(customer.lat);
            var lng = parseFloat(customer.lng);
            if (isNaN(lat) || isNaN(lng)) {
                return;
            }

            var markerOptions = {};
            if (customer.approximate) {
                markerOptions.icon = L.divIcon({
                    className: 'map-customer-marker map-customer-marker-approx',
                    html: '<div class="map-pin map-pin-approx"><i class="fa fa-map-marker"></i></div>',
                    iconSize: [24, 34],
                    iconAnchor: [12, 34]
                });
            }

            var marker = L.marker([lat, lng], markerOptions).addTo(group);
            var coordsText = lat + ',' + lng;
            var locationNote = customer.approximate
                ? '<br><em>{/literal}{Lang::T('Map_Location_From_Router')}{literal}</em>'
                : '';

            var popupContent =
                '<strong>{/literal}{Lang::T('Full Name')}{literal}</strong>: ' + (customer.name || '-') + '<br>' +
                '<strong>{/literal}{Lang::T('Username')}{literal}</strong>: ' + (customer.username || '-') + '<br>' +
                '<strong>{/literal}{Lang::T('Phone')}{literal}</strong>: ' + (customer.phonenumber || '-') + '<br>' +
                '<strong>{/literal}{Lang::T('Service Type')}{literal}</strong>: ' + (customer.service_type || '-') + '<br>' +
                '<strong>{/literal}{Lang::T('Balance')}{literal}</strong>: ' + (customer.balance || '0') + '<br>' +
                '<strong>{/literal}{Lang::T('Address')}{literal}</strong>: ' + (customer.address || '-') +
                locationNote + '<br>' +
                '<a href="' + customerViewUrl + customer.id + '">{/literal}{Lang::T('View')}{literal}</a> &bull; ' +
                '<a href="' + customerEditUrl + customer.id + '">{/literal}{Lang::T('Edit')}{literal}</a> &bull; ' +
                '<a href="https://www.google.com/maps/dir//' + coordsText + '" target="_blank" rel="noopener">{/literal}{Lang::T('Get_Directions')}{literal}</a>';

            marker.bindTooltip(customer.name || customer.username || ('#' + customer.id), {
                permanent: mapPoints.length <= 20,
                direction: 'top',
                offset: [0, -8]
            }).bindPopup(popupContent);
        });

        if (group.getLayers().length > 0) {
            map.fitBounds(group.getBounds().pad(0.15));
        } else {
            map.setView([mapCenter.lat, mapCenter.lng], mapCenter.zoom || 6);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }
})();
</script>
<style>
    .map-customer-marker-approx .map-pin-approx {
        color: #f39c12;
        font-size: 24px;
        line-height: 1;
        text-shadow: 0 0 2px #fff;
    }
</style>
{/literal}

{include file="sections/footer.tpl"}
