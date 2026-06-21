{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{Lang::T('OLT_Management')}</div>
            <div class="panel-body">
                <div class="row" style="margin-bottom:20px">
                    <div class="col-md-7">
                        <p class="text-muted" style="margin:8px 0 0">{Lang::T('Manage optical line terminals and port capacity for FTTH.')}</p>
                    </div>
                    <div class="col-md-5 text-right">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#oltAddModal">
                            <i class="ion ion-android-add"></i> {Lang::T('Add')}
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>{Lang::T('Name')}</th>
                                <th>{Lang::T('Brand')}</th>
                                <th>{Lang::T('Model')}</th>
                                <th>{Lang::T('IP Address')}</th>
                                <th>{Lang::T('Ports')}</th>
                                <th>{Lang::T('Status')}</th>
                                <th>{Lang::T('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $olts as $olt}
                            <tr>
                                <td>{$olt['name']|escape}</td>
                                <td>{$olt['brand']|escape}</td>
                                <td>{$olt['model']|escape}</td>
                                <td>{$olt['ip_address']|escape}</td>
                                <td>{$olt['used_ports']}/{$olt['total_ports']}</td>
                                <td>{$olt['status']|escape}</td>
                                <td>
                                    <a href="{Text::url('plugin/olt_management/delete/')}{$olt['id']}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('{Lang::T('Delete')} ?');">
                                        <i class="glyphicon glyphicon-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            {foreachelse}
                            <tr><td colspan="7" class="text-center text-muted">{Lang::T('No Data')}</td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="oltAddModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{Text::url('plugin/olt_management/add-post')}" class="modal-content">
            {csrf_field()}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{Lang::T('Add')} OLT</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{Lang::T('Name')} *</label>
                    <input type="text" name="name" class="form-control" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>{Lang::T('Brand')}</label>
                    <input type="text" name="brand" class="form-control" maxlength="50">
                </div>
                <div class="form-group">
                    <label>{Lang::T('Model')}</label>
                    <input type="text" name="model" class="form-control" maxlength="100">
                </div>
                <div class="form-group">
                    <label>{Lang::T('IP Address')}</label>
                    <input type="text" name="ip_address" class="form-control" maxlength="45">
                </div>
                <div class="form-group">
                    <label>{Lang::T('Ports')}</label>
                    <input type="number" name="total_ports" class="form-control" min="1" value="8">
                </div>
                <div class="form-group">
                    <label>{Lang::T('Address')}</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>{Lang::T('Description')}</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{Lang::T('Cancel')}</button>
                <button type="submit" class="btn btn-primary">{Lang::T('Save')}</button>
            </div>
        </form>
    </div>
</div>

{include file="sections/footer.tpl"}
