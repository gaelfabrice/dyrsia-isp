{include file="sections/header.tpl"}
<section class="content-header"><h1>Conformité RGPD</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            <form method="post" class="form-inline" style="margin-bottom:15px">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <input type="number" name="customer_id" class="form-control" placeholder="ID client" required>
                <button type="submit" name="export_customer" value="1" class="btn btn-info">Exporter JSON</button>
            </form>
            <form method="post" onsubmit="return confirm('Anonymiser ce client ?');">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <input type="number" name="customer_id" class="form-control" placeholder="ID client" required>
                <button type="submit" name="erase_customer" value="1" class="btn btn-danger">Droit à l'effacement</button>
            </form>
        </div>
    </div>
</section>
{include file="sections/footer.tpl"}
