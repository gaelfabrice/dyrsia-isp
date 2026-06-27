{include file="sections/header.tpl"}
<section class="content-header"><h1>API Mobile (JWT)</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-body">
        <p>Base URL: <code>{$api_base}</code></p>
        <p>Header: <code>Authorization: Bearer &lt;token&gt;</code></p>
        <p>Exemple token (24h): <code style="word-break:break-all">{$jwt_sample}</code></p>
        <ul>
            <li>GET <code>?r=wifizone_api/me</code></li>
            <li>GET <code>?r=wifizone_api/customers</code></li>
            <li>GET <code>?r=wifizone_api/routers</code></li>
            <li>POST <code>?r=wifizone_api/recharge</code> (customer_id, plan_id, router)</li>
        </ul>
    </div></div>
</section>
{include file="sections/footer.tpl"}
