{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/campay">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <i class="fa fa-mobile"></i> CamPay Mobile Money - Payment Gateway
                </div>
                <div class="panel-body">
                    
                    <div class="alert alert-info">
                        <strong><i class="fa fa-info-circle"></i> CamPay</strong> permet d'accepter les paiements 
                        <strong>MTN Mobile Money</strong> et <strong>Orange Money</strong> au Cameroun.
                        <br><small>Créez un compte sur <a href="https://www.campay.net" target="_blank">campay.net</a> pour obtenir vos credentials.</small>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Environment')}</label>
                        <div class="col-md-6">
                            <select name="campay_environment" class="form-control">
                                <option value="demo" {if $_c['campay_environment'] eq 'demo' || empty($_c['campay_environment'])}selected{/if}>
                                    Demo (Test)
                                </option>
                                <option value="prod" {if $_c['campay_environment'] eq 'prod'}selected{/if}>
                                    Production (Live)
                                </option>
                            </select>
                            <span class="help-block">
                                Demo: <code>demo.campay.net</code> | Production: <code>campay.net</code>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">App Username</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="campay_username" name="campay_username"
                                value="{$_c['campay_username']}" placeholder="Votre App Username CamPay">
                            <span class="help-block">
                                <a href="https://demo.campay.net/en/users/signup/" target="_blank">
                                    <i class="fa fa-external-link"></i> Créer un compte Demo
                                </a> | 
                                <a href="https://www.campay.net/en/login/" target="_blank">
                                    <i class="fa fa-external-link"></i> Dashboard Production
                                </a>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">App Password</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="campay_password" name="campay_password"
                                value="{$_c['campay_password']}" placeholder="Votre App Password CamPay">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Currency')}</label>
                        <div class="col-md-6">
                            <select name="campay_currency" class="form-control">
                                <option value="XAF" {if $_c['campay_currency'] eq 'XAF' || empty($_c['campay_currency'])}selected{/if}>
                                    XAF - Franc CFA (CEMAC)
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Webhook URL</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()"
                                value="{$_url}callback/campay">
                            <span class="help-block">
                                Copiez cette URL dans votre dashboard CamPay sous "Webhook URL"
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">
                                <i class="fa fa-save"></i> {Lang::T('Save')}
                            </button>
                        </div>
                    </div>

                    <div class="bs-callout bs-callout-warning" id="callout-mikrotik">
                        <h4><i class="fa fa-wifi"></i> Configuration MikroTik</h4>
                        <p>Ajoutez ces domaines au walled-garden pour permettre les paiements :</p>
                        <pre>/ip hotspot walled-garden
add dst-host=campay.net
add dst-host=*.campay.net
add dst-host=demo.campay.net
add dst-host=*.demo.campay.net</pre>
                    </div>

                    <div class="bs-callout bs-callout-info">
                        <h4><i class="fa fa-phone"></i> Opérateurs supportés</h4>
                        <ul>
                            <li><strong>MTN Mobile Money</strong> - Numéros commençant par 67, 68, 650-654</li>
                            <li><strong>Orange Money</strong> - Numéros commençant par 69, 655-659</li>
                        </ul>
                        <small class="text-muted">
                            Le client reçoit une demande de paiement USSD sur son téléphone et confirme avec son code PIN.
                        </small>
                    </div>

                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}
