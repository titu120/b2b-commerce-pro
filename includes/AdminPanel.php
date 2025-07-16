<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdminPanel {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    // Add main admin menu and submenus
    public function add_admin_menu() {
        add_menu_page( 'B2B Commerce Pro', 'B2B Commerce', 'manage_options', 'b2b-dashboard', [ $this, 'dashboard_page' ], 'dashicons-businessman', 55 );
        add_submenu_page( 'b2b-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'b2b-dashboard', [ $this, 'dashboard_page' ] );
        add_submenu_page( 'b2b-dashboard', 'User Management', 'Users', 'list_users', 'b2b-users', [ $this, 'user_management_page' ] );
        add_submenu_page( 'b2b-dashboard', 'Order Management', 'Orders', 'manage_woocommerce', 'b2b-orders', [ $this, 'order_management_page' ] );
        add_submenu_page( 'b2b-dashboard', 'Email Templates', 'Email Templates', 'manage_options', 'b2b-emails', [ $this, 'email_templates_page' ] );
        add_submenu_page( 'b2b-dashboard', 'Settings', 'Settings', 'manage_options', 'b2b-settings', [ $this, 'settings_page' ] );
    }

    // Render the admin wrapper with custom sidebar and centered carded content
    public function render_admin_wrapper($current_page, $content) {
        $menu_items = [
            'b2b-dashboard' => ['Dashboard', 'dashicons-chart-area'],
            'b2b-users' => ['Users', 'dashicons-groups'],
            'b2b-orders' => ['Orders', 'dashicons-cart'],
            'b2b-emails' => ['Email Templates', 'dashicons-email'],
            'b2b-settings' => ['Settings', 'dashicons-admin-generic'],
            'b2b-pricing' => ['B2B Pricing', 'dashicons-tag'], // Added B2B Pricing
        ];
        echo '<div class="b2b-admin-demo-wrapper">';
        // Sidebar
        echo '<aside class="b2b-admin-sidebar">';
        echo '<div class="b2b-admin-logo">';
        echo '<img src="https://dummyimage.com/120x40/23272f/ffd700&text=B2B+Pro" alt="B2B Commerce Pro Logo">';
        echo '<h1>B2B Commerce Pro</h1>';
        echo '</div>';
        echo '<ul class="b2b-admin-menu">';
        foreach ($menu_items as $page => $item) {
            $active_class = ($current_page === $page) ? 'active' : '';
            echo '<li>';
            echo '<a href="' . admin_url('admin.php?page=' . $page) . '" class="' . $active_class . '"><span class="icon dashicons ' . $item[1] . '"></span>' . $item[0] . '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</aside>';
        // Centered content area
        echo '<main class="b2b-admin-content">';
        echo '<div style="max-width: 1300px; margin: 0 auto;">';
        echo $content;
        echo '</div>';
        echo '</main>';
        echo '</div>';
    }

    // Dashboard page
    public function dashboard_page() {
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-chart-area"></span>Dashboard</h1>
    <p>Welcome to B2B Commerce Pro. Monitor your business performance and manage your B2B operations.</p>
</div>
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-admin-links"></span>Quick Actions</div>
    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="' . admin_url('admin.php?page=b2b-users') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-groups"></span>Manage Users</a>
        <a href="' . admin_url('admin.php?page=b2b-orders') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-cart"></span>View Orders</a>
        <a href="' . admin_url('admin.php?page=b2b-emails') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-email"></span>Email Templates</a>
        <a href="' . admin_url('admin.php?page=b2b-settings') . '" class="b2b-admin-btn"><span class="icon dashicons dashicons-admin-generic"></span>Settings</a>
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
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-groups"></span>User Management</h1>
    <p>Manage B2B users, approve applications, and control user access.</p>
</div>
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
    <table class="b2b-admin-table">
        <thead>
            <tr>
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
                <td><strong>' . esc_html( $user->user_login ) . '</strong></td>
                <td>' . esc_html( $user->user_email ) . '</td>
                <td>' . esc_html( implode( ', ', $user->roles ) ) . '</td>
                <td>' . esc_html( get_user_meta( $user->ID, 'company_name', true ) ) . '</td>
                <td>' . esc_html( $approval_status ) . '</td>
                <td>
                    <a href="' . admin_url('user-edit.php?user_id=' . $user->ID) . '" class="b2b-admin-btn" style="padding: 6px 12px; font-size: 0.9em;"><span class="icon dashicons dashicons-edit"></span>Edit</a>
                </td>
            </tr>';
        }
        $content .= '
        </tbody>
    </table>
</div>';
        $this->render_admin_wrapper('b2b-users', $content);
    }

    // Order management page
    public function order_management_page() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            $content = '<div class="b2b-admin-card"><span class="icon dashicons dashicons-warning"></span> WooCommerce is required to view orders.</div>';
            $this->render_admin_wrapper('b2b-orders', $content);
            return;
        }
        $args = [ 'limit' => 30, 'orderby' => 'date', 'order' => 'DESC' ];
        $orders = wc_get_orders( $args );
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-cart"></span>Order Management</h1>
    <p>Monitor and manage B2B orders, track status, and view order details.</p>
</div>
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-list-view"></span>Recent Orders (' . count($orders) . ')</div>
    <table class="b2b-admin-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';
        foreach ( $orders as $order ) {
            $status = $order->get_status();
            $content .= '
            <tr>
                <td><strong><a href="' . esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) . '">#' . $order->get_id() . '</a></strong></td>
                <td>' . esc_html( $order->get_date_created()->date( 'Y-m-d H:i' ) ) . '</td>
                <td>' . esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) . '</td>
                <td>' . esc_html( wc_get_order_status_name( $status ) ) . '</td>
                <td><strong>' . esc_html( $order->get_formatted_order_total() ) . '</strong></td>
                <td><a href="' . esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) . '" class="b2b-admin-btn" style="padding: 6px 12px; font-size: 0.9em;"><span class="icon dashicons dashicons-visibility"></span>View</a></td>
            </tr>';
        }
        $content .= '
        </tbody>
    </table>
</div>';
        $this->render_admin_wrapper('b2b-orders', $content);
    }

    // Email template customization page
    public function email_templates_page() {
        $templates = [ 'approval' => 'Approval', 'rejection' => 'Rejection', 'quote' => 'Quote', 'inquiry' => 'Inquiry' ];
        if ( isset( $_POST['b2b_email_template_nonce'] ) && wp_verify_nonce( $_POST['b2b_email_template_nonce'], 'b2b_email_template' ) ) {
            foreach ( $templates as $key => $label ) {
                update_option( 'b2b_email_template_' . $key, wp_kses_post( $_POST[ 'template_' . $key ] ) );
            }
            $success_message = '<div class="b2b-admin-card" style="color: #2196f3;"><span class="icon dashicons dashicons-yes-alt"></span> Email templates updated successfully!</div>';
        }
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-email"></span>Email Templates</h1>
    <p>Customize email templates for B2B communications, approvals, and notifications.</p>
</div>';
        if (isset($success_message)) {
            $content .= $success_message;
        }
        $content .= '<form method="post" class="b2b-admin-form">';
        $content .= wp_nonce_field( 'b2b_email_template', 'b2b_email_template_nonce', true, false );
        foreach ( $templates as $key => $label ) {
            $val = get_option( 'b2b_email_template_' . $key, '' );
            $icon = $key === 'approval' ? 'dashicons-yes-alt' : ($key === 'rejection' ? 'dashicons-no-alt' : ($key === 'quote' ? 'dashicons-money-alt' : 'dashicons-email-alt'));
            $content .= '
            <div class="b2b-admin-card">
                <div class="b2b-admin-card-title"><span class="icon dashicons ' . $icon . '"></span>' . esc_html( $label ) . ' Email Template</div>
                <div class="b2b-admin-form-group">
                    <label for="template_' . esc_attr( $key ) . '">Email Content</label>
                    <textarea name="template_' . esc_attr( $key ) . '" id="template_' . esc_attr( $key ) . '" rows="8">' . esc_textarea( $val ) . '</textarea>
                    <p style="color: #666; font-size: 0.9em; margin-top: 8px;">Use placeholders: {user_name}, {company_name}, {site_name}</p>
                </div>
            </div>';
        }
        $content .= '<div class="b2b-admin-card"><button type="submit" class="b2b-admin-btn"><span class="icon dashicons dashicons-saved"></span>Save All Templates</button></div></form>';
        $this->render_admin_wrapper('b2b-emails', $content);
    }

    // Settings page
    public function settings_page() {
        $opts = get_option( 'b2b_general_settings', [] );
        $content = '
<div class="b2b-admin-header">
    <h1><span class="icon dashicons dashicons-admin-generic"></span>Settings</h1>
    <p>Configure B2B Commerce Pro settings and customize your B2B experience.</p>
</div>
<form method="post" action="options.php" class="b2b-admin-form">';
        $content .= settings_fields( 'b2b_settings' );
        // $content .= do_settings_sections( 'b2b-settings' ); // Commented out to remove duplicate unstyled field
        $content .= '
<div class="b2b-admin-card">
    <div class="b2b-admin-card-title"><span class="icon dashicons dashicons-admin-generic"></span>General Settings</div>
    <div class="b2b-admin-form-group">
        <label for="b2b_company_required">Require Company Name</label>
        <label class="b2b-admin-toggle">
            <input type="checkbox" id="b2b_company_required" name="b2b_general_settings[company_required]" value="1" ' . checked( $opts['company_required'] ?? '', 1, false ) . '>
            <span class="b2b-admin-toggle-slider"></span>
        </label>
        <span style="margin-left: 12px;">Yes, require company name during registration</span>
    </div>
    <button type="submit" class="b2b-admin-btn"><span class="icon dashicons dashicons-saved"></span>Save Changes</button>
</div>
</form>';
        $this->render_admin_wrapper('b2b-settings', $content);
    }

    public function register_settings() {
        register_setting( 'b2b_settings', 'b2b_general_settings', [ $this, 'validate_settings' ] );
        add_settings_section( 'b2b_general', 'General Settings', null, 'b2b-settings' );
        add_settings_field( 'b2b_company_required', 'Require Company Name', [ $this, 'field_company_required' ], 'b2b-settings', 'b2b_general' );
    }
    public function field_company_required() {
        $opts = get_option( 'b2b_general_settings', [] );
        echo '<input type="checkbox" name="b2b_general_settings[company_required]" value="1" ' . checked( $opts['company_required'] ?? '', 1, false ) . '> Yes';
    }
    public function validate_settings( $input ) {
        $input['company_required'] = ! empty( $input['company_required'] ) ? 1 : 0;
        return $input;
    }
} 