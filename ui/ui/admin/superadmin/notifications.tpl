{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">
                <i class="fa fa-paper-plane"></i> Telegram Config
            </div>
            <div class="panel-body">
                <p style="margin:0 0 18px;color:#64748b;line-height:1.55">
                    Configurez le bot Telegram du SuperAdmin pour recevoir une alerte à chaque création d'instance
                    (Full Name, Email, Phone Number, ISP / Business Name, Pays, Desired Subdomain).
                </p>

                <form method="post" action="{Text::url('superadmin/notifications-post')}" class="form-horizontal">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">

                    <div class="form-group">
                        <label class="col-md-4 control-label" for="telegram_bot">Telegram Bot Token</label>
                        <div class="col-md-7">
                            <input type="password" class="form-control" id="telegram_bot" name="telegram_bot"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                                value="{$telegram_settings.bot|default:''}"
                                placeholder="123456789:AAExampleToken">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label" for="telegram_chat_id">Telegram Chat ID</label>
                        <div class="col-md-7">
                            <input type="text" class="form-control" id="telegram_chat_id" name="telegram_chat_id"
                                value="{$telegram_settings.chat_id|default:''}"
                                placeholder="-1001234567890">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-7 col-md-offset-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> {Lang::T('Save')}
                            </button>
                            <a href="{Text::url('superadmin/notifications&testTg=test')}" class="btn btn-success">
                                <i class="fa fa-paper-plane"></i> Test Telegram
                            </a>
                        </div>
                    </div>
                </form>

                <hr>

                <p style="margin:0 0 8px;font-weight:700">Aperçu du message envoyé :</p>
                <pre style="background:#0f172a;color:#e2e8f0;border-radius:10px;padding:16px;font-size:12px;line-height:1.5;white-space:pre-wrap;margin:0">🔔 ──────────────────────────────
    ✨ NOUVELLE INSTANCE ✨
────────────────────────────────

👤  Full Name          :  Jean Dupont
📧  Email              :  admin@isp.com
🏢  ISP/Business Name  :  Mombasa Fiber
🌍  Pays               :  cameroun
📱  Phone Number       :  677123456
🔗  Desired Subdomain  :  wizfiber.dyrsia.com

────────────────────────────────
🕐  DD/MM/YYYY - HH:MM
🔔 ──────────────────────────────</pre>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
