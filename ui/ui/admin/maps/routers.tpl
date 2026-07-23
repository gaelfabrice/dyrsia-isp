{include file="sections/header.tpl"}


<form id="site-search" method="get" action="{Text::url('maps/routers')}">
    <input type="hidden" name="_route" value="maps/routers">
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
    var routers = {/literal}{$d|json_encode}{literal};
    var mapCenter = {/literal}{$map_center|json_encode}{literal};
    var routerEditUrl = '{/literal}{Text::url('routers/edit/')}{literal}';

    function initMap() {
        var map = L.map('map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var group = L.featureGroup().addTo(map);

        routers.forEach(function (router) {
            var lat = parseFloat(router.lat);
            var lng = parseFloat(router.lng);
            if (isNaN(lat) || isNaN(lng)) {
                return;
            }

            var coordsText = lat + ',' + lng;
            var popupContent =
                '<strong>{/literal}{Lang::T('Name')}{literal}</strong>: ' + (router.name || '-') + '<br>' +
                '<strong>{/literal}{Lang::T('Description')}{literal}</strong>: ' + (router.description || '-') + '<br>' +
                '<a href="' + routerEditUrl + router.id + '">{/literal}{Lang::T('Edit')}{literal}</a> &bull; ' +
                '<a href="https://www.google.com/maps/dir//' + coordsText + '" target="_blank" rel="noopener">{/literal}{Lang::T('Get_Directions')}{literal}</a>';

            var color = router.enabled == 1 ? 'blue' : 'red';
            if (router.coverage > 0) {
                L.circle([lat, lng], {
                    radius: router.coverage * 1,
                    color: color,
                    fillOpacity: 0.1
                }).addTo(map);
            }

            L.marker([lat, lng]).addTo(group)
                .bindTooltip(router.name || ('#' + router.id), { permanent: routers.length <= 20 })
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
