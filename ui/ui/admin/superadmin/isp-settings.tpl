{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-6">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('ISP Subscription Prices')}</div>
            <div class="panel-body">
                <form method="post" action="{Text::url('superadmin/isp-settings-post')}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="form-group">
                        <label>Business Price</label>
                        <input type="number" step="0.01" min="0" name="business_price" class="form-control" value="{$isp_settings.business_price|default:0}" required>
                    </div>
                    <div class="form-group">
                        <label>PRO Price Per Router</label>
                        <input type="number" step="0.01" min="0" name="pro_price_per_router" class="form-control" value="{$isp_settings.pro_price_per_router|default:0}" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {Lang::T('Save')}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Modification History')}</div>
            <div class="panel-body no-padding">
                <table class="table table-striped table-condensed">
                    <thead><tr><th>Key</th><th>Value</th><th>Updated By</th><th>Updated At</th></tr></thead>
                    <tbody>
                    {foreach $settings_rows as $row}
                        <tr><td>{$row.setting_key}</td><td>{$row.setting_value}</td><td>{$row.updated_by}</td><td>{$row.updated_at}</td></tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
