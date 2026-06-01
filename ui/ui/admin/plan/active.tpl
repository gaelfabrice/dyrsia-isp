{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    <div class="btn-group pull-right">
                        <a class="btn btn-primary btn-xs" title="save" href="{Text::url('')}plan/sync"
                            onclick="return ask(this, '{Lang::T("This will sync dan send Customer active package to Mikrotik")}?')"><span
                                class="glyphicon glyphicon-refresh" aria-hidden="true"></span> {Lang::T("Sync")}</a>
                    </div>
                {/if}
                &nbsp;
                {Lang::T('Active Customers')}
            </div>

            <!-- Main Search Form -->
            <form id="site-search" method="post" action="{Text::url('')}plan/list/">
                <!-- Show limit hidden input (Keep current limit on search) -->
                {if $show}
                    <input type="hidden" name="show" value="{$show}">
                {/if}
                <div class="panel-body">
                    <div class="row row-no-gutters" style="padding: 5px">
                        <div class="col-lg-2">
                            <div class="input-group">
                                <div class="input-group-btn">
                                    <a class="btn btn-danger" title="Clear Search Query"
                                        href="{Text::url('')}plan/list"><span
                                            class="glyphicon glyphicon-remove-circle"></span></a>
                                </div>
                                <input type="text" name="search" class="form-control"
                                    placeholder="{Lang::T("Search")}..." value="{$search}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <select class="form-control" id="router" name="router">
                                <option value="">{Lang::T("Location")}</option>
                                {foreach $routers as $r}
                                    <option value="{$r}" {if $router eq $r }selected{/if}>{$r}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <select class="form-control" id="plan" name="plan">
                                <option value="">{Lang::T("Plan Name")}</option>
                                {foreach $plans as $p}
                                    <option value="{$p['id']}" {if $plan eq $p['id'] }selected{/if}>{$p['name_plan']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-2 col-xs-4">
                            <select class="form-control" id="status" name="status">
                                <option value="-">{Lang::T("Status")}</option>
                                <option value="on" {if $status eq 'on' }selected{/if}>{Lang::T("Active")}</option>
                                <option value="off" {if $status eq 'off' }selected{/if}>{Lang::T("Expired")}</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-xs-6">
                            <button class="btn btn-success btn-block" type="submit"><span
                                    class="fa fa-search"></span></button>
                        </div>
                        <div class="col-md-2 col-xs-6">
                            <a href="{Text::url('')}plan/recharge" class="btn btn-primary btn-block"><i
                                    class="ion ion-android-add">
                                </i> {Lang::T("Recharge Account")}</a>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Counts -->
            {assign var="activeCount" value=0}
            {assign var="expiredCount" value=0}
            
            {foreach $d as $ds}
                {if $ds['status'] == 'on'}
                    {assign var="activeCount" value=$activeCount+1}
                {else}
                    {assign var="expiredCount" value=$expiredCount+1}
                {/if}
            {/foreach}
            
            <div class="pull-right" style="margin-top:-3px; margin-right:15px;">
                <span class="label label-warning" style="margin-right:5px; font-size:12px;">
                    Active: {$activeCount}
                </span>
                <span class="label label-danger" style="margin-right:5px; font-size:12px;">
                    Expired: {$expiredCount}
                </span>
                <span class="label label-success" style="font-size:12px;">
                    Total: {$activeCount+$expiredCount}
                </span>
            </div>

            <!-- Show Entries Form (Moved OUTSIDE the bulkDeleteForm) -->
            <div class="row">
                <div class="col-md-12">
                    <div style="margin-left: 15px; margin-bottom: 10px;">
                        <!-- action সরাসরি index.php দিন -->
    <form method="get" action="index.php" class="form-inline">
        
        <!-- আপনার সিস্টেমের জন্য এই লাইনটি সবচেয়ে গুরুত্বপূর্ণ -->
        <input type="hidden" name="_route" value="plan/list">
        
        <div class="input-group input-group-sm" style="width: 180px;">
            <span class="input-group-addon">{Lang::T('Show')}</span>
            <select name="show" class="form-control" onchange="this.form.submit()">
                <option value="25" {if $show == '25'}selected{/if}>25</option>
                <option value="50" {if $show == '50'}selected{/if}>50</option>
                <option value="100" {if $show == '100'}selected{/if}>100</option>
                <option value="250" {if $show == '250'}selected{/if}>250</option>
                <option value="500" {if $show == '500'}selected{/if}>500</option>
                <option value="all" {if $show == 'all'}selected{/if}>{Lang::T('All')}</option>
            </select>
            <span class="input-group-addon">{Lang::T('Entries')}</span>
        </div>

        <!-- আগের ফিল্টারগুলো ধরে রাখার জন্য নিচের হিডেন ইনপুটগুলো দিন -->
        {if $search} <input type="hidden" name="search" value="{$search}"> {/if}
        {if $router} <input type="hidden" name="router" value="{$router}"> {/if}
        {if $plan} <input type="hidden" name="plan" value="{$plan}"> {/if}
        {if $status} <input type="hidden" name="status" value="{$status}"> {/if}
    </form>
                    </div>
                </div>
            </div>

            <!-- Bulk Delete and Table Form -->
            <form method="post" action="{Text::url('')}plan/delete-selected" id="bulkDeleteForm">
                <div class="row" style="margin-bottom:10px;">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-danger btn-sm" style="margin-left: 15px;"
                            onclick="return confirm('Are you sure want to delete selected items?')">
                            <i class="glyphicon glyphicon-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <div style="margin-left: 5px; margin-right: 5px;">&nbsp;
                        <table id="datatable" class="table table-bordered table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll"></th>
                                    <th>{Lang::T("Username")}</th>
                                    <th>{Lang::T("Full Name")}</th>
                                    <th>{Lang::T("Address")}</th>
                                    <th>{Lang::T("Phone Number")}</th>
                                    <th>{Lang::T("Plan Name")}</th>
                                    <th>{Lang::T("Type")}</th>
                                    <th>{Lang::T("Created On")}</th>
                                    <th>{Lang::T("Expires On")}</th>
                                    <th>{Lang::T("Method")}</th>
                                    <th><a href="{Text::url('')}routers/list">{Lang::T("Location")}</a></th>
                                    <th>{Lang::T("Manage")}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $d as $ds}
                                    <tr {if $ds['status']=='off' }class="danger" {/if}>
                                        <td>
                                            <input type="checkbox" name="ids[]" value="{$ds['id']}" class="checkItem">
                                        </td>
                                        <td>
    {if $ds['customer_id'] == '0'}
        <a href="{Text::url('plan/voucher/&search=')}{$ds['username']}">
            {$ds['username']}
        </a>
    {else}
        <a href="{Text::url('customers/view/')}{$ds['customer_id']}">
            {$ds['username']}
        </a>
    {/if}
</td>
                                        <td>{$ds['fullname']}</td> 
                                        <td>{$ds['address']}</td>
                                        <td>{$ds['phonenumber']}</td>
                                        <td>
                                            {if $ds['type'] == 'Hotspot'}
                                                <a href="{Text::url('')}services/edit/{$ds['plan_id']}">{$ds['namebp']}</a>
                                                <span api-get-text="{Text::url('')}autoload/customer_is_active/{$ds['username']}/{$ds['plan_id']}"></span>
                                            {elseif $ds['type'] == 'PPPOE'}
                                                <a href="{Text::url('')}services/pppoe-edit/{$ds['plan_id']}">{$ds['namebp']}</a>
                                                <span api-get-text="{Text::url('')}autoload/customer_is_active/{$ds['username']}/{$ds['plan_id']}"></span>
                                            {elseif $ds['type'] == 'VPN'}
                                                <a href="{Text::url('')}services/vpn-edit/{$ds['plan_id']}">{$ds['namebp']}</a>
                                            {/if}
                                        </td>
                                        <td>{$ds['type']}</td>
                                        <td>{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                                        <td>{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                                        <td>{$ds['method']}</td>
                                        <td>{$ds['routers']}</td>
                                        <td>
                                            <a href="{Text::url('')}plan/edit/{$ds['id']}" class="btn btn-warning btn-xs"
                                                style="color: black;">{Lang::T("Edit")}</a>
                                            
                                            {if $ds['status']=='off' && $_c['extend_expired']}
                                                <a href="javascript:extend('{$ds['id']}')"
                                                    class="btn btn-info btn-xs">{Lang::T("Extend")}</a>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
                {include file="pagination.tpl"}
            </form>
        </div>
    </div>
</div>

<script>
    function extend(idP) {
        var res = prompt("Extend for many days?", "3");
        if (res) {
            if (confirm("Extend for " + res + " days?")) {
                window.location.href = "{Text::url('plan/extend/')}" + idP + "/" + res + "{Text::isQA('? or &')}stoken={App::getToken()}";
            }
        }
    }
    
    document.getElementById('checkAll').onclick = function () {
        var checkboxes = document.querySelectorAll('.checkItem');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    };
</script>

{include file="sections/footer.tpl"}