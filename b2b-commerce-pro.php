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

// Define plugin constants
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