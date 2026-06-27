{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="ion ion-stats-bars"></i> Admin Wallet & Commission Balance</h3>
            </div>
            <div class="panel-body">
                <!-- উপরের ছোট সামারি বক্স -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-4">
                        <div style="background: #ede7f6; padding: 15px; border-radius: 5px; border-left: 5px solid #673ab7;">
                            <span style="color: #673ab7; font-weight: bold; text-transform: uppercase; font-size: 12px;">Total System Commission</span>
                            <h3 style="margin: 5px 0; color: #333;">{$_c['currency_code']} {$w_commission|default:"0.00"}</h3>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th>Admin Name</th>
                                <th>Username</th>
                                <th class="text-right">Wallet Balance</th>
                                <th class="text-right" style="background: #f3e5f5; color: #673ab7;">Commission Balance</th>
                                <th>Last Updated</th>
                                <th class="text-center">Actions</th> <!-- নতুন কলাম -->
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $commissions as $comm}
                            <tr>
                                <td><strong>{$comm['fullname']}</strong></td>
                                <td><span class="label label-default">{$comm['username']}</span></td>
                                <td class="text-right">
                                    <strong>{$_c['currency_code']} {number_format($comm['balance'], 2)}</strong>
                                </td>
                                <td class="text-right" style="background: #fdfbff;">
                                    <span style="font-size: 16px; color: #673ab7; font-weight: bold;">
                                        {$_c['currency_code']} {number_format($comm['commission_balance'], 2)}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fa fa-clock-o"></i> {$comm['updated_at']}
                                    </small>
                                </td>
                                <td class="text-center">
                                    {if $comm['commission_balance'] > 0}
                                        <form method="post" action="">
                                            <!-- অ্যাডমিন আইডি হিডেন ফিল্ডে রাখা হলো -->
                                            <input type="hidden" name="t_admin_id" value="{$comm['admin_id']}">
                                            
                                            <button type="submit" name="transfer_commission" class="btn btn-primary btn-xs" style="background: #673ab7; border: none;" onclick="return confirm('Transfer {$_c['currency_code']} {number_format($comm['commission_balance'], 2)} to Main Wallet?')">
                                                <i class="fa fa-send"></i> Transfer
                                            </button>
                                        </form>
                                    {else}
                                        <span class="text-muted" style="font-size: 11px;">No Commission</span>
                                    {/if}
                                </td>
                            </tr>
                            {/foreach}
                            {if empty($commissions)}
                            <tr>
                                <td colspan="6" class="text-center">No data found</td>
                            </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
                
                <div class="panel-footer">
                    <small>* Wallet Balance is for recharge/billing, Commission Balance is earned profit from recharges.</small>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}