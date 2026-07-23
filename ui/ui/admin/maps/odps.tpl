{include file="sections/header.tpl"}


<form id="site-search" method="get" action="{Text::url('maps/odp')}">
    <input type="hidden" name="_route" value="maps/odp">
    <div class="input-group">
        <div class="input-group-addon">
            <span class="fa fa-search"></span>
        </div>
        <input type="text" name="name" class="form-control" value="{$name|escape}" placeholder="{Lang::T('Search')}...">
        <div class="input-group-btn">
            <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
        </div>
    </div>
</form>

<div id="map" class="well" style="width: 100%; height: 70vh; margin: 20px auto"></div>

{literal}
<script>
(function () {
    var odps = {/literal}{$d|json_encode}{literal};
    var mapCenter = {/literal}{$map_center|json_encode}{literal};
    var odpEditUrl = '{/literal}{Text::url('odp/edit/')}{literal}';

    function initMap() {
        var map = L.map('map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var group = L.featureGroup().addTo(map);

        odps.forEach(function (odp) {
            var lat = parseFloat(odp.lat);
            var lng = parseFloat(odp.lng);
            if (isNaN(lat) || isNaN(lng)) {
                return;
            }

            var coordsText = lat + ',' + lng;
            var popupContent =
                '<strong>{/literal}{Lang::T('Name')}{literal}</strong>: ' + (odp.name || '-') + '<br>' +
                '<strong>{/literal}{Lang::T('Port_Amount')}{literal}</strong>: ' + (odp.port_amount || '-') + '<br>' +
                '<strong>{/literal}{Lang::T('Address')}{literal}</strong>: ' + (odp.address || '-') + '<br>' +
                '<strong>{/literal}{Lang::T('Coverage')}{literal}</strong>: ' + (odp.coverage || '0') + ' m<br>' +
                '<a href="' + odpEditUrl + odp.id + '">{/literal}{Lang::T('Edit')}{literal}</a> &bull; ' +
                '<a href="https://www.google.com/maps/dir//' + coordsText + '" target="_blank" rel="noopener">{/literal}{Lang::T('Get_Directions')}{literal}</a>';

            if (odp.coverage > 0) {
                L.circle([lat, lng], {
                    radius: odp.coverage * 1,
                    color: 'blue',
                    fillOpacity: 0.1
                }).addTo(map);
            }

            L.marker([lat, lng]).addTo(group)
                .bindTooltip(odp.name || ('#' + odp.id), { permanent: odps.length <= 20 })
                .bindPopup(popupContent);
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
{/literal}
{include file="sections/footer.tpl"}
