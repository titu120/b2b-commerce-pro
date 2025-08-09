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
        // Quote request system
        add_action( 'woocommerce_single_product_summary', [ $this, 'quote_request_button' ], 35 );
        add_action( 'wp_ajax_b2b_quote_request', [ $this, 'handle_quote_request' ] );
        add_action( 'wp_ajax_nopriv_b2b_quote_request', [ $this, 'handle_quote_request' ] );
        // Bulk pricing calculator
        add_action( 'woocommerce_single_product_summary', [ $this, 'bulk_pricing_calculator' ], 30 );
        add_action( 'wp_ajax_b2b_calculate_bulk_price', [ $this, 'calculate_bulk_price' ] );
        add_action( 'wp_ajax_nopriv_b2b_calculate_bulk_price', [ $this, 'calculate_bulk_price' ] );
        // Advanced reporting - REMOVED: Duplicate menu registration
        // add_action( 'admin_menu', [ $this, 'add_advanced_reports_menu' ] ); // REMOVED: Duplicate menu registration
        // Plugin integration hooks (placeholder)
        // Catalog mode & checkout controls
        add_filter( 'woocommerce_is_purchasable', [ $this, 'maybe_disable_purchasable' ], 10, 2 );
        add_filter( 'woocommerce_get_price_html', [ $this, 'maybe_hide_price_html' ], 10, 2 );
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_payment_gateways_by_role' ] );
        add_filter( 'woocommerce_package_rates', [ $this, 'filter_package_rates_by_role' ], 10, 2 );
        add_action( 'woocommerce_checkout_process', [ $this, 'block_checkout_when_forced_quote' ] );

        // VAT validation (simple VIES check if SOAP present)
        add_action( 'woocommerce_after_checkout_validation', [ $this, 'maybe_validate_vat' ], 10, 2 );
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
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        ] );
        register_rest_route( 'b2b/v1', '/orders', [
            'methods' => 'GET',
            'callback' => [ $this, 'rest_get_orders' ],
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        ] );
        register_rest_route( 'b2b/v1', '/pricing', [
            'methods' => 'GET',
            'callback' => [ $this, 'rest_get_pricing' ],
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
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

    public function bulk_order_management() {
        if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) {
            return '<p>WooCommerce is required for bulk order management.</p>';
        }
        
        $orders = wc_get_orders([
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
    }
    
    // Quote request button
    public function quote_request_button() {
        if (!is_user_logged_in()) return;
        
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        $b2b_roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
        
        if (!array_intersect($user_roles, $b2b_roles)) return;
        
        global $product;
        if (!$product) return;
        
        echo '<div class="b2b-quote-request">';
        echo '<button type="button" class="button b2b-quote-btn" data-product-id="' . $product->get_id() . '">';
        echo '<span class="dashicons dashicons-email-alt"></span> Request Quote';
        echo '</button>';
        echo '<div class="b2b-quote-form" style="display: none;">';
        echo '<h4>Request Quote</h4>';
        echo '<p><label>Quantity: <input type="number" name="quote_qty" min="1" value="1"></label></p>';
        echo '<p><label>Message: <textarea name="quote_message" placeholder="Additional requirements..."></textarea></label></p>';
        echo '<button type="button" class="button button-primary submit-quote">Submit Quote Request</button>';
        echo '<button type="button" class="button cancel-quote">Cancel</button>';
        echo '</div>';
        echo '</div>';
    }
    
    // Handle quote request
    public function handle_quote_request() {
        // CSRF protection - accept either the global AJAX nonce or legacy b2b_quote_request nonce
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $nonce_ok = false;
        if ( $nonce && wp_verify_nonce( $nonce, 'b2b_ajax_nonce' ) ) {
            $nonce_ok = true;
        } elseif ( $nonce && wp_verify_nonce( $nonce, 'b2b_quote_request' ) ) {
            $nonce_ok = true;
        }
        if ( ! $nonce_ok ) {
            wp_send_json_error( 'Security check failed' );
            return;
        }
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to request quotes');
            return;
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $message = sanitize_textarea_field($_POST['message']);
        $user_id = get_current_user_id();
        
        if (!$product_id || !$quantity) {
            wp_send_json_error('Invalid request data');
            return;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Product not found');
            return;
        }
        
        // Save quote request
        $quote_data = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'message' => $message,
            'user_id' => $user_id,
            'status' => 'pending',
            'date' => current_time('mysql')
        ];
        
        $quotes = get_option('b2b_quote_requests', []);
        $quotes[] = $quote_data;
        update_option('b2b_quote_requests', $quotes);
        
        // Send email notification to admin
        $admin_email = get_option('admin_email');
        $subject = 'New B2B Quote Request';
        $message_body = sprintf(
            'New quote request received for %s (Qty: %d) from user ID: %d',
            $product->get_name(),
            $quantity,
            $user_id
        );
        
        wp_mail($admin_email, $subject, $message_body);
        
        wp_send_json_success('Quote request submitted successfully');
    }

    // Catalog Mode: hide prices and disable add-to-cart
    public function maybe_disable_purchasable( $purchasable, $product ) {
        $catalog = get_option('b2b_catalog_mode', []);
        if ( !is_user_logged_in() && !empty($catalog['disable_add_to_cart']) ) {
            return false;
        }
        if ( !empty($catalog['force_quote_mode']) ) {
            return false;
        }
        return $purchasable;
    }

    public function maybe_hide_price_html( $price, $product ) {
        $catalog = get_option('b2b_catalog_mode', []);
        if ( !is_user_logged_in() && !empty($catalog['hide_prices_guests']) ) {
            return '<span class="price">' . esc_html__('Login to see prices', 'b2b-commerce-pro') . '</span>';
        }
        if ( !empty($catalog['force_quote_mode']) ) {
            return '<span class="price">' . esc_html__('Request a quote', 'b2b-commerce-pro') . '</span>';
        }
        return $price;
    }

    // Role-based checkout controls
    public function filter_payment_gateways_by_role( $gateways ) {
        if ( !is_user_logged_in() ) return $gateways;
        $settings = get_option('b2b_role_payment_methods', []);
        if ( empty($settings) ) return $gateways;
        $roles = wp_get_current_user()->roles;
        foreach ( $gateways as $id => $gateway ) {
            $allowed = false;
            foreach ( $roles as $role ) {
                if ( !empty($settings[$role]) && in_array($id, (array) $settings[$role], true) ) {
                    $allowed = true; break;
                }
            }
            if ( !$allowed ) {
                unset($gateways[$id]);
            }
        }
        return $gateways;
    }

    public function filter_package_rates_by_role( $rates, $package ) {
        if ( !is_user_logged_in() ) return $rates;
        $settings = get_option('b2b_role_shipping_methods', []);
        if ( empty($settings) ) return $rates;
        $roles = wp_get_current_user()->roles;
        foreach ( $rates as $rate_id => $rate ) {
            $method_id = isset($rate->method_id) ? $rate->method_id : '';
            $allowed = false;
            foreach ( $roles as $role ) {
                if ( !empty($settings[$role]) && in_array($method_id, (array) $settings[$role], true) ) {
                    $allowed = true; break;
                }
            }
            if ( !$allowed ) {
                unset($rates[$rate_id]);
            }
        }
        return $rates;
    }

    public function block_checkout_when_forced_quote() {
        if ( !function_exists('is_checkout') || !is_checkout() ) return;
        $catalog = get_option('b2b_catalog_mode', []);
        if ( !empty($catalog['force_quote_mode']) ) {
            wc_add_notice( __('Checkout is disabled. Please request a quote.', 'b2b-commerce-pro'), 'error' );
        }
    }

    // VAT validation via VIES (if SOAP is installed)
    public function maybe_validate_vat( $data, $errors ) {
        $vat = get_option('b2b_vat_settings', []);
        if ( empty($vat['enable_vat_validation']) ) return;
        $tax_id = isset($_POST['billing_vat']) ? sanitize_text_field($_POST['billing_vat']) : '';
        if ( empty($tax_id) ) return;
        if ( !class_exists('SoapClient') ) return;
        try {
            $country = substr($tax_id, 0, 2);
            $number  = preg_replace('/\D/', '', substr($tax_id, 2));
            $client = new \SoapClient('https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
            $result = $client->checkVat([ 'countryCode' => $country, 'vatNumber' => $number ]);
            if ( !empty($result->valid) && !empty($vat['auto_tax_exempt']) ) {
                update_user_meta( get_current_user_id(), 'b2b_tax_exempt', 1 );
            } elseif ( empty($result->valid) ) {
                $errors->add('billing_vat', __('Invalid VAT number. Please verify.', 'b2b-commerce-pro'));
            }
        } catch ( \Throwable $e ) {
            // Fail silently
        }
    }
    
    // Bulk pricing calculator
    public function bulk_pricing_calculator() {
        if (!is_user_logged_in()) return;
        
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        $b2b_roles = ['b2b_customer', 'wholesale_customer', 'distributor', 'retailer'];
        
        if (!array_intersect($user_roles, $b2b_roles)) return;
        
        global $product;
        if (!$product) return;
        
        echo '<div class="b2b-bulk-calculator" data-product-id="' . esc_attr( $product->get_id() ) . '">';
        echo '<h4>Bulk Pricing Calculator</h4>';
        echo '<p><label>Quantity: <input type="number" name="bulk_qty" min="1" value="1" class="bulk-qty-input"></label></p>';
        echo '<p><strong>Price: <span class="bulk-price-display">' . $product->get_price_html() . '</span></strong></p>';
        echo '<button type="button" class="button calculate-bulk-price">Calculate Bulk Price</button>';
        echo '</div>';
    }
    
    // Calculate bulk price
    public function calculate_bulk_price() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'b2b_ajax_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to calculate bulk prices');
            return;
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $user_id = get_current_user_id();
        
        if (!$product_id || !$quantity) {
            wp_send_json_error('Invalid request data');
            return;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Product not found');
            return;
        }
        
        // Get user role for pricing
        $user = get_userdata($user_id);
        $user_role = $user->roles[0] ?? '';
        
        // Get pricing rules
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE product_id = %d AND role = %s AND min_qty <= %d ORDER BY min_qty DESC LIMIT 1",
            $product_id,
            $user_role,
            $quantity
        ));
        
        $original_price = (float) $product->get_price();
        $unit_price = $original_price;
        $discount_display = 'No discount';
        
        if (!empty($rules)) {
            $rule = $rules[0];
            if ($rule->type === 'percentage') {
                // Use absolute percentage; admin UI stores discounts as negative, normalize here
                $percent = abs((float) $rule->price);
                $unit_price = $original_price * (1 - ($percent / 100));
                $discount_display = sprintf('%.0f%%', $percent);
            } else {
                // Fixed type represents final price in our system
                $unit_price = (float) $rule->price;
                $discount_amount = max(0, $original_price - $unit_price);
                $discount_display = $discount_amount > 0 ? wc_price($discount_amount) : 'No discount';
            }
            $unit_price = max(0, $unit_price);
        }
        
        $total_price = $unit_price * $quantity;
        
        wp_send_json_success([
            'unit_price' => wc_price($unit_price),
            'total_price' => wc_price($total_price),
            'discount' => $discount_display
        ]);
    }
    
    // Add advanced reports menu - REMOVED: Duplicate menu registration
    // public function add_advanced_reports_menu() {
    //     add_submenu_page(
    //         'b2b-dashboard',
    //         'Advanced Reports',
    //         'Advanced Reports',
    //         'manage_options',
    //         'b2b-advanced-reports',
    //         [$this, 'advanced_reports_page']
    //     );
    // }
    
    // Advanced reports page
    public function advanced_reports_page() {
        echo '<div class="wrap">';
        echo '<h1>B2B Advanced Reports</h1>';
        echo '<div class="b2b-admin-card">';
        echo '<h3>Quote Requests</h3>';
        
        $quotes = get_option('b2b_quote_requests', []);
        if (empty($quotes)) {
            echo '<p>No quote requests found.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Product</th><th>Quantity</th><th>User</th><th>Status</th><th>Date</th></tr></thead>';
            echo '<tbody>';
            foreach ($quotes as $quote) {
                $product = wc_get_product($quote['product_id']);
                $user = get_userdata($quote['user_id']);
                echo '<tr>';
                echo '<td>' . ($product ? $product->get_name() : 'Product not found') . '</td>';
                echo '<td>' . $quote['quantity'] . '</td>';
                echo '<td>' . ($user ? $user->display_name : 'User not found') . '</td>';
                echo '<td>' . ucfirst($quote['status']) . '</td>';
                echo '<td>' . $quote['date'] . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        echo '</div>';
        echo '</div>';
    }
} 