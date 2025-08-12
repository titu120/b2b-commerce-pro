<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminPanel {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_notices', [ $this, 'show_admin_notifications' ] );
        add_action( 'wp_ajax_b2b_dismiss_notification', [ $this, 'dismiss_notification' ] );
        add_action( 'admin_post_b2b_update_quote', [ $this, 'handle_update_quote' ] );
        add_action( 'wp_ajax_b2b_update_quote_ajax', [ $this, 'handle_update_quote_ajax' ] );
        add_action( 'admin_post_b2b_update_inquiry', [ $this, 'handle_update_inquiry' ] );
        add_action( 'admin_post_b2b_delete_inquiry', [ $this, 'handle_delete_inquiry' ] );
        add_action( 'admin_post_b2b_migrate_quotes', [ $this, 'handle_migrate_quotes' ] );
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
        
        // Dashboard is the main page, so we don't need a duplicate submenu
        // The main menu page will show the dashboard content
        
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

        // Quotes Management
        add_submenu_page(
            'b2b-dashboard',
            'Quotes',
            'Quotes',
            'manage_options',
            'b2b-quotes',
            [ $this, 'quotes_page' ]
        );
        
        // Product Inquiries Management
        add_submenu_page(
            'b2b-dashboard',
            'Product Inquiries',
            'Product Inquiries',
            'manage_options',
            'b2b-inquiries',
            [ $this, 'inquiries_page' ]
        );
         


        
        add_submenu_page(
            'b2b-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'b2b-settings',
            [ $this, 'settings_page' ]
        );

        // Catalog Mode
        add_submenu_page(
            'b2b-dashboard',
            'Catalog Mode',
            'Catalog Mode',
            'manage_options',
            'b2b-catalog',
            [ $this, 'catalog_mode_page' ]
        );

        // Checkout Controls
        add_submenu_page(
            'b2b-dashboard',
            'Checkout Controls',
            'Checkout Controls',
            'manage_options',
            'b2b-checkout-controls',
            [ $this, 'checkout_controls_page' ]
        );

        // VAT Settings
        add_submenu_page(
            'b2b-dashboard',
            'VAT Settings',
            'VAT Settings',
            'manage_options',
            'b2b-vat',
            [ $this, 'vat_settings_page' ]
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
        
        add_submenu_page(
            'b2b-dashboard',
            'Import/Export',
            'Import/Export',
            'manage_options',
            'b2b-import-export',
            [ $this, 'import_export_page' ]
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
        
        // Get notification counts
        $quotes = get_option('b2b_quote_requests', []);
        $inquiries = get_option('b2b_product_inquiries', []);
        $pending_quotes = 0;
        $pending_inquiries = 0;
        
        foreach ($quotes as $quote) {
            if (($quote['status'] ?? 'pending') === 'pending') {
                $pending_quotes++;
            }
        }
        
        foreach ($inquiries as $inquiry) {
            if (($inquiry['status'] ?? 'pending') === 'pending') {
                $pending_inquiries++;
            }
        }
        
        // Keep this list in sync with the WP admin submenu for consistency
        $menu_items = [
            'b2b-dashboard' => ['Dashboard', 'dashicons-chart-area', 0],
            'b2b-users' => ['User Management', 'dashicons-groups', 0],
            'b2b-add-user' => ['Add B2B User', 'dashicons-plus', 0],
            'b2b-pricing' => ['Pricing Rules', 'dashicons-tag', 0],
            'b2b-orders' => ['Order Management', 'dashicons-cart', 0],
            'b2b-quotes' => ['Quotes', 'dashicons-email-alt', $pending_quotes],
            'b2b-inquiries' => ['Product Inquiries', 'dashicons-format-chat', $pending_inquiries],
            'b2b-settings' => ['Settings', 'dashicons-admin-generic', 0],
            'b2b-catalog' => ['Catalog Mode', 'dashicons-visibility', 0],
            'b2b-checkout-controls' => ['Checkout Controls', 'dashicons-admin-settings', 0],
            'b2b-vat' => ['VAT Settings', 'dashicons-universal-access-alt', 0],
            'b2b-emails' => ['Email Templates', 'dashicons-email', 0],
            'b2b-analytics' => ['Analytics', 'dashicons-chart-line', 0],
            'b2b-import-export' => ['Import/Export', 'dashicons-upload', 0],
            'b2b-test' => ['System Test', 'dashicons-admin-tools', 0],
        ];
        
        foreach ($menu_items as $page => $item) {
            $is_active = ($current_page === $page) ? 'active' : '';
            $icon = $item[1];
            $title = $item[0];
            $badge_count = $item[2];
            
            echo '<a href="' . esc_url( admin_url('admin.php?page=' . $page) ) . '" class="b2b-nav-item ' . $is_active . '">';
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
            try {
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
            } catch (Exception $e) {
                // Log error but don't break the dashboard
                error_log('B2B Commerce Pro WooCommerce Error: ' . $e->getMessage());
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
        
        // Get quotes and inquiries counts
        $quotes = get_option('b2b_quote_requests', []);
        $inquiries = get_option('b2b_product_inquiries', []);
        $pending_quotes = 0;
        $pending_inquiries = 0;
        
        foreach ($quotes as $quote) {
            if (($quote['status'] ?? 'pending') === 'pending') {
                $pending_quotes++;
            }
        }
        
        foreach ($inquiries as $inquiry) {
            if (($inquiry['status'] ?? 'pending') === 'pending') {
                $pending_inquiries++;
            }
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
    
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Pending Quotes</h3>
                <p style="margin: 5px 0 0 0; font-size: 1.5em; font-weight: bold; color: #ff9800;">' . $pending_quotes . '</p>
            </div>
            <span class="dashicons dashicons-email-alt" style="font-size: 2em; color: #ff9800;"></span>
        </div>
    </div>
    
    <div class="b2b-admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: #666; font-size: 0.9em;">Pending Inquiries</h3>
                <p style="margin: 5px 0 0 0; font-size: 1.5em; font-weight: bold; color: #e91e63;">' . $pending_inquiries . '</p>
            </div>
            <span class="dashicons dashicons-format-chat" style="font-size: 2em; color: #e91e63;"></span>
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
        <a href="' . admin_url('admin.php?page=b2b-quotes') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-email-alt"></span>Manage Quotes</a>
        <a href="' . admin_url('admin.php?page=b2b-inquiries') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-format-chat"></span>Product Inquiries</a>
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

    // Simple wrappers to reuse main settings sections (for sidebar entries)
    public function catalog_mode_page() {
        if (!current_user_can('manage_options')) return;
        $opts = get_option('b2b_catalog_mode', []);
        if (isset($_POST['b2b_catalog_nonce']) && wp_verify_nonce($_POST['b2b_catalog_nonce'], 'b2b_catalog_mode')) {
            $opts = [
                'hide_prices_guests' => isset($_POST['b2b_catalog_mode']['hide_prices_guests']) ? 1 : 0,
                'disable_add_to_cart' => isset($_POST['b2b_catalog_mode']['disable_add_to_cart']) ? 1 : 0,
                'force_quote_mode' => isset($_POST['b2b_catalog_mode']['force_quote_mode']) ? 1 : 0,
            ];
            update_option('b2b_catalog_mode', $opts);
            echo '<div class="b2b-admin-card" style="color:#2196f3;">Catalog mode saved.</div>';
        }
        $content = '<div class="b2b-admin-header"><h1><span class="icon dashicons dashicons-visibility"></span>Catalog Mode</h1><p>Control price visibility and purchasing.</p></div>';
        $content .= '<div class="b2b-admin-card"><form method="post" class="b2b-admin-form">' . wp_nonce_field('b2b_catalog_mode', 'b2b_catalog_nonce', true, false);

        // Row 1
        $content .= '<div style="display:flex; align-items:center; justify-content:space-between; padding:15px; background:#f8f9fa; border-radius:6px; margin-bottom:12px;">';
        $content .= '<div><label style="margin:0; font-weight:600; color:#23272f;">Hide prices for guests</label><p style="margin:5px 0 0 0; color:#666; font-size:0.9em;">Guests will see "Login to see prices" instead of price.</p></div>';
        $content .= '<label class="b2b-admin-toggle"><input type="checkbox" name="b2b_catalog_mode[hide_prices_guests]" value="1" ' . checked($opts['hide_prices_guests'] ?? '', 1, false) . '><span class="b2b-admin-toggle-slider"></span></label>';
        $content .= '</div>';

        // Row 2
        $content .= '<div style="display:flex; align-items:center; justify-content:space-between; padding:15px; background:#f8f9fa; border-radius:6px; margin-bottom:12px;">';
        $content .= '<div><label style="margin:0; font-weight:600; color:#23272f;">Disable add to cart for guests</label><p style="margin:5px 0 0 0; color:#666; font-size:0.9em;">Prevents guests from purchasing until they log in.</p></div>';
        $content .= '<label class="b2b-admin-toggle"><input type="checkbox" name="b2b_catalog_mode[disable_add_to_cart]" value="1" ' . checked($opts['disable_add_to_cart'] ?? '', 1, false) . '><span class="b2b-admin-toggle-slider"></span></label>';
        $content .= '</div>';

        // Row 3
        $content .= '<div style="display:flex; align-items:center; justify-content:space-between; padding:15px; background:#f8f9fa; border-radius:6px;">';
        $content .= '<div><label style="margin:0; font-weight:600; color:#23272f;">Force Quote Mode (site‑wide)</label><p style="margin:5px 0 0 0; color:#666; font-size:0.9em;">Disables add to cart for everyone; customers can request quotes instead.</p></div>';
        $content .= '<label class="b2b-admin-toggle"><input type="checkbox" name="b2b_catalog_mode[force_quote_mode]" value="1" ' . checked($opts['force_quote_mode'] ?? '', 1, false) . '><span class="b2b-admin-toggle-slider"></span></label>';
        $content .= '</div>';

        $content .= '<div style="margin-top:15px;"><button class="b2b-admin-btn" type="submit"><span class="icon dashicons dashicons-saved"></span>Save</button></div></form></div>';
        $this->render_admin_wrapper('b2b-catalog', $content);
    }

    public function checkout_controls_page() {
        if (!current_user_can('manage_options')) return;
        $role_payment = get_option('b2b_role_payment_methods', []);
        $role_shipping = get_option('b2b_role_shipping_methods', []);
        if (isset($_POST['b2b_checkout_controls_nonce']) && wp_verify_nonce($_POST['b2b_checkout_controls_nonce'], 'b2b_checkout_controls')) {
            $roles = ['b2b_customer','wholesale_customer','distributor','retailer'];
            $rp = [];$rs = [];
            foreach ($roles as $role) {
                $rp[$role] = isset($_POST['b2b_role_payment_methods'][$role]) ? array_map('sanitize_text_field', (array) $_POST['b2b_role_payment_methods'][$role]) : [];
                $rs[$role] = isset($_POST['b2b_role_shipping_methods'][$role]) ? array_map('sanitize_text_field', (array) $_POST['b2b_role_shipping_methods'][$role]) : [];
            }
            update_option('b2b_role_payment_methods', $rp);
            update_option('b2b_role_shipping_methods', $rs);
            $role_payment = $rp; $role_shipping = $rs;
            echo '<div class="b2b-admin-card" style="color:#2196f3;">Checkout controls saved.</div>';
        }
        $content = '<div class="b2b-admin-header"><h1><span class="icon dashicons dashicons-admin-settings"></span>Checkout Controls</h1><p>Restrict checkout methods per role.</p></div>';
        $content .= '<div class="b2b-admin-card" style="max-width: 1100px;"><form method="post" class="b2b-admin-form">' . wp_nonce_field('b2b_checkout_controls', 'b2b_checkout_controls_nonce', true, false);
        if ( class_exists('WC_Payment_Gateways') ) {
            // In admin, get_available_payment_gateways() can be empty. Collect all registered gateways.
            $gateways = [];
            if ( function_exists('WC') && WC()->payment_gateways() ) {
                $gateways = WC()->payment_gateways->payment_gateways(); // all gateways
            }
            if ( empty($gateways) ) {
                $gateways = \WC_Payment_Gateways::instance()->payment_gateways();
            }

            $gateway_titles = [];
            foreach ($gateways as $gid => $gateway) { $gateway_titles[$gid] = $gateway->get_title(); }
            $roles = ['b2b_customer'=>'B2B Customer','wholesale_customer'=>'Wholesale','distributor'=>'Distributor','retailer'=>'Retailer'];
            $content .= '<h3 style="margin-top:0;">Payment Gateways</h3>';
            if (empty($gateway_titles)) {
                $content .= '<p style="color:#666;">No gateways found. Enable them in <a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">WooCommerce → Payments</a>.</p>';
            } else {
                $content .= '<table class="b2b-admin-table b2b-matrix-table"><thead><tr><th>Role</th>';
                foreach ($gateway_titles as $gid => $title) {
                    $content .= '<th>' . esc_html($title) . '</th>';
                }
                $content .= '</tr></thead><tbody>';
                foreach ($roles as $role_key => $role_label) {
                    $content .= '<tr><td><strong>' . esc_html($role_label) . '</strong></td>';
                    foreach ($gateway_titles as $gid => $title) {
                        $checked = in_array($gid, $role_payment[$role_key] ?? []) ? 'checked' : '';
                        $content .= '<td style="text-align:center;">'
                                  . '<input type="checkbox" name="b2b_role_payment_methods[' . esc_attr($role_key) . '][]" value="' . esc_attr($gid) . '" ' . $checked . '>'
                                  . '</td>';
                    }
                    $content .= '</tr>';
                }
                $content .= '</tbody></table>';
            }
        }

        if ( class_exists('WC_Shipping') ) {
            $shipping_methods = \WC_Shipping::instance()->get_shipping_methods();
            // Map to base IDs and titles (flat_rate, free_shipping, local_pickup)
            $method_titles = [];
            foreach ($shipping_methods as $mid => $method) { $method_titles[$method->id] = $method->get_method_title(); }
            // Keep only common core methods in predictable order if present
            $order = ['flat_rate','free_shipping','local_pickup'];
            $ordered = [];
            foreach ($order as $id) { if (isset($method_titles[$id])) { $ordered[$id] = $method_titles[$id]; } }
            // append any others
            foreach ($method_titles as $id => $title) { if (!isset($ordered[$id])) { $ordered[$id] = $title; } }
            $roles = ['b2b_customer'=>'B2B Customer','wholesale_customer'=>'Wholesale','distributor'=>'Distributor','retailer'=>'Retailer'];
            $content .= '<h3 style="margin-top:24px;">Shipping Methods</h3>';
            if (empty($ordered)) {
                $content .= '<p style="color:#666;">No shipping methods found. Configure them in <a href="' . admin_url('admin.php?page=wc-settings&tab=shipping') . '">WooCommerce → Shipping</a>.</p>';
            } else {
                $content .= '<table class="b2b-admin-table b2b-matrix-table"><thead><tr><th>Role</th>';
                foreach ($ordered as $id => $title) { $content .= '<th>' . esc_html($title) . '</th>'; }
                $content .= '</tr></thead><tbody>';
                foreach ($roles as $role_key => $role_label) {
                    $content .= '<tr><td><strong>' . esc_html($role_label) . '</strong></td>';
                    foreach ($ordered as $id => $title) {
                        $checked = in_array($id, $role_shipping[$role_key] ?? []) ? 'checked' : '';
                        $content .= '<td style="text-align:center;">'
                                  . '<input type="checkbox" name="b2b_role_shipping_methods[' . esc_attr($role_key) . '][]" value="' . esc_attr($id) . '" ' . $checked . '>'
                                  . '</td>';
                    }
                    $content .= '</tr>';
                }
                $content .= '</tbody></table>';
            }
        }
        $content .= '<div style="margin-top:18px; display:flex; gap:10px;">';
        $content .= '<button class="b2b-admin-btn" type="submit"><span class="icon dashicons dashicons-saved"></span>Save</button>';
        $content .= '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '" class="b2b-admin-btn" style="background:#eef3fb;color:#1976d2;box-shadow:none;">Open WooCommerce Payments</a>';
        $content .= '</div></form></div>';
        $this->render_admin_wrapper('b2b-checkout-controls', $content);
    }

    public function vat_settings_page() {
        if (!current_user_can('manage_options')) return;
        $opts = get_option('b2b_vat_settings', []);
        if (isset($_POST['b2b_vat_nonce']) && wp_verify_nonce($_POST['b2b_vat_nonce'], 'b2b_vat_settings')) {
            $opts = [
                'enable_vat_validation' => isset($_POST['b2b_vat_settings']['enable_vat_validation']) ? 1 : 0,
                'auto_tax_exempt' => isset($_POST['b2b_vat_settings']['auto_tax_exempt']) ? 1 : 0,
            ];
            update_option('b2b_vat_settings', $opts);
            echo '<div class="b2b-admin-card" style="color:#2196f3;">VAT settings saved.</div>';
        }
        $content = '<div class="b2b-admin-header"><h1><span class="icon dashicons dashicons-universal-access-alt"></span>VAT Settings</h1><p>Validate EU VAT numbers and auto-set exemptions.</p></div>';
        $content .= '<div class="b2b-admin-card" style="max-width: 720px;"><form method="post" class="b2b-admin-form">' . wp_nonce_field('b2b_vat_settings', 'b2b_vat_nonce', true, false);
        $content .= '<div style="display:flex; align-items:center; justify-content:space-between; padding:15px; background:#f8f9fa; border-radius:8px; margin-bottom:12px;">'
                 . '<div><label style="margin:0; font-weight:600; color:#23272f;">Enable EU VAT validation (VIES)</label><p style="margin:5px 0 0 0; color:#666; font-size:0.9em;">Checks VAT numbers using the European Commission VIES service.</p></div>'
                 . '<label class="b2b-admin-toggle"><input type="checkbox" name="b2b_vat_settings[enable_vat_validation]" value="1" ' . checked($opts['enable_vat_validation'] ?? '', 1, false) . '><span class="b2b-admin-toggle-slider"></span></label>'
                 . '</div>';
        $content .= '<div style="display:flex; align-items:center; justify-content:space-between; padding:15px; background:#f8f9fa; border-radius:8px;">'
                 . '<div><label style="margin:0; font-weight:600; color:#23272f;">Auto set Tax Exempt on valid VAT</label><p style="margin:5px 0 0 0; color:#666; font-size:0.9em;">When validation passes, mark the user as tax exempt automatically.</p></div>'
                 . '<label class="b2b-admin-toggle"><input type="checkbox" name="b2b_vat_settings[auto_tax_exempt]" value="1" ' . checked($opts['auto_tax_exempt'] ?? '', 1, false) . '><span class="b2b-admin-toggle-slider"></span></label>'
                 . '</div>';
        $content .= '<div style="margin-top:18px;"><button class="b2b-admin-btn" type="submit"><span class="icon dashicons dashicons-saved"></span>Save</button></div></form></div>';
        $this->render_admin_wrapper('b2b-vat', $content);
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
                    $user = new \WP_User($user_id);
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
    <form method="post" class="b2b-admin-form" id="b2b-add-user-form">
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
        $rules = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY id DESC", $table));
        
        // Handle form submission
        if (isset($_POST['b2b_pricing_nonce']) && wp_verify_nonce($_POST['b2b_pricing_nonce'], 'b2b_pricing_action')) {
            $this->save_pricing_rule();
        }
        
        // Show success message if redirected after update
        if (isset($_GET['updated']) && $_GET['updated'] == '1') {
            $content = '<div class="notice notice-success"><p>Pricing rule updated successfully!</p></div>';
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
                <label for="product_id">Product (Optional)</label>
                <select name="product_id" id="product_id">
                    <option value="0">All Products (Global Rule)</option>';
                    
                    // Get all products
                    $products = get_posts([
                        'post_type' => 'product',
                        'numberposts' => -1,
                        'post_status' => 'publish'
                    ]);
                    
                    foreach ($products as $product) {
                        $content .= '<option value="' . $product->ID . '">' . esc_html($product->post_title) . '</option>';
                    }
                    
                    $content .= '
                </select>
                <small>Leave as "All Products" for global pricing, or select specific product</small>
            </div>
            
            <div class="b2b-admin-form-group">
                <label for="min_qty">Min Quantity</label>
                <input type="number" name="min_qty" id="min_qty" min="1" value="1" placeholder="1">
                <small>Minimum order quantity required</small>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-top: 20px;">
            <div class="b2b-admin-form-group">
                <label for="price">Value *</label>
                <input type="number" name="price" id="price" step="0.01" required placeholder="Enter value">
                <small>For discount: use negative number (e.g., -15 for 15% off)</small>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit" class="b2b-admin-btn"><span class="icon dashicons dashicons-saved"></span>Save Pricing Rule</button>
        </div>
    </form>
</div>

<!-- Edit Modal -->
<div id="editModal" class="b2b-modal" style="display: none;">
    <div class="b2b-modal-content">
        <div class="b2b-modal-header">
            <h3>Edit Pricing Rule</h3>
            <span class="b2b-modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="post" action="">
            <input type="hidden" name="edit_rule_id" id="edit_rule_id" value="">
            ' . wp_nonce_field('b2b_pricing_action', 'b2b_pricing_nonce', true, false) . '
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="b2b-admin-form-group">
                    <label for="edit_role">Customer Type</label>
                    <select name="role" id="edit_role" required>
                        <option value="">Choose customer type</option>
                        <option value="wholesale_customer">Wholesale Customer</option>
                        <option value="distributor">Distributor</option>
                        <option value="retailer">Retailer</option>
                    </select>
                </div>
                
                <div class="b2b-admin-form-group">
                    <label for="edit_type">Discount Type</label>
                    <select name="type" id="edit_type" required>
                        <option value="percentage">Percentage Discount</option>
                        <option value="fixed">Fixed Price</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="b2b-admin-form-group">
                    <label for="edit_product_id">Product</label>
                    <select name="product_id" id="edit_product_id">
                        <option value="0">All Products (Global Rule)</option>';
                        
                        // Get all products
                        $products = get_posts([
                            'post_type' => 'product',
                            'numberposts' => -1,
                            'post_status' => 'publish'
                        ]);
                        
                        foreach ($products as $product) {
                            $content .= '<option value="' . $product->ID . '">' . esc_html($product->post_title) . '</option>';
                        }
                        
                        $content .= '
                    </select>
                </div>
                
                <div class="b2b-admin-form-group">
                    <label for="edit_min_qty">Min Quantity</label>
                    <input type="number" name="min_qty" id="edit_min_qty" min="1" value="1" required>
                </div>
            </div>
            
            <div class="b2b-admin-form-group" style="margin-top: 20px;">
                <label for="edit_price">Value</label>
                <input type="number" name="price" id="edit_price" step="0.01" required>
                <small id="edit_price_help">Enter percentage (e.g., 20 for 20% discount) or fixed price</small>
            </div>
            
            <div class="b2b-modal-footer">
                <button type="button" class="b2b-admin-btn b2b-admin-btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="b2b-admin-btn">Update Rule</button>
            </div>
        </form>
    </div>
</div>

<style>
.b2b-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.b2b-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.b2b-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.b2b-modal-header h3 {
    margin: 0;
    color: #333;
}

.b2b-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.b2b-modal-close:hover {
    color: #000;
}

.b2b-modal form {
    padding: 20px;
}

.b2b-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.b2b-modal-footer button {
    margin-left: 10px;
}
</style>

<script>
function openEditModal(id, role, type, price, minQty, productId) {
    document.getElementById("edit_rule_id").value = id;
    document.getElementById("edit_role").value = role;
    document.getElementById("edit_type").value = type;
    document.getElementById("edit_price").value = price;
    document.getElementById("edit_min_qty").value = minQty;
    document.getElementById("edit_product_id").value = productId;
    
    document.getElementById("editModal").style.display = "block";
}

function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById("editModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

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
                    <th>Product</th>
                    <th>Customer Type</th>
                    <th>Pricing Type</th>
                    <th>Value</th>
                    <th>Min Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';
                
                foreach ($rules as $rule) {
                    $value_display = $rule->type === 'percentage' ? abs($rule->price) . '% discount' : '$' . number_format($rule->price, 2);
                    
                    // Get product name
                    $product_name = 'All Products';
                    if ($rule->product_id > 0) {
                        $product = get_post($rule->product_id);
                        $product_name = $product ? $product->post_title : 'Product #' . $rule->product_id;
                    }
                    
                    $content .= '
                <tr>
                    <td>' . esc_html($product_name) . '</td>
                    <td>' . esc_html(ucfirst(str_replace('_', ' ', $rule->role))) . '</td>
                    <td>' . esc_html(ucfirst($rule->type)) . '</td>
                    <td>' . esc_html($value_display) . '</td>
                    <td>' . esc_html($rule->min_qty ?: 'Any') . '</td>
                    <td>
                        <button type="button" class="b2b-admin-btn b2b-admin-btn-secondary" style="padding: 6px 12px; font-size: 0.9em; margin-right: 5px;" onclick="openEditModal(' . $rule->id . ', \'' . esc_js($rule->role) . '\', \'' . esc_js($rule->type) . '\', ' . $rule->price . ', ' . $rule->min_qty . ', ' . $rule->product_id . ')"><span class="icon dashicons dashicons-edit"></span>Edit</button>
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
        try {
            // Check if WooCommerce is active
            if (!class_exists('WooCommerce')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('WooCommerce is required for pricing rules.', 'b2b-commerce-pro') . '</p></div>';
                return;
            }

            // Verify nonce and permissions
            if (!isset($_POST['b2b_pricing_nonce']) || !wp_verify_nonce($_POST['b2b_pricing_nonce'], 'b2b_pricing_action')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed.', 'b2b-commerce-pro') . '</p></div>';
                return;
            }

            if (!current_user_can('manage_options')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('You do not have permission to perform this action.', 'b2b-commerce-pro') . '</p></div>';
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
            $product_id = intval($_POST['product_id'] ?? 0);

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
                'product_id' => $product_id,
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

            // Check if this is an edit operation
            $edit_rule_id = isset($_POST['edit_rule_id']) ? intval($_POST['edit_rule_id']) : 0;
            
            if ($edit_rule_id > 0) {
                // Update existing rule
                $result = $wpdb->update($table, $data, ['id' => $edit_rule_id]);
                $message = 'Pricing rule updated successfully!';
                
                // Debug: Log the update
                error_log("B2B Pricing: Updated rule ID $edit_rule_id with price $price");
            } else {
                // Insert new rule
                $result = $wpdb->insert($table, $data);
                $message = 'Pricing rule saved successfully!';
            }

            if ($result === false) {
                echo '<div class="notice notice-error"><p>Failed to save pricing rule. Database error: ' . esc_html($wpdb->last_error) . '</p></div>';
            } else {
                // Redirect with success message
                wp_redirect(admin_url('admin.php?page=b2b-pricing&updated=1'));
                exit;
            }
            
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error saving pricing rule: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    // Ensure pricing table exists
    private function ensure_pricing_table_exists() {
        try {
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
        } catch (Exception $e) {
            error_log('B2B Commerce Pro Table Check Error: ' . $e->getMessage());
            return false;
        }
    }

    // Email template customization page
    public function email_templates_page() {
        // Handle form submission
        if (isset($_POST['b2b_email_nonce']) && wp_verify_nonce($_POST['b2b_email_nonce'], 'b2b_email_templates')) {
            $email_templates = [
                'user_approval_subject' => sanitize_text_field($_POST['user_approval_subject'] ?? ''),
                'user_approval_message' => wp_kses_post($_POST['user_approval_message'] ?? ''),
                'user_rejection_subject' => sanitize_text_field($_POST['user_rejection_subject'] ?? ''),
                'user_rejection_message' => wp_kses_post($_POST['user_rejection_message'] ?? ''),
                'welcome_subject' => sanitize_text_field($_POST['welcome_subject'] ?? ''),
                'welcome_message' => wp_kses_post($_POST['welcome_message'] ?? ''),
                'order_status_subject' => sanitize_text_field($_POST['order_status_subject'] ?? ''),
                'order_status_message' => wp_kses_post($_POST['order_status_message'] ?? ''),
                'pricing_change_subject' => sanitize_text_field($_POST['pricing_change_subject'] ?? ''),
                'pricing_change_message' => wp_kses_post($_POST['pricing_change_message'] ?? '')
            ];
            
            update_option('b2b_email_templates', $email_templates);
            $success_message = '<div class="b2b-admin-card" style="color: #4caf50; border-color: #4caf50; background: #f1f8e9;"><span class="icon dashicons dashicons-yes-alt"></span> Email templates saved successfully!</div>';
        }
        
        $templates = get_option('b2b_email_templates', []);
        
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-email"></span>Email Templates</h1>
    <p>Customize email notifications for B2B users and administrators.</p>
</div>';
        
        if (isset($success_message)) {
            $content .= $success_message;
        }
        
        $content .= '
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-email"></span>Email Template Management</div>
    
    <form method="post" class="b2b-admin-form">
        ' . wp_nonce_field('b2b_email_templates', 'b2b_email_nonce', true, false) . '
        
        <div class="b2b-admin-card" style="margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #23272f;">User Approval Notification</h3>
            <div class="b2b-admin-form-group">
                <label for="user_approval_subject">Subject Line:</label>
                <input type="text" id="user_approval_subject" name="user_approval_subject" value="' . esc_attr($templates['user_approval_subject'] ?? 'Your B2B Account Has Been Approved') . '" placeholder="Your B2B Account Has Been Approved">
            </div>
            <div class="b2b-admin-form-group">
                <label for="user_approval_message">Message:</label>
                <textarea id="user_approval_message" name="user_approval_message" rows="6" placeholder="Dear {user_name},&#10;&#10;Congratulations! Your B2B account has been approved. You can now log in and access wholesale pricing.&#10;&#10;Login URL: {login_url}&#10;&#10;Best regards,&#10;{site_name}">' . esc_textarea($templates['user_approval_message'] ?? 'Dear {user_name},

Congratulations! Your B2B account has been approved. You can now log in and access wholesale pricing.

Login URL: {login_url}

Best regards,
{site_name}') . '</textarea>
                <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Available variables: {user_name}, {login_url}, {site_name}</p>
            </div>
        </div>
        
        <div class="b2b-admin-card" style="margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #23272f;">User Rejection Notification</h3>
            <div class="b2b-admin-form-group">
                <label for="user_rejection_subject">Subject Line:</label>
                <input type="text" id="user_rejection_subject" name="user_rejection_subject" value="' . esc_attr($templates['user_rejection_subject'] ?? 'B2B Account Application Status') . '" placeholder="B2B Account Application Status">
            </div>
            <div class="b2b-admin-form-group">
                <label for="user_rejection_message">Message:</label>
                <textarea id="user_rejection_message" name="user_rejection_message" rows="6" placeholder="Dear {user_name},&#10;&#10;We regret to inform you that your B2B account application has been rejected. Please contact us for more information.&#10;&#10;Best regards,&#10;{site_name}">' . esc_textarea($templates['user_rejection_message'] ?? 'Dear {user_name},

We regret to inform you that your B2B account application has been rejected. Please contact us for more information.

Best regards,
{site_name}') . '</textarea>
                <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Available variables: {user_name}, {site_name}</p>
            </div>
        </div>
        
        <div class="b2b-admin-card" style="margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #23272f;">Welcome Email for New B2B Users</h3>
            <div class="b2b-admin-form-group">
                <label for="welcome_subject">Subject Line:</label>
                <input type="text" id="welcome_subject" name="welcome_subject" value="' . esc_attr($templates['welcome_subject'] ?? 'Welcome to {site_name} B2B Platform') . '" placeholder="Welcome to {site_name} B2B Platform">
            </div>
            <div class="b2b-admin-form-group">
                <label for="welcome_message">Message:</label>
                <textarea id="welcome_message" name="welcome_message" rows="6" placeholder="Dear {user_name},&#10;&#10;Welcome to our B2B platform! Your account has been created successfully.&#10;&#10;Login URL: {login_url}&#10;Dashboard: {dashboard_url}&#10;&#10;Best regards,&#10;{site_name}">' . esc_textarea($templates['welcome_message'] ?? 'Dear {user_name},

Welcome to our B2B platform! Your account has been created successfully.

Login URL: {login_url}
Dashboard: {dashboard_url}

Best regards,
{site_name}') . '</textarea>
                <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Available variables: {user_name}, {login_url}, {dashboard_url}, {site_name}</p>
            </div>
        </div>
        
        <div class="b2b-admin-card" style="margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #23272f;">Order Status Updates</h3>
            <div class="b2b-admin-form-group">
                <label for="order_status_subject">Subject Line:</label>
                <input type="text" id="order_status_subject" name="order_status_subject" value="' . esc_attr($templates['order_status_subject'] ?? 'Order #{order_id} Status Update') . '" placeholder="Order #{order_id} Status Update">
            </div>
            <div class="b2b-admin-form-group">
                <label for="order_status_message">Message:</label>
                <textarea id="order_status_message" name="order_status_message" rows="6" placeholder="Dear {user_name},&#10;&#10;Your order #{order_id} status has been updated to: {order_status}&#10;&#10;Order Total: {order_total}&#10;Order Date: {order_date}&#10;&#10;View Order: {order_url}&#10;&#10;Best regards,&#10;{site_name}">' . esc_textarea($templates['order_status_message'] ?? 'Dear {user_name},

Your order #{order_id} status has been updated to: {order_status}

Order Total: {order_total}
Order Date: {order_date}

View Order: {order_url}

Best regards,
{site_name}') . '</textarea>
                <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Available variables: {user_name}, {order_id}, {order_status}, {order_total}, {order_date}, {order_url}, {site_name}</p>
            </div>
        </div>
        
        <div class="b2b-admin-card" style="margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #23272f;">Pricing Change Notifications</h3>
            <div class="b2b-admin-form-group">
                <label for="pricing_change_subject">Subject Line:</label>
                <input type="text" id="pricing_change_subject" name="pricing_change_subject" value="' . esc_attr($templates['pricing_change_subject'] ?? 'Pricing Update Notification') . '" placeholder="Pricing Update Notification">
            </div>
            <div class="b2b-admin-form-group">
                <label for="pricing_change_message">Message:</label>
                <textarea id="pricing_change_message" name="pricing_change_message" rows="6" placeholder="Dear {user_name},&#10;&#10;We wanted to inform you about recent pricing updates for your account.&#10;&#10;Please log in to view the latest pricing information.&#10;&#10;Login URL: {login_url}&#10;&#10;Best regards,&#10;{site_name}">' . esc_textarea($templates['pricing_change_message'] ?? 'Dear {user_name},

We wanted to inform you about recent pricing updates for your account.

Please log in to view the latest pricing information.

Login URL: {login_url}

Best regards,
{site_name}') . '</textarea>
                <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Available variables: {user_name}, {login_url}, {site_name}</p>
            </div>
        </div>
        
        <div class="b2b-admin-form-group">
            <button type="submit" class="b2b-admin-btn b2b-admin-btn-success">
                <span class="icon dashicons dashicons-saved"></span>Save Email Templates
            </button>
        </div>
    </form>
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
                'require_tax_id' => isset($_POST['b2b_general_settings']['require_tax_id']) ? 1 : 0,
                'enable_white_label' => isset($_POST['b2b_general_settings']['enable_white_label']) ? 1 : 0,
                'enable_notifications' => isset($_POST['b2b_general_settings']['enable_notifications']) ? 1 : 0,
                'enable_custom_fields' => isset($_POST['b2b_general_settings']['enable_custom_fields']) ? 1 : 0
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
        
        <div class="b2b-admin-form-group">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f8f9fa; border-radius: 6px; margin-bottom: 15px;">
                <div>
                    <label for="b2b_enable_white_label" style="margin: 0; font-weight: 600; color: #23272f;">Enable White Labeling</label>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Remove plugin branding for client sites</p>
                </div>
                <label class="b2b-admin-toggle">
                    <input type="checkbox" id="b2b_enable_white_label" name="b2b_general_settings[enable_white_label]" value="1" ' . checked( $opts['enable_white_label'] ?? '', 1, false ) . '>
                    <span class="b2b-admin-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="b2b-admin-form-group">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f8f9fa; border-radius: 6px; margin-bottom: 15px;">
                <div>
                    <label for="b2b_enable_notifications" style="margin: 0; font-weight: 600; color: #23272f;">Enable Admin Notifications</label>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Show notifications for pending approvals and new orders</p>
                </div>
                <label class="b2b-admin-toggle">
                    <input type="checkbox" id="b2b_enable_notifications" name="b2b_general_settings[enable_notifications]" value="1" ' . checked( $opts['enable_notifications'] ?? '', 1, false ) . '>
                    <span class="b2b-admin-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="b2b-admin-form-group">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: #f8f9fa; border-radius: 6px; margin-bottom: 15px;">
                <div>
                    <label for="b2b_enable_custom_fields" style="margin: 0; font-weight: 600; color: #23272f;">Enable Custom Fields</label>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Allow custom fields in user registration and product forms</p>
                </div>
                <label class="b2b-admin-toggle">
                    <input type="checkbox" id="b2b_enable_custom_fields" name="b2b_general_settings[enable_custom_fields]" value="1" ' . checked( $opts['enable_custom_fields'] ?? '', 1, false ) . '>
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
            <li><strong>White Labeling:</strong> When enabled, the plugin branding is removed from client sites</li>
            <li><strong>Admin Notifications:</strong> When enabled, you receive notifications for pending approvals and new orders</li>
            <li><strong>Custom Fields:</strong> When enabled, custom fields can be added to user registration and product forms</li>
        </ul>
    </div>
</div>';
        
        $this->render_admin_wrapper('b2b-settings', $content);
    }

    // Quotes admin list
    public function quotes_page() {
        if (!current_user_can('manage_options')) return;
        $quotes = get_option('b2b_quote_requests', []);
        
        // Migrate existing quotes to include user_email if missing
        $quotes_updated = false;
        foreach ($quotes as $index => $quote) {
            if (!isset($quote['user_email']) && isset($quote['user_id'])) {
                $user = get_userdata($quote['user_id']);
                $quotes[$index]['user_email'] = $user ? $user->user_email : 'User not found';
                $quotes_updated = true;
            }
        }
        
        // Save updated quotes if any were modified
        if ($quotes_updated) {
            update_option('b2b_quote_requests', $quotes);
        }
        
        $content = '<div class="b2b-admin-header"><h1><span class="icon dashicons dashicons-email-alt"></span>Quotes</h1><p>Manage incoming quote requests.</p></div>';
        
        // Show success message if quote was updated
        if (isset($_GET['updated']) && $_GET['updated'] == '1') {
            $status = sanitize_text_field($_GET['status'] ?? '');
            $status_text = ucfirst($status);
            $content .= '<div class="notice notice-success is-dismissible"><p><strong>Success!</strong> Quote has been ' . esc_html($status_text) . '.</p></div>';
        }
        

        
        // Add migration button if there are quotes without emails
        $quotes_without_emails = 0;
        foreach ($quotes as $quote) {
            if (!isset($quote['user_email']) && isset($quote['user_id'])) {
                $quotes_without_emails++;
            }
        }
        
        if ($quotes_without_emails > 0) {
            $migrate_url = wp_nonce_url(admin_url('admin-post.php?action=b2b_migrate_quotes'), 'b2b_migrate_quotes');
            $content .= '<div class="b2b-admin-card" style="margin-bottom: 20px; background: #fff3cd; border: 1px solid #ffeaa7;">';
            $content .= '<div style="padding: 15px;">';
            $content .= '<h4 style="margin: 0 0 10px 0; color: #856404;">⚠️ Data Migration Required</h4>';
            $content .= '<p style="margin: 0 0 15px 0; color: #856404;">Found ' . $quotes_without_emails . ' quote request(s) with missing email data. Click the button below to fix this.</p>';
            $content .= '<a href="' . esc_url($migrate_url) . '" class="b2b-admin-btn" style="background: #dc3545; color: #fff;">Migrate Quote Data</a>';
            $content .= '</div>';
            $content .= '</div>';
        }
        
        $content .= '<div class="b2b-admin-card">';
        if (empty($quotes)) {
            $content .= '<p>No quote requests found.</p>';
        } else {
            $content .= '<table class="b2b-admin-table"><thead><tr><th>Date</th><th>User</th><th>Product</th><th>Qty</th><th>Message</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
            foreach ($quotes as $index => $q) {
                $product = function_exists('wc_get_product') ? wc_get_product($q['product_id']) : null;
                $content .= '<tr>';
                $content .= '<td>' . esc_html($q['date'] ?? '') . '</td>';
                $content .= '<td>' . esc_html($q['user_email'] ?? 'No email') . '</td>';
                $content .= '<td>' . esc_html($product ? $product->get_name() : ('#' . ($q['product_id'] ?? ''))) . '</td>';
                $content .= '<td>' . esc_html($q['quantity'] ?? '') . '</td>';
                $content .= '<td>' . esc_html($q['message'] ?? '') . '</td>';
                $content .= '<td>' . esc_html(ucfirst($q['status'] ?? 'pending')) . '</td>';
                $content .= '<td><button type="button" class="b2b-admin-btn b2b-quote-action" data-index="' . $index . '" data-action="approve">Approve</button> <button type="button" class="b2b-admin-btn b2b-admin-btn-danger b2b-quote-action" data-index="' . $index . '" data-action="decline">Decline</button></td>';
                $content .= '</tr>';
            }
            $content .= '</tbody></table>';
        }
        $content .= '</div>';
        
        // Add CSS and JavaScript to improve button functionality
        $content .= '<style>
        .b2b-quote-action {
            display: inline-block;
            padding: 6px 12px;
            margin: 2px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .b2b-quote-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .b2b-quote-action.processing {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .b2b-admin-btn {
            background: #0073aa;
            color: white;
            border: 1px solid #0073aa;
        }
        .b2b-admin-btn:hover {
            background: #005a87;
            color: white;
        }
        .b2b-admin-btn-danger {
            background: #dc3545;
            border-color: #dc3545;
        }
        .b2b-admin-btn-danger:hover {
            background: #c82333;
        }
        </style>';
        
        $content .= '<script>
        jQuery(document).ready(function($) {
            $(".b2b-quote-action").on("click", function() {
                var $btn = $(this);
                if ($btn.hasClass("processing")) {
                    return false;
                }
                
                var index = $btn.data("index");
                var action = $btn.data("action");
                var confirmText = action === "approve" ? "Are you sure you want to approve this quote?" : "Are you sure you want to decline this quote?";
                
                if (!confirm(confirmText)) {
                    return false;
                }
                
                $btn.addClass("processing").text("Processing...");
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "b2b_update_quote_ajax",
                        quote_index: index,
                        status: action + "d", // approved or declined
                        nonce: "' . wp_create_nonce('b2b_update_quote_ajax') . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            var notice = $("<div class=\"notice notice-success is-dismissible\"><p><strong>Success!</strong> " + response.data.message + "</p></div>");
                            $(".b2b-admin-header").after(notice);
                            
                            // Update the status in the table
                            $btn.closest("tr").find("td:nth-child(6)").text(response.data.status.charAt(0).toUpperCase() + response.data.status.slice(1));
                            
                            // Remove the buttons or disable them
                            $btn.closest("td").html("<span class=\"b2b-status-badge b2b-status-" + response.data.status + "\">" + response.data.status.charAt(0).toUpperCase() + response.data.status.slice(1) + "</span>");
                        } else {
                            alert("Error: " + response.data);
                        }
                    },
                    error: function() {
                        alert("Error: Failed to update quote. Please try again.");
                    },
                    complete: function() {
                        $btn.removeClass("processing").text(action === "approve" ? "Approve" : "Decline");
                    }
                });
            });
        });
        </script>';
        
        $this->render_admin_wrapper('b2b-quotes', $content);
    }

    // Product Inquiries admin list
    public function inquiries_page() {
        if (!current_user_can('manage_options')) return;
        $inquiries = get_option('b2b_product_inquiries', []);
        $content = '<div class="b2b-admin-header"><h1><span class="icon dashicons dashicons-format-chat"></span>Product Inquiries</h1><p>Manage incoming product inquiries.</p></div>';
        $content .= '<div class="b2b-admin-card">';
        if (empty($inquiries)) {
            $content .= '<p>No product inquiries found.</p>';
        } else {
            $content .= '<table class="b2b-admin-table"><thead><tr><th style="width: 120px;">Date</th><th style="width: 180px;">Email</th><th style="width: 150px;">Product</th><th style="width: 400px;">Message</th><th style="width: 100px;">Status</th><th style="width: 120px; text-align: center;">Actions</th></tr></thead><tbody>';
            foreach ($inquiries as $index => $inquiry) {
                $product = function_exists('wc_get_product') ? wc_get_product($inquiry['product_id']) : null;
                $content .= '<tr>';
                $content .= '<td>' . esc_html($inquiry['date'] ?? '') . '</td>';
                $content .= '<td>' . esc_html($inquiry['email'] ?? '') . '</td>';
                $content .= '<td>' . esc_html($product ? $product->get_name() : ('#' . ($inquiry['product_id'] ?? ''))) . '</td>';
                $content .= '<td><div class="message-content">' . esc_html($inquiry['message'] ?? '') . '</div></td>';
                $status = $inquiry['status'] ?? 'pending';
                $status_class = 'b2b-status-' . $status;
                $content .= '<td><span class="b2b-status-badge ' . $status_class . '">' . esc_html(ucfirst($status)) . '</span></td>';
                $content .= '<td class="actions-cell">';
                if (($inquiry['status'] ?? 'pending') === 'pending') {
                    $content .= '<button type="button" class="b2b-respond-btn" data-index="' . $index . '">Respond</button>';
                    $content .= '<button type="button" class="b2b-close-btn" data-index="' . $index . '">Close</button>';
                } elseif (($inquiry['status'] ?? 'pending') === 'responded') {
                    $content .= '<button type="button" class="b2b-view-response-btn" data-index="' . $index . '" data-response="' . esc_attr($inquiry['admin_response'] ?? '') . '" data-responded-date="' . esc_attr($inquiry['responded_date'] ?? '') . '" data-responded-by="' . esc_attr(get_userdata($inquiry['responded_by'] ?? 0)->display_name ?? 'Admin') . '">View Response</button>';
                    $content .= '<button type="button" class="b2b-close-btn" data-index="' . $index . '">Close</button>';
                } elseif (($inquiry['status'] ?? 'pending') === 'closed') {
                    $content .= '<button type="button" class="b2b-view-response-btn" data-index="' . $index . '" data-response="' . esc_attr($inquiry['admin_response'] ?? '') . '" data-responded-date="' . esc_attr($inquiry['responded_date'] ?? '') . '" data-responded-by="' . esc_attr(get_userdata($inquiry['responded_by'] ?? 0)->display_name ?? 'Admin') . '">View Response</button>';
                    $content .= '<span class="b2b-status-badge b2b-status-closed">Closed</span>';
                }
                
                // Add delete button for all inquiries
                $content .= '<button type="button" class="b2b-delete-btn" data-index="' . $index . '">Delete</button>';
                $content .= '</td>';
                $content .= '</tr>';
            }
            $content .= '</tbody></table>';
        }
        $content .= '</div>';
        
        // Add response modal
        $content .= '<div id="b2b-response-modal" style="display:none;" class="b2b-modal">';
        $content .= '<div class="b2b-modal-content">';
        $content .= '<span class="b2b-modal-close">&times;</span>';
        $content .= '<h3>Respond to Inquiry</h3>';
        $content .= '<form method="post" action="' . admin_url('admin-post.php') . '">';
        $content .= '<input type="hidden" name="action" value="b2b_update_inquiry">';
        $content .= wp_nonce_field('b2b_update_inquiry', '_wpnonce', true, false);
        $content .= '<input type="hidden" name="inquiry_index" id="inquiry_index">';
        $content .= '<input type="hidden" name="status" id="inquiry_status" value="responded">';
        $content .= '<p><label>Your Response:<br><textarea name="admin_response" rows="5" style="width:100%;" required></textarea></label></p>';
        $content .= '<p><button type="submit" class="b2b-admin-btn">Send Response</button></p>';
        $content .= '</form>';
        $content .= '</div>';
        $content .= '</div>';
        
        // Add view response modal
        $content .= '<div id="b2b-view-response-modal" style="display:none;" class="b2b-modal">';
        $content .= '<div class="b2b-modal-content">';
        $content .= '<span class="b2b-modal-close">&times;</span>';
        $content .= '<h3>View Response</h3>';
        $content .= '<div id="response-content" style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;"></div>';
        $content .= '<p><button type="button" class="b2b-admin-btn" onclick="$(\'#b2b-view-response-modal\').hide();">Close</button></p>';
        $content .= '</div>';
        $content .= '</div>';
        
        // Add delete confirmation modal
        $content .= '<div id="b2b-delete-modal" style="display:none;" class="b2b-modal">';
        $content .= '<div class="b2b-modal-content">';
        $content .= '<span class="b2b-modal-close">&times;</span>';
        $content .= '<h3>Delete Inquiry</h3>';
        $content .= '<p>Are you sure you want to delete this inquiry? This action cannot be undone.</p>';
        $content .= '<form method="post" action="' . admin_url('admin-post.php') . '">';
        $content .= '<input type="hidden" name="action" value="b2b_delete_inquiry">';
        $content .= wp_nonce_field('b2b_delete_inquiry', '_wpnonce', true, false);
        $content .= '<input type="hidden" name="inquiry_index" id="delete_inquiry_index">';
        $content .= '<p><button type="submit" class="b2b-admin-btn b2b-admin-btn-danger">Delete</button> <button type="button" class="b2b-admin-btn" onclick="$(\'#b2b-delete-modal\').hide();">Cancel</button></p>';
        $content .= '</form>';
        $content .= '</div>';
        $content .= '</div>';
        
        // Add JavaScript for modal functionality
        $content .= '<script>
        jQuery(function($){
            $(".b2b-respond-btn").on("click", function(){
                var index = $(this).data("index");
                $("#inquiry_index").val(index);
                $("#inquiry_status").val("responded");
                $("#b2b-response-modal").show();
            });
            
            $(".b2b-view-response-btn").on("click", function(){
                var index = $(this).data("index");
                var response = $(this).data("response");
                var respondedDate = $(this).data("responded-date");
                var respondedBy = $(this).data("responded-by");
                
                var content = "<strong>Response:</strong><br>" + response + "<br><br>";
                content += "<strong>Responded on:</strong> " + respondedDate + "<br>";
                content += "<strong>Responded by:</strong> " + respondedBy;
                
                $("#response-content").html(content);
                $("#b2b-view-response-modal").show();
            });
            
            $(".b2b-close-btn").on("click", function(){
                var index = $(this).data("index");
                $("#inquiry_index").val(index);
                $("#inquiry_status").val("closed");
                $("input[name=admin_response]").val("Inquiry closed by admin.");
                $("form").submit();
            });
            
            $(".b2b-delete-btn").on("click", function(){
                var index = $(this).data("index");
                $("#delete_inquiry_index").val(index);
                $("#b2b-delete-modal").show();
            });
            
            $(".b2b-modal-close").on("click", function(){
                $(".b2b-modal").hide();
            });
            
            $(window).on("click", function(e){
                if($(e.target).hasClass("b2b-modal")){
                    $(".b2b-modal").hide();
                }
            });
        });
        </script>';
        
        $this->render_admin_wrapper('b2b-inquiries', $content);
    }

    // Handle quote status updates
    public function handle_update_quote() {
        try {
            if (!current_user_can('manage_options')) {
                wp_die(__('Unauthorized access.', 'b2b-commerce-pro'));
            }
            
            $index = isset($_GET['quote']) ? intval($_GET['quote']) : -1;
            $status = sanitize_text_field($_GET['status'] ?? '');
            
            if ($index < 0 || !in_array($status, ['approved','declined'], true)) {
                wp_die(__('Invalid request parameters.', 'b2b-commerce-pro'));
            }
            
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'b2b_update_quote_' . $index)) {
                wp_die(__('Security check failed.', 'b2b-commerce-pro'));
            }
            
            $quotes = get_option('b2b_quote_requests', []);
            if (!isset($quotes[$index])) {
                wp_die(__('Quote not found.', 'b2b-commerce-pro'));
            }
            
            $quotes[$index]['status'] = $status;
            $quotes[$index]['updated_at'] = current_time('mysql');
            $quotes[$index]['updated_by'] = get_current_user_id();
            
            $result = update_option('b2b_quote_requests', $quotes);
            
            if (!$result) {
                wp_die(__('Failed to update quote status.', 'b2b-commerce-pro'));
            }
            
            // Send email notification to customer
            $this->send_quote_status_email($quotes[$index], $status);
            
            wp_redirect(admin_url('admin.php?page=b2b-quotes&updated=1&status=' . $status));
            exit;
            
        } catch (Exception $e) {
            error_log('B2B Quote Update Error: ' . $e->getMessage());
            wp_die(__('An error occurred while updating the quote.', 'b2b-commerce-pro'));
        }
    }

    // AJAX handler for quote updates
    public function handle_update_quote_ajax() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Unauthorized access.', 'b2b-commerce-pro'));
            }
            
            // Validate and sanitize input
            $index = isset($_POST['quote_index']) ? absint($_POST['quote_index']) : -1;
            $status = sanitize_text_field($_POST['status'] ?? '');
            
            if ($index < 0 || !in_array($status, ['approved','declined'], true)) {
                wp_send_json_error(__('Invalid request parameters.', 'b2b-commerce-pro'));
            }
            
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'b2b_update_quote_ajax')) {
                wp_send_json_error(__('Security check failed.', 'b2b-commerce-pro'));
            }
            
            $quotes = get_option('b2b_quote_requests', []);
            if (!isset($quotes[$index])) {
                wp_send_json_error(__('Quote not found.', 'b2b-commerce-pro'));
            }
            
            $quotes[$index]['status'] = $status;
            $quotes[$index]['updated_at'] = current_time('mysql');
            $quotes[$index]['updated_by'] = get_current_user_id();
            
            $result = update_option('b2b_quote_requests', $quotes);
            
            if (!$result) {
                wp_send_json_error(__('Failed to update quote status.', 'b2b-commerce-pro'));
            }
            
            // Send email notification
            $this->send_quote_status_email($quotes[$index], $status);
            
            wp_send_json_success([
                'message' => sprintf(__('Quote has been %s.', 'b2b-commerce-pro'), ucfirst($status)),
                'status' => $status
            ]);
            
        } catch (Exception $e) {
            error_log('B2B Quote AJAX Error: ' . $e->getMessage());
            wp_send_json_error(__('An error occurred while updating the quote.', 'b2b-commerce-pro'));
        }
    }

    // Handle inquiry status updates
    public function handle_update_inquiry() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('b2b_update_inquiry');
        
        $inquiry_index = intval($_POST['inquiry_index']);
        $new_status = sanitize_text_field($_POST['status']);
        $admin_response = sanitize_textarea_field($_POST['admin_response']);
        
        $inquiries = get_option('b2b_product_inquiries', []);
        
        if (isset($inquiries[$inquiry_index])) {
            $inquiries[$inquiry_index]['status'] = $new_status;
            $inquiries[$inquiry_index]['admin_response'] = $admin_response;
            $inquiries[$inquiry_index]['responded_date'] = current_time('mysql');
            $inquiries[$inquiry_index]['responded_by'] = get_current_user_id();
            
            update_option('b2b_product_inquiries', $inquiries);
            
            // Send email response to customer if provided
            if (!empty($admin_response) && !empty($inquiries[$inquiry_index]['email'])) {
                $this->send_inquiry_response_email($inquiries[$inquiry_index]);
            }
            
            wp_redirect(admin_url('admin.php?page=b2b-inquiries&updated=1'));
            exit;
        }
        
        wp_redirect(admin_url('admin.php?page=b2b-inquiries&error=1'));
        exit;
    }
    
    private function send_inquiry_response_email($inquiry) {
        $product = wc_get_product($inquiry['product_id']);
        $subject = 'Response to your inquiry about ' . ($product ? $product->get_name() : 'Product');
        
        $message = "Dear Customer,\n\n";
        $message .= "Thank you for your inquiry about " . ($product ? $product->get_name() : 'our product') . ".\n\n";
        $message .= "Our response:\n" . $inquiry['admin_response'] . "\n\n";
        $message .= "Best regards,\n" . get_bloginfo('name');
        
        wp_mail($inquiry['email'], $subject, $message);
    }
    
    // Handle inquiry deletion
    public function handle_delete_inquiry() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('b2b_delete_inquiry');
        
        $inquiry_index = intval($_POST['inquiry_index']);
        $inquiries = get_option('b2b_product_inquiries', []);
        
        if (isset($inquiries[$inquiry_index])) {
            // Remove the inquiry from the array
            unset($inquiries[$inquiry_index]);
            // Reindex the array
            $inquiries = array_values($inquiries);
            update_option('b2b_product_inquiries', $inquiries);
            
            wp_redirect(admin_url('admin.php?page=b2b-inquiries&deleted=1'));
            exit;
        }
        
        wp_redirect(admin_url('admin.php?page=b2b-inquiries&error=1'));
        exit;
    }
    
    // Handle quote data migration
    public function handle_migrate_quotes() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('b2b_migrate_quotes');
        
        $quotes = get_option('b2b_quote_requests', []);
        $migrated_count = 0;
        
        foreach ($quotes as $index => $quote) {
            if (!isset($quote['user_email']) && isset($quote['user_id'])) {
                $user = get_userdata($quote['user_id']);
                $quotes[$index]['user_email'] = $user ? $user->user_email : 'User not found';
                $migrated_count++;
            }
        }
        
        if ($migrated_count > 0) {
            update_option('b2b_quote_requests', $quotes);
            wp_redirect(admin_url('admin.php?page=b2b-quotes&migrated=' . $migrated_count));
        } else {
            wp_redirect(admin_url('admin.php?page=b2b-quotes&no_migration=1'));
        }
        exit;
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
        // Get analytics data
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
        
        // Get WooCommerce analytics
        $total_revenue = 0;
        $total_orders = 0;
        $monthly_revenue = 0;
        $recent_orders = [];
        
        if (class_exists('WooCommerce') && function_exists('wc_get_orders')) {
            try {
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
                
                // Get recent orders
                $recent_orders = wc_get_orders(['limit' => 5, 'orderby' => 'date', 'order' => 'DESC']);
            } catch (Exception $e) {
                error_log('B2B Analytics Error: ' . $e->getMessage());
            }
        }
        
        // Get pricing rules count
        global $wpdb;
        $pricing_rules_count = 0;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $pricing_rules_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        }
        
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-chart-line"></span>Analytics</h1>
    <p>Track B2B performance metrics, sales analytics, and business insights.</p>
</div>

<!-- Key Metrics Overview -->
<div class="b2b-stats-grid">
    <div class="b2b-stat-card">
        <div class="stat-icon dashicons dashicons-groups"></div>
        <div class="stat-value">' . number_format($total_users) . '</div>
        <div class="stat-label">Total B2B Users</div>
    </div>
    <div class="b2b-stat-card">
        <div class="stat-icon dashicons dashicons-yes-alt"></div>
        <div class="stat-value">' . number_format($approved_users) . '</div>
        <div class="stat-label">Approved Users</div>
    </div>
    <div class="b2b-stat-card">
        <div class="stat-icon dashicons dashicons-clock"></div>
        <div class="stat-value">' . number_format($pending_users) . '</div>
        <div class="stat-label">Pending Approvals</div>
    </div>
    <div class="b2b-stat-card">
        <div class="stat-icon dashicons dashicons-cart"></div>
        <div class="stat-value">' . number_format($total_orders) . '</div>
        <div class="stat-label">Total Orders</div>
    </div>
    <div class="b2b-stat-card">
        <div class="stat-icon dashicons dashicons-money-alt"></div>
        <div class="stat-value">$' . number_format($total_revenue, 2) . '</div>
        <div class="stat-label">Total Revenue</div>
    </div>
    <div class="b2b-stat-card">
        <div class="stat-icon dashicons dashicons-tag"></div>
        <div class="stat-value">' . number_format($pricing_rules_count) . '</div>
        <div class="stat-label">Pricing Rules</div>
    </div>
</div>

<!-- Revenue Analytics -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-chart-area"></span>Revenue Analytics</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="padding: 20px; background: linear-gradient(135deg, #4caf50, #388e3c); color: white; border-radius: 8px;">
            <h3 style="margin: 0 0 10px 0; font-size: 1.2em;">Total Revenue</h3>
            <div style="font-size: 2em; font-weight: bold;">$' . number_format($total_revenue, 2) . '</div>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">All-time revenue from B2B orders</p>
        </div>
        <div style="padding: 20px; background: linear-gradient(135deg, #2196f3, #1976d2); color: white; border-radius: 8px;">
            <h3 style="margin: 0 0 10px 0; font-size: 1.2em;">Monthly Revenue</h3>
            <div style="font-size: 2em; font-weight: bold;">$' . number_format($monthly_revenue, 2) . '</div>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Revenue for ' . date('F Y') . '</p>
        </div>
        <div style="padding: 20px; background: linear-gradient(135deg, #ff9800, #f57c00); color: white; border-radius: 8px;">
            <h3 style="margin: 0 0 10px 0; font-size: 1.2em;">Average Order Value</h3>
            <div style="font-size: 2em; font-weight: bold;">$' . ($total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00') . '</div>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Average revenue per order</p>
        </div>
    </div>
</div>

<!-- User Analytics -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-groups"></span>User Analytics</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <div style="font-size: 2em; color: #2196f3; font-weight: bold;">' . number_format($total_users) . '</div>
            <div style="color: #666; font-size: 0.9em;">Total B2B Users</div>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <div style="font-size: 2em; color: #4caf50; font-weight: bold;">' . number_format($approved_users) . '</div>
            <div style="color: #666; font-size: 0.9em;">Approved Users</div>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <div style="font-size: 2em; color: #ff9800; font-weight: bold;">' . number_format($pending_users) . '</div>
            <div style="color: #666; font-size: 0.9em;">Pending Approvals</div>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <div style="font-size: 2em; color: #9c27b0; font-weight: bold;">' . ($total_users > 0 ? round(($approved_users / $total_users) * 100, 1) : '0') . '%</div>
            <div style="color: #666; font-size: 0.9em;">Approval Rate</div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-cart"></span>Recent Orders</div>';
        
        if (!empty($recent_orders)) {
            $content .= '
    <table class="b2b-admin-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>';
            
            foreach ($recent_orders as $order) {
                $customer = $order->get_customer_id() ? get_userdata($order->get_customer_id()) : null;
                $customer_name = $customer ? $customer->display_name : 'Guest';
                
                $content .= '
            <tr>
                <td>#' . $order->get_id() . '</td>
                <td>' . esc_html($customer_name) . '</td>
                <td>' . $order->get_date_created()->date('M j, Y') . '</td>
                <td><span class="b2b-badge b2b-badge-' . ($order->get_status() === 'completed' ? 'success' : 'warning') . '">' . ucfirst($order->get_status()) . '</span></td>
                <td>$' . number_format($order->get_total(), 2) . '</td>
            </tr>';
            }
            
            $content .= '
        </tbody>
    </table>';
        } else {
            $content .= '
    <div style="text-align: center; padding: 40px; color: #666;">
        <div class="dashicons dashicons-cart" style="font-size: 3em; color: #ddd; margin-bottom: 15px;"></div>
        <p>No orders found. Orders will appear here once customers start placing orders.</p>
    </div>';
        }
        
        $content .= '
</div>

<!-- Performance Metrics -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-chart-bar"></span>Performance Metrics</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: #23272f;">User Growth</h4>
            <div style="font-size: 1.5em; font-weight: bold; color: #2196f3;">' . number_format($total_users) . '</div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Total registered B2B users</p>
        </div>
        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: #23272f;">Order Volume</h4>
            <div style="font-size: 1.5em; font-weight: bold; color: #4caf50;">' . number_format($total_orders) . '</div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Total completed orders</p>
        </div>
        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: #23272f;">Revenue Growth</h4>
            <div style="font-size: 1.5em; font-weight: bold; color: #ff9800;">$' . number_format($total_revenue, 2) . '</div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Total revenue generated</p>
        </div>
        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: #23272f;">Pricing Rules</h4>
            <div style="font-size: 1.5em; font-weight: bold; color: #9c27b0;">' . number_format($pricing_rules_count) . '</div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">Active pricing rules</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-admin-tools"></span>Quick Actions</div>
    <div class="b2b-quick-actions">
        <a href="' . admin_url('admin.php?page=b2b-add-user') . '" class="b2b-admin-btn">
            <span class="icon dashicons dashicons-plus"></span>Add B2B User
        </a>
        <a href="' . admin_url('admin.php?page=b2b-pricing') . '" class="b2b-admin-btn">
            <span class="icon dashicons dashicons-tag"></span>Manage Pricing
        </a>
        <a href="' . admin_url('admin.php?page=b2b-orders') . '" class="b2b-admin-btn">
            <span class="icon dashicons dashicons-cart"></span>View Orders
        </a>
        <a href="' . admin_url('admin.php?page=b2b-analytics') . '" class="b2b-admin-btn">
            <span class="icon dashicons dashicons-chart-line"></span>Analytics
        </a>
        <button onclick="importDemoData()" class="b2b-admin-btn b2b-admin-btn-success">
            <span class="icon dashicons dashicons-download"></span>Import Demo Data
        </button>
    </div>
    
    <script>
    function importDemoData() {
        if (confirm("This will import demo B2B users and pricing rules. Continue?")) {
            jQuery.post(ajaxurl, {
                action: "b2b_import_demo_data",
                nonce: "' . wp_create_nonce('b2b_import_demo') . '"
            }, function(response) {
                if (response.success) {
                    alert("Demo data imported successfully! Refresh the page to see the changes.");
                    location.reload();
                } else {
                    alert("Error importing demo data: " + response.data);
                }
            });
        }
    }
    </script>
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
            <h4 style="margin: 0 0 5px 0; color: #23272f;">Database</h4>
            <p style="margin: 0; color: ' . ($tests['database']['status'] === 'OK' ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . ($tests['database']['status'] === 'OK' ? 'yes-alt' : 'no-alt') . '"></span>
                ' . $tests['database']['message'] . '
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">Database Tables</h4>
            <p style="margin: 0; color: ' . ($tests['tables']['status'] === 'OK' ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . ($tests['tables']['status'] === 'OK' ? 'yes-alt' : 'no-alt') . '"></span>
                ' . $tests['tables']['message'] . '
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">Email</h4>
            <p style="margin: 0; color: ' . ($tests['email']['status'] === 'OK' ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . ($tests['email']['status'] === 'OK' ? 'yes-alt' : 'no-alt') . '"></span>
                ' . $tests['email']['message'] . '
            </p>
        </div>
        <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <h4 style="margin: 0 0 5px 0; color: #23272f;">Permissions</h4>
            <p style="margin: 0; color: ' . ($tests['permissions']['status'] === 'OK' ? '#4caf50' : '#f44336') . ';">
                <span class="dashicons dashicons-' . ($tests['permissions']['status'] === 'OK' ? 'yes-alt' : 'no-alt') . '"></span>
                ' . $tests['permissions']['message'] . '
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
        
        // Database connectivity test
        global $wpdb;
        $db_connected = $wpdb->check_connection();
        $tests['database'] = [
            'status' => $db_connected ? 'OK' : 'FAILED',
            'message' => $db_connected ? 'Connected' : 'Connection Failed'
        ];
        
        // WooCommerce integration test
        $wc_active = class_exists('WooCommerce');
        $tests['woocommerce'] = [
            'status' => $wc_active ? 'OK' : 'FAILED',
            'message' => $wc_active ? 'Active' : 'Not Active'
        ];
        
        // Email functionality test
        $email_working = wp_mail('test@example.com', 'Test', 'Test message') !== false;
        $tests['email'] = [
            'status' => $email_working ? 'OK' : 'FAILED',
            'message' => $email_working ? 'Working' : 'Not Working'
        ];
        
        // File permissions test
        $permissions_ok = is_writable(WP_CONTENT_DIR);
        $tests['permissions'] = [
            'status' => $permissions_ok ? 'OK' : 'FAILED',
            'message' => $permissions_ok ? 'Writable' : 'Not Writable'
        ];
        
        // Plugin tables test
        $pricing_table = $wpdb->prefix . 'b2b_pricing_rules';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$pricing_table'") === $pricing_table;
        $tests['tables'] = [
            'status' => $table_exists ? 'OK' : 'FAILED',
            'message' => $table_exists ? 'Exists' : 'Missing'
        ];
        
        // User roles test
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
    
    // Import/Export page
    public function import_export_page() {
        $this->render_admin_wrapper('b2b-import-export', $this->get_import_export_content());
    }
    
    private function get_import_export_content() {
        ob_start();
        ?>
        <div class="b2b-import-export-container">
            <h2>Bulk Import/Export</h2>
            <p style="color: #666; margin-bottom: 20px;">Export your B2B data to CSV format for backup or external processing.</p>
            
            <div class="b2b-import-export-section">
                <h3>Export Data</h3>
                <p style="color: #666; margin-bottom: 15px;">Click any button below to export the corresponding data:</p>
                <div class="b2b-export-options">
                    <button class="button button-primary" onclick="exportB2BData('users')">Export Users</button>
                    <span style="margin-left: 10px; color: #666; font-size: 0.9em;">Exports all B2B users with their details</span><br><br>
                    <button class="button button-primary" onclick="exportB2BData('pricing')">Export Pricing Rules</button>
                    <span style="margin-left: 10px; color: #666; font-size: 0.9em;">Exports all B2B pricing rules</span><br><br>
                    <button class="button button-primary" onclick="exportB2BData('orders')">Export Orders</button>
                    <span style="margin-left: 10px; color: #666; font-size: 0.9em;">Exports all WooCommerce orders (if any exist)</span>
                </div>
                <p style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #2196f3; color: #666;">
                    <strong>Note:</strong> If no data exists for a particular export type, the CSV will contain a message indicating "No data found".
                </p>
            </div>
            
            <div class="b2b-import-export-section">
                <h3>Import Data</h3>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('b2b_import_export', 'b2b_import_nonce'); ?>
                    <p>
                        <label>Select File Type:</label>
                        <select name="import_type">
                            <option value="users">Users</option>
                            <option value="pricing">Pricing Rules</option>
                        </select>
                    </p>
                    <p>
                        <label>CSV File:</label>
                        <input type="file" name="import_file" accept=".csv" required>
                    </p>
                    <p>
                        <input type="submit" name="b2b_import" value="Import Data" class="button button-primary">
                    </p>
                </form>
            </div>
            
            <div class="b2b-import-export-section">
                <h3>Template Downloads</h3>
                <p>Download CSV templates for importing data:</p>
                <a href="<?php echo admin_url('admin-ajax.php?action=b2b_download_template&type=users&nonce=' . wp_create_nonce('b2b_template_nonce')); ?>" class="button">Users Template</a>
                <a href="<?php echo admin_url('admin-ajax.php?action=b2b_download_template&type=pricing&nonce=' . wp_create_nonce('b2b_template_nonce')); ?>" class="button">Pricing Template</a>
            </div>
        </div>
        
        <script>
        function exportB2BData(type) {
            jQuery.post(ajaxurl, {
                action: 'b2b_export_data',
                type: type,
                nonce: '<?php echo wp_create_nonce("b2b_ajax_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    // Create and download CSV file
                    var blob = new Blob([response.data], {type: 'text/csv'});
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'b2b_' + type + '_' + new Date().toISOString().slice(0,10) + '.csv';
                    a.click();
                } else {
                    alert('Export failed: ' + response.data);
                }
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    // Helper function to process email templates
    private function process_email_template($template_key, $variables = []) {
        $templates = get_option('b2b_email_templates', []);
        
        $subject_key = $template_key . '_subject';
        $message_key = $template_key . '_message';
        
        $subject = $templates[$subject_key] ?? '';
        $message = $templates[$message_key] ?? '';
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return [
            'subject' => $subject,
            'message' => $message
        ];
    }
    
    // Show admin notifications
    public function show_admin_notifications() {
        $opts = get_option('b2b_general_settings', []);
        if (!isset($opts['enable_notifications']) || !$opts['enable_notifications']) {
            return;
        }
        
        $dismissed_notifications = get_option('b2b_dismissed_notifications', []);
        
        // Check for pending approvals
        $pending_users = count(get_users([
            'role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'],
            'meta_key' => 'b2b_approval_status',
            'meta_value' => 'pending'
        ]));
        
        if ($pending_users > 0 && !in_array('pending_approvals', $dismissed_notifications)) {
            echo '<div class="notice notice-warning is-dismissible" data-notification="pending_approvals">
                <p><strong>B2B Commerce Pro:</strong> You have <strong>' . $pending_users . '</strong> pending B2B user approval(s). 
                <a href="' . admin_url('admin.php?page=b2b-users') . '">Review now</a></p>
            </div>';
        }
        
        // Check for recent orders
        if (class_exists('WooCommerce') && function_exists('wc_get_orders')) {
            $recent_orders = wc_get_orders([
                'limit' => 5,
                'orderby' => 'date',
                'order' => 'DESC',
                'date_created' => '>=' . date('Y-m-d', strtotime('-24 hours'))
            ]);
            
            if (count($recent_orders) > 0 && !in_array('recent_orders', $dismissed_notifications)) {
                echo '<div class="notice notice-info is-dismissible" data-notification="recent_orders">
                    <p><strong>B2B Commerce Pro:</strong> You have <strong>' . count($recent_orders) . '</strong> new order(s) in the last 24 hours. 
                    <a href="' . admin_url('admin.php?page=b2b-orders') . '">View orders</a></p>
                </div>';
            }
        }
        
        // Add JavaScript for dismissible notifications
        echo '<script>
        jQuery(document).ready(function($) {
            $(".notice[data-notification]").on("click", ".notice-dismiss", function() {
                var notification = $(this).closest(".notice").data("notification");
                $.post(ajaxurl, {
                    action: "b2b_dismiss_notification",
                    notification: notification,
                    nonce: "' . wp_create_nonce('b2b_dismiss_notification') . '"
                });
            });
        });
        </script>';
    }
    
    // Dismiss notification
    public function dismiss_notification() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'b2b_dismiss_notification')) {
            wp_die('Security check failed');
        }
        
        $notification = sanitize_text_field($_POST['notification']);
        $dismissed = get_option('b2b_dismissed_notifications', []);
        $dismissed[] = $notification;
        update_option('b2b_dismissed_notifications', array_unique($dismissed));
        
        wp_send_json_success('Notification dismissed');
    }
    
    // Send email notification for quote status changes
    private function send_quote_status_email($quote, $status) {
        if (empty($quote['user_email'])) {
            return false;
        }
        
        $product = wc_get_product($quote['product_id']);
        $product_name = $product ? $product->get_name() : __('Product', 'b2b-commerce-pro');
        
        $subject = sprintf(__('Your quote request for %s has been %s', 'b2b-commerce-pro'), $product_name, $status);
        
        $message = sprintf(
            __('Dear Customer,

Your quote request for %s (Quantity: %d) has been %s.

Quote Details:
- Product: %s
- Quantity: %d
- Your Message: %s
- Status: %s

%s

Best regards,
%s', 'b2b-commerce-pro'),
            $product_name,
            $quote['quantity'],
            $status,
            $product_name,
            $quote['quantity'],
            $quote['message'] ?? '',
            ucfirst($status),
            $status === 'approved' ? __('We will contact you shortly with pricing information.' , 'b2b-commerce-pro') : __('We are unable to fulfill this request at this time.' , 'b2b-commerce-pro'),
            get_bloginfo('name')
        );
        
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        
        return wp_mail($quote['user_email'], $subject, $message, $headers);
    }
} 