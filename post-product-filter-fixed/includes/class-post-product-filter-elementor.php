<?php
/**
 * Elementor Widget - FIXED: Proper preset selector with validation
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) exit;

// Only proceed if Elementor classes are available
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class Post_Product_Filter_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'post-product-filter';
    }

    public function get_title() {
        return __('Post/Product Filter', 'post-product-filter');
    }

    public function get_icon() {
        return 'eicon-filter';
    }

    public function get_categories() {
        return ['general'];
    }
    
    public function get_keywords() {
        return ['filter', 'post', 'product', 'ajax', 'category'];
    }

    protected function register_controls() {
        // Get all available presets
        $presets = get_option('post_product_filter_presets', array());
        $preset_options = array();
        
        if (!empty($presets)) {
            foreach ($presets as $slug => $preset) {
                $type_label = isset($preset['settings']['preset_type']) ? ' (' . ucfirst($preset['settings']['preset_type']) . ')' : '';
                $preset_options[$slug] = $preset['name'] . $type_label;
            }
        }
        
        // Add default empty option
        $preset_options = array_merge(
            array('' => __('-- Select a Preset --', 'post-product-filter')),
            $preset_options
        );
        
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Filter Settings', 'post-product-filter'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        // Info if no presets exist
        if (count($preset_options) === 1) {
            $this->add_control(
                'no_presets_notice',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        __('<div style="padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
                            <strong>⚠️ No Presets Available</strong><br>
                            Please create a preset first in:<br>
                            <strong>WordPress Admin → Post/Product Filter</strong>
                        </div>', 'post-product-filter')
                    ),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                ]
            );
        }
        
        $this->add_control(
            'preset_slug',
            [
                'label' => __('Select Preset', 'post-product-filter'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $preset_options,
                'default' => '',
                'description' => __('Choose which filter preset to display', 'post-product-filter'),
            ]
        );
        
        // Help text
        $this->add_control(
            'preset_help',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => sprintf(
                    __('<div style="padding: 10px; background: #e8f4f8; border-left: 3px solid #2271b1; margin-top: 10px;">
                        <strong>💡 How to use:</strong><br>
                        1. Create presets in <strong>WP Admin → Post/Product Filter</strong><br>
                        2. Select a preset from the dropdown above<br>
                        3. The filter will appear on your page<br><br>
                        <a href="%s" target="_blank" style="color: #2271b1;">Manage Presets →</a>
                    </div>', 'post-product-filter'),
                    admin_url('admin.php?page=post-product-filter')
                ),
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Additional Styling', 'post-product-filter'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'style_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => __('<p>All styling options are configured in the preset settings. Edit your preset to change colors, fonts, spacing, etc.</p>', 'post-product-filter'),
            ]
        );
        
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $preset_slug = isset($settings['preset_slug']) ? sanitize_key($settings['preset_slug']) : '';
        
        // Editor mode preview
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            if (empty($preset_slug)) {
                echo '<div style="padding: 40px 20px; background: #fff3cd; border: 2px dashed #ffc107; text-align: center; border-radius: 8px;">';
                echo '<p style="margin: 0 0 10px 0; font-size: 18px; color: #856404;"><strong>⚠️ No Preset Selected</strong></p>';
                echo '<p style="margin: 0; color: #856404;">Please select a preset from the widget settings panel.</p>';
                echo '<p style="margin: 10px 0 0 0; font-size: 12px; color: #856404;">This message only appears in the editor.</p>';
                echo '</div>';
                return;
            }
            
            // Check if preset exists
            $presets = get_option('post_product_filter_presets', array());
            if (!isset($presets[$preset_slug])) {
                echo '<div style="padding: 40px 20px; background: #f8d7da; border: 2px solid #dc3545; text-align: center; border-radius: 8px;">';
                echo '<p style="margin: 0 0 10px 0; font-size: 18px; color: #721c24;"><strong>❌ Preset Not Found</strong></p>';
                echo '<p style="margin: 0; color: #721c24;">The selected preset "' . esc_html($preset_slug) . '" does not exist.</p>';
                echo '<p style="margin: 10px 0 0 0; font-size: 12px; color: #721c24;">Please select a different preset or create one.</p>';
                echo '</div>';
                return;
            }
            
            // Preview in editor
            $preset = $presets[$preset_slug];
            $preset_type = isset($preset['settings']['preset_type']) ? $preset['settings']['preset_type'] : 'posts';
            
            echo '<div style="padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; text-align: center; border-radius: 8px; color: white;">';
            echo '<p style="margin: 0 0 10px 0; font-size: 24px;"><strong>✨ ' . esc_html($preset['name']) . '</strong></p>';
            echo '<p style="margin: 0 0 5px 0; opacity: 0.9;">Preset Slug: <code style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 3px;">' . esc_html($preset_slug) . '</code></p>';
            echo '<p style="margin: 0; opacity: 0.9;">Type: <strong>' . esc_html(ucfirst($preset_type)) . '</strong></p>';
            echo '<p style="margin: 15px 0 0 0; font-size: 12px; opacity: 0.8;">🔍 Preview mode - Filter will be functional on the frontend</p>';
            echo '</div>';
            return;
        }
        
        // Frontend render - validate preset exists
        if (empty($preset_slug)) {
            return;
        }
        
        $presets = get_option('post_product_filter_presets', array());
        if (!isset($presets[$preset_slug])) {
            if (current_user_can('edit_posts')) {
                echo '<div style="padding: 20px; background: #f8d7da; border: 1px solid #dc3545; border-radius: 4px; color: #721c24;">';
                echo '<strong>Error:</strong> Preset "' . esc_html($preset_slug) . '" not found.';
                echo '</div>';
            }
            return;
        }
        
        // Render the filter using the shortcode handler
        if (class_exists('Post_Product_Filter_Public')) {
            $public = new Post_Product_Filter_Public();
            echo $public->shortcode_handler(array('slug' => $preset_slug));
        }
    }
    
    protected function content_template() {
        ?>
        <#
        if (settings.preset_slug) {
            #>
            <div style="padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; text-align: center; border-radius: 8px; color: white;">
                <p style="margin: 0 0 10px 0; font-size: 24px;"><strong>✨ Post/Product Filter</strong></p>
                <p style="margin: 0 0 5px 0; opacity: 0.9;">Preset: <code style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 3px;">{{ settings.preset_slug }}</code></p>
                <p style="margin: 15px 0 0 0; font-size: 12px; opacity: 0.8;">🔍 Preview available in editor mode</p>
            </div>
            <#
        } else {
            #>
            <div style="padding: 40px 20px; background: #fff3cd; border: 2px dashed #ffc107; text-align: center; border-radius: 8px;">
                <p style="margin: 0 0 10px 0; font-size: 18px; color: #856404;"><strong>⚠️ No Preset Selected</strong></p>
                <p style="margin: 0; color: #856404;">Please select a preset from the widget settings.</p>
            </div>
            <#
        }
        #>
        <?php
    }
}
