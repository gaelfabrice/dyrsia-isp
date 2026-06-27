{include file="sections/header.tpl"}

<div class="row">
    <!-- রিচার্জ এবং রেট আপডেট ফর্ম -->
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">Update Wallet & Rate</div>
            <div class="panel-body">
                <form action="{$_url}plugin/admin_wallet" method="post">
                    <input type="hidden" name="wallet_update" value="1">
                    
                    <div class="form-group">
                        <label>Select Admin</label>
                        <select name="admin_id" class="form-control" required>
                            <option value="">-- Choose Admin --</option>
                            {foreach $admins as $admin}
                                <option value="{$admin['id']}">{$admin['fullname']}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Transaction Type</label>
                        <select name="type" class="form-control">
                            <option value="credit">Credit (Add Balance)</option>
                            <option value="debit">Debit (Deduct Balance)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label>Commission Rate (%)</label>
                        <input type="number" step="0.1" name="commission_rate" class="form-control" value="10.0">
                        <small class="text-muted">এই অ্যাডমিন প্রতি প্যাকেজ সেলে কত % পাবে।</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Submit Update</button>
                </form>
            </div>
        </div>
    </div>

    <!-- বর্তমান ব্যালেন্স এবং ইন্ডিভিজুয়াল রেট লিস্ট -->
    <div class="col-md-8">
        <div class="panel panel-success">
            <div class="panel-heading">Current Admin Balances</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr class="bg-faded">
                                <th>Admin</th>
                                <th>Balance</th>
                                <th>Comm. Rate</th>
                                <th>Last Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $admins as $admin}
                                <tr>
                                    <td><strong>{$admin['fullname']}</strong><br><small>{$admin['username']}</small></td>
                                    <td class="text-primary" style="font-weight: bold;">
                                        {$_c['currency_code']} {if isset($wallet_data[$admin['id']])}{$wallet_data[$admin['id']]->balance}{else}0.00{/if}
                                    </td>
                                    <td>
                                        <span class="label label-info">
                                            {if isset($wallet_data[$admin['id']])}{$wallet_data[$admin['id']]->commission_rate}{else}10.00{/if}%
                                        </span>
                                    </td>
                                    <td><small>{if isset($wallet_data[$admin['id']])}{$wallet_data[$admin['id']]->updated_at}{else}-{/if}</small></td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- ট্রানজেকশন লগ -->
    <div class="col-md-12">
        <div class="panel panel-info">
            <div class="panel-heading">Recent Logs</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Total</th>
                                <th>Note</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $logs as $log}
                            <tr>
                                <td>{$log['admin_name']}</td>
                                <td>
                                    {if $log['type'] == 'recharge'}<span class="label label-success">Credit</span>
                                    {elseif $log['type'] == 'deduction'}<span class="label label-danger">Debit</span>
                                    {elseif $log['type'] == 'commission'}<span class="label label-info">Comm.</span>
                                    {else}<span class="label label-default">{$log['type']}</span>{/if}
                                </td>
                                <td>{$log['amount']}</td>
                                <td><strong>{$log['total_balance']}</strong></td>
                                <td><small>{$log['note']}</small></td>
                                <td>{$log['created_at']}</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}