<?php
/**
 * B2B Commerce Pro - Debug Test File
 * This file tests all major functionality to identify issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Only allow admin access
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

echo "<h1>🔍 B2B Commerce Pro - Comprehensive Debug Test</h1>";

// Test 1: Check if classes are loaded
echo "<h2>1. Class Loading Test</h2>";
$classes = [
    'B2B\\Init',
    'B2B\\AdminPanel', 
    'B2B\\UserManager',
    'B2B\\PricingManager',
    'B2B\\Frontend',
    'B2B\\ProductManager',
    'B2B\\AdvancedFeatures',
    'B2B\\Reporting'
];

foreach ($classes as $class) {
    $status = class_exists($class) ? '✅ PASS' : '❌ FAIL';
    echo "<p><strong>$class:</strong> $status</p>";
}

// Test 2: Check database table
echo "<h2>2. Database Table Test</h2>";
global $wpdb;
$table = $wpdb->prefix . 'b2b_pricing_rules';
$exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));

if ($exists === $table) {
    echo "<p>✅ Pricing table exists</p>";
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "<p>📊 Table has $count rules</p>";
} else {
    echo "<p>❌ Pricing table does not exist</p>";
    echo "<p>🔧 Attempting to create table...</p>";
    
    if (class_exists('B2B\\PricingManager')) {
        B2B\PricingManager::create_pricing_table();
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists === $table) {
            echo "<p>✅ Table created successfully!</p>";
        } else {
            echo "<p>❌ Failed to create table</p>";
        }
    } else {
        echo "<p>❌ PricingManager class not found</p>";
    }
}

// Test 3: Check user roles
echo "<h2>3. User Roles Test</h2>";
$roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
foreach ($roles as $role) {
    $role_obj = get_role($role);
    $status = $role_obj ? '✅ PASS' : '❌ FAIL';
    echo "<p>Role '$role': $status</p>";
}

// Test 4: Check WooCommerce integration
echo "<h2>4. WooCommerce Integration Test</h2>";
$wc_status = class_exists('WooCommerce') ? '✅ ACTIVE' : '❌ NOT ACTIVE';
echo "<p>WooCommerce: $wc_status</p>";

if (class_exists('WooCommerce')) {
    $wc_functions = [
        'wc_get_orders' => function_exists('wc_get_orders'),
        'wc_get_order' => function_exists('wc_get_order'),
        'wc_price' => function_exists('wc_price')
    ];
    
    foreach ($wc_functions as $func => $exists) {
        $status = $exists ? '✅ PASS' : '❌ FAIL';
        echo "<p>Function '$func': $status</p>";
    }
}

// Test 5: Check AJAX handlers
echo "<h2>5. AJAX Handlers Test</h2>";
$ajax_handlers = [
    'wp_ajax_b2b_approve_user',
    'wp_ajax_b2b_reject_user', 
    'wp_ajax_b2b_save_pricing_rule',
    'wp_ajax_b2b_delete_pricing_rule',
    'wp_ajax_b2b_export_data'
];

foreach ($ajax_handlers as $handler) {
    $status = has_action($handler) ? '✅ PASS' : '❌ FAIL';
    echo "<p>$handler: $status</p>";
}

// Test 6: Check shortcodes
echo "<h2>6. Shortcodes Test</h2>";
$shortcodes = [
    'b2b_dashboard',
    'b2b_order_history', 
    'b2b_account',
    'b2b_wishlist',
    'b2b_registration',
    'b2b_bulk_order'
];

foreach ($shortcodes as $shortcode) {
    $status = shortcode_exists($shortcode) ? '✅ PASS' : '❌ FAIL';
    echo "<p>Shortcode '$shortcode': $status</p>";
}

// Test 7: Check asset files
echo "<h2>7. Asset Files Test</h2>";
$css_file = B2B_COMMERCE_PRO_PATH . 'assets/css/b2b-admin-standalone-demo.css';
$js_file = B2B_COMMERCE_PRO_PATH . 'assets/js/b2b-commerce-pro.js';

$css_status = file_exists($css_file) ? '✅ PASS' : '❌ FAIL';
$js_status = file_exists($js_file) ? '✅ PASS' : '❌ FAIL';

echo "<p>CSS file: $css_status</p>";
echo "<p>JS file: $js_status</p>";

// Test 8: Check database permissions
echo "<h2>8. Database Permissions Test</h2>";
if ($exists === $table) {
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
        echo "<p>✅ Database permissions: PASS</p>";
    } else {
        echo "<p>❌ Database permissions: FAIL - " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p>❌ Cannot test database permissions - table does not exist</p>";
}

// Test 9: Check admin menu
echo "<h2>9. Admin Menu Test</h2>";
$menu_exists = has_action('admin_menu') ? '✅ PASS' : '❌ FAIL';
echo "<p>Admin menu hooks: $menu_exists</p>";

// Test 10: Check constants
echo "<h2>10. Plugin Constants Test</h2>";
$constants = [
    'B2B_COMMERCE_PRO_VERSION',
    'B2B_COMMERCE_PRO_PATH',
    'B2B_COMMERCE_PRO_URL',
    'B2B_COMMERCE_PRO_BASENAME'
];

foreach ($constants as $constant) {
    $status = defined($constant) ? '✅ PASS' : '❌ FAIL';
    echo "<p>Constant '$constant': $status</p>";
}

echo "<h2>🎯 Overall Assessment</h2>";
echo "<p><strong>All critical functionality has been tested!</strong></p>";
echo "<p>If you see any ❌ FAIL results above, those are the specific issues that need to be fixed.</p>";
echo "<p>If all tests show ✅ PASS, the plugin should work perfectly!</p>";
?> 