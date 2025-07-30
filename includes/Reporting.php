<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class Reporting {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_reporting_menu' ] );
        add_action( 'wp_ajax_b2b_get_sales_data', [ $this, 'get_sales_data' ] );
        add_action( 'wp_ajax_b2b_get_user_analytics', [ $this, 'get_user_analytics' ] );
        add_action( 'wp_ajax_b2b_get_performance_metrics', [ $this, 'get_performance_metrics' ] );
        add_action( 'wp_ajax_b2b_export_report', [ $this, 'export_report' ] );
    }

    public function add_reporting_menu() {
        add_submenu_page(
            'b2b-dashboard',
            'B2B Analytics & Reports',
            'Analytics',
            'manage_options',
            'b2b-analytics',
            [ $this, 'analytics_page' ]
        );
    }

    public function analytics_page() {
        $tab = $_GET['tab'] ?? 'dashboard';
        
        echo '<div class="b2b-admin-header">';
        echo '<h1><span class="icon dashicons dashicons-chart-line"></span>B2B Analytics & Reports</h1>';
        echo '<p>Comprehensive analytics and reporting for your B2B operations.</p>';
        echo '</div>';
        
        echo '<div class="b2b-admin-card">';
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="' . admin_url('admin.php?page=b2b-analytics&tab=dashboard') . '" class="nav-tab' . ($tab === 'dashboard' ? ' nav-tab-active' : '') . '">Dashboard</a>';
        echo '<a href="' . admin_url('admin.php?page=b2b-analytics&tab=sales') . '" class="nav-tab' . ($tab === 'sales' ? ' nav-tab-active' : '') . '">Sales Analytics</a>';
        echo '<a href="' . admin_url('admin.php?page=b2b-analytics&tab=users') . '" class="nav-tab' . ($tab === 'users' ? ' nav-tab-active' : '') . '">User Analytics</a>';
        echo '<a href="' . admin_url('admin.php?page=b2b-analytics&tab=performance') . '" class="nav-tab' . ($tab === 'performance' ? ' nav-tab-active' : '') . '">Performance</a>';
        echo '<a href="' . admin_url('admin.php?page=b2b-analytics&tab=reports') . '" class="nav-tab' . ($tab === 'reports' ? ' nav-tab-active' : '') . '">Reports</a>';
        echo '</nav>';
        
        switch ($tab) {
            case 'sales':
                $this->sales_analytics_tab();
                break;
            case 'users':
                $this->user_analytics_tab();
                break;
            case 'performance':
                $this->performance_tab();
                break;
            case 'reports':
                $this->reports_tab();
                break;
            default:
                $this->dashboard_tab();
                break;
        }
        
        echo '</div>';
    }

    private function dashboard_tab() {
        echo '<div class="b2b-dashboard-stats">';
        echo '<div class="stat-grid">';
        
        // Key metrics
        $total_revenue = $this->get_total_revenue();
        $total_orders = $this->get_total_orders();
        $active_users = $this->get_active_users();
        $avg_order_value = $this->get_average_order_value();
        
        echo '<div class="stat-card">';
        echo '<h3>Total Revenue</h3>';
        echo '<p class="stat-value">' . wc_price($total_revenue) . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<h3>Total Orders</h3>';
        echo '<p class="stat-value">' . number_format($total_orders) . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<h3>Active Users</h3>';
        echo '<p class="stat-value">' . number_format($active_users) . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card">';
        echo '<h3>Avg Order Value</h3>';
        echo '<p class="stat-value">' . wc_price($avg_order_value) . '</p>';
        echo '</div>';
        
        echo '</div>';
        
        // Charts
        echo '<div class="chart-container">';
        echo '<div id="revenue-chart" style="height: 300px;"></div>';
        echo '<div id="orders-chart" style="height: 300px;"></div>';
        echo '</div>';
        
        echo '</div>';
        
        echo '<script>
        // Initialize charts with Chart.js
        document.addEventListener("DOMContentLoaded", function() {
            // Revenue chart
            const revenueCtx = document.getElementById("revenue-chart").getContext("2d");
            new Chart(revenueCtx, {
                type: "line",
                data: {
                    labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
                    datasets: [{
                        label: "Revenue",
                        data: [12000, 19000, 15000, 25000, 22000, 30000],
                        borderColor: "rgb(75, 192, 192)",
                        tension: 0.1
                    }]
                }
            });
            
            // Orders chart
            const ordersCtx = document.getElementById("orders-chart").getContext("2d");
            new Chart(ordersCtx, {
                type: "bar",
                data: {
                    labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
                    datasets: [{
                        label: "Orders",
                        data: [65, 59, 80, 81, 56, 55],
                        backgroundColor: "rgba(54, 162, 235, 0.2)",
                        borderColor: "rgb(54, 162, 235)",
                        borderWidth: 1
                    }]
                }
            });
        });
        </script>';
    }

    private function sales_analytics_tab() {
        echo '<div class="b2b-sales-analytics">';
        echo '<h2>Sales Analytics</h2>';
        
        // Date range selector
        echo '<div class="date-range-selector">';
        echo '<label>Date Range: </label>';
        echo '<select id="date-range">';
        echo '<option value="7">Last 7 days</option>';
        echo '<option value="30" selected>Last 30 days</option>';
        echo '<option value="90">Last 90 days</option>';
        echo '<option value="365">Last year</option>';
        echo '</select>';
        echo '<button onclick="updateSalesData()" class="button">Update</button>';
        echo '</div>';
        
        // Sales metrics
        echo '<div class="sales-metrics">';
        echo '<div class="metric-card">';
        echo '<h3>Revenue by Customer Type</h3>';
        echo '<div id="revenue-by-type"></div>';
        echo '</div>';
        
        echo '<div class="metric-card">';
        echo '<h3>Top Products</h3>';
        echo '<div id="top-products"></div>';
        echo '</div>';
        
        echo '<div class="metric-card">';
        echo '<h3>Sales Trend</h3>';
        echo '<div id="sales-trend"></div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }

    private function user_analytics_tab() {
        echo '<div class="b2b-user-analytics">';
        echo '<h2>User Analytics</h2>';
        
        // User statistics
        $total_users = $this->get_total_users();
        $new_users_this_month = $this->get_new_users_this_month();
        $active_users_this_month = $this->get_active_users_this_month();
        $conversion_rate = $this->get_conversion_rate();
        
        echo '<div class="user-stats">';
        echo '<div class="stat-item">';
        echo '<h3>Total Users</h3>';
        echo '<p>' . number_format($total_users) . '</p>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<h3>New Users (This Month)</h3>';
        echo '<p>' . number_format($new_users_this_month) . '</p>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<h3>Active Users (This Month)</h3>';
        echo '<p>' . number_format($active_users_this_month) . '</p>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<h3>Conversion Rate</h3>';
        echo '<p>' . number_format($conversion_rate, 1) . '%</p>';
        echo '</div>';
        echo '</div>';
        
        // User activity chart
        echo '<div class="user-activity-chart">';
        echo '<h3>User Activity Over Time</h3>';
        echo '<div id="user-activity-chart"></div>';
        echo '</div>';
        
        echo '</div>';
    }

    private function performance_tab() {
        echo '<div class="b2b-performance">';
        echo '<h2>Performance Metrics</h2>';
        
        // Performance metrics
        $avg_response_time = $this->get_average_response_time();
        $order_fulfillment_rate = $this->get_order_fulfillment_rate();
        $customer_satisfaction = $this->get_customer_satisfaction();
        $repeat_customer_rate = $this->get_repeat_customer_rate();
        
        echo '<div class="performance-metrics">';
        echo '<div class="metric-item">';
        echo '<h3>Avg Response Time</h3>';
        echo '<p>' . $avg_response_time . ' hours</p>';
        echo '</div>';
        
        echo '<div class="metric-item">';
        echo '<h3>Order Fulfillment Rate</h3>';
        echo '<p>' . number_format($order_fulfillment_rate, 1) . '%</p>';
        echo '</div>';
        
        echo '<div class="metric-item">';
        echo '<h3>Customer Satisfaction</h3>';
        echo '<p>' . number_format($customer_satisfaction, 1) . '/5</p>';
        echo '</div>';
        
        echo '<div class="metric-item">';
        echo '<h3>Repeat Customer Rate</h3>';
        echo '<p>' . number_format($repeat_customer_rate, 1) . '%</p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }

    private function reports_tab() {
        echo '<div class="b2b-reports">';
        echo '<h2>Reports</h2>';
        
        echo '<div class="report-options">';
        echo '<h3>Generate Reports</h3>';
        
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="b2b_generate_report">';
        echo wp_nonce_field('b2b_generate_report', 'b2b_report_nonce', true, false);
        
        echo '<p><label>Report Type: </label>';
        echo '<select name="report_type" required>';
        echo '<option value="">Select Report</option>';
        echo '<option value="sales_summary">Sales Summary</option>';
        echo '<option value="customer_analysis">Customer Analysis</option>';
        echo '<option value="product_performance">Product Performance</option>';
        echo '<option value="revenue_analysis">Revenue Analysis</option>';
        echo '</select></p>';
        
        echo '<p><label>Date Range: </label>';
        echo '<select name="date_range" required>';
        echo '<option value="7">Last 7 days</option>';
        echo '<option value="30">Last 30 days</option>';
        echo '<option value="90">Last 90 days</option>';
        echo '<option value="365">Last year</option>';
        echo '</select></p>';
        
        echo '<p><label>Format: </label>';
        echo '<select name="format" required>';
        echo '<option value="csv">CSV</option>';
        echo '<option value="pdf">PDF</option>';
        echo '<option value="excel">Excel</option>';
        echo '</select></p>';
        
        echo '<p><button type="submit" class="button button-primary">Generate Report</button></p>';
        echo '</form>';
        echo '</div>';
        
        echo '</div>';
    }

    // Helper methods for analytics
    private function get_total_revenue() {
        if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) {
            return 0;
        }
        
        $orders = wc_get_orders([
            'status' => 'completed',
            'limit' => -1
        ]);
        
        $total = 0;
        foreach ($orders as $order) {
            $total += $order->get_total();
        }
        
        return $total;
    }

    private function get_total_orders() {
        if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) {
            return 0;
        }
        
        $orders = wc_get_orders([
            'status' => 'completed',
            'limit' => -1
        ]);
        
        return count($orders);
    }

    private function get_active_users() {
        $users = get_users([
            'role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'],
            'meta_query' => [
                [
                    'key' => 'last_activity',
                    'value' => date('Y-m-d', strtotime('-30 days')),
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ]
        ]);
        
        return count($users);
    }

    private function get_average_order_value() {
        $orders = wc_get_orders([
            'status' => ['completed', 'processing'],
            'limit' => -1
        ]);
        
        if (empty($orders)) return 0;
        
        $total = 0;
        foreach ($orders as $order) {
            $total += $order->get_total();
        }
        
        return $total / count($orders);
    }

    private function get_total_users() {
        $users = get_users([
            'role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer']
        ]);
        
        return count($users);
    }

    private function get_new_users_this_month() {
        $users = get_users([
            'role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'],
            'date_query' => [
                [
                    'after' => '1 month ago'
                ]
            ]
        ]);
        
        return count($users);
    }

    private function get_active_users_this_month() {
        $users = get_users([
            'role__in' => ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'],
            'meta_query' => [
                [
                    'key' => 'last_activity',
                    'value' => date('Y-m-d', strtotime('-30 days')),
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ]
        ]);
        
        return count($users);
    }

    private function get_conversion_rate() {
        $total_visitors = get_option('b2b_total_visitors', 1000);
        $total_orders = $this->get_total_orders();
        
        if ($total_visitors == 0) return 0;
        
        return ($total_orders / $total_visitors) * 100;
    }

    private function get_average_response_time() {
        // Placeholder - in real implementation, track actual response times
        return 2.5;
    }

    private function get_order_fulfillment_rate() {
        $total_orders = wc_get_orders([
            'status' => ['completed', 'processing'],
            'limit' => -1
        ]);
        
        $fulfilled_orders = wc_get_orders([
            'status' => ['completed'],
            'limit' => -1
        ]);
        
        if (empty($total_orders)) return 0;
        
        return (count($fulfilled_orders) / count($total_orders)) * 100;
    }

    private function get_customer_satisfaction() {
        // Placeholder - in real implementation, track actual satisfaction scores
        return 4.2;
    }

    private function get_repeat_customer_rate() {
        $customers = [];
        $repeat_customers = 0;
        
        $orders = wc_get_orders([
            'status' => ['completed', 'processing'],
            'limit' => -1
        ]);
        
        foreach ($orders as $order) {
            $customer_id = $order->get_customer_id();
            if (!isset($customers[$customer_id])) {
                $customers[$customer_id] = 0;
            }
            $customers[$customer_id]++;
        }
        
        foreach ($customers as $customer_id => $order_count) {
            if ($order_count > 1) {
                $repeat_customers++;
            }
        }
        
        if (empty($customers)) return 0;
        
        return ($repeat_customers / count($customers)) * 100;
    }
} 