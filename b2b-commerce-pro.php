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

autoload_b2b_commerce_pro();

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

// Bootstrap the plugin and load text domain
add_action( 'plugins_loaded', function() {
    // i18n support
    load_plugin_textdomain( 'b2b-commerce-pro', false, dirname( B2B_COMMERCE_PRO_BASENAME ) . '/languages' );
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
            echo '<div class="notice notice-error"><p><strong>B2B Commerce Pro:</strong> Required classes not found. Please reinstall the plugin.</p></div>';
        });
    }
} );

// Register activation and deactivation hooks
register_activation_hook( __FILE__, function() {
    try {
        // Create pricing table
        if ( class_exists( 'B2B\\PricingManager' ) ) {
            B2B\PricingManager::create_pricing_table();
        }
        // Add roles
        if ( class_exists( 'B2B\\UserManager' ) ) {
            B2B\UserManager::add_roles();
        }
    } catch ( Exception $e ) {
        // Log activation error
        error_log( 'B2B Commerce Pro Activation Error: ' . $e->getMessage() );
    }
} );

register_deactivation_hook( __FILE__, function() {
    try {
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
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        if (!isset($_POST['user_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        $user_id = intval($_POST['user_id']);
        $nonce = $_POST['nonce'] ?? '';
        
        // Accept either per-user nonce or a generic approve nonce for flexibility
        if (!wp_verify_nonce($nonce, 'b2b_approve_user_' . $user_id) && !wp_verify_nonce($nonce, 'b2b_approve_user_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('User not found');
            return;
        }
        
        update_user_meta($user_id, 'b2b_approval_status', 'approved');
        
        // Send email notification using custom template
        $user = get_userdata($user_id);
        $templates = get_option('b2b_email_templates', []);
        
        $subject = $templates['user_approval_subject'] ?? 'Your B2B Account Approved';
        $message = $templates['user_approval_message'] ?? 'Congratulations! Your B2B account has been approved. You can now log in and access wholesale pricing.';
        
        // Replace variables
        $subject = str_replace(['{user_name}', '{login_url}', '{site_name}'], 
            [$user->display_name, wp_login_url(), get_bloginfo('name')], $subject);
        $message = str_replace(['{user_name}', '{login_url}', '{site_name}'], 
            [$user->display_name, wp_login_url(), get_bloginfo('name')], $message);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($user->user_email, $subject, $message, $headers);
        
        wp_send_json_success('User approved successfully');
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

add_action('wp_ajax_b2b_reject_user', function() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        if (!isset($_POST['user_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        $user_id = intval($_POST['user_id']);
        $nonce = $_POST['nonce'] ?? '';
        
        if (!wp_verify_nonce($nonce, 'b2b_reject_user_' . $user_id) && !wp_verify_nonce($nonce, 'b2b_reject_user_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('User not found');
            return;
        }
        
        update_user_meta($user_id, 'b2b_approval_status', 'rejected');
        
        // Send email notification using custom template
        $user = get_userdata($user_id);
        $templates = get_option('b2b_email_templates', []);
        
        $subject = $templates['user_rejection_subject'] ?? 'B2B Account Application Status';
        $message = $templates['user_rejection_message'] ?? 'We regret to inform you that your B2B account application has been rejected. Please contact us for more information.';
        
        // Replace variables
        $subject = str_replace(['{user_name}', '{site_name}'], 
            [$user->display_name, get_bloginfo('name')], $subject);
        $message = str_replace(['{user_name}', '{site_name}'], 
            [$user->display_name, get_bloginfo('name')], $message);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($user->user_email, $subject, $message, $headers);
        
        wp_send_json_success('User rejected successfully');
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

add_action('wp_ajax_b2b_save_pricing_rule', function() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        // Verify nonce (supports both form and AJAX usage)
        $form_nonce = $_POST['b2b_pricing_nonce'] ?? '';
        $ajax_nonce = $_POST['nonce'] ?? '';
        if (!( $form_nonce && wp_verify_nonce($form_nonce, 'b2b_pricing_action') )
            && !( $ajax_nonce && wp_verify_nonce($ajax_nonce, 'b2b_pricing_nonce') )) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Validate required fields
        $required_fields = ['role', 'type', 'price', 'min_qty'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error('Missing required field: ' . $field);
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
                    wp_send_json_error('Failed to create database table');
                    return;
                }
            } else {
                wp_send_json_error('PricingManager class not found');
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
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        } else {
            wp_send_json_success('Pricing rule saved successfully');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

add_action('wp_ajax_b2b_delete_pricing_rule', function() {
    try {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        // Verify nonce
        $nonce = $_POST['nonce'] ?? '';
        if (!$nonce || !wp_verify_nonce($nonce, 'b2b_pricing_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!isset($_POST['rule_id'])) {
            wp_send_json_error('Missing rule ID');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $rule_id = intval($_POST['rule_id']);
        
        $result = $wpdb->delete($table, array('id' => $rule_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        } else {
            wp_send_json_success('Pricing rule deleted successfully');
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

add_action('wp_ajax_b2b_export_data', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $type = sanitize_text_field($_POST['type']);
    $nonce = $_POST['nonce'];
    
    if (!wp_verify_nonce($nonce, 'b2b_ajax_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    switch ($type) {
        case 'users':
            $users = get_users(['role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer']]);
            $csv_data = "User,Email,Role,Company,Approval Status\n";
            
            if (empty($users)) {
                $csv_data .= "No B2B users found\n";
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
            $csv_data = "Customer Type,Pricing Type,Value,Min Quantity\n";
            
            if (empty($rules)) {
                $csv_data .= "No pricing rules found\n";
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
            $csv_data = "Order ID,Date,Status,Customer,Total,Payment Method\n";
            
            if (empty($orders)) {
                $csv_data .= "No orders found\n";
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
            wp_send_json_error('Invalid export type');
    }
    
    wp_send_json_success($csv_data);
});

// AJAX handler for bulk product search
add_action('wp_ajax_b2b_bulk_product_search', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
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
    wp_send_json_error('Login required');
});

// Import/Export AJAX handlers
add_action('wp_ajax_b2b_download_template', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $type = sanitize_text_field($_GET['type']);
    $nonce = $_GET['nonce'];
    
    if (!wp_verify_nonce($nonce, 'b2b_template_nonce')) {
        wp_die('Security check failed');
    }
    
    switch ($type) {
        case 'users':
            $csv_data = "Username,Email,First Name,Last Name,Company Name,Business Type,Phone,Role,Approval Status\n";
            $csv_data .= "john_doe,john@example.com,John,Doe,ABC Company,Retail,555-0123,wholesale_customer,approved\n";
            break;
            
        case 'pricing':
            $csv_data = "Role,Type,Price,Min Quantity,Max Quantity,Product ID\n";
            $csv_data .= "wholesale_customer,percentage,10,10,0,0\n";
            $csv_data .= "distributor,fixed,5,50,0,0\n";
            break;
            
        default:
            wp_die('Invalid template type');
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="b2b_' . $type . '_template.csv"');
    echo $csv_data;
    exit;
});

// Demo data import handler
add_action('wp_ajax_b2b_import_demo_data', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'b2b_import_demo')) {
        wp_send_json_error('Security check failed');
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
                'company' => 'Demo Wholesale Co.',
                'phone' => '555-0101'
            ],
            [
                'user_login' => 'distributor_demo',
                'user_email' => 'distributor@demo.com',
                'user_pass' => 'demo123',
                'first_name' => 'Jane',
                'last_name' => 'Distributor',
                'role' => 'distributor',
                'company' => 'Demo Distributor Inc.',
                'phone' => '555-0102'
            ],
            [
                'user_login' => 'retailer_demo',
                'user_email' => 'retailer@demo.com',
                'user_pass' => 'demo123',
                'first_name' => 'Mike',
                'last_name' => 'Retailer',
                'role' => 'retailer',
                'company' => 'Demo Retail Store',
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
        
        // Create demo pricing rules
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $demo_rules = [
                [
                    'role' => 'wholesale_customer',
                    'type' => 'percentage',
                    'price' => 15,
                    'min_qty' => 10
                ],
                [
                    'role' => 'distributor',
                    'type' => 'percentage',
                    'price' => 25,
                    'min_qty' => 50
                ],
                [
                    'role' => 'retailer',
                    'type' => 'fixed',
                    'price' => 5,
                    'min_qty' => 5
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
        
        wp_send_json_success('Demo data imported successfully! Created ' . count($demo_users) . ' users and ' . count($demo_rules) . ' pricing rules.');
        
    } catch (Exception $e) {
        wp_send_json_error('Error importing demo data: ' . $e->getMessage());
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
        wp_send_json_error('Unauthorized');
    }
    $nonce = $_POST['nonce'] ?? '';
    if (!$nonce || !wp_verify_nonce($nonce, 'b2b_bulk_user_action')) {
        wp_send_json_error('Security check failed');
    }

    $operation = isset($_POST['operation']) ? sanitize_text_field($_POST['operation']) : '';
    $user_ids = isset($_POST['user_ids']) && is_array($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : [];
    if (!$operation || empty($user_ids)) {
        wp_send_json_error('Missing data');
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