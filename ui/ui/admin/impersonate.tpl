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
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h4>{Lang::T('Administrators')}</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed" id="impersonate-admins">
                                <tbody><tr><td class="text-muted">{Lang::T('Type at least 2 characters')}</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4>{Lang::T('Customers')}</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed" id="impersonate-customers">
                                <tbody><tr><td class="text-muted">{Lang::T('Type at least 2 characters')}</td></tr></tbody>
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
            '<td style="width:120px"><a class="btn btn-warning btn-xs btn-block" href="' + href + '" onclick="return confirm(\'Login as this administrator?\')">Login</a></td></tr>';
    }

    function rowCustomer(c) {
        var href = impersonateBaseUrl + 'customer/' + c.id + '&token=' + encodeURIComponent(impersonateToken);
        return '<tr><td><strong>' + esc(c.username) + '</strong><br><small>' + esc(c.fullname) + '</small></td>' +
            '<td style="width:120px"><a class="btn btn-warning btn-xs btn-block" href="' + href + '" onclick="return confirm(\'Login as this customer?\')">Login</a></td></tr>';
    }

    function search() {
        var val = (q.value || '').trim();
        if (val.length < 2) return;
        fetch(impersonateSearchUrl + '&q=' + encodeURIComponent(val), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var at = document.querySelector('#impersonate-admins tbody');
                var ct = document.querySelector('#impersonate-customers tbody');
                at.innerHTML = (data.admins && data.admins.length) ? data.admins.map(rowAdmin).join('') : '<tr><td class="text-muted">—</td></tr>';
                ct.innerHTML = (data.customers && data.customers.length) ? data.customers.map(rowCustomer).join('') : '<tr><td class="text-muted">—</td></tr>';
            });
    }

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
