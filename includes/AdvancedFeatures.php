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

    // Enforce credit limit at checkout
    public function enforce_credit_limit() {
        $user_id = get_current_user_id();
        $limit = floatval( get_user_meta( $user_id, 'b2b_credit_limit', true ) );
        if ( $limit > 0 ) {
            $current_balance = $this->get_user_credit_balance( $user_id );
            $cart_total = WC()->cart->total;
            if ( $cart_total + $current_balance > $limit ) {
                wc_add_notice( 'Your order exceeds your credit limit.', 'error' );
            }
        }
    }
    // Get user credit balance (placeholder: should sum unpaid orders)
    public function get_user_credit_balance( $user_id ) {
        // For demo, return 0. In production, sum unpaid orders.
        return 0;
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

    // Plugin integration hooks (placeholder)
    public function plugin_integrations() {}
} 