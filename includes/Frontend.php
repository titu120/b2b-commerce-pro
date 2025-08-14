<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class Frontend {
    public function __construct() {
        add_shortcode( 'b2b_dashboard', [ $this, 'b2b_dashboard_shortcode' ] );
        add_shortcode( 'b2b_order_history', [ $this, 'order_history_shortcode' ] );
        add_shortcode( 'b2b_account', [ $this, 'account_management_shortcode' ] );
        add_shortcode( 'b2b_wishlist', [ $this, 'wishlist_shortcode' ] );
        add_shortcode( 'b2b_registration', [ $this, 'registration_form_shortcode' ] );
        add_shortcode( 'b2b_bulk_order', [ $this, 'bulk_order_shortcode' ] );
        add_action( 'init', [ $this, 'handle_invoice_download' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
    }

    // B2B dashboard shortcode
    public function b2b_dashboard_shortcode() {
        if ( ! is_user_logged_in() ) return '<p>' . __('Please log in to access your B2B dashboard.', 'b2b-commerce-pro') . '</p>';
        $user = wp_get_current_user();
        ob_start();
        echo '<div class="b2b-dashboard">';
        echo '<h2>Welcome, ' . esc_html( $user->display_name ) . '</h2>';
        echo '<ul class="b2b-dashboard-links">';
        echo '<li><a href="' . esc_url( wc_get_account_endpoint_url( 'orders' ) ) . '">Order History</a></li>';
        echo '<li><a href="' . esc_url( wc_get_account_endpoint_url( 'edit-account' ) ) . '">Account Management</a></li>';
        echo '<li><a href="' . esc_url( wc_get_cart_url() ) . '">Cart</a></li>';
        echo '<li><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">Shop</a></li>';
        echo '<li><a href="#b2b-quick-order">Quick Order</a></li>';
        echo '<li><a href="#b2b-wishlist">Wishlist</a></li>';
        echo '</ul>';
        echo '<hr><h3>Order History</h3>';
        echo do_shortcode('[b2b_order_history]');
        echo '<hr><h3>Account Management</h3>';
        echo do_shortcode('[b2b_account]');
        echo '<hr><h3 id="b2b-wishlist">Wishlist</h3>';
        echo do_shortcode('[b2b_wishlist]');
        echo '</div>';
        return ob_get_clean();
    }

    // Order history shortcode
    public function order_history_shortcode() {
        if ( ! is_user_logged_in() ) return '';
        
        if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) {
            return '<p>WooCommerce is required for order history.</p>';
        }
        
        $user_id = get_current_user_id();
        $orders = wc_get_orders( [ 'customer_id' => $user_id, 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ] );
        ob_start();
        echo '<table class="b2b-order-history"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th>Invoice</th></tr></thead><tbody>';
        foreach ( $orders as $order ) {
            echo '<tr>';
            echo '<td>#' . $order->get_id() . '</td>';
            echo '<td>' . esc_html( $order->get_date_created()->date( 'Y-m-d' ) ) . '</td>';
            echo '<td>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>';
            echo '<td>' . esc_html( get_woocommerce_currency_symbol() . number_format( $order->get_total(), 2 ) ) . '</td>';
            echo '<td><a href="' . esc_url( add_query_arg( [ 'b2b_invoice' => $order->get_id() ] ) ) . '" target="_blank">Download</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    // Invoice download handler (HTML)
    public function handle_invoice_download() {
        if ( isset( $_GET['b2b_invoice'] ) && is_user_logged_in() ) {
            if (!class_exists('WooCommerce') || !function_exists('wc_get_order')) {
                wp_die('WooCommerce is required for invoice functionality.');
            }
            
            $order_id = intval( $_GET['b2b_invoice'] );
            $order = wc_get_order( $order_id );
            if ( $order && $order->get_user_id() == get_current_user_id() ) {
                header( 'Content-Type: text/html' );
                echo '<h2>Invoice for Order #' . $order->get_id() . '</h2>';
                echo '<p>Date: ' . esc_html( $order->get_date_created()->date( 'Y-m-d' ) ) . '</p>';
                echo '<p>Total: ' . esc_html( get_woocommerce_currency_symbol() . number_format( $order->get_total(), 2 ) ) . '</p>';
                echo '<h3>Items</h3><ul>';
                foreach ( $order->get_items() as $item ) {
                    echo '<li>' . esc_html( $item->get_name() ) . ' x ' . esc_html( $item->get_quantity() ) . '</li>';
                }
                echo '</ul>';
                exit;
            }
        }
    }

    // Account management shortcode
    public function account_management_shortcode() {
        if ( ! is_user_logged_in() ) return '';
        $user = wp_get_current_user();
        ob_start();
        echo '<form method="post">';
        wp_nonce_field( 'b2b_account_update', 'b2b_account_nonce' );
        echo '<p><label>Company Name<br><input type="text" name="company_name" value="' . esc_attr( get_user_meta( $user->ID, 'company_name', true ) ) . '"></label></p>';
        echo '<p><label>Business Type<br><input type="text" name="business_type" value="' . esc_attr( get_user_meta( $user->ID, 'business_type', true ) ) . '"></label></p>';
        echo '<p><label>Tax ID<br><input type="text" name="tax_id" value="' . esc_attr( get_user_meta( $user->ID, 'tax_id', true ) ) . '"></label></p>';
        echo '<p><button type="submit" class="button">Update</button></p>';
        echo '</form>';
        if ( isset( $_POST['b2b_account_nonce'] ) && wp_verify_nonce( $_POST['b2b_account_nonce'], 'b2b_account_update' ) ) {
            update_user_meta( $user->ID, 'company_name', sanitize_text_field( $_POST['company_name'] ) );
            update_user_meta( $user->ID, 'business_type', sanitize_text_field( $_POST['business_type'] ) );
            update_user_meta( $user->ID, 'tax_id', sanitize_text_field( $_POST['tax_id'] ) );
            echo '<div class="notice notice-success"><p>Account updated.</p></div>';
        }
        return ob_get_clean();
    }

    // Wishlist integration (basic)
    public function wishlist_shortcode() {
        if ( function_exists( 'YITH_WCWL' ) ) {
            return do_shortcode('[yith_wcwl_wishlist]');
        } elseif ( function_exists( 'woosw_init' ) ) {
            return do_shortcode('[woosw]');
        } else {
            return '<p>No wishlist plugin detected.</p>';
        }
    }

    // B2B Registration form shortcode
    public function registration_form_shortcode() {
        if (is_user_logged_in()) {
            return '<p>You are already logged in. <a href="' . wp_logout_url() . '">Logout</a> to register a new account.</p>';
        }

        $message = '';
        
        // Handle form submission first
        if (isset($_POST['b2b_registration_nonce']) && wp_verify_nonce($_POST['b2b_registration_nonce'], 'b2b_registration')) {
            $message = $this->process_registration_form();
        }

        ob_start();
        ?>
        <div class="b2b-registration-form">
            <h2>B2B Account Registration</h2>
            <p>Register for a B2B account to access wholesale pricing and bulk ordering.</p>
            
            <?php if ($message): ?>
                <div class="b2b-message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form method="post" action="" class="b2b-registration-form">
                <?php wp_nonce_field('b2b_registration', 'b2b_registration_nonce'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_login">Username *</label>
                        <input type="text" name="user_login" id="user_login" value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="user_email">Email *</label>
                        <input type="email" name="user_email" id="user_email" value="<?php echo isset($_POST['user_email']) ? esc_attr($_POST['user_email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_password">Password *</label>
                        <input type="password" name="user_password" id="user_password" required>
                    </div>
                    <div class="form-group">
                        <label for="user_password_confirm">Confirm Password *</label>
                        <input type="password" name="user_password_confirm" id="user_password_confirm" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_name">Company Name *</label>
                        <input type="text" name="company_name" id="company_name" value="<?php echo isset($_POST['company_name']) ? esc_attr($_POST['company_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="business_type">Business Type *</label>
                        <select name="business_type" id="business_type" required>
                            <option value="">Select Business Type</option>
                            <option value="wholesale" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'wholesale') ? 'selected' : ''; ?>>Wholesale</option>
                            <option value="retail" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'retail') ? 'selected' : ''; ?>>Retail</option>
                            <option value="distributor" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'distributor') ? 'selected' : ''; ?>>Distributor</option>
                            <option value="manufacturer" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'manufacturer') ? 'selected' : ''; ?>>Manufacturer</option>
                            <option value="other" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tax_id">Tax ID / VAT Number</label>
                        <input type="text" name="tax_id" id="tax_id" value="<?php echo isset($_POST['tax_id']) ? esc_attr($_POST['tax_id']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="user_role">Account Type *</label>
                        <select name="user_role" id="user_role" required>
                            <option value="">Select Account Type</option>
                            <option value="wholesale_customer" <?php echo (isset($_POST['user_role']) && $_POST['user_role'] === 'wholesale_customer') ? 'selected' : ''; ?>>Wholesale Customer</option>
                            <option value="distributor" <?php echo (isset($_POST['user_role']) && $_POST['user_role'] === 'distributor') ? 'selected' : ''; ?>>Distributor</option>
                            <option value="retailer" <?php echo (isset($_POST['user_role']) && $_POST['user_role'] === 'retailer') ? 'selected' : ''; ?>>Retailer</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Business Address</label>
                    <textarea name="address" id="address" rows="3"><?php echo isset($_POST['address']) ? esc_textarea($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="terms_agreement" required>
                        I agree to the <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Privacy Policy</a>
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="b2b-submit-btn">Register B2B Account</button>
                </div>
            </form>
        </div>
        
        <style>
        .b2b-registration-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .b2b-registration-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .b2b-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid;
        }
        .b2b-message.notice-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .b2b-message.notice-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .b2b-submit-btn {
            background: #2196f3;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .b2b-submit-btn:hover {
            background: #1976d2;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        
        return ob_get_clean();
    }

    // Process registration form with comprehensive error handling
    private function process_registration_form() {
        // Check if user is already logged in
        if (is_user_logged_in()) {
            return '<div class="b2b-message notice-error"><p>❌ You are already logged in. Please logout to register a new account.</p></div>';
        }

        // Verify nonce
        if (!isset($_POST['b2b_registration_nonce']) || !wp_verify_nonce($_POST['b2b_registration_nonce'], 'b2b_registration')) {
            return '<div class="b2b-message notice-error"><p>❌ Security check failed. Please try again.</p></div>';
        }

        // Sanitize input data
        $user_login = sanitize_user($_POST['user_login']);
        $user_email = sanitize_email($_POST['user_email']);
        $user_password = $_POST['user_password'];
        $user_password_confirm = $_POST['user_password_confirm'];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $company_name = sanitize_text_field($_POST['company_name']);
        $business_type = sanitize_text_field($_POST['business_type']);
        $tax_id = sanitize_text_field($_POST['tax_id']);
        $user_role = sanitize_text_field($_POST['user_role']);
        $phone = sanitize_text_field($_POST['phone']);
        $address = sanitize_textarea_field($_POST['address']);

        // Comprehensive validation
        $errors = [];
        
        // Required field validation
        if (empty($user_login)) {
            $errors[] = 'Username is required.';
        }
        
        if (empty($user_email)) {
            $errors[] = 'Email is required.';
        }
        
        if (empty($user_password)) {
            $errors[] = 'Password is required.';
        }
        
        if (empty($user_password_confirm)) {
            $errors[] = 'Password confirmation is required.';
        }
        
        if (empty($first_name)) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($last_name)) {
            $errors[] = 'Last name is required.';
        }
        
        if (empty($company_name)) {
            $errors[] = 'Company name is required.';
        }
        
        if (empty($business_type)) {
            $errors[] = 'Business type is required.';
        }
        
        if (empty($user_role)) {
            $errors[] = 'Account type is required.';
        }

        // Email validation
        if (!empty($user_email) && !is_email($user_email)) {
            $errors[] = 'Please enter a valid email address.';
        }

        // Password validation
        if (!empty($user_password) && strlen($user_password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        
        if (!empty($user_password) && !empty($user_password_confirm) && $user_password !== $user_password_confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // Username validation
        if (!empty($user_login) && !validate_username($user_login)) {
            $errors[] = 'Username contains invalid characters.';
        }

        // Check if user already exists
        if (!empty($user_login) && username_exists($user_login)) {
            $errors[] = 'Username already exists. Please choose a different username.';
        }
        
        if (!empty($user_email) && email_exists($user_email)) {
            $errors[] = 'Email already exists. Please use a different email address.';
        }

        // Role validation
        $valid_roles = ['wholesale_customer', 'distributor', 'retailer'];
        if (!empty($user_role) && !in_array($user_role, $valid_roles)) {
            $errors[] = 'Invalid account type selected.';
        }


        if (!empty($errors)) {
            return '<div class="b2b-message notice-error"><p>❌ ' . implode('<br>', $errors) . '</p></div>';
        }

        // Create user with error handling
        try {
            $user_id = wp_create_user($user_login, $user_password, $user_email);
            
            if (is_wp_error($user_id)) {
                return '<div class="b2b-message notice-error"><p>❌ Registration failed: ' . esc_html($user_id->get_error_message()) . '</p></div>';
            }

            // Set user role
            $user = get_userdata($user_id);
            if (!$user) {
                return '<div class="b2b-message notice-error"><p>❌ Failed to create user account.</p></div>';
            }

            $user->set_role($user_role);
            
            // Update user meta with error handling
            $update_result = wp_update_user([
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name
            ]);

            if (is_wp_error($update_result)) {
                return '<div class="b2b-message notice-error"><p>❌ Failed to update user profile: ' . esc_html($update_result->get_error_message()) . '</p></div>';
            }
            
            // Update user meta fields
            update_user_meta($user_id, 'company_name', $company_name);
            update_user_meta($user_id, 'business_type', $business_type);
            update_user_meta($user_id, 'tax_id', $tax_id);
            update_user_meta($user_id, 'phone', $phone);
            update_user_meta($user_id, 'address', $address);
            update_user_meta($user_id, 'b2b_approval_status', 'pending');
            
            return '<div class="b2b-message notice-success"><p>✅ Registration successful! Your account is pending approval. You will receive an email once your account is approved.</p></div>';
            
        } catch (Exception $e) {
            return '<div class="b2b-message notice-error"><p>❌ Registration failed: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    // Bulk order shortcode
    public function bulk_order_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to access bulk ordering.</p>';
        }

        ob_start();
        ?>
        <div class="b2b-bulk-order">
            <h3>Bulk Order</h3>
            <form id="b2b-bulk-order-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('b2b_bulk_order', 'b2b_bulk_order_nonce'); ?>
                <div id="b2b-bulk-products">
                    <div class="b2b-bulk-row">
                        <input type="text" class="b2b-product-search" name="product_search[]" placeholder="Search product by name or SKU" autocomplete="off">
                        <input type="number" name="product_qty[]" min="1" value="1" placeholder="Quantity">
                    </div>
                </div>
                <button type="button" id="b2b-add-row" class="button">Add Another Product</button>
                <hr>
                <h4>Or Import from CSV</h4>
                <input type="file" name="b2b_bulk_csv" accept=".csv">
                <hr>
                <button type="submit" class="button button-primary">Add to Cart</button>
                <div class="b2b-bulk-order-response"></div>
            </form>
        </div>
        <script>
        jQuery(function($){
            $('#b2b-add-row').on('click', function(){
                $('#b2b-bulk-products').append('<div class="b2b-bulk-row"><input type="text" class="b2b-product-search" name="product_search[]" placeholder="Search product by name or SKU" autocomplete="off"><input type="number" name="product_qty[]" min="1" value="1" placeholder="Quantity"></div>');
            });
            $(document).on('input', '.b2b-product-search', function(){
                var input = $(this);
                $.get((typeof b2b_ajax !== 'undefined' ? b2b_ajax.ajaxurl : ajaxurl), {action:'b2b_bulk_product_search', term:input.val()}, function(res){
                    if(res.success && res.data.length){
                        var list = $('<ul class="b2b-search-list"></ul>');
                        $.each(res.data, function(i, p){
                            list.append('<li data-id="'+p.id+'">'+p.text+'</li>');
                        });
                        input.nextAll('.b2b-search-list').remove();
                        input.after(list);
                    }
                });
            });
            $(document).on('click', '.b2b-search-list li', function(){
                var id = $(this).data('id');
                var text = $(this).text();
                var input = $(this).closest('.b2b-bulk-row').find('.b2b-product-search');
                input.val(text);
                input.data('product-id', id);
                $(this).parent().remove();
                $('<input type="hidden" name="product_id[]" value="'+id+'">').insertAfter(input);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    // Enqueue frontend scripts
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'b2b-commerce-pro-frontend',
            B2B_COMMERCE_PRO_URL . 'assets/css/b2b-commerce-pro.css',
            [],
            B2B_COMMERCE_PRO_VERSION
        );
        
        wp_enqueue_script(
            'b2b-commerce-pro-frontend',
            B2B_COMMERCE_PRO_URL . 'assets/js/b2b-commerce-pro.js',
            ['jquery'],
            B2B_COMMERCE_PRO_VERSION,
            true
        );
        
        // Add mobile-responsive features
        wp_add_inline_script('b2b-commerce-pro-frontend', '
            jQuery(document).ready(function($) {
                // Mobile-responsive table
                $(".b2b-order-history").each(function() {
                    if ($(window).width() < 768) {
                        $(this).addClass("mobile-table");
                    }
                });
                
                // Mobile menu toggle for B2B dashboard
                $(".b2b-dashboard-links").prepend("<button class=\'b2b-mobile-toggle\'>☰ Menu</button>");
                $(".b2b-mobile-toggle").click(function() {
                    $(this).siblings("li").toggle();
                });
                
                // Responsive form handling
                $(".b2b-registration-form input, .b2b-registration-form select").on("focus", function() {
                    $(this).parent().addClass("focused");
                }).on("blur", function() {
                    if (!$(this).val()) {
                        $(this).parent().removeClass("focused");
                    }
                });
            });
        ');
    }
} 