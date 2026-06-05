<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{Lang::T('Login')} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/inter/inter.css">
    <style>
        {literal}
        :root {
            --bg: #030712;
            --text: #f8fafc;
            --muted: #94a3b8;
            --line: rgba(148, 163, 184, 0.2);
            --brand: #22c55e;
            --brand2: #06b6d4;
        }
        html, body {
            width: 100%; height: 100%; margin: 0; padding: 0; overflow: hidden;
            font-family: Inter, system-ui, Arial, sans-serif;
            background: var(--bg); color: var(--text);
        }
        .admin-login {
            width: 100vw; height: 100vh; display: grid;
            grid-template-columns: 55% 45%;
            background: var(--bg);
        }
        .admin-left {
            position: relative; overflow: hidden;
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(34, 197, 94, 0.14), transparent),
                radial-gradient(circle at 90% 10%, rgba(6, 182, 212, 0.1), transparent 40%),
                var(--bg);
            color: var(--text);
            display: flex; align-items: center; justify-content: center;
            border-right: 1px solid var(--line);
        }
        .admin-left:before {
            content: ""; position: absolute; width: 300px; height: 300px;
            border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 50%;
            right: -66px; top: -88px;
        }
        .admin-left:after {
            content: ""; position: absolute; width: 260px; height: 260px;
            border-radius: 50%; left: -108px; bottom: -102px;
            background: rgba(6, 182, 212, 0.06);
        }
        .orb { position: absolute; border-radius: 50%; }
        .orb.one { width: 32px; height: 32px; left: 42px; top: 132px; background: rgba(34, 197, 94, 0.2); }
        .orb.two { width: 42px; height: 42px; right: 132px; top: 310px; background: rgba(6, 182, 212, 0.22); }
        .orb.three { width: 24px; height: 24px; left: 72px; bottom: 210px; background: rgba(34, 197, 94, 0.15); }
        .orb.four { width: 28px; height: 28px; right: 68px; bottom: 150px; background: rgba(6, 182, 212, 0.24); }
        .marketing { position: relative; z-index: 1; width: 420px; margin-top: -10px; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 38px; }
        .brand-icon {
            width: 38px; height: 38px; border-radius: 11px;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            display: grid; place-items: center; color: #021014; font-weight: 900;
        }
        .brand strong { display: block; font-size: 17px; line-height: 16px; color: #fff; }
        .brand strong span {
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .brand small { font-size: 10px; color: var(--muted); }
        .headline {
            font-size: 31px; line-height: 1.24; margin: 0 0 20px;
            font-weight: 900; letter-spacing: -.7px; color: #fff;
        }
        .headline em {
            font-style: normal;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .lead { color: var(--muted); font-size: 13px; line-height: 1.85; max-width: 395px; margin: 0 0 34px; }
        .feature {
            display: flex; align-items: center; gap: 15px;
            color: #cbd5e1; font-size: 12px; margin: 17px 0;
        }
        .feature i {
            width: 30px; height: 30px; border-radius: 9px;
            background: rgba(34, 197, 94, 0.12); color: var(--brand);
            display: flex; align-items: center; justify-content: center; flex: 0 0 30px;
        }
        .admin-right {
            display: flex; align-items: center; justify-content: center;
            background: rgba(15, 23, 42, 0.45);
        }
        .login-box {
            width: 360px; margin-top: -8px;
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid var(--line);
            border-radius: 20px; padding: 32px 28px;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
        }
        .login-box h1 { font-size: 21px; color: #fff; margin: 0 0 6px; font-weight: 900; }
        .login-box .sub { font-size: 13px; color: var(--muted); margin: 0 0 28px; }
        .form-field { margin-bottom: 20px; }
        .form-field label { display: block; color: #e2e8f0; font-size: 11px; font-weight: 900; margin-bottom: 8px; }
        .input-wrap {
            height: 46px; border: 1px solid var(--line); border-radius: 9px;
            display: flex; align-items: center; gap: 10px; padding: 0 12px;
            background: rgba(255, 255, 255, 0.05);
        }
        .input-wrap:focus-within {
            border-color: rgba(34, 197, 94, 0.55);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
        }
        .input-wrap i { color: var(--muted); width: 14px; text-align: center; }
        .input-wrap input {
            border: 0; outline: 0; width: 100%; font-size: 13px;
            color: #fff; background: transparent;
        }
        .input-wrap input::placeholder { color: #64748b; }
        .signin {
            width: 100%; height: 45px; border: 0; border-radius: 999px;
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            color: #021014; font-size: 13px; font-weight: 900; cursor: pointer;
        }
        .signin:hover { filter: brightness(1.06); }
        .back-link {
            display: block; text-align: center; margin-top: 16px;
            color: var(--brand2); text-decoration: none; font-size: 12px; font-weight: 900;
        }
        .back-link:hover { color: #67e8f9; }
        .notify { margin-bottom: 16px; }
        @media (max-width: 900px) {
            html, body { overflow: auto; }
            .admin-login { height: auto; min-height: 100vh; grid-template-columns: 1fr; }
            .admin-left { min-height: 430px; padding: 42px; }
            .marketing { width: 100%; max-width: 420px; }
            .admin-right { min-height: 520px; padding: 36px 22px; }
            .headline { font-size: 28px; }
            .login-box { width: 100%; max-width: 360px; }
        }
        @media (max-width: 520px) {
            .admin-left { display: none; }
            .admin-right { min-height: 100vh; }
            .login-box { max-width: 100%; }
        }
        {/literal}
    </style>
</head>
<body>
    <main class="admin-login">
        <section class="admin-left">
            <span class="orb one"></span>
            <span class="orb two"></span>
            <span class="orb three"></span>
            <span class="orb four"></span>
            <div class="marketing">
                <div class="brand">
                    <span class="brand-icon"><i class="fa fa-bolt"></i></span>
                    <div>
                        <strong>Isp<span>Dyrsia</span></strong>
                        <small>Admin</small>
                    </div>
                </div>
                <h2 class="headline">Your complete <em>ISP management</em> solution</h2>
                <p class="lead">Everything you need to run your internet service business — from billing to network monitoring.</p>
                <div class="feature"><i class="fa fa-wifi"></i><span>Manage your ISP network and packages</span></div>
                <div class="feature"><i class="fa fa-users"></i><span>Track customers, staff, and payments</span></div>
                <div class="feature"><i class="fa fa-money"></i><span>Full billing and invoice management</span></div>
            </div>
        </section>
        <section class="admin-right">
            <div class="login-box">
                <h1>Welcome back{if $tenant_login} — {$tenant_login.business_name|escape}{/if}</h1>
                <p class="sub">{Lang::T('Enter Admin Area')}</p>
                {if isset($notify)}
                    <div class="notify">{$notify}</div>
                {/if}
                <form action="{Text::url('admin/post')}" method="post">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <div class="form-field">
                        <label>{Lang::T('Username')}</label>
                        <div class="input-wrap">
                            <i class="fa fa-user-o"></i>
                            <input type="text" required name="username" placeholder="{Lang::T('Username')}">
                        </div>
                    </div>
                    <div class="form-field">
                        <label>{Lang::T('Password')}</label>
                        <div class="input-wrap">
                            <i class="fa fa-lock"></i>
                            <input type="password" required name="password" placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" class="signin">{Lang::T('Login')}</button>
                    <a href="{Text::url('login')}" class="back-link">{Lang::T('Go Back')}</a>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
