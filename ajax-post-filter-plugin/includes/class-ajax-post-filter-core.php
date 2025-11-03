<?php
/**
 * The core plugin class
 *
 * @package Ajax_Post_Filter
 * @author  Ahmed haj abed
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Post_Filter_Core {
    
    protected $admin;
    protected $public;
    protected $ajax_handler;
    
    public function __construct() {
        $this->load_dependencies();
    }
    
    private function load_dependencies() {
        $this->admin = new Ajax_Post_Filter_Admin();
        $this->public = new Ajax_Post_Filter_Public();
        $this->ajax_handler = new Ajax_Post_Filter_Ajax_Handler();
    }
    
    public function run() {
        // Admin hooks
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_admin_assets'));
        
        // Public hooks
        add_action('wp_enqueue_scripts', array($this->public, 'enqueue_public_assets'));
        add_shortcode('ajax_post_filter', array($this->public, 'shortcode_handler'));
        
        // AJAX hooks
        add_action('wp_ajax_filter_posts', array($this->ajax_handler, 'filter_posts'));
        add_action('wp_ajax_nopriv_filter_posts', array($this->ajax_handler, 'filter_posts'));
        
        // SEO hooks
        add_action('wp_head', array($this->public, 'add_seo_meta'));
        
        // Custom CSS
        add_action('wp_head', array($this->admin, 'output_custom_css'));
        
        // Elementor (only register if Elementor is loaded)
        if (did_action('elementor/loaded')) {
            add_action('elementor/widgets/register', array($this, 'register_elementor_widget'));
        }
    }
    
    public function register_elementor_widget($widgets_manager) {
        if (class_exists('Ajax_Post_Filter_Elementor_Widget')) {
            $widgets_manager->register(new Ajax_Post_Filter_Elementor_Widget());
        }
    }
}
