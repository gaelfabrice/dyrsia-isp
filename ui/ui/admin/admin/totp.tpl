<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{Lang::T('SuperAdmin_2FA_title')} - {$_c['CompanyName']}</title>
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <style>
        {literal}
        :root { --bg: #030712; --text: #f8fafc; --muted: #94a3b8; --line: rgba(148,163,184,.2); --brand: #22c55e; --brand2: #06b6d4; }
        html, body { margin: 0; min-height: 100%; font-family: system-ui, sans-serif; background: var(--bg); color: var(--text); }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .box {
            width: 100%; max-width: 420px; background: rgba(15,23,42,.65); border: 1px solid var(--line);
            border-radius: 20px; padding: 32px 28px; box-shadow: 0 24px 48px rgba(0,0,0,.35);
        }
        h1 { font-size: 21px; margin: 0 0 8px; font-weight: 900; }
        .sub { color: var(--muted); font-size: 13px; margin: 0 0 24px; line-height: 1.5; }
        .qr { text-align: center; margin: 16px 0; min-height: 216px; display: flex; align-items: center; justify-content: center; }
        .qr img { background: #fff; padding: 8px; border-radius: 12px; width: 200px; height: 200px; object-fit: contain; }
        .secret { font-family: monospace; font-size: 12px; word-break: break-all; background: rgba(0,0,0,.35); padding: 10px; border-radius: 8px; margin: 12px 0; user-select: all; }
        .form-field { margin-bottom: 18px; }
        .form-field label { display: block; font-size: 11px; font-weight: 800; margin-bottom: 8px; color: #e2e8f0; }
        .input-wrap {
            height: 46px; border: 1px solid var(--line); border-radius: 9px; display: flex; align-items: center;
            padding: 0 12px; background: rgba(255,255,255,.05);
        }
        .input-wrap input { border: 0; outline: 0; width: 100%; font-size: 20px; letter-spacing: 0.2em; text-align: center;
            color: #fff; background: transparent; }
        .signin {
            width: 100%; height: 45px; border: 0; border-radius: 999px; cursor: pointer; font-weight: 900;
            background: linear-gradient(135deg, var(--brand), var(--brand2)); color: #021014;
        }
        .back-link { display: block; text-align: center; margin-top: 16px; color: var(--brand2); font-size: 12px; text-decoration: none; }
        .alert-flash { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.35); color: #fecaca; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        {/literal}
    </style>
</head>
<body>
<div class="wrap">
    <div class="box">
        {if $totp_mode eq 'setup'}
            <h1>{Lang::T('SuperAdmin_2FA_setup_title')}</h1>
            <p class="sub">{Lang::T('SuperAdmin_2FA_setup_help')}</p>
            <div class="qr">
                <img src="{$totp_qr_url|escape:'html'}" width="200" height="200" alt="QR code Authenticator" loading="eager">
            </div>
            <p class="sub">{Lang::T('SuperAdmin_2FA_manual_secret')}</p>
            <div class="secret" id="totp-secret">{$totp_secret|escape}</div>
        {else}
            <h1>{Lang::T('SuperAdmin_2FA_verify_title')}</h1>
            <p class="sub">{Lang::T('SuperAdmin_2FA_verify_help')}</p>
        {/if}
        {if isset($notify) && $notify ne ''}
            <div class="alert-flash">{$notify|escape}</div>
        {/if}
        <form action="{Text::url('admin/2fa-post')}" method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <div class="form-field">
                <label>{Lang::T('SuperAdmin_2FA_code_label')}</label>
                <div class="input-wrap">
                    <input type="text" name="totp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required placeholder="000000" autofocus>
                </div>
            </div>
            <button type="submit" class="signin">{Lang::T('SuperAdmin_2FA_continue')}</button>
        </form>
        <a class="back-link" href="{Text::url('admin')}">{Lang::T('Go_Back')}</a>
    </div>
</div>
</body>
</html>
