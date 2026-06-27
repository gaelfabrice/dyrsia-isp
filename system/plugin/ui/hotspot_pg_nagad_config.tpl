{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" autocomplete="off" role="form" action="">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">Hotspot Nagad Payment Gateway Settings</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Merchant ID</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="hotspot_pg_nagad_merchant_id" name="hotspot_pg_nagad_merchant_id"
                                value="{$_c['hotspot_pg_nagad_merchant_id']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Merchant Account No</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="hotspot_pg_nagad_merchant_no" name="hotspot_pg_nagad_merchant_no"
                                value="{$_c['hotspot_pg_nagad_merchant_no']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Private Key</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="hotspot_pg_nagad_private_key"
                                name="hotspot_pg_nagad_private_key" value="{$_c['hotspot_pg_nagad_private_key']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Environment')}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="hotspot_pg_nagad_env" id="hotspot_pg_nagad_env">
                              <option value="sandbox" {if $_c['hotspot_pg_nagad_env'] == 'sandbox'}selected{/if}>Sandbox or Testing</option>
                              <option value="live" {if $_c['hotspot_pg_nagad_env'] == 'live'}selected{/if}>Live or Production</option>
                            </select>
                              <small class="form-text text-muted"><font color="red"><b>Sandbox</b></font> is for testing purpose, please switch to <font color="green"><b>Live</b></font> in production.</small>
                          </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">URL Callback</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()"
                                value="{$_url}plugin/hotspot_pg_nagad_verify">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" name="save" value="save"
                                type="submit">{Lang::T('Save')}</button>
                        </div>
                    </div>
                    <pre>/ip hotspot walled-garden
add dst-host=nagad.com
add dst-host=*.nagad.com
add dst-host=*.mynagad.com
</pre>
                    <small id="emailHelp" class="form-text text-muted">Set Telegram Bot to get any error and notification</small>
                </div>
            </div>

        </div>
    </div>
</form>

{include file="sections/footer.tpl"}