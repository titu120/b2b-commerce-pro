<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class PricingManager {
    public function __construct() {
        // Create pricing table on activation
        register_activation_hook( B2B_COMMERCE_PRO_BASENAME, [ __CLASS__, 'create_pricing_table' ] );
        // Self-healing: check and create table if missing
        add_action( 'init', [ $this, 'maybe_create_pricing_table' ] );
        add_action( 'admin_notices', [ $this, 'admin_notice_table_error' ] );
        add_action( 'admin_menu', [ $this, 'add_pricing_menu' ] );
        add_filter( 'woocommerce_product_get_price', [ $this, 'apply_pricing_rules' ], 10, 2 );
        add_filter( 'woocommerce_product_get_sale_price', [ $this, 'apply_pricing_rules' ], 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'enforce_min_max_quantity' ] );
        add_action( 'admin_post_b2b_save_pricing_rule', [ $this, 'save_pricing_rule' ] );
        add_action( 'admin_post_b2b_delete_pricing_rule', [ $this, 'delete_pricing_rule' ] );
        // Quote request system
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_quote_scripts' ] );
        add_action( 'wp_ajax_b2b_quote_request', [ $this, 'handle_quote_request' ] );
        add_action( 'wp_ajax_nopriv_b2b_quote_request', [ $this, 'handle_quote_request' ] );
        // Price request system placeholder
        add_action( 'woocommerce_single_product_summary', [ $this, 'price_request_button' ], 35 );
    }

    // Create custom table for pricing rules
    public static function create_pricing_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(64),
            user_id BIGINT UNSIGNED,
            group_id BIGINT UNSIGNED,
            geo_zone VARCHAR(64),
            start_date DATE,
            end_date DATE,
            min_qty INT,
            max_qty INT,
            price DECIMAL(20,6),
            type VARCHAR(32),
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    // Self-healing: check and create table if missing
    public function maybe_create_pricing_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( $exists != $table ) {
            self::create_pricing_table();
            // Check again
            $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
            if ( $exists != $table ) {
                update_option( 'b2b_pricing_table_error', 1 );
            } else {
                delete_option( 'b2b_pricing_table_error' );
            }
        } else {
            delete_option( 'b2b_pricing_table_error' );
        }
    }

    public function admin_notice_table_error() {
        if ( get_option( 'b2b_pricing_table_error' ) ) {
            echo '<div class="notice notice-error"><p><strong>B2B Commerce Pro:</strong> Could not create the pricing rules table. Please check your database permissions or contact your host.</p></div>';
        }
    }

    // Add admin menu for pricing rules
    public function add_pricing_menu() {
        add_submenu_page(
            'b2b-dashboard', // Parent slug for B2B Commerce
            'B2B Pricing',
            'B2B Pricing',
            'manage_woocommerce',
            'b2b-pricing',
            [ $this, 'pricing_rules_page' ]
        );
    }

    // Admin UI for pricing rules
    public function pricing_rules_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_rule = $edit_id ? $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id) ) : null;
        $rules = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
        $content = '';
        $content .= '<div class="b2b-admin-header">';
        $content .= '<h1><span class="icon dashicons dashicons-tag"></span>B2B Pricing Rules</h1>';
        $content .= '<p>Manage and create pricing rules for B2B customers.</p>';
        $content .= '</div>';
        // Add/Edit form
        $content .= '<div class="b2b-admin-card">';
        $content .= '<div class="b2b-admin-card-title"><span class="icon dashicons dashicons-plus"></span>' . ($edit_rule ? 'Edit' : 'Add') . ' Pricing Rule</div>';
        $content .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="b2b-admin-form">';
        $content .= wp_nonce_field('b2b_save_pricing_rule', 'b2b_nonce', true, false);
        if ($edit_rule) $content .= '<input type="hidden" name="id" value="' . esc_attr($edit_rule->id) . '">';
        $fields = [
            ['Product ID', 'product_id', 'number', true],
            ['Role', 'role', 'text', false],
            ['User ID', 'user_id', 'number', false],
            ['Group ID', 'group_id', 'number', false],
            ['Geo Zone', 'geo_zone', 'text', false],
            ['Start Date', 'start_date', 'date', false],
            ['End Date', 'end_date', 'date', false],
            ['Min Qty', 'min_qty', 'number', false],
            ['Max Qty', 'max_qty', 'number', false],
            ['Price', 'price', 'text', true],
            ['Type', 'type', 'text', false],
        ];
        foreach ($fields as $f) {
            $content .= '<div class="b2b-admin-form-group">';
            $content .= '<label for="' . $f[1] . '">' . $f[0] . ($f[3] ? ' <span style="color:#e53935">*</span>' : '') . '</label>';
            $content .= '<input type="' . $f[2] . '" name="' . $f[1] . '" id="' . $f[1] . '" value="' . esc_attr($edit_rule->{$f[1]} ?? '') . '"' . ($f[3] ? ' required' : '') . '>';
            $content .= '</div>';
        }
        $content .= '<input type="hidden" name="action" value="b2b_save_pricing_rule">';
        $content .= '<button type="submit" class="b2b-admin-btn"><span class="icon dashicons dashicons-saved"></span>' . ($edit_rule ? 'Update' : 'Add') . ' Rule</button>';
        $content .= '</form>';
        $content .= '</div>';
        // List table
        $content .= '<div class="b2b-admin-card">';
        $content .= '<div class="b2b-admin-card-title"><span class="icon dashicons dashicons-list-view"></span>All Pricing Rules</div>';
        $content .= '<div style="overflow-x:auto">';
        $content .= '<table class="b2b-admin-table">';
        $content .= '<thead><tr><th>ID</th><th>Product</th><th>Role</th><th>User</th><th>Group</th><th>Geo</th><th>Start</th><th>End</th><th>Min</th><th>Max</th><th>Price</th><th>Type</th><th>Actions</th></tr></thead><tbody>';
        foreach ($rules as $rule) {
            $content .= '<tr>';
            $content .= '<td>' . esc_html($rule->id) . '</td>';
            $content .= '<td>' . esc_html($rule->product_id) . '</td>';
            $content .= '<td>' . esc_html($rule->role) . '</td>';
            $content .= '<td>' . esc_html($rule->user_id) . '</td>';
            $content .= '<td>' . esc_html($rule->group_id) . '</td>';
            $content .= '<td>' . esc_html($rule->geo_zone) . '</td>';
            $content .= '<td>' . esc_html($rule->start_date) . '</td>';
            $content .= '<td>' . esc_html($rule->end_date) . '</td>';
            $content .= '<td>' . esc_html($rule->min_qty) . '</td>';
            $content .= '<td>' . esc_html($rule->max_qty) . '</td>';
            $content .= '<td>' . esc_html($rule->price) . '</td>';
            $content .= '<td>' . esc_html($rule->type) . '</td>';
            $content .= '<td><a href="' . esc_url(admin_url('admin.php?page=b2b-pricing&edit=' . $rule->id)) . '" class="b2b-admin-btn" style="padding: 6px 12px; font-size: 0.9em;"><span class="icon dashicons dashicons-edit"></span>Edit</a> ';
            $content .= '<a href="' . esc_url(admin_url('admin-post.php?action=b2b_delete_pricing_rule&id=' . $rule->id . '&_wpnonce=' . wp_create_nonce('b2b_delete_pricing_rule'))) . '" class="b2b-admin-btn" style="padding: 6px 12px; font-size: 0.9em;"><span class="icon dashicons dashicons-trash"></span>Delete</a></td>';
            $content .= '</tr>';
        }
        $content .= '</tbody></table>';
        $content .= '</div></div>';
        // Use the same wrapper as other admin pages
        if (class_exists('B2B\\AdminPanel')) {
            $panel = new \B2B\AdminPanel();
            $panel->render_admin_wrapper('b2b-pricing', $content);
        } else {
            echo $content;
        }
    }

    // Apply pricing rules to WooCommerce product price
    public function apply_pricing_rules( $price, $product ) {
        if ( ! is_user_logged_in() ) return $price;
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $roles = $user->roles;
        $product_id = $product->get_id();
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        // Query for best matching rule (role, user, group, geo, time, qty)
        $rules = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE product_id = %d", $product_id ) );
        $best_price = $price;
        foreach ( $rules as $rule ) {
            // Check role
            if ( $rule->role && ! in_array( $rule->role, $roles ) ) continue;
            // Check user
            if ( $rule->user_id && $rule->user_id != $user_id ) continue;
            // Check group
            if ( $rule->group_id ) {
                $user_groups = wp_get_object_terms( $user_id, 'b2b_user_group', [ 'fields' => 'ids' ] );
                if ( ! in_array( $rule->group_id, $user_groups ) ) continue;
            }
            // Check geo (placeholder)
            // Check time
            $now = date( 'Y-m-d' );
            if ( $rule->start_date && $now < $rule->start_date ) continue;
            if ( $rule->end_date && $now > $rule->end_date ) continue;
            // Check quantity (handled in cart)
            // Use lowest price
            if ( $rule->price < $best_price ) {
                $best_price = $rule->price;
            }
        }
        return $best_price;
    }

    // Enforce min/max quantity in cart
    public function enforce_min_max_quantity( $cart ) {
        if ( is_admin() ) return;
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            $rules = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE product_id = %d", $product_id ) );
            foreach ( $rules as $rule ) {
                if ( $rule->min_qty && $cart_item['quantity'] < $rule->min_qty ) {
                    wc_add_notice( 'Minimum quantity for this product is ' . $rule->min_qty, 'error' );
                }
                if ( $rule->max_qty && $cart_item['quantity'] > $rule->max_qty ) {
                    wc_add_notice( 'Maximum quantity for this product is ' . $rule->max_qty, 'error' );
                }
            }
        }
    }

    // Save pricing rule (add/edit)
    public function save_pricing_rule() {
        if (!current_user_can('manage_woocommerce') || !isset($_POST['b2b_nonce']) || !wp_verify_nonce($_POST['b2b_nonce'], 'b2b_save_pricing_rule')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $data = [
            'product_id' => intval($_POST['product_id']),
            'role' => sanitize_text_field($_POST['role']),
            'user_id' => intval($_POST['user_id']),
            'group_id' => intval($_POST['group_id']),
            'geo_zone' => sanitize_text_field($_POST['geo_zone']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'min_qty' => intval($_POST['min_qty']),
            'max_qty' => intval($_POST['max_qty']),
            'price' => floatval($_POST['price']),
            'type' => sanitize_text_field($_POST['type']),
        ];
        if (!empty($_POST['id'])) {
            $wpdb->update($table, $data, ['id' => intval($_POST['id'])]);
        } else {
            $wpdb->insert($table, $data);
        }
        wp_redirect(admin_url('admin.php?page=b2b-pricing'));
        exit;
    }

    // Delete pricing rule
    public function delete_pricing_rule() {
        if (!current_user_can('manage_woocommerce') || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'b2b_delete_pricing_rule')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'b2b_pricing_rules';
        $wpdb->delete($table, ['id' => intval($_GET['id'])]);
        wp_redirect(admin_url('admin.php?page=b2b-pricing'));
        exit;
    }

    // Enqueue scripts for quote request
    public function enqueue_quote_scripts() {
        wp_enqueue_script('b2b-quote-request', B2B_COMMERCE_PRO_URL . 'assets/js/b2b-commerce-pro.js', ['jquery'], B2B_COMMERCE_PRO_VERSION, true);
        wp_localize_script('b2b-quote-request', 'b2bQuote', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2b_quote_request'),
        ]);
        wp_enqueue_style('b2b-quote-style', B2B_COMMERCE_PRO_URL . 'assets/css/b2b-commerce-pro.css', [], B2B_COMMERCE_PRO_VERSION);
    }

    // Price request button and modal
    public function price_request_button() {
        global $product;
        if ( ! $product->is_type( 'simple' ) ) return;
        echo '<button class="button b2b-price-request" data-product="' . esc_attr($product->get_id()) . '">Request a Quote</button>';
        echo '<div id="b2b-quote-modal" style="display:none;"><form id="b2b-quote-form"><h3>Request a Quote</h3>';
        echo '<input type="hidden" name="product_id" value="' . esc_attr($product->get_id()) . '">';
        echo '<p><label>Your Email<br><input type="email" name="email" required></label></p>';
        echo '<p><label>Quantity<br><input type="number" name="quantity" min="1" required></label></p>';
        echo '<p><label>Message<br><textarea name="message" required></textarea></label></p>';
        echo '<p><button type="submit" class="button">Send Request</button></p>';
        echo '<div class="b2b-quote-response"></div>';
        echo '</form></div>';
        ?>
        <script>
        jQuery(function($){
            $('.b2b-price-request').on('click', function(e){
                e.preventDefault();
                $('#b2b-quote-modal').show();
            });
            $('#b2b-quote-form').on('submit', function(e){
                e.preventDefault();
                var data = $(this).serialize();
                $.post(b2bQuote.ajax_url, data + '&action=b2b_quote_request&nonce=' + b2bQuote.nonce, function(response){
                    $('.b2b-quote-response').html(response.data ? response.data : response);
                });
            });
        });
        </script>
        <?php
    }

    // Handle AJAX quote request
    public function handle_quote_request() {
        check_ajax_referer('b2b_quote_request', 'nonce');
        $product_id = intval($_POST['product_id']);
        $email = sanitize_email($_POST['email']);
        $quantity = intval($_POST['quantity']);
        $message = sanitize_textarea_field($_POST['message']);
        $product = wc_get_product($product_id);
        $admin_email = get_option('admin_email');
        $subject = 'B2B Quote Request for ' . $product->get_name();
        $body = "Product: " . $product->get_name() . "\nEmail: $email\nQuantity: $quantity\nMessage: $message";
        wp_mail($admin_email, $subject, $body);
        wp_send_json_success('Your quote request has been sent!');
    }

    // Placeholder methods for all pricing features
    public function tiered_pricing() {}
    public function role_based_pricing() {}
    public function customer_specific_pricing() {}
    public function geographic_pricing() {}
    public function time_based_pricing() {}
    public function min_max_quantity() {}
    public function price_request() {}
} 