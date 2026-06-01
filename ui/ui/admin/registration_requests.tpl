{include file="sections/header.tpl"}

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-user-plus"></i> {Lang::T('Customer Registration Requests')}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{Lang::T('Instance')}</th>
                    <th>{Lang::T('Name')}</th>
                    <th>{Lang::T('Email')}</th>
                    <th>{Lang::T('Phone')}</th>
                    <th>{Lang::T('Location')}</th>
                    <th>{Lang::T('Status')}</th>
                    <th>{Lang::T('Trial')}</th>
                    <th>{Lang::T('Action')}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $requests as $r}
                    <tr>
                        <td>{$r['id']}</td>
                        <td><strong>{$r['instance_name']}</strong></td>
                        <td>{$r['first_name']} {$r['last_name']}</td>
                        <td>{$r['email']}</td>
                        <td>{$r['phone']}</td>
                        <td>{$r['city']}, {$r['country']}</td>
                        <td><span class="label label-{if $r['status'] eq 'pending_approval'}warning{elseif $r['status'] eq 'approved_trial'}success{elseif $r['status'] eq 'rejected'}danger{else}default{/if}">{$r['status']}</span></td>
                        <td>{if $r['trial_expires_at']}{$r['trial_expires_at']}{else}-{/if}</td>
                        <td>
                            {if $r['status'] eq 'pending_approval'}
                                <a class="btn btn-success btn-xs" href="{Text::url('registration_requests/approve/')}{$r['id']}">{Lang::T('Approve')}</a>
                                <a class="btn btn-danger btn-xs" href="{Text::url('registration_requests/reject/')}{$r['id']}">{Lang::T('Reject')}</a>
                            {else}
                                -
                            {/if}
                        </td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="9" class="text-center text-muted">{Lang::T('No Data')}</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>

{include file="sections/footer.tpl"}
