<?php
/**
 * Public-facing functionality
 *
 * @package Ajax_Post_Filter
 * @author  Ahmed haj abed
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Post_Filter_Public {
    
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'ajax-post-filter-public',
            AJAX_POST_FILTER_URL . 'public/css/ajax-post-filter-public.css',
            array(),
            AJAX_POST_FILTER_VERSION
        );
        
        wp_enqueue_script(
            'ajax-post-filter-public',
            AJAX_POST_FILTER_URL . 'public/js/ajax-post-filter-public.js',
            array('jquery'),
            AJAX_POST_FILTER_VERSION,
            true
        );
        
        wp_localize_script('ajax-post-filter-public', 'ajaxPostFilter', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('post_filter_nonce')
        ));
    }
    
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'slug' => 'default-preset',
        ), $atts);
        
        $presets = get_option('ajax_post_filter_presets', array());
        $preset = isset($presets[$atts['slug']]) ? $presets[$atts['slug']] : (isset($presets['default-preset']) ? $presets['default-preset'] : array());
        
        ob_start();
        $this->render_filter($preset);
        return ob_get_clean();
    }
    
    public function render_filter($preset = null) {
        if (!$preset || !isset($preset['settings'])) {
            $presets = get_option('ajax_post_filter_presets', array());
            $preset = isset($presets['default-preset']) ? $presets['default-preset'] : array('settings' => array());
        }
        
        $settings = $preset['settings'];
        $show_search = isset($settings['show_search']) ? $settings['show_search'] : true;
        $show_count = isset($settings['show_count']) ? $settings['show_count'] : true;
        $selected_categories = isset($settings['selected_categories']) ? $settings['selected_categories'] : array();
        $lazy_load = isset($settings['lazy_load']) ? $settings['lazy_load'] : true;
        $pagination_type = isset($settings['pagination_type']) ? $settings['pagination_type'] : 'pagination';
        $posts_per_page = isset($settings['posts_per_page']) ? $settings['posts_per_page'] : 9;
        
        // Get button text from options
        $apply_text = get_option('ajax_post_filter_apply_button_text', 'Apply Filters');
        $reset_text = get_option('ajax_post_filter_reset_button_text', 'Reset');
        $load_more_text = get_option('ajax_post_filter_load_more_text', 'Load More');
        $loading_text = get_option('ajax_post_filter_loading_text', 'Loading...');
        ?>
        
        <div id="ajax-post-filter-wrapper" 
             data-lazy-load="<?php echo $lazy_load ? 'true' : 'false'; ?>"
             data-pagination-type="<?php echo esc_attr($pagination_type); ?>"
             data-posts-per-page="<?php echo esc_attr($posts_per_page); ?>"
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
    
    public function add_seo_meta() {
        if (!is_singular()) {
            return;
        }
        
        global $wp_query;
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        if ($paged > 1) {
            $prev_page = $paged - 1;
            if ($prev_page == 1) {
                echo '<link rel="prev" href="' . esc_url(get_permalink()) . '" />' . "\n";
            } else {
                echo '<link rel="prev" href="' . esc_url(add_query_arg('paged', $prev_page, get_permalink())) . '" />' . "\n";
            }
        }
        
        if ($paged < $wp_query->max_num_pages) {
            $next_page = $paged + 1;
            echo '<link rel="next" href="' . esc_url(add_query_arg('paged', $next_page, get_permalink())) . '" />' . "\n";
        }
    }
}
