{include file="sections/header.tpl"}
<form method="post">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="panel panel-info panel-hovered">
                <div class="panel-heading">{Lang::T('Payment Gateway')}</div>
                <div class="table-responsive">
                    <table class="table table-striped table-condensed">
                        <tbody>
                            <tr>
                                <td colspan="3">
                                    <div class="alert alert-warning" style="margin:0">
                                        CamPay et MyPVit sont exclusifs : une seule passerelle mobile peut être active.
                                        {if $active_mobile_gateway}
                                            Actuelle : <strong>{$active_mobile_gateway|ucwords}</strong>
                                        {/if}
                                    </div>
                                </td>
                            </tr>
                            {foreach $pg_rows as $pgRow}
                                {assign var=pg value=$pgRow.name}
                                <tr>
                                    <td width="10" align="center" valign="center">
                                        {if $pgRow.is_mobile}
                                            <input type="radio" name="mobile_pg" value="{$pg}"
                                                {if $pg eq $active_mobile_gateway}checked{/if}
                                                onclick="document.getElementById('pg_{$pg}').checked = true; clearOtherMobile('{$pg}');">
                                            <input type="checkbox" name="pgs[]" id="pg_{$pg}" class="mobile-pg-checkbox" data-mobile="1"
                                                {if in_array($pg, $actives)}checked{/if} value="{$pg}" style="display:none">
                                        {else}
                                            <input type="checkbox" name="pgs[]"
                                                {if in_array($pg, $actives)}checked{/if} value="{$pg}">
                                        {/if}
                                    </td>
                                    <td><a href="{Text::url('paymentgateway/')}{$pg}"
                                            class="btn btn-block btn-{if in_array($pg, $actives)}info{else}default{/if} text-left">{ucwords($pg)}
                                            {if in_array($pg, $actives)}<span class="label label-success pull-right">{Lang::T('Active Gateway')}</span>{/if}</a>
                                    </td>
                                    <td width="114">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="btn-group" role="group">
                                                <a href="{Text::url('paymentgateway/audit/')}{$pg}"
                                                    class="btn btn-success text-black">Audit</a>
                                            </div>
                                            <div class="btn-group" role="group">
                                                <a href="{Text::url('paymentgateway/delete/')}{$pg}"
                                                    onclick="return ask(this, '{Lang::T('Delete')} {$pg}?')"
                                                    class="btn btn-danger"><i class="glyphicon glyphicon-trash"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="panel-footer">
                    <button type="submit" class="btn btn-primary btn-block" name="save"
                        value="actives">{Lang::T('Save Changes')}</button>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
function clearOtherMobile(active) {
    document.querySelectorAll('.mobile-pg-checkbox').forEach(function (el) {
        el.checked = el.value === active;
    });
}
document.querySelectorAll('input[name="mobile_pg"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        if (this.checked) clearOtherMobile(this.value);
    });
});
</script>
{include file="sections/footer.tpl"}