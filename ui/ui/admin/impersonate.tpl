{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary panel-hovered mb20">
            <div class="panel-heading">{Lang::T('Login as user')}</div>
            <div class="panel-body">
                <p class="text-muted">{Lang::T('Super Admin only. Open any administrator or customer account without a password, then use Exit when finished.')}</p>
                <div class="form-group">
                    <label>{Lang::T('Search')}</label>
                    <input type="text" id="impersonate-q" class="form-control" placeholder="{Lang::T('Username, name, email, phone…')}" autocomplete="off">
                    <p class="help-block text-muted">{Lang::T('Optional filter — clear the field to show the full list again.')}</p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h4>{Lang::T('Administrators')} <small class="text-muted" id="impersonate-admins-count"></small></h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed" id="impersonate-admins">
                                <thead>
                                    <tr><th>{Lang::T('Account')}</th><th style="width:120px"></th></tr>
                                </thead>
                                <tbody>
                                {if $impersonate_admins|@count > 0}
                                    {foreach $impersonate_admins as $u}
                                    <tr>
                                        <td><strong>{$u.username|escape}</strong><br><small>{$u.fullname|escape} · {$u.user_type|escape}</small></td>
                                        <td><a class="btn btn-warning btn-xs btn-block" href="{Text::url('impersonate/admin/')}{$u.id}&amp;token={$csrf_token|escape:'url'}" onclick="return confirm('{Lang::T('Login as this administrator?')|escape:'javascript'}')">{Lang::T('Login')}</a></td>
                                    </tr>
                                    {/foreach}
                                {else}
                                    <tr><td colspan="2" class="text-muted">—</td></tr>
                                {/if}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4>{Lang::T('Customers')} <small class="text-muted" id="impersonate-customers-count"></small></h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed" id="impersonate-customers">
                                <thead>
                                    <tr><th>{Lang::T('Account')}</th><th style="width:120px"></th></tr>
                                </thead>
                                <tbody>
                                {if $impersonate_customers|@count > 0}
                                    {foreach $impersonate_customers as $c}
                                    <tr>
                                        <td><strong>{$c.username|escape}</strong><br><small>{$c.fullname|escape}</small></td>
                                        <td><a class="btn btn-warning btn-xs btn-block" href="{Text::url('impersonate/customer/')}{$c.id}&amp;token={$csrf_token|escape:'url'}" onclick="return confirm('{Lang::T('Login as this customer?')|escape:'javascript'}')">{Lang::T('Login')}</a></td>
                                    </tr>
                                    {/foreach}
                                {else}
                                    <tr><td colspan="2" class="text-muted">—</td></tr>
                                {/if}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var impersonateSearchUrl = '{Text::url('impersonate/search')}';
var impersonateBaseUrl = '{Text::url('impersonate/')}';
var impersonateToken = '{$csrf_token}';
var impersonateLoginAdminConfirm = '{Lang::T('Login as this administrator?')|escape:'javascript'}';
var impersonateLoginCustomerConfirm = '{Lang::T('Login as this customer?')|escape:'javascript'}';
var impersonateLoginLabel = '{Lang::T('Login')|escape:'javascript'}';
</script>
{literal}
<script>
(function () {
    var q = document.getElementById('impersonate-q');
    var timer;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function rowAdmin(u) {
        var href = impersonateBaseUrl + 'admin/' + u.id + '&token=' + encodeURIComponent(impersonateToken);
        return '<tr><td><strong>' + esc(u.username) + '</strong><br><small>' + esc(u.fullname) + ' · ' + esc(u.user_type) + '</small></td>' +
            '<td style="width:120px"><a class="btn btn-warning btn-xs btn-block" href="' + href + '" onclick="return confirm(\'' + impersonateLoginAdminConfirm.replace(/'/g, "\\'") + '\')">' + esc(impersonateLoginLabel) + '</a></td></tr>';
    }

    function rowCustomer(c) {
        var href = impersonateBaseUrl + 'customer/' + c.id + '&token=' + encodeURIComponent(impersonateToken);
        return '<tr><td><strong>' + esc(c.username) + '</strong><br><small>' + esc(c.fullname) + '</small></td>' +
            '<td style="width:120px"><a class="btn btn-warning btn-xs btn-block" href="' + href + '" onclick="return confirm(\'' + impersonateLoginCustomerConfirm.replace(/'/g, "\\'") + '\')">' + esc(impersonateLoginLabel) + '</a></td></tr>';
    }

    function setCount(elId, n) {
        var el = document.getElementById(elId);
        if (el) {
            el.textContent = n > 0 ? '(' + n + ')' : '';
        }
    }

    function render(data) {
        var at = document.querySelector('#impersonate-admins tbody');
        var ct = document.querySelector('#impersonate-customers tbody');
        var admins = data.admins || [];
        var customers = data.customers || [];
        at.innerHTML = admins.length ? admins.map(rowAdmin).join('') : '<tr><td colspan="2" class="text-muted">—</td></tr>';
        ct.innerHTML = customers.length ? customers.map(rowCustomer).join('') : '<tr><td colspan="2" class="text-muted">—</td></tr>';
        setCount('impersonate-admins-count', admins.length);
        setCount('impersonate-customers-count', customers.length);
    }

    function search() {
        var val = (q && q.value) ? q.value.trim() : '';
        if (val.length === 1) {
            return;
        }
        fetch(impersonateSearchUrl + '&q=' + encodeURIComponent(val), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () {});
    }

    setCount('impersonate-admins-count', {/literal}{$impersonate_admins|@count}{literal});
    setCount('impersonate-customers-count', {/literal}{$impersonate_customers|@count}{literal});

    if (q) {
        q.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(search, 350);
        });
    }
})();
</script>
{/literal}

{include file="sections/footer.tpl"}
