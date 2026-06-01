{include file="sections/header.tpl"}
<!-- reports-period -->

<div class="row">
    <div class="col-sm-12 col-md-6">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('Period Reports')}</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}reports/period-view">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('From Date')}</label>
                        <div class="col-md-9">
                            <input type="date" class="form-control" value="{$tdate}" name="fdate" id="fdate">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('To Date')}</label>
                        <div class="col-md-9">
                            <input type="date" class="form-control" value="{$mdate}" name="tdate" id="tdate">
                        </div>
                    </div>
                    <div class="form-group">
    <label class="col-md-3 control-label">{Lang::T('Type')}</label>
    <div class="col-md-9">
        <select class="form-control" id="stype" name="stype">
            <option value="" selected="">{Lang::T('All Transactions')}</option>
            <option value="Hotspot">Hotspot</option>
            <option value="PPPOE">PPPOE</option>
            <option value="Balance">Balance</option>
        </select>
    </div>
</div>

{if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
<div class="form-group">
    <label class="col-md-3 control-label">{Lang::T('Admin/User')}</label>
    <div class="col-md-9">
        <select class="form-control" name="admin_id" id="admin_id">
            <option value="">{Lang::T('All Users')}</option>
            {foreach ORM::for_table('tbl_users')->order_by_asc('fullname')->find_many() as $u}
                <option value="{$u['id']}">{$u['fullname']} ({$u['username']}) - {$u['user_type']}</option>
            {/foreach}
        </select>
    </div>
</div>
{/if}


                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" id="submit"
                                class="btn btn-primary">{Lang::T('Period Reports')}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}