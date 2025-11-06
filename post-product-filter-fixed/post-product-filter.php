<?php
/**
 * Plugin Name: Post/Product Filter - Security Hardened
 * Plugin URI: https://example.com/post-product-filter
 * Description: Advanced AJAX blog post and WooCommerce product filter with lazy loading, multiple pagination types, SEO optimization, and full customization options. Security hardened version with all vulnerabilities fixed.
 * Version: 1.1.0
 * Author: Ahmed haj abed
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: post-product-filter
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Security: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
define('POST_PRODUCT_FILTER_VERSION', '1.1.0');

// Plugin path
define('POST_PRODUCT_FILTER_PATH', plugin_dir_path(__FILE__));

// Plugin URL
define('POST_PRODUCT_FILTER_URL', plugin_dir_url(__FILE__));

// Security: Define unique nonce action constants for each operation
define('POST_PRODUCT_FILTER_AJAX_FILTER_NONCE', 'ppf_ajax_filter_posts');
define('POST_PRODUCT_FILTER_AJAX_GET_DATA_NONCE', 'ppf_ajax_get_preset_data');
define('POST_PRODUCT_FILTER_ADMIN_NONCE', 'ppf_admin_general');
define('POST_PRODUCT_FILTER_SAVE_NONCE', 'ppf_save_preset_action');
define('POST_PRODUCT_FILTER_DELETE_NONCE', 'ppf_delete_preset_action');
define('POST_PRODUCT_FILTER_EDIT_NONCE', 'ppf_edit_preset_action');

/**
 * The core plugin class
 */
require_once POST_PRODUCT_FILTER_PATH . 'includes/class-post-product-filter-core.php';

/**
 * Admin class
 */
require_once POST_PRODUCT_FILTER_PATH . 'admin/class-post-product-filter-admin.php';

/**
 * Public class
 */
require_once POST_PRODUCT_FILTER_PATH . 'public/class-post-product-filter-public.php';

/**
 * AJAX handler
 */
require_once POST_PRODUCT_FILTER_PATH . 'includes/class-post-product-filter-ajax-handler.php';

/**
 * Helper functions
 */
require_once POST_PRODUCT_FILTER_PATH . 'includes/helper-functions.php';

/**
 * Initialize the plugin
 */
function post_product_filter_init() {
    $plugin = new Post_Product_Filter_Core();
    $plugin->run();
}
add_action('plugins_loaded', 'post_product_filter_init');

/**
 * Activation hook
 */
function post_product_filter_activate() {
    // Create empty presets array
    add_option('post_product_filter_presets', array());
    
    // Add option to track if database should be deleted on uninstall
    add_option('post_product_filter_delete_on_uninstall', false);
    
    // Create security log table for tracking
    global $wpdb;
    $table_name = $wpdb->prefix . 'ppf_security_log';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        event_type varchar(50) NOT NULL,
        user_id bigint(20) DEFAULT NULL,
        ip_address varchar(45) NOT NULL,
        details text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY ip_address (ip_address),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'post_product_filter_activate');

/**
 * Deactivation hook
 */
function post_product_filter_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'post_product_filter_deactivate');

/**
 * Add security headers
 */
function post_product_filter_security_headers() {
    if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
        return;
    }
    
    // Only on pages with our shortcode
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'post_product_filter')) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
add_action('send_headers', 'post_product_filter_security_headers');
