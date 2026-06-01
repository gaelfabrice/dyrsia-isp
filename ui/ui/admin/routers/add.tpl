{include file="sections/header.tpl"}
<!-- routers-add -->

<style>
{literal}
    .router-add-wrap{max-width:980px;margin:0 auto 30px}.router-hero{background:linear-gradient(135deg,#4f46e5,#8b35f6);color:#fff;border-radius:18px 18px 0 0;padding:24px 28px;box-shadow:0 18px 40px rgba(79,70,229,.25)}.router-hero h3{margin:0;font-weight:800}.router-hero p{margin:6px 0 0;color:rgba(255,255,255,.78)}.router-card{background:#fff;border-radius:0 0 18px 18px;padding:28px;box-shadow:0 18px 40px rgba(15,23,42,.12)}.router-section{border:2px solid #e5e7eb;border-radius:16px;padding:22px;margin-bottom:18px}.router-section.ident{background:#f8f7ff;border-color:#d9d6ff}.router-section.net{background:#effdf5;border-color:#a7f3d0}.router-section.auth{background:#fff7ed;border-color:#fed7aa}.router-section-title{display:flex;align-items:center;gap:12px;margin-bottom:18px;font-weight:800;letter-spacing:.4px}.router-section-title i{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff}.ident .router-section-title{color:#4338ca}.ident .router-section-title i{background:#6366f1}.net .router-section-title{color:#047857}.net .router-section-title i{background:#059669}.auth .router-section-title{color:#9a3412}.auth .router-section-title i{background:#d97706}.router-field label{font-size:12px;text-transform:uppercase;color:#64748b;font-weight:800}.router-field .form-control{height:54px;border-radius:12px;border:2px solid #e5e7eb;box-shadow:none;font-size:15px}.router-field textarea.form-control{height:90px}.router-field .form-control:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}.router-status-box{display:flex;align-items:center;gap:10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 16px;color:#1d4ed8;margin-bottom:18px}.router-status-box.success{background:#ecfdf5;border-color:#86efac;color:#047857}.router-status-box.error{background:#fef2f2;border-color:#fecaca;color:#b91c1c}.router-actions{display:flex;gap:14px}.router-actions .btn{height:54px;border-radius:12px;font-weight:800}.router-actions .btn-primary{background:linear-gradient(135deg,#4f46e5,#8b35f6);border:0}.router-actions .btn-primary:disabled{background:#cbd5e1;border:0;cursor:not-allowed}.router-toggle label{margin-right:18px}.router-test-btn{height:54px;border-radius:12px;font-weight:800;background:#0f766e;color:#fff;border:0;margin-top:24px}.router-test-btn:hover,.router-test-btn:focus{color:#fff;background:#0d9488}
{/literal}
</style>

<div class="router-add-wrap">
    <div class="router-hero">
        <h3><i class="fa fa-globe"></i> {Lang::T('Nouveau Routeur MikroTik')}</h3>
        <p>{Lang::T('Ajoutez un routeur à votre parc et vérifiez son statut réel')}</p>
    </div>
    <div class="router-card">
        <form method="post" role="form" action="{Text::url('')}routers/add-post">
            <div class="router-section ident">
                <div class="router-section-title"><i class="fa fa-tag"></i> {Lang::T('Identification')}</div>
                <div class="row">
                    <div class="col-md-6 router-field">
                        <label>{Lang::T('Nom du routeur')} *</label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="32" placeholder="ex: Routeur Akwa Nord" required>
                    </div>
                    <div class="col-md-6 router-field">
                        <label>{Lang::T('Zone / Description')}</label>
                        <textarea class="form-control" id="description" name="description" placeholder="ex: Quartier Centre-ville"></textarea>
                    </div>
                </div>
            </div>

            <div class="router-section net">
                <div class="router-section-title"><i class="fa fa-sitemap"></i> {Lang::T('Connexion Réseau')}</div>
                <div class="row">
                    <div class="col-md-6 router-field">
                        <label>{Lang::T('Adresse IP')} *</label>
                        <input type="text" placeholder="192.168.88.1" class="form-control" id="ip_address" name="ip_address" required>
                    </div>
                    <div class="col-md-6 router-field">
                        <label>{Lang::T('Port API')}</label>
                        <input type="number" class="form-control" id="api_port" name="api_port" value="8728" required>
                    </div>
                </div>
            </div>

            <div class="router-section auth">
                <div class="router-section-title"><i class="fa fa-key"></i> {Lang::T('Authentification MikroTik')}</div>
                <div class="row">
                    <div class="col-md-6 router-field">
                        <label>{Lang::T('Utilisateur')} *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="col-md-6 router-field">
                        <label>{Lang::T('Mot de passe')}</label>
                        <input type="password" class="form-control" id="password" name="password" onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                    </div>
                </div>
            </div>

            <div class="router-status-box" id="router-test-message">
                <i class="fa fa-info-circle"></i>
                <span>{Lang::T('Testez d’abord la connexion. Le bouton Créer le routeur sera activé uniquement si le test réussit.')}</span>
            </div>

            <div class="form-group router-toggle">
                <label><input type="radio" checked name="enabled" value="1"> {Lang::T('Enable')}</label>
                <label><input type="radio" name="enabled" value="0"> {Lang::T('Disable')}</label>
                <label><input type="checkbox" checked name="testIt" value="yes"> {Lang::T('Test Connection')}</label>
            </div>

            <div class="router-actions">
                <a href="{Text::url('')}routers/list" class="btn btn-default btn-lg btn-block">{Lang::T('Cancel')}</a>
                <button type="button" class="btn router-test-btn btn-lg btn-block" id="test-router-connection">
                    <i class="fa fa-shield"></i> {Lang::T('Tester la connexion')}
                </button>
                <button class="btn btn-primary btn-lg btn-block" id="create-router-button" disabled onclick="return ask(this, '{Lang::T("Continue the process of adding Routers?")}')" type="submit">
                    <i class="fa fa-plus"></i> {Lang::T('Créer le routeur')}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
{literal}
    (function () {
        var testPassed = false;
        var message = document.getElementById('router-test-message');
        var createButton = document.getElementById('create-router-button');
        var testButton = document.getElementById('test-router-connection');

        function setMessage(type, icon, text) {
            message.classList.remove('success', 'error');
            if (type) {
                message.classList.add(type);
            }
            message.innerHTML = '<i class="fa ' + icon + '"></i><span>' + text + '</span>';
        }

        function resetTest() {
            testPassed = false;
            createButton.disabled = true;
            message.classList.remove('success', 'error');
            message.innerHTML = '<i class="fa fa-info-circle"></i><span>Testez d’abord la connexion. Le bouton Créer le routeur sera activé uniquement si le test réussit.</span>';
        }

        ['ip_address', 'api_port', 'username', 'password'].forEach(function (fieldId) {
            var field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', resetTest);
                field.addEventListener('change', resetTest);
            }
        });

        testButton.addEventListener('click', function () {
            testPassed = false;
            createButton.disabled = true;
            testButton.disabled = true;
            testButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Test en cours...';
            setMessage('', 'fa-spinner fa-spin', 'Connexion au routeur en cours...');

            var data = new FormData();
            data.append('ip_address', document.getElementById('ip_address').value);
            data.append('api_port', document.getElementById('api_port').value);
            data.append('username', document.getElementById('username').value);
            data.append('password', document.getElementById('password').value);

            fetch('{/literal}{Text::url('routers/test-connection')}{literal}', {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (response.success) {
                        testPassed = true;
                        createButton.disabled = false;
                        setMessage('success', 'fa-check-circle', response.message + ' — ' + response.ip_address);
                    } else {
                        setMessage('error', 'fa-times-circle', response.message);
                    }
                })
                .catch(function () {
                    setMessage('error', 'fa-times-circle', 'Erreur pendant le test de connexion.');
                })
                .finally(function () {
                    testButton.disabled = false;
                    testButton.innerHTML = '<i class="fa fa-shield"></i> Tester la connexion';
                });
        });

        document.querySelector('.router-card form').addEventListener('submit', function (event) {
            if (!testPassed) {
                event.preventDefault();
            }
        });
    })();
{/literal}
</script>

{include file="sections/footer.tpl"}
