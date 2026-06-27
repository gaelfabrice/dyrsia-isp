{include file="sections/header.tpl"}

<style>
    /* Checkbox container */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    /* Hidden checkbox */
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* Slider */
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #2196F3;
    }

    input:focus+.slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
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
<!-- <section class="content-header">
    <button class="btn btn-default"><a href="{$_url}plugin/hotspot_update&db">Update Database</a></button></li>
</section> -->
<section class="content-header">
    <form class="form-horizontal" method="post" autocomplete="off" role="form" action="">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="panel panel-primary panel-hovered panel-stacked mb30">
                    <div class="panel-heading">{Lang::T('Hotspot System General Settings')}
                        <div class="btn-group pull-right">
                            <a class="btn btn-danger btn-xs" title="Update Database"
                                href="{$_url}plugin/hotspot_update&db"><span class="glyphicon glyphicon-upload"
                                    aria-hidden="true"></span> {Lang::T('Update Database')}</a>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Enable Voucher')}</label>
                            <div class="col-md-6">
                                <label class="switch">
                                    <input type="checkbox" id="hotspot_voucher_mode" value="1"
                                        name="hotspot_voucher_mode" {if $_c['hotspot_voucher_mode']==1}checked{/if}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Use voucher system: it will replace login
                                    method to voucher code instead of phone number')}</small></p>
                        </div>
                        <div class="form-group" id="voucher-type-group">
                            <label class="col-md-2 control-label">{Lang::T('Voucher Type')}</label>
                            <div class="col-md-6">
                                <select class="form-control" name="hotspot_voucher_type" id="hotspot_voucher_type">
                                    <option value="random" {if $_c['hotspot_voucher_type']=='random' }selected{/if}>
                                        {Lang::T('ABC123')}</option>
                                    <option value="number" {if $_c['hotspot_voucher_type']=='number' }selected{/if}>
                                        {Lang::T('Number 0-9')}</option>
                                </select>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Choose voucher random code type')}</small>
                            </p>
                        </div>
                        <div class="form-group" id="">
                            <label class="col-md-2 control-label">{Lang::T('Payment Type')}</label>
                            <div class="col-md-6">
                                <select class="form-control" name="hotspot_payment_type" id="hotspot_payment_type">
                                    <option value="payment_gateways" {if $_c['hotspot_payment_type']=='random'
                                        }selected{/if}>
                                        {Lang::T('Payment Gateways')}</option>
                                    <option value="payment_token" {if $_c['hotspot_payment_type']=='payment_token'
                                        }selected{/if}>
                                        {Lang::T('Payment Token')}</option>
                                    <option value="both" {if $_c['hotspot_payment_type']=='both' }selected{/if}>
                                        {Lang::T('Both')}</option>
                                </select>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Select payment gateway(s) or token card(s)
                                    to be used for payment')}</small></p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Enable Message')}</label>
                            <div class="col-md-6">
                                <label class="switch">
                                    <input type="checkbox" id="hotspot_message" value="1" name="hotspot_message" {if
                                        $_c['hotspot_message']==1 }checked{/if}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Enable activation message')}</small></p>
                        </div>
                        <div class="form-group" id="message-group">
                            <label class="col-md-2 control-label">{Lang::T('Send Via')}</label>
                            <div class="col-md-6">
                                <select class="form-control" name="hotspot_message_via" id="hotspot_message_via">
                                    <option value="sms" {if $_c['hotspot_message_via']=='sms' }selected{/if}>
                                        {Lang::T('SMS')}</option>
                                    <option value="wa" {if $_c['hotspot_message_via']=='wa' }selected{/if}>
                                        {Lang::T('WhatsApp')}</option>
                                    <option value="both" {if $_c['hotspot_message_via']=='both' }selected{/if}>
                                        {Lang::T('Both')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="message-content-group">
                            <label class="col-md-2 control-label">{Lang::T('Message')}</label>
                            <div class="col-md-6">
                                <textarea class="form-control" id="hotspot_message_content"
                                    name="hotspot_message_content"
                                    rows="6">{Lang::htmlspecialchars($_json['hotspot_message_content'])}</textarea>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Message content to be sent to customer')}
                                    <br>
                                    <b>[[login_code]]</b> - {Lang::T('will replace customer login code')}.<br>
                                    <b>[[package]]</b> - {Lang::T('will replace customer package name')}.<br>
                                    <b>[[expiry]]</b> - {Lang::T('will replace customer package expiry date')}.<br>
                                    <b>[[company]]</b> - {Lang::T('will replace your Company Name')}.<br>
                                </small></p>
                        </div>
                        <div class="form-group" id="">
                            <label class="col-md-2 control-label">{Lang::T('Voucher Send Template')}</label>
                            <div class="col-md-6">
                                <textarea rows="8" name="voucher_send" id="voucher_send"
                                    class="form-control">{Lang::htmlspecialchars($_json['voucher_send'])}</textarea>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Message content to be used to sent voucher
                                    to customer')} <br>
                                    <b>[[code]]</b> - {Lang::T('will replace voucher code')}.<br>
                                    <b>[[data]]</b> - {Lang::T('will replace voucher data limit')}.<br>
                                    <b>[[validity]]</b> - {Lang::T('will replace voucher validity or duration')}.<br>
                                    <b>[[company]]</b> - {Lang::T('will replace your Company Name')}.<br>
                                </small>
                            </p>
                        </div>

                        <div class="form-group" id="">
                            <label class="col-md-2 control-label">{Lang::T('Voucher Print Template')}</label>
                            <div class="col-md-6">
                                <textarea rows="8" name="voucher_template" id="voucher_template"
                                    class="form-control">{Lang::htmlspecialchars($_json['voucher_template'])}</textarea>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Voucher printing content')}
                                    <br>
                                    <b>[[currency]]</b> - {Lang::T('will replace voucher currency code')}.<br>
                                    <b>[[plan_price]]</b> - {Lang::T('will replace voucher plan price')}.<br>
                                    <b>[[code]]</b> - {Lang::T('will replace voucher code')}.<br>
                                    <b>[[data]]</b> - {Lang::T('will replace voucher data limit')}.<br>
                                    <b>[[validity]]</b> - {Lang::T('will replace voucher validity or duration')}.<br>
                                    <b>[[url]]</b> - {Lang::T('will replace voucher with hotspot login url')}.<br>
                                    <b>[[qrcode]]</b> - {Lang::T('will replace voucher QRcode')}.<br></small>
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Clear Expired Vouchers')}</label>
                            <div class="col-md-6">
                                <label class="switch">
                                    <input type="checkbox" id="hotspot_cev" value="1" name="hotspot_cev" {if
                                        $_c['hotspot_cev']==1}checked{/if} onchange="toggleBatchSize()">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <p class="help-block col-md-4">
                                <b><small>{Lang::T('Clear expired vouchers to reduce database load')}.</small><br>
                            </p>
                        </div>
                        <div class="form-group" id="hotspot_cev_batch"
                            style="{if $_c['hotspot_cev']!=1}display:none;{/if}">
                            <label class="col-md-2 control-label">{Lang::T('Batch to clear')}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="hotspot_cev_batch" required
                                    value="{$_c['hotspot_cev_batch']}" placeholder="10" />
                            </div>
                            <p class="help-block col-md-4"><small>
                                    <b>{Lang::T('Batch to clear each time cron run to reduced overlapping [Max
                                        50]')}.</b></small><br>
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Clear Pending
                                Vouchers/Transactions')}</label>
                            <div class="col-md-6">
                                <label class="switch">
                                    <input type="checkbox" id="hotspot_clear_pending" value="1"
                                        name="hotspot_clear_pending" {if $_c['hotspot_clear_pending']==1}checked{/if}
                                        onchange="togglePendingTime()">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <p class="help-block col-md-4">
                                <b><small>{Lang::T('Clear pending vouchers/transactions to reduce database
                                        load')}.</small><br>
                            </p>
                        </div>
                        <div class="form-group" id="hotspot_clear_pending_time"
                            style="{if $_c['hotspot_clear_pending']!=1}display:none;{/if}">
                            <label class="col-md-2 control-label">{Lang::T('Time Interval [Mins]')}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="hotspot_clear_pending_time" required
                                    value="{$_c['hotspot_clear_pending_time']}" placeholder="30" />
                            </div>
                            <p class="help-block col-md-4"><small>
                                    <b>{Lang::T('Time Interval to clear pending vouchers/transactions, Ex. 30 for 30
                                        minutes')}.</b></small><br>
                            </p>
                        </div>

                        <div class="form-group" id="">
                            <label class="col-md-2 control-label">{Lang::T('Hotspot Login URL')}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="hotspot_url" required
                                    value="{$_c['hotspot_url']}" placeholder="http://login.phpwifizones.hotspot" />
                            </div>
                            <p class="help-block col-md-4">
                                <b><small>{Lang::T('The Hostpot DNS Name or IP Address set in Mikrotik')}.</small><br>
                            </p>
                        </div>

                        <div class="form-group" id="">
                            <label class="col-md-2 control-label">{Lang::T('Hotspot Redirect URL')}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="hotspot_redirect_url" required
                                    value="{$_c['hotspot_redirect_url']}" placeholder="http://www.google.com" />
                            </div>
                            <p class="help-block col-md-4">
                                <b><small>{Lang::T('The URL you want to redirect users after successful
                                        purchase')}.</small><br>
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Enable Radius')}</label>
                            <div class="col-md-6">
                                <label class="switch">
                                    <input type="checkbox" id="hotspot_radius_mode" value="1" name="hotspot_radius_mode"
                                        {if $_c['hotspot_radius_mode']==1}checked{/if}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Use FreeRadius System')}</small></p>
                        </div>

                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Enable Auto Login')}</label>
                            <div class="col-md-6">
                                <label class="switch">
                                    <input type="checkbox" id="hotspot_auto_login" value="1" name="hotspot_auto_login"
                                        {if $_c['hotspot_auto_login']==1}checked{/if}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <p class="help-block col-md-4"><small>{Lang::T('Log user in automatically after payment is
                                    confirmed')}</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {include file="hotspot_config_notifications.tpl"}
        <div class="panel-body">
            <div class="form-group">
                <button class="btn btn-success btn-block" name="save" value="save" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </form>
</section>
{include file="hotspot_config_notifications_js.tpl"}
<script>
    function toggleVoucherType() {
        var voucherMode = document.getElementById('hotspot_voucher_mode').checked;
        document.getElementById('voucher-type-group').style.display = voucherMode ? 'block' : 'none';
    }
    function toggleMessageGroups() {
        var messageMode = document.getElementById('hotspot_message').checked;
        document.getElementById('message-group').style.display = messageMode ? 'block' : 'none';
        document.getElementById('message-content-group').style.display = messageMode ? 'block' : 'none';
    }
    toggleVoucherType();
    toggleMessageGroups();

    document.getElementById('hotspot_voucher_mode').addEventListener('change', toggleVoucherType);
    document.getElementById('hotspot_message').addEventListener('change', toggleMessageGroups);

    function toggleBatchSize() {
        const checkbox = document.getElementById('hotspot_cev');
        const batchSizeDiv = document.getElementById('hotspot_cev_batch');
        batchSizeDiv.style.display = checkbox.checked ? 'block' : 'none';
    }

    function togglePendingTime() {
        const checkbox = document.getElementById('hotspot_clear_pending');
        const pendingTimeDiv = document.getElementById('hotspot_clear_pending_time');
        pendingTimeDiv.style.display = checkbox.checked ? 'block' : 'none';
    }
</script>

{include file="sections/footer.tpl"}