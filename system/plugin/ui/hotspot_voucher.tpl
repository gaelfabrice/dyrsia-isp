{include file="sections/header.tpl"}
<style>
    /* Styles for overall layout and responsiveness */
    body {
        background-color: #f8f9fa;
        font-family: 'Arial', sans-serif;
        padding: 0;
        margin: 0;
    }

    .container {
        margin-top: 20px;
        background-color: #d8dfe5;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        max-width: 98%;
        overflow-x: auto;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }

    /* Styles for table and pagination */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: #fff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table th {
        vertical-align: middle;
        border-color: #dee2e6;
        background-color: #343a40;
        color: #fff;
    }

    .table td {
        vertical-align: middle;
        border-color: #dee2e6;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.075);
        color: #333;
        font-weight: bold;
        transition: background-color 0.3s, color 0.3s;
    }

    .pagination .page-item .page-link {
        color: #007bff;
        background-color: #fff;
        border: 1px solid #dee2e6;
        margin: 0 2px;
        padding: 6px 12px;
        transition: background-color 0.3s, color 0.3s;
    }

    .pagination .page-item .page-link:hover {
        background-color: #e9ecef;
        color: #0056b3;
    }

    .pagination .page-item.active .page-link {
        z-index: 1;
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        display: inline-block;
        padding: 5px 10px;
        margin-right: 5px;
        border: 1px solid #ccc;
        background-color: #fff;
        color: #333;
        cursor: pointer;
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

<div class="box box-solid box-default" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px;">
    <div class="row">
<form action="{$_url}plugin/hotspot_voucher_bulk_lock" method="post">
            <div class="col-md-4">
                <label><strong>Global MAC Lock Control:</strong></label>
<select name="lock_status" class="form-control">
    <option value="1" {if $global_mac_lock == 1}selected{/if}>
        Enable Lock for All Vouchers
    </option>
    <option value="0" {if $global_mac_lock == 0}selected{/if}>
        Disable Lock for All Vouchers
    </option>
</select>
            </div>
            <div class="col-md-4" style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary" onclick="return confirm('এটি আপনার সকল পুরনো এবং নতুন ভাউচারের লক স্ট্যাটাস পরিবর্তন করবে। আপনি কি নিশ্চিত?')">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- voucher -->
<div class="row" style="padding: 5px">
    <div class="col-lg-3 col-lg-offset-9">
        <div class="btn-group btn-group-justified" role="group">
            <div class="btn-group" role="group">
                <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#create"><i
                        class="ion ion-android-add"></i>
                    {Lang::T('Generate')}</a>
            </div>
            <div class="btn-group" role="group">
                <a href="{$_url}plugin/hotspot_voucherPrint" target="print_voucher" class="btn btn-info"><i
                        class="ion ion-android-print"></i> Print All</a>
            </div>
        </div>
    </div>
</div>
<div class="panel panel-hovered mb20 panel-primary">
    <div class="panel-heading">
        <!-- {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
        <div class="btn-group pull-right">
            <a class="btn btn-danger btn-xs" title="Remove used Voucher" href="{$_url}plugin/hotspot_voucher"
                onclick="return confirm('Delete all used voucher code?')"><span class="glyphicon glyphicon-trash"
                    aria-hidden="true"></span> Delete All</a>
        </div>
        {/if} -->
        &nbsp;
    </div>


    <div class="container">
        &nbsp;<form class="form-horizontal" method="post" role="form" action="{$_url}plugin/hotspot_voucher">
            <ul class="nav nav-tabs">
                {foreach $routers as $r}
                <li role="presentation" {if $r['name']==$router}class="active" {/if}>
                    <a href="{$_url}plugin/hotspot_voucher/{$r['name']}">{$r['name']}</a>
                </li>
                {/foreach}
            </ul>
        </form>
        &nbsp;&nbsp;
        <table id="datatable" class="table table-bordered table-striped table-condensed">
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
                    <td>{$ds['name_plan']}</td>
                    <td style="background-color: black; color: black;"
                        onmouseleave="this.style.backgroundColor = 'black';"
                        onmouseenter="this.style.backgroundColor = 'white';">
                        {$ds['code']}
                    </td>
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
        <br>
        <div class="row" style="padding: 5px">
            <div class="col-lg-3 col-lg-offset-9">
                <div class="btn-group btn-group-justified" role="group">
                    <div class="btn-group" role="group">
                        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                        <button id="deleteSelectedVouchers" class="btn btn-danger">{Lang::T('Delete
                            Selected')}</button>
                        {/if}
                    </div>
                    <div class="btn-group" role="group">
                        <button id="printSelectedVouchers" class="btn btn-success">{Lang::T('Print
                            Selected')}</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- <button id="printSelectedVouchers" class="btn btn-block btn-primary">Print Selected</button>
            <button id="deleteSelectedVouchers" class="btn btn-block btn-danger">Delete Selected Vouchers</button> -->

    </div>
    &nbsp;
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
        $j('#datatable').DataTable({
            "pagingType": "full_numbers",
            "order": [
                [1, 'desc']
            ]
        });
    });

    document.getElementById('printSelectedVouchers').addEventListener('click', function () {
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