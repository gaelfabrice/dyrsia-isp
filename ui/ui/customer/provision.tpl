{assign var=companyName value=$_c['CompanyName']|default:'DYRSIA'}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$_title} — {$companyName}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        {literal}
        :root {
            --bg: #0b1220;
            --card: #111827;
            --text: #f8fafc;
            --muted: #94a3b8;
            --line: rgba(148, 163, 184, 0.22);
            --brand: #2563eb;
            --brand-hover: #1d4ed8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh;
            font-family: Inter, system-ui, sans-serif;
            background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(37, 99, 235, 0.18), transparent), var(--bg);
            color: var(--text);
            display: flex; align-items: center; justify-content: center;
            padding: 24px 16px;
        }
        .wrap { width: min(520px, 100%); }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 32px 28px 28px;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
        }
        h1 { margin: 0 0 8px; font-size: 26px; font-weight: 800; text-align: center; }
        .sub { margin: 0 0 28px; text-align: center; color: var(--muted); font-size: 14px; line-height: 1.5; }
        label {
            display: block; font-size: 11px; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 8px;
        }
        .field { margin-bottom: 18px; }
        input[type="text"], input[type="email"] {
            width: 100%; padding: 12px 14px;
            border-radius: 10px; border: 1px solid var(--line);
            background: #0f172a; color: var(--text); font-size: 15px;
        }
        input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25); }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 520px) { .row2 { grid-template-columns: 1fr; } }
        .subdomain-wrap { position: relative; }
        .subdomain-wrap input { padding-right: 72px; }
        .suffix {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: var(--muted); font-size: 14px; font-weight: 600; pointer-events: none;
        }
        .btn {
            width: 100%; margin-top: 8px; padding: 14px 20px;
            border: 0; border-radius: 10px; font-size: 13px; font-weight: 800;
            letter-spacing: 0.04em; text-transform: uppercase;
            background: var(--brand); color: #fff; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn:hover:not(:disabled) { background: var(--brand-hover); }
        .btn:disabled { opacity: 0.85; cursor: wait; }
        .status { text-align: center; margin-top: 14px; font-size: 13px; color: #60a5fa; min-height: 20px; }
        .back { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: var(--muted); }
        .back a { color: #93c5fd; }
        .alert {
            padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-size: 14px;
        }
        .alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #fecaca; }
        .alert-success { background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.35); color: #bbf7d0; }
        .hp-field { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
        {/literal}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Provision Your Instance</h1>
            <p class="sub">Create your dedicated database and admin panel in under 60 seconds.</p>

            {if $notify}
                <div class="alert alert-{$notify_t|default:'danger'}">{$notify}</div>
            {/if}

            <form method="post" action="{Text::url('provision/submit')}" id="provision-form" autocomplete="on">
                <div class="hp-field" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="field">
                    <label for="business_name">ISP / Business Name</label>
                    <input type="text" id="business_name" name="business_name" placeholder="e.g. Mombasa Fiber" required maxlength="150">
                </div>
                <div class="row2">
                    <div class="field">
                        <label for="subdomain">Desired Subdomain</label>
                        <div class="subdomain-wrap">
                            <input type="text" id="subdomain" name="subdomain" placeholder="wizfiber" pattern="[a-zA-Z0-9][a-zA-Z0-9-]*" required maxlength="63">
                            <span class="suffix">{$tenant_domain_suffix}</span>
                        </div>
                    </div>
                    <div class="field">
                        <label for="email">Admin Email</label>
                        <input type="email" id="email" name="email" placeholder="admin@isp.com" required maxlength="150">
                    </div>
                </div>
                <button type="submit" class="btn" id="provision-btn">
                    <span class="btn-label">Create Environment →</span>
                </button>
                <p class="status" id="provision-status" aria-live="polite"></p>
            </form>
        </div>
        <p class="back"><a href="{Text::url('welcome')}"><i class="fa fa-arrow-left"></i> Back to home</a></p>
    </div>
    <script>
    {literal}
    (function () {
        var form = document.getElementById('provision-form');
        var btn = document.getElementById('provision-btn');
        var status = document.getElementById('provision-status');
        var sub = document.getElementById('subdomain');
        if (btn) {
            btn.disabled = false;
        }
        if (status) {
            status.textContent = '';
        }
        if (sub) {
            sub.addEventListener('input', function () {
                this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
            });
        }
        if (form && btn) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                btn.disabled = true;
                var label = btn.querySelector('.btn-label');
                if (label) label.textContent = 'Provisioning Environment… ◯';
                if (status) status.textContent = 'Cloning Database & Configuring Security…';
                var data = new FormData(form);
                data.append('ajax', '1');
                fetch(form.action, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json();
                }).then(function (res) {
                    if (res.status === 'success') {
                        if (status) {
                            status.innerHTML = 'Instance created. Username: <strong>' + res.username + '</strong> Password: <strong>' + res.password + '</strong><br><a style="color:#93c5fd" href="' + res.redirect + '">Open dashboard</a>';
                        }
                        window.location.href = res.redirect;
                        return;
                    }
                    btn.disabled = false;
                    if (label) label.textContent = 'Create Environment →';
                    if (status) status.textContent = res.message || 'Provisioning failed.';
                }).catch(function () {
                    btn.disabled = false;
                    if (label) label.textContent = 'Create Environment →';
                    if (status) status.textContent = 'Provisioning failed. Please check the server response and try again.';
                });
            });
        }
    })();
    {/literal}
    </script>
</body>
</html>
