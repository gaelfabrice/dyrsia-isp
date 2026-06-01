{include file="sections/header.tpl"}
<!-- pool -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                    <div class="btn-group pull-right">
                        <a class="btn btn-primary btn-xs" title="save" href="{Text::url('logs/list-csv')}"
                            onclick="return ask(this, '{Lang::T('This will export to CSV')}?')"><span class="glyphicon glyphicon-download"
                                aria-hidden="true"></span> CSV</a>
                    </div>
                {/if}
                {Lang::T('Activity Log')}
            </div>
            <div class="panel-body">
                <div class="text-center" style="padding: 15px">
                    <div class="col-md-4">
                        <form id="site-search" method="post" action="{Text::url('logs/list/')}">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="q" class="form-control" value="{$q}"
                                    placeholder="{Lang::T('Search by Name')}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <form class="form-inline" method="post" action="{Text::url('logs/list/')}" style="margin-bottom:8px">
                            <div class="input-group has-error">
                                <span class="input-group-addon">{Lang::T('Keep Logs')} </span>
                                <input type="text" name="keep" class="form-control" placeholder="90" value="90">
                                <span class="input-group-addon">{Lang::T('Days')}</span>
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return ask(this, '{Lang::T("Clear old logs?")}')">{Lang::T('Clean up Logs')}</button>
                        </form>
                        <form class="form-inline" method="post" action="{Text::url('logs/list/')}">
                            <input type="hidden" name="clear_all" value="yes">
                            <button type="submit" class="btn btn-warning btn-sm"
                                onclick="return ask(this, '{Lang::T("Delete_all_activity_logs_")}')">{Lang::T('Delete_All')}</button>
                        </form>
                    </div>&nbsp;
                </div>
                <br>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>{Lang::T('Date')}</th>
                                <th>{Lang::T('Type')}</th>
                                <th>{Lang::T('User')}</th>
                                <th>IP</th>
                                <th>{Lang::T('Description')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr>
                                    <td>{$ds['id']}</td>
                                    <td>{Lang::dateTimeFormat($ds['date'])}</td>
                                    <td>{$ds['type']}</td>
                                    <td>{$ds['userid']}</td>
                                    <td>{$ds['ip']}</td>
                                    <td style="overflow-x: scroll;">{nl2br($ds['description'])}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {include file="pagination.tpl"}
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
