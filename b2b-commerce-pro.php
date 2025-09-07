<?php
/*
Plugin Name: B2B Commerce Pro
Plugin URI: https://codecanyon.net/item/b2b-commerce-pro/your-id
Description: Premium WooCommerce B2B & Wholesale Plugin with advanced user management, pricing, product control, and more.
Version: 1.0.0
Author: Your Name
Author URI: https://yourwebsite.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: b2b-commerce-pro
Domain Path: /languages
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constant
define( 'B2B_COMMERCE_PRO_VERSION', '1.0.0' );
define( 'B2B_COMMERCE_PRO_PATH', plugin_dir_path( __FILE__ ) );
define( 'B2B_COMMERCE_PRO_URL', plugin_dir_url( __FILE__ ) );
define( 'B2B_COMMERCE_PRO_BASENAME', plugin_basename( __FILE__ ) );

function autoload_b2b_commerce_pro() {
    spl_autoload_register( function ( $class ) {
        $prefix = 'B2B\\';
        $base_dir = __DIR__ . '/includes/';
        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }
        $relative_class = substr( $class, $len );
        $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
        if ( file_exists( $file ) ) {
            require $file;
        }
    } );
}

// Register the autoloader
autoload_b2b_commerce_pro();

// Load text domain early
add_action( 'init', function() {
    load_plugin_textdomain( 'b2b-commerce-pro', false, dirname( B2B_COMMERCE_PRO_BASENAME ) . '/languages' );
});

// Bootstrap the plugin
add_action( 'plugins_loaded', function() {
    if ( !class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>' . __('B2B Commerce Pro:', 'b2b-commerce-pro') . '</strong> ' . __('WooCommerce is required for this plugin to work.', 'b2b-commerce-pro') . '</p></div>';
        });
        return;
    }
    
    // Check if required classes exist before initializing
    if ( class_exists( 'B2B\\Init' ) ) {
        try {
            B2B\Init::instance();
        } catch ( Exception $e ) {
            // Log error but don't break the site
            error_log( 'B2B Commerce Pro Error: ' . $e->getMessage() );
        }
    } else {
        // Show admin notice if Init class is missing
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>' . __('B2B Commerce Pro:', 'b2b-commerce-pro') . '</strong> ' . __('Required classes not found. Please reinstall the plugin.', 'b2b-commerce-pro') . '</p></div>';
        });
    }
    
    // Ensure pricing table exists
    if ( class_exists( 'B2B\\PricingManager' ) ) {
        B2B\PricingManager::create_pricing_table();
    }
} );

// Register activation and deactivation hooks
register_activation_hook( __FILE__, function() {
    try {
        // Load autoloader first
        autoload_b2b_commerce_pro();
        
        // Create pricing table
        if ( class_exists( 'B2B\\PricingManager' ) ) {
            B2B\PricingManager::create_pricing_table();
        }
        // Add roles
        if ( class_exists( 'B2B\\UserManager' ) ) {
            B2B\UserManager::add_roles();
        }
        
        // Auto-create essential B2B pages
        create_b2b_pages();
        
    } catch ( Exception $e ) {
        // Log activation error
        error_log( 'B2B Commerce Pro Activation Error: ' . $e->getMessage() );
    }
} );

// Function to create essential B2B pages
function create_b2b_pages() {
    // Check if pages already exist to avoid duplicates
    $existing_pages = get_posts([
        'post_type' => 'page',
        'meta_query' => [
            [
                'key' => '_b2b_page_type',
                'value' => ['registration', 'dashboard', 'bulk_order', 'account'],
                'compare' => 'IN'
            ]
        ],
        'posts_per_page' => -1
    ]);
    
    $existing_page_types = wp_list_pluck($existing_pages, 'post_name');
    
    // B2B Registration Page
    if (!in_array('b2b-registration', $existing_page_types)) {
        $registration_page = wp_insert_post([
            'post_title' => __('B2B Registration', 'b2b-commerce-pro'),
            'post_name' => 'b2b-registration',
            'post_content' => '[b2b_registration]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'meta_input' => [
                '_b2b_page_type' => 'registration'
            ]
        ]);
        
        if ($registration_page) {
            update_option('b2b_registration_page_id', $registration_page);
        }
    }
    
    // B2B Dashboard Page
    if (!in_array('b2b-dashboard', $existing_page_types)) {
        $dashboard_page = wp_insert_post([
            'post_title' => __('B2B Dashboard', 'b2b-commerce-pro'),
            'post_name' => 'b2b-dashboard',
            'post_content' => '[b2b_dashboard]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'meta_input' => [
                '_b2b_page_type' => 'dashboard'
            ]
        ]);
        
        if ($dashboard_page) {
            update_option('b2b_dashboard_page_id', $dashboard_page);
        }
    }
    
    // Bulk Order Page
    if (!in_array('bulk-order', $existing_page_types)) {
        $bulk_order_page = wp_insert_post([
            'post_title' => __('Bulk Order', 'b2b-commerce-pro'),
            'post_name' => 'bulk-order',
            'post_content' => '[b2b_bulk_order]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'meta_input' => [
                '_b2b_page_type' => 'bulk_order'
            ]
        ]);
        
        if ($bulk_order_page) {
            update_option('b2b_bulk_order_page_id', $bulk_order_page);
        }
    }
    
    // Account Settings Page
    if (!in_array('account-settings', $existing_page_types)) {
        $account_page = wp_insert_post([
            'post_title' => __('Account Settings', 'b2b-commerce-pro'),
            'post_name' => 'account-settings',
            'post_content' => '[b2b_account]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'meta_input' => [
                '_b2b_page_type' => 'account'
            ]
        ]);
        
        if ($account_page) {
            update_option('b2b_account_page_id', $account_page);
        }
    }
    
    // Add admin notice about created pages
    add_option('b2b_pages_created_notice', true);
    
    // Try to add B2B Registration to main menu
    add_b2b_registration_to_menu();
}

// Function to add B2B Registration to main menu
function add_b2b_registration_to_menu() {
    $registration_page_id = get_option('b2b_registration_page_id');
    if (!$registration_page_id) return;
    
    // Get the primary menu
    $primary_menu = get_nav_menu_locations();
    $primary_menu_id = $primary_menu['primary'] ?? null;
    
    if (!$primary_menu_id) {
        // Try to find any menu
        $menus = wp_get_nav_menus();
        if (!empty($menus)) {
            $primary_menu_id = $menus[0]->term_id;
        }
    }
    
    if ($primary_menu_id) {
        // Check if menu item already exists
        $menu_items = wp_get_nav_menu_items($primary_menu_id);
        $registration_exists = false;
        
        foreach ($menu_items as $item) {
            if ($item->object_id == $registration_page_id) {
                $registration_exists = true;
                break;
            }
        }
        
        if (!$registration_exists) {
            wp_update_nav_menu_item($primary_menu_id, 0, [
                'menu-item-title' => __('B2B Registration', 'b2b-commerce-pro'),
                'menu-item-object-id' => $registration_page_id,
                'menu-item-object' => 'page',
                'menu-item-status' => 'publish',
                'menu-item-type' => 'post_type'
            ]);
        }
    }
}

register_deactivation_hook( __FILE__, function() {
    try {
        // Load autoloader first
        autoload_b2b_commerce_pro();
        
        if ( class_exists( 'B2B\\UserManager' ) ) {
            B2B\UserManager::remove_roles();
        }
    } catch ( Exception $e ) {
        // Log deactivation error
        error_log( 'B2B Commerce Pro Deactivation Error: ' . $e->getMessage() );
    }
} );

// Removed creating pricing table on every load; handled on activation and self-healing in PricingManager

// AJAX handlers for B2B Commerce Pro with comprehensive error handling
add_action('wp_ajax_b2b_approve_user', function() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'b2b-commerce-pro'));
            return;
        }
        
        if (!isset($_POST['user_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error(__('Missing required parameters', 'b2b-commerce-pro'));
            return;
        }
        
        $user_id = intval($_POST['user_id']);
        $nonce = $_POST['nonce'] ?? '';
        
        // Accept either per-user nonce or a generic approve nonce for flexibility
        if (!wp_verify_nonce($nonce, 'b2b_approve_user_' . $user_id) && !wp_verify_nonce($nonce, 'b2b_approve_user_nonce')) {
            wp_send_json_error(__('Security check failed', 'b2b-commerce-pro'));
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(__('User not found', 'b2b-commerce-pro'));
            return;
        }
        
        update_user_meta($user_id, 'b2b_approval_status', 'approved');
        
        // Send email notification using custom template
        $user = get_userdata($user_id);
        $templates = get_option('b2b_email_templates', []);
        
        $subject = $templates['user_approval_subject'] ?? __('Your B2B Account Approved', 'b2b-commerce-pro');
        $message = $templates['user_approval_message'] ?? __('Congratulations! Your B2B account has been approved. You can now log in and access wholesale pricing.', 'b2b-commerce-pro');
        
        // Replace variables
        $subject = str_replace(['{user_name}', '{login_url}', '{site_name}'], 
            [$user->display_name, wp_login_url(), get_bloginfo('name')], $subject);
        $message = str_replace(['{user_name}', '{login_url}', '{site_name}'], 
            [$user->display_name, wp_login_url(), get_bloginfo('name')], $message);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($user->user_email, $subject, $message, $headers);
        
        wp_send_json_success(__('User approved successfully', 'b2b-commerce-pro'));
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

add_action('wp_ajax_b2b_reject_user', function() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'b2b-commerce-pro'));
            return;
        }
        
        if (!isset($_POST['user_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error(__('Missing required parameters', 'b2b-commerce-pro'));
            return;
        }
        
        $user_id = intval($_POST['user_id']);
        $nonce = $_POST['nonce'] ?? '';
        
        if (!wp_verify_nonce($nonce, 'b2b_reject_user_' . $user_id) && !wp_verify_nonce($nonce, 'b2b_reject_user_nonce')) {
            wp_send_json_error(__('Security check failed', 'b2b-commerce-pro'));
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(__('User not found', 'b2b-commerce-pro'));
            return;
        }
        
        update_user_meta($user_id, 'b2b_approval_status', 'rejected');
        
        // Send email notification using custom template
        $user = get_userdata($user_id);
        $templates = get_option('b2b_email_templates', []);
        
        $subject = $templates['user_rejection_subject'] ?? __('B2B Account Application Status', 'b2b-commerce-pro');
        $message = $templates['user_rejection_message'] ?? __('We regret to inform you that your B2B account application has been rejected. Please contact us for more information.', 'b2b-commerce-pro');
        
        // Replace variables
        $subject = str_replace(['{user_name}', '{site_name}'], 
            [$user->display_name, get_bloginfo('name')], $subject);
        $message = str_replace(['{user_name}', '{site_name}'], 
            [$user->display_name, get_bloginfo('name')], $message);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($user->user_email, $subject, $message, $headers);
        
        wp_send_json_success(__('User rejected successfully', 'b2b-commerce-pro'));
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

add_action('wp_ajax_b2b_save_pricing_rule', function() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'b2b-commerce-pro'));
            return;
        }
        // Verify nonce (supports both form and AJAX usage)
        $form_nonce = $_POST['b2b_pricing_nonce'] ?? '';
        $ajax_nonce = $_POST['nonce'] ?? '';
        if (!( $form_nonce && wp_verify_nonce($form_nonce, 'b2b_pricing_action') )
            && !( $ajax_nonce && wp_verify_nonce($ajax_nonce, 'b2b_pricing_nonce') )) {
            wp_send_json_error(__('Security check failed', 'b2b-commerce-pro'));
            return;
        }

        // Validate required fields
        $required_fields = ['role', 'type', 'price', 'min_qty'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error(sprintf(__('Missing required field: %s', 'b2b-commerce-pro'), $field));
                return;
            }
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        
        // Ensure table exists
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) {
            if (class_exists('B2B\\PricingManager')) {
                B2B\PricingManager::create_pricing_table();
                $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
                if ($exists !== $table) {
                    wp_send_json_error(__('Failed to create database table', 'b2b-commerce-pro'));
                    return;
                }
            } else {
                wp_send_json_error(__('PricingManager class not found', 'b2b-commerce-pro'));
                return;
            }
        }
        
        $data = array(
            'product_id' => 0,
            'role' => sanitize_text_field($_POST['role']),
            'user_id' => 0,
            'group_id' => 0,
            'geo_zone' => '',
            'start_date' => '',
            'end_date' => '',
            'min_qty' => intval($_POST['min_qty']),
            'max_qty' => 0,
            'price' => floatval($_POST['price']),
            'type' => sanitize_text_field($_POST['type'])
        );
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            wp_send_json_error(sprintf(__('Database error: %s', 'b2b-commerce-pro'), $wpdb->last_error));
        } else {
            wp_send_json_success(__('Pricing rule saved successfully', 'b2b-commerce-pro'));
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

add_action('wp_ajax_b2b_delete_pricing_rule', function() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'b2b-commerce-pro'));
            return;
        }
        // Verify nonce
        $nonce = $_POST['nonce'] ?? '';
        if (!$nonce || !wp_verify_nonce($nonce, 'b2b_pricing_nonce')) {
            wp_send_json_error(__('Security check failed', 'b2b-commerce-pro'));
            return;
        }

        if (!isset($_POST['rule_id'])) {
            wp_send_json_error(__('Missing rule ID', 'b2b-commerce-pro'));
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $rule_id = intval($_POST['rule_id']);
        
        $result = $wpdb->delete($table, array('id' => $rule_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error(sprintf(__('Database error: %s', 'b2b-commerce-pro'), $wpdb->last_error));
        } else {
            wp_send_json_success(__('Pricing rule deleted successfully', 'b2b-commerce-pro'));
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

add_action('wp_ajax_b2b_export_data', function() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'b2b-commerce-pro'));
    }
    
    $type = sanitize_text_field($_POST['type']);
    $nonce = $_POST['nonce'];
    
    if (!wp_verify_nonce($nonce, 'b2b_ajax_nonce')) {
        wp_send_json_error(__('Security check failed', 'b2b-commerce-pro'));
    }
    
    switch ($type) {
        case 'users':
            $users = get_users(['role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer']]);
            $csv_data = __("User,Email,Role,Company,Approval Status", 'b2b-commerce-pro') . "\n";
            
            if (empty($users)) {
                $csv_data .= __("No B2B users found", 'b2b-commerce-pro') . "\n";
            } else {
                foreach ($users as $user) {
                    $csv_data .= sprintf(
                        "%s,%s,%s,%s,%s\n",
                        $user->user_login,
                        $user->user_email,
                        implode(',', $user->roles),
                        get_user_meta($user->ID, 'company_name', true),
                        get_user_meta($user->ID, 'b2b_approval_status', true) ?: 'pending'
                    );
                }
            }
            break;
            
        case 'pricing':
            global $wpdb;
            $table = $wpdb->prefix . 'b2b_pricing_rules';
            $rules = $wpdb->get_results("SELECT * FROM $table");
            $csv_data = __("Customer Type,Pricing Type,Value,Min Quantity", 'b2b-commerce-pro') . "\n";
            
            if (empty($rules)) {
                $csv_data .= __("No pricing rules found", 'b2b-commerce-pro') . "\n";
            } else {
                foreach ($rules as $rule) {
                    $csv_data .= sprintf(
                        "%s,%s,%s,%s\n",
                        $rule->role,
                        $rule->type,
                        $rule->price,
                        $rule->min_qty
                    );
                }
            }
            break;
            
        case 'orders':
            if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) {
                wp_send_json_error('WooCommerce is required for order export');
                return;
            }
            
            $orders = wc_get_orders(['limit' => -1]);
            $csv_data = __("Order ID,Date,Status,Customer,Total,Payment Method", 'b2b-commerce-pro') . "\n";
            
            if (empty($orders)) {
                $csv_data .= __("No orders found", 'b2b-commerce-pro') . "\n";
            } else {
                foreach ($orders as $order) {
                    $customer = $order->get_customer_id() ? get_userdata($order->get_customer_id()) : null;
                    $customer_name = $customer ? $customer->display_name : 'Guest';
                    
                    $csv_data .= sprintf(
                        "%s,%s,%s,%s,%s,%s\n",
                        $order->get_id(),
                        $order->get_date_created()->date('Y-m-d H:i:s'),
                        $order->get_status(),
                        $customer_name,
                        $order->get_total(),
                        $order->get_payment_method_title()
                    );
                }
            }
            break;
            
        default:
            wp_send_json_error(__('Invalid export type', 'b2b-commerce-pro'));
    }
    
    wp_send_json_success($csv_data);
});

// AJAX handler for bulk product search
add_action('wp_ajax_b2b_bulk_product_search', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error(__('User not logged in', 'b2b-commerce-pro'));
        return;
    }
    
    $term = sanitize_text_field($_GET['term'] ?? '');
    if (empty($term)) {
        wp_send_json_success([]);
        return;
    }
    
    $args = [
        'post_type' => 'product',
        'posts_per_page' => 10,
        's' => $term,
        'post_status' => 'publish'
    ];
    
    $query = new WP_Query($args);
    $results = [];
    
    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if ($product) {
            $results[] = [
                'id' => $post->ID,
                'text' => $product->get_name() . ' (SKU: ' . $product->get_sku() . ')'
            ];
        }
    }
    
    wp_send_json_success($results);
});

add_action('wp_ajax_nopriv_b2b_bulk_product_search', function() {
    wp_send_json_error(__('Login required', 'b2b-commerce-pro'));
});

// Import/Export AJAX handlers
add_action('wp_ajax_b2b_download_template', function() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'b2b-commerce-pro'));
    }
    
    $type = sanitize_text_field($_GET['type']);
    $nonce = $_GET['nonce'];
    
    if (!wp_verify_nonce($nonce, 'b2b_template_nonce')) {
        wp_die(__('Security check failed.', 'b2b-commerce-pro'));
    }
    
    switch ($type) {
        case 'users':
            $csv_data = __("Username,Email,First Name,Last Name,Company Name,Business Type,Phone,Role,Approval Status", 'b2b-commerce-pro') . "\n";
            $csv_data .= "john_doe,john@example.com,John,Doe,ABC Company,Retail,555-0123,wholesale_customer,approved\n";
            break;
            
        case 'pricing':
            $csv_data = __("Role,Type,Price,Min Quantity,Max Quantity,Product ID", 'b2b-commerce-pro') . "\n";
            $csv_data .= "wholesale_customer,percentage,10,10,0,0\n";
            $csv_data .= "distributor,fixed,5,50,0,0\n";
            break;
            
        default:
            wp_die(__('Invalid template type.', 'b2b-commerce-pro'));
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="b2b_' . $type . '_template.csv"');
    echo $csv_data;
    exit;
});

// Demo data import handler
add_action('wp_ajax_b2b_import_demo_data', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'b2b-commerce-pro'));
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'b2b_import_demo')) {
        wp_send_json_error(__('Security check failed', 'b2b-commerce-pro'));
        return;
    }
    
    try {
        // Create demo B2B users
        $demo_users = [
            [
                'user_login' => 'wholesale_demo',
                'user_email' => 'wholesale@demo.com',
                'user_pass' => 'demo123',
                'first_name' => 'John',
                'last_name' => 'Wholesale',
                'role' => 'wholesale_customer',
                'company' => __('Demo Wholesale Co.', 'b2b-commerce-pro'),
                'phone' => '555-0101'
            ],
            [
                'user_login' => 'distributor_demo',
                'user_email' => 'distributor@demo.com',
                'user_pass' => 'demo123',
                'first_name' => 'Jane',
                'last_name' => 'Distributor',
                'role' => 'distributor',
                'company' => __('Demo Distributor Inc.', 'b2b-commerce-pro'),
                'phone' => '555-0102'
            ],
            [
                'user_login' => 'retailer_demo',
                'user_email' => 'retailer@demo.com',
                'user_pass' => 'demo123',
                'first_name' => 'Mike',
                'last_name' => 'Retailer',
                'role' => 'retailer',
                'company' => __('Demo Retail Store', 'b2b-commerce-pro'),
                'phone' => '555-0103'
            ]
        ];
        
        foreach ($demo_users as $user_data) {
            $user_id = wp_create_user($user_data['user_login'], $user_data['user_pass'], $user_data['user_email']);
            if (!is_wp_error($user_id)) {
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $user_data['first_name'],
                    'last_name' => $user_data['last_name'],
                    'role' => $user_data['role']
                ]);
                
                update_user_meta($user_id, 'company_name', $user_data['company']);
                update_user_meta($user_id, 'phone', $user_data['phone']);
                update_user_meta($user_id, 'b2b_approval_status', 'approved');
            }
        }
        
        // Create demo pricing rules with more flexible minimums
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $demo_rules = [
                [
                    'role' => 'wholesale_customer',
                    'type' => 'percentage',
                    'price' => 15,
                    'min_qty' => 5  // Reduced from 10 to 5
                ],
                [
                    'role' => 'distributor',
                    'type' => 'percentage',
                    'price' => 25,
                    'min_qty' => 20  // Reduced from 50 to 20
                ],
                [
                    'role' => 'retailer',
                    'type' => 'fixed',
                    'price' => 5,
                    'min_qty' => 1   // Reduced from 5 to 1
                ]
            ];
            
            foreach ($demo_rules as $rule) {
                $wpdb->insert($table, [
                    'product_id' => 0,
                    'role' => $rule['role'],
                    'user_id' => 0,
                    'group_id' => 0,
                    'geo_zone' => '',
                    'start_date' => '',
                    'end_date' => '',
                    'min_qty' => $rule['min_qty'],
                    'max_qty' => 0,
                    'price' => $rule['price'],
                    'type' => $rule['type']
                ]);
            }
        }
        
        wp_send_json_success(sprintf(__('Demo data imported successfully! Created %d users and %d pricing rules.', 'b2b-commerce-pro'), count($demo_users), count($demo_rules)));
        
    } catch (Exception $e) {
        wp_send_json_error(sprintf(__('Error importing demo data: %s', 'b2b-commerce-pro'), $e->getMessage()));
    }
});

// Note: Quote request and bulk pricing AJAX handlers are handled in AdvancedFeatures.php

// Enqueue modern admin CSS and JS for all B2B Commerce Pro admin pages
add_action('admin_enqueue_scripts', function($hook) {
    if (isset($_GET['page']) && strpos($_GET['page'], 'b2b-') === 0) {
        wp_enqueue_style(
            'b2b-admin-standalone-demo',
            B2B_COMMERCE_PRO_URL . 'assets/css/b2b-admin-standalone-demo.css',
            [],
            B2B_COMMERCE_PRO_VERSION
        );
        
        wp_enqueue_script(
            'b2b-commerce-pro-admin',
            B2B_COMMERCE_PRO_URL . 'assets/js/b2b-commerce-pro.js',
            ['jquery'],
            B2B_COMMERCE_PRO_VERSION,
            true
        );
        
        // Localize script for AJAX with proper nonce
        wp_localize_script('b2b-commerce-pro-admin', 'b2b_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2b_ajax_nonce'),
            'approve_nonce' => wp_create_nonce('b2b_approve_user_nonce'),
            'reject_nonce' => wp_create_nonce('b2b_reject_user_nonce'),
            'pricing_nonce' => wp_create_nonce('b2b_pricing_nonce'),
            'bulk_user_nonce' => wp_create_nonce('b2b_bulk_user_action')
        ));
    }
});

// Also localize for frontend
add_action('wp_enqueue_scripts', function() {
    wp_localize_script('jquery', 'b2b_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('b2b_ajax_nonce')
    ));
}); 

// Bulk user actions (approve / reject / delete)
add_action('wp_ajax_b2b_bulk_user_action', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'b2b-commerce-pro'));
    }
    $nonce = $_POST['nonce'] ?? '';
    if (!$nonce || !wp_verify_nonce($nonce, 'b2b_bulk_user_action')) {
        wp_send_json_error(__('Security check failed', 'b2b-commerce-pro'));
    }

    $operation = isset($_POST['operation']) ? sanitize_text_field($_POST['operation']) : '';
    $user_ids = isset($_POST['user_ids']) && is_array($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : [];
    if (!$operation || empty($user_ids)) {
        wp_send_json_error(__('Missing data', 'b2b-commerce-pro'));
    }

    $affected = 0;
    foreach ($user_ids as $uid) {
        if ($uid <= 0) { continue; }
        if ($operation === 'approve') {
            update_user_meta($uid, 'b2b_approval_status', 'approved');
            $affected++;
        } elseif ($operation === 'reject') {
            update_user_meta($uid, 'b2b_approval_status', 'rejected');
            $affected++;
        } elseif ($operation === 'delete') {
            if (current_user_can('delete_users') && get_current_user_id() !== $uid) {
                if (function_exists('wp_delete_user')) {
                    $deleted = wp_delete_user($uid);
                    if ($deleted) { $affected++; }
                }
            }
        }
    }

    wp_send_json_success(array('affected' => $affected));
});

// AJAX handler for quote deletion
add_action('wp_ajax_b2b_delete_quote_ajax', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'b2b-commerce-pro'));
    }
    
    $nonce = $_POST['nonce'] ?? '';
    if (!$nonce || !wp_verify_nonce($nonce, 'b2b_delete_quote_ajax')) {
        wp_send_json_error(__('Security check failed', 'b2b-commerce-pro'));
    }
    
    $index = isset($_POST['quote_index']) ? absint($_POST['quote_index']) : -1;
    if ($index < 0) {
        wp_send_json_error(__('Invalid quote index', 'b2b-commerce-pro'));
    }
    
    $quotes = get_option('b2b_quote_requests', []);
    if (!isset($quotes[$index])) {
        wp_send_json_error(__('Quote not found', 'b2b-commerce-pro'));
    }
    
    // Remove the quote from the array
    unset($quotes[$index]);
    
    // Reindex the array to maintain sequential keys
    $quotes = array_values($quotes);
    
    $result = update_option('b2b_quote_requests', $quotes);
    
    if ($result) {
        wp_send_json_success(__('Quote deleted successfully', 'b2b-commerce-pro'));
    } else {
        wp_send_json_error(__('Failed to delete quote', 'b2b-commerce-pro'));
    }
});

// Add admin notice about created pages
add_action('admin_notices', function() {
    if (get_option('b2b_pages_created_notice')) {
        $registration_page_id = get_option('b2b_registration_page_id');
        $dashboard_page_id = get_option('b2b_dashboard_page_id');
        $bulk_order_page_id = get_option('b2b_bulk_order_page_id');
        $account_page_id = get_option('b2b_account_page_id');
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<h3>🎉 ' . esc_html__('B2B Commerce Pro - Pages Created Successfully!', 'b2b-commerce-pro') . '</h3>';
        echo '<p>' . esc_html__('The following B2B pages have been automatically created:', 'b2b-commerce-pro') . '</p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        
        if ($registration_page_id) {
            echo '<li><strong>' . esc_html__('B2B Registration:', 'b2b-commerce-pro') . '</strong> <a href="' . esc_url(get_edit_post_link($registration_page_id)) . '">' . esc_html__('Edit Page', 'b2b-commerce-pro') . '</a> | <a href="' . esc_url(get_permalink($registration_page_id)) . '" target="_blank">' . esc_html__('View Page', 'b2b-commerce-pro') . '</a></li>';
        }
        
        if ($dashboard_page_id) {
            echo '<li><strong>' . esc_html__('B2B Dashboard:', 'b2b-commerce-pro') . '</strong> <a href="' . esc_url(get_edit_post_link($dashboard_page_id)) . '">' . esc_html__('Edit Page', 'b2b-commerce-pro') . '</a> | <a href="' . esc_url(get_permalink($dashboard_page_id)) . '" target="_blank">' . esc_html__('View Page', 'b2b-commerce-pro') . '</a></li>';
        }
        
        if ($bulk_order_page_id) {
            echo '<li><strong>' . esc_html__('Bulk Order:', 'b2b-commerce-pro') . '</strong> <a href="' . esc_url(get_edit_post_link($bulk_order_page_id)) . '">' . esc_html__('Edit Page', 'b2b-commerce-pro') . '</a> | <a href="' . esc_url(get_permalink($bulk_order_page_id)) . '" target="_blank">' . esc_html__('View Page', 'b2b-commerce-pro') . '</a></li>';
        }
        
        if ($account_page_id) {
            echo '<li><strong>' . esc_html__('Account Settings:', 'b2b-commerce-pro') . '</strong> <a href="' . esc_url(get_edit_post_link($account_page_id)) . '">' . esc_html__('Edit Page', 'b2b-commerce-pro') . '</a> | <a href="' . esc_url(get_permalink($account_page_id)) . '" target="_blank">' . esc_html__('View Page', 'b2b-commerce-pro') . '</a></li>';
        }
        
        echo '</ul>';
        echo '<p><strong>' . esc_html__('Next Steps:', 'b2b-commerce-pro') . '</strong></p>';
        echo '<ol style="list-style: decimal; margin-left: 20px;">';
        echo '<li>✅ ' . esc_html__('"B2B Registration" has been automatically added to your main navigation menu', 'b2b-commerce-pro') . '</li>';
        echo '<li>' . esc_html__('Add "B2B Dashboard" and "Bulk Order" to your user menu (after login)', 'b2b-commerce-pro') . '</li>';
        echo '<li>' . sprintf(esc_html__('Configure B2B settings in %s', 'b2b-commerce-pro'), '<a href="' . admin_url('admin.php?page=b2b-commerce-pro') . '">' . esc_html__('B2B Commerce Pro Settings', 'b2b-commerce-pro') . '</a>') . '</li>';
        echo '</ol>';
        echo '<p><em>' . esc_html__('All pages are ready to use with the appropriate shortcodes already added!', 'b2b-commerce-pro') . '</em></p>';
        echo '</div>';
        
        // Remove the notice flag
        delete_option('b2b_pages_created_notice');
    }
});