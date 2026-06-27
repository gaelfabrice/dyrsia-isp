{include file="sections/header.tpl"}
<section class="content-header"><h1>Cartographie réseau</h1></section>
<section class="content">
    <p><a class="btn btn-default" href="{U}plugin/wifizone_network_map&export=geojson">Export GeoJSON</a></p>
    <form method="post">{csrf_field()}
        <button type="submit" name="sync_routers" value="1" class="btn btn-primary">Importer les routeurs</button>
    </form>
    <table class="table table-bordered" style="margin-top:15px">
        <thead><tr><th>Type</th><th>Nom</th></tr></thead>
        <tbody>{foreach $nodes as $n}<tr><td>{$n.node_type}</td><td>{$n.name}</td></tr>{/foreach}</tbody>
    </table>
</section>
{include file="sections/footer.tpl"}
