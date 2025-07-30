<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminPanel {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
    }

    // Add main admin menu and submenus
    public function add_admin_menu() {
        add_menu_page(
            'B2B Commerce Pro',
            'B2B Commerce',
            'manage_options',
            'b2b-dashboard',
            [ $this, 'dashboard_page' ],
            'dashicons-store',
            30
        );
        
        add_submenu_page(
            'b2b-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'b2b-dashboard',
            [ $this, 'dashboard_page' ]
        );
        
        add_submenu_page(
            'b2b-dashboard',
            'User Management',
            'User Management',
            'manage_options',
            'b2b-users',
            [ $this, 'user_management_page' ]
        );
        
        add_submenu_page(
            'b2b-dashboard',
            'Add B2B User',
            'Add B2B User',
            'manage_options',
            'b2b-add-user',
            [ $this, 'add_b2b_user_page' ]
        );
        
        add_submenu_page(
            'b2b-dashboard',
            'Pricing Rules',
            'Pricing Rules',
            'manage_options',
            'b2b-pricing',
            [ $this, 'pricing_page' ]
        );
        
        add_submenu_page(
            'b2b-dashboard',
            'Order Management',
            'Order Management',
            'manage_options',
            'b2b-orders',
            [ $this, 'order_management_page' ]
        );
        
        add_submenu_page(
            'b2b-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'b2b-settings',
            [ $this, 'settings_page' ]
        );
        
        add_submenu_page(
            'b2b-dashboard',
            'Email Templates',
            'Email Templates',
            'manage_options',
            'b2b-emails',
            [ $this, 'email_templates_page' ]
        );
        
        add_submenu_page(
            'b2b-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'b2b-analytics',
            [ $this, 'analytics_page' ]
        );
        
        // System Test - Essential for CodeCanyon support
        add_submenu_page(
            'b2b-dashboard',
            'System Test',
            'System Test',
            'manage_options',
            'b2b-test',
            [ $this, 'test_page' ]
        );
    }



    // Render the admin wrapper with unified navigation
    public function render_admin_wrapper($current_page, $content) {
        echo '<div class="b2b-admin-wrapper">';
        
        // Unified Navigation Header
        echo '<div class="b2b-unified-nav">';
        echo '<div class="b2b-nav-header">';
        echo '<div class="b2b-nav-brand">';
        echo '<span class="dashicons dashicons-store" style="color: #2196f3; font-size: 1.5em; margin-right: 10px;"></span>';
        echo '<div>';
        echo '<h2 style="margin: 0; color: #23272f; font-size: 1.3em;">B2B Commerce Pro</h2>';
        echo '<small style="color: #666; font-size: 0.9em;">Premium B2B & Wholesale Solution</small>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Unified Navigation Menu
        echo '<div class="b2b-nav-menu">';
        $menu_items = [
            'b2b-dashboard' => ['Dashboard', 'dashicons-chart-area'],
            'b2b-users' => ['User Management', 'dashicons-groups'],
            'b2b-add-user' => ['Add B2B User', 'dashicons-plus'],
            'b2b-pricing' => ['Pricing Rules', 'dashicons-tag'],
            'b2b-orders' => ['Order Management', 'dashicons-cart'],
            'b2b-settings' => ['Settings', 'dashicons-admin-generic'],
            'b2b-emails' => ['Email Templates', 'dashicons-email'],
            'b2b-analytics' => ['Analytics', 'dashicons-chart-line'],
            'b2b-test' => ['System Test', 'dashicons-admin-tools']
        ];
        
        foreach ($menu_items as $page => $item) {
            $is_active = ($current_page === $page) ? 'active' : '';
            $icon = $item[1];
            $title = $item[0];
            
            echo '<a href="' . admin_url('admin.php?page=' . $page) . '" class="b2b-nav-item ' . $is_active . '">';
            echo '<span class="dashicons ' . $icon . '"></span>';
            echo '<span>' . $title . '</span>';
            echo '</a>';
        }
        echo '</div>';
        echo '</div>';
        
        // Main Content Area
        echo '<div class="b2b-admin-content">';
        echo $content;
        echo '</div>';
        
        echo '</div>';
    }

    // Dashboard page
    public function dashboard_page() {
        // Get comprehensive statistics
        $total_users = count(get_users(['role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer']]));
        $pending_users = count(get_users([
            'role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'],
            'meta_key' => 'b2b_approval_status',
            'meta_value' => 'pending'
        ]));
        $approved_users = count(get_users([
            'role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'],
            'meta_key' => 'b2b_approval_status',
            'meta_value' => 'approved'
        ]));
        $rejected_users = count(get_users([
            'role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'],
            'meta_key' => 'b2b_approval_status',
            'meta_value' => 'rejected'
        ]));
        
        // Get WooCommerce statistics
        $total_revenue = 0;
        $total_orders = 0;
        $recent_orders = [];
        $monthly_revenue = 0;
        
        if (class_exists('WooCommerce') && function_exists('wc_get_orders')) {
            $recent_orders = wc_get_orders(['limit' => 10, 'orderby' => 'date', 'order' => 'DESC']);
            $all_orders = wc_get_orders(['limit' => -1, 'status' => 'completed']);
            $total_orders = count($all_orders);
            
            foreach ($all_orders as $order) {
                $total_revenue += $order->get_total();
            }
            
            // Get monthly revenue
            $current_month = date('Y-m');
            $monthly_orders = wc_get_orders([
                'limit' => -1, 
                'status' => 'completed',
                'date_created' => '>=' . $current_month . '-01'
            ]);
            
            foreach ($monthly_orders as $order) {
                $monthly_revenue += $order->get_total();
            }
        }
        
        // Get pricing rules count
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $pricing_rules_count = 0;
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
            $pricing_rules_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        }
        
        // Get user role breakdown
        $role_breakdown = [];
        $roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
        foreach ($roles as $role) {
            $role_breakdown[$role] = count(get_users(['role' => $role]));
        }
        
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-chart-area"></span>B2B Commerce Dashboard</h1>
    <p>Welcome to B2B Commerce Pro. Monitor your business performance and manage your B2B operations.</p>
</div>

<!-- Main Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Total B2B Users</h3>
                <p style="margin: 5px 0 0 0; font-size: 2em; font-weight: bold; color: #2196f3;">' . $total_users . '</p>
                <small style="color: #666;">Active B2B customers</small>
            </div>
            <span class="dashicons dashicons-groups" style="font-size: 2.5em; color: #2196f3;"></span>
        </div>
    </div>
    
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Pending Approvals</h3>
                <p style="margin: 5px 0 0 0; font-size: 2em; font-weight: bold; color: #ff9800;">' . $pending_users . '</p>
                <small style="color: #666;">Awaiting approval</small>
            </div>
            <span class="dashicons dashicons-clock" style="font-size: 2.5em; color: #ff9800;"></span>
        </div>
    </div>
    
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Total Revenue</h3>
                <p style="margin: 5px 0 0 0; font-size: 2em; font-weight: bold; color: #9c27b0;">$' . number_format($total_revenue, 2) . '</p>
                <small style="color: #666;">All time revenue</small>
            </div>
            <span class="dashicons dashicons-chart-line" style="font-size: 2.5em; color: #9c27b0;"></span>
        </div>
    </div>
    
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Total Orders</h3>
                <p style="margin: 5px 0 0 0; font-size: 2em; font-weight: bold; color: #4caf50;">' . $total_orders . '</p>
                <small style="color: #666;">Completed orders</small>
            </div>
            <span class="dashicons dashicons-cart" style="font-size: 2.5em; color: #4caf50;"></span>
        </div>
    </div>
</div>

<!-- Secondary Statistics Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Approved Users</h3>
                <p style="margin: 5px 0 0 0; font-size: 1.5em; font-weight: bold; color: #4caf50;">' . $approved_users . '</p>
            </div>
            <span class="dashicons dashicons-yes-alt" style="font-size: 2em; color: #4caf50;"></span>
        </div>
    </div>
    
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Rejected Users</h3>
                <p style="margin: 5px 0 0 0; font-size: 1.5em; font-weight: bold; color: #f44336;">' . $rejected_users . '</p>
            </div>
            <span class="dashicons dashicons-no-alt" style="font-size: 2em; color: #f44336;"></span>
        </div>
    </div>
    
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Monthly Revenue</h3>
                <p style="margin: 5px 0 0 0; font-size: 1.5em; font-weight: bold; color: #ff9800;">$' . number_format($monthly_revenue, 2) . '</p>
            </div>
            <span class="dashicons dashicons-calendar-alt" style="font-size: 2em; color: #ff9800;"></span>
        </div>
    </div>
    
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Pricing Rules</h3>
                <p style="margin: 5px 0 0 0; font-size: 1.5em; font-weight: bold; color: #2196f3;">' . $pricing_rules_count . '</p>
            </div>
            <span class="dashicons dashicons-tag" style="font-size: 2em; color: #2196f3;"></span>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-admin-links"></span>Quick Actions</div>
    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="' . admin_url('admin.php?page=b2b-users') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-groups"></span>Manage Users</a>
        <a href="' . admin_url('admin.php?page=b2b-add-user') . '" class="b2b-admin-btn b2b-admin-btn-success"><span class="icon dashicons dashicons-plus"></span>Add New User</a>
        <a href="' . admin_url('admin.php?page=b2b-orders') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-cart"></span>View Orders</a>
        <a href="' . admin_url('admin.php?page=b2b-pricing') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-tag"></span>B2B Pricing</a>
        <a href="' . admin_url('admin.php?page=b2b-emails') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-email"></span>Email Templates</a>
        <a href="' . admin_url('admin.php?page=b2b-settings') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-admin-generic"></span>Settings</a>
        <a href="' . admin_url('admin.php?page=b2b-analytics') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-chart-line"></span>Analytics</a>
    </div>
</div>

<!-- User Role Breakdown -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-groups"></span>User Role Breakdown</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #2196f3;">B2B Customers</h4>
            <p style="margin: 0; font-size: 1.5em; font-weight: bold;">' . $role_breakdown['b2b_customer'] . '</p>
        </div>
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #4caf50;">Wholesale</h4>
            <p style="margin: 0; font-size: 1.5em; font-weight: bold;">' . $role_breakdown['wholesale_customer'] . '</p>
        </div>
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #ff9800;">Distributors</h4>
            <p style="margin: 0; font-size: 1.5em; font-weight: bold;">' . $role_breakdown['distributor'] . '</p>
        </div>
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #9c27b0;">Retailers</h4>
            <p style="margin: 0; font-size: 1.5em; font-weight: bold;">' . $role_breakdown['retailer'] . '</p>
        </div>
    </div>
</div>';

        // Add recent orders if available
        if (!empty($recent_orders)) {
            $content .= '
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-list-view"></span>Recent Orders</div>
    <table class="b2b-admin-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';
            foreach ($recent_orders as $order) {
                $status_class = $order->get_status() === 'completed' ? 'success' : ($order->get_status() === 'processing' ? 'warning' : 'danger');
                $content .= '
            <tr>
                <td><strong><a href="' . admin_url('post.php?post=' . $order->get_id() . '&action=edit') . '">#' . $order->get_id() . '</a></strong></td>
                <td>' . esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '</td>
                <td>' . esc_html($order->get_date_created()->date('Y-m-d H:i')) . '</td>
                <td><strong>' . esc_html($order->get_formatted_order_total()) . '</strong></td>
                <td><span class="b2b-badge b2b-badge-' . $status_class . '">' . esc_html(wc_get_order_status_name($order->get_status())) . '</span></td>
                <td>
                    <a href="' . admin_url('post.php?post=' . $order->get_id() . '&action=edit') . '" class="b2b-admin-btn" style="padding: 4px 8px; font-size: 0.8em;"><span class="icon dashicons dashicons-edit"></span>View</a>
                </td>
            </tr>';
            }
            $content .= '
        </tbody>
    </table>
    <div style="margin-top: 15px; text-align: center;">
        <a href="' . admin_url('edit.php?post_type=shop_order') . '" class="b2b-admin-btn">View All Orders</a>
    </div>
</div>';
        } else {
            $content .= '
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-list-view"></span>Recent Orders</div>
    <div style="text-align: center; padding: 40px 20px; color: #666;">
        <div class="b2b-empty-state">
            <span class="dashicons dashicons-cart" style="font-size: 3em; color: #ddd;"></span>
            <p>No orders found</p>
            <small>Orders will appear here once customers start placing orders</small>
        </div>
    </div>
</div>';
        }
        
        // Add system status
        $content .= '
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-admin-tools"></span>System Status</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">WooCommerce</h4>
            <p style="margin: 0; color: ' . (class_exists('WooCommerce') ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . (class_exists('WooCommerce') ? 'yes-alt' : 'no-alt') . '"></span>
                ' . (class_exists('WooCommerce') ? 'Active' : 'Not Active') . '
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">Database Table</h4>
            <p style="margin: 0; color: ' . ($pricing_rules_count >= 0 ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . ($pricing_rules_count >= 0 ? 'yes-alt' : 'no-alt') . '"></span>
                ' . ($pricing_rules_count >= 0 ? 'Ready' : 'Not Ready') . '
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">User Roles</h4>
            <p style="margin: 0; color: #4caf50;">
                <span class="dashicons dashicons-yes-alt"></span>
                Configured
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">Plugin Version</h4>
            <p style="margin: 0; color: #2196f3;">
                <span class="dashicons dashicons-info"></span>
                v' . B2B_COMMERCE_PRO_VERSION . '
            </p>
        </div>
    </div>
</div>';
        
        $this->render_admin_wrapper('b2b-dashboard', $content);
    }

    // User management page
    public function user_management_page() {
        $role = $_GET['role'] ?? '';
        $approval = $_GET['approval'] ?? '';
        $args = [ 'role__in' => [ 'b2b_customer', 'wholesale_customer', 'distributor', 'retailer' ] ];
        if ( $role ) $args['role'] = $role;
        if ( $approval ) $args['meta_key'] = 'b2b_approval_status';
        if ( $approval ) $args['meta_value'] = $approval;
        $users = get_users( $args );
        
        // Show success messages
        $success_message = '';
        if (isset($_GET['approved']) && $_GET['approved'] == '1') {
            $success_message = '<div class="b2b-admin-card" style="color: #4caf50; border-color: #4caf50;"><span class="icon dashicons dashicons-yes-alt"></span> User approved successfully!</div>';
        }
        if (isset($_GET['rejected']) && $_GET['rejected'] == '1') {
            $success_message = '<div class="b2b-admin-card" style="color: #f44336; border-color: #f44336;"><span class="icon dashicons dashicons-no-alt"></span> User rejected successfully!</div>';
        }
        
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-groups"></span>User Management</h1>
    <p>Manage B2B users, approve applications, and control user access.</p>
</div>';
        
        if ($success_message) {
            $content .= $success_message;
        }
        $content .= '
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-filter"></span>Filter Users</div>
    <form method="get" class="b2b-admin-form" style="display: flex; gap: 24px; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="page" value="b2b-users">
        <div class="b2b-admin-form-group">
            <label for="role">Role</label>
            <select name="role" id="role">
                <option value="">All Roles</option>
                <option value="b2b_customer"' . selected($role, 'b2b_customer', false) . '>B2B Customer</option>
                <option value="wholesale_customer"' . selected($role, 'wholesale_customer', false) . '>Wholesale</option>
                <option value="distributor"' . selected($role, 'distributor', false) . '>Distributor</option>
                <option value="retailer"' . selected($role, 'retailer', false) . '>Retailer</option>
            </select>
        </div>
        <div class="b2b-admin-form-group">
            <label for="approval">Approval Status</label>
            <select name="approval" id="approval">
                <option value="">All Status</option>
                <option value="pending"' . selected($approval, 'pending', false) . '>Pending</option>
                <option value="approved"' . selected($approval, 'approved', false) . '>Approved</option>
                <option value="rejected"' . selected($approval, 'rejected', false) . '>Rejected</option>
            </select>
        </div>
        <div class="b2b-admin-form-group">
            <button type="submit" class="b2b-admin-btn"><span class="icon dashicons dashicons-search"></span>Filter</button>
        </div>
    </form>
</div>
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-groups"></span>B2B Users (' . count($users) . ')</div>
    
    <!-- Add New User Button -->
    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <a href="' . admin_url('admin.php?page=b2b-add-user') . '" class="b2b-admin-btn b2b-admin-btn-success" style="text-decoration: none;">
            <span class="icon dashicons dashicons-plus"></span>Add New B2B User
        </a>
    </div>
    
    <!-- Bulk Actions -->
    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <select id="bulk-action-selector" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Bulk Actions</option>
                <option value="approve">Approve Selected</option>
                <option value="reject">Reject Selected</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button class="b2b-admin-btn disabled bulk-action-btn" style="opacity: 0.5;">Apply</button>
            <button class="b2b-admin-btn" onclick="B2BCommercePro.exportData(\'users\')" style="margin-left: auto;">
                <span class="icon dashicons dashicons-download"></span>Export Users
            </button>
        </div>
    </div>
    
    <table class="b2b-admin-table">
        <thead>
            <tr>
                <th style="width: 30px;"><input type="checkbox" id="select-all-users"></th>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Company</th>
                <th>Approval Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';
        foreach ( $users as $user ) {
            $approval_status = get_user_meta( $user->ID, 'b2b_approval_status', true ) ?: 'pending';
            $badge_class = $approval_status === 'approved' ? 'success' : ($approval_status === 'rejected' ? 'danger' : 'warning');
            $content .= '
            <tr>
                <td><input type="checkbox" class="user-checkbox" value="' . $user->ID . '"></td>
                <td><strong>' . esc_html( $user->user_login ) . '</strong></td>
                <td>' . esc_html( $user->user_email ) . '</td>
                <td>' . esc_html( implode( ', ', $user->roles ) ) . '</td>
                <td>' . esc_html( get_user_meta( $user->ID, 'company_name', true ) ) . '</td>
                <td><span class="b2b-badge b2b-badge-' . $badge_class . '">' . esc_html( $approval_status ) . '</span></td>
                <td>
                    <a href="' . admin_url('user-edit.php?user_id=' . $user->ID) . '" class="b2b-admin-btn" style="padding: 6px 12px; font-size: 0.9em;"><span class="icon dashicons dashicons-edit"></span>Edit</a>';
            
            // Add approval/rejection buttons for pending users
            if ($approval_status === 'pending') {
                $content .= '
                    <a href="' . admin_url('admin-post.php?action=b2b_approve_user&user_id=' . $user->ID . '&_wpnonce=' . wp_create_nonce('b2b_approve_user_' . $user->ID)) . '" class="b2b-admin-btn b2b-admin-btn-success" style="padding: 6px 12px; font-size: 0.9em; margin-left: 5px;"><span class="icon dashicons dashicons-yes-alt"></span>Approve</a>
                    <a href="' . admin_url('admin-post.php?action=b2b_reject_user&user_id=' . $user->ID . '&_wpnonce=' . wp_create_nonce('b2b_reject_user_' . $user->ID)) . '" class="b2b-admin-btn b2b-admin-btn-danger" style="padding: 6px 12px; font-size: 0.9em; margin-left: 5px;"><span class="icon dashicons dashicons-no-alt"></span>Reject</a>';
            }
            
            $content .= '
                </td>
            </tr>';
        }
        $content .= '
        </tbody>
    </table>
</div>';
        $this->render_admin_wrapper('b2b-users', $content);
    }

    // Add B2B User page
    public function add_b2b_user_page() {
        $success_message = '';
        $error_message = '';
        
        // Handle form submission
        if (isset($_POST['b2b_add_user_nonce']) && wp_verify_nonce($_POST['b2b_add_user_nonce'], 'b2b_add_user')) {
            $username = sanitize_user($_POST['username']);
            $email = sanitize_email($_POST['email']);
            $role = sanitize_text_field($_POST['role']);
            $company = sanitize_text_field($_POST['company']);
            $password = $_POST['password'];
            
            // Validate required fields
            if (empty($username) || empty($email) || empty($role) || empty($password)) {
                $error_message = '<div class="b2b-admin-card" style="color: #dc3545;"><span class="icon dashicons dashicons-no-alt"></span> All fields are required!</div>';
            } elseif (!is_email($email)) {
                $error_message = '<div class="b2b-admin-card" style="color: #dc3545;"><span class="icon dashicons dashicons-no-alt"></span> Please enter a valid email address!</div>';
            } elseif (username_exists($username)) {
                $error_message = '<div class="b2b-admin-card" style="color: #dc3545;"><span class="icon dashicons dashicons-no-alt"></span> Username already exists!</div>';
            } elseif (email_exists($email)) {
                $error_message = '<div class="b2b-admin-card" style="color: #dc3545;"><span class="icon dashicons dashicons-no-alt"></span> Email already exists!</div>';
            } else {
                // Create the user
                $user_id = wp_create_user($username, $password, $email);
                
                if (is_wp_error($user_id)) {
                    $error_message = '<div class="b2b-admin-card" style="color: #dc3545;"><span class="icon dashicons dashicons-no-alt"></span> Error creating user: ' . $user_id->get_error_message() . '</div>';
                } else {
                    // Set the role
                    $user = new WP_User($user_id);
                    $user->set_role($role);
                    
                    // Add company name
                    update_user_meta($user_id, 'company_name', $company);
                    
                    // Set approval status
                    update_user_meta($user_id, 'b2b_approval_status', 'approved');
                    
                    $success_message = '<div class="b2b-admin-card" style="color: #2196f3;"><span class="icon dashicons dashicons-yes-alt"></span> B2B user created successfully!</div>';
                }
            }
        }
        
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-plus"></span>Add New B2B User</h1>
    <p>Create a new B2B user account with specific role and permissions.</p>
</div>';
        
        if ($error_message) {
            $content .= $error_message;
        }
        
        if ($success_message) {
            $content .= $success_message;
        }
        
        $content .= '
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-plus"></span>Create B2B User</div>
    <form method="post" class="b2b-admin-form">
        ' . wp_nonce_field('b2b_add_user', 'b2b_add_user_nonce', true, false) . '
        
        <div class="b2b-admin-form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="b2b-admin-form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="b2b-admin-form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="b2b-admin-form-group">
            <label for="role">B2B Role *</label>
            <select id="role" name="role" required>
                <option value="">Select Role</option>
                <option value="b2b_customer">B2B Customer</option>
                <option value="wholesale_customer">Wholesale Customer</option>
                <option value="distributor">Distributor</option>
                <option value="retailer">Retailer</option>
            </select>
        </div>
        
        <div class="b2b-admin-form-group">
            <label for="company">Company Name</label>
            <input type="text" id="company" name="company">
        </div>
        
        <button type="submit" class="b2b-admin-btn"><span class="icon dashicons dashicons-plus"></span>Create B2B User</button>
    </form>
</div>';
        
        $this->render_admin_wrapper('b2b-add-user', $content);
    }

    // Order management page
    public function order_management_page() {
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-cart"></span>Order Management</h1>
    <p>Monitor and manage B2B orders, track status, and view order details.</p>
</div>
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-list-view"></span>Recent Orders (0)</div>
    <table class="b2b-admin-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Customer</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3" style="text-align: center; padding: 40px 20px; color: #666;">
                    <div class="b2b-empty-state">
                        <span class="dashicons dashicons-cart"></span>
                        <p>No orders found</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>';
        $this->render_admin_wrapper('b2b-orders', $content);
    }

    // B2B Pricing page
    public function pricing_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        
        // Get existing rules
        $rules = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
        
        // Handle form submission
        if (isset($_POST['b2b_pricing_nonce']) && wp_verify_nonce($_POST['b2b_pricing_nonce'], 'b2b_pricing_action')) {
            $this->save_pricing_rule();
        }
        
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-tag"></span>B2B Pricing Management</h1>
    <p>Set up wholesale pricing for different customer types.</p>
</div>

<!-- Quick Pricing Setup -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-plus"></span>Add Pricing Rule</div>
    <form method="post" class="b2b-admin-form">
        ' . wp_nonce_field('b2b_pricing_action', 'b2b_pricing_nonce', true, false) . '
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="b2b-admin-form-group">
                <label for="role">Customer Type *</label>
                <select name="role" id="role" required>
                    <option value="">Choose customer type</option>
                    <option value="wholesale_customer">Wholesale Customer</option>
                    <option value="distributor">Distributor</option>
                    <option value="retailer">Retailer</option>
                </select>
            </div>
            
            <div class="b2b-admin-form-group">
                <label for="type">Pricing Type *</label>
                <select name="type" id="type" required>
                    <option value="percentage">Percentage Discount</option>
                    <option value="fixed">Fixed Price</option>
                </select>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="b2b-admin-form-group">
                <label for="price">Value *</label>
                <input type="number" name="price" id="price" step="0.01" required placeholder="Enter value">
                <small>For discount: use negative number (e.g., -15 for 15% off)</small>
            </div>
            
            <div class="b2b-admin-form-group">
                <label for="min_qty">Min Quantity</label>
                <input type="number" name="min_qty" id="min_qty" min="1" value="1" placeholder="1">
                <small>Minimum order quantity required</small>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="b2b-admin-btn"><span class="icon dashicons dashicons-saved"></span>Save Pricing Rule</button>
        </div>
    </form>
</div>

        <!-- Current Pricing Rules -->
        <div class="b2b-admin-card">
            <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-list-view"></span>Current Pricing Rules (' . count($rules) . ')</div>';
            
            if (empty($rules)) {
                $content .= '<p style="text-align: center; color: #666; padding: 20px;">No pricing rules found. Add your first rule above.</p>';
            } else {
                $content .= '
        <table class="b2b-admin-table">
            <thead>
                <tr>
                    <th>Customer Type</th>
                    <th>Pricing Type</th>
                    <th>Value</th>
                    <th>Min Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';
                
                foreach ($rules as $rule) {
                    $value_display = $rule->type === 'percentage' ? abs($rule->price) . '% discount' : wc_price($rule->price);
                    
                    $content .= '
                <tr>
                    <td>' . esc_html(ucfirst(str_replace('_', ' ', $rule->role))) . '</td>
                    <td>' . esc_html(ucfirst($rule->type)) . '</td>
                    <td>' . esc_html($value_display) . '</td>
                    <td>' . esc_html($rule->min_qty ?: 'Any') . '</td>
                    <td>
                        <a href="' . admin_url('admin-post.php?action=b2b_delete_pricing_rule&id=' . $rule->id . '&_wpnonce=' . wp_create_nonce('b2b_delete_pricing_rule')) . '" class="b2b-admin-btn b2b-admin-btn-danger" style="padding: 6px 12px; font-size: 0.9em;" onclick="return confirm(\'Delete this pricing rule?\')"><span class="icon dashicons dashicons-trash"></span>Delete</a>
                    </td>
                </tr>';
                }
                
                $content .= '
            </tbody>
        </table>';
            }
            
            $content .= '
        </div>
        
        <!-- Simple Help -->
        <div class="b2b-admin-card">
            <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-info"></span>How It Works</div>
            <div style="padding: 20px;">
                <h4>Simple Pricing Rules</h4>
                <p>Set different prices for different customer types:</p>
                <ul style="margin-left: 20px;">
                    <li><strong>Wholesale Customer:</strong> Usually gets 10-20% discount</li>
                    <li><strong>Distributor:</strong> Usually gets 20-30% discount</li>
                    <li><strong>Retailer:</strong> Usually gets 5-15% discount</li>
                </ul>
                
                <h4>Example Values</h4>
                <ul style="margin-left: 20px;">
                    <li><strong>Percentage Discount:</strong> Enter -15 for 15% off</li>
                    <li><strong>Fixed Price:</strong> Enter 19.99 for exact price</li>
                    <li><strong>Min Quantity:</strong> Set minimum order amount</li>
                </ul>
            </div>
        </div>';
        
        $this->render_admin_wrapper('b2b-pricing', $content);
    }
    
    // Save pricing rule with comprehensive error handling
    private function save_pricing_rule() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>WooCommerce is required for pricing rules.</p></div>';
            return;
        }

        // Verify nonce and permissions
        if (!isset($_POST['b2b_pricing_nonce']) || !wp_verify_nonce($_POST['b2b_pricing_nonce'], 'b2b_pricing_action')) {
            echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
            return;
        }

        if (!current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p>You do not have permission to perform this action.</p></div>';
            return;
        }

        // Validate required fields
        $required_fields = ['role', 'type', 'price', 'min_qty'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        if (!empty($errors)) {
            echo '<div class="notice notice-error"><p>' . implode('<br>', $errors) . '</p></div>';
            return;
        }

        // Sanitize and validate data
        $role = sanitize_text_field($_POST['role']);
        $type = sanitize_text_field($_POST['type']);
        $price = floatval($_POST['price']);
        $min_qty = intval($_POST['min_qty']);

        // Validate price
        if ($price == 0 && $type === 'percentage') {
            echo '<div class="notice notice-error"><p>Price cannot be zero for percentage discounts.</p></div>';
            return;
        }

        // Validate minimum quantity
        if ($min_qty < 1) {
            echo '<div class="notice notice-error"><p>Minimum quantity must be at least 1.</p></div>';
            return;
        }

        // Insert into database with error handling
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        
        // Ensure table exists
        if (!$this->ensure_pricing_table_exists()) {
            echo '<div class="notice notice-error"><p>Database table could not be created. Please check your database permissions.</p></div>';
            return;
        }

        $data = array(
            'product_id' => 0,
            'role' => $role,
            'user_id' => 0,
            'group_id' => 0,
            'geo_zone' => '',
            'start_date' => '',
            'end_date' => '',
            'min_qty' => $min_qty,
            'max_qty' => 0,
            'price' => $price,
            'type' => $type
        );

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            echo '<div class="notice notice-error"><p>Failed to save pricing rule. Database error: ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Pricing rule saved successfully!</p></div>';
        }
    }

    // Ensure pricing table exists
    private function ensure_pricing_table_exists() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        
        // Check if table exists
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        
        if ($exists !== $table) {
            // Try to create table
            if (class_exists('B2B\\PricingManager')) {
                B2B\PricingManager::create_pricing_table();
                
                // Check again
                $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
                return $exists === $table;
            }
            return false;
        }
        
        return true;
    }

    // Email template customization page
    public function email_templates_page() {
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-email"></span>Email Templates</h1>
    <p>Customize email notifications for B2B users and administrators.</p>
</div>
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-email"></span>Email Templates</div>
    <p>Email template functionality is coming soon. For now, you can use WordPress default email settings.</p>
    
    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <h3>Available Email Types:</h3>
        <ul style="list-style: disc; margin-left: 20px;">
            <li>User Approval Notification</li>
            <li>User Rejection Notification</li>
            <li>Welcome Email for New B2B Users</li>
            <li>Order Status Updates</li>
            <li>Pricing Change Notifications</li>
        </ul>
    </div>
</div>';
        
        $this->render_admin_wrapper('b2b-emails', $content);
    }

    // Settings page
    public function settings_page() {
        $opts = get_option( 'b2b_general_settings', [] );
        
        // Handle form submission
        if (isset($_POST['b2b_settings_nonce']) && wp_verify_nonce($_POST['b2b_settings_nonce'], 'b2b_settings')) {
            $new_settings = [
                'company_required' => isset($_POST['b2b_general_settings']['company_required']) ? 1 : 0,
                'auto_approve' => isset($_POST['b2b_general_settings']['auto_approve']) ? 1 : 0,
                'require_tax_id' => isset($_POST['b2b_general_settings']['require_tax_id']) ? 1 : 0
            ];
            
            update_option('b2b_general_settings', $new_settings);
            $opts = $new_settings;
            $success_message = '<div class="b2b-admin-card" style="color: #2196f3; border-color: #2196f3; background: #f8f9fa;"><span class="icon dashicons dashicons-yes-alt"></span> Settings saved successfully!</div>';
        }
        
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-admin-generic"></span>Settings</h1>
    <p>Configure B2B Commerce Pro settings and customize your B2B experience.</p>
</div>';
        
        if (isset($success_message)) {
            $content .= $success_message;
        }
        
        $content .= '
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-admin-generic"></span>General Settings</div>
    
    <form method="post" class="b2b-admin-form">
        ' . wp_nonce_field('b2b_settings', 'b2b_settings_nonce', true, false) . '
        
        <div class="b2b-admin-form-group">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f8f9fa; border-radius: 6px; margin-bottom: 15px;">
                <div>
                    <label for="b2b_company_required" style="margin: 0; font-weight: 600; color: #23272f;">Require Company Name</label>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Yes, require company name during registration</p>
                </div>
                <label class="b2b-admin-toggle">
                    <input type="checkbox" id="b2b_company_required" name="b2b_general_settings[company_required]" value="1" ' . checked( $opts['company_required'] ?? '', 1, false ) . '>
                    <span class="b2b-admin-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="b2b-admin-form-group">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f8f9fa; border-radius: 6px; margin-bottom: 15px;">
                <div>
                    <label for="b2b_auto_approve" style="margin: 0; font-weight: 600; color: #23272f;">Auto-approve New Users</label>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Automatically approve new B2B registrations</p>
                </div>
                <label class="b2b-admin-toggle">
                    <input type="checkbox" id="b2b_auto_approve" name="b2b_general_settings[auto_approve]" value="1" ' . checked( $opts['auto_approve'] ?? '', 1, false ) . '>
                    <span class="b2b-admin-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="b2b-admin-form-group">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f8f9fa; border-radius: 6px; margin-bottom: 15px;">
                <div>
                    <label for="b2b_require_tax_id" style="margin: 0; font-weight: 600; color: #23272f;">Require Tax ID</label>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Require tax ID during registration</p>
                </div>
                <label class="b2b-admin-toggle">
                    <input type="checkbox" id="b2b_require_tax_id" name="b2b_general_settings[require_tax_id]" value="1" ' . checked( $opts['require_tax_id'] ?? '', 1, false ) . '>
                    <span class="b2b-admin-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <button type="submit" class="b2b-admin-btn"><span class="icon dashicons dashicons-saved"></span>Save Changes</button>
        </div>
    </form>
</div>

<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-info"></span>Settings Information</div>
    <div style="padding: 20px; background: #f8f9fa; border-radius: 6px;">
        <h4 style="margin: 0 0 15px 0; color: #23272f;">About These Settings</h4>
        <ul style="margin: 0; padding-left: 20px; color: #666;">
            <li><strong>Company Name:</strong> When enabled, users must provide their company name during registration</li>
            <li><strong>Auto-approve:</strong> When enabled, new B2B registrations are automatically approved</li>
            <li><strong>Tax ID:</strong> When enabled, users must provide their tax ID during registration</li>
        </ul>
    </div>
</div>';
        
        $this->render_admin_wrapper('b2b-settings', $content);
    }

    public function register_settings() {
        // Settings are now handled directly in settings_page()
        // No need for WordPress settings API registration
    }
    
    public function field_company_required() {
        // This method is no longer needed
        // Settings are handled directly in settings_page()
    }
    
    public function validate_settings( $input ) {
        // This method is no longer needed
        // Settings validation is handled directly in settings_page()
        return $input;
    }
    
    // Analytics page
    public function analytics_page() {
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-chart-line"></span>Analytics</h1>
    <p>Track B2B performance metrics, sales analytics, and business insights.</p>
</div>
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-chart-line"></span>Analytics Dashboard</div>
    <p>Advanced analytics and reporting features are coming soon. This will include:</p>
    <ul style="list-style: disc; margin-left: 20px;">
        <li>Sales Performance Metrics</li>
        <li>Customer Behavior Analysis</li>
        <li>Revenue Tracking</li>
        <li>Order Analytics</li>
        <li>User Engagement Reports</li>
    </ul>
</div>';
        
        $this->render_admin_wrapper('b2b-analytics', $content);
    }

    // Test page
    public function test_page() {
        // Run comprehensive tests
        $tests = $this->run_system_tests();
        
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-admin-tools"></span>System Test</h1>
    <p>Run various checks to ensure B2B Commerce Pro is functioning correctly.</p>
</div>

<!-- System Status Overview -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-admin-tools"></span>System Status Overview</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">WooCommerce</h4>
            <p style="margin: 0; color: ' . ($tests['woocommerce']['status'] === 'OK' ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . ($tests['woocommerce']['status'] === 'OK' ? 'yes-alt' : 'no-alt') . '"></span>
                ' . $tests['woocommerce']['message'] . '
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">Database Table</h4>
            <p style="margin: 0; color: ' . ($tests['database']['status'] === 'OK' ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . ($tests['database']['status'] === 'OK' ? 'yes-alt' : 'no-alt') . '"></span>
                ' . $tests['database']['message'] . '
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">User Roles</h4>
            <p style="margin: 0; color: ' . ($tests['user_roles']['status'] === 'OK' ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . ($tests['user_roles']['status'] === 'OK' ? 'yes-alt' : 'no-alt') . '"></span>
                ' . $tests['user_roles']['message'] . '
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">Plugin Version</h4>
            <p style="margin: 0; color: #2196f3;">
                <span class="dashicons dashicons-info"></span>
                v' . B2B_COMMERCE_PRO_VERSION . '
            </p>
        </div>
    </div>
</div>

<!-- Detailed Test Results -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-list-view"></span>Detailed Test Results</div>
    <table class="b2b-admin-table">
        <thead>
            <tr>
                <th>Component</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($tests['detailed'] as $component => $test) {
            $status_color = $test['status'] === 'OK' ? '#4caf50' : '#f44336';
            $status_icon = $test['status'] === 'OK' ? 'yes-alt' : 'no-alt';
            
            $content .= '
            <tr>
                <td><strong>' . esc_html($component) . '</strong></td>
                <td><span style="color: ' . $status_color . ';"><span class="dashicons dashicons-' . $status_icon . '"></span> ' . $test['status'] . '</span></td>
                <td>' . esc_html($test['message']) . '</td>
            </tr>';
        }
        
        $content .= '
        </tbody>
    </table>
</div>

<!-- Quick Actions -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-admin-links"></span>Quick Actions</div>
    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="' . admin_url('admin.php?page=b2b-dashboard') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-chart-area"></span>Dashboard</a>
        <a href="' . admin_url('admin.php?page=b2b-users') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-groups"></span>Manage Users</a>
        <a href="' . admin_url('admin.php?page=b2b-add-user') . '" class="b2b-admin-btn b2b-admin-btn-success"><span class="icon dashicons dashicons-plus"></span>Add New User</a>
        <a href="' . admin_url('admin.php?page=b2b-pricing') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-tag"></span>B2B Pricing</a>
        <a href="' . admin_url('admin.php?page=b2b-settings') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-admin-generic"></span>Settings</a>
    </div>
</div>

<!-- Troubleshooting Guide -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-info"></span>Troubleshooting Guide</div>
    <div style="padding: 20px; background: #f8f9fa; border-radius: 6px;">
        <h4 style="margin: 0 0 15px 0; color: #23272f;">Common Issues & Solutions</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <h5 style="margin: 0 0 10px 0; color: #f44336;">❌ WooCommerce Not Active</h5>
                <p style="margin: 0; font-size: 0.9em; color: #666;">Solution: Install and activate WooCommerce plugin</p>
            </div>
            <div>
                <h5 style="margin: 0 0 10px 0; color: #ff9800;">⚠️ Database Table Missing</h5>
                <p style="margin: 0; font-size: 0.9em; color: #666;">Solution: Deactivate and reactivate the plugin</p>
            </div>
            <div>
                <h5 style="margin: 0 0 10px 0; color: #2196f3;">ℹ️ User Roles Not Created</h5>
                <p style="margin: 0; font-size: 0.9em; color: #666;">Solution: Check plugin activation and permissions</p>
            </div>
        </div>
    </div>
</div>';
        
        $this->render_admin_wrapper('b2b-test', $content);
    }
    
    // Run comprehensive system tests
    private function run_system_tests() {
        $tests = [];
        
        // Test WooCommerce
        $tests['woocommerce'] = [
            'status' => class_exists('WooCommerce') ? 'OK' : 'FAILED',
            'message' => class_exists('WooCommerce') ? 'Active' : 'Not Active'
        ];
        
        // Test Database
        $db_ready = $this->ensure_pricing_table_exists();
        $tests['database'] = [
            'status' => $db_ready ? 'OK' : 'FAILED',
            'message' => $db_ready ? 'Ready' : 'Not Ready'
        ];
        
        // Test User Roles
        $roles_exist = true;
        $required_roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
        foreach ($required_roles as $role) {
            if (!get_role($role)) {
                $roles_exist = false;
                break;
            }
        }
        $tests['user_roles'] = [
            'status' => $roles_exist ? 'OK' : 'FAILED',
            'message' => $roles_exist ? 'Configured' : 'Missing Roles'
        ];
        
        // Detailed tests
        $tests['detailed'] = [
            'Plugin Files' => [
                'status' => file_exists(B2B_COMMERCE_PRO_PATH . 'b2b-commerce-pro.php') ? 'OK' : 'FAILED',
                'message' => 'Main plugin file check'
            ],
            'Admin Panel' => [
                'status' => class_exists('B2B\\AdminPanel') ? 'OK' : 'FAILED',
                'message' => 'Admin panel class loaded'
            ],
            'CSS Files' => [
                'status' => file_exists(B2B_COMMERCE_PRO_PATH . 'assets/css/b2b-admin-standalone-demo.css') ? 'OK' : 'FAILED',
                'message' => 'Admin CSS files present'
            ],
            'JS Files' => [
                'status' => file_exists(B2B_COMMERCE_PRO_PATH . 'assets/js/b2b-commerce-pro.js') ? 'OK' : 'FAILED',
                'message' => 'Admin JavaScript files present'
            ],
            'Database Permissions' => [
                'status' => current_user_can('manage_options') ? 'OK' : 'FAILED',
                'message' => 'Admin permissions check'
            ],
            'AJAX Endpoints' => [
                'status' => has_action('wp_ajax_b2b_approve_user') ? 'OK' : 'FAILED',
                'message' => 'AJAX handlers registered'
            ]
        ];
        
        return $tests;
    }
} 