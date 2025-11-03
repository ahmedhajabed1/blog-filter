<?php
/**
 * AJAX Blog Post Filter - Admin Backend
 * 
 * Author: Ahmed haj abed
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Ajax_Post_Filter_Admin {
    
    public function __construct() {
        // Constructor
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Ajax Post Filter',
            'Post Filter',
            'manage_options',
            'ajax-post-filter',
            array($this, 'presets_page'),
            'dashicons-filter',
            30
        );
        
        add_submenu_page(
            'ajax-post-filter',
            'Filter Presets',
            'Filter Presets',
            'manage_options',
            'ajax-post-filter',
            array($this, 'presets_page')
        );
        
        add_submenu_page(
            'ajax-post-filter',
            'General Options',
            'General Options',
            'manage_options',
            'ajax-post-filter-general',
            array($this, 'general_page')
        );
        
        add_submenu_page(
            'ajax-post-filter',
            'Customization',
            'Customization',
            'manage_options',
            'ajax-post-filter-customization',
            array($this, 'customization_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ajax-post-filter') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ajax-post-filter-admin-style',
            AJAX_POST_FILTER_URL . 'admin/css/ajax-post-filter-admin.css',
            array(),
            AJAX_POST_FILTER_VERSION
        );
        
        wp_enqueue_script(
            'ajax-post-filter-admin-script',
            AJAX_POST_FILTER_URL . 'admin/js/ajax-post-filter-admin.js',
            array('jquery'),
            AJAX_POST_FILTER_VERSION,
            true
        );
        
        wp_localize_script('ajax-post-filter-admin-script', 'ajaxPostFilterAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('post_filter_admin_nonce')
        ));
    }
    
    public function presets_page() {
        ajax_post_filter_presets_page();
    }
    
    public function general_page() {
        ajax_post_filter_general_page();
    }
    
    public function customization_page() {
        ajax_post_filter_customization_page();
    }
    
    public function output_custom_css() {
        ajax_post_filter_custom_css();
    }
}

// Keep all the existing functions below


// Filter Presets Page
function ajax_post_filter_presets_page() {
    // Handle preset deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['preset_id'])) {
        check_admin_referer('delete_preset_' . $_GET['preset_id']);
        ajax_post_filter_delete_preset($_GET['preset_id']);
        echo '<div class="notice notice-success is-dismissible"><p>Preset deleted successfully!</p></div>';
    }
    
    // Handle preset save
    if (isset($_POST['save_preset'])) {
        check_admin_referer('ajax_post_filter_save_preset');
        ajax_post_filter_save_preset();
        echo '<div class="notice notice-success is-dismissible"><p>Preset saved successfully!</p></div>';
    }
    
    $presets = get_option('ajax_post_filter_presets', array());
    if (empty($presets)) {
        // Create default preset
        $presets = array(
            'default-preset' => array(
                'name' => 'Default preset',
                'slug' => 'default-preset',
                'settings' => array(
                    'posts_per_page' => 9,
                    'show_search' => true,
                    'show_count' => true,
                    'layout' => 'sidebar'
                )
            )
        );
        update_option('ajax_post_filter_presets', $presets);
    }
    ?>
    
    <div class="ajax-post-filter-admin-wrapper">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2>Ajax Post Filter</h2>
                <span class="version">v1.0.0</span>
            </div>
            
            <nav class="admin-nav">
                <a href="?page=ajax-post-filter" class="nav-item active">
                    <span class="dashicons dashicons-filter"></span>
                    Filter presets
                </a>
                <a href="?page=ajax-post-filter-general" class="nav-item">
                    <span class="dashicons dashicons-admin-settings"></span>
                    General options
                </a>
                <a href="?page=ajax-post-filter-customization" class="nav-item">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    Customization
                </a>
                <a href="#" class="nav-item" id="collapse-sidebar">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Collapse
                </a>
            </nav>
            
            <div class="admin-footer">
                <p>Developed by<br><strong>Ahmed haj abed</strong></p>
            </div>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Filter presets</h1>
                <button type="button" class="button button-primary" id="add-preset-btn">
                    <span class="dashicons dashicons-plus-alt"></span> Add preset
                </button>
            </div>
            
            <p class="description">The list of all filter sets created and configured for your site.</p>
            
            <div class="presets-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Preset name</th>
                            <th>Shortcode</th>
                            <th class="column-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presets as $slug => $preset) : ?>
                        <tr>
                            <td class="preset-name">
                                <strong><?php echo esc_html($preset['name']); ?></strong>
                            </td>
                            <td class="preset-shortcode">
                                <code class="shortcode-text">[ajax_post_filter slug="<?php echo esc_attr($slug); ?>"]</code>
                                <button type="button" class="button button-small copy-shortcode-btn" data-shortcode='[ajax_post_filter slug="<?php echo esc_attr($slug); ?>"]'>
                                    <span class="dashicons dashicons-clipboard"></span> Copy
                                </button>
                            </td>
                            <td class="column-actions">
                                <div class="preset-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <button type="button" class="button edit-preset-btn" data-preset="<?php echo esc_attr($slug); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <?php if ($slug !== 'default-preset') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ajax-post-filter&action=delete&preset_id=' . $slug), 'delete_preset_' . $slug); ?>" 
                                   class="button delete-preset-btn" 
                                   onclick="return confirm('Are you sure you want to delete this preset?');">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="admin-actions">
                <button type="submit" class="button button-primary button-large">Save Options</button>
                <button type="button" class="button button-large">Reset Defaults</button>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Preset Modal -->
    <div id="preset-modal" class="preset-modal" style="display: none;">
        <div class="preset-modal-content">
            <div class="preset-modal-header">
                <h2 id="modal-title">Add New Preset</h2>
                <button type="button" class="close-modal">&times;</button>
            </div>
            
            <form id="preset-form" method="post">
                <?php wp_nonce_field('ajax_post_filter_save_preset'); ?>
                <input type="hidden" name="save_preset" value="1">
                <input type="hidden" name="preset_slug" id="preset_slug" value="">
                
                <div class="form-group">
                    <label for="preset_name">Preset Name *</label>
                    <input type="text" id="preset_name" name="preset_name" class="regular-text" required>
                </div>
                
                <div class="form-group">
                    <label for="posts_per_page">Posts Per Page</label>
                    <input type="number" id="posts_per_page" name="posts_per_page" value="9" min="1" max="50">
                </div>
                
                <div class="form-group">
                    <label for="pagination_type">Pagination Type</label>
                    <select id="pagination_type" name="pagination_type">
                        <option value="pagination">Standard Pagination</option>
                        <option value="load_more">Load More Button</option>
                        <option value="infinite">Infinite Scroll</option>
                    </select>
                    <p class="description">Choose how users navigate through posts</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="lazy_load" id="lazy_load" checked>
                        Enable lazy loading for images
                    </label>
                    <p class="description">Images load only when visible (improves performance)</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="show_search" id="show_search" checked>
                        Show category search box
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="show_count" id="show_count" checked>
                        Show post count
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="layout">Layout</label>
                    <select id="layout" name="layout">
                        <option value="sidebar">Sidebar</option>
                        <option value="horizontal">Horizontal</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="selected_categories">Select Categories (leave empty for all)</label>
                    <select id="selected_categories" name="selected_categories[]" multiple size="10" class="widefat">
                        <?php
                        $categories = get_categories(array('hide_empty' => false));
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . ' (' . $category->count . ')</option>';
                        }
                        ?>
                    </select>
                    <p class="description">Hold Ctrl/Cmd to select multiple categories</p>
                </div>
                
                <div class="preset-modal-footer">
                    <button type="submit" class="button button-primary button-large">Save Preset</button>
                    <button type="button" class="button button-large close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
}

// General Options Page
function ajax_post_filter_general_page() {
    if (isset($_POST['save_general_options'])) {
        check_admin_referer('ajax_post_filter_general_options');
        
        update_option('ajax_post_filter_default_posts_per_page', intval($_POST['default_posts_per_page']));
        update_option('ajax_post_filter_animation_speed', sanitize_text_field($_POST['animation_speed']));
        update_option('ajax_post_filter_enable_ajax', isset($_POST['enable_ajax']));
        update_option('ajax_post_filter_auto_apply', isset($_POST['auto_apply']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    $default_posts_per_page = get_option('ajax_post_filter_default_posts_per_page', 9);
    $animation_speed = get_option('ajax_post_filter_animation_speed', 'normal');
    $enable_ajax = get_option('ajax_post_filter_enable_ajax', true);
    $auto_apply = get_option('ajax_post_filter_auto_apply', false);
    ?>
    
    <div class="ajax-post-filter-admin-wrapper">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2>Ajax Post Filter</h2>
                <span class="version">v1.0.0</span>
            </div>
            
            <nav class="admin-nav">
                <a href="?page=ajax-post-filter" class="nav-item">
                    <span class="dashicons dashicons-filter"></span>
                    Filter presets
                </a>
                <a href="?page=ajax-post-filter-general" class="nav-item active">
                    <span class="dashicons dashicons-admin-settings"></span>
                    General options
                </a>
                <a href="?page=ajax-post-filter-customization" class="nav-item">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    Customization
                </a>
                <a href="#" class="nav-item" id="collapse-sidebar">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Collapse
                </a>
            </nav>
            
            <div class="admin-footer">
                <p>Developed by<br><strong>Ahmed haj abed</strong></p>
            </div>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>General Options</h1>
            </div>
            
            <form method="post" class="settings-form">
                <?php wp_nonce_field('ajax_post_filter_general_options'); ?>
                <input type="hidden" name="save_general_options" value="1">
                
                <div class="settings-section">
                    <h2>Basic Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="default_posts_per_page">Default Posts Per Page</label>
                            </th>
                            <td>
                                <input type="number" id="default_posts_per_page" name="default_posts_per_page" 
                                       value="<?php echo esc_attr($default_posts_per_page); ?>" min="1" max="50" class="small-text">
                                <p class="description">Number of posts to display per page (default: 9)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="animation_speed">Animation Speed</label>
                            </th>
                            <td>
                                <select id="animation_speed" name="animation_speed">
                                    <option value="fast" <?php selected($animation_speed, 'fast'); ?>>Fast (200ms)</option>
                                    <option value="normal" <?php selected($animation_speed, 'normal'); ?>>Normal (400ms)</option>
                                    <option value="slow" <?php selected($animation_speed, 'slow'); ?>>Slow (600ms)</option>
                                </select>
                                <p class="description">Speed of filter animations</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Enable AJAX</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_ajax" <?php checked($enable_ajax); ?>>
                                    Enable AJAX filtering (disable to use standard page reload)
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Auto-Apply Filters</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_apply" <?php checked($auto_apply); ?>>
                                    Apply filters automatically on selection (no apply button needed)
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="admin-actions">
                    <button type="submit" class="button button-primary button-large">Save Options</button>
                    <button type="button" class="button button-large">Reset Defaults</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
}

// Customization Page
function ajax_post_filter_customization_page() {
    if (isset($_POST['save_customization'])) {
        check_admin_referer('ajax_post_filter_customization');
        
        update_option('ajax_post_filter_primary_color', sanitize_hex_color($_POST['primary_color']));
        update_option('ajax_post_filter_button_style', sanitize_text_field($_POST['button_style']));
        update_option('ajax_post_filter_button_color', sanitize_hex_color($_POST['button_color']));
        update_option('ajax_post_filter_button_text_color', sanitize_hex_color($_POST['button_text_color']));
        update_option('ajax_post_filter_button_hover_color', sanitize_hex_color($_POST['button_hover_color']));
        update_option('ajax_post_filter_apply_button_text', sanitize_text_field($_POST['apply_button_text']));
        update_option('ajax_post_filter_reset_button_text', sanitize_text_field($_POST['reset_button_text']));
        update_option('ajax_post_filter_load_more_text', sanitize_text_field($_POST['load_more_text']));
        update_option('ajax_post_filter_loading_text', sanitize_text_field($_POST['loading_text']));
        update_option('ajax_post_filter_read_more_text', sanitize_text_field($_POST['read_more_text']));
        update_option('ajax_post_filter_custom_css', wp_kses_post($_POST['custom_css']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Customization saved successfully!</p></div>';
    }
    
    $primary_color = get_option('ajax_post_filter_primary_color', '#2271b1');
    $button_style = get_option('ajax_post_filter_button_style', 'rounded');
    $button_color = get_option('ajax_post_filter_button_color', '#2271b1');
    $button_text_color = get_option('ajax_post_filter_button_text_color', '#ffffff');
    $button_hover_color = get_option('ajax_post_filter_button_hover_color', '#135e96');
    $apply_button_text = get_option('ajax_post_filter_apply_button_text', 'Apply Filters');
    $reset_button_text = get_option('ajax_post_filter_reset_button_text', 'Reset');
    $load_more_text = get_option('ajax_post_filter_load_more_text', 'Load More');
    $loading_text = get_option('ajax_post_filter_loading_text', 'Loading...');
    $read_more_text = get_option('ajax_post_filter_read_more_text', 'Read More');
    $custom_css = get_option('ajax_post_filter_custom_css', '');
    ?>
    
    <div class="ajax-post-filter-admin-wrapper">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2>Ajax Post Filter</h2>
                <span class="version">v1.0.0</span>
            </div>
            
            <nav class="admin-nav">
                <a href="?page=ajax-post-filter" class="nav-item">
                    <span class="dashicons dashicons-filter"></span>
                    Filter presets
                </a>
                <a href="?page=ajax-post-filter-general" class="nav-item">
                    <span class="dashicons dashicons-admin-settings"></span>
                    General options
                </a>
                <a href="?page=ajax-post-filter-customization" class="nav-item active">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    Customization
                </a>
                <a href="#" class="nav-item" id="collapse-sidebar">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    Collapse
                </a>
            </nav>
            
            <div class="admin-footer">
                <p>Developed by<br><strong>Ahmed haj abed</strong></p>
            </div>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Customization</h1>
            </div>
            
            <form method="post" class="settings-form">
                <?php wp_nonce_field('ajax_post_filter_customization'); ?>
                <input type="hidden" name="save_customization" value="1">
                
                <div class="settings-section">
                    <h2>Color Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="primary_color">Primary Color</label>
                            </th>
                            <td>
                                <input type="color" id="primary_color" name="primary_color" 
                                       value="<?php echo esc_attr($primary_color); ?>" class="color-picker">
                                <p class="description">Main color for links and accents</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="button_color">Button Background Color</label>
                            </th>
                            <td>
                                <input type="color" id="button_color" name="button_color" 
                                       value="<?php echo esc_attr($button_color); ?>" class="color-picker">
                                <p class="description">Background color for all buttons</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="button_text_color">Button Text Color</label>
                            </th>
                            <td>
                                <input type="color" id="button_text_color" name="button_text_color" 
                                       value="<?php echo esc_attr($button_text_color); ?>" class="color-picker">
                                <p class="description">Text color for buttons</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="button_hover_color">Button Hover Color</label>
                            </th>
                            <td>
                                <input type="color" id="button_hover_color" name="button_hover_color" 
                                       value="<?php echo esc_attr($button_hover_color); ?>" class="color-picker">
                                <p class="description">Background color when hovering over buttons</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="settings-section">
                    <h2>Button Text</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="apply_button_text">Apply Button Text</label>
                            </th>
                            <td>
                                <input type="text" id="apply_button_text" name="apply_button_text" 
                                       value="<?php echo esc_attr($apply_button_text); ?>" class="regular-text">
                                <p class="description">Text for the apply filters button</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="reset_button_text">Reset Button Text</label>
                            </th>
                            <td>
                                <input type="text" id="reset_button_text" name="reset_button_text" 
                                       value="<?php echo esc_attr($reset_button_text); ?>" class="regular-text">
                                <p class="description">Text for the reset button</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="load_more_text">Load More Button Text</label>
                            </th>
                            <td>
                                <input type="text" id="load_more_text" name="load_more_text" 
                                       value="<?php echo esc_attr($load_more_text); ?>" class="regular-text">
                                <p class="description">Text for the load more button (if enabled)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="loading_text">Loading Text</label>
                            </th>
                            <td>
                                <input type="text" id="loading_text" name="loading_text" 
                                       value="<?php echo esc_attr($loading_text); ?>" class="regular-text">
                                <p class="description">Text displayed while loading content</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="read_more_text">Read More Text</label>
                            </th>
                            <td>
                                <input type="text" id="read_more_text" name="read_more_text" 
                                       value="<?php echo esc_attr($read_more_text); ?>" class="regular-text">
                                <p class="description">Text for the read more link on posts</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="settings-section">
                    <h2>Style Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="button_style">Button Style</label>
                            </th>
                            <td>
                                <select id="button_style" name="button_style">
                                    <option value="rounded" <?php selected($button_style, 'rounded'); ?>>Rounded (4px)</option>
                                    <option value="square" <?php selected($button_style, 'square'); ?>>Square (0px)</option>
                                    <option value="pill" <?php selected($button_style, 'pill'); ?>>Pill (25px)</option>
                                </select>
                                <p class="description">Border radius for buttons</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="settings-section">
                    <h2>Custom CSS</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="custom_css">Additional CSS</label>
                            </th>
                            <td>
                                <textarea id="custom_css" name="custom_css" rows="10" class="large-text code"><?php echo esc_textarea($custom_css); ?></textarea>
                                <p class="description">Add your custom CSS code here</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="admin-actions">
                    <button type="submit" class="button button-primary button-large">Save Options</button>
                    <button type="button" class="button button-large">Reset Defaults</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
}

// Save preset function
function ajax_post_filter_save_preset() {
    $presets = get_option('ajax_post_filter_presets', array());
    
    $preset_slug = sanitize_title($_POST['preset_slug']);
    if (empty($preset_slug)) {
        $preset_slug = sanitize_title($_POST['preset_name']);
    }
    
    $presets[$preset_slug] = array(
        'name' => sanitize_text_field($_POST['preset_name']),
        'slug' => $preset_slug,
        'settings' => array(
            'posts_per_page' => intval($_POST['posts_per_page']),
            'pagination_type' => sanitize_text_field($_POST['pagination_type']),
            'lazy_load' => isset($_POST['lazy_load']),
            'show_search' => isset($_POST['show_search']),
            'show_count' => isset($_POST['show_count']),
            'layout' => sanitize_text_field($_POST['layout']),
            'selected_categories' => isset($_POST['selected_categories']) ? array_map('intval', $_POST['selected_categories']) : array()
        )
    );
    
    update_option('ajax_post_filter_presets', $presets);
}

// Delete preset function
function ajax_post_filter_delete_preset($preset_slug) {
    $presets = get_option('ajax_post_filter_presets', array());
    
    if (isset($presets[$preset_slug]) && $preset_slug !== 'default-preset') {
        unset($presets[$preset_slug]);
        update_option('ajax_post_filter_presets', $presets);
    }
}

// Output custom CSS
function ajax_post_filter_custom_css() {
    $custom_css = get_option('ajax_post_filter_custom_css', '');
    $primary_color = get_option('ajax_post_filter_primary_color', '#2271b1');
    $button_color = get_option('ajax_post_filter_button_color', '#2271b1');
    $button_text_color = get_option('ajax_post_filter_button_text_color', '#ffffff');
    $button_hover_color = get_option('ajax_post_filter_button_hover_color', '#135e96');
    $button_style = get_option('ajax_post_filter_button_style', 'rounded');
    
    // Button radius based on style
    $border_radius = '4px';
    if ($button_style === 'square') {
        $border_radius = '0';
    } else if ($button_style === 'pill') {
        $border_radius = '25px';
    }
    
    if (!empty($custom_css) || $primary_color !== '#2271b1' || $button_color !== '#2271b1') {
        echo '<style type="text/css" id="ajax-post-filter-custom-styles">';
        
        // Primary color
        if ($primary_color !== '#2271b1') {
            echo '
            .post-title a:hover, 
            #category-search:focus,
            .filter-option:hover {
                color: ' . esc_attr($primary_color) . ' !important;
            }
            #category-search:focus {
                border-color: ' . esc_attr($primary_color) . ' !important;
            }
            ';
        }
        
        // Button colors
        if ($button_color !== '#2271b1' || $button_text_color !== '#ffffff') {
            echo '
            .btn-apply, 
            .read-more, 
            .load-more-btn,
            .active-filter-tag, 
            .pagination .current {
                background-color: ' . esc_attr($button_color) . ' !important;
                color: ' . esc_attr($button_text_color) . ' !important;
            }
            ';
        }
        
        // Button hover
        if ($button_hover_color !== '#135e96') {
            echo '
            .btn-apply:hover, 
            .read-more:hover, 
            .load-more-btn:hover,
            .pagination a:hover {
                background-color: ' . esc_attr($button_hover_color) . ' !important;
            }
            ';
        }
        
        // Button style
        if ($border_radius !== '4px') {
            echo '
            .btn-apply,
            .btn-reset,
            .read-more,
            .load-more-btn {
                border-radius: ' . esc_attr($border_radius) . ' !important;
            }
            ';
        }
        
        // Custom CSS
        if (!empty($custom_css)) {
            echo wp_kses_post($custom_css);
        }
        
        echo '</style>';
    }
}
