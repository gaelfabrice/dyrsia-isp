<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{Lang::T('Create Account')} - {$_c['CompanyName']}</title>
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <style>
        {literal}
        body{margin:0;min-height:100vh;background:radial-gradient(circle at 20% 10%,rgba(34,197,94,.16),transparent 28%),#050816;color:#e5e7eb;font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;padding:30px}.card{width:min(760px,100%);background:rgba(10,16,31,.88);border:1px solid rgba(255,255,255,.12);border-radius:22px;padding:28px;box-shadow:0 30px 90px rgba(0,0,0,.45)}h2{text-align:center;margin-top:0}.form-control{background:#0b1220;border-color:#1f2937;color:#fff}.form-control:focus{border-color:#22c55e;box-shadow:none}.btn-primary{background:linear-gradient(135deg,#22c55e,#06b6d4);border:0;color:#021014;font-weight:700}.muted{color:#94a3b8}.section{font-size:11px;text-transform:uppercase;letter-spacing:1.4px;color:#94a3b8;margin:18px 0 10px}.back{color:#86efac}
        {/literal}
    </style>
</head>
<body>
<div class="card">
    <h2>{Lang::T('Create Your ISP Account')}</h2>
    <p class="text-center muted">{Lang::T('Your request will be approved manually by the super administrator.')}</p>
    {if isset($notify)}<div class="alert alert-danger">{$notify}</div>{/if}
    <form method="post" action="{Text::url('customer_requests/submit')}">
        <div class="section">{Lang::T('Business Information')}</div>
        <div class="row">
            <div class="col-sm-6 form-group"><label>{Lang::T('Nom')}</label><input class="form-control" name="last_name" required></div>
            <div class="col-sm-6 form-group"><label>{Lang::T('Prénom')}</label><input class="form-control" name="first_name" required></div>
            <div class="col-sm-6 form-group"><label>{Lang::T('Ville')}</label><input class="form-control" name="city" required></div>
            <div class="col-sm-6 form-group"><label>{Lang::T('Pays')}</label><input class="form-control" name="country" required></div>
            <div class="col-sm-12 form-group"><label>{Lang::T('Adresse')}</label><input class="form-control" name="address" required></div>
            <div class="col-sm-6 form-group"><label>{Lang::T('Numéro de téléphone')}</label><input class="form-control" name="phone" required></div>
            <div class="col-sm-6 form-group"><label>{Lang::T('Nom de votre instance')}</label><input class="form-control" name="instance_name" required></div>
        </div>
        <div class="section">{Lang::T('Login Credentials')}</div>
        <div class="row">
            <div class="col-sm-12 form-group"><label>{Lang::T('Email')}</label><input type="email" class="form-control" name="email" required></div>
            <div class="col-sm-6 form-group"><label>{Lang::T('Password')}</label><input type="password" class="form-control" name="password" required minlength="6"></div>
            <div class="col-sm-6 form-group"><label>{Lang::T('Confirm Password')}</label><input type="password" class="form-control" name="confirm_password" required minlength="6"></div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">{Lang::T('Create Account & Start Free Trial')}</button>
    </form>
    <p class="text-center" style="margin-top:15px;"><a class="back" href="{Text::url('portal')}">{Lang::T('Already have an account? Log in')}</a></p>
</div>
</body>
</html>
