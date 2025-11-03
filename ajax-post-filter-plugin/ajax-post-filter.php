<?php
/**
 * Plugin Name: AJAX Post Filter
 * Plugin URI: https://example.com/ajax-post-filter
 * Description: Advanced AJAX blog post filter with Elementor support, lazy loading, multiple pagination types, SEO optimization, and full customization options. Created by Ahmed haj abed.
 * Version: 1.0.0
 * Author: Ahmed haj abed
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ajax-post-filter
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('AJAX_POST_FILTER_VERSION', '1.0.0');

// Plugin path
define('AJAX_POST_FILTER_PATH', plugin_dir_path(__FILE__));

// Plugin URL
define('AJAX_POST_FILTER_URL', plugin_dir_url(__FILE__));

/**
 * The core plugin class
 */
require_once AJAX_POST_FILTER_PATH . 'includes/class-ajax-post-filter-core.php';

/**
 * Admin class
 */
require_once AJAX_POST_FILTER_PATH . 'admin/class-ajax-post-filter-admin.php';

/**
 * Public class
 */
require_once AJAX_POST_FILTER_PATH . 'public/class-ajax-post-filter-public.php';

/**
 * AJAX handler
 */
require_once AJAX_POST_FILTER_PATH . 'includes/class-ajax-post-filter-ajax-handler.php';

/**
 * Elementor widget (only if Elementor is active)
 */
if (did_action('elementor/loaded')) {
    require_once AJAX_POST_FILTER_PATH . 'includes/class-ajax-post-filter-elementor.php';
}

/**
 * Initialize the plugin
 */
function ajax_post_filter_init() {
    $plugin = new Ajax_Post_Filter_Core();
    $plugin->run();
}
add_action('plugins_loaded', 'ajax_post_filter_init');

/**
 * Activation hook
 */
function ajax_post_filter_activate() {
    // Create default preset
    $default_preset = array(
        'default-preset' => array(
            'name' => 'Default preset',
            'slug' => 'default-preset',
            'settings' => array(
                'posts_per_page' => 9,
                'pagination_type' => 'pagination',
                'lazy_load' => true,
                'show_search' => true,
                'show_count' => true,
                'layout' => 'sidebar',
                'selected_categories' => array()
            )
        )
    );
    
    add_option('ajax_post_filter_presets', $default_preset);
    add_option('ajax_post_filter_default_posts_per_page', 9);
    add_option('ajax_post_filter_animation_speed', 'normal');
    add_option('ajax_post_filter_enable_ajax', true);
    add_option('ajax_post_filter_auto_apply', false);
    add_option('ajax_post_filter_primary_color', '#2271b1');
    add_option('ajax_post_filter_button_color', '#2271b1');
    add_option('ajax_post_filter_button_text_color', '#ffffff');
    add_option('ajax_post_filter_button_hover_color', '#135e96');
    add_option('ajax_post_filter_button_style', 'rounded');
    add_option('ajax_post_filter_apply_button_text', 'Apply Filters');
    add_option('ajax_post_filter_reset_button_text', 'Reset');
    add_option('ajax_post_filter_load_more_text', 'Load More');
    add_option('ajax_post_filter_loading_text', 'Loading...');
    add_option('ajax_post_filter_read_more_text', 'Read More');
    
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ajax_post_filter_activate');

/**
 * Deactivation hook
 */
function ajax_post_filter_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ajax_post_filter_deactivate');
