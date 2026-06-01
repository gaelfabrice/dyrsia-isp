{include file="admin/header.tpl"}

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">Serveur SMTP</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{Text::url('settings/smtp-post')}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="form-group">
                        <label class="col-md-3 control-label">Serveur SMTP</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="smtp_host" value="{$_c['smtp_host']|default:'smtp.gmail.com'}" placeholder="smtp.gmail.com" pattern="[a-zA-Z0-9.-]+" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Port</label>
                        <div class="col-md-9">
                            <input type="number" class="form-control" name="smtp_port" value="{$_c['smtp_port']|default:'587'}" placeholder="587" min="1" max="65535" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Sécurité</label>
                        <div class="col-md-9">
                            <select class="form-control" name="smtp_ssltls">
                                <option value="tls" {if ($_c['smtp_ssltls']|default:'tls') eq 'tls'}selected{/if}>TLS</option>
                                <option value="ssl" {if ($_c['smtp_ssltls']|default:'tls') eq 'ssl'}selected{/if}>SSL</option>
                                <option value="" {if ($_c['smtp_ssltls']|default:'tls') eq ''}selected{/if}>Aucune</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Login SMTP</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="smtp_user" value="{$_c['smtp_user']|default:''}" placeholder="votrecompte@gmail.com" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Password SMTP</label>
                        <div class="col-md-9">
                            <input type="password" class="form-control" name="smtp_pass" value="{$_c['smtp_pass']|default:''}" placeholder="Mot de passe d'application Gmail" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Email expéditeur</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="mail_from" value="{$_c['mail_from']|default:''}" placeholder="votrecompte@gmail.com">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Reply-To</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="mail_reply_to" value="{$_c['mail_reply_to']|default:''}" placeholder="support@example.com">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Email de test</label>
                        <div class="col-md-9">
                            <input type="email" class="form-control" name="test_email" placeholder="Email qui recevra le test SMTP">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-3 col-md-9">
                            <button class="btn btn-primary" type="submit">Enregistrer</button>
                            <button class="btn btn-success" type="submit" onclick="document.querySelector('[name=test_email]').required=true;">Tester SMTP</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default panel-hovered panel-stacked mb30">
            <div class="panel-heading">Configuration Gmail par défaut</div>
            <div class="panel-body">
                <p><strong>Serveur :</strong> smtp.gmail.com</p>
                <p><strong>Port :</strong> 587</p>
                <p><strong>Sécurité :</strong> TLS</p>
                <p>Pour Gmail, utilisez un <strong>mot de passe d'application</strong>, pas le mot de passe normal du compte.</p>
            </div>
        </div>
    </div>
</div>

{include file="admin/footer.tpl"}
