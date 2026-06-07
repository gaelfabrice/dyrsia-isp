{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/mypvit">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <i class="fa fa-mobile"></i> MyPVit Mobile Money - Payment Gateway
                </div>
                <div class="panel-body">

                    <div class="alert alert-info">
                        <strong><i class="fa fa-info-circle"></i> MyPVit</strong> permet d'accepter les paiements
                        <strong>Airtel Money</strong>, <strong>Moov Money</strong> et cartes via l'API REST CEMAC.
                        <br><small>Documentation : <a href="https://docs.mypvit.pro/fr/intro/getting-started" target="_blank">docs.mypvit.pro</a></small>
                    </div>

                    {if $mobile_gateway_conflict && $mobile_gateway_conflict neq 'mypvit'}
                        <div class="alert alert-warning">
                            <strong>Passerelle active :</strong> {$mobile_gateway_conflict|ucwords}.
                            En enregistrant MyPVit, CamPay sera désactivé automatiquement.
                        </div>
                    {/if}

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Environment')}</label>
                        <div class="col-md-6">
                            <select name="mypvit_environment" class="form-control">
                                <option value="test" {if $_c['mypvit_environment'] eq 'test' || empty($_c['mypvit_environment'])}selected{/if}>Test</option>
                                <option value="prod" {if $_c['mypvit_environment'] eq 'prod'}selected{/if}>Production</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Passage en production MyPVit</strong><br>
                        MyPVit compte les transactions dans votre dashboard uniquement si :
                        <ul class="mb0">
                            <li>Le webhook callback (<code>{$_url}callback/mypvit</code>) est accessible en HTTPS depuis Internet (ngrok ou domaine public).</li>
                            <li>Le code callback <strong>{$_c['mypvit_callback_url_code']|default:'—'}</strong> est actif dans MyPVit → Paramétrages → Urls.</li>
                            <li>Votre serveur répond au callback avec <code>{"responseCode":200,"transactionId":"PAY..."}</code> — obligatoire en mode test.</li>
                            <li>Vous consultez <strong>MyPVit → Reporting</strong> (pas seulement le tableau de bord) filtré sur le compte test <strong>{$_c['mypvit_operation_account_code']|default:'ACC_...'}</strong>.</li>
                            <li>Tests requis : 2 paiements réussis (≤ 1000 XAF) + 2 échecs (&gt; 1000 XAF) via le compte d'opération test.</li>
                        </ul>
                    </div>

                    {if isset($mypvit_diagnostic)}
                    <div class="panel panel-default">
                        <div class="panel-heading"><i class="fa fa-stethoscope"></i> Diagnostic MyPVit</div>
                        <div class="panel-body">
                            <p>{$mypvit_diagnostic.message}</p>
                            {if $mypvit_diagnostic.recent_payments|@count > 0}
                            <div class="table-responsive">
                                <table class="table table-condensed table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Ref. marchand</th>
                                            <th>ID MyPVit</th>
                                            <th>Statut local</th>
                                            <th>Statut MyPVit API</th>
                                            <th>Montant</th>
                                            <th>Compte op.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach $mypvit_diagnostic.recent_payments as $row}
                                        <tr>
                                            <td>{$row.merchant_ref|default:'—'}</td>
                                            <td>{$row.pay_id}</td>
                                            <td>{$row.local_status}</td>
                                            <td><strong>{$row.mypvit_status}</strong></td>
                                            <td>{$row.amount} XAF</td>
                                            <td>{$row.operation_account|default:$mypvit_diagnostic.operation_account}</td>
                                        </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                            <p class="help-block mb0">
                                Si le statut MyPVit API est <strong>SUCCESS</strong> ou <strong>FAILED</strong>, la transaction est bien chez MyPVit.
                                Ouvrez <a href="https://mypvit.pro/" target="_blank">mypvit.pro</a> → <strong>Reporting</strong> pour la voir dans le dashboard.
                            </p>
                            {/if}
                        </div>
                    </div>
                    {/if}

                    <div class="alert alert-warning">
                        <strong>Renouveler la clé secrète (SK)</strong> — ce n'est <em>pas</em> sur la page Comptes.<br>
                        1. MyPVit → <strong>Paramétrages → APIs</strong><br>
                        2. Bloc <strong>RENEW SECRET KEY</strong> (v1) — saisir le mot de passe puis <strong>Valider</strong><br>
                        3. La nouvelle clé arrive sur le webhook « clé secrète » (ngrok actif) ou cochez ci-dessous depuis WifiZone.
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Code URL REST</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="mypvit_code_url"
                                value="{$_c['mypvit_code_url']}" placeholder="Ex: BR1DRZIKAXEQ9T2C">
                            <span class="help-block">MyPVit → APIs → endpoint <strong>REST (v2)</strong> — segment avant <code>/rest</code>.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Code URL check status</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="mypvit_status_code_url"
                                value="{$_c['mypvit_status_code_url']}" placeholder="Ex: 5J8H43CUQEOT7IHK">
                            <span class="help-block">MyPVit → APIs → <strong>CHECK STATUS</strong> — segment avant <code>/status</code>. Distinct du code REST.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Code URL renew-secret</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="mypvit_renew_secret_code_url"
                                value="{$_c['mypvit_renew_secret_code_url']}" placeholder="Ex: FFZ58X67WJAPFKF0">
                            <span class="help-block">MyPVit → APIs → <strong>RENEW SECRET KEY</strong> — segment avant <code>/renew-secret</code>. Si v2 : préfixe <code>/v2/</code>, la clé est renvoyée directement (sans webhook). Attention <strong>O</strong> vs <strong>0</strong> dans le code.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Clé secrète (X-Secret)</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="mypvit_secret_key"
                                value="{$_c['mypvit_secret_key']}" placeholder="sk_live_...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Compte d'opération</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="mypvit_operation_account_code"
                                value="{$_c['mypvit_operation_account_code']}" placeholder="ACC_XXXXX">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Code URL callback</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="mypvit_callback_url_code"
                                value="{$_c['mypvit_callback_url_code']}" placeholder="Ex: A1B2C3D4E5F6" maxlength="12">
                            <span class="help-block">Code court généré dans MyPVit → <strong>Urls</strong> (type Callback), max 12 caractères — <em>pas</em> l'URL ngrok.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Code URL réception clé</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="mypvit_secret_reception_url_code"
                                value="{$_c['mypvit_secret_reception_url_code']}" placeholder="Code renew-secret" maxlength="12">
                            <span class="help-block">Code MyPVit (type Réception de clé secrète), pas une URL.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Mot de passe API</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="mypvit_api_password"
                                value="{$_c['mypvit_api_password']}" placeholder="Mot de passe renew-secret">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Indicatif téléphone</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="mypvit_phone_prefix"
                                value="{$_c['mypvit_phone_prefix']|default:'241'}" placeholder="241">
                            <span class="help-block">241 (Gabon) ou 237 (Cameroun) selon votre marché.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Opérateur par défaut</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="mypvit_default_operator_code"
                                value="{$_c['mypvit_default_operator_code']}" placeholder="MOOV_MONEY, GAB_AIRTEL, CMR_ORANGE...">
                            <span class="help-block">Laisser vide pour détection automatique selon le numéro.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Currency')}</label>
                        <div class="col-md-6">
                            <select name="mypvit_currency" class="form-control">
                                <option value="XAF" {if $_c['mypvit_currency'] eq 'XAF' || empty($_c['mypvit_currency'])}selected{/if}>XAF - Franc CFA (CEMAC)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Webhook paiement</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()"
                                value="{$_url}callback/mypvit">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Webhook clé secrète</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()"
                                value="{$_url}callback/mypvit_secret">
                            <span class="help-block">URL de réception de clé à enregistrer dans MyPVit (type « Réception de clé secrète »).</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-offset-2 col-md-6">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="renew_secret" value="1"> Renouveler la clé secrète maintenant (renew-secret)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <button class="btn btn-primary btn-block" name="save" value="1" type="submit">
                        {Lang::T('Save Changes')}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}
