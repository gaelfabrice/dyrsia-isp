{include file="sections/header.tpl"}
<section class="content-header">
    <h1>WifiZone <small>Tableau de bord unifié</small></h1>
</section>
<section class="content">
    {$kpi_html}
    <div class="box box-primary">
        <div class="box-header"><h3 class="box-title">État système</h3></div>
        <div class="box-body">
            <ul>
                <li>Base de données: {if $health.database}<span class="text-green">OK</span>{else}<span class="text-red">Erreur</span>{/if}</li>
                <li>Cron récent: {if $health.cron_marker}<span class="text-green">OK</span>{else}<span class="text-yellow">À vérifier</span>{/if}</li>
                <li>Dossier uploads: {if $health.writable_uploads}<span class="text-green">OK</span>{else}<span class="text-red">Non accessible</span>{/if}</li>
            </ul>
        </div>
    </div>
</section>
{include file="sections/footer.tpl"}
