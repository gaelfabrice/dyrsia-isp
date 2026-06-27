{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}plugin/mikrotik_import_start_ui">
    <div class="row">
        <div class="col-sm-12 col-md-12">

            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">Information</div>
                <div class="panel-body">
                    <ol>
                        <li>Imports Packages and Users from Mikrotik to wifizones</li>
                        <li>Select a Plan — imported users will be activated with that plan</li>
                        <li>Use <strong>Dry Run (Preview)</strong> to see what will happen before actually importing</li>
                        <li>Set <strong>Expiry Date</strong> to override plan's default validity</li>
                    </ol>
                </div>
            </div>

            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">Import Users from Mikrotik</div>
                <div class="panel-body">

                    {* ── Type ── *}
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Type')}</label>
                        <div class="col-md-6">
                            <label class="radio-inline">
                                <input type="radio" name="type" value="Hotspot" checked
                                    onclick="showPlans('hotspot')"> Hotspot
                            </label>
                            &nbsp;&nbsp;
                            <label class="radio-inline">
                                <input type="radio" name="type" value="PPPOE"
                                    onclick="showPlans('pppoe')"> PPPOE
                            </label>
                        </div>
                    </div>

                    {* ── Router ── *}
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Routers')}</label>
                        <div class="col-md-6">
                            <select id="server" required name="server" class="form-control">
                                <option value="">{Lang::T('Select Routers')}</option>
                            </select>
                        </div>
                    </div>

                    {* ── Plan Hotspot ── *}
                    <div class="form-group" id="plan_hotspot_group">
                        <label class="col-md-2 control-label">Activate Plan</label>
                        <div class="col-md-6">
                            <select name="plan_id" id="plan_hotspot" class="form-control">
                                <option value="">-- Import Only (No Activation) --</option>
                                {foreach $plans_hotspot as $p}
                                    <option value="{$p['id']}">
                                        {$p['name_plan']} | {$p['validity']} {$p['validity_unit']} | {$p['price']}
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">Active imported users using this plan</p>
                        </div>
                    </div>

                    {* ── Plan PPPOE ── *}
                    <div class="form-group" id="plan_pppoe_group" style="display:none;">
                        <label class="col-md-2 control-label">Activate Plan</label>
                        <div class="col-md-6">
                            <select name="plan_id_pppoe" id="plan_pppoe" class="form-control">
                                <option value="">-- Import Only (No Activation) --</option>
                                {foreach $plans_pppoe as $p}
                                    <option value="{$p['id']}">
                                        {$p['name_plan']} | {$p['validity']} {$p['validity_unit']} | {$p['price']}
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">Active imported users using this plan</p>
                        </div>
                    </div>

                    {* ── Expiry Date ── *}
                    <div class="form-group">
                        <label class="col-md-2 control-label">Expiry Date
                            <small class="text-muted">(optional)</small>
                        </label>
                        <div class="col-md-4">
                            <input type="date" name="expiry_date" class="form-control"
                                min="{date('Y-m-d')}">
                            <p class="help-block">Leave blank to use plan's default validity</p>
                        </div>
                    </div>

                    {* ── Existing User Action ── *}
                    <div class="form-group">
                        <label class="col-md-2 control-label">Existing Active Users</label>
                        <div class="col-md-6">
                            <select name="exist_action" class="form-control">
                                <option value="skip">Skip — keep as is</option>
                                <option value="activate">Activate if not active</option>
                                <option value="reactivate">Re-activate — reset expiry</option>
                            </select>

                        </div>
                    </div>

                    {* ── Phone Number ── *}
                    <div class="form-group">
                        <label class="col-md-2 control-label">Phone Number</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="use_phone" value="1">
                                    Auto-detect phone from Mikrotik
                                </label>
                            </div>
                        </div>
                    </div>

                    {* ── AI Date Detection ── *}
                    <div class="form-group">
                        <label class="col-md-2 control-label">Detect Expire Date</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="ai_date" value="1" checked>
                                    Auto-detect expire date from Mikrotik
                                </label>
                            </div>
                        </div>
                    </div>

                    {* ── Dry Run ── *}
                    <div class="form-group">
                        <label class="col-md-2 control-label">Dry Run (Preview)</label>
                        <div class="col-md-6">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="dry_run" value="1">
                                    <strong>Preview only</strong> — show what will happen
                                </label>
                            </div>
                        </div>
                    </div>

                    {* ── Buttons ── *}
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-info" type="submit" onclick="setDryRun(true)">
                                <i class="fa fa-eye"></i> Preview
                            </button>
                            &nbsp;
                            <button class="btn btn-success" type="submit" onclick="setDryRun(false)">
                                <i class="fa fa-download"></i> Import & Activate
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</form>

<script>
function showPlans(type) {
    var hg = document.getElementById('plan_hotspot_group');
    var pg = document.getElementById('plan_pppoe_group');
    var hs = document.getElementById('plan_hotspot');
    var ps = document.getElementById('plan_pppoe');

    if (type === 'hotspot') {
        hg.style.display = '';
        pg.style.display = 'none';
        hs.name = 'plan_id';
        ps.name = 'plan_id_pppoe';
    } else {
        hg.style.display = 'none';
        pg.style.display = '';
        ps.name = 'plan_id';
        hs.name = 'plan_id_hotspot';
    }
}

function setDryRun(isDryRun) {
    var cb = document.querySelector('input[name="dry_run"]');
    if (cb) cb.checked = isDryRun;
}
</script>

{include file="sections/footer.tpl"}
