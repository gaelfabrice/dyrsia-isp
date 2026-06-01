<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{Lang::T('Verify Your Email')}</title>
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <style>
        {literal}
        body{margin:0;min-height:100vh;background:radial-gradient(circle at 75% 25%,rgba(6,182,212,.14),transparent 28%),radial-gradient(circle at 20% 12%,rgba(34,197,94,.16),transparent 30%),#050816;color:#e5e7eb;font-family:Inter,Arial,sans-serif;display:flex;align-items:center;justify-content:center;padding:30px}.card{width:min(520px,100%);text-align:center;background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.035));border:1px solid rgba(255,255,255,.12);border-radius:26px;padding:36px;box-shadow:0 30px 100px rgba(0,0,0,.45);backdrop-filter:blur(18px)}.mail{width:68px;height:68px;border-radius:20px;display:grid;place-items:center;margin:0 auto 22px;background:linear-gradient(135deg,#22c55e,#06b6d4);color:#021014;font-size:32px;font-weight:900}h2{margin:0 0 10px;font-size:24px}.otp{font-size:30px;letter-spacing:12px;text-align:center;height:58px;background:rgba(2,6,23,.62);border:1px solid rgba(148,163,184,.18);color:#fff;border-radius:16px;box-shadow:none}.otp:focus{border-color:rgba(34,197,94,.68);box-shadow:0 0 0 3px rgba(34,197,94,.1);outline:0}.btn-primary{height:46px;border-radius:999px;background:linear-gradient(135deg,#22c55e,#06b6d4);border:0;color:#021014;font-weight:900}.muted{color:#94a3b8;line-height:1.6}.card hr{border:0;border-top:1px solid rgba(255,255,255,.1);margin:22px 0}
        {/literal}
    </style>
</head>
<body>
<div class="card">
    <div class="mail">✉</div>
    <h2>{Lang::T('Verify Your Email')}</h2>
    <p class="muted">{Lang::T('We sent a 6-digit verification code to')}<br><strong>{$request['email']}</strong></p>
    <form method="post" action="{Text::url('customer_requests/verify/')}{$request['id']}">
        <div class="form-group"><input class="form-control otp" name="otp_code" maxlength="6" required autofocus></div>
        <button class="btn btn-primary btn-block" type="submit">{Lang::T('Verify Email')}</button>
    </form>
    <hr>
    <p class="muted">{Lang::T('The code expires in 15 minutes.')}</p>
</div>
</body>
</html>
