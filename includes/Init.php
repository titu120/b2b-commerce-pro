<?php
namespace B2B;

if ( ! defined( 'ABSPATH' ) ) exit;

class Init {
    private static $instance = null;
    private $admin_panel;
    private $user_manager;
    private $pricing_manager;
    private $product_manager;
    private $frontend;
    private $advanced_features;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        $this->load_modules();
    }

    private function define_constants() {
        // Additional constants can be defined here
    }

    private function includes() {
        // Load core classes
        require_once B2B_COMMERCE_PRO_PATH . 'includes/AdminPanel.php';
        require_once B2B_COMMERCE_PRO_PATH . 'includes/UserManager.php';
        require_once B2B_COMMERCE_PRO_PATH . 'includes/PricingManager.php';
        require_once B2B_COMMERCE_PRO_PATH . 'includes/ProductManager.php';
        require_once B2B_COMMERCE_PRO_PATH . 'includes/Frontend.php';
        require_once B2B_COMMERCE_PRO_PATH . 'includes/AdvancedFeatures.php';
        require_once B2B_COMMERCE_PRO_PATH . 'includes/Reporting.php';
    }

    private function init_hooks() {
        // Register hooks, actions, filters
    }

    private function load_modules() {
        $this->admin_panel = new AdminPanel();
        $this->user_manager = new UserManager();
        $this->pricing_manager = new PricingManager();
        $this->product_manager = new ProductManager();
        $this->frontend = new Frontend();
        $this->advanced_features = new AdvancedFeatures();
        $this->reporting = new Reporting();
    }
} 