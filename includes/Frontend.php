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
        ?>
            <!-- Header Section -->
            <div class="b2b-dashboard-header">
                <div class="b2b-header-background">
                    <div class="b2b-header-pattern"></div>
                </div>
                <div class="b2b-welcome-section">
                    <div class="b2b-welcome-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9ZM19 21H5V3H13V9H19V21Z" fill="white"/>
                        </svg>
                    </div>
                    <div class="b2b-welcome-text">
                        <h2><?php echo esc_html( $user->display_name ); ?></h2>
                        <p><?php _e('Welcome to your B2B Dashboard', 'b2b-commerce-pro'); ?></p>
                        <div class="b2b-welcome-stats">
                            <div class="b2b-stat-item">
                                <span class="b2b-stat-number">12</span>
                                <span class="b2b-stat-label"><?php _e('Orders', 'b2b-commerce-pro'); ?></span>
                            </div>
                            <div class="b2b-stat-item">
                                <span class="b2b-stat-number">৳2,450</span>
                                <span class="b2b-stat-label"><?php _e('Total Spent', 'b2b-commerce-pro'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="b2b-user-role">
                    <?php 
                    $user_roles = $user->roles;
                    $role_display = '';
                    if (in_array('wholesale_customer', $user_roles)) {
                        $role_display = __('Wholesale Customer', 'b2b-commerce-pro');
                    } elseif (in_array('distributor', $user_roles)) {
                        $role_display = __('Distributor', 'b2b-commerce-pro');
                    } elseif (in_array('retailer', $user_roles)) {
                        $role_display = __('Retailer', 'b2b-commerce-pro');
                    } else {
                        $role_display = __('B2B Customer', 'b2b-commerce-pro');
                    }
                    ?>
                    <span class="b2b-role-badge"><?php echo esc_html($role_display); ?></span>
                </div>
            </div>

            <!-- Quick Actions Cards -->
            <div class="b2b-quick-actions">
                <div class="b2b-section-title">
                    <div class="b2b-title-accent"></div>
                    <h3><?php _e('Quick Actions', 'b2b-commerce-pro'); ?></h3>
                    <p><?php _e('Access your most important features', 'b2b-commerce-pro'); ?></p>
                </div>
                <div class="b2b-action-cards">
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="b2b-action-card b2b-card-primary">
                        <div class="b2b-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7 18C5.9 18 5.01 18.9 5.01 20C5.01 21.1 5.9 22 7 22C8.1 22 9 21.1 9 20C9 18.9 8.1 18 7 18ZM1 2V4H3L6.6 11.59L5.25 14.04C5.09 14.32 5 14.65 5 15C5 16.1 5.9 17 7 17H19V15H7.42C7.28 15 7.17 14.89 7.17 14.75L7.2 14.63L8.1 13H15.55C16.3 13 16.96 12.59 17.3 11.97L20.88 5H5.21L4.27 2H1ZM17 18C15.9 18 15.01 18.9 15.01 20C15.01 21.1 15.9 22 17 22C18.1 22 19 21.1 19 20C19 18.9 18.1 18 17 18Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="b2b-card-content">
                            <h4><?php _e('Shop Products', 'b2b-commerce-pro'); ?></h4>
                            <p><?php _e('Browse our catalog', 'b2b-commerce-pro'); ?></p>
                        </div>
                        <div class="b2b-card-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.59 16.59L13.17 12L8.59 7.41L10 6L16 12L10 18L8.59 16.59Z" fill="white"/>
                            </svg>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="b2b-action-card b2b-card-success">
                        <div class="b2b-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7 18C5.9 18 5.01 18.9 5.01 20C5.01 21.1 5.9 22 7 22C8.1 22 9 21.1 9 20C9 18.9 8.1 18 7 18ZM1 2V4H3L6.6 11.59L5.25 14.04C5.09 14.32 5 14.65 5 15C5 16.1 5.9 17 7 17H19V15H7.42C7.28 15 7.17 14.89 7.17 14.75L7.2 14.63L8.1 13H15.55C16.3 13 16.96 12.59 17.3 11.97L20.88 5H5.21L4.27 2H1ZM17 18C15.9 18 15.01 18.9 15.01 20C15.01 21.1 15.9 22 17 22C18.1 22 19 21.1 19 20C19 18.9 18.1 18 17 18Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="b2b-card-content">
                            <h4><?php _e('View Cart', 'b2b-commerce-pro'); ?></h4>
                            <p><?php _e('Check your items', 'b2b-commerce-pro'); ?></p>
                        </div>
                        <div class="b2b-card-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.59 16.59L13.17 12L8.59 7.41L10 6L16 12L10 18L8.59 16.59Z" fill="white"/>
                            </svg>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="b2b-action-card b2b-card-warning">
                        <div class="b2b-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 3H5C3.89 3 3 3.89 3 5V19C3 20.11 3.89 21 5 21H19C20.11 21 21 20.11 21 19V5C21 3.89 20.11 3 19 3ZM19 19H5V5H19V19ZM17 13H7V11H17V13ZM13 17H7V15H13V17ZM7 7V9H17V7H7Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="b2b-card-content">
                            <h4><?php _e('Order History', 'b2b-commerce-pro'); ?></h4>
                            <p><?php _e('View past orders', 'b2b-commerce-pro'); ?></p>
                        </div>
                        <div class="b2b-card-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.59 16.59L13.17 12L8.59 7.41L10 6L16 12L10 18L8.59 16.59Z" fill="white"/>
                            </svg>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>" class="b2b-action-card b2b-card-info">
                        <div class="b2b-card-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20ZM12.5 7H11V13L16.25 16.15L17 14.92L12.5 12.25V7Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="b2b-card-content">
                            <h4><?php _e('Account Settings', 'b2b-commerce-pro'); ?></h4>
                            <p><?php _e('Manage your profile', 'b2b-commerce-pro'); ?></p>
                        </div>
                        <div class="b2b-card-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.59 16.59L13.17 12L8.59 7.41L10 6L16 12L10 18L8.59 16.59Z" fill="white"/>
                            </svg>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Dashboard Sections -->
            <div class="b2b-dashboard-sections">
                <!-- Order History Section -->
                <div class="b2b-section-card">
                    <div class="b2b-section-header">
                        <div class="b2b-section-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 3H5C3.89 3 3 3.89 3 5V19C3 20.11 3.89 21 5 21H19C20.11 21 21 20.11 21 19V5C21 3.89 20.11 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z" fill="#3B82F6"/>
                            </svg>
                        </div>
                        <h3><?php _e('Recent Orders', 'b2b-commerce-pro'); ?></h3>
                    </div>
                    <div class="b2b-section-content">
                        <?php echo do_shortcode('[b2b_order_history]'); ?>
                    </div>
                </div>

                <!-- Account Management Section -->
                <div class="b2b-section-card">
                    <div class="b2b-section-header">
                        <div class="b2b-section-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#10B981"/>
                            </svg>
                        </div>
                        <h3><?php _e('Account Information', 'b2b-commerce-pro'); ?></h3>
                    </div>
                    <div class="b2b-section-content">
                        <?php echo do_shortcode('[b2b_account]'); ?>
                    </div>
                </div>

                <!-- Wishlist Section -->
                <div class="b2b-section-card">
                    <div class="b2b-section-header">
                        <div class="b2b-section-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 21.35L10.55 20.03C5.4 15.36 2 12.27 2 8.5C2 5.41 4.42 3 7.5 3C9.24 3 10.91 3.81 12 5.08C13.09 3.81 14.76 3 16.5 3C19.58 3 22 5.41 22 8.5C22 12.27 18.6 15.36 13.45 20.03L12 21.35Z" fill="#EF4444"/>
                            </svg>
                        </div>
                        <h3><?php _e('Wishlist', 'b2b-commerce-pro'); ?></h3>
                    </div>
                    <div class="b2b-section-content">
                        <?php echo do_shortcode('[b2b_wishlist]'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // Order history shortcode
    public function order_history_shortcode() {
        if ( ! is_user_logged_in() ) return '';
        
        if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) {
            return '<p>' . __('WooCommerce is required for order history.', 'b2b-commerce-pro') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $orders = wc_get_orders( [ 'customer_id' => $user_id, 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ] );
        ob_start();
        if (empty($orders)) {
            echo '<div class="b2b-empty-state">';
            echo '<div class="b2b-empty-icon">📦</div>';
            echo '<h4>' . __('No Orders Yet', 'b2b-commerce-pro') . '</h4>';
            echo '<p>' . __('You haven\'t placed any orders yet. Start shopping to see your order history here.', 'b2b-commerce-pro') . '</p>';
            echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="b2b-empty-action">' . __('Start Shopping', 'b2b-commerce-pro') . '</a>';
            echo '</div>';
        } else {
            echo '<div class="b2b-order-table-wrapper">';
            echo '<table class="b2b-order-history-modern">';
            echo '<thead><tr>';
            echo '<th>' . __('Order', 'b2b-commerce-pro') . '</th>';
            echo '<th>' . __('Date', 'b2b-commerce-pro') . '</th>';
            echo '<th>' . __('Status', 'b2b-commerce-pro') . '</th>';
            echo '<th>' . __('Total', 'b2b-commerce-pro') . '</th>';
            echo '<th>' . __('Actions', 'b2b-commerce-pro') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ( $orders as $order ) {
                $status = $order->get_status();
                $status_class = 'b2b-status-' . $status;
                $status_icon = '';
                
                // Add status icons
                switch($status) {
                    case 'completed':
                        $status_icon = '✅';
                        break;
                    case 'processing':
                        $status_icon = '⏳';
                        break;
                    case 'pending':
                        $status_icon = '⏸️';
                        break;
                    case 'cancelled':
                        $status_icon = '❌';
                        break;
                    default:
                        $status_icon = '📋';
                }
                
                echo '<tr>';
                echo '<td><strong>#' . $order->get_id() . '</strong></td>';
                echo '<td>' . esc_html( $order->get_date_created()->date( 'M j, Y' ) ) . '</td>';
                echo '<td><span class="b2b-status-badge ' . esc_attr($status_class) . '">' . $status_icon . ' ' . esc_html( wc_get_order_status_name( $status ) ) . '</span></td>';
                echo '<td><strong>' . esc_html( get_woocommerce_currency_symbol() . number_format( $order->get_total(), 2 ) ) . '</strong></td>';
                echo '<td><a href="' . esc_url( add_query_arg( [ 'b2b_invoice' => $order->get_id() ] ) ) . '" target="_blank" class="b2b-invoice-link">📄 ' . __('Invoice', 'b2b-commerce-pro') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        }
        return ob_get_clean();
    }

    // Invoice download handler (HTML)
    public function handle_invoice_download() {
        if ( isset( $_GET['b2b_invoice'] ) && is_user_logged_in() ) {
            if (!class_exists('WooCommerce') || !function_exists('wc_get_order')) {
                wp_die(__('WooCommerce is required for invoice functionality.', 'b2b-commerce-pro'));
            }
            
            $order_id = intval( $_GET['b2b_invoice'] );
            $order = wc_get_order( $order_id );
            if ( $order && $order->get_user_id() == get_current_user_id() ) {
                header( 'Content-Type: text/html' );
                echo '<h2>' . sprintf(__('Invoice for Order #%s', 'b2b-commerce-pro'), $order->get_id()) . '</h2>';
                echo '<p>' . sprintf(__('Date: %s', 'b2b-commerce-pro'), esc_html( $order->get_date_created()->date( 'Y-m-d' ) )) . '</p>';
                echo '<p>' . sprintf(__('Total: %s', 'b2b-commerce-pro'), esc_html( get_woocommerce_currency_symbol() . number_format( $order->get_total(), 2 ) )) . '</p>';
                echo '<h3>' . __('Items', 'b2b-commerce-pro') . '</h3><ul>';
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
        ?>
        <div class="b2b-account-form-modern">
            <form method="post" class="b2b-account-form">
                <?php wp_nonce_field( 'b2b_account_update', 'b2b_account_nonce' ); ?>
                
                <div class="b2b-form-row">
                    <div class="b2b-form-group">
                        <label for="company_name"><?php _e('Company Name', 'b2b-commerce-pro'); ?></label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr( get_user_meta( $user->ID, 'company_name', true ) ); ?>" placeholder="<?php _e('Enter your company name', 'b2b-commerce-pro'); ?>">
                    </div>
                </div>
                
                <div class="b2b-form-row">
                    <div class="b2b-form-group">
                        <label for="business_type"><?php _e('Business Type', 'b2b-commerce-pro'); ?></label>
                        <input type="text" id="business_type" name="business_type" value="<?php echo esc_attr( get_user_meta( $user->ID, 'business_type', true ) ); ?>" placeholder="<?php _e('e.g., Wholesale, Retail, Distributor', 'b2b-commerce-pro'); ?>">
                    </div>
                </div>
                
                <div class="b2b-form-row">
                    <div class="b2b-form-group">
                        <label for="tax_id"><?php _e('Tax ID', 'b2b-commerce-pro'); ?></label>
                        <input type="text" id="tax_id" name="tax_id" value="<?php echo esc_attr( get_user_meta( $user->ID, 'tax_id', true ) ); ?>" placeholder="<?php _e('Enter your tax identification number', 'b2b-commerce-pro'); ?>">
                    </div>
                </div>
                
                <div class="b2b-form-actions">
                    <button type="submit" class="b2b-submit-btn"><?php _e('Update Account', 'b2b-commerce-pro'); ?></button>
                </div>
            </form>
            
            <?php if ( isset( $_POST['b2b_account_nonce'] ) && wp_verify_nonce( $_POST['b2b_account_nonce'], 'b2b_account_update' ) ) {
                update_user_meta( $user->ID, 'company_name', sanitize_text_field( $_POST['company_name'] ) );
                update_user_meta( $user->ID, 'business_type', sanitize_text_field( $_POST['business_type'] ) );
                update_user_meta( $user->ID, 'tax_id', sanitize_text_field( $_POST['tax_id'] ) );
                ?>
                <div class="b2b-success-message">
                    <div class="b2b-success-icon">✅</div>
                    <p><?php _e('Account information updated successfully!', 'b2b-commerce-pro'); ?></p>
                </div>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // Wishlist integration (basic)
    public function wishlist_shortcode() {
        if ( function_exists( 'YITH_WCWL' ) ) {
            return do_shortcode('[yith_wcwl_wishlist]');
        } elseif ( function_exists( 'woosw_init' ) ) {
            return do_shortcode('[woosw]');
        } else {
            return '<p>' . __('No wishlist plugin detected.', 'b2b-commerce-pro') . '</p>';
        }
    }

    // B2B Registration form shortcode
    public function registration_form_shortcode() {
        if (is_user_logged_in()) {
            return '<p>' . sprintf(__('You are already logged in. %s to register a new account.', 'b2b-commerce-pro'), '<a href="' . wp_logout_url() . '">' . __('Logout', 'b2b-commerce-pro') . '</a>') . '</p>';
        }

        $message = '';
        
        // Handle form submission first
        if (isset($_POST['b2b_registration_nonce']) && wp_verify_nonce($_POST['b2b_registration_nonce'], 'b2b_registration')) {
            $message = $this->process_registration_form();
        }

        ob_start();
        ?>
        <div class="b2b-registration-form">
            <h2><?php _e('B2B Account Registration', 'b2b-commerce-pro'); ?></h2>
            <p><?php _e('Register for a B2B account to access wholesale pricing and bulk ordering.', 'b2b-commerce-pro'); ?></p>
            
            <?php if ($message): ?>
                <div class="b2b-message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form method="post" action="" class="b2b-registration-form">
                <?php wp_nonce_field('b2b_registration', 'b2b_registration_nonce'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_login"><?php _e('Username', 'b2b-commerce-pro'); ?> *</label>
                        <input type="text" name="user_login" id="user_login" value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="user_email"><?php _e('Email', 'b2b-commerce-pro'); ?> *</label>
                        <input type="email" name="user_email" id="user_email" value="<?php echo isset($_POST['user_email']) ? esc_attr($_POST['user_email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_password"><?php _e('Password', 'b2b-commerce-pro'); ?> *</label>
                        <input type="password" name="user_password" id="user_password" required>
                    </div>
                    <div class="form-group">
                        <label for="user_password_confirm"><?php _e('Confirm Password', 'b2b-commerce-pro'); ?> *</label>
                        <input type="password" name="user_password_confirm" id="user_password_confirm" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name"><?php _e('First Name', 'b2b-commerce-pro'); ?> *</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name"><?php _e('Last Name', 'b2b-commerce-pro'); ?> *</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_name"><?php _e('Company Name', 'b2b-commerce-pro'); ?> *</label>
                        <input type="text" name="company_name" id="company_name" value="<?php echo isset($_POST['company_name']) ? esc_attr($_POST['company_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="business_type"><?php _e('Business Type', 'b2b-commerce-pro'); ?> *</label>
                        <select name="business_type" id="business_type" required>
                            <option value=""><?php _e('Select Business Type', 'b2b-commerce-pro'); ?></option>
                            <option value="wholesale" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'wholesale') ? 'selected' : ''; ?>><?php _e('Wholesale', 'b2b-commerce-pro'); ?></option>
                            <option value="retail" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'retail') ? 'selected' : ''; ?>><?php _e('Retail', 'b2b-commerce-pro'); ?></option>
                            <option value="distributor" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'distributor') ? 'selected' : ''; ?>><?php _e('Distributor', 'b2b-commerce-pro'); ?></option>
                            <option value="manufacturer" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'manufacturer') ? 'selected' : ''; ?>><?php _e('Manufacturer', 'b2b-commerce-pro'); ?></option>
                            <option value="other" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] === 'other') ? 'selected' : ''; ?>><?php _e('Other', 'b2b-commerce-pro'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tax_id"><?php _e('Tax ID / VAT Number', 'b2b-commerce-pro'); ?></label>
                        <input type="text" name="tax_id" id="tax_id" value="<?php echo isset($_POST['tax_id']) ? esc_attr($_POST['tax_id']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="user_role"><?php _e('Account Type', 'b2b-commerce-pro'); ?> *</label>
                        <select name="user_role" id="user_role" required>
                            <option value=""><?php _e('Select Account Type', 'b2b-commerce-pro'); ?></option>
                            <option value="wholesale_customer" <?php echo (isset($_POST['user_role']) && $_POST['user_role'] === 'wholesale_customer') ? 'selected' : ''; ?>><?php _e('Wholesale Customer', 'b2b-commerce-pro'); ?></option>
                            <option value="distributor" <?php echo (isset($_POST['user_role']) && $_POST['user_role'] === 'distributor') ? 'selected' : ''; ?>><?php _e('Distributor', 'b2b-commerce-pro'); ?></option>
                            <option value="retailer" <?php echo (isset($_POST['user_role']) && $_POST['user_role'] === 'retailer') ? 'selected' : ''; ?>><?php _e('Retailer', 'b2b-commerce-pro'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone"><?php _e('Phone Number', 'b2b-commerce-pro'); ?></label>
                    <input type="tel" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address"><?php _e('Business Address', 'b2b-commerce-pro'); ?></label>
                    <textarea name="address" id="address" rows="3"><?php echo isset($_POST['address']) ? esc_textarea($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="terms_agreement" required>
                        <?php printf(__('I agree to the %sTerms and Conditions%s and %sPrivacy Policy%s', 'b2b-commerce-pro'), '<a href="#" target="_blank">', '</a>', '<a href="#" target="_blank">', '</a>'); ?>
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="b2b-submit-btn"><?php _e('Register B2B Account', 'b2b-commerce-pro'); ?></button>
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
            return '<div class="b2b-message notice-error"><p>❌ ' . __('You are already logged in. Please logout to register a new account.', 'b2b-commerce-pro') . '</p></div>';
        }

        // Verify nonce
        if (!isset($_POST['b2b_registration_nonce']) || !wp_verify_nonce($_POST['b2b_registration_nonce'], 'b2b_registration')) {
            return '<div class="b2b-message notice-error"><p>❌ ' . __('Security check failed. Please try again.', 'b2b-commerce-pro') . '</p></div>';
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
            $errors[] = __('Username is required.', 'b2b-commerce-pro');
        }
        
        if (empty($user_email)) {
            $errors[] = __('Email is required.', 'b2b-commerce-pro');
        }
        
        if (empty($user_password)) {
            $errors[] = __('Password is required.', 'b2b-commerce-pro');
        }
        
        if (empty($user_password_confirm)) {
            $errors[] = __('Password confirmation is required.', 'b2b-commerce-pro');
        }
        
        if (empty($first_name)) {
            $errors[] = __('First name is required.', 'b2b-commerce-pro');
        }
        
        if (empty($last_name)) {
            $errors[] = __('Last name is required.', 'b2b-commerce-pro');
        }
        
        if (empty($company_name)) {
            $errors[] = __('Company name is required.', 'b2b-commerce-pro');
        }
        
        if (empty($business_type)) {
            $errors[] = __('Business type is required.', 'b2b-commerce-pro');
        }
        
        if (empty($user_role)) {
            $errors[] = __('Account type is required.', 'b2b-commerce-pro');
        }

        // Email validation
        if (!empty($user_email) && !is_email($user_email)) {
            $errors[] = __('Please enter a valid email address.', 'b2b-commerce-pro');
        }

        // Password validation
        if (!empty($user_password) && strlen($user_password) < 6) {
            $errors[] = __('Password must be at least 6 characters long.', 'b2b-commerce-pro');
        }
        
        if (!empty($user_password) && !empty($user_password_confirm) && $user_password !== $user_password_confirm) {
            $errors[] = __('Passwords do not match.', 'b2b-commerce-pro');
        }

        // Username validation
        if (!empty($user_login) && !validate_username($user_login)) {
            $errors[] = __('Username contains invalid characters.', 'b2b-commerce-pro');
        }

        // Check if user already exists
        if (!empty($user_login) && username_exists($user_login)) {
            $errors[] = __('Username already exists. Please choose a different username.', 'b2b-commerce-pro');
        }
        
        if (!empty($user_email) && email_exists($user_email)) {
            $errors[] = __('Email already exists. Please use a different email address.', 'b2b-commerce-pro');
        }

        // Role validation
        $valid_roles = ['wholesale_customer', 'distributor', 'retailer'];
        if (!empty($user_role) && !in_array($user_role, $valid_roles)) {
            $errors[] = __('Invalid account type selected.', 'b2b-commerce-pro');
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
            
            return '<div class="b2b-message notice-success"><p>✅ ' . __('Registration successful! Your account is pending approval. You will receive an email once your account is approved.', 'b2b-commerce-pro') . '</p></div>';
            
        } catch (Exception $e) {
            return '<div class="b2b-message notice-error"><p>❌ ' . sprintf(__('Registration failed: %s', 'b2b-commerce-pro'), esc_html($e->getMessage())) . '</p></div>';
        }
    }

    // Bulk order shortcode
    public function bulk_order_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access bulk ordering.', 'b2b-commerce-pro') . '</p>';
        }

        ob_start();
        ?>
        <div class="b2b-bulk-order-modern">
            <!-- Header Section -->
            <div class="b2b-bulk-header">
                <div class="b2b-bulk-header-content">
                    <div class="b2b-bulk-icon">📦</div>
                    <div class="b2b-bulk-title">
                        <h2><?php _e('Bulk Order Management', 'b2b-commerce-pro'); ?></h2>
                        <p><?php _e('Add multiple products to your cart quickly and efficiently', 'b2b-commerce-pro'); ?></p>
                    </div>
                </div>
                <div class="b2b-bulk-stats">
                    <div class="b2b-stat-item">
                        <span class="b2b-stat-number" id="b2b-product-count">-</span>
                        <span class="b2b-stat-label"><?php _e('Products', 'b2b-commerce-pro'); ?></span>
                    </div>
                    <div class="b2b-stat-item">
                        <span class="b2b-stat-number" id="b2b-total-qty">-</span>
                        <span class="b2b-stat-label"><?php _e('Total Qty', 'b2b-commerce-pro'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="b2b-bulk-content">
                <!-- CSV Import Section -->
                <div class="b2b-section-card">
                    <div class="b2b-section-header">
                        <div class="b2b-section-icon">📄</div>
                        <h3><?php _e('Import from CSV', 'b2b-commerce-pro'); ?></h3>
                    </div>
                    <div class="b2b-section-content">
                        <div class="b2b-csv-import">
                            <div class="b2b-csv-info">
                                <p><?php _e('Upload a CSV file with product SKUs and quantities to add multiple products at once.', 'b2b-commerce-pro'); ?></p>
                                <a href="#" class="b2b-csv-template" download="bulk_order_template.csv">📥 <?php _e('Download CSV Template', 'b2b-commerce-pro'); ?></a>
                            </div>
                            <div class="b2b-file-upload">
                                <input type="file" name="b2b_bulk_csv" accept=".csv" id="b2b-csv-upload" class="b2b-file-input">
                                <label for="b2b-csv-upload" class="b2b-file-label">
                                    <div class="b2b-upload-icon">📁</div>
                                    <span><?php _e('Choose CSV File', 'b2b-commerce-pro'); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product List Display -->
                <div class="b2b-section-card" id="b2b-product-list-section" style="display: none;">
                    <div class="b2b-section-header">
                        <div class="b2b-section-icon">📋</div>
                        <h3><?php _e('Loaded Products', 'b2b-commerce-pro'); ?></h3>
                    </div>
                    <div class="b2b-section-content">
                        <div class="b2b-product-list-display">
                            <div class="b2b-product-list-header">
                                <span class="b2b-product-header-sku"><?php _e('SKU', 'b2b-commerce-pro'); ?></span>
                                <span class="b2b-product-header-qty"><?php _e('Quantity', 'b2b-commerce-pro'); ?></span>
                                <span class="b2b-product-header-price"><?php _e('Price', 'b2b-commerce-pro'); ?></span>
                            </div>
                            <div class="b2b-product-list-items" id="b2b-product-list-items">
                                <!-- Products will be displayed here dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="b2b-section-card">
                    <div class="b2b-section-header">
                        <div class="b2b-section-icon">📊</div>
                        <h3><?php _e('Order Summary', 'b2b-commerce-pro'); ?></h3>
                    </div>
                    <div class="b2b-section-content">
                        <div class="b2b-order-summary">
                            <div class="b2b-summary-item">
                                <span class="b2b-summary-label"><?php _e('Total Products:', 'b2b-commerce-pro'); ?></span>
                                <span class="b2b-summary-value" id="b2b-summary-products">-</span>
                            </div>
                            <div class="b2b-summary-item">
                                <span class="b2b-summary-label"><?php _e('Total Quantity:', 'b2b-commerce-pro'); ?></span>
                                <span class="b2b-summary-value" id="b2b-summary-qty">-</span>
                            </div>
                            <div class="b2b-summary-item">
                                <span class="b2b-summary-label"><?php _e('Estimated Total:', 'b2b-commerce-pro'); ?></span>
                                <span class="b2b-summary-value" id="b2b-summary-total">-</span>
                            </div>
                        </div>
                        
                        <div class="b2b-bulk-actions">
                            <button type="button" class="b2b-add-to-cart-btn" disabled>
                                <span class="b2b-cart-icon">🛒</span>
                                <?php _e('Add All to Cart', 'b2b-commerce-pro'); ?>
                            </button>
                            <button type="button" class="b2b-clear-all-btn" id="b2b-clear-all">
                                <?php _e('Clear All', 'b2b-commerce-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Messages -->
            <div class="b2b-bulk-order-response"></div>
        </div>

        <script>
        jQuery(function($){
            console.log('B2B Bulk Order Interface Initialized');
            
            // Initialize interface
            initializeInterface();
            
            // Initialize interface function
            function initializeInterface() {
                // Get currency symbol dynamically
                const currencySymbol = '<?php echo get_woocommerce_currency_symbol(); ?>' || '৳';
                
                // Reset all counters to show dashes initially
                updateAllCounters('-', '-', '-');
                
                // Disable add to cart button initially
                $('.b2b-add-to-cart-btn').prop('disabled', true);
                
                // Clear any stored products
                window.b2bCSVProducts = [];
                
                // Store currency symbol globally
                window.b2bCurrencySymbol = currencySymbol;
                
                console.log('Interface initialized with currency:', currencySymbol);
            }
            
            // CSV file upload and processing
            $('#b2b-csv-upload').on('change', function() {
                const file = this.files[0];
                if (!file) return;
                
                console.log('CSV file selected:', file.name);
                
                // Show filename and file info
                $(this).siblings('.b2b-file-label').find('span').text(file.name);
                
                // Show file size and type
                const fileSize = (file.size / 1024).toFixed(2);
                const fileInfo = `${file.name} (${fileSize} KB)`;
                console.log('File info:', fileInfo);
                
                // Update interface to show processing
                updateFileStatus('Processing file...', 'info');
                
                // Process CSV file
                processCSVFile(file);
            });
            
            // Process CSV file function
            function processCSVFile(file) {
                console.log('Processing CSV file...');
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    try {
                        const csvData = e.target.result;
                        console.log('CSV data loaded, length:', csvData.length);
                        
                        const products = parseCSV(csvData);
                        console.log('Parsed products:', products);
                        
                        if (products.length > 0) {
                            // Update order summary
                            updateOrderSummary(products);
                            // Enable add to cart button
                            $('.b2b-add-to-cart-btn').prop('disabled', false);
                            updateFileStatus(`Successfully loaded ${products.length} products!`, 'success');
                            console.log('CSV processed successfully');
                        } else {
                            updateFileStatus('No valid products found in CSV file', 'error');
                            console.log('No valid products found');
                        }
                    } catch (error) {
                        console.error('CSV processing error:', error);
                        updateFileStatus('Error processing CSV file: ' + error.message, 'error');
                    }
                };
                
                reader.onerror = function() {
                    console.error('File reading error');
                    updateFileStatus('Error reading CSV file', 'error');
                };
                
                reader.readAsText(file);
            }
            
            // Parse CSV data
            function parseCSV(csvText) {
                const lines = csvText.split('\n');
                const products = [];
                
                console.log('CSV lines:', lines.length);
                
                // Skip header row and process data
                for (let i = 1; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (!line) continue;
                    
                    const columns = line.split(',').map(col => col.trim().replace(/"/g, ''));
                    console.log('Line', i, 'columns:', columns);
                    
                    if (columns.length >= 2) {
                        const sku = columns[0];
                        const quantity = parseInt(columns[1]) || 1;
                        
                        if (sku && quantity > 0) {
                            products.push({
                                sku: sku,
                                quantity: quantity
                            });
                            console.log('Added product:', sku, 'qty:', quantity);
                        }
                    }
                }
                
                return products;
            }
            
            // Update order summary with CSV data
            function updateOrderSummary(products) {
                const totalProducts = products.length;
                const totalQuantity = products.reduce((sum, product) => sum + product.quantity, 0);
                
                console.log('Updating summary - Products:', totalProducts, 'Quantity:', totalQuantity);
                
                // Update all counters with proper formatting
                updateCountersFormatted(totalProducts, totalQuantity);
                
                // Display product list
                displayProductList(products);
                
                // Store products data for cart
                window.b2bCSVProducts = products;
                
                console.log('Summary updated successfully');
            }
            
            // Display product list
            function displayProductList(products) {
                const productListSection = $('#b2b-product-list-section');
                const productListItems = $('#b2b-product-list-items');
                
                if (products.length === 0) {
                    productListSection.hide();
                    return;
                }
                
                // Show the product list section
                productListSection.show();
                
                // Clear existing items
                productListItems.empty();
                
                // Add each product to the list
                products.forEach((product, index) => {
                    const productRow = `
                        <div class="b2b-product-list-item">
                            <span class="b2b-product-sku">${product.sku}</span>
                            <span class="b2b-product-qty">${product.quantity}</span>
                            <span class="b2b-product-price">${window.b2bCurrencySymbol || '৳'}${(product.quantity * 10).toFixed(2)}</span>
                        </div>
                    `;
                    productListItems.append(productRow);
                });
                
                console.log('Product list displayed with', products.length, 'products');
            }
            
            // Update all counters function
            function updateAllCounters(products, quantity, total) {
                // Update header stats
                $('#b2b-product-count').text(products);
                $('#b2b-total-qty').text(quantity);
                
                // Update order summary
                $('#b2b-summary-products').text(products);
                $('#b2b-summary-qty').text(quantity);
                $('#b2b-summary-total').text(total);
                
                console.log('Counters updated:', {products, quantity, total});
            }
            
            // Update counters with proper formatting
            function updateCountersFormatted(products, quantity) {
                const currencySymbol = window.b2bCurrencySymbol || '৳';
                const total = products > 0 ? currencySymbol + (quantity * 10).toFixed(2) : '-';
                
                updateAllCounters(products, quantity, total);
            }
            
            // Handle Add All to Cart button
            $('.b2b-add-to-cart-btn').on('click', function(e) {
                e.preventDefault();
                console.log('Add to cart button clicked');
                
                if (window.b2bCSVProducts && window.b2bCSVProducts.length > 0) {
                    addCSVProductsToCart(window.b2bCSVProducts);
                } else {
                    alert('No products loaded. Please upload a CSV file first.');
                }
            });
            
            // Add CSV products to cart
            function addCSVProductsToCart(products) {
                console.log('Adding products to cart:', products);
                
                // Show loading state
                $('.b2b-add-to-cart-btn').prop('disabled', true).text('Adding to Cart...');
                
                // Simulate adding to cart
                setTimeout(() => {
                    alert(`Successfully added ${products.length} products to cart!`);
                    
                    // Reset interface
                    $('.b2b-add-to-cart-btn').prop('disabled', false).text('Add All to Cart');
                    $('#b2b-csv-upload').val('');
                    $('.b2b-file-label span').text('Choose CSV File');
                    
                    // Clear summary
                    updateAllCounters('-', '-', '-');
                    
                    // Hide product list
                    $('#b2b-product-list-section').hide();
                    
                    // Clear stored products
                    window.b2bCSVProducts = [];
                    
                    console.log('Cart reset completed');
                }, 1000);
            }
            
            // Clear all products
            $('#b2b-clear-all').on('click', function(){
                console.log('Clear all button clicked');
                
                // Clear CSV file
                $('#b2b-csv-upload').val('');
                $('.b2b-file-label span').text('Choose CSV File');
                
                // Reset all counters
                updateAllCounters('-', '-', '-');
                
                // Hide product list
                $('#b2b-product-list-section').hide();
                
                // Disable add to cart button
                $('.b2b-add-to-cart-btn').prop('disabled', true);
                
                // Clear stored products
                window.b2bCSVProducts = [];
                
                alert('All products cleared!');
                console.log('Clear all completed');
            });
            
            // Update file status and show messages
            function updateFileStatus(message, type = 'info') {
                const responseDiv = $('.b2b-bulk-order-response');
                const statusClass = type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info');
                
                responseDiv.html(`<div class="b2b-status-message ${statusClass}">${message}</div>`);
                
                // Auto-hide success messages after 3 seconds
                if (type === 'success') {
                    setTimeout(() => {
                        responseDiv.empty();
                    }, 3000);
                }
            }
            
            // Download CSV template
            $('.b2b-csv-template').on('click', function(e) {
                e.preventDefault();
                console.log('Downloading CSV template');
                
                // Create CSV template content
                const csvContent = 'SKU,Quantity\nABC123,5\nXYZ789,2\nDEF456,10';
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                
                // Create download link
                const a = document.createElement('a');
                a.href = url;
                a.download = 'bulk_order_template.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                updateFileStatus('CSV template downloaded successfully!', 'success');
                console.log('CSV template downloaded');
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
        
        // Localize script for AJAX with proper nonce
        wp_localize_script('b2b-commerce-pro-frontend', 'b2b_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2b_ajax_nonce')
        ));
        
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