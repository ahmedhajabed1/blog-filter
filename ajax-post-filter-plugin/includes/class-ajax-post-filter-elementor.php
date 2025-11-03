<?php
/**
 * AJAX Post Filter - Elementor Widget
 * 
 * Author: Ahmed haj abed
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Don't load if Elementor is not active
if (!did_action('elementor/loaded')) {
    return;
}

class Ajax_Post_Filter_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ajax-post-filter';
    }

    public function get_title() {
        return __('Ajax Post Filter', 'ajax-post-filter');
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_keywords() {
        return ['post', 'filter', 'ajax', 'category', 'blog'];
    }

    protected function register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Filter Settings', 'ajax-post-filter'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Get available presets
        $presets = get_option('ajax_post_filter_presets', array());
        $preset_options = array();
        foreach ($presets as $slug => $preset) {
            $preset_options[$slug] = $preset['name'];
        }

        $this->add_control(
            'preset_slug',
            [
                'label' => __('Select Preset', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'default-preset',
                'options' => $preset_options,
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Posts Per Page', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 9,
                'min' => 1,
                'max' => 50,
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label' => __('Show Category Search', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'ajax-post-filter'),
                'label_off' => __('Hide', 'ajax-post-filter'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_count',
            [
                'label' => __('Show Post Count', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'ajax-post-filter'),
                'label_off' => __('Hide', 'ajax-post-filter'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'lazy_load',
            [
                'label' => __('Enable Lazy Load', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'ajax-post-filter'),
                'label_off' => __('No', 'ajax-post-filter'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Load images only when visible', 'ajax-post-filter'),
            ]
        );

        $this->add_control(
            'pagination_type',
            [
                'label' => __('Pagination Type', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'pagination',
                'options' => [
                    'pagination' => __('Standard Pagination', 'ajax-post-filter'),
                    'load_more' => __('Load More Button', 'ajax-post-filter'),
                    'infinite' => __('Infinite Scroll', 'ajax-post-filter'),
                ],
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '2' => __('2 Columns', 'ajax-post-filter'),
                    '3' => __('3 Columns', 'ajax-post-filter'),
                    '4' => __('4 Columns', 'ajax-post-filter'),
                ],
            ]
        );

        $this->end_controls_section();

        // Button Text Section
        $this->start_controls_section(
            'button_text_section',
            [
                'label' => __('Button Text', 'ajax-post-filter'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'apply_button_text',
            [
                'label' => __('Apply Button Text', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Apply Filters', 'ajax-post-filter'),
            ]
        );

        $this->add_control(
            'reset_button_text',
            [
                'label' => __('Reset Button Text', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Reset', 'ajax-post-filter'),
            ]
        );

        $this->add_control(
            'load_more_text',
            [
                'label' => __('Load More Text', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Load More', 'ajax-post-filter'),
                'condition' => [
                    'pagination_type' => 'load_more',
                ],
            ]
        );

        $this->add_control(
            'loading_text',
            [
                'label' => __('Loading Text', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Loading...', 'ajax-post-filter'),
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Colors & Style', 'ajax-post-filter'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => __('Primary Color', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#2271b1',
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => __('Button Background', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#2271b1',
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Button Text Color', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => __('Button Hover Background', 'ajax-post-filter'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#135e96',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get preset
        $presets = get_option('ajax_post_filter_presets', array());
        $preset = isset($presets[$settings['preset_slug']]) ? $presets[$settings['preset_slug']] : $presets['default-preset'];
        
        // Override with Elementor settings
        $preset['settings']['posts_per_page'] = $settings['posts_per_page'];
        $preset['settings']['show_search'] = $settings['show_search'] === 'yes';
        $preset['settings']['show_count'] = $settings['show_count'] === 'yes';
        $preset['settings']['lazy_load'] = $settings['lazy_load'] === 'yes';
        $preset['settings']['pagination_type'] = $settings['pagination_type'];
        $preset['settings']['columns'] = $settings['columns'];
        
        // Button text
        $preset['settings']['apply_button_text'] = $settings['apply_button_text'];
        $preset['settings']['reset_button_text'] = $settings['reset_button_text'];
        $preset['settings']['load_more_text'] = $settings['load_more_text'];
        $preset['settings']['loading_text'] = $settings['loading_text'];
        
        // Colors
        $preset['settings']['primary_color'] = $settings['primary_color'];
        $preset['settings']['button_color'] = $settings['button_color'];
        $preset['settings']['button_text_color'] = $settings['button_text_color'];
        $preset['settings']['button_hover_color'] = $settings['button_hover_color'];
        
        // Add inline styles
        ?>
        <style>
            .elementor-element-<?php echo $this->get_id(); ?> .btn-apply,
            .elementor-element-<?php echo $this->get_id(); ?> .read-more,
            .elementor-element-<?php echo $this->get_id(); ?> .load-more-btn,
            .elementor-element-<?php echo $this->get_id(); ?> .active-filter-tag {
                background-color: <?php echo esc_attr($settings['button_color']); ?> !important;
                color: <?php echo esc_attr($settings['button_text_color']); ?> !important;
            }
            .elementor-element-<?php echo $this->get_id(); ?> .btn-apply:hover,
            .elementor-element-<?php echo $this->get_id(); ?> .read-more:hover,
            .elementor-element-<?php echo $this->get_id(); ?> .load-more-btn:hover {
                background-color: <?php echo esc_attr($settings['button_hover_color']); ?> !important;
            }
            .elementor-element-<?php echo $this->get_id(); ?> .posts-grid {
                grid-template-columns: repeat(<?php echo esc_attr($settings['columns']); ?>, 1fr) !important;
            }
        </style>
        <?php
        
        // Render the filter
        ajax_post_filter_render_elementor($preset);
    }
}

// Register Elementor widget
function register_ajax_post_filter_elementor_widget($widgets_manager) {
    require_once(__DIR__ . '/ajax-post-filter-elementor.php');
    $widgets_manager->register(new \Ajax_Post_Filter_Elementor_Widget());
}
add_action('elementor/widgets/register', 'register_ajax_post_filter_elementor_widget');

// Render function for Elementor
function ajax_post_filter_render_elementor($preset) {
    $settings = $preset['settings'];
    $show_search = isset($settings['show_search']) ? $settings['show_search'] : true;
    $show_count = isset($settings['show_count']) ? $settings['show_count'] : true;
    $selected_categories = isset($settings['selected_categories']) ? $settings['selected_categories'] : array();
    $lazy_load = isset($settings['lazy_load']) ? $settings['lazy_load'] : true;
    $pagination_type = isset($settings['pagination_type']) ? $settings['pagination_type'] : 'pagination';
    
    // Button text
    $apply_text = isset($settings['apply_button_text']) ? $settings['apply_button_text'] : 'Apply Filters';
    $reset_text = isset($settings['reset_button_text']) ? $settings['reset_button_text'] : 'Reset';
    $load_more_text = isset($settings['load_more_text']) ? $settings['load_more_text'] : 'Load More';
    $loading_text = isset($settings['loading_text']) ? $settings['loading_text'] : 'Loading...';
    ?>
    
    <div id="ajax-post-filter-wrapper" 
         data-lazy-load="<?php echo $lazy_load ? 'true' : 'false'; ?>"
         data-pagination-type="<?php echo esc_attr($pagination_type); ?>"
         data-load-more-text="<?php echo esc_attr($load_more_text); ?>"
         data-loading-text="<?php echo esc_attr($loading_text); ?>">
        <div class="filter-container">
            <div class="filter-sidebar">
                <div class="filter-widget">
                    <h3 class="filter-title">Filter by Category</h3>
                    
                    <?php if ($show_search) : ?>
                    <div class="filter-search">
                        <input type="text" id="category-search" placeholder="Search categories...">
                    </div>
                    <?php endif; ?>
                    
                    <div class="filter-options">
                        <?php
                        $categories = get_categories(array(
                            'orderby' => 'name',
                            'order' => 'ASC',
                            'hide_empty' => true,
                            'include' => !empty($selected_categories) ? $selected_categories : ''
                        ));
                        
                        foreach ($categories as $category) :
                            $count = $category->count;
                        ?>
                            <label class="filter-option" data-category-name="<?php echo esc_attr(strtolower($category->name)); ?>">
                                <input type="checkbox" 
                                       name="category_filter" 
                                       value="<?php echo esc_attr($category->term_id); ?>"
                                       data-count="<?php echo esc_attr($count); ?>">
                                <span class="filter-label">
                                    <?php echo esc_html($category->name); ?>
                                    <?php if ($show_count) : ?>
                                    <span class="filter-count">(<?php echo $count; ?>)</span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="button" id="apply-filters" class="btn-apply"><?php echo esc_html($apply_text); ?></button>
                        <button type="button" id="reset-filters" class="btn-reset"><?php echo esc_html($reset_text); ?></button>
                    </div>
                </div>
                
                <div class="active-filters" style="display: none;">
                    <h4>Active Filters:</h4>
                    <div id="active-filters-list"></div>
                </div>
            </div>
            
            <div class="posts-container">
                <div class="posts-header">
                    <div class="results-count">
                        Showing <span id="results-count">0</span> results
                    </div>
                    
                    <div class="loading-overlay" style="display: none;">
                        <div class="loader"></div>
                        <p class="loading-text"><?php echo esc_html($loading_text); ?></p>
                    </div>
                </div>
                
                <div id="posts-grid" class="posts-grid">
                    <!-- Posts will be loaded here via AJAX -->
                </div>
                
                <div id="posts-pagination" class="posts-pagination">
                    <!-- Pagination or Load More button will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <?php
}
