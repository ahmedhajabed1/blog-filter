<?php
/**
 * Fired when the plugin is uninstalled.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!defined('ABSPATH')) {
    exit;
}

$delete_data = get_option('post_product_filter_delete_on_uninstall', false);

if ($delete_data) {
    // Delete options
    delete_option('post_product_filter_presets');
    delete_option('post_product_filter_delete_on_uninstall');
    
    // Delete security log table
    global $wpdb;
    $table_name = $wpdb->prefix . 'ppf_security_log';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
