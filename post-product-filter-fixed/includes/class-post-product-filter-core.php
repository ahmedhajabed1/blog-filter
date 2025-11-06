<?php
/**
 * Core Plugin Class - SECURITY HARDENED v1.1.0
 * Coordinates all plugin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Post_Product_Filter_Core {
    
    private $admin;
    private $public;
    private $ajax_handler;
    
    public function __construct() {
        // Initialize components
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Already loaded in main plugin file
    }
    
    private function init_hooks() {
        // Admin hooks
        if (is_admin()) {
            $this->admin = new Post_Product_Filter_Admin();
            add_action('admin_menu', array($this->admin, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_admin_assets'));
            add_action('admin_post_save_post_product_filter_preset', array($this->admin, 'handle_save_preset'));
            add_action('admin_init', array($this->admin, 'handle_delete_preset'));
        }
        
        // Public hooks
        $this->public = new Post_Product_Filter_Public();
        add_action('wp_enqueue_scripts', array($this->public, 'enqueue_public_assets'));
        add_shortcode('post_product_filter', array($this->public, 'shortcode_handler'));
        
        // AJAX hooks
        $this->ajax_handler = new Post_Product_Filter_Ajax_Handler();
        add_action('wp_ajax_filter_posts', array($this->ajax_handler, 'filter_posts'));
        add_action('wp_ajax_nopriv_filter_posts', array($this->ajax_handler, 'filter_posts'));
        
        // Custom CSS output
        add_action('wp_head', 'post_product_filter_custom_css', 100);
        
        // SEO meta tags
        add_action('wp_head', array($this->public, 'add_seo_meta'));
        
        // Elementor integration - FIXED INITIALIZATION
        add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'add_elementor_widget_categories'));
    }
    
    /**
     * FIXED: Properly register Elementor widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        if (!class_exists('Post_Product_Filter_Elementor_Widget')) {
            require_once POST_PRODUCT_FILTER_PATH . 'includes/class-post-product-filter-elementor.php';
        }
        
        $widgets_manager->register(new Post_Product_Filter_Elementor_Widget());
    }
    
    /**
     * Add custom Elementor category
     */
    public function add_elementor_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'post-product-filter',
            array(
                'title' => __('Post/Product Filter', 'post-product-filter'),
                'icon' => 'fa fa-filter',
            )
        );
    }
    
    public function run() {
        // Plugin is running
        do_action('post_product_filter_loaded');
    }
}
