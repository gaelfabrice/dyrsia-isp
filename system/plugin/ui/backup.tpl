{include file="sections/header.tpl"}


<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-danger">
            <div class="panel-heading">Sauvegarde complete</div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <strong>Backup complet en 1 clic:</strong> cette sauvegarde inclut la base de donnees, les transactions,
                    les tickets, les comptes actifs/expires, les fichiers de `system/uploads` et les fichiers essentiels
                    comme `config.php`. Lors d'une restauration complete, une sauvegarde de secours est creee
                    automatiquement avant toute modification.
                    Si l'upload Telegram est active dans les reglages ci-dessous, une copie du package complet
                    (`.wzb.zip`) est aussi envoyee sur Telegram (limite ~49 Mo).
                </div>
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form method="post" action="{$_url}plugin/backup_upload_full_form" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <div class="input-group">
                                <input class="form-control" type="file" name="file" accept=".zip,.wzb.zip,application/zip">
                                <div class="input-group-btn">
                                    <button class="btn btn-warning" type="submit"><span class="fa fa-upload"></span> Upload package complet</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="POST" action="{$_url}plugin/backup_create_full" id="form-backup-create-full">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <input type="hidden" name="ajax" value="1">
                            <button class="btn btn-danger btn-block waves-effect" type="submit" id="btn-backup-create-full">
                                Creer un backup complet
                            </button>
                        </form>
                        <p id="backup-full-status" class="text-muted" style="margin-top:8px;display:none;"></p>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    {if empty($fullBackupFiles)}
                    <p align="center"><b>Aucun backup complet.</b></p>
                    {else}
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>{Lang::T('Date')}</th>
                                <th>{Lang::T('Size')}</th>
                                <th>{Lang::T('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $fullBackupFiles as $backup}
                            <tr>
                                <td>{$backup.file}</td>
                                <td>{$backup.creation_date}</td>
                                <td>{$backup.size}</td>
                                <td align="center">
                                    <a href="{$_url}plugin/backup_download_full&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;" class="btn btn-success btn-xs">{Lang::T('Download')}</a>
                                    <a href="{$_url}plugin/backup_restore_full&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;"
                                        onclick="return confirm('Cette restauration va remettre la base, les uploads et la configuration de ce backup. Une sauvegarde de secours sera creee automatiquement. Continuer ?')"
                                        class="btn btn-danger btn-xs">Restaurer complet</a>
                                    <a href="{$_url}plugin/backup_delete_full&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;"
                                        onclick="return confirm('{Lang::T('Are you Sure you want to Delete this Database?')}')"
                                        class="btn btn-default btn-xs">{Lang::T('Delete')}</a>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    {/if}
                </div>
            </div>
        </div>

        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{Lang::T('Backup Database')}</div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form method="post" action="{$_url}plugin/backup_upload_form" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <div class="input-group">
                                <input class="form-control" type="file" name="file" accept="application/*.sql">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit"><span class="fa fa-upload">
                                        </span> {Lang::T('Upload')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="POST" action="{$_url}plugin/backup_add">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <input class="btn btn-primary btn-block waves-effect" type="submit" name="createBackup"
                                value="Create Backup">
                        </form>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    {if empty($backupFiles)}
                    <p align="center"><b>{Lang::T('Backup not found.')}</b></p>
                    {else}
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{Lang::T('Backup File')}</th>
                                <th>{Lang::T('Date')}</th>
                                <th>{Lang::T('Size')}</th>
                                <th>{Lang::T('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>

                            {foreach $backupFiles as $backup}
                            <tr>
                                <td>{$backup.file}</td>
                                <td>{$backup.creation_date}</td>
                                <td>{$backup.size}</td>
                                <td align="center">
                                    <a href="{$_url}plugin/backup_download&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;" class="btn btn-success btn-xs">{Lang::T('Download')}</a>
                                    <a href="{$_url}plugin/backup_restore&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;"
                                        onclick="return confirm('{Lang::T('Are you Sure you want to Restore this Database?')}')"
                                        class="btn btn-primary btn-xs">{Lang::T('Restore')}</a>
                                    <a href="{$_url}plugin/backup_delete&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;"
                                        onclick="return confirm('{Lang::T('Are you Sure you want to Delete this Database?')}')"
                                        class="btn btn-danger btn-xs">{Lang::T('Delete')}</a>
                                </td>
                            </tr>
                            {/foreach}
                            {/if}

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<form class="form-horizontal" method="post" role="form" action="{$_url}plugin/backup_settingsPost">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Backup Settings')}</div>
                <div class="panel-body">
                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Auto Backup')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="backup_auto" value="1" name="backup_auto" {if
                                    $_c['backup_auto']==1}checked{/if} onchange="toggleBackupFrequency()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div id="backup_frequency_section" style="display: {if $_c['backup_auto']==1}block{else}none{/if};">
                        <div class="form-group col-6">
                            <label class="col-md-3 control-label">{Lang::T('Choose Backup Frequency')}</label>
                            <div class="col-md-6">
                                <select class="form-control" name="backup_backup_time" id="backup_backup_time">
                                    <option value="everyday" {if $_c['backup_backup_time']=='everyday' }selected{/if}>
                                        {Lang::T('Everyday')}</option>
                                    <option value="everyweek" {if $_c['backup_backup_time']=='everyweek' }selected{/if}>
                                        {Lang::T('Everyweek')}</option>
                                    <option value="everymonth" {if $_c['backup_backup_time']=='everymonth'
                                        }selected{/if}>
                                        {Lang::T('Everymonth')}</option>
                                </select>
                                <small class="form-text text-muted">
                                    <font color="red"></font> {Lang::T('Backup occurs at 00:00 Hrs')}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Auto Clear Old Backup')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="backup_clear_old" value="1" name="backup_clear_old" {if
                                    $_c['backup_clear_old']==1}checked{/if} onchange="toggleRetainCount()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div id="retain_count_section" style="display: {if $_c['backup_clear_old']==1}block{else}none{/if};"
                        class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Backup Retain Count')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" id="backup_retain_count"
                                name="backup_retain_count" placeholder="5" value="{$_c['backup_retain_count']}">
                            <small class="form-text text-muted">
                                <font color="red"></font> {Lang::T('Retain count must be greater than 0, if you enable
                                auto clear old backup.')}
                            </small>
                        </div>
                    </div>

                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Cloud Upload')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="cloud_upload" value="1" name="cloud_upload" {if
                                    $_c['cloud_upload']==1}checked{/if} onchange="toggleCloudFields()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Dropbox Upload')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="backup_dropbox_upload" value="1" name="backup_dropbox_upload"
                                    {if $_c['backup_dropbox_upload']==1}checked{/if} onchange="toggleDropboxFields()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div id="dropbox_fields" style="display: {if $_c['dropbox_upload']==1}block{else}none{/if};">
                        <div class="form-group col-6">
                            <label for="backup_dropbox_token" class="col-md-3 control-label">{Lang::T('Dropbox Access
                                Token')}</label>
                            <div class="col-md-6">
                                <input type="password" class="form-control" id="backup_dropbox_token"
                                    name="backup_dropbox_token"
                                    placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
                                    value="{$_c['backup_dropbox_token']}">
                                <small class="form-text text-muted">
                                    <font color="red"></font> {Lang::T('Your Dropbox Access Token, get it from your
                                    Dropbox App settings.')} <br>
                                    <a href="https://www.dropbox.com/developers/apps" target="_blank">{Lang::T('Get
                                        Dropbox Access Token')}</a>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Google Drive Upload')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="backup_gdrive_upload" value="1" name="backup_gdrive_upload"
                                    {if $_c['backup_gdrive_upload']==1}checked{/if} onchange="toggleGdriveFields()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div id="gdrive_fields" style="display: {if $_c['backup_gdrive_upload']==1}block{else}none{/if};">
                        <div class="form-group col-6">
                            <label for="backup_gdrive_client_id" class="col-md-3 control-label">{Lang::T('Google Client ID')}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="backup_gdrive_client_id"
                                    name="backup_gdrive_client_id" placeholder="123456789.apps.googleusercontent.com"
                                    value="{$_c['backup_gdrive_client_id']}">
                            </div>
                        </div>
                        <div class="form-group col-6">
                            <label for="backup_gdrive_client_secret" class="col-md-3 control-label">{Lang::T('Google Client Secret')}</label>
                            <div class="col-md-6">
                                <input type="password" class="form-control" id="backup_gdrive_client_secret"
                                    name="backup_gdrive_client_secret" placeholder="GOCSPX-xxxxxxxx"
                                    value="{$_c['backup_gdrive_client_secret']}">
                            </div>
                        </div>
                        <div class="form-group col-6">
                            <label for="backup_gdrive_refresh_token" class="col-md-3 control-label">{Lang::T('Google Refresh Token')}</label>
                            <div class="col-md-6">
                                <input type="password" class="form-control" id="backup_gdrive_refresh_token"
                                    name="backup_gdrive_refresh_token" placeholder="1//xxxxxxxx"
                                    value="{$_c['backup_gdrive_refresh_token']}">
                                <small class="form-text text-muted">
                                    {Lang::T('OAuth refresh token with Drive scope')} (<code>https://www.googleapis.com/auth/drive.file</code>).
                                    <a href="https://developers.google.com/oauthplayground/" target="_blank">{Lang::T('Google OAuth Playground')}</a>
                                </small>
                            </div>
                        </div>
                        <div class="form-group col-6">
                            <label for="backup_gdrive_folder_id" class="col-md-3 control-label">{Lang::T('Google Drive Folder ID')}</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="backup_gdrive_folder_id"
                                    name="backup_gdrive_folder_id" placeholder="1abcDEFghiJKLmnOPQ"
                                    value="{$_c['backup_gdrive_folder_id']}">
                                <small class="form-text text-muted">
                                    {Lang::T('Optional — leave empty to upload to My Drive root. Folder ID is in the Drive URL after /folders/')}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Telegram Upload')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="backup_telegram_upload" value="1"
                                    name="backup_telegram_upload" {if $_c['backup_telegram_upload']==1}checked{/if}
                                    onchange="toggleTelegramFields()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div id="telegram_fields"
                        style="display: {if $_c['backup_telegram_upload']==1}block{else}none{/if};">
                        <div class="form-group col-6">
                            <label for="backup_telegram_chatId" class="col-md-3 control-label">{Lang::T('Telegram UserID
                                or Chat ID')}</label>
                            <div class="col-md-6">
                                <input type="password" class="form-control" id="backup_telegram_chatId"
                                    name="backup_telegram_chatId" placeholder="172662882"
                                    value="{$_c['backup_telegram_chatId']}">
                                <small class="form-text text-muted">
                                    {Lang::T('To get your Telegram UserID, Message ')}<a href="https://t.me/userinfobot"
                                        target="_blank">@userinfobot</a> | {Lang::T('Leave empty if you want to send to
                                    system Telegram notification ID')}
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group col-6">
                        <div class="col-lg-offset-3 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" name="save" value="save"
                                type="submit">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="bs-callout bs-callout-info" id="callout-navbar-role">
    <p>
    <h4><b>Note</b>:</h4>
    {Lang::T('Make sure your server support shell_exec function, else you may get errors while creating database
    backup.')} <br> {Lang::T('Auto Clear Old Backup will clear your old backups and leave only 5 recent backups.')}
    </p>
    <p>
    <h4><b>Dropbox Cloud Backup</b>:</h4>
    {Lang::T('Visit:')} <a href="https://www.dropbox.com/developers/apps" target="_blank">Get
        Dropbox Access Token</a>
    <br>
    {Lang::T('Create a')} New App <br>
    {Lang::T('Select')} "Full Access" <br>
    {Lang::T('Goto')} "Permission Tab" <br>
    {Lang::T('In')} "Individual Scopes" <br>
    {Lang::T('Under')} "Files and folders"<br>
    {Lang::T('Select')}: "files.content.write" and "files.content.read"<br>
    {Lang::T('Click on')} Submit <br>
    {Lang::T('Goto')} "Settings Tab" <br>
    {Lang::T('Generate')} "new access token" <br>
    {Lang::T('Copy the generated')} "access token" <br>
    <p>
    <h4><b>Google Drive Cloud Backup</b>:</h4>
    {Lang::T('Visit')} <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a><br>
    {Lang::T('Create a project, enable Google Drive API, then create OAuth 2.0 credentials (Desktop or Web app).')}<br>
    {Lang::T('Use')} <a href="https://developers.google.com/oauthplayground/" target="_blank">OAuth Playground</a>
    {Lang::T('with scope')} <code>https://www.googleapis.com/auth/drive.file</code> {Lang::T('to obtain a refresh token.')}<br>
    {Lang::T('Paste Client ID, Client Secret and Refresh Token above, then enable Google Drive Upload.')}
    </p>
</div>

<script>
    function toggleBackupFrequency() {
        const autoBackupCheckbox = document.getElementById('backup_auto');
        const backupFrequencySection = document.getElementById('backup_frequency_section');
        backupFrequencySection.style.display = autoBackupCheckbox.checked ? 'block' : 'none';
    }

    function toggleRetainCount() {
        const autoClearCheckbox = document.getElementById('backup_clear_old');
        const retainCountSection = document.getElementById('retain_count_section');
        retainCountSection.style.display = autoClearCheckbox.checked ? 'block' : 'none';
    }

    function toggleCloudFields() {
        const cloudUploadCheckbox = document.getElementById('cloud_upload');
        const dropboxSection = document.querySelector('.form-group:has(#backup_dropbox_upload)');
        const gdriveSection = document.querySelector('.form-group:has(#backup_gdrive_upload)');
        const telegramSection = document.querySelector('.form-group:has(#backup_telegram_upload)');
        const dropboxFields = document.getElementById('dropbox_fields');
        const gdriveFields = document.getElementById('gdrive_fields');
        const telegramFields = document.getElementById('telegram_fields');

        if (cloudUploadCheckbox.checked) {
            dropboxSection.style.display = 'block';
            gdriveSection.style.display = 'block';
            telegramSection.style.display = 'block';
            toggleDropboxFields();
            toggleGdriveFields();
            toggleTelegramFields();
        } else {
            dropboxSection.style.display = 'none';
            gdriveSection.style.display = 'none';
            telegramSection.style.display = 'none';
            dropboxFields.style.display = 'none';
            gdriveFields.style.display = 'none';
            telegramFields.style.display = 'none';
        }
    }

    function toggleDropboxFields() {
        if (!document.getElementById('cloud_upload').checked) return;
        const dropboxUploadCheckbox = document.getElementById('backup_dropbox_upload');
        const dropBoxFields = document.getElementById('dropbox_fields');
        dropBoxFields.style.display = dropboxUploadCheckbox.checked ? 'block' : 'none';
    }

    function toggleGdriveFields() {
        if (!document.getElementById('cloud_upload').checked) return;
        const gdriveUploadCheckbox = document.getElementById('backup_gdrive_upload');
        const gdriveFields = document.getElementById('gdrive_fields');
        gdriveFields.style.display = gdriveUploadCheckbox.checked ? 'block' : 'none';
    }

    function toggleTelegramFields() {
        if (!document.getElementById('cloud_upload').checked) return;
        const telegramUploadCheckbox = document.getElementById('backup_telegram_upload');
        const telegramFields = document.getElementById('telegram_fields');
        telegramFields.style.display = telegramUploadCheckbox.checked ? 'block' : 'none';
    }

    toggleBackupFrequency();
    toggleRetainCount();
    toggleCloudFields(); // Call this instead of individual toggles

    (function () {
        var form = document.getElementById('form-backup-create-full');
        var btn = document.getElementById('btn-backup-create-full');
        var statusEl = document.getElementById('backup-full-status');
        if (!form || !btn) {
            return;
        }

        function setStatus(text, isError) {
            if (!statusEl) {
                return;
            }
            statusEl.style.display = text ? 'block' : 'none';
            statusEl.className = isError ? 'text-danger' : 'text-muted';
            statusEl.textContent = text || '';
        }

        function pollJob(jobId, attempts) {
            attempts = attempts || 0;
            if (attempts > 180) {
                btn.disabled = false;
                btn.textContent = 'Creer un backup complet';
                setStatus('Delai depasse. Actualisez la page pour verifier si le package est apparu.', true);
                return;
            }
            var url = '{$_url}plugin/backup_create_full_status&ajax=1&job_id=' + encodeURIComponent(jobId);
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(function (data) {
                var st = (data && data.status) ? data.status : 'unknown';
                if (st === 'completed') {
                    var tg = data.telegram || {};
                    var tgMsg = '';
                    if (tg.ok) {
                        tgMsg = ' + copie Telegram envoyee';
                    } else if (tg.reason === 'file_too_large') {
                        tgMsg = ' (Telegram ignore: fichier trop volumineux)';
                    } else if (tg.reason === 'telegram_upload_disabled' || tg.reason === 'telegram_not_configured') {
                        tgMsg = '';
                    } else if (tg.error) {
                        tgMsg = ' (envoi Telegram echoue)';
                    }
                    setStatus('Backup cree: ' + (data.file || '') + tgMsg + ' — rechargement…', false);
                    window.location.href = '{$_url}plugin/backup_list';
                    return;
                }
                if (st === 'failed') {
                    btn.disabled = false;
                    btn.textContent = 'Creer un backup complet';
                    setStatus('Echec: ' + (data.error || 'erreur inconnue'), true);
                    return;
                }
                setStatus('Sauvegarde en cours… (' + st + ')', false);
                setTimeout(function () { pollJob(jobId, attempts + 1); }, 1500);
            }).catch(function () {
                setTimeout(function () { pollJob(jobId, attempts + 1); }, 2000);
            });
        }

        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            if (btn.disabled) {
                return;
            }
            btn.disabled = true;
            btn.textContent = 'Creation en cours…';
            setStatus('Demarrage de la sauvegarde complete…', false);

            var body = new FormData(form);
            body.set('ajax', '1');

            fetch(form.action, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json().then(function (data) { return { okHttp: r.ok, data: data }; }); })
            .then(function (res) {
                var data = res.data || {};
                if (!data.ok) {
                    throw new Error(data.error || 'Echec demarrage sauvegarde');
                }
                if (data.file && !data.async) {
                    setStatus('Backup cree: ' + data.file + ' — rechargement…', false);
                    window.location.href = '{$_url}plugin/backup_list';
                    return;
                }
                if (!data.job_id) {
                    throw new Error('job_id manquant');
                }
                setStatus(data.message || 'Sauvegarde en cours…', false);
                pollJob(data.job_id, 0);
            }).catch(function (err) {
                btn.disabled = false;
                btn.textContent = 'Creer un backup complet';
                setStatus(err && err.message ? err.message : 'Erreur reseau', true);
            });
        });
    })();
</script>
{include file="sections/footer.tpl"}