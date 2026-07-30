{include file="sections/header.tpl"}
<style>
    body.wz-command .content.wz-admin-content:has(.hv-page) {
        padding-top: 18px !important;
    }
    .hv-page {
        max-width: 100%;
        padding-bottom: 24px;
    }
    .hv-hero {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
    }
    .hv-hero h1 {
        margin: 0 0 6px;
        font-size: 26px;
        font-weight: 800;
        color: var(--wz-p-heading, #fff);
        letter-spacing: -0.02em;
    }
    .hv-hero p {
        margin: 0;
        color: var(--wz-p-muted, #94a3b8);
        font-size: 14px;
        max-width: 520px;
    }
    .hv-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .hv-card {
        background: var(--wz-p-card, rgba(15, 23, 42, 0.92));
        border: 1px solid var(--wz-p-line, rgba(148, 163, 184, 0.18));
        border-radius: var(--wz-p-radius, 16px);
        box-shadow: var(--wz-p-shadow, 0 12px 40px rgba(0, 0, 0, 0.22));
        padding: 18px;
        margin-bottom: 18px;
        color: var(--wz-p-text, #f8fafc);
    }
    .hv-card-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--wz-p-muted, #94a3b8);
        margin: 0 0 12px;
    }
    .hv-mac-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }
    .hv-mac-row .form-group { margin-bottom: 0; flex: 1; min-width: 200px; }
    .hv-router-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
        list-style: none;
        padding: 0;
    }
    .hv-router-tabs a {
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 13px;
        border: 1px solid var(--wz-p-line, rgba(148, 163, 184, 0.25));
        color: var(--wz-p-text, #e2e8f0);
        background: rgba(255, 255, 255, 0.04);
        text-decoration: none;
        transition: border-color 0.2s, background 0.2s;
    }
    .hv-router-tabs li.active a,
    .hv-router-tabs a:hover {
        border-color: var(--wz-p-brand, #2563eb);
        background: rgba(37, 99, 235, 0.15);
        color: var(--wz-p-heading, #fff);
        text-decoration: none;
    }
    .hv-page .table {
        width: 100%;
        margin-bottom: 0;
        background: transparent !important;
        color: var(--wz-p-text, #f8fafc);
    }
    .hv-page .table > thead > tr > th {
        background: rgba(15, 23, 42, 0.85) !important;
        color: var(--wz-p-heading, #fff) !important;
        border-color: var(--wz-p-line, rgba(148, 163, 184, 0.2)) !important;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
        vertical-align: middle;
    }
    .hv-page .table > tbody > tr > td {
        border-color: var(--wz-p-line, rgba(148, 163, 184, 0.15)) !important;
        vertical-align: middle;
        color: var(--wz-p-text, #e2e8f0);
    }
    .hv-page .table-striped > tbody > tr:nth-of-type(odd) {
        background: rgba(255, 255, 255, 0.03);
    }
    .hv-page .table-hover > tbody > tr:hover {
        background: rgba(37, 99, 235, 0.08) !important;
    }
    .hv-code {
        display: inline-block;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.08em;
        padding: 6px 10px;
        border-radius: 8px;
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.35);
        color: #6ee7b7;
    }
    body.wz-command.theme-light .hv-code {
        color: #047857;
        background: #ecfdf5;
        border-color: #a7f3d0;
    }
    .hv-table-wrap {
        overflow-x: auto;
        border-radius: 12px;
        border: 1px solid var(--wz-p-line, rgba(148, 163, 184, 0.15));
    }
    .hv-footer-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 16px;
    }
    .hv-empty-routers {
        text-align: center;
        padding: 32px 16px;
        color: var(--wz-p-muted, #94a3b8);
    }
    .hv-page .dataTables_wrapper .dataTables_length,
    .hv-page .dataTables_wrapper .dataTables_filter,
    .hv-page .dataTables_wrapper .dataTables_info {
        color: var(--wz-p-muted, #94a3b8) !important;
    }
    .hv-page .dataTables_wrapper .dataTables_filter input,
    .hv-page .dataTables_wrapper .dataTables_length select {
        background: rgba(15, 23, 42, 0.6) !important;
        border: 1px solid var(--wz-p-line, rgba(148, 163, 184, 0.25)) !important;
        color: var(--wz-p-text, #f8fafc) !important;
        border-radius: 8px;
        padding: 6px 10px;
    }
    body.wz-command.theme-light .hv-page .dataTables_wrapper .dataTables_filter input,
    body.wz-command.theme-light .hv-page .dataTables_wrapper .dataTables_length select {
        background: #fff !important;
        color: #1e293b !important;
    }
</style>
{if isset($message)}
<div class="alert alert-{if $notify_t == 's'}success{else}danger{/if}">
    <button type="button" class="close" data-dismiss="alert">
        <span aria-hidden="true">×</span>
    </button>
    <div>{$message}</div>
</div>
{/if}

<div class="wz-page hv-page">
    <div class="hv-hero">
        <div>
            <h1><i class="ion ion-card"></i> {Lang::T('Hotspot Voucher Code Generator')}</h1>
            <p>{Lang::T('Generate, print and manage hotspot vouchers for your routers.')}</p>
        </div>
        <div class="hv-actions">
            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#create">
                <i class="ion ion-android-add"></i> {Lang::T('Generate')}
            </a>
            <a href="{$_url}plugin/hotspot_voucherPrint" target="print_voucher" class="btn btn-default">
                <i class="ion ion-android-print"></i> {Lang::T('Print All')}
            </a>
        </div>
    </div>

    <div class="hv-card">
        <p class="hv-card-title"><i class="fa fa-lock"></i> {Lang::T('Global MAC Lock Control')}</p>
        <form action="{$_url}plugin/hotspot_voucher_bulk_lock" method="post" class="hv-mac-row">
            <div class="form-group">
                <select name="lock_status" class="form-control">
                    <option value="1" {if $global_mac_lock == 1}selected{/if}>{Lang::T('Enable Lock for All Vouchers')}</option>
                    <option value="0" {if $global_mac_lock == 0}selected{/if}>{Lang::T('Disable Lock for All Vouchers')}</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" onclick="return confirm('{Lang::T('This will change MAC lock status for all vouchers on this router. Continue?')|escape:'javascript'}')">
                {Lang::T('Save')}
            </button>
        </form>
    </div>

    <div class="hv-card">
        {if $routers|@count > 0}
        <ul class="hv-router-tabs">
            {foreach $routers as $r}
            <li role="presentation" {if $r['name']==$router}class="active"{/if}>
                <a href="{$_url}plugin/hotspot_voucher/{$r['name']|escape:'url'}"><i class="fa fa-server"></i> {$r['name']|escape}</a>
            </li>
            {/foreach}
        </ul>
        <div class="hv-table-wrap">
        <table id="datatable" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>ID</th>
                    <th>{Lang::T('Routers')}</th>
                    <th>{Lang::T('Plan Name')}</th>
                    <th>{Lang::T('Voucher Code')}</th>
                    <th>{Lang::T('Price')}</th>
                    <th>{Lang::T('Status')}</th>
                    <th>{Lang::T('Generated By')}</th>
                    <th>{Lang::T('MAC Address')}</th>
                    <th>{Lang::T('MAC Lock')}</th>
                    <th>{Lang::T('Manage')}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $d as $ds}
                <tr>
                    <td><input type="checkbox" name="voucher_ids[]" value="{$ds['id']}"></td>
                    <td>{$ds['id']}</td>
                    <td>{$ds['server']}</td>
                    <td>{$ds['name_plan']|escape}</td>
                    <td><span class="hv-code">{$ds['code']|escape}</span></td>
                    <td>{$ds['price']}</td>
                    <td>
                        {if $ds.is_used}
                        <span class="label label-success">{Lang::T('Used')}</span>
                        {else}
                        <span class="label label-danger">{Lang::T('Not Used')}</span>
                        {/if}
                    </td>
                    <td>{if $ds['generated_by'] && $ds['is_admin']}
                        <a href="{$_url}settings/users-view/{$ds['admin_id']}">{$ds['generated_by']}</a>
                        {else}
                        <a href="{$_url}plugin/hotspot_resellerAdmin/view/{$ds['admin_id']}">{$ds['generated_by']}</a>
                        {/if}
                    </td>
                    <td>
    {if isset($ds.mac_address) && $ds.mac_address != ''}
        <code style="font-size:11px;">{$ds.mac_address}</code>
    {else}
        <span class="text-muted">No MAC</span>
    {/if}
</td>

<td>
    <div style="display:flex; align-items:center; gap:8px;">

        {if isset($ds.mac_lock) && $ds.mac_lock == 1}
            <span class="label label-warning" style="font-size:10px;">
                <i class="fa fa-lock"></i> LOCKED
            </span>

            <form method="post" action="{$_url}plugin/hotspot_voucher_reset_mac&id={$ds['id']}" style="display:inline;">
                <input type="hidden" name="id" value="{$ds.id}">
                <button class="btn btn-danger btn-xs"
                    onclick="return confirm('Reset MAC lock?')">
                    <i class="glyphicon glyphicon-refresh"></i>
                </button>
            </form>

        {else}
            <span class="label label-default" style="font-size:10px;">
                <i class="fa fa-unlock"></i> UNLOCKED
            </span>
        {/if}

    </div>
</td>
                    <td>
                        {if $ds['status'] neq '1'}
                        <a href="{$_url}plugin/hotspot_voucher_print&voucher_id={$ds['id']}" id="{$ds['id']}"
                            style="margin: 0px;"
                            class="btn btn-success btn-xs">&nbsp;&nbsp;{Lang::T('Print')}&nbsp;&nbsp;</a>
                        {/if}
                        <button type="button" class="btn btn-primary btn-xs send-voucher" data-id="{$ds['id']}"
                            data-toggle="modal" data-target="#sendVoucherModal">
                            <i class="glyphicon glyphicon-send"></i> {Lang::T('Send')}
                        </button>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        </div>
        <div class="hv-footer-actions">
            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
            <button id="deleteSelectedVouchers" class="btn btn-danger"><i class="fa fa-trash"></i> {Lang::T('Delete Selected')}</button>
            {/if}
            <button id="printSelectedVouchers" class="btn btn-success"><i class="fa fa-print"></i> {Lang::T('Print Selected')}</button>
        </div>
        {else}
        <div class="hv-empty-routers">
            <p><i class="fa fa-server fa-2x"></i></p>
            <p>{Lang::T('No routers found. Add a router in Network first.')}</p>
            <a href="{Text::url('routers/add')}" class="btn btn-primary btn-sm">{Lang::T('Add Router')}</a>
        </div>
        {/if}
    </div>
</div>
<form id="printVouchersForm" method="POST" action="{$_url}plugin/hotspot_voucher_print">
    <input type="hidden" name="voucherIds" id="voucherIdsInput">
</form>

<div class="modal fade" id="create">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"> {Lang::T('Generate Voucher')}</h4>
            </div>
            <div class="box-body">
                <div class="tab-pane">
                    <form action="{$_url}plugin/hotspot_GenerateVoucher" method="post" enctype="multipart/form-data"
                        class="form-horizontal">
                        <input type="hidden" name="csrf_token" id="" value="{$csrf_token}">
                        <input type="hidden" name="is_admin" id="" value="1">
                        <input type="hidden" name="generate_by" id="" value="{$_admin['id']}">
                        <div class="form-group">
                            <label for="inputEmail" class="col-sm-2 control-label">{Lang::T('Routers')}</label>

                            <div class="col-sm-10">
                                <select style="width: 100%" id="server" name="server" class="form-control select2">
                                    {foreach $routers as $ds}
                                    <option value="{$ds['name']}">{$ds['name']}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputName" class="col-sm-2 control-label"> {Lang::T('Service Plan')}</label>

                            <div class="col-sm-10">
                                <select style="width: 100%" id="plan" name="plan" class="form-control select2">
                                    <option value=''>{Lang::T('Select Plans')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputExperience" class="col-sm-2 control-label">{Lang::T('No of
                                Vouchers')}</label>

                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="numbervoucher" value="1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputSkills" class="col-sm-2 control-label"> {Lang::T('Voucher Format')}</label>

                            <div class="col-sm-10">
                                <select name="voucher_format" id="voucher_format" class="form-control">
                                    <option value="numbers">Numbers</option>
                                    <option value="up">UPPERCASE</option>
                                    <option value="low">lowercase</option>
                                    <option value="rand">RaNdoM</option>
                                </select>
                                <p class="help-block"><small>{Lang::T('UPPERCASE, lowercase, RaNdoM, Number
                                        0-9')}</small></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputSkills" class="col-sm-2 control-label">{Lang::T('Voucher Prefix')}</label>

                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="prefix" placeholder="HOTSPOT-"
                                    value="{$_c['voucher_prefix']}">
                                <p class="help-block"><small>HOTSPOT-VoUCHeRCOdE</small></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputSkills" class="col-sm-2 control-label">{Lang::T('Code Length')}</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="lengthcode" value="6">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputSkills" class="col-sm-2 control-label">{Lang::T('Batches')}</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control" name="batch" placeholder="Number of Batches"
                                    value="1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputSkills" class="col-sm-2 control-label">{Lang::T('Print Now')}</label>

                            <div class="col-sm-10">
                                <input type="checkbox" id="print_now" name="print_now" class="iCheck" value="1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="activate" class="col-sm-2 control-label">{Lang::T('Activate Now')}</label>

                            <div class="col-sm-10">
                                <input type="checkbox" id="activate" name="activate" class="iCheck" value="1">
                            </div>
                        </div>
                        <!-- Phone Number field initially hidden -->
                        <div class="form-group" id="phoneField" style="display: none;">
                            <label for="inputSkills" class="col-sm-2 control-label">{Lang::T('Phone Number')}</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="phone" name="phone"
                                    placeholder="Enter customer phone number">
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="pull-right">
                                <button type="submit" value="Generate Vouchers" class="btn btn-primary">
                                    <i class="fa fa-telegram"></i> {Lang::T('Generate')}
                                </button>
                            </div>
                            <button type="button" data-dismiss="modal" class="btn btn-danger">
                                <i class=""></i> {Lang::T('Cancel')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Voucher Modal HTML -->

<div class="modal fade" id="sendVoucherModal" tabindex="-1" role="dialog" aria-labelledby="sendVoucherLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="sendVoucherLabel"> {Lang::T('Send Voucher Code')}</h4>
            </div>
            <div class="box-body">
                <div class="tab-pane">
                    <form action="{$_url}plugin/hotspot_voucher_sendVoucher" method="post" enctype="multipart/form-data"
                        class="form-horizontal">
                        <input type="hidden" id="voucherId" name="voucherId">
                        <div class="form-group">
                            <label for="phone_number" class="col-sm-2 control-label">{Lang::T('Phone No')}</label>
                            <div class="col-sm-10">
                                <input type="text" id="phone_number" name="phoneNumber"
                                    placeholder="{Lang::T('Enter the receiver phone number')}" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="method" class="col-sm-2 control-label">{Lang::T('Send Via')}</label>
                            <div class="col-sm-10">
                                <select name="method" id="method" class="form-control">
                                    <option value="sms">{Lang::T('SMS')}</option>
                                    <option value="wa">{Lang::T('WhatsApp')}</option>
                                    <option value="both">{Lang::T('Both')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="pull-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-telegram"></i> {Lang::T('Send Now')}
                                </button>
                            </div>
                            <button type="button" data-dismiss="modal" class="btn btn-danger">
                                <i class=""></i> {Lang::T('Cancel')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script>
    // Select or deselect all checkboxes
    document.getElementById('select-all').addEventListener('change', function () {
        var checkboxes = document.querySelectorAll('input[name="voucher_ids[]"]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    // Common function to handle the delete request
    function deleteVouchers(voucherIds) {
        if (voucherIds.length > 0) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '{$_url}plugin/hotspot_voucher_delete', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            var response = JSON.parse(xhr.responseText);

                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload(); // Reload the page after confirmation
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to delete vouchers. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    };
                    xhr.send('voucherIds=' + JSON.stringify(voucherIds));
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'No vouchers selected to delete.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    }

    // Handle bulk delete selected vouchers
    document.getElementById('deleteSelectedVouchers').addEventListener('click', function () {
        var selectedVouchers = [];
        document.querySelectorAll('input[name="voucher_ids[]"]:checked').forEach(function (checkbox) {
            selectedVouchers.push(checkbox.value);
        });

        deleteVouchers(selectedVouchers);
    });

    // Handle single voucher deletion
    document.querySelectorAll('.delete-voucher').forEach(function (button) {
        button.addEventListener('click', function () {
            var voucherId = this.getAttribute('data-id');
            deleteVouchers([voucherId]); 
        });
    });

    $(document).ready(function () {
        $("#server").change(function () {
            var server = $(this).val();
            $.ajax({
                type: "POST",
                dataType: "html",
                url: "index.php?_route=autoload/plan",
                data: { jenis: "Hotspot", server: server },
                success: function (response) {
                    $("#plan").html(response);
                },
                error: function () {
                    alert("Failed to load packages. Please try again.");
                }
            });
        });

        $("#server").trigger('change');
    });

    const $j = jQuery.noConflict();

    $j(document).ready(function () {
        if ($j('#datatable').length) {
            $j('#datatable').DataTable({
                pagingType: 'full_numbers',
                order: [[1, 'desc']],
                language: {
                    emptyTable: '{Lang::T('No Data')|escape:'javascript'}',
                    search: '{Lang::T('Search')|escape:'javascript'}:',
                    lengthMenu: '{Lang::T('Show')} _MENU_'
                }
            });
        }
    });

    var printSelectedBtn = document.getElementById('printSelectedVouchers');
    if (printSelectedBtn) {
    printSelectedBtn.addEventListener('click', function () {
        var selectedVouchers = [];
        document.querySelectorAll('input[name="voucher_ids[]"]:checked').forEach(function (checkbox) {
            selectedVouchers.push(checkbox.value);
        });

        if (selectedVouchers.length > 0) {
            document.getElementById('voucherIdsInput').value = JSON.stringify(selectedVouchers);
            document.getElementById('printVouchersForm').submit(); // This triggers the backend to handle the print
        } else {
            alert('Please select at least one voucher to print.');
        }
    });
    }

    var $ = jQuery.noConflict();
    $(document).ready(function () {
        $('.send-voucher').on('click', function () {
            var voucherId = $(this).data('id');
            console.log('Voucher ID:', voucherId);
            $('#sendVoucherModal').find('#voucherId').val(voucherId);
        });
    });

    $(document).ready(function () {
        $('#activate').change(function () {
            if ($(this).is(':checked')) {
                $('#phoneField').show();
                $('#phone').prop('required', true);
            } else {
                $('#phoneField').hide();
                $('#phone').prop('required', false);
            }
        });
    });
    function saveMacLock(id) {
    var macValue = $('#mac_input_' + id).val();
    
    $.ajax({
        type: "POST",
        // এখানে রুট হবে plugin/hotspot/mac_lock
        url: "index.php?_route=plugin/hotspot/mac_lock",
        data: { id: id, mac_address: macValue },
        success: function (response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            } catch (e) {
                alert("Success! MAC Saved.");
                location.reload();
            }
        },
        error: function () {
            alert("Error: ফাংশনটি পাওয়া যাচ্ছে না। আপনার ফাইলের নাম এবং ফাংশনের নাম চেক করুন।");
        }
    });
}
</script>

{include file="sections/footer.tpl"}