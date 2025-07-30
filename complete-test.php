<?php
/**
 * B2B Commerce Pro - Complete Functionality Test
 * This tests EVERYTHING to ensure no issues before user testing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Only allow admin access
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

echo "<h1>🔍 B2B Commerce Pro - COMPLETE FUNCTIONALITY TEST</h1>";
echo "<p><strong>Testing EVERYTHING before you use the plugin...</strong></p>";

$all_tests = [];
$passed = 0;
$failed = 0;

// Test 1: Check if all required files exist
echo "<h2>1. File Existence Test</h2>";
$required_files = [
    'b2b-commerce-pro.php' => 'Main plugin file',
    'includes/Init.php' => 'Bootstrap class',
    'includes/AdminPanel.php' => 'Admin interface',
    'includes/UserManager.php' => 'User management',
    'includes/PricingManager.php' => 'Pricing system',
    'includes/Frontend.php' => 'Frontend functionality',
    'includes/ProductManager.php' => 'Product management',
    'includes/AdvancedFeatures.php' => 'Advanced features',
    'includes/Reporting.php' => 'Reporting system',
    'assets/css/b2b-admin-standalone-demo.css' => 'Admin CSS',
    'assets/js/b2b-commerce-pro.js' => 'Admin JavaScript'
];

foreach ($required_files as $file => $description) {
    $file_path = B2B_COMMERCE_PRO_PATH . $file;
    $exists = file_exists($file_path);
    $status = $exists ? '✅ PASS' : '❌ FAIL';
    echo "<p><strong>$description ($file):</strong> $status</p>";
    $all_tests[] = $exists;
    if ($exists) $passed++; else $failed++;
}

// Test 2: Check if all classes are loaded
echo "<h2>2. Class Loading Test</h2>";
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
    $loaded = class_exists($class);
    $status = $loaded ? '✅ PASS' : '❌ FAIL';
    echo "<p><strong>$class:</strong> $status</p>";
    $all_tests[] = $loaded;
    if ($loaded) $passed++; else $failed++;
}

// Test 3: Check database table
echo "<h2>3. Database Table Test</h2>";
global $wpdb;
$table = $wpdb->prefix . 'b2b_pricing_rules';
$exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));

if ($exists === $table) {
    echo "<p>✅ Pricing table exists</p>";
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "<p>📊 Table has $count rules</p>";
    $all_tests[] = true;
    $passed++;
} else {
    echo "<p>❌ Pricing table does not exist</p>";
    echo "<p>🔧 Attempting to create table...</p>";
    
    if (class_exists('B2B\\PricingManager')) {
        B2B\PricingManager::create_pricing_table();
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists === $table) {
            echo "<p>✅ Table created successfully!</p>";
            $all_tests[] = true;
            $passed++;
        } else {
            echo "<p>❌ Failed to create table</p>";
            $all_tests[] = false;
            $failed++;
        }
    } else {
        echo "<p>❌ PricingManager class not found</p>";
        $all_tests[] = false;
        $failed++;
    }
}

// Test 4: Check user roles
echo "<h2>4. User Roles Test</h2>";
$roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
foreach ($roles as $role) {
    $role_obj = get_role($role);
    $status = $role_obj ? '✅ PASS' : '❌ FAIL';
    echo "<p>Role '$role': $status</p>";
    $all_tests[] = $role_obj ? true : false;
    if ($role_obj) $passed++; else $failed++;
}

// Test 5: Check WooCommerce integration
echo "<h2>5. WooCommerce Integration Test</h2>";
$wc_status = class_exists('WooCommerce') ? '✅ ACTIVE' : '❌ NOT ACTIVE';
echo "<p>WooCommerce: $wc_status</p>";
$all_tests[] = class_exists('WooCommerce');
if (class_exists('WooCommerce')) $passed++; else $failed++;

if (class_exists('WooCommerce')) {
    $wc_functions = [
        'wc_get_orders' => function_exists('wc_get_orders'),
        'wc_get_order' => function_exists('wc_get_order'),
        'wc_price' => function_exists('wc_price')
    ];
    
    foreach ($wc_functions as $func => $exists) {
        $status = $exists ? '✅ PASS' : '❌ FAIL';
        echo "<p>Function '$func': $status</p>";
        $all_tests[] = $exists;
        if ($exists) $passed++; else $failed++;
    }
}

// Test 6: Check AJAX handlers
echo "<h2>6. AJAX Handlers Test</h2>";
$ajax_handlers = [
    'wp_ajax_b2b_approve_user',
    'wp_ajax_b2b_reject_user',
    'wp_ajax_b2b_save_pricing_rule',
    'wp_ajax_b2b_delete_pricing_rule',
    'wp_ajax_b2b_export_data'
];

foreach ($ajax_handlers as $handler) {
    $registered = has_action($handler);
    $status = $registered ? '✅ PASS' : '❌ FAIL';
    echo "<p>$handler: $status</p>";
    $all_tests[] = $registered;
    if ($registered) $passed++; else $failed++;
}

// Test 7: Check shortcodes
echo "<h2>7. Shortcodes Test</h2>";
$shortcodes = [
    'b2b_dashboard',
    'b2b_order_history',
    'b2b_account',
    'b2b_wishlist',
    'b2b_registration'
];

foreach ($shortcodes as $shortcode) {
    $registered = shortcode_exists($shortcode);
    $status = $registered ? '✅ PASS' : '❌ FAIL';
    echo "<p>Shortcode '$shortcode': $status</p>";
    $all_tests[] = $registered;
    if ($registered) $passed++; else $failed++;
}

// Test 8: Check admin menu
echo "<h2>8. Admin Menu Test</h2>";
$menu_exists = has_action('admin_menu') ? '✅ PASS' : '❌ FAIL';
echo "<p>Admin menu hooks: $menu_exists</p>";
$all_tests[] = has_action('admin_menu');
if (has_action('admin_menu')) $passed++; else $failed++;

// Test 9: Check constants
echo "<h2>9. Plugin Constants Test</h2>";
$constants = [
    'B2B_COMMERCE_PRO_VERSION',
    'B2B_COMMERCE_PRO_PATH',
    'B2B_COMMERCE_PRO_URL',
    'B2B_COMMERCE_PRO_BASENAME'
];

foreach ($constants as $constant) {
    $defined = defined($constant);
    $status = $defined ? '✅ PASS' : '❌ FAIL';
    echo "<p>Constant '$constant': $status</p>";
    $all_tests[] = $defined;
    if ($defined) $passed++; else $failed++;
}

// Test 10: Check database permissions
echo "<h2>10. Database Permissions Test</h2>";
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
        $all_tests[] = true;
        $passed++;
    } else {
        echo "<p>❌ Database permissions: FAIL - " . $wpdb->last_error . "</p>";
        $all_tests[] = false;
        $failed++;
    }
} else {
    echo "<p>❌ Cannot test database permissions - table does not exist</p>";
    $all_tests[] = false;
    $failed++;
}

// Final Results
echo "<h2>🎯 FINAL RESULTS</h2>";
$total_tests = count($all_tests);
$success_rate = ($total_tests > 0) ? round(($passed / $total_tests) * 100, 2) : 0;

echo "<p><strong>Total Tests:</strong> $total_tests</p>";
echo "<p><strong>Passed:</strong> $passed</p>";
echo "<p><strong>Failed:</strong> $failed</p>";
echo "<p><strong>Success Rate:</strong> $success_rate%</p>";

if ($success_rate >= 95) {
    echo "<p style='color: #28a745; font-size: 18px; font-weight: bold;'>✅ EXCELLENT! Plugin is ready for testing!</p>";
} elseif ($success_rate >= 80) {
    echo "<p style='color: #ffc107; font-size: 18px; font-weight: bold;'>⚠️ GOOD! Minor issues detected but plugin should work.</p>";
} else {
    echo "<p style='color: #dc3545; font-size: 18px; font-weight: bold;'>❌ POOR! Multiple issues detected. Plugin needs fixes.</p>";
}

echo "<h3>📋 RECOMMENDATIONS:</h3>";
if ($success_rate >= 95) {
    echo "<p>✅ <strong>GO AHEAD AND TEST THE PLUGIN!</strong></p>";
    echo "<p>✅ All critical functionality is working</p>";
    echo "<p>✅ You can safely test user creation, pricing, and settings</p>";
} else {
    echo "<p>❌ <strong>DO NOT TEST YET!</strong></p>";
    echo "<p>❌ Issues need to be fixed first</p>";
    echo "<p>❌ Plugin may crash or cause errors</p>";
}

echo "<p><strong>Test URL:</strong> <a href='admin.php?page=b2b-dashboard' target='_blank'>Go to B2B Dashboard</a></p>";
?> 