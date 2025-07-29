<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class AdvancedFeatures {
    public function __construct() {
        // Credit limit and payment terms admin UI
        add_action( 'show_user_profile', [ $this, 'user_advanced_fields' ] );
        add_action( 'edit_user_profile', [ $this, 'user_advanced_fields' ] );
        add_action( 'personal_options_update', [ $this, 'save_user_advanced_fields' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_user_advanced_fields' ] );
        // Credit limit enforcement
        add_action( 'woocommerce_checkout_process', [ $this, 'enforce_credit_limit' ] );
        // Payment terms display
        add_action( 'woocommerce_review_order_after_payment', [ $this, 'show_payment_terms' ] );
        // Tax exemption
        add_filter( 'woocommerce_customer_is_vat_exempt', [ $this, 'handle_tax_exemption' ], 10, 2 );
        // Multi-currency support (integration hook)
        add_filter( 'woocommerce_currency', [ $this, 'handle_multi_currency' ] );
        // REST API endpoints
        add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
        // Plugin integration hooks (placeholder)
    }

    // Admin UI for credit, payment terms, tax exemption
    public function user_advanced_fields( $user ) {
        ?>
        <h3>B2B Advanced Features</h3>
        <table class="form-table">
            <tr>
                <th><label for="b2b_credit_limit">Credit Limit</label></th>
                <td><input type="number" name="b2b_credit_limit" id="b2b_credit_limit" value="<?php echo esc_attr( get_user_meta( $user->ID, 'b2b_credit_limit', true ) ); ?>" step="0.01"></td>
            </tr>
            <tr>
                <th><label for="b2b_payment_terms">Payment Terms</label></th>
                <td><input type="text" name="b2b_payment_terms" id="b2b_payment_terms" value="<?php echo esc_attr( get_user_meta( $user->ID, 'b2b_payment_terms', true ) ); ?>" placeholder="Net 30, Net 60, etc."></td>
            </tr>
            <tr>
                <th><label for="b2b_tax_exempt">Tax Exempt</label></th>
                <td><input type="checkbox" name="b2b_tax_exempt" value="1" <?php checked( get_user_meta( $user->ID, 'b2b_tax_exempt', true ), 1 ); ?>> Yes<br>
                <input type="text" name="b2b_tax_exempt_number" value="<?php echo esc_attr( get_user_meta( $user->ID, 'b2b_tax_exempt_number', true ) ); ?>" placeholder="Tax Exempt Number"></td>
            </tr>
        </table>
        <?php
    }
    public function save_user_advanced_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        update_user_meta( $user_id, 'b2b_credit_limit', floatval( $_POST['b2b_credit_limit'] ?? 0 ) );
        update_user_meta( $user_id, 'b2b_payment_terms', sanitize_text_field( $_POST['b2b_payment_terms'] ?? '' ) );
        update_user_meta( $user_id, 'b2b_tax_exempt', isset( $_POST['b2b_tax_exempt'] ) ? 1 : 0 );
        update_user_meta( $user_id, 'b2b_tax_exempt_number', sanitize_text_field( $_POST['b2b_tax_exempt_number'] ?? '' ) );
    }

    // Enhanced credit limit enforcement
    public function enforce_credit_limit() {
        $user_id = get_current_user_id();
        $limit = floatval(get_user_meta($user_id, 'b2b_credit_limit', true));
        
        if ($limit <= 0) return;
        
        $current_balance = $this->get_user_credit_balance($user_id);
        $cart_total = WC()->cart->get_total('raw');
        
        if ($cart_total + $current_balance > $limit) {
            $remaining = $limit - $current_balance;
            wc_add_notice(
                sprintf(
                    'Your order exceeds your credit limit. Maximum order amount: %s. Current balance: %s. Remaining credit: %s.',
                    wc_price($limit),
                    wc_price($current_balance),
                    wc_price($remaining)
                ),
                'error'
            );
        }
    }

    // Enhanced credit balance calculation
    public function get_user_credit_balance($user_id) {
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status' => ['processing', 'completed'],
            'limit' => -1
        ]);
        
        $total_credit_used = 0;
        
        foreach ($orders as $order) {
            $payment_method = $order->get_payment_method();
            
            // Only count orders with credit terms (not immediate payment)
            if (in_array($payment_method, ['b2b_credit', 'b2b_net30', 'b2b_net60'])) {
                $total_credit_used += $order->get_total();
            }
        }
        
        return $total_credit_used;
    }

    // Show payment terms at checkout
    public function show_payment_terms() {
        $user_id = get_current_user_id();
        $terms = get_user_meta( $user_id, 'b2b_payment_terms', true );
        if ( $terms ) {
            echo '<tr class="b2b-payment-terms"><td colspan="2">Payment Terms: ' . esc_html( $terms ) . '</td></tr>';
        }
    }

    // Tax exemption
    public function handle_tax_exemption( $is_exempt, $customer ) {
        $user_id = is_object( $customer ) ? $customer->get_id() : get_current_user_id();
        if ( get_user_meta( $user_id, 'b2b_tax_exempt', true ) ) {
            return true;
        }
        return $is_exempt;
    }

    // Multi-currency support (integration hook)
    public function handle_multi_currency( $currency ) {
        // Integrate with WooCommerce multi-currency plugins if available
        // For demo, return default
        return $currency;
    }

    // REST API endpoints
    public function register_rest_endpoints() {
        register_rest_route( 'b2b/v1', '/users', [
            'methods' => 'GET',
            'callback' => [ $this, 'rest_get_users' ],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( 'b2b/v1', '/orders', [
            'methods' => 'GET',
            'callback' => [ $this, 'rest_get_orders' ],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( 'b2b/v1', '/pricing', [
            'methods' => 'GET',
            'callback' => [ $this, 'rest_get_pricing' ],
            'permission_callback' => '__return_true',
        ] );
    }
    public function rest_get_users() {
        $users = get_users( [ 'role__in' => [ 'b2b_customer', 'wholesale_customer', 'distributor', 'retailer' ] ] );
        $data = [];
        foreach ( $users as $user ) {
            $data[] = [
                'id' => $user->ID,
                'email' => $user->user_email,
                'role' => $user->roles,
                'company' => get_user_meta( $user->ID, 'company_name', true ),
                'group' => wp_get_object_terms( $user->ID, 'b2b_user_group', [ 'fields' => 'names' ] ),
                'credit_limit' => get_user_meta( $user->ID, 'b2b_credit_limit', true ),
                'payment_terms' => get_user_meta( $user->ID, 'b2b_payment_terms', true ),
                'tax_exempt' => get_user_meta( $user->ID, 'b2b_tax_exempt', true ),
            ];
        }
        return $data;
    }
    public function rest_get_orders() {
        $orders = wc_get_orders( [ 'limit' => 50, 'orderby' => 'date', 'order' => 'DESC' ] );
        $data = [];
        foreach ( $orders as $order ) {
            $data[] = [
                'id' => $order->get_id(),
                'user_id' => $order->get_user_id(),
                'total' => $order->get_total(),
                'status' => $order->get_status(),
                'date' => $order->get_date_created()->date( 'Y-m-d' ),
            ];
        }
        return $data;
    }
    public function rest_get_pricing() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $rules = $wpdb->get_results( "SELECT * FROM $table" );
        return $rules;
    }

    // Advanced features implementation
    public function plugin_integrations() {
        // WooCommerce Multi-Currency integration
        if (class_exists('WOOCS')) {
            add_filter('woocommerce_currency', [$this, 'handle_woocs_currency']);
        }
        
        // WooCommerce Advanced Shipping integration
        if (class_exists('WooCommerce_Advanced_Shipping')) {
            add_filter('woocommerce_shipping_methods', [$this, 'add_b2b_shipping_methods']);
        }
        
        // WooCommerce PDF Invoices integration
        if (class_exists('WooCommerce_PDF_Invoices')) {
            add_action('woocommerce_order_status_completed', [$this, 'generate_b2b_invoice']);
        }
        
        // WooCommerce Subscriptions integration
        if (class_exists('WC_Subscriptions')) {
            add_filter('woocommerce_subscription_price', [$this, 'apply_b2b_subscription_pricing']);
        }
        
        // WooCommerce Bookings integration
        if (class_exists('WC_Bookings')) {
            add_filter('woocommerce_bookings_cost', [$this, 'apply_b2b_booking_pricing']);
        }
        
        // Advanced Custom Fields integration
        if (class_exists('ACF')) {
            add_action('acf/save_post', [$this, 'save_b2b_acf_fields']);
        }
        
        // Yoast SEO integration
        if (class_exists('WPSEO_Admin')) {
            add_filter('wpseo_title', [$this, 'modify_b2b_seo_title']);
        }
    }

    public function handle_woocs_currency($currency) {
        $user_id = get_current_user_id();
        $user_currency = get_user_meta($user_id, 'b2b_preferred_currency', true);
        
        if ($user_currency && class_exists('WOOCS')) {
            global $WOOCS;
            $WOOCS->set_currency($user_currency);
            return $user_currency;
        }
        
        return $currency;
    }

    public function add_b2b_shipping_methods($methods) {
        $methods['b2b_free_shipping'] = 'B2B_Free_Shipping_Method';
        $methods['b2b_priority_shipping'] = 'B2B_Priority_Shipping_Method';
        return $methods;
    }

    public function generate_b2b_invoice($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        if (!$user_id) return;
        
        $user = get_user_by('id', $user_id);
        $user_roles = $user->roles;
        
        // Only generate B2B invoices for B2B customers
        if (!array_intersect($user_roles, ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'])) {
            return;
        }
        
        // Generate custom B2B invoice
        $invoice_data = [
            'order_id' => $order_id,
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'company_name' => get_user_meta($user_id, 'company_name', true),
            'tax_id' => get_user_meta($user_id, 'tax_id', true),
            'payment_terms' => get_user_meta($user_id, 'b2b_payment_terms', true),
            'invoice_number' => 'INV-' . $order_id,
            'invoice_date' => current_time('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'items' => $order->get_items(),
            'total' => $order->get_total(),
            'tax' => $order->get_total_tax(),
            'shipping' => $order->get_shipping_total()
        ];
        
        // Store invoice data
        update_post_meta($order_id, '_b2b_invoice_data', $invoice_data);
        
        // Send invoice email
        $this->send_b2b_invoice_email($order_id, $invoice_data);
    }

    public function apply_b2b_subscription_pricing($price) {
        $user_id = get_current_user_id();
        if (!$user_id) return $price;
        
        $user_roles = wp_get_current_user()->roles;
        $discount = 0;
        
        if (in_array('wholesale_customer', $user_roles)) {
            $discount = 15; // 15% discount for wholesale
        } elseif (in_array('distributor', $user_roles)) {
            $discount = 20; // 20% discount for distributors
        }
        
        if ($discount > 0) {
            $price = $price * (1 - ($discount / 100));
        }
        
        return $price;
    }

    public function apply_b2b_booking_pricing($cost) {
        $user_id = get_current_user_id();
        if (!$user_id) return $cost;
        
        $user_roles = wp_get_current_user()->roles;
        $discount = 0;
        
        if (in_array('wholesale_customer', $user_roles)) {
            $discount = 10; // 10% discount for wholesale bookings
        }
        
        if ($discount > 0) {
            $cost = $cost * (1 - ($discount / 100));
        }
        
        return $cost;
    }

    public function save_b2b_acf_fields($post_id) {
        if (get_post_type($post_id) !== 'product') return;
        
        // Save B2B-specific ACF fields
        if (function_exists('get_field')) {
            $b2b_visible_roles = get_field('b2b_visible_roles', $post_id);
            $b2b_wholesale_only = get_field('b2b_wholesale_only', $post_id);
            $b2b_min_order_qty = get_field('b2b_min_order_qty', $post_id);
            
            if ($b2b_visible_roles) {
                update_post_meta($post_id, '_b2b_visible_roles', $b2b_visible_roles);
            }
            if ($b2b_wholesale_only) {
                update_post_meta($post_id, '_b2b_wholesale_only', 'yes');
            }
            if ($b2b_min_order_qty) {
                update_post_meta($post_id, '_b2b_min_order_qty', $b2b_min_order_qty);
            }
        }
    }

    public function modify_b2b_seo_title($title) {
        if (!is_user_logged_in()) return $title;
        
        $user_roles = wp_get_current_user()->roles;
        
        if (array_intersect($user_roles, ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'])) {
            $title = '[B2B] ' . $title;
        }
        
        return $title;
    }

    private function send_b2b_invoice_email($order_id, $invoice_data) {
        $order = wc_get_order($order_id);
        $user = get_user_by('id', $order->get_user_id());
        
        $subject = 'Invoice for Order #' . $order_id . ' - ' . get_bloginfo('name');
        
        $message = $this->generate_invoice_email_content($invoice_data);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }

    private function generate_invoice_email_content($invoice_data) {
        $content = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $content .= '<h2>Invoice</h2>';
        $content .= '<p><strong>Invoice Number:</strong> ' . $invoice_data['invoice_number'] . '</p>';
        $content .= '<p><strong>Date:</strong> ' . $invoice_data['invoice_date'] . '</p>';
        $content .= '<p><strong>Due Date:</strong> ' . $invoice_data['due_date'] . '</p>';
        $content .= '<p><strong>Customer:</strong> ' . $invoice_data['customer_name'] . '</p>';
        $content .= '<p><strong>Company:</strong> ' . $invoice_data['company_name'] . '</p>';
        $content .= '<p><strong>Payment Terms:</strong> ' . $invoice_data['payment_terms'] . '</p>';
        
        $content .= '<h3>Items</h3>';
        $content .= '<table style="width: 100%; border-collapse: collapse;">';
        $content .= '<thead><tr><th style="border: 1px solid #ddd; padding: 8px;">Item</th><th style="border: 1px solid #ddd; padding: 8px;">Qty</th><th style="border: 1px solid #ddd; padding: 8px;">Price</th><th style="border: 1px solid #ddd; padding: 8px;">Total</th></tr></thead><tbody>';
        
        foreach ($invoice_data['items'] as $item) {
            $content .= '<tr>';
            $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $item->get_name() . '</td>';
            $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $item->get_quantity() . '</td>';
            $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . wc_price($item->get_total() / $item->get_quantity()) . '</td>';
            $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . wc_price($item->get_total()) . '</td>';
            $content .= '</tr>';
        }
        
        $content .= '</tbody></table>';
        
        $content .= '<h3>Summary</h3>';
        $content .= '<p><strong>Subtotal:</strong> ' . wc_price($invoice_data['total'] - $invoice_data['tax'] - $invoice_data['shipping']) . '</p>';
        $content .= '<p><strong>Tax:</strong> ' . wc_price($invoice_data['tax']) . '</p>';
        $content .= '<p><strong>Shipping:</strong> ' . wc_price($invoice_data['shipping']) . '</p>';
        $content .= '<p><strong>Total:</strong> ' . wc_price($invoice_data['total']) . '</p>';
        
        $content .= '</div>';
        
        return $content;
    }
} 