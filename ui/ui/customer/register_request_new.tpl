<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{Lang::T('Create Account')} - {$_c['CompanyName']}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{$app_url}/ui/ui/images/favicon.png" />
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="{$app_url}/ui/ui/images/apple-touch-icon.png" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/font-awesome.min.css">
    <style>
        {literal}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 16% 12%,rgba(34,197,94,.18),transparent 31%),radial-gradient(circle at 86% 34%,rgba(6,182,212,.16),transparent 30%),#050816;color:#f8fafc;font-family:Inter,Arial,sans-serif;padding:30px;display:flex;align-items:center;justify-content:center}.wrap{width:100%;max-width:430px}.brand{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:22px;font-size:20px;font-weight:900}.brand-icon{width:36px;height:36px;border-radius:12px;background:linear-gradient(135deg,#22c55e,#06b6d4);display:grid;place-items:center;box-shadow:0 0 35px rgba(34,197,94,.35);color:#021014}.card{background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.035));border:1px solid rgba(255,255,255,.12);border-radius:26px;padding:26px;box-shadow:0 30px 100px rgba(0,0,0,.45);backdrop-filter:blur(18px)}h1{font-size:22px;text-align:center;margin:0 0 8px;letter-spacing:-.7px}.subtitle{text-align:center;color:#94a3b8;font-size:13px;line-height:1.55;margin:0 0 24px}.section{font-size:10px;text-transform:uppercase;letter-spacing:1.4px;color:#94a3b8;margin:18px 0 10px;display:flex;align-items:center;gap:10px}.section:after{content:"";height:1px;background:rgba(255,255,255,.1);flex:1}.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.field{margin-bottom:12px}label{display:block;font-size:12px;color:#cbd5e1;margin-bottom:7px;font-weight:800}.required:after{content:" *";color:#86efac}.input{width:100%;height:43px;border:1px solid rgba(148,163,184,.18);background:rgba(2,6,23,.62);color:#fff;border-radius:14px;padding:10px 12px;outline:none;font-size:13px;box-shadow:none}.input::placeholder{color:#64748b}.input:focus{border-color:rgba(34,197,94,.68);box-shadow:0 0 0 3px rgba(34,197,94,.1)}.password-wrap{position:relative}.password-wrap .input{padding-right:38px}.eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);color:#64748b;font-size:13px}.btn{width:100%;height:46px;border:0;border-radius:999px;background:linear-gradient(135deg,#22c55e,#06b6d4);color:#021014;font-weight:900;font-size:14px;cursor:pointer;margin-top:4px;box-shadow:0 18px 45px rgba(34,197,94,.18)}.btn:hover{filter:brightness(1.06)}.login{text-align:center;margin:16px 0 0;color:#94a3b8;font-size:13px}.login a{color:#86efac;text-decoration:none;font-weight:900}.alert{border-radius:14px;padding:11px 12px;margin-bottom:16px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.24);color:#fecaca;font-size:13px}.note{display:flex;gap:9px;align-items:flex-start;margin-top:14px;color:#94a3b8;font-size:12px;line-height:1.45}.note i{color:#22c55e;margin-top:2px}@media(max-width:520px){body{padding:18px}.card{padding:20px}.row{grid-template-columns:1fr}.wrap{max-width:100%}}
        {/literal}
    </style>
</head>
<body>
<main class="wrap">
    <div class="brand">
        <span class="brand-icon"><i class="fa fa-bolt"></i></span>
        <span>{$_c['CompanyName']|default:'DYRSIA'}</span>
    </div>
    <section class="card">
        <h1>{Lang::T('Create Your ISP Account')}</h1>
        <p class="subtitle">{Lang::T('Set up your ISP billing workspace and start your free trial after approval.')}</p>
        {if isset($notify)}<div class="alert">{$notify}</div>{/if}
        <form method="post" action="{Text::url('customer_requests/submit')}">
            <div class="section">{Lang::T('Business Information')}</div>
            <div class="field">
                <label class="required">{Lang::T('Business Name')}</label>
                <input class="input" name="instance_name" placeholder="e.g., Karl's Internet Services" required>
            </div>
            <div class="row">
                <div class="field">
                    <label class="required">{Lang::T('Owner Name')}</label>
                    <input class="input" name="first_name" placeholder="Your full name" required>
                    <input type="hidden" name="last_name" value="-">
                </div>
                <div class="field">
                    <label>{Lang::T('Phone')}</label>
                    <input class="input" name="phone" placeholder="09171234567">
                </div>
            </div>
            <div class="field">
                <label>{Lang::T('Business Address')}</label>
                <input class="input" name="address" placeholder="City, Province">
                <input type="hidden" name="city" value="-">
                <input type="hidden" name="country" value="-">
            </div>
            <div class="section">{Lang::T('Login Credentials')}</div>
            <div class="field">
                <label class="required">{Lang::T('Email Address')}</label>
                <input type="email" class="input" name="email" placeholder="you@example.com" required>
            </div>
            <div class="field">
                <label class="required">{Lang::T('Password')}</label>
                <div class="password-wrap">
                    <input type="password" class="input" name="password" placeholder="At least 6 characters" required minlength="6">
                    <span class="eye"><i class="fa fa-eye"></i></span>
                </div>
            </div>
            <div class="field">
                <label class="required">{Lang::T('Confirm Password')}</label>
                <input type="password" class="input" name="confirm_password" placeholder="Re-enter your password" required minlength="6">
            </div>
            <button class="btn" type="submit">{Lang::T('Create Account & Start Free Trial')}</button>
        </form>
        <div class="note"><i class="fa fa-shield"></i><span>{Lang::T('Your request will be reviewed manually by the administrator before activation.')}</span></div>
        <p class="login">{Lang::T('Already have an account?')} <a href="{Text::url('portal')}">{Lang::T('Log in')}</a></p>
    </section>
</main>
</body>
</html>
