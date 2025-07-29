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

// Enqueue modern admin CSS for all B2B Commerce Pro admin pages
add_action('admin_enqueue_scripts', function($hook) {
    if (isset($_GET['page']) && strpos($_GET['page'], 'b2b-') === 0) {
        wp_enqueue_style(
            'b2b-admin-standalone-demo',
            B2B_COMMERCE_PRO_URL . 'assets/css/b2b-admin-standalone-demo.css',
            [],
            B2B_COMMERCE_PRO_VERSION
        );
    }
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