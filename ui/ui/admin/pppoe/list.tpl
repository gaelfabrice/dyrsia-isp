{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save" href="{Text::url('')}services/sync/pppoe"
                        onclick="return ask(this, 'Synchroniser profils, clients PPPoE et supprimer les secrets orphelins sur MikroTik ?')"><span
                            class="glyphicon glyphicon-refresh" aria-hidden="true"></span> sync complet</a>
                </div>{Lang::T('PPPOE Package')}
            </div>
            <form id="site-search" method="post" action="{Text::url('')}services/pppoe">
                <div class="panel-body">
                    <div class="row row-no-gutters" style="padding: 5px">
                        <div class="col-lg-2">
                            <div class="input-group">
                                <div class="input-group-btn">
                                    <a class="btn btn-danger" title="Clear Search Query"
                                        href="{Text::url('')}services/pppoe"><span
                                            class="glyphicon glyphicon-remove-circle"></span></a>
                                </div>
                                <input type="text" name="name" class="form-control"
                                    placeholder="{Lang::T('Search by Name')}...">
                            </div>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="type1" name="type1">
                                <option value="">{Lang::T('Prepaid')} &amp; {Lang::T('Postpaid')}</option>
                                <option value="yes" {if $type1 eq 'yes' }selected{/if}>{Lang::T('Prepaid')}</option>
                                <option value="no" {if $type1 eq 'no' }selected{/if}>{Lang::T('Postpaid')}</option>
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="type2" name="type2">
                                <option value="">{Lang::T('Type')}</option>
                                {foreach $type2s as $t}
                                    <option value="{$t}" {if $type2 eq $t }selected{/if}>{Lang::T($t)}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="bandwidth" name="bandwidth">
                                <option value="">Bandwidth</option>
                                {foreach $bws as $b}
                                    <option value="{$b['id']}" {if $bandwidth eq $b['id'] }selected{/if}>
                                        {$b['name_bw']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="type3" name="type3">
                                <option value="">{Lang::T('Category')}</option>
                                {foreach $type3s as $t}
                                    <option value="{$t}" {if $type3 eq $t }selected{/if}>{$t}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="valid" name="valid">
                                <option value="">{Lang::T('Validity')}</option>
                                {foreach $valids as $v}
                                    <option value="{$v}" {if $valid eq $v }selected{/if}>{$v}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="router" name="router">
                                <option value="">{Lang::T('Location')}</option>
                                {foreach $routers as $r}
                                    <option value="{$r}" {if $router eq $r }selected{/if}>{$r}</option>
                                {/foreach}
                                <option value="radius" {if $router eq 'radius' }selected{/if}>Radius</option>
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="device" name="device">
                                <option value="">{Lang::T('Device')}</option>
                                {foreach $devices as $r}
                                    <option value="{$r}" {if $device eq $r }selected{/if}>{$r}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="status" name="status">
                                <option value="-">{Lang::T('Status')}</option>
                                <option value="1" {if $status eq '1' }selected{/if}>{Lang::T('Enabled')}</option>
                                <option value="0" {if $status eq '0' }selected{/if}>{Lang::T('Disable')}</option>
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-8">
                            <button class="btn btn-success btn-block" type="submit"><span
                                    class="fa fa-search"></span></button>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <a href="{Text::url('')}services/pppoe-add" class="btn btn-primary btn-block"
                                title="{Lang::T('New Service Plan')}"><i class="ion ion-android-add"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <div style="display:flex; justify-content:flex-end; margin:10px 5px;">
                <button type="button" class="btn btn-danger btn-sm" onclick="nuxDeleteCheck()">
                    <i class="glyphicon glyphicon-trash"></i>
                    Delete Selected
                </button>
            </div>

            <div class="table-responsive">
                <form id="pppoeBulkForm" method="post" action="{Text::url('')}services/pppoe-bulk-delete">
                    <div style="margin-left: 5px; margin-right: 5px;">
                        <table class="table table-bordered table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th width="30"><input type="checkbox" id="checkAll"></th>
                                    <th colspan="5" class="text-center">{Lang::T('Internet Plan')}</th>
                                    <th></th>
                                    <th colspan="2" class="text-center" style="background-color: rgb(243, 241, 172);">{Lang::T('Expired')}</th>
                                    <th colspan="4"></th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th>{Lang::T('Name')}</th>
                                    <th>{Lang::T('Type')}</th>
                                    <th>{Lang::T('Bandwidth')}</th>
                                    <th>{Lang::T('Price')}</th>
                                    <th>{Lang::T('Validity')}</th>
                                    <th>{Lang::T('IP Pool')}</th>
                                    <th style="background-color: rgb(243, 241, 172);">{Lang::T('Internet Plan')}</th>
                                    <th style="background-color: rgb(243, 241, 172);">{Lang::T('Date')}</th>
                                    <th>{Lang::T('Location')}</th>
                                    <th>{Lang::T('Device')}</th>
                                    <th>{Lang::T('Manage')}</th>
                                    <th>ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $d as $ds}
                                    <tr {if $ds['enabled'] !=1}class="danger" title="disabled" {/if}>
                                        {if $ds['name_plan'] == 'EXPIRE'}
                                        <td></td>
                                        <td>{$ds['name_plan']} <span class="label label-default">{Lang::T('System')}</span></td>
                                        {else}
                                        <td><input type="checkbox" name="ids[]" value="{$ds['id']}" class="checkItem"></td>
                                        <td>{$ds['name_plan']}</td>
                                        {/if}
                                        <td>{$ds['plan_type']} {if $ds['prepaid'] != 'yes'}<b>{Lang::T('Postpaid')}</b>{else}{Lang::T('Prepaid')}{/if}</td>
                                        <td>{$ds['name_bw']}</td>
                                        <td>{Lang::moneyFormat($ds['price'])}{if !empty($ds['price_old'])}<sup style="text-decoration: line-through; color: red">{Lang::moneyFormat($ds['price_old'])}</sup>{/if}</td>
                                        <td>{$ds['validity']} {$ds['validity_unit']}</td>
                                        <td>{$ds['pool']}</td>
                                        <td>{if $ds['plan_expired']}<a href="{Text::url('')}services/pppoe-edit/{$ds['plan_expired']}">{Lang::T('Yes')}</a>{else}{Lang::T('No')}{/if}</td>
                                        <td>{if $ds['prepaid'] == 'no'}{$ds['expired_date']}{/if}</td>
                                        <td>{if $ds['is_radius']}<span class="label label-primary">RADIUS</span>{else}{if $ds['routers']!=''}<a href="{Text::url('routers/edit/0&name=')}{$ds['routers']}">{$ds['routers']}</a>{/if}{/if}</td>
                                        <td>{$ds['device']}</td>
                                        <td>
                                            <a href="{Text::url('')}services/pppoe-edit/{$ds['id']}" class="btn btn-info btn-xs">{Lang::T('Edit')}</a>
                                        </td>
                                        <td>{$ds['id']}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="panel-footer">
                {include file="pagination.tpl"}
                <div class="bs-callout bs-callout-info" id="callout-navbar-role">
                    <h4>{Lang::T('Create expired Internet Plan')}</h4>
                    <p>{Lang::T('The EXPIRE system plan is created automatically for each router and cannot be deleted. Expired clients are switched to this plan.')}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Check All functionality
document.getElementById('checkAll').onclick = function () {
    let items = document.querySelectorAll('.checkItem');
    items.forEach(i => i.checked = this.checked);
};

// wifizones White Style Logic
function nuxDeleteCheck() {
    let checked = document.querySelectorAll('.checkItem:checked');

    if (checked.length === 0) {
        // wifizones Original White Style Error Alert
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Please select at least one item!',
                background: '#ffffff', // ফুল হোয়াইট ব্যাকগ্রাউন্ড
                confirmButtonColor: '#3085d6'
            });
        } else if (typeof bootbox !== "undefined") {
            bootbox.alert({
                message: "Please select at least one item!",
                backdrop: true
            });
        } else {
            alert("Please select at least one item!");
        }
        return false;
    }

    // ডিলিট করার কনফার্মেশন (সিস্টেম স্টাইল)
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete selected plans?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            background: '#ffffff' // হোয়াইট ব্যাকগ্রাউন্ড
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('pppoeBulkForm').submit();
            }
        });
    } else {
        if (confirm("Are you sure you want to delete selected plans?")) {
            document.getElementById('pppoeBulkForm').submit();
        }
    }
}
</script>

{include file="sections/footer.tpl"}