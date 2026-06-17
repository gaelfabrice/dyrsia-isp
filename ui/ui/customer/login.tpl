<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{$app_url}/ui/ui/images/favicon.png" />
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="{$app_url}/ui/ui/images/apple-touch-icon.png" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <script>
        var appUrl = '{$app_url}';
    </script>
</head>
<body>

<style>
    {literal}
    html,body{width:100%!important;height:100%!important;margin:0!important;padding:0!important;background:#fff!important;overflow:hidden!important;font-family:Inter,Arial,sans-serif!important}.container,.container-fluid,.wrapper,.content-wrapper,.main-footer,.main-header,header,nav.navbar{max-width:none!important;width:auto!important;margin:0!important;padding:0!important}.container>h1,.container>h2,.container>h3,body>h1,body>h2,body>h3{display:none!important}.portal-split{width:100vw;height:100vh;display:grid;grid-template-columns:55% 45%;margin:0!important;position:fixed;inset:0;z-index:9999;background:#fff}.portal-left{position:relative;overflow:hidden;background:#172338;color:#f8fafc;display:flex;align-items:center;justify-content:center;padding:0}.portal-left:before{content:"";position:absolute;width:300px;height:300px;border:1px solid rgba(249,115,22,.13);border-radius:50%;right:-66px;top:-88px;box-shadow:0 0 0 64px rgba(249,115,22,.035)}.portal-left:after{content:"";position:absolute;width:260px;height:260px;border-radius:50%;left:-108px;bottom:-102px;background:rgba(148,163,184,.045)}.orb{position:absolute;border-radius:50%;background:rgba(249,115,22,.13)}.orb.one{width:32px;height:32px;left:42px;top:132px}.orb.two{width:42px;height:42px;right:132px;top:310px;background:rgba(79,70,229,.22)}.orb.three{width:24px;height:24px;left:72px;bottom:210px}.orb.four{width:28px;height:28px;right:68px;bottom:150px;background:rgba(79,70,229,.24)}.marketing{position:relative;z-index:1;width:420px;margin-top:-10px}.logo{display:flex;align-items:center;gap:10px;margin-bottom:38px}.logo-mark{width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#ff5b00,#ff8a00);display:grid;place-items:center;color:#fff;font-weight:900}.logo strong{display:block;font-size:17px;line-height:16px}.logo strong .accent{color:#ff7a00}.logo span{font-size:10px;color:#94a3b8}.headline{font-size:31px;line-height:1.24;margin:0 0 20px;font-weight:900;letter-spacing:-.7px;color:#f8fafc}.headline em{font-style:normal;color:#ff7a00}.lead{color:#aeb8c8;font-size:13px;line-height:1.85;max-width:395px;margin:0 0 34px}.feature{display:flex;align-items:center;gap:15px;color:#dbe4f0;font-size:12px;margin:17px 0}.feature i{width:30px;height:30px;border-radius:9px;background:rgba(249,115,22,.12);color:#ff7a00;display:flex;align-items:center;justify-content:center;flex:0 0 30px}.portal-right{display:flex;align-items:center;justify-content:center;background:#fff;padding:0}.login-box{width:360px;margin-top:-8px}.login-box h1{font-size:21px;color:#172338;margin:0 0 6px;font-weight:900}.login-box .sub{font-size:13px;color:#7b8494;margin:0 0 28px}.form-field{margin-bottom:20px}.form-field label{display:block;color:#172338;font-size:11px;font-weight:900;margin-bottom:8px}.input-wrap{height:46px;border:1px solid #b9c6d8;border-radius:9px;display:flex;align-items:center;gap:10px;padding:0 12px;background:#fff}.input-wrap:focus-within{border-color:#f97316;box-shadow:0 0 0 3px rgba(249,115,22,.1)}.input-wrap i{color:#64748b;width:14px;text-align:center}.input-wrap input{border:0;outline:0;width:100%;font-size:13px;color:#172338;background:transparent}.input-wrap input::placeholder{color:#9ca3af}.forgot{text-align:right;margin-top:-12px;margin-bottom:24px}.forgot a{font-size:11px;color:#f97316;text-decoration:none;font-weight:800}.signin{width:100%;height:45px;border:0;border-radius:5px;background:#f97316;color:#fff;font-size:13px;font-weight:900;cursor:pointer}.signin:hover{background:#ea580c}.register-row{text-align:center;margin-top:16px;color:#7b8494;font-size:12px}.register-row a{color:#f97316;text-decoration:none;font-weight:900}.admin-row,.legal{display:none!important}@media(max-width:900px){html,body{overflow:auto!important}.portal-split{position:relative;height:auto;min-height:100vh;grid-template-columns:1fr}.portal-left{min-height:430px;padding:42px}.marketing{width:100%;max-width:420px}.portal-right{min-height:520px;padding:36px 22px}.headline{font-size:28px}.login-box{width:100%;max-width:360px}}@media(max-width:520px){.portal-left{display:none}.portal-right{min-height:100vh}.login-box{max-width:100%}}
    {/literal}
</style>

<main class="portal-split">
    <section class="portal-left">
        <span class="orb one"></span>
        <span class="orb two"></span>
        <span class="orb three"></span>
        <span class="orb four"></span>
        <div class="marketing">
            <div class="logo">
                <span class="logo-mark"><i class="fa fa-bolt"></i></span>
                <div>
                    <strong>Isp<span class="accent">Daftar</span></strong>
                    <span>Admin</span>
                </div>
            </div>
            <h2 class="headline">Your complete <em>ISP management</em> solution</h2>
            <p class="lead">Everything you need to run your internet service business — from billing to network monitoring.</p>
            <div class="feature"><i class="fa fa-wifi"></i><span>Manage your ISP network and packages</span></div>
            <div class="feature"><i class="fa fa-users"></i><span>Track customers, staff, and payments</span></div>
            <div class="feature"><i class="fa fa-money"></i><span>Full billing and invoice management</span></div>
        </div>
    </section>
    <section class="portal-right">
        <div class="login-box">
            <h1>Welcome back</h1>
            <p class="sub">Sign in to your account to continue</p>
            <form action="{Text::url('login/post')}" method="post">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <div class="form-field">
                    <label>
                        {if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email Address')}{else}{Lang::T('Usernames')}{/if}
                    </label>
                    <div class="input-wrap">
                        <i class="fa fa-envelope-o"></i>
                        <input type="text" name="username" required placeholder="{Lang::T('Usernames')}">
                    </div>
                </div>
                <div class="form-field">
                    <label>{Lang::T('Password')}</label>
                    <div class="input-wrap">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" required placeholder="••••••••">
                        <i class="fa fa-eye-slash"></i>
                    </div>
                </div>
                <div class="forgot"><a href="{Text::url('forgot')}">{Lang::T('Forgot password?')}</a></div>
                <button type="submit" class="signin">{Lang::T('Sign In')}</button>
            </form>
            {if $_c['disable_registration'] != 'noreg'}
                <div class="register-row">{Lang::T('New customer?')} <a href="{Text::url('customer_requests')}">{Lang::T('Register')}</a></div>
            {/if}
        </div>
    </section>
</main>

<!-- Modal -->
<div class="modal fade" id="HTMLModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Info</h4>
            </div>
            <div class="modal-body" id="HTMLModal_konten"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="{$app_url}/ui/ui/scripts/vendors.js?v=1"></script>
</body>
</html>