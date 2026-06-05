{include file="sections/header.tpl"}
<!-- routers -->

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{Lang::T('Routers')}
                <div class="btn-group pull-right">
                    <a class="btn btn-primary btn-xs" title="save" href="{Text::url('')}routers/maps">
                        <span class="glyphicon glyphicon-map-marker"></span></a>
                </div>
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">

                        <form id="site-search" method="post" action="{Text::url('')}routers/list/">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="fa fa-search"></span>
                                </div>
                                <input type="text" name="name" class="form-control"
                                    placeholder="{Lang::T('Search by Name')}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        {if !isset($router_add_permission) || $router_add_permission.ok}
                        <a href="{Text::url('')}routers/add" class="btn btn-primary btn-block"><i
                                class="ion ion-android-add">
                            </i> {Lang::T('New Router')}</a>
                        {else}
                            <div class="alert alert-warning" style="margin-bottom:0">{$router_add_permission.message}</div>
                        {/if}
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{Lang::T('Router Name')}</th>
                                <th>{Lang::T('IP Address')}</th>
                                <th>{Lang::T('Username')}</th>
                                <th>{Lang::T('Description')}</th>
                                <th>{Lang::T('Online Status')}</th>
                                <th>{Lang::T('Last Seen')}</th>
                                <th>{Lang::T('Status')}</th>
                                <th>{Lang::T('Manage')}</th>
                                <th>ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr {if $ds['enabled'] !=1}class="danger" title="disabled" {/if}>
                                    <td>
                                        {if $ds['coordinates']}
                                            <a href="https://www.google.com/maps/dir//{$ds['coordinates']}/" target="_blank"
                                                class="btn btn-default btn-xs" title="{$ds['coordinates']}"><i
                                                    class="glyphicon glyphicon-map-marker"></i></a>
                                        {/if}
                                        {$ds['name']}
                                    </td>
                                    <td style="background-color: black; color: black;"
                                        onmouseleave="this.style.backgroundColor = 'black';"
                                        onmouseenter="this.style.backgroundColor = 'white';">{$ds['ip_address']}</td>
                                    <td style="background-color: black; color: black;"
                                        onmouseleave="this.style.backgroundColor = 'black';"
                                        onmouseenter="this.style.backgroundColor = 'white';">{$ds['username']}</td>
                                    <td>{$ds['description']}</td>
                                    <td>
                                        <span class="label {if $ds['status'] == 'Online'}label-success {else}label-danger {/if}">
                                            {if $ds['status'] == 'Online'}
                                                {Lang::T('Online')}
                                            {else}
                                                {Lang::T('Offline')}
                                            {/if}
                                        </span>
                                    </td>
                                    <td>{if $ds['last_seen']}{$ds['last_seen']}{else}-{/if}</td>
                                    <td>{if $ds['enabled'] == 1}{Lang::T('Enabled')}{else}{Lang::T('Disabled')}{/if}</td>
                                    <td>
                                        <a href="{Text::url('')}routers/edit/{$ds['id']}"
                                            class="btn btn-info btn-xs">{Lang::T('Edit')}</a>
                                        <form method="post" action="{Text::url('')}routers/delete/{$ds['id']}" style="display:inline;margin:0;padding:0"
                                            onsubmit="return confirm('{Lang::T('Delete')}?');">
                                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                            <button type="submit" class="btn btn-danger btn-xs" title="{Lang::T('Delete')}">
                                                <i class="glyphicon glyphicon-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>{$ds['id']}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {include file="pagination.tpl"}
                <div class="bs-callout bs-callout-info" id="callout-navbar-role">
                    <h4>{Lang::T('Online Status')}</h4>
                    <p>{Lang::T('Status is updated once per day by the cron job (ping). Enable monitoring in')}
                        <a href="{Text::url('settings/miscellaneous')}#router_check" class="btn btn-link">{Lang::T('Miscellaneous Settings')}</a>.
                        {if $_admin['user_type'] eq 'SuperAdmin'}
                            <a href="{Text::url('routers/run-check')}" class="btn btn-warning btn-xs"
                                onclick="return confirm('Lancer une vérification maintenant ?');">{Lang::T('Run check now')}</a>
                        {/if}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>


{include file="sections/footer.tpl"}