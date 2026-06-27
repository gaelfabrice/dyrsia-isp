{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">
                <i class="fa fa-{if $dry_run}eye{else}download{/if}"></i>
                {if $dry_run}
                    <strong>[PREVIEW]</strong> Import Preview — No changes made
                {else}
                    Import Results — Plan: {$plan_name}
                {/if}
            </div>
            <div class="panel-body">

                {if $dry_run}
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>Dry Run Mode</strong> — This is a preview only. Nothing has been saved.
                        Go back and uncheck "Preview only" to actually import.
                    </div>
                {/if}

                {* Summary counts *}
                {assign var="cnt_ai"        value=0}
                {assign var="cnt_created"    value=0}
                {assign var="cnt_exists"     value=0}
                {assign var="cnt_activated"  value=0}
                {assign var="cnt_reactivate" value=0}
                {assign var="cnt_skip"       value=0}
                {assign var="cnt_failed"     value=0}
                {foreach $results as $r}
                    {if $r['status'] == 'ai'}         {$cnt_ai=$cnt_ai+1}
                    {elseif $r['status'] == 'created'}    {$cnt_created=$cnt_created+1}
                    {elseif $r['status'] == 'exists'}    {$cnt_exists=$cnt_exists+1}
                    {elseif $r['status'] == 'activated'} {$cnt_activated=$cnt_activated+1}
                    {elseif $r['status'] == 'reactivate'}{$cnt_reactivate=$cnt_reactivate+1}
                    {elseif $r['status'] == 'skip'}      {$cnt_skip=$cnt_skip+1}
                    {elseif $r['status'] == 'failed'}    {$cnt_failed=$cnt_failed+1}
                    {/if}
                {/foreach}

                <div style="margin-bottom:15px;">
                    <span class="label label-success"  style="font-size:12px;padding:5px 8px;">
                        <i class="fa fa-plus"></i> Created: {$cnt_created}
                    </span>
                    <span class="label label-primary"  style="font-size:12px;padding:5px 8px;">
                        <i class="fa fa-bolt"></i> Activated: {$cnt_activated}
                    </span>
                    <span class="label label-warning"  style="font-size:12px;padding:5px 8px;">
                        <i class="fa fa-info"></i> Exists: {$cnt_exists}
                    </span>
                    <span class="label label-default"  style="font-size:12px;padding:5px 8px;">
                        <i class="fa fa-forward"></i> Skipped: {$cnt_skip}
                    </span>
                    <span class="label label-info"     style="font-size:12px;padding:5px 8px;">
                        <i class="fa fa-refresh"></i> Re-activated: {$cnt_reactivate}
                    </span>
                    {if $cnt_failed > 0}
                    <span class="label label-danger"   style="font-size:12px;padding:5px 8px;">
                        <i class="fa fa-times"></i> Failed: {$cnt_failed}
                    </span>
                    {/if}
                </div>

                {if $results}
                    <ol>
                        {foreach $results as $r}
                            <li style="
                                {if $r['status'] == 'info'}color:#16a085;font-style:italic;
                                {elseif $r['status'] == 'created'}color:#27ae60;
                                {elseif $r['status'] == 'activated'}color:#2980b9;font-weight:bold;
                                {elseif $r['status'] == 'reactivate'}color:#8e44ad;font-weight:bold;
                                {elseif $r['status'] == 'exists'}color:#e67e22;
                                {elseif $r['status'] == 'skip'}color:#95a5a6;
                                {elseif $r['status'] == 'failed'}color:#e74c3c;font-weight:bold;
                                {/if}
                            ">{$r['msg']}</li>
                        {/foreach}
                    </ol>
                {else}
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> No results. Check Mikrotik connection.
                    </div>
                {/if}

                <div style="margin-top:20px;">
                    <a href="{$_url}plugin/mikrotik_import_ui" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                    {if not $dry_run}
                    <a href="{$_url}customers" class="btn btn-success">
                        <i class="fa fa-users"></i> View Users
                    </a>
                    {/if}
                </div>

            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
