<aside class="main-sidebar">
    <section class="sidebar">
        <ul class="sidebar-menu" data-widget="tree">

            <li class="header wz-nav-section">{Lang::T('Command_Center')}</li>
            <li {if $_system_menu eq 'dashboard'}class="active"{/if}>
                <a href="{Text::url('dashboard')}"><i class="ion ion-monitor"></i> <span class="wz-nav-label">{Lang::T('Dashboard')}</span></a>
            </li>

            {if in_array($_admin['user_type'], ['SuperAdmin','Admin','Report'])}
            <li class="header wz-nav-section">{Lang::T('Reports')}</li>
            <li class="{if $_system_menu eq 'reports' || $_routes[0] eq 'reports'}active menu-open{/if} treeview">
                <a href="#"><i class="ion ion-clipboard"></i> <span class="wz-nav-label">{Lang::T('Reports')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_routes[0] eq 'reports' && ($_routes[1] eq '' || $_routes[1] eq 'daily-report')}class="active"{/if}>
                        <a href="{Text::url('reports')}">{Lang::T('Daily_Reports')}</a></li>
                    <li {if $_routes[1] eq 'data-usage'}class="active"{/if}>
                        <a href="{Text::url('reports/data-usage')}">{Lang::T('Data_Usage')}</a></li>
                    {$_MENU_REPORTS}
                </ul>
            </li>
            {/if}
            {$_MENU_AFTER_REPORTS}

            <li class="header wz-nav-section">{Lang::T('Customer_Management')}</li>
            <li class="{if $_system_menu eq 'customers' || $_routes[0] eq 'customers' || $_system_menu eq 'quickadd'}active menu-open{/if} treeview">
                <a href="#"><i class="fa fa-users"></i> <span class="wz-nav-label">{Lang::T('Customer_Management')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_system_menu eq 'customers'}class="active"{/if}><a href="{Text::url('customers')}"><i class="fa fa-user"></i> {Lang::T('Customer')}</a></li>
                    <li {if $_system_menu eq 'quickadd'}class="active"{/if}><a href="{Text::url('plugin/quickadd')}"><i class="ion ion-person-add"></i> {Lang::T('Add_Customer')}</a></li>
                    <li {if $_routes[1] eq 'olt_management'}class="active"{/if}><a href="{Text::url('plugin/olt_management')}"><i class="ion ion-network"></i> {Lang::T('OLT_Management')}</a></li>
                    {if in_array($_admin['user_type'], ['SuperAdmin','Admin','Agent'])}
                    <li {if $_routes[1] eq 'users'}class="active"{/if}><a href="{Text::url('settings/users')}"><i class="ion ion-person-stalker"></i> {Lang::T('Administrator_Users')}</a></li>
                    {/if}
                    {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active|default:false}
                    <li {if $_routes[0] eq 'impersonate'}class="active"{/if}><a href="{Text::url('impersonate')}"><i class="fa fa-user-secret"></i> {Lang::T('Login as user')}</a></li>
                    {/if}
                    {if in_array($_admin['user_type'], ['SuperAdmin','Admin'])}
                    <li {if $_routes[0] eq 'registration_requests'}class="active"{/if}><a href="{Text::url('registration_requests')}"><i class="ion ion-person-add"></i> {Lang::T('Registration_Requests')}</a></li>
                    {/if}
                    {$_MENU_CUSTOMERS}
                </ul>
            </li>

            {if !in_array($_admin['user_type'], ['Report'])}
            <li class="header wz-nav-section">{Lang::T('Monitoring')}</li>
            <li class="{if $_system_menu eq 'monitoring'}active menu-open{/if} treeview">
                <a href="#"><i class="fa fa-line-chart"></i> <span class="wz-nav-label">{Lang::T('Monitoring')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_system_menu eq 'monitoring' && ($_routes[1] eq '' || !$_routes[1])}class="active"{/if}><a href="{Text::url('monitoring')}">{Lang::T('Overview')}</a></li>
                    <li {if $_routes[1] eq 'expiry'}class="active"{/if}><a href="{Text::url('monitoring/expiry')}">{Lang::T('Customer Expiry Status')}</a></li>
                </ul>
            </li>

            <li class="header wz-nav-section">{Lang::T('Finance')}</li>
            <li class="{if $_system_menu eq 'finance' || $_routes[1] eq 'withdrawals' || $_routes[1] eq 'reversement'}active menu-open{/if} treeview">
                <a href="#"><i class="fa fa-credit-card"></i> <span class="wz-nav-label">{Lang::T('Finance')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_system_menu eq 'finance' && ($_routes[1] eq '' || !$_routes[1])}class="active"{/if}><a href="{Text::url('finance')}">{Lang::T('Overview')}</a></li>
                    {if $_admin['user_type'] eq 'Admin'}
                    <li {if $_routes[1] eq 'withdrawals'}class="active"{/if}><a href="{Text::url('finance/withdrawals')}"><i class="fa fa-money"></i> {Lang::T('Withdrawal_Request')}</a></li>
                    {/if}
                    {if $_admin['user_type'] eq 'SuperAdmin'}
                    <li {if $_routes[1] eq 'reversement'}class="active"{/if}><a href="{Text::url('finance/reversement')}"><i class="fa fa-exchange"></i> {Lang::T('Reversement')}{if $withdrawal_pending_count|default:0 > 0} <span class="label label-danger">{$withdrawal_pending_count}</span>{/if}</a></li>
                    {/if}
                </ul>
            </li>

            <li class="header wz-nav-section">{Lang::T('Services')}</li>
            <li class="{if $_routes[0] eq 'plan' || $_routes[0] eq 'coupons'}active menu-open{/if} treeview">
                <a href="#"><i class="fa fa-ticket"></i> <span class="wz-nav-label">{Lang::T('Services')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li                     {if $_routes[1] eq 'list'}class="active"{/if}><a href="{Text::url('plan/list')}">{Lang::T('Active_Customers')}</a></li>
                    {if $_c['disable_voucher'] != 'yes'}
                    <li {if $_routes[1] eq 'voucher'}class="active"{/if}><a href="{Text::url('plan/voucher')}">{Lang::T('Vouchers')}</a></li>
                    {/if}
                    {if $_c['enable_coupons'] == 'yes'}
                    <li {if $_routes[0] eq 'coupons'}class="active"{/if}><a href="{Text::url('coupons')}">{Lang::T('Coupons')}</a></li>
                    {/if}
                    <li {if $_routes[1] eq 'recharge'}class="active"{/if}><a href="{Text::url('plan/recharge')}">{Lang::T('Recharge_Customer')}</a></li>
                    {if $_c['enable_balance'] == 'yes'}
                    <li {if $_routes[1] eq 'deposit'}class="active"{/if}><a href="{Text::url('plan/deposit')}">{Lang::T('Refill_Balance')}</a></li>
                    {/if}
                    {$_MENU_SERVICES}
                </ul>
            </li>
            {/if}
            {$_MENU_AFTER_SERVICES}

            {if in_array($_admin['user_type'], ['SuperAdmin','Admin'])}
            <li class="header wz-nav-section">{Lang::T('Internet_Plan')}</li>
            <li class="{if $_system_menu eq 'services' || $_routes[0] eq 'services' || $_routes[0] eq 'bandwidth'}active menu-open{/if} treeview">
                <a href="#"><i class="ion ion-cube"></i> <span class="wz-nav-label">{Lang::T('Internet_Plan')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_routes[1] eq 'pppoe'}class="active"{/if}><a href="{Text::url('services/pppoe')}">{Lang::T('PPPOE')}</a></li>
                    <li {if $_routes[1] eq 'hotspot'}class="active"{/if}><a href="{Text::url('services/hotspot')}">{Lang::T('Hotspot_Plan')}</a></li>
                    <li {if $_routes[1] eq 'vpn'}class="active"{/if}><a href="{Text::url('services/vpn')}">{Lang::T('VPN')}</a></li>
                    <li {if $_routes[0] eq 'bandwidth'}class="active"{/if}><a href="{Text::url('bandwidth/list')}">{Lang::T('Bandwidth')}</a></li>
                    {if $_c['enable_balance'] == 'yes'}
                    <li {if $_routes[1] eq 'balance'}class="active"{/if}><a href="{Text::url('services/balance')}">{Lang::T('Customer_Balance')}</a></li>
                    {/if}
                    {$_MENU_PLANS}
                    {$_MENU_AFTER_PLANS}
                </ul>
            </li>
            {/if}

            {if $_admin['user_type'] eq 'SuperAdmin'}
            <li class="header wz-nav-section">{Lang::T('Maps')}</li>
            <li class="{if $_routes[0] eq 'maps'}active menu-open{/if} treeview">
                <a href="#"><i class="fa fa-map-marker"></i> <span class="wz-nav-label">{Lang::T('Maps')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_routes[1] eq 'customer'}class="active"{/if}><a href="{Text::url('maps/customer')}">{Lang::T('Customer')}</a></li>
                    <li {if $_routes[1] eq 'routers'}class="active"{/if}><a href="{Text::url('maps/routers')}">{Lang::T('Routers')}</a></li>
                    <li {if $_routes[1] eq 'odp'}class="active"{/if}><a href="{Text::url('maps/odp')}">{Lang::T('ODPs')}</a></li>
                    {$_MENU_MAPS}
                </ul>
            </li>
            {/if}

            {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active|default:false}
            <li class="header wz-nav-section">{Lang::T('Send_Message')}</li>
            <li class="{if $_system_menu eq 'message'}active menu-open{/if} treeview">
                <a href="#"><i class="ion ion-android-chat"></i> <span class="wz-nav-label">{Lang::T('Send_Message')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_routes[1] eq 'send'}class="active"{/if}><a href="{Text::url('message/send')}">{Lang::T('Single_Customer')}</a></li>
                    <li {if $_routes[1] eq 'send_bulk'}class="active"{/if}><a href="{Text::url('message/send_bulk')}">{Lang::T('Bulk_Customers')}</a></li>
                    {$_MENU_MESSAGE}
                </ul>
            </li>
            {/if}
            {$_MENU_AFTER_MESSAGE}

            {if in_array($_admin['user_type'], ['SuperAdmin','Admin'])}
            <li class="header wz-nav-section">{Lang::T('Network')}</li>
            <li class="{if $_system_menu eq 'network'}active menu-open{/if} treeview">
                <a href="#"><i class="ion ion-network"></i> <span class="wz-nav-label">{Lang::T('Network')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_routes[0] eq 'routers' && ($_routes[1] eq '' || !$_routes[1])}class="active"{/if}><a href="{Text::url('routers')}">{Lang::T('Routers')}</a></li>
                    <li {if $_routes[0] eq 'pool' && $_routes[1] eq 'list'}class="active"{/if}><a href="{Text::url('pool/list')}">{Lang::T('IP_Pool')}</a></li>
                    <li {if $_routes[0] eq 'pool' && $_routes[1] eq 'port'}class="active"{/if}><a href="{Text::url('pool/port')}">{Lang::T('Port_Pool')}</a></li>
                    <li {if $_routes[0] eq 'odp' && ($_routes[1] eq '' || !$_routes[1])}class="active"{/if}><a href="{Text::url('odp')}">{Lang::T('ODP_List')}</a></li>
                    {$_MENU_NETWORK}
                </ul>
            </li>
            {$_MENU_AFTER_NETWORKS}

            {if $_c['radius_enable']}
            <li class="header wz-nav-section">{Lang::T('Radius')}</li>
            <li class="{if $_system_menu eq 'radius'}active menu-open{/if} treeview">
                <a href="#"><i class="fa fa-database"></i> <span class="wz-nav-label">{Lang::T('Radius')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_routes[0] eq 'radius' && $_routes[1] eq 'nas-list'}class="active"{/if}><a href="{Text::url('radius/nas-list')}">{Lang::T('Radius NAS')}</a></li>
                    {$_MENU_RADIUS}
                </ul>
            </li>
            {/if}
            {$_MENU_AFTER_RADIUS}

            <li class="header wz-nav-section">{Lang::T('Notification')}</li>
            <li class="{if $_system_menu eq 'pages' || ($_system_menu eq 'settings' && in_array($_routes[1], ['notifications','smtp']))}active menu-open{/if} treeview">
                <a href="#"><i class="ion ion-android-notifications"></i> <span class="wz-nav-label">{Lang::T('Notification')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    <li {if $_routes[1] eq 'notifications'}class="active"{/if}><a href="{Text::url('settings/notifications')}">{Lang::T('Sms_notification')}</a></li>
                    <li {if $_routes[1] eq 'notifications'}class="active"{/if}><a href="{Text::url('settings/notifications')}">{Lang::T('Email_Notification')}</a></li>
                    <li {if $_routes[1] eq 'notifications'}class="active"{/if}><a href="{Text::url('settings/notifications')}">{Lang::T('Telegram_notification')}</a></li>
                    <li {if $_routes[1] eq 'notifications'}class="active"{/if}><a href="{Text::url('settings/notifications')}">{Lang::T('User_notification')}</a></li>
                    <li {if $_routes[1] eq 'smtp'}class="active"{/if}><a href="{Text::url('settings/smtp')}">{Lang::T('SMTP_Server')}</a></li>
                    {$_MENU_NOTIFICATION}
                </ul>
            </li>
            {/if}

            {if $_admin['user_type'] eq 'Admin' || ($_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active|default:false)}
            <li class="header wz-nav-section">{Lang::T('ISP_Reseller_Plan')}</li>
            <li class="{if $_system_menu eq 'isp_reseller' || ($_routes[0] eq 'admin' && $_routes[1] eq 'subscription') || ($_routes[0] eq 'superadmin')}active menu-open{/if} treeview">
                <a href="#"><i class="fa fa-sitemap"></i> <span class="wz-nav-label">{Lang::T('ISP_Reseller_Plan')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    {if $_admin['user_type'] eq 'Admin'}
                    <li {if $_routes[0] eq 'admin' && $_routes[1] eq 'subscription'}class="active"{/if}><a href="{Text::url('admin/subscription')}">{Lang::T('My_Subscription')}</a></li>
                    {/if}
                    {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active|default:false}
                    <li {if $_routes[0] eq 'superadmin' && $_routes[1] eq 'isp-settings'}class="active"{/if}><a href="{Text::url('superadmin/isp-settings')}">{Lang::T('ISP_Settings')}</a></li>
                    <li {if $_routes[0] eq 'superadmin' && $_routes[1] eq 'admin-subscriptions'}class="active"{/if}><a href="{Text::url('superadmin/admin-subscriptions')}">{Lang::T('Admin_Subscriptions')}</a></li>
                    <li {if $_routes[0] eq 'superadmin' && $_routes[1] eq 'instances'}class="active"{/if}><a href="{Text::url('superadmin/instances')}">{Lang::T('Instances')}</a></li>
                    {/if}
                </ul>
            </li>
            {/if}

            <li class="header wz-nav-section">{Lang::T('Settings')}</li>
            <li class="{if $_system_menu eq 'settings' || $_system_menu eq 'paymentgateway'}active menu-open{/if} treeview">
                <a href="#"><i class="ion ion-gear-a"></i> <span class="wz-nav-label">{Lang::T('Settings')}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                <ul class="treeview-menu">
                    {if in_array($_admin['user_type'], ['SuperAdmin','Admin'])}
                    <li {if $_routes[0] eq 'settings' && $_routes[1] eq 'hotspot'}class="active"{/if}><a href="{Text::url('settings/hotspot')}">{Lang::T('Hotspot_Settings')}</a></li>
                    {/if}
                    {if $_admin['user_type'] eq 'SuperAdmin' && !$impersonation_active|default:false}
                    <li {if $_routes[1] eq 'app'}class="active"{/if}><a href="{Text::url('settings/app')}">{Lang::T('General_Settings')}</a></li>
                    <li {if $_routes[1] eq 'miscellaneous'}class="active"{/if}><a href="{Text::url('settings/miscellaneous')}">{Lang::T('Miscellaneous')}</a></li>
                    <li {if $_routes[1] eq 'maintenance'}class="active"{/if}><a href="{Text::url('settings/maintenance')}">{Lang::T('Maintenance_Mode')}</a></li>
                    <li {if $_routes[0] eq 'widgets'}class="active"{/if}><a href="{Text::url('widgets')}">{Lang::T('Widgets')}</a></li>
                    <li {if $_routes[1] eq 'devices'}class="active"{/if}><a href="{Text::url('settings/devices')}">{Lang::T('Devices')}</a></li>
                    <li {if $_routes[1] eq 'dbstatus'}class="active"{/if}><a href="{Text::url('settings/dbstatus')}">{Lang::T('Backup/Restore')}</a></li>
                    <li {if $_system_menu eq 'paymentgateway'}class="active"{/if}><a href="{Text::url('paymentgateway')}">{Lang::T('Payment_Gateway')}</a></li>
                    {$_MENU_SETTINGS}
                    <li {if $_routes[0] eq 'pluginmanager'}class="active"{/if}><a href="{Text::url('pluginmanager')}">{Lang::T('Plugin_Manager')}</a></li>
                    {/if}
                </ul>
            </li>
            {$_MENU_AFTER_SETTINGS}

        </ul>
    </section>
</aside>
