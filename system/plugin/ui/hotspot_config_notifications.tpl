        <div class="row" style="margin-top: 15px;">
            <div class="col-sm-12">
                <div class="panel panel-info panel-hovered mb30">
                    <div class="panel-heading">{Lang::T('Notification channels')}
                        <small class="pull-right" style="font-weight: normal;">
                            <a href="{$settings_app_url}">{Lang::T('General Settings')}</a>
                        </small>
                    </div>
                    <div class="panel-body">
                        <p class="text-muted">{Lang::T('Hotspot messages use the same credentials as')} <strong>{Lang::T('General Settings')}</strong>. {Lang::T('Save First before Test')}</p>

                        <div class="panel-group" id="hotspotNotifyAccordion" role="tablist">
                            <div class="panel panel-default" id="collapseHotspotTg">
                                <div class="panel-heading" style="cursor:pointer;" data-toggle="collapse" data-target="#hotspotTgBody">
                                    <strong>{Lang::T('Telegram Notification')}</strong>
                                    <span class="label label-{if $notify_tg_ok}success{else}warning{/if} pull-right">{if $notify_tg_ok}{Lang::T('success')}{else}{Lang::T('Not configured')}{/if}</span>
                                    <a class="btn btn-success btn-xs pull-right" style="margin-right:8px;color:#000;" href="javascript:void(0)" onclick="event.stopPropagation();hotspotTestTg();return false;">Test TG</a>
                                </div>
                                <div id="hotspotTgBody" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="list-unstyled">
                                            <li><b>{Lang::T('Telegram Bot Token')}:</b> {if $notify_tg_ok}••••••{else}<em>{Lang::T('Empty')}</em>{/if}</li>
                                            <li><b>{Lang::T('Telegram User/Channel/Group ID')}:</b> {if $_c['telegram_target_id']}{$_c['telegram_target_id']}{else}<em>{Lang::T('Empty')}</em>{/if}</li>
                                        </ul>
                                        <a href="{$settings_app_url}#collapseTelegramNotification" class="btn btn-default btn-sm">{Lang::T('Edit in General Settings')}</a>
                                    </div>
                                </div>
                            </div>

                            <div class="panel panel-default" id="collapseHotspotSms">
                                <div class="panel-heading" style="cursor:pointer;" data-toggle="collapse" data-target="#hotspotSmsBody">
                                    <strong>{Lang::T('SMS Notification')}</strong>
                                    <span class="label label-{if $notify_sms_ok}success{else}warning{/if} pull-right">{if $notify_sms_ok}{Lang::T('success')}{else}{Lang::T('Not configured')}{/if}</span>
                                    <a class="btn btn-success btn-xs pull-right" style="margin-right:8px;color:#000;" href="javascript:void(0)" onclick="event.stopPropagation();hotspotTestSms();return false;">{Lang::T('Test SMS')}</a>
                                </div>
                                <div id="hotspotSmsBody" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <p><b>{Lang::T('SMS Server URL')}:</b><br><code style="word-break:break-all;">{if $_c['sms_url']}{$_c['sms_url']}{else}{Lang::T('Empty')}{/if}</code></p>
                                        <p><b>{Lang::T('Mikrotik SMS Command')}:</b> {$_c['mikrotik_sms_command']|default:'/tool sms send'}</p>
                                        <a href="{$settings_app_url}#collapseSMSNotification" class="btn btn-default btn-sm">{Lang::T('Edit in General Settings')}</a>
                                    </div>
                                </div>
                            </div>

                            <div class="panel panel-default" id="collapseHotspotWa">
                                <div class="panel-heading" style="cursor:pointer;" data-toggle="collapse" data-target="#hotspotWaBody">
                                    <strong>{Lang::T('Whatsapp Notification')}</strong>
                                    <span class="label label-{if $notify_wa_ok}success{else}warning{/if} pull-right">{if $notify_wa_ok}{Lang::T('success')}{else}{Lang::T('Not configured')}{/if}</span>
                                    <a class="btn btn-success btn-xs pull-right" style="margin-right:8px;color:#000;" href="javascript:void(0)" onclick="event.stopPropagation();hotspotTestWa();return false;">Test WA</a>
                                </div>
                                <div id="hotspotWaBody" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <p><b>{Lang::T('WhatsApp Server URL')}:</b><br><code style="word-break:break-all;">{if $_c['wa_url']}{$_c['wa_url']}{else}{Lang::T('Empty')}{/if}</code></p>
                                        <a href="{$settings_app_url}#collapseWhatsappNotification" class="btn btn-default btn-sm">{Lang::T('Edit in General Settings')}</a>
                                    </div>
                                </div>
                            </div>

                            <div class="panel panel-default" id="collapseHotspotEmail">
                                <div class="panel-heading" style="cursor:pointer;" data-toggle="collapse" data-target="#hotspotEmailBody">
                                    <strong>{Lang::T('Email Notification')}</strong>
                                    <span class="label label-{if $notify_email_ok}success{else}warning{/if} pull-right">{if $notify_email_ok}{Lang::T('success')}{else}{Lang::T('Not configured')}{/if}</span>
                                    <a class="btn btn-success btn-xs pull-right" style="margin-right:8px;color:#000;" href="javascript:void(0)" onclick="event.stopPropagation();hotspotTestEmail();return false;">Test Email</a>
                                </div>
                                <div id="hotspotEmailBody" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="list-unstyled">
                                            <li><b>SMTP:</b> {$_c['smtp_host']|default:'—'} : {$_c['smtp_port']|default:'—'}</li>
                                            <li><b>{Lang::T('SMTP Username')}:</b> {$_c['smtp_user']|default:'—'}</li>
                                            <li><b>Mail {Lang::T('From')}:</b> {$_c['mail_from']|default:'—'}</li>
                                        </ul>
                                        <a href="{$settings_app_url}#collapseEmailNotification" class="btn btn-default btn-sm">{Lang::T('Edit in General Settings')}</a>
                                    </div>
                                </div>
                            </div>

                            <div class="panel panel-default" id="collapseHotspotUserNotif">
                                <div class="panel-heading" style="cursor:pointer;" data-toggle="collapse" data-target="#hotspotUserNotifBody">
                                    <strong>{Lang::T('User Notification')}</strong>
                                </div>
                                <div id="hotspotUserNotifBody" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="list-unstyled">
                                            <li><b>{Lang::T('Expired Notification')}:</b> {$_c['user_notification_expired']|default:'none'}</li>
                                            <li><b>{Lang::T('Payment Notification')}:</b> {$_c['user_notification_payment']|default:'none'}</li>
                                            <li><b>{Lang::T('Reminder Notification')}:</b> {$_c['user_notification_reminder']|default:'none'}</li>
                                        </ul>
                                        <a href="{$settings_app_url}#collapseUserNotification" class="btn btn-default btn-sm">{Lang::T('Edit in General Settings')}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
