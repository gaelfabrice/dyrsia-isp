{include file="sections/header.tpl"}
<section class="content-header"><h1>Assistant FreeRADIUS</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-body">
        <p>Tables Radius: {if $status.radius_tables}<span class="text-green">Détectées</span>{else}<span class="text-red">Non trouvées — importez radius.sql</span>{/if}</p>
        <form method="post">{csrf_field()}
            <button type="submit" name="sync_plans" value="1" class="btn btn-primary">Synchroniser les forfaits Radius</button>
        </form>
    </div></div>
</section>
{include file="sections/footer.tpl"}
