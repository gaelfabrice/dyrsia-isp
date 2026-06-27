{include file="sections/header.tpl"}

<section class="content-header">
    <h1>{Lang::T('Hotspot Resellers')}</h1>
    <ol class="breadcrumb">
        <li><a href="{$_url}dashboard">{Lang::T('Dashboard')}</a></li>
        <li class="active">{Lang::T('Hotspot Resellers')}</li>
    </ol>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3>{$totalResellers}</h3>
                    <p>{Lang::T('Resellers')}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3>{$totalActive}</h3>
                    <p>{Lang::T('Active')}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3>{$totalSuspended}</h3>
                    <p>{Lang::T('Suspended')}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3>{$currency_code} {number_format($totalBalance, 2)}</h3>
                    <p>{Lang::T('Total balance')}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">{Lang::T('Add reseller')}</div>
                <div class="panel-body">
                    <form method="post" action="{$_url}plugin/hotspot_resellers">
                        <input type="hidden" name="_route" value="plugin/hotspot_resellers">
                        <div class="form-group">
                            <label>{Lang::T('Username')}</label>
                            <input type="text" name="username" class="form-control" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Password')}</label>
                            <input type="password" name="password" class="form-control" required autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Full Name')}</label>
                            <input type="text" name="fullname" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Phone Number')}</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>{Lang::T('Initial balance')} ({$currency_code})</label>
                            <input type="number" step="0.01" name="balance" class="form-control" value="0">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">{Lang::T('Save')}</button>
                    </form>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-body text-center">
                    <p><strong>{Lang::T('Tokens')}:</strong> {$totalTokens} &nbsp;|&nbsp; <strong>{Lang::T('Vouchers')}:</strong> {$totalVouchers}</p>
                    <a href="{$_url}plugin/hotspot_resellers_topup_reports" class="btn btn-default btn-sm">{Lang::T('Top-up reports')}</a>
                    <a href="{$_url}plugin/hotspot_logs" class="btn btn-default btn-sm">{Lang::T('Logs')}</a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {Lang::T('Reseller list')}
                    <form method="get" action="{$_url}plugin/hotspot_resellers" class="pull-right" style="max-width:280px;">
                        <input type="hidden" name="_route" value="plugin/hotspot_resellers">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" value="{$smarty.get.search|default:''}" placeholder="{Lang::T('Search')}...">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                            </span>
                        </div>
                    </form>
                </div>
                <div class="panel-body table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{Lang::T('Username')}</th>
                                <th>{Lang::T('Full Name')}</th>
                                <th>{Lang::T('Phone')}</th>
                                <th>{Lang::T('Balance')}</th>
                                <th>{Lang::T('Tokens')}</th>
                                <th>{Lang::T('Vouchers')}</th>
                                <th>{Lang::T('Status')}</th>
                                <th>{Lang::T('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if $resellers|@count == 0}
                            <tr><td colspan="9" class="text-center text-muted">{Lang::T('No data available')}</td></tr>
                            {/if}
                            {foreach $resellers as $r}
                            <tr>
                                <td>{$r.id}</td>
                                <td><strong>{$r.username}</strong></td>
                                <td>{$r.fullname}</td>
                                <td>{$r.phone}</td>
                                <td>{$currency_code} {number_format($r.balance, 2)}</td>
                                <td>
                                    <small>{Lang::T('Total')}: {$r.tokens}</small><br>
                                    <span class="label label-success">{$r.tokens_active}</span>
                                    <span class="label label-default">{$r.tokens_unused}</span>
                                    <span class="label label-danger">{$r.tokens_used}</span>
                                </td>
                                <td>
                                    <small>{Lang::T('Total')}: {$r.vouchers}</small><br>
                                    <span class="label label-success">{$r.vouchers_unused}</span>
                                    <span class="label label-danger">{$r.vouchers_used}</span>
                                </td>
                                <td>
                                    {if $r.status == 'active'}
                                    <span class="label label-success">{Lang::T('Active')}</span>
                                    {else}
                                    <span class="label label-warning">{Lang::T('Suspended')}</span>
                                    {/if}
                                </td>
                                <td>
                                    <a href="{$_url}plugin/hotspot_reseller_view/{$r.id}" class="btn btn-info btn-xs" title="{Lang::T('View')}"><i class="fa fa-eye"></i></a>
                                    <a href="{$_url}plugin/hotspot_reseller_edit/{$r.id}" class="btn btn-primary btn-xs" title="{Lang::T('Edit')}"><i class="fa fa-pencil"></i></a>
                                    {if $r.status == 'active'}
                                    <a href="{$_url}plugin/hotspot_resellers&res_action=suspend&id={$r.id}" class="btn btn-warning btn-xs" onclick="return confirm('{Lang::T('Suspend this reseller?')}');"><i class="fa fa-pause"></i></a>
                                    {else}
                                    <a href="{$_url}plugin/hotspot_resellers&res_action=active&id={$r.id}" class="btn btn-success btn-xs"><i class="fa fa-play"></i></a>
                                    {/if}
                                    <a href="{$_url}plugin/hotspot_resellers&res_action=delete&id={$r.id}" class="btn btn-danger btn-xs" onclick="return confirm('{Lang::T('Delete this reseller?')}');"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

{include file="sections/footer.tpl"}
