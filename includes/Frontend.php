<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class Frontend {
    public function __construct() {
        add_shortcode( 'b2b_dashboard', [ $this, 'b2b_dashboard_shortcode' ] );
        add_shortcode( 'b2b_order_history', [ $this, 'order_history_shortcode' ] );
        add_shortcode( 'b2b_account', [ $this, 'account_management_shortcode' ] );
        add_shortcode( 'b2b_wishlist', [ $this, 'wishlist_shortcode' ] );
        add_action( 'init', [ $this, 'handle_invoice_download' ] );
    }

    // B2B dashboard shortcode
    public function b2b_dashboard_shortcode() {
        if ( ! is_user_logged_in() ) return '<p>Please log in to access your B2B dashboard.</p>';
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
        echo '<hr><h3 id="b2b-quick-order">Quick Order Pad</h3>';
        echo do_shortcode('[b2b_bulk_order]');
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
        $user_id = get_current_user_id();
        $orders = wc_get_orders( [ 'customer_id' => $user_id, 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ] );
        ob_start();
        echo '<table class="b2b-order-history"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th>Invoice</th></tr></thead><tbody>';
        foreach ( $orders as $order ) {
            echo '<tr>';
            echo '<td>#' . $order->get_id() . '</td>';
            echo '<td>' . esc_html( $order->get_date_created()->date( 'Y-m-d' ) ) . '</td>';
            echo '<td>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>';
            echo '<td>' . esc_html( $order->get_formatted_order_total() ) . '</td>';
            echo '<td><a href="' . esc_url( add_query_arg( [ 'b2b_invoice' => $order->get_id() ] ) ) . '" target="_blank">Download</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    // Invoice download handler (HTML)
    public function handle_invoice_download() {
        if ( isset( $_GET['b2b_invoice'] ) && is_user_logged_in() ) {
            $order_id = intval( $_GET['b2b_invoice'] );
            $order = wc_get_order( $order_id );
            if ( $order && $order->get_user_id() == get_current_user_id() ) {
                header( 'Content-Type: text/html' );
                echo '<h2>Invoice for Order #' . $order->get_id() . '</h2>';
                echo '<p>Date: ' . esc_html( $order->get_date_created()->date( 'Y-m-d' ) ) . '</p>';
                echo '<p>Total: ' . esc_html( $order->get_formatted_order_total() ) . '</p>';
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
} 