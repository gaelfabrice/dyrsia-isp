{include file="customer/header.tpl"}
<!-- user-dashboard -->

{function showWidget pos=0}
    {foreach $widgets as $w}
        {if $w['position'] == $pos}
            {$w['content']}
        {/if}
    {/foreach}
{/function}


<div class="wz-orbit-page">
    <div class="wz-orbit-hero">
        <div class="wz-orbit-hero-copy">
            <span class="wz-orbit-kicker">{Lang::T('Customer Space')}</span>
            <h2>{$_user['fullname']}</h2>
            <p>{Lang::T('Manage your packages, vouchers, balance and payment history from a clean connected experience.')}</p>
        </div>
        <div class="wz-orbit-actions">
            <a href="{Text::url('order/package')}" class="btn btn-primary"><i class="ion ion-ios-cart"></i> {Lang::T('Buy Package')}</a>
            {if $_c['enable_balance'] == 'yes'}
                <a href="{Text::url('order/balance')}" class="btn btn-default"><i class="fa fa-credit-card"></i> {Lang::T('Buy Balance')}</a>
            {/if}
        </div>
    </div>

    {assign rows explode(".", $_c['dashboard_Customer'])}
    {assign pos 1}
    {foreach $rows as $cols}
        {if $cols == 12}
            <div class="row wz-orbit-row">
                <div class="col-md-12 wz-orbit-col">
                    {showWidget widgets=$widgets pos=$pos}
                </div>
            </div>
            {assign pos value=$pos+1}
        {else}
            {assign colss explode(",", $cols)}
            <div class="row wz-orbit-row">
                {foreach $colss as $c}
                    <div class="col-md-{$c} wz-orbit-col">
                        {showWidget widgets=$widgets pos=$pos}
                    </div>
                    {assign pos value=$pos+1}
                {/foreach}
            </div>
        {/if}
    {/foreach}
</div>


{if isset($hostname) && $hchap == 'true' && $_c['hs_auth_method'] == 'hchap'}
    <script type="text/javascript" src="/ui/ui/scripts/md5.js"></script>
    <script type="text/javascript">
        var hostname = "http://{$hostname}/login";
        var user = "{$_user['username']}";
        var pass = "{$_user['password']}";
        var dst = "{$apkurl}";
        var authdly = "2";
        var key = hexMD5('{$key1}' + pass + '{$key2}');
        var auth = hostname + '?username=' + user + '&dst=' + dst + '&password=' + key;
        document.write('<meta http-equiv="refresh" target="_blank" content="' + authdly + '; url=' + auth + '">');
    </script>
{/if}
{include file="customer/footer.tpl"}