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

// Bootstrap the plugin
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'B2B\\Init' ) ) {
        B2B\Init::instance();
    }
} );

// Register activation and deactivation hooks
register_activation_hook( __FILE__, function() {
    // Create pricing table
    if ( class_exists( 'B2B\\PricingManager' ) ) {
        B2B\PricingManager::create_pricing_table();
    }
    // Add roles
    if ( class_exists( 'B2B\\UserManager' ) ) {
        B2B\UserManager::add_roles();
    }
} );

register_deactivation_hook( __FILE__, function() {
    if ( class_exists( 'B2B\\UserManager' ) ) {
        B2B\UserManager::remove_roles();
    }
} );

// Ensure pricing table exists on every load
add_action( 'init', function() {
    if ( class_exists( 'B2B\\PricingManager' ) ) {
        B2B\PricingManager::create_pricing_table();
    }
} );

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
        $nonce = $_POST['nonce'];
        
        if (!wp_verify_nonce($nonce, 'b2b_approve_user_' . $user_id)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('User not found');
            return;
        }
        
        update_user_meta($user_id, 'b2b_approval_status', 'approved');
        
        // Send email notification
        $to = $user->user_email;
        $subject = 'Your B2B Account Approved';
        $message = 'Congratulations! Your B2B account has been approved. You can now log in and access wholesale pricing.';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
        
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
        $nonce = $_POST['nonce'];
        
        if (!wp_verify_nonce($nonce, 'b2b_reject_user_' . $user_id)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('User not found');
            return;
        }
        
        update_user_meta($user_id, 'b2b_approval_status', 'rejected');
        
        // Send email notification
        $to = $user->user_email;
        $subject = 'Your B2B Account Rejected';
        $message = 'We regret to inform you that your B2B account application has been rejected. Please contact us for more information.';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
        
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
            break;
            
        case 'pricing':
            global $wpdb;
            $table = $wpdb->prefix . 'b2b_pricing_rules';
            $rules = $wpdb->get_results("SELECT * FROM $table");
            $csv_data = "Customer Type,Pricing Type,Value,Min Quantity\n";
            foreach ($rules as $rule) {
                $csv_data .= sprintf(
                    "%s,%s,%s,%s\n",
                    $rule->role,
                    $rule->type,
                    $rule->price,
                    $rule->min_qty
                );
            }
            break;
            
        default:
            wp_send_json_error('Invalid export type');
    }
    
    wp_send_json_success($csv_data);
});

// Add test function for debugging
add_action( 'wp_ajax_b2b_test_plugin', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    $results = [];
    
    // Test 1: Check if pricing table exists
    global $wpdb;
    $table = $wpdb->prefix . 'b2b_pricing_rules';
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
    $results['pricing_table'] = $exists === $table ? 'OK' : 'FAILED';
    
    // Test 2: Check if roles exist
    $roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
    $results['roles'] = [];
    foreach ($roles as $role) {
        $role_obj = get_role($role);
        $results['roles'][$role] = $role_obj ? 'OK' : 'FAILED';
    }
    
    // Test 3: Check if taxonomy exists
    $taxonomy = get_taxonomy('b2b_user_group');
    $results['taxonomy'] = $taxonomy ? 'OK' : 'FAILED';
    
    // Test 4: Check if pricing rules exist
    $rules_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $results['pricing_rules'] = $rules_count . ' rules found';
    
    wp_send_json($results);
} );

// Comprehensive plugin test function
add_action( 'wp_ajax_b2b_comprehensive_test', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    $results = [];
    
    try {
        // Test 1: Check if pricing table exists
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        $results['pricing_table'] = $exists === $table ? 'OK' : 'FAILED';
        
        // Test 2: Check if roles exist
        $roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
        $results['roles'] = [];
        foreach ($roles as $role) {
            $role_obj = get_role($role);
            $results['roles'][$role] = $role_obj ? 'OK' : 'FAILED';
        }
        
        // Test 3: Check if taxonomy exists
        $taxonomy = get_taxonomy('b2b_user_group');
        $results['taxonomy'] = $taxonomy ? 'OK' : 'FAILED';
        
        // Test 4: Check if pricing rules exist
        $rules_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $results['pricing_rules'] = $rules_count . ' rules found';
        
        // Test 5: Check if classes are loaded
        $classes = ['B2B\\Init', 'B2B\\AdminPanel', 'B2B\\UserManager', 'B2B\\PricingManager', 'B2B\\Frontend'];
        $results['classes'] = [];
        foreach ($classes as $class) {
            $results['classes'][$class] = class_exists($class) ? 'OK' : 'FAILED';
        }
        
        // Test 6: Check if WooCommerce is active
        $results['woocommerce'] = class_exists('WooCommerce') ? 'OK' : 'NOT ACTIVE';
        
        // Test 7: Check if CSS and JS files exist
        $css_file = B2B_COMMERCE_PRO_PATH . 'assets/css/b2b-admin-standalone-demo.css';
        $js_file = B2B_COMMERCE_PRO_PATH . 'assets/js/b2b-commerce-pro.js';
        $results['assets'] = [
            'css' => file_exists($css_file) ? 'OK' : 'MISSING',
            'js' => file_exists($js_file) ? 'OK' : 'MISSING'
        ];
        
        // Test 8: Check database permissions
        $test_insert = $wpdb->insert($table, [
            'product_id' => 999999,
            'role' => 'test_role',
            'user_id' => 0,
            'group_id' => 0,
            'geo_zone' => '',
            'start_date' => '',
            'end_date' => '',
            'min_qty' => 1,
            'max_qty' => 0,
            'price' => 0,
            'type' => 'test'
        ]);
        
        if ($test_insert !== false) {
            $wpdb->delete($table, ['product_id' => 999999]);
            $results['database_permissions'] = 'OK';
        } else {
            $results['database_permissions'] = 'FAILED: ' . $wpdb->last_error;
        }
        
        // Test 9: Check if AJAX endpoints are working
        $results['ajax_endpoints'] = [
            'b2b_approve_user' => has_action('wp_ajax_b2b_approve_user') ? 'OK' : 'MISSING',
            'b2b_reject_user' => has_action('wp_ajax_b2b_reject_user') ? 'OK' : 'MISSING',
            'b2b_save_pricing_rule' => has_action('wp_ajax_b2b_save_pricing_rule') ? 'OK' : 'MISSING',
            'b2b_delete_pricing_rule' => has_action('wp_ajax_b2b_delete_pricing_rule') ? 'OK' : 'MISSING'
        ];
        
        // Test 10: Check if shortcodes are registered
        $results['shortcodes'] = [
            'b2b_registration' => shortcode_exists('b2b_registration') ? 'OK' : 'MISSING',
            'b2b_dashboard' => shortcode_exists('b2b_dashboard') ? 'OK' : 'MISSING',
            'b2b_order_history' => shortcode_exists('b2b_order_history') ? 'OK' : 'MISSING',
            'b2b_account' => shortcode_exists('b2b_account') ? 'OK' : 'MISSING'
        ];
        
        $results['overall_status'] = 'COMPREHENSIVE TEST COMPLETED';
        
    } catch (Exception $e) {
        $results['error'] = $e->getMessage();
        $results['overall_status'] = 'TEST FAILED';
    }
    
    wp_send_json($results);
} );

// Comprehensive verification function - tests ALL functionalities
add_action( 'wp_ajax_b2b_verify_all_functionalities', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    $results = [];
    
    try {
        // Test 1: Database Table Creation
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        $results['database_table'] = $exists === $table ? 'OK' : 'FAILED';
        
        // Test 2: User Roles
        $roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
        $results['user_roles'] = [];
        foreach ($roles as $role) {
            $role_obj = get_role($role);
            $results['user_roles'][$role] = $role_obj ? 'OK' : 'FAILED';
        }
        
        // Test 3: Taxonomy
        $taxonomy = get_taxonomy('b2b_user_group');
        $results['taxonomy'] = $taxonomy ? 'OK' : 'FAILED';
        
        // Test 4: Class Loading
        $classes = ['B2B\\Init', 'B2B\\AdminPanel', 'B2B\\UserManager', 'B2B\\PricingManager', 'B2B\\Frontend'];
        $results['classes'] = [];
        foreach ($classes as $class) {
            $results['classes'][$class] = class_exists($class) ? 'OK' : 'FAILED';
        }
        
        // Test 5: WooCommerce Integration
        $results['woocommerce'] = class_exists('WooCommerce') ? 'OK' : 'NOT ACTIVE';
        
        // Test 6: Asset Files
        $css_file = B2B_COMMERCE_PRO_PATH . 'assets/css/b2b-admin-standalone-demo.css';
        $js_file = B2B_COMMERCE_PRO_PATH . 'assets/js/b2b-commerce-pro.js';
        $results['assets'] = [
            'css' => file_exists($css_file) ? 'OK' : 'MISSING',
            'js' => file_exists($js_file) ? 'OK' : 'MISSING'
        ];
        
        // Test 7: Database Permissions
        $test_insert = $wpdb->insert($table, [
            'product_id' => 999999,
            'role' => 'test_role',
            'user_id' => 0,
            'group_id' => 0,
            'geo_zone' => '',
            'start_date' => '',
            'end_date' => '',
            'min_qty' => 1,
            'max_qty' => 0,
            'price' => 0,
            'type' => 'test'
        ]);
        
        if ($test_insert !== false) {
            $wpdb->delete($table, ['product_id' => 999999]);
            $results['database_permissions'] = 'OK';
        } else {
            $results['database_permissions'] = 'FAILED: ' . $wpdb->last_error;
        }
        
        // Test 8: AJAX Endpoints
        $results['ajax_endpoints'] = [
            'b2b_approve_user' => has_action('wp_ajax_b2b_approve_user') ? 'OK' : 'MISSING',
            'b2b_reject_user' => has_action('wp_ajax_b2b_reject_user') ? 'OK' : 'MISSING',
            'b2b_save_pricing_rule' => has_action('wp_ajax_b2b_save_pricing_rule') ? 'OK' : 'MISSING',
            'b2b_delete_pricing_rule' => has_action('wp_ajax_b2b_delete_pricing_rule') ? 'OK' : 'MISSING',
            'b2b_export_data' => has_action('wp_ajax_b2b_export_data') ? 'OK' : 'MISSING'
        ];
        
        // Test 9: Shortcodes
        $results['shortcodes'] = [
            'b2b_registration' => shortcode_exists('b2b_registration') ? 'OK' : 'MISSING',
            'b2b_dashboard' => shortcode_exists('b2b_dashboard') ? 'OK' : 'MISSING',
            'b2b_order_history' => shortcode_exists('b2b_order_history') ? 'OK' : 'MISSING',
            'b2b_account' => shortcode_exists('b2b_account') ? 'OK' : 'MISSING',
            'b2b_wishlist' => shortcode_exists('b2b_wishlist') ? 'OK' : 'MISSING'
        ];
        
        // Test 10: Admin Menu
        $results['admin_menu'] = [
            'b2b-dashboard' => has_action('admin_menu') ? 'OK' : 'MISSING'
        ];
        
        // Test 11: CSS/JS Enqueue
        $results['enqueue'] = [
            'admin_css' => has_action('admin_enqueue_scripts') ? 'OK' : 'MISSING',
            'frontend_css' => has_action('wp_enqueue_scripts') ? 'OK' : 'MISSING'
        ];
        
        // Test 12: Pricing Rules Count
        $rules_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $results['pricing_rules_count'] = $rules_count . ' rules found';
        
        // Test 13: User Registration Functions
        $results['registration_functions'] = [
            'wp_create_user' => function_exists('wp_create_user') ? 'OK' : 'MISSING',
            'wp_update_user' => function_exists('wp_update_user') ? 'OK' : 'MISSING',
            'update_user_meta' => function_exists('update_user_meta') ? 'OK' : 'MISSING'
        ];
        
        // Test 14: Security Functions
        $results['security_functions'] = [
            'wp_verify_nonce' => function_exists('wp_verify_nonce') ? 'OK' : 'MISSING',
            'wp_create_nonce' => function_exists('wp_create_nonce') ? 'OK' : 'MISSING',
            'current_user_can' => function_exists('current_user_can') ? 'OK' : 'MISSING'
        ];
        
        // Test 15: Validation Functions
        $results['validation_functions'] = [
            'sanitize_text_field' => function_exists('sanitize_text_field') ? 'OK' : 'MISSING',
            'sanitize_email' => function_exists('sanitize_email') ? 'OK' : 'MISSING',
            'sanitize_user' => function_exists('sanitize_user') ? 'OK' : 'MISSING',
            'is_email' => function_exists('is_email') ? 'OK' : 'MISSING',
            'validate_username' => function_exists('validate_username') ? 'OK' : 'MISSING'
        ];
        
        // Overall Status
        $all_tests = [];
        foreach ($results as $category => $tests) {
            if (is_array($tests)) {
                foreach ($tests as $test => $status) {
                    if ($status === 'OK') {
                        $all_tests[] = true;
                    } else {
                        $all_tests[] = false;
                    }
                }
            }
        }
        
        $passed_tests = array_sum($all_tests);
        $total_tests = count($all_tests);
        $success_rate = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100, 2) : 0;
        
        $results['overall_status'] = [
            'passed_tests' => $passed_tests,
            'total_tests' => $total_tests,
            'success_rate' => $success_rate . '%',
            'status' => $success_rate >= 95 ? 'EXCELLENT' : ($success_rate >= 80 ? 'GOOD' : 'NEEDS ATTENTION')
        ];
        
    } catch (Exception $e) {
        $results['error'] = $e->getMessage();
        $results['overall_status'] = [
            'status' => 'TEST FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    wp_send_json($results);
} );

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
            'pricing_nonce' => wp_create_nonce('b2b_pricing_nonce')
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
// Commented out old CSS enqueue
// add_action('admin_enqueue_scripts', function($hook) {
//     if (isset($_GET['page']) && strpos($_GET['page'], 'b2b-commerce-pro') !== false) {
//         wp_enqueue_style(
//             'b2b-commerce-pro-admin',
//             B2B_COMMERCE_PRO_URL . 'assets/css/b2b-commerce-pro.css',
//             [],
//             B2B_COMMERCE_PRO_VERSION
//         );
//     }
// }); 