<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class UserManager {
    public function __construct() {
        // Register hooks
        register_activation_hook( B2B_COMMERCE_PRO_BASENAME, [ __CLASS__, 'add_roles' ] );
        register_deactivation_hook( B2B_COMMERCE_PRO_BASENAME, [ __CLASS__, 'remove_roles' ] );

        add_action( 'init', [ $this, 'register_roles' ] );
        add_action( 'init', [ $this, 'register_group_taxonomy' ] );
        add_action( 'register_form', [ $this, 'registration_form' ] );
        add_action( 'user_register', [ $this, 'save_registration_fields' ] );
        add_action( 'admin_menu', [ $this, 'add_approval_menu' ] );
        add_action( 'admin_menu', [ $this, 'add_group_menu' ] );
        add_action( 'admin_menu', [ $this, 'add_import_export_menu' ] );
        add_action( 'admin_post_b2b_approve_user', [ $this, 'approve_user' ] );
        add_action( 'admin_post_b2b_reject_user', [ $this, 'reject_user' ] );
        add_action( 'show_user_profile', [ $this, 'user_group_field' ] );
        add_action( 'edit_user_profile', [ $this, 'user_group_field' ] );
        add_action( 'personal_options_update', [ $this, 'save_user_group_field' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_user_group_field' ] );
    }

    // Register custom roles
    public static function add_roles() {
        add_role( 'b2b_customer', 'B2B Customer', [ 'read' => true ] );
        add_role( 'wholesale_customer', 'Wholesale Customer', [ 'read' => true ] );
        add_role( 'distributor', 'Distributor', [ 'read' => true ] );
        add_role( 'retailer', 'Retailer', [ 'read' => true ] );
    }

    // Remove custom roles
    public static function remove_roles() {
        remove_role( 'b2b_customer' );
        remove_role( 'wholesale_customer' );
        remove_role( 'distributor' );
        remove_role( 'retailer' );
    }

    // Ensure roles exist on init
    public function register_roles() {
        self::add_roles();
    }

    // Register custom taxonomy for user groups
    public function register_group_taxonomy() {
        register_taxonomy(
            'b2b_user_group',
            'user',
            [
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'labels' => [
                    'name' => 'Customer Groups',
                    'singular_name' => 'Customer Group',
                    'add_new_item' => 'Add New Group',
                    'edit_item' => 'Edit Group',
                    'search_items' => 'Search Groups',
                ],
                'rewrite' => false,
                'capabilities' => [
                    'manage_terms' => 'manage_options',
                    'edit_terms' => 'manage_options',
                    'delete_terms' => 'manage_options',
                    'assign_terms' => 'edit_users',
                ],
            ]
        );
    }

    // Add group management to admin menu
    public function add_group_menu() {
        add_users_page( 'Customer Groups', 'Customer Groups', 'manage_options', 'edit-tags.php?taxonomy=b2b_user_group&post_type=user' );
    }

    // Add advanced registration fields
    public function registration_form() {
        ?>
        <p>
            <label for="company_name">Company Name<br/>
                <input type="text" name="company_name" id="company_name" class="input" value="<?php echo esc_attr( $_POST['company_name'] ?? '' ); ?>" size="25" /></label>
        </p>
        <p>
            <label for="business_type">Business Type<br/>
                <input type="text" name="business_type" id="business_type" class="input" value="<?php echo esc_attr( $_POST['business_type'] ?? '' ); ?>" size="25" /></label>
        </p>
        <p>
            <label for="tax_id">Tax ID<br/>
                <input type="text" name="tax_id" id="tax_id" class="input" value="<?php echo esc_attr( $_POST['tax_id'] ?? '' ); ?>" size="25" /></label>
        </p>
        <p>
            <label for="user_role">Register as<br/>
                <select name="user_role" id="user_role">
                    <option value="b2b_customer">B2B Customer</option>
                    <option value="wholesale_customer">Wholesale Customer</option>
                    <option value="distributor">Distributor</option>
                    <option value="retailer">Retailer</option>
                </select>
            </label>
        </p>
        <?php
    }

    // Save registration fields
    public function save_registration_fields( $user_id ) {
        if ( isset( $_POST['company_name'] ) ) {
            update_user_meta( $user_id, 'company_name', sanitize_text_field( $_POST['company_name'] ) );
        }
        if ( isset( $_POST['business_type'] ) ) {
            update_user_meta( $user_id, 'business_type', sanitize_text_field( $_POST['business_type'] ) );
        }
        if ( isset( $_POST['tax_id'] ) ) {
            update_user_meta( $user_id, 'tax_id', sanitize_text_field( $_POST['tax_id'] ) );
        }
        if ( isset( $_POST['user_role'] ) ) {
            $role = sanitize_text_field( $_POST['user_role'] );
            $user = get_userdata( $user_id );
            $user->set_role( $role );
            // Set approval status to pending
            update_user_meta( $user_id, 'b2b_approval_status', 'pending' );
        }
    }

    // Add approval menu in admin
    public function add_approval_menu() {
        add_users_page( 'B2B Approvals', 'B2B Approvals', 'manage_options', 'b2b-approvals', [ $this, 'approval_page' ] );
    }

    // Render approval page
    public function approval_page() {
        $pending_users = get_users( [
            'meta_key' => 'b2b_approval_status',
            'meta_value' => 'pending',
        ] );
        echo '<div class="wrap"><h1>B2B User Approvals</h1>';
        if ( empty( $pending_users ) ) {
            echo '<p>No pending users.</p></div>';
            return;
        }
        echo '<table class="widefat"><thead><tr><th>User</th><th>Company</th><th>Role</th><th>Actions</th></tr></thead><tbody>';
        foreach ( $pending_users as $user ) {
            echo '<tr>';
            echo '<td>' . esc_html( $user->user_login ) . '</td>';
            echo '<td>' . esc_html( get_user_meta( $user->ID, 'company_name', true ) ) . '</td>';
            echo '<td>' . esc_html( implode( ', ', $user->roles ) ) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url( admin_url( 'admin-post.php?action=b2b_approve_user&user_id=' . $user->ID ) ) . '" class="button">Approve</a> ';
            echo '<a href="' . esc_url( admin_url( 'admin-post.php?action=b2b_reject_user&user_id=' . $user->ID ) ) . '" class="button">Reject</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    // Approve user
    public function approve_user() {
        if ( ! current_user_can( 'manage_options' ) || empty( $_GET['user_id'] ) ) {
            wp_die( 'Unauthorized' );
        }
        $user_id = intval( $_GET['user_id'] );
        update_user_meta( $user_id, 'b2b_approval_status', 'approved' );
        // Send approval email
        wp_mail( get_userdata( $user_id )->user_email, 'Your B2B Account Approved', 'Congratulations! Your account has been approved.' );
        wp_redirect( admin_url( 'users.php?page=b2b-approvals' ) );
        exit;
    }

    // Reject user
    public function reject_user() {
        if ( ! current_user_can( 'manage_options' ) || empty( $_GET['user_id'] ) ) {
            wp_die( 'Unauthorized' );
        }
        $user_id = intval( $_GET['user_id'] );
        update_user_meta( $user_id, 'b2b_approval_status', 'rejected' );
        // Send rejection email
        wp_mail( get_userdata( $user_id )->user_email, 'Your B2B Account Rejected', 'Sorry, your account has been rejected.' );
        wp_redirect( admin_url( 'users.php?page=b2b-approvals' ) );
        exit;
    }

    // Show group field on user profile
    public function user_group_field( $user ) {
        $groups = get_terms( [ 'taxonomy' => 'b2b_user_group', 'hide_empty' => false ] );
        $user_groups = wp_get_object_terms( $user->ID, 'b2b_user_group', [ 'fields' => 'ids' ] );
        ?>
        <h3>Customer Group</h3>
        <table class="form-table">
            <tr>
                <th><label for="b2b_user_group">Group</label></th>
                <td>
                    <select name="b2b_user_group" id="b2b_user_group">
                        <option value="">None</option>
                        <?php foreach ( $groups as $group ) : ?>
                            <option value="<?php echo esc_attr( $group->term_id ); ?>" <?php selected( in_array( $group->term_id, $user_groups ) ); ?>><?php echo esc_html( $group->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    // Save group field from user profile
    public function save_user_group_field( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        $group = isset( $_POST['b2b_user_group'] ) ? intval( $_POST['b2b_user_group'] ) : '';
        wp_set_object_terms( $user_id, $group ? [ $group ] : [], 'b2b_user_group', false );
    }

    // Add import/export menu
    public function add_import_export_menu() {
        add_users_page( 'Import/Export Users', 'Import/Export Users', 'manage_options', 'b2b-import-export', [ $this, 'import_export_page' ] );
    }

    // Render import/export page
    public function import_export_page() {
        echo '<div class="wrap"><h1>Bulk User Import/Export</h1>';
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<h2>Export Users</h2>';
        echo '<input type="submit" name="b2b_export_users" class="button button-primary" value="Export CSV">';
        echo '</form>';
        echo '<form method="post" enctype="multipart/form-data">';
        echo '<h2>Import Users</h2>';
        echo '<input type="file" name="b2b_import_file" accept=".csv">';
        echo '<input type="submit" name="b2b_import_users" class="button button-primary" value="Import CSV">';
        echo '</form></div>';

        // Handle export
        if ( isset( $_POST['b2b_export_users'] ) ) {
            $this->export_users_csv();
        }
        // Handle import
        if ( isset( $_POST['b2b_import_users'] ) && ! empty( $_FILES['b2b_import_file']['tmp_name'] ) ) {
            $this->import_users_csv( $_FILES['b2b_import_file']['tmp_name'] );
        }
    }

    // Export users to CSV
    public function export_users_csv() {
        $users = get_users();
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="b2b-users-export.csv"' );
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, [ 'user_email', 'user_role', 'company', 'group', 'approved' ] );
        foreach ( $users as $user ) {
            $group = wp_get_object_terms( $user->ID, 'b2b_user_group', [ 'fields' => 'names' ] );
            fputcsv( $output, [
                $user->user_email,
                implode( ',', $user->roles ),
                get_user_meta( $user->ID, 'company_name', true ),
                implode( ',', $group ),
                get_user_meta( $user->ID, 'b2b_approval_status', true ),
            ] );
        }
        fclose( $output );
        exit;
    }

    // Import users from CSV
    public function import_users_csv( $file ) {
        $handle = fopen( $file, 'r' );
        if ( ! $handle ) return;
        $header = fgetcsv( $handle );
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $data = array_combine( $header, $row );
            $user = get_user_by( 'email', $data['user_email'] );
            if ( ! $user ) {
                $user_id = wp_create_user( $data['user_email'], wp_generate_password(), $data['user_email'] );
            } else {
                $user_id = $user->ID;
            }
            if ( ! empty( $data['user_role'] ) ) {
                $user_obj = get_userdata( $user_id );
                $user_obj->set_role( $data['user_role'] );
            }
            if ( ! empty( $data['company'] ) ) {
                update_user_meta( $user_id, 'company_name', $data['company'] );
            }
            if ( ! empty( $data['group'] ) ) {
                $group = get_term_by( 'name', $data['group'], 'b2b_user_group' );
                if ( $group ) {
                    wp_set_object_terms( $user_id, [ $group->term_id ], 'b2b_user_group', false );
                }
            }
            if ( ! empty( $data['approved'] ) ) {
                update_user_meta( $user_id, 'b2b_approval_status', $data['approved'] );
            }
        }
        fclose( $handle );
        echo '<div class="notice notice-success"><p>Users imported successfully.</p></div>';
    }

    // Placeholder for group management
    public function group_management() {}
    // Placeholder for bulk import/export
    public function bulk_import_export() {}
    // Placeholder for email notifications
    public function email_notifications() {}
} 