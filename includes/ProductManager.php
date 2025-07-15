<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class ProductManager {
    public function __construct() {
        // Product meta for visibility and wholesale-only
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_product_visibility_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_visibility_fields' ] );
        add_filter( 'woocommerce_product_is_visible', [ $this, 'filter_product_visibility' ], 10, 2 );
        add_filter( 'woocommerce_product_query_meta_query', [ $this, 'filter_product_query_visibility' ] );
        // Category restrictions
        add_action( 'product_cat_add_form_fields', [ $this, 'add_category_restriction_fields' ] );
        add_action( 'product_cat_edit_form_fields', [ $this, 'edit_category_restriction_fields' ], 10, 2 );
        add_action( 'edited_product_cat', [ $this, 'save_category_restriction_fields' ] );
        add_action( 'create_product_cat', [ $this, 'save_category_restriction_fields' ] );
        add_filter( 'woocommerce_product_query_tax_query', [ $this, 'filter_category_restrictions' ] );
        // Product inquiry system
        add_action( 'woocommerce_single_product_summary', [ $this, 'product_inquiry_button' ], 40 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_inquiry_scripts' ] );
        add_action( 'wp_ajax_b2b_product_inquiry', [ $this, 'handle_product_inquiry' ] );
        add_action( 'wp_ajax_nopriv_b2b_product_inquiry', [ $this, 'handle_product_inquiry' ] );
        // Bulk order and CSV import placeholders
        add_shortcode( 'b2b_bulk_order', [ $this, 'bulk_order_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_bulk_order_scripts' ] );
        add_action( 'wp_ajax_b2b_bulk_product_search', [ $this, 'ajax_product_search' ] );
        add_action( 'wp_ajax_nopriv_b2b_bulk_product_search', [ $this, 'ajax_product_search' ] );
        add_action( 'wp_loaded', [ $this, 'handle_bulk_order_form' ] );
    }

    // Add product visibility and wholesale-only fields
    public function add_product_visibility_fields() {
        global $post;
        echo '<div class="options_group">';
        // User roles
        woocommerce_wp_text_input([
            'id' => '_b2b_visible_roles',
            'label' => 'Visible to Roles (comma separated)',
            'desc_tip' => true,
            'description' => 'Enter user roles allowed to see this product.'
        ]);
        // User groups
        woocommerce_wp_text_input([
            'id' => '_b2b_visible_groups',
            'label' => 'Visible to Groups (comma separated IDs)',
            'desc_tip' => true,
            'description' => 'Enter group IDs allowed to see this product.'
        ]);
        // Wholesale-only
        woocommerce_wp_checkbox([
            'id' => '_b2b_wholesale_only',
            'label' => 'Wholesale Only',
            'description' => 'Only visible to wholesale users.'
        ]);
        echo '</div>';
    }

    // Save product visibility fields
    public function save_product_visibility_fields( $post_id ) {
        update_post_meta( $post_id, '_b2b_visible_roles', sanitize_text_field( $_POST['_b2b_visible_roles'] ?? '' ) );
        update_post_meta( $post_id, '_b2b_visible_groups', sanitize_text_field( $_POST['_b2b_visible_groups'] ?? '' ) );
        update_post_meta( $post_id, '_b2b_wholesale_only', isset( $_POST['_b2b_wholesale_only'] ) ? 'yes' : 'no' );
    }

    // Filter product visibility on frontend
    public function filter_product_visibility( $visible, $product_id ) {
        if ( is_admin() ) return $visible;
        $roles = (array) ( wp_get_current_user()->roles ?? [] );
        $groups = wp_get_object_terms( get_current_user_id(), 'b2b_user_group', [ 'fields' => 'ids' ] );
        $allowed_roles = array_map( 'trim', explode( ',', get_post_meta( $product_id, '_b2b_visible_roles', true ) ) );
        $allowed_groups = array_map( 'intval', explode( ',', get_post_meta( $product_id, '_b2b_visible_groups', true ) ) );
        $wholesale_only = get_post_meta( $product_id, '_b2b_wholesale_only', true ) === 'yes';
        if ( $wholesale_only && ! in_array( 'wholesale_customer', $roles ) ) return false;
        if ( $allowed_roles && $allowed_roles[0] && ! array_intersect( $roles, $allowed_roles ) ) return false;
        if ( $allowed_groups && $allowed_groups[0] && ! array_intersect( $groups, $allowed_groups ) ) return false;
        return $visible;
    }

    // Filter product queries for visibility
    public function filter_product_query_visibility( $meta_query ) {
        if ( is_admin() ) return $meta_query;
        $user = wp_get_current_user();
        $roles = (array) ( $user->roles ?? [] );
        $groups = wp_get_object_terms( $user->ID, 'b2b_user_group', [ 'fields' => 'ids' ] );
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key' => '_b2b_visible_roles',
                'value' => '',
                'compare' => '='
            ],
            [
                'key' => '_b2b_visible_roles',
                'value' => implode( ',', $roles ),
                'compare' => 'LIKE'
            ]
        ];
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key' => '_b2b_visible_groups',
                'value' => '',
                'compare' => '='
            ],
            [
                'key' => '_b2b_visible_groups',
                'value' => implode( ',', $groups ),
                'compare' => 'LIKE'
            ]
        ];
        return $meta_query;
    }

    // Add category restriction fields
    public function add_category_restriction_fields() {
        ?>
        <div class="form-field">
            <label for="b2b_cat_roles">Allowed Roles (comma separated)</label>
            <input type="text" name="b2b_cat_roles" id="b2b_cat_roles">
        </div>
        <div class="form-field">
            <label for="b2b_cat_groups">Allowed Groups (comma separated IDs)</label>
            <input type="text" name="b2b_cat_groups" id="b2b_cat_groups">
        </div>
        <?php
    }
    public function edit_category_restriction_fields( $term, $taxonomy ) {
        $roles = get_term_meta( $term->term_id, 'b2b_cat_roles', true );
        $groups = get_term_meta( $term->term_id, 'b2b_cat_groups', true );
        ?>
        <tr class="form-field">
            <th scope="row"><label for="b2b_cat_roles">Allowed Roles</label></th>
            <td><input type="text" name="b2b_cat_roles" id="b2b_cat_roles" value="<?php echo esc_attr( $roles ); ?>"></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="b2b_cat_groups">Allowed Groups</label></th>
            <td><input type="text" name="b2b_cat_groups" id="b2b_cat_groups" value="<?php echo esc_attr( $groups ); ?>"></td>
        </tr>
        <?php
    }
    public function save_category_restriction_fields( $term_id ) {
        update_term_meta( $term_id, 'b2b_cat_roles', sanitize_text_field( $_POST['b2b_cat_roles'] ?? '' ) );
        update_term_meta( $term_id, 'b2b_cat_groups', sanitize_text_field( $_POST['b2b_cat_groups'] ?? '' ) );
    }
    public function filter_category_restrictions( $tax_query ) {
        if ( is_admin() ) return $tax_query;
        // Placeholder: could add logic to restrict categories by user role/group
        return $tax_query;
    }

    // Product inquiry button and modal
    public function product_inquiry_button() {
        global $product;
        echo '<button class="button b2b-inquiry-btn" data-product="' . esc_attr( $product->get_id() ) . '">Product Inquiry</button>';
        echo '<div id="b2b-inquiry-modal" style="display:none;"><form id="b2b-inquiry-form"><h3>Product Inquiry</h3>';
        echo '<input type="hidden" name="product_id" value="' . esc_attr( $product->get_id() ) . '">';
        echo '<p><label>Your Email<br><input type="email" name="email" required></label></p>';
        echo '<p><label>Message<br><textarea name="message" required></textarea></label></p>';
        echo '<p><button type="submit" class="button">Send Inquiry</button></p>';
        echo '<div class="b2b-inquiry-response"></div>';
        echo '</form></div>';
        ?>
        <script>
        jQuery(function($){
            $('.b2b-inquiry-btn').on('click', function(e){
                e.preventDefault();
                $('#b2b-inquiry-modal').show();
            });
            $('#b2b-inquiry-form').on('submit', function(e){
                e.preventDefault();
                var data = $(this).serialize();
                $.post(b2bQuote.ajax_url, data + '&action=b2b_product_inquiry&nonce=' + b2bQuote.nonce, function(response){
                    $('.b2b-inquiry-response').html(response.data ? response.data : response);
                });
            });
        });
        </script>
        <?php
    }
    public function enqueue_inquiry_scripts() {
        wp_enqueue_script('b2b-quote-request', B2B_COMMERCE_PRO_URL . 'assets/js/b2b-commerce-pro.js', ['jquery'], B2B_COMMERCE_PRO_VERSION, true);
        wp_localize_script('b2b-quote-request', 'b2bQuote', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2b_quote_request'),
        ]);
        wp_enqueue_style('b2b-quote-style', B2B_COMMERCE_PRO_URL . 'assets/css/b2b-commerce-pro.css', [], B2B_COMMERCE_PRO_VERSION);
    }
    public function handle_product_inquiry() {
        check_ajax_referer('b2b_quote_request', 'nonce');
        $product_id = intval($_POST['product_id']);
        $email = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message']);
        $product = wc_get_product($product_id);
        $admin_email = get_option('admin_email');
        $subject = 'B2B Product Inquiry for ' . $product->get_name();
        $body = "Product: " . $product->get_name() . "\nEmail: $email\nMessage: $message";
        wp_mail($admin_email, $subject, $body);
        wp_send_json_success('Your inquiry has been sent!');
    }

    // Bulk order shortcode
    public function bulk_order_shortcode() {
        ob_start();
        ?>
        <form id="b2b-bulk-order-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'b2b_bulk_order', 'b2b_bulk_order_nonce' ); ?>
            <h3>Bulk Order</h3>
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
        <script>
        jQuery(function($){
            $('#b2b-add-row').on('click', function(){
                $('#b2b-bulk-products').append('<div class="b2b-bulk-row"><input type="text" class="b2b-product-search" name="product_search[]" placeholder="Search product by name or SKU" autocomplete="off"><input type="number" name="product_qty[]" min="1" value="1" placeholder="Quantity"></div>');
            });
            $(document).on('input', '.b2b-product-search', function(){
                var input = $(this);
                $.get(b2bQuote.ajax_url, {action:'b2b_bulk_product_search', term:input.val()}, function(res){
                    if(res.data && res.data.length){
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

    // Enqueue scripts for bulk order
    public function enqueue_bulk_order_scripts() {
        wp_enqueue_script('b2b-quote-request', B2B_COMMERCE_PRO_URL . 'assets/js/b2b-commerce-pro.js', ['jquery'], B2B_COMMERCE_PRO_VERSION, true);
        wp_localize_script('b2b-quote-request', 'b2bQuote', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2b_quote_request'),
        ]);
        wp_enqueue_style('b2b-quote-style', B2B_COMMERCE_PRO_URL . 'assets/css/b2b-commerce-pro.css', [], B2B_COMMERCE_PRO_VERSION);
    }

    // AJAX product search
    public function ajax_product_search() {
        $term = sanitize_text_field($_GET['term'] ?? '');
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 10,
            's' => $term,
        ];
        $query = new \WP_Query($args);
        $results = [];
        foreach ($query->posts as $p) {
            $results[] = [ 'id' => $p->ID, 'text' => $p->post_title ];
        }
        wp_send_json_success($results);
    }

    // Handle bulk order form submission
    public function handle_bulk_order_form() {
        if ( isset($_POST['b2b_bulk_order_nonce']) && wp_verify_nonce($_POST['b2b_bulk_order_nonce'], 'b2b_bulk_order') ) {
            $product_ids = $_POST['product_id'] ?? [];
            $qtys = $_POST['product_qty'] ?? [];
            foreach ($product_ids as $i => $pid) {
                $pid = intval($pid);
                $qty = intval($qtys[$i] ?? 1);
                if ($pid && $qty > 0) {
                    \WC()->cart->add_to_cart($pid, $qty);
                }
            }
            // Handle CSV import
            if ( !empty($_FILES['b2b_bulk_csv']['tmp_name']) ) {
                $file = fopen($_FILES['b2b_bulk_csv']['tmp_name'], 'r');
                while ( ($row = fgetcsv($file)) !== false ) {
                    $pid = wc_get_product_id_by_sku($row[0]);
                    if ( !$pid ) $pid = intval($row[0]);
                    $qty = intval($row[1] ?? 1);
                    if ($pid && $qty > 0) {
                        \WC()->cart->add_to_cart($pid, $qty);
                    }
                }
                fclose($file);
            }
            wp_safe_redirect( wc_get_cart_url() );
            exit;
        }
    }

    // Bulk order and CSV import placeholders
    public function bulk_order() {}
    public function csv_import() {}
} 