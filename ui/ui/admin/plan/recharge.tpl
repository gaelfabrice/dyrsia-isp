{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Recharge Account')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}plan/recharge-confirm" id="plan-recharge-form">
                    <input type="hidden" name="plan_type" id="plan_type" value="">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Select Account')}</label>
                        <div class="col-md-6">
                            <select {if $cust}{else}id="personSelect" {/if} class="form-control select2"
                                name="id_customer" style="width: 100%"
                                data-placeholder="{Lang::T('Select a customer')}...">
                                {if $cust}
                                    <option value="{$cust['id']}">{$cust['username']} &bull; {$cust['fullname']} &bull;
                                        {$cust['email']}</option>
                                {/if}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Type')}</label>
                        <div class="col-md-6">
                            <label><input type="radio" id="Hot" name="type" value="Hotspot">
                                {Lang::T('Hotspot Plans')}</label>
                            <label><input type="radio" id="POE" name="type" value="PPPOE" checked>
                                {Lang::T('PPPOE Plans')}</label>
                            <label><input type="radio" id="VPN" name="type" value="VPN"> {Lang::T('VPN Plans')}</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Routers')}</label>
                        <div class="col-md-6">
                            <select id="server" data-type="server" name="server" class="form-control select2">
                                <option value=''>{Lang::T('Select Routers')}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Service Plan')}</label>
                        <div class="col-md-6">
                            <select id="plan" name="plan" class="form-control select2">
                                <option value=''>{Lang::T('Select Plans')}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Using')}</label>
                        <div class="col-md-6">
                            <select name="using" id="recharge_using" class="form-control"></select>
                        </div>
                        <p class="help-block col-md-4" id="recharge_using_help">{Lang::T('Postpaid Recharge for the first time use')}
                            {$_c['currency_code']} 0</p>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-success"
                                onclick="return ask(this, '{Lang::T('Continue the Recharge process')}?')"
                                type="submit">{Lang::T('Recharge')}</button>
                            {Lang::T('Or')} <a href="{Text::url('')}customers/list">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.PLAN_RECHARGE_UI = {
    pppoeOptions: {$pppoe_payment_options|@json_encode nofilter},
    legacyOptions: {$legacy_usings|@json_encode nofilter},
    enableBalance: {if $_c['enable_balance'] eq 'yes'}true{else}false{/if},
    isSuperAdmin: {if $is_superadmin_recharge}true{else}false{/if},
    balanceLabel: {Lang::T('Customer Balance')|@json_encode nofilter},
    zeroLabel: {($_c['currency_code']|cat:' 0')|@json_encode nofilter},
    gatewayLabel: {$mobile_gateway_label|@json_encode nofilter}
};
</script>
<script src="{$app_url}/ui/ui/scripts/plan-recharge.js?v=1"></script>

{include file="sections/footer.tpl"}
