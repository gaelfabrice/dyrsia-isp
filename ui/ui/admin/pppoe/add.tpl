{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Add Service Plan')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}services/pppoe-add-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Status')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("Customer cannot buy disabled Package, but admin can recharge it, use it if you want only admin recharge it")}">?</a>
                        </label>
                        <div class="col-md-10">
                            <input type="radio" checked name="enabled" value="1"> {Lang::T('Enable')}
                            <input type="radio" name="enabled" value="0"> {Lang::T('Disable')}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Type')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("Postpaid will have fix expired date")}">?</a>
                        </label>
                        <div class="col-md-10">
                            <input type="radio" name="prepaid" onclick="prePaid()" value="yes" checked> {Lang::T('Prepaid')}
                            <input type="radio" name="prepaid" onclick="postPaid()" value="no"> {Lang::T('Postpaid')}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Type')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("Personal Package will only show to personal Customer, Business package will only show to Business Customer")}">?</a>
                        </label>
                        <div class="col-md-10">
                            <input type="radio" name="plan_type" value="Personal" checked> {Lang::T('Personal')}
                            <input type="radio" name="plan_type" value="Business"> {Lang::T('Business')}
                        </div>
                    </div>
                    {if $_c['radius_enable']}
                        <div class="form-group">
                            <label class="col-md-2 control-label">Radius
                                <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                    data-trigger="focus" data-container="body"
                                    data-content="{Lang::T("If you enable Radius, choose device to radius, except if you have custom device.")}">?</a>
                            </label>
                            <div class="col-md-6">
                                <input type="checkbox" name="radius" onclick="isRadius(this)" value="1"> Radius Plan
                            </div>
                            <p class="help-block col-md-4">{Lang::T('Cannot be change after saved')}</p>
                        </div>
                    {/if}
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Device')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("This Device are the logic how DYRSIA Communicate with Mikrotik or other Devices")}">?</a>
                        </label>
                        <div class="col-md-6">
                            <select class="form-control" id="device" name="device">
                                {foreach $devices as $dev}
                                    <option value="{$dev}">{$dev}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Name')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="name_plan" maxlength="40" name="name_plan">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{Text::url('')}bandwidth/add">{Lang::T('Bandwidth Name')}</a></label>
                        <div class="col-md-6">
                            <select id="id_bw" name="id_bw" class="form-control select2">
                                <option value="">{Lang::T('Select Bandwidth')}...</option>
                                {foreach $d as $ds}
                                    <option value="{$ds['id']}">{$ds['name_bw']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Price')}</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-addon">{$_c['currency_code']}</span>
                                <input type="number" class="form-control" name="price" required>
                            </div>
                        </div>
                        {if $_c['enable_tax'] == 'yes'}
                            {if $_c['tax_rate'] == 'custom'}
                                <p class="help-block col-md-4">{number_format($_c['custom_tax_rate'], 2)} % {Lang::T('Tax Rates
                            will be added')}</p>
                            {else}
                                <p class="help-block col-md-4">{number_format($_c['tax_rate'] * 100, 2)} % {Lang::T('Tax Rates
                            will be added')}</p>
                            {/if}
                        {/if}
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Plan Validity')}</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="validity" name="validity">
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="validity_unit" name="validity_unit">
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('1 Period = 1 Month, Expires the 20th of each month')}
                        </p>
                    </div>
                    <div class="form-group hidden" id="expired_date">
                        <label class="col-md-2 control-label">{Lang::T('Expired Date')}
                            <a tabindex="0" class="btn btn-link btn-xs" role="button" data-toggle="popover"
                                data-trigger="focus" data-container="body"
                                data-content="{Lang::T("Expired will be this date every month")}">?</a>
                        </label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" name="expired_date" maxlength="2" value="20" min="1" max="28" step="1" >
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a
                                href="{Text::url('')}routers/add">{Lang::T('Router Name')}</a></label>
                        <div class="col-md-6">
                            <select id="routers" name="routers" required class="form-control select2">
                                <option value=''>{Lang::T('Select Routers')}</option>
                                {foreach $r as $rs}
                                    <option value="{$rs['name']}">{$rs['name']}</option>
                                {/foreach}
                            </select>
                            <p class="help-block">{Lang::T('Cannot be change after saved')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><a href="{Text::url('')}pool/add">{Lang::T('IP Pool')}</a></label>
                        <div class="col-md-6">
                            <div class="pppoe-pool-mode">
                                <label class="pppoe-pool-mode-opt">
                                    <input type="radio" name="pool_mode" value="existing" checked>
                                    {Lang::T('Existing IP Pool')}
                                </label>
                                <label class="pppoe-pool-mode-opt">
                                    <input type="radio" name="pool_mode" value="new">
                                    {Lang::T('New IP Pool')}
                                </label>
                            </div>

                            <div id="pool_section_existing" class="pppoe-pool-panel">
                                <div class="pppoe-pool-row">
                                    <select id="pool_picker" name="pool_existing" class="form-control pppoe-pool-picker" aria-label="Pools du routeur">
                                        <option value="">— {Lang::T('Select Pool')} —</option>
                                    </select>
                                    <button type="button" id="pool_sync_btn" class="btn btn-default pppoe-pool-sync" title="{Lang::T('Sync')}">
                                        <i class="fa fa-refresh"></i>
                                    </button>
                                </div>
                                <p class="help-block">{Lang::T('Select an existing pool on the router — no new pool will be created on MikroTik')}.</p>
                            </div>

                            <div id="pool_section_new" class="pppoe-pool-panel" style="display:none;">
                                <div class="form-group" style="margin-bottom:8px;">
                                    <label class="control-label">{Lang::T('Pool Name')}</label>
                                    <input type="text" id="pool_name_new" name="pool_name_new" class="form-control" placeholder="pppoe-clients" autocomplete="off">
                                </div>
                                <div class="form-group" style="margin-bottom:8px;">
                                    <label class="control-label">{Lang::T('Range IP')}</label>
                                    <input type="text" name="pool_range" id="pool_range" class="form-control" placeholder="10.10.10.2-10.10.10.254">
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="control-label">{Lang::T('Local IP')}</label>
                                    <input type="text" name="pool_local_ip" id="pool_local_ip" class="form-control" placeholder="10.10.10.1">
                                </div>
                                <p class="help-block">{Lang::T('A new pool will be created on the MikroTik router when saving the plan')}.</p>
                            </div>

                            <p id="pool_sync_status" class="help-block text-muted" style="margin-top:8px;"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-2 col-md-10">
                            <button class="btn btn-primary" onclick="return ask(this, '{Lang::T("Continue the process of adding the PPPoE Package?")}')" type="submit">{Lang::T('Save Changes')}</button>
                            Or <a href="{Text::url('')}services/pppoe">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<style>
{literal}
.pppoe-pool-mode { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 12px; }
.pppoe-pool-mode-opt { font-weight: 600; margin: 0; cursor: pointer; }
.pppoe-pool-mode-opt input { margin-right: 6px; }
.pppoe-pool-panel { padding: 12px; border-radius: 8px; background: rgba(0,0,0,.03); border: 1px solid rgba(0,0,0,.08); }
.pppoe-pool-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: stretch; }
.pppoe-pool-picker { flex: 1 1 200px; min-width: 0; }
.pppoe-pool-sync { flex: 0 0 auto; min-width: 42px; }
@media (max-width: 768px) {
    .pppoe-pool-picker { flex: 1 1 100%; }
}
{/literal}
</style>
<script>
    var preOpt = `<option value="Mins">{Lang::T('Mins')}</option>
    <option value="Hrs">{Lang::T('Hrs')}</option>
    <option value="Days">{Lang::T('Days')}</option>
    <option value="Months">{Lang::T('Months')}</option>`;
    var postOpt = `<option value="Period">{Lang::T('Period')}
    <option value="Months">{Lang::T('Months')}</option>`;
    function prePaid() {
        $("#validity_unit").html(preOpt);
        $('#expired_date').addClass('hidden');
    }

    function postPaid() {
        $("#validity_unit").html(postOpt);
        $("#expired_date").removeClass('hidden');
    }
    document.addEventListener("DOMContentLoaded", function(event) {
        prePaid();
    })
</script>
{if $_c['radius_enable']}
    {literal}
        <script>
            function isRadius(cek) {
                if (cek.checked) {
                    document.getElementById("routers").required = false;
                    document.getElementById("routers").disabled = true;
                    if (window.pppoePlanPoolLoadRadius) {
                        window.pppoePlanPoolLoadRadius();
                    }
                } else {
                    document.getElementById("routers").required = true;
                    document.getElementById("routers").disabled = false;
                    if (window.pppoePlanPoolLoadRouter) {
                        window.pppoePlanPoolLoadRouter(document.getElementById("routers").value);
                    }
                }
            }
        </script>
    {/literal}
{/if}
{include file="sections/footer.tpl"}
<script>
window.PPPOE_POOL_FETCH_URL = '{$pppoe_pool_fetch_url|escape:'javascript'}';
window.PPPOE_POOL_RADIUS_URL = '{Text::url('autoload/pool')|escape:'javascript'}';
</script>
<script src="{$app_url}/ui/ui/scripts/pppoe-plan-pool.js?2026.08.03a"></script>
