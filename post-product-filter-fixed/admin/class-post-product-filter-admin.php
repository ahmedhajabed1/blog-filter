<?php
/**
 * Admin Functionality - SECURITY HARDENED v1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Post_Product_Filter_Admin {
    
    public function add_admin_menu() {
        add_menu_page(
            __('Post/Product Filter', 'post-product-filter'),
            __('Post/Product Filter', 'post-product-filter'),
            'manage_options',
            'post-product-filter',
            array($this, 'render_admin_page'),
            'dashicons-filter',
            30
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_post-product-filter') {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        
        wp_enqueue_style(
            'post-product-filter-admin',
            POST_PRODUCT_FILTER_URL . 'admin/css/post-product-filter-admin.css',
            array(),
            POST_PRODUCT_FILTER_VERSION
        );
        
        wp_enqueue_script(
            'post-product-filter-admin',
            POST_PRODUCT_FILTER_URL . 'admin/js/post-product-filter-admin.js',
            array('jquery', 'wp-color-picker'),
            POST_PRODUCT_FILTER_VERSION,
            true
        );
    }
    
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'post-product-filter'));
        }
        
        $presets = get_option('post_product_filter_presets', array());
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $preset_slug = isset($_GET['preset_slug']) ? sanitize_key($_GET['preset_slug']) : '';
        
        if ($action === 'edit' && !empty($preset_slug) && isset($presets[$preset_slug])) {
            $this->render_edit_preset_page($presets[$preset_slug]);
        } elseif ($action === 'new') {
            $this->render_edit_preset_page();
        } else {
            $this->render_presets_list($presets);
        }
    }
    
    private function render_presets_list($presets) {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Post/Product Filter Presets', 'post-product-filter'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=post-product-filter&action=new')); ?>" class="page-title-action">
                <?php esc_html_e('Add New Preset', 'post-product-filter'); ?>
            </a>
            
            <hr class="wp-header-end">
            
            <?php if (isset($_GET['message'])) : ?>
                <?php if ($_GET['message'] === 'saved') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Preset saved successfully!', 'post-product-filter'); ?></p>
                </div>
                <?php elseif ($_GET['message'] === 'deleted') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Preset deleted successfully!', 'post-product-filter'); ?></p>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (empty($presets)) : ?>
                <div class="no-presets" style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px; margin-top: 30px;">
                    <div style="font-size: 64px; color: #ddd; margin-bottom: 20px;">🎯</div>
                    <h2 style="margin: 0 0 10px 0; color: #333;"><?php esc_html_e('No Presets Yet', 'post-product-filter'); ?></h2>
                    <p style="color: #666; margin: 0 0 20px 0;"><?php esc_html_e('Create your first filter preset to get started!', 'post-product-filter'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=post-product-filter&action=new')); ?>" class="button button-primary button-hero">
                        <?php esc_html_e('Create Your First Preset', 'post-product-filter'); ?>
                    </a>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'post-product-filter'); ?></th>
                            <th><?php esc_html_e('Slug', 'post-product-filter'); ?></th>
                            <th><?php esc_html_e('Type', 'post-product-filter'); ?></th>
                            <th><?php esc_html_e('Shortcode', 'post-product-filter'); ?></th>
                            <th><?php esc_html_e('Actions', 'post-product-filter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presets as $slug => $preset) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($preset['name']); ?></strong></td>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td>
                                <?php 
                                $type = isset($preset['settings']['preset_type']) ? $preset['settings']['preset_type'] : 'posts';
                                echo esc_html(ucfirst($type));
                                ?>
                            </td>
                            <td>
                                <input type="text" readonly value='[post_product_filter slug="<?php echo esc_attr($slug); ?>"]' class="regular-text" onclick="this.select();">
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=post-product-filter&action=edit&preset_slug=' . $slug)); ?>" class="button button-small">
                                    <?php esc_html_e('Edit', 'post-product-filter'); ?>
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=post-product-filter&action=delete&preset_slug=' . $slug), 'delete_preset_' . $slug, 'ppf_delete_nonce')); ?>" 
                                   class="button button-small button-link-delete" 
                                   onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this preset?', 'post-product-filter'); ?>');">
                                    <?php esc_html_e('Delete', 'post-product-filter'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="ppf-info-box" style="margin-top: 40px; padding: 20px; background: #fff; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #000;"><?php esc_html_e('How to Use', 'post-product-filter'); ?></h3>
                <ol style="line-height: 1.8; color: #000;">
                    <li style="color: #000;"><?php esc_html_e('Create a preset by clicking "Add New Preset"', 'post-product-filter'); ?></li>
                    <li style="color: #000;"><?php esc_html_e('Configure your filter settings in the 3 tabs (General, Display, Styling)', 'post-product-filter'); ?></li>
                    <li style="color: #000;"><?php esc_html_e('Save the preset', 'post-product-filter'); ?></li>
                    <li style="color: #000;"><?php esc_html_e('Use the shortcode on any page/post, OR', 'post-product-filter'); ?></li>
                    <li style="color: #000;"><?php esc_html_e('Use the Elementor widget and select your preset', 'post-product-filter'); ?></li>
                </ol>
                
                <p style="margin-bottom: 0; color: #000;">
                    <strong style="color: #000;"><?php esc_html_e('Version:', 'post-product-filter'); ?></strong> <span style="color: #000;"><?php echo esc_html(POST_PRODUCT_FILTER_VERSION); ?></span> | 
                    <strong style="color: #000;"><?php esc_html_e('Security Status:', 'post-product-filter'); ?></strong> <span style="color: #00a32a;">✅ Hardened</span>
                </p>
            </div>
        </div>
        <?php
    }
    
    private function render_edit_preset_page($preset = null) {
        $is_new = !$preset;
        $preset_name = $preset ? $preset['name'] : '';
        $preset_slug = $preset ? $preset['slug'] : '';
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_new ? esc_html__('Add New Preset', 'post-product-filter') : esc_html__('Edit Preset', 'post-product-filter'); ?></h1>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ppf-preset-form">
                <?php wp_nonce_field(POST_PRODUCT_FILTER_SAVE_NONCE, '_wpnonce'); ?>
                <input type="hidden" name="action" value="save_post_product_filter_preset">
                <?php if (!$is_new) : ?>
                <input type="hidden" name="preset_slug" value="<?php echo esc_attr($preset_slug); ?>">
                <?php endif; ?>
                
                <div class="ppf-preset-header">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="preset_name"><?php esc_html_e('Preset Name', 'post-product-filter'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       name="preset_name" 
                                       id="preset_name" 
                                       value="<?php echo esc_attr($preset_name); ?>" 
                                       class="regular-text" 
                                       required
                                       maxlength="100">
                                <p class="description"><?php esc_html_e('A descriptive name for this filter preset.', 'post-product-filter'); ?></p>
                            </td>
                        </tr>
                        
                        <?php if ($is_new) : ?>
                        <tr>
                            <th scope="row">
                                <label for="preset_slug_field"><?php esc_html_e('Preset Slug', 'post-product-filter'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       name="preset_slug_field" 
                                       id="preset_slug_field" 
                                       value="<?php echo esc_attr($preset_slug); ?>" 
                                       class="regular-text" 
                                       pattern="[a-z0-9\-]+" 
                                       maxlength="50"
                                       placeholder="<?php esc_attr_e('e.g., my-filter-preset', 'post-product-filter'); ?>"
                                       required>
                                <button type="button" id="generate-slug-btn" class="button button-secondary" style="margin-left: 10px;">
                                    <?php esc_html_e('Auto-Generate from Name', 'post-product-filter'); ?>
                                </button>
                                <p class="description">
                                    <?php esc_html_e('Unique identifier used in shortcodes. Only lowercase letters, numbers, and hyphens allowed. Example: my-blog-filter', 'post-product-filter'); ?>
                                    <br>
                                    <strong><?php esc_html_e('Shortcode:', 'post-product-filter'); ?></strong> 
                                    <code>[post_product_filter slug="<span id="slug-preview">your-slug</span>"]</code>
                                </p>
                            </td>
                        </tr>
                        <?php else : ?>
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Preset Slug', 'post-product-filter'); ?></label>
                            </th>
                            <td>
                                <input type="text" value="<?php echo esc_attr($preset_slug); ?>" class="regular-text" disabled style="background: #f0f0f0;">
                                <p class="description">
                                    <?php esc_html_e('Slug cannot be changed after creation to prevent breaking existing shortcodes.', 'post-product-filter'); ?>
                                    <br>
                                    <strong><?php esc_html_e('Your shortcode:', 'post-product-filter'); ?></strong> 
                                    <input type="text" value='[post_product_filter slug="<?php echo esc_attr($preset_slug); ?>"]' readonly onclick="this.select();" class="regular-text" style="margin-top: 5px;">
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <div class="ppf-tabs-wrapper">
                    <h2 class="nav-tab-wrapper">
                        <a href="#tab-general" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'post-product-filter'); ?></a>
                        <a href="#tab-display" class="nav-tab"><?php esc_html_e('Display', 'post-product-filter'); ?></a>
                        <a href="#tab-styling" class="nav-tab"><?php esc_html_e('Styling', 'post-product-filter'); ?> <span class="new-badge">NEW</span></a>
                    </h2>
                    
                    <div class="tab-content" id="tab-general">
                        <?php post_product_filter_render_general_tab($preset); ?>
                    </div>
                    
                    <div class="tab-content" id="tab-display" style="display: none;">
                        <?php post_product_filter_render_display_tab($preset); ?>
                    </div>
                    
                    <div class="tab-content" id="tab-styling" style="display: none;">
                        <?php post_product_filter_render_styling_tab($preset); ?>
                    </div>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-hero">
                        <?php echo $is_new ? esc_html__('Create Preset', 'post-product-filter') : esc_html__('Update Preset', 'post-product-filter'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=post-product-filter')); ?>" class="button button-secondary">
                        <?php esc_html_e('Cancel', 'post-product-filter'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function handle_save_preset() {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], POST_PRODUCT_FILTER_SAVE_NONCE)) {
            wp_die(__('Security check failed.', 'post-product-filter'));
        }
        
        // Verify capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'post-product-filter'));
        }
        
        // Save preset using helper function
        $result = post_product_filter_save_preset();
        
        if ($result) {
            $redirect_url = add_query_arg(
                array('page' => 'post-product-filter', 'message' => 'saved'),
                admin_url('admin.php')
            );
        } else {
            $redirect_url = add_query_arg(
                array('page' => 'post-product-filter', 'message' => 'error'),
                admin_url('admin.php')
            );
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    public function handle_delete_preset() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete') {
            return;
        }
        
        if (!isset($_GET['preset_slug']) || !isset($_GET['ppf_delete_nonce'])) {
            return;
        }
        
        $preset_slug = sanitize_key($_GET['preset_slug']);
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['ppf_delete_nonce'], 'delete_preset_' . $preset_slug)) {
            wp_die(__('Security check failed.', 'post-product-filter'));
        }
        
        // Verify capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'post-product-filter'));
        }
        
        // Delete preset
        $result = post_product_filter_delete_preset($preset_slug);
        
        if ($result) {
            $redirect_url = add_query_arg(
                array('page' => 'post-product-filter', 'message' => 'deleted'),
                admin_url('admin.php')
            );
        } else {
            $redirect_url = add_query_arg(
                array('page' => 'post-product-filter', 'message' => 'error'),
                admin_url('admin.php')
            );
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }
}
