<?php
/**
 * Render Helper Functions for Admin Forms
 * Includes ALL NEW styling options (secured)
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render General Settings Tab
 */
function post_product_filter_render_general_tab($preset = null) {
    $settings = $preset ? $preset['settings'] : array();
    $preset_type = isset($settings['preset_type']) ? $settings['preset_type'] : 'posts';
    $posts_per_page = isset($settings['posts_per_page']) ? absint($settings['posts_per_page']) : 6;
    $pagination_type = isset($settings['pagination_type']) ? $settings['pagination_type'] : 'pagination';
    $columns = isset($settings['columns']) ? $settings['columns'] : '3';
    $selected_categories = isset($settings['selected_categories']) ? $settings['selected_categories'] : array();
    ?>
    
    <h3><?php esc_html_e('Preset Type', 'post-product-filter'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Filter Type', 'post-product-filter'); ?></th>
            <td>
                <select name="preset_type" id="preset_type">
                    <option value="posts" <?php selected($preset_type, 'posts'); ?>><?php esc_html_e('Blog Posts', 'post-product-filter'); ?></option>
                    <?php if (class_exists('WooCommerce')) : ?>
                    <option value="products" <?php selected($preset_type, 'products'); ?>><?php esc_html_e('WooCommerce Products', 'post-product-filter'); ?></option>
                    <?php endif; ?>
                </select>
                <p class="description"><?php esc_html_e('Choose whether to filter blog posts or WooCommerce products.', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Posts Per Page', 'post-product-filter'); ?></th>
            <td>
                <input type="number" name="posts_per_page" value="<?php echo esc_attr($posts_per_page); ?>" min="1" max="100" />
                <p class="description"><?php esc_html_e('Number of items to display per page (1-100).', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Pagination Type', 'post-product-filter'); ?></th>
            <td>
                <select name="pagination_type">
                    <option value="pagination" <?php selected($pagination_type, 'pagination'); ?>><?php esc_html_e('Classic Pagination', 'post-product-filter'); ?></option>
                    <option value="load_more" <?php selected($pagination_type, 'load_more'); ?>><?php esc_html_e('Load More Button', 'post-product-filter'); ?></option>
                    <option value="infinite" <?php selected($pagination_type, 'infinite'); ?>><?php esc_html_e('Infinite Scroll', 'post-product-filter'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Choose how users navigate through pages.', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Grid Columns', 'post-product-filter'); ?></th>
            <td>
                <select name="columns">
                    <option value="2" <?php selected($columns, '2'); ?>><?php esc_html_e('2 Columns', 'post-product-filter'); ?></option>
                    <option value="3" <?php selected($columns, '3'); ?>><?php esc_html_e('3 Columns', 'post-product-filter'); ?></option>
                    <option value="4" <?php selected($columns, '4'); ?>><?php esc_html_e('4 Columns', 'post-product-filter'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Number of columns in the grid layout.', 'post-product-filter'); ?></p>
            </td>
        </tr>
    </table>
    
    <h3><?php esc_html_e('Category Selection', 'post-product-filter'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Select Categories', 'post-product-filter'); ?></th>
            <td>
                <div class="category-selector" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #fff;">
                    <?php
                    if ($preset_type === 'products' && class_exists('WooCommerce')) {
                        $categories = get_terms(array(
                            'taxonomy' => 'product_cat',
                            'hide_empty' => false,
                            'orderby' => 'name'
                        ));
                    } else {
                        $categories = get_categories(array(
                            'hide_empty' => false,
                            'orderby' => 'name'
                        ));
                    }
                    
                    if (!empty($categories) && !is_wp_error($categories)) {
                        foreach ($categories as $category) {
                            $checked = in_array($category->term_id, $selected_categories) ? 'checked' : '';
                            ?>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="selected_categories[]" value="<?php echo esc_attr($category->term_id); ?>" <?php echo $checked; ?>>
                                <?php echo esc_html($category->name); ?> (<?php echo absint($category->count); ?>)
                            </label>
                            <?php
                        }
                    } else {
                        echo '<p>' . esc_html__('No categories found.', 'post-product-filter') . '</p>';
                    }
                    ?>
                </div>
                <p class="description"><?php esc_html_e('Select which categories to include in the filter. These are the ONLY categories that will be shown (not all site categories).', 'post-product-filter'); ?></p>
            </td>
        </tr>
    </table>
    
    <?php
}

/**
 * Render Display Options Tab
 */
function post_product_filter_render_display_tab($preset = null) {
    $settings = $preset ? $preset['settings'] : array();
    $lazy_load = isset($settings['lazy_load']) ? $settings['lazy_load'] : true;
    $show_search = isset($settings['show_search']) ? $settings['show_search'] : true;
    $show_count = isset($settings['show_count']) ? $settings['show_count'] : true;
    $show_excerpt = isset($settings['show_excerpt']) ? $settings['show_excerpt'] : false;
    $show_read_more = isset($settings['show_read_more']) ? $settings['show_read_more'] : true;
    $show_meta = isset($settings['show_meta']) ? $settings['show_meta'] : true;
    $show_categories = isset($settings['show_categories']) ? $settings['show_categories'] : true;
    $hide_out_of_stock = isset($settings['hide_out_of_stock']) ? $settings['hide_out_of_stock'] : false;
    $show_price = isset($settings['show_price']) ? $settings['show_price'] : true;
    $show_add_to_cart = isset($settings['show_add_to_cart']) ? $settings['show_add_to_cart'] : true;
    
    $form_title = isset($settings['form_title']) ? $settings['form_title'] : 'Filter by Categories';
    $load_more_text = isset($settings['load_more_text']) ? $settings['load_more_text'] : 'Load More';
    $loading_text = isset($settings['loading_text']) ? $settings['loading_text'] : 'Loading...';
    $read_more_text = isset($settings['read_more_text']) ? $settings['read_more_text'] : 'Read More';
    $add_to_cart_text = isset($settings['add_to_cart_text']) ? $settings['add_to_cart_text'] : 'Add to Cart';
    ?>
    
    <h3><?php esc_html_e('General Display Options', 'post-product-filter'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Performance', 'post-product-filter'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="lazy_load" value="1" <?php checked($lazy_load); ?>>
                    <?php esc_html_e('Enable Lazy Loading for Images', 'post-product-filter'); ?>
                </label>
                <p class="description"><?php esc_html_e('Improves page load time by loading images only when they become visible.', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Filter Widget', 'post-product-filter'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="show_search" value="1" <?php checked($show_search); ?>>
                    <?php esc_html_e('Show Category Search Box', 'post-product-filter'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="show_count" value="1" <?php checked($show_count); ?>>
                    <?php esc_html_e('Show Post Count Next to Categories', 'post-product-filter'); ?>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Filter Widget Title', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="form_title" value="<?php echo esc_attr($form_title); ?>" class="regular-text" maxlength="100">
                <p class="description"><?php esc_html_e('Text displayed at the top of the filter widget.', 'post-product-filter'); ?></p>
            </td>
        </tr>
    </table>
    
    <h3><?php esc_html_e('Post Display Options', 'post-product-filter'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Show/Hide Elements', 'post-product-filter'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="show_categories" value="1" <?php checked($show_categories); ?>>
                    <?php esc_html_e('Show Category Badges', 'post-product-filter'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="show_meta" value="1" <?php checked($show_meta); ?>>
                    <?php esc_html_e('Show Post Meta (Date, Author)', 'post-product-filter'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="show_excerpt" value="1" <?php checked($show_excerpt); ?>>
                    <?php esc_html_e('Show Excerpt', 'post-product-filter'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="show_read_more" value="1" <?php checked($show_read_more); ?>>
                    <?php esc_html_e('Show Read More Button', 'post-product-filter'); ?>
                </label>
            </td>
        </tr>
    </table>
    
    <h3><?php esc_html_e('Product Display Options', 'post-product-filter'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Product Options', 'post-product-filter'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="show_price" value="1" <?php checked($show_price); ?>>
                    <?php esc_html_e('Show Product Price', 'post-product-filter'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="show_add_to_cart" value="1" <?php checked($show_add_to_cart); ?>>
                    <?php esc_html_e('Show Add to Cart Button', 'post-product-filter'); ?>
                </label>
                <br>
                <label>
                    <input type="checkbox" name="hide_out_of_stock" value="1" <?php checked($hide_out_of_stock); ?>>
                    <?php esc_html_e('Hide Out of Stock Products', 'post-product-filter'); ?>
                </label>
            </td>
        </tr>
    </table>
    
    <h3><?php esc_html_e('Button Text', 'post-product-filter'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Load More Button Text', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="load_more_text" value="<?php echo esc_attr($load_more_text); ?>" class="regular-text" maxlength="50">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Loading Text', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="loading_text" value="<?php echo esc_attr($loading_text); ?>" class="regular-text" maxlength="50">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Read More Button Text', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="read_more_text" value="<?php echo esc_attr($read_more_text); ?>" class="regular-text" maxlength="50">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Add to Cart Button Text', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="add_to_cart_text" value="<?php echo esc_attr($add_to_cart_text); ?>" class="regular-text" maxlength="50">
            </td>
        </tr>
    </table>
    
    <?php
}

/**
 * Render Styling Tab with ALL NEW OPTIONS
 */
function post_product_filter_render_styling_tab($preset = null) {
    $settings = $preset ? $preset['settings'] : array();
    
    // Title styling
    $title_font_size = isset($settings['title_font_size']) ? absint($settings['title_font_size']) : 20;
    $title_color = isset($settings['title_color']) ? $settings['title_color'] : '#333333';
    $title_hover_color = isset($settings['title_hover_color']) ? $settings['title_hover_color'] : '#2271b1';
    
    // Filter widget styling - NEW!
    $filter_title_font_size = isset($settings['filter_title_font_size']) ? absint($settings['filter_title_font_size']) : 18;
    $filter_bg_color = isset($settings['filter_bg_color']) ? $settings['filter_bg_color'] : '#ffffff';
    $filter_border_color = isset($settings['filter_border_color']) ? $settings['filter_border_color'] : '#e5e5e5';
    $filter_title_color = isset($settings['filter_title_color']) ? $settings['filter_title_color'] : '#333333';
    
    // Category items - NEW!
    $category_font_size = isset($settings['category_font_size']) ? absint($settings['category_font_size']) : 12;
    $category_item_bg_color = isset($settings['category_item_bg_color']) ? $settings['category_item_bg_color'] : '#f0f0f0';
    $category_item_text_color = isset($settings['category_item_text_color']) ? $settings['category_item_text_color'] : '#666666';
    $category_item_hover_bg = isset($settings['category_item_hover_bg']) ? $settings['category_item_hover_bg'] : '#f8f9fa';
    
    // Result count - NEW!
    $result_count_font_size = isset($settings['result_count_font_size']) ? absint($settings['result_count_font_size']) : 16;
    $result_count_color = isset($settings['result_count_color']) ? $settings['result_count_color'] : '#666666';
    
    // Spacing - NEW!
    $container_padding = isset($settings['container_padding']) ? absint($settings['container_padding']) : 40;
    $item_spacing = isset($settings['item_spacing']) ? absint($settings['item_spacing']) : 30;
    
    // Read More button
    $button_color = isset($settings['button_color']) ? $settings['button_color'] : '#2271b1';
    $button_text_color = isset($settings['button_text_color']) ? $settings['button_text_color'] : '#ffffff';
    $button_hover_color = isset($settings['button_hover_color']) ? $settings['button_hover_color'] : '#135e96';
    
    // Load More button - NEW!
    $load_more_bg_color = isset($settings['load_more_bg_color']) ? $settings['load_more_bg_color'] : '#2271b1';
    $load_more_text_color = isset($settings['load_more_text_color']) ? $settings['load_more_text_color'] : '#ffffff';
    $load_more_hover_color = isset($settings['load_more_hover_color']) ? $settings['load_more_hover_color'] : '#135e96';
    
    // Product styling
    $price_color = isset($settings['price_color']) ? $settings['price_color'] : '#333333';
    $sale_price_color = isset($settings['sale_price_color']) ? $settings['sale_price_color'] : '#ff0000';
    $add_to_cart_bg_color = isset($settings['add_to_cart_bg_color']) ? $settings['add_to_cart_bg_color'] : '#2271b1';
    $add_to_cart_text_color = isset($settings['add_to_cart_text_color']) ? $settings['add_to_cart_text_color'] : '#ffffff';
    $add_to_cart_hover_color = isset($settings['add_to_cart_hover_color']) ? $settings['add_to_cart_hover_color'] : '#135e96';
    
    // Custom CSS
    $custom_css = isset($settings['custom_css']) ? $settings['custom_css'] : '';
    ?>
    
    <h3><?php esc_html_e('Title Styling', 'post-product-filter'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Title Font Size', 'post-product-filter'); ?></th>
            <td>
                <input type="number" name="title_font_size" value="<?php echo esc_attr($title_font_size); ?>" min="10" max="60"> px
                <p class="description"><?php esc_html_e('Font size for post/product titles (10-60px).', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Title Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="title_color" value="<?php echo esc_attr($title_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Title Hover Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="title_hover_color" value="<?php echo esc_attr($title_hover_color); ?>" class="color-picker">
            </td>
        </tr>
    </table>
    
    <div data-section="filter-widget">
    <h3><?php esc_html_e('Filter Widget Styling', 'post-product-filter'); ?> <span class="new-badge" style="background: #00a32a; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">NEW</span></h3>
    <p class="description" style="margin-top: 0; padding: 10px; background: #e7f3ff; border-left: 3px solid #2271b1;">
        <?php esc_html_e('💡 This section appears when "Show Category Search Box" or "Show Post Count" is enabled in the Display tab.', 'post-product-filter'); ?>
    </p>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Filter Title Font Size', 'post-product-filter'); ?></th>
            <td>
                <input type="number" name="filter_title_font_size" value="<?php echo esc_attr($filter_title_font_size); ?>" min="12" max="32"> px
                <p class="description"><?php esc_html_e('Font size for filter widget title (12-32px).', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Filter Background Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="filter_bg_color" value="<?php echo esc_attr($filter_bg_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Filter Border Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="filter_border_color" value="<?php echo esc_attr($filter_border_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Filter Title Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="filter_title_color" value="<?php echo esc_attr($filter_title_color); ?>" class="color-picker">
            </td>
        </tr>
    </table>
    </div>
    
    <div data-section="category-items">
    <h3><?php esc_html_e('Category Items Styling', 'post-product-filter'); ?> <span class="new-badge" style="background: #00a32a; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">NEW</span></h3>
    <p class="description" style="margin-top: 0; padding: 10px; background: #e7f3ff; border-left: 3px solid #2271b1;">
        <?php esc_html_e('💡 This section appears when "Show Category Badges" is enabled in the Display tab.', 'post-product-filter'); ?>
    </p>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Category Font Size', 'post-product-filter'); ?></th>
            <td>
                <input type="number" name="category_font_size" value="<?php echo esc_attr($category_font_size); ?>" min="10" max="24"> px
                <p class="description"><?php esc_html_e('Font size for category badges and filter items (10-24px).', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Category Item Background', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="category_item_bg_color" value="<?php echo esc_attr($category_item_bg_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Category Item Text Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="category_item_text_color" value="<?php echo esc_attr($category_item_text_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Category Item Hover Background', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="category_item_hover_bg" value="<?php echo esc_attr($category_item_hover_bg); ?>" class="color-picker">
            </td>
        </tr>
    </table>
    </div>
    
    <div data-section="result-count">
    <h3><?php esc_html_e('Result Count Styling', 'post-product-filter'); ?> <span class="new-badge" style="background: #00a32a; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">NEW</span></h3>
    <p class="description" style="margin-top: 0; padding: 10px; background: #e7f3ff; border-left: 3px solid #2271b1;">
        <?php esc_html_e('💡 This section appears when "Show Post Count Next to Categories" is enabled in the Display tab.', 'post-product-filter'); ?>
    </p>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Result Count Font Size', 'post-product-filter'); ?></th>
            <td>
                <input type="number" name="result_count_font_size" value="<?php echo esc_attr($result_count_font_size); ?>" min="12" max="24"> px
                <p class="description"><?php esc_html_e('Font size for showing X results text (12-24px).', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Result Count Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="result_count_color" value="<?php echo esc_attr($result_count_color); ?>" class="color-picker">
            </td>
        </tr>
    </table>
    
    <h3><?php esc_html_e('Spacing & Layout', 'post-product-filter'); ?> <span class="new-badge" style="background: #00a32a; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">NEW</span></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Container Padding', 'post-product-filter'); ?></th>
            <td>
                <input type="number" name="container_padding" value="<?php echo esc_attr($container_padding); ?>" min="0" max="100"> px
                <p class="description"><?php esc_html_e('Padding around the main filter container (0-100px).', 'post-product-filter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Item Spacing', 'post-product-filter'); ?></th>
            <td>
                <input type="number" name="item_spacing" value="<?php echo esc_attr($item_spacing); ?>" min="0" max="100"> px
                <p class="description"><?php esc_html_e('Gap between post/product items in the grid (0-100px).', 'post-product-filter'); ?></p>
            </td>
        </tr>
    </table>
    </div>
    
    <div data-section="read-more">
    <h3><?php esc_html_e('Read More Button', 'post-product-filter'); ?></h3>
    <p class="description" style="margin-top: 0; padding: 10px; background: #e7f3ff; border-left: 3px solid #2271b1;">
        <?php esc_html_e('💡 This section appears when "Show Read More Button" is enabled in the Display tab.', 'post-product-filter'); ?>
    </p>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Button Background Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="button_color" value="<?php echo esc_attr($button_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Button Text Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="button_text_color" value="<?php echo esc_attr($button_text_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Button Hover Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="button_hover_color" value="<?php echo esc_attr($button_hover_color); ?>" class="color-picker">
            </td>
        </tr>
    </table>
    </div>
    
    <div data-section="load-more">
    <h3><?php esc_html_e('Load More Button', 'post-product-filter'); ?> <span class="new-badge" style="background: #00a32a; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">NEW</span></h3>
    <p class="description" style="margin-top: 0; padding: 10px; background: #e7f3ff; border-left: 3px solid #2271b1;">
        <?php esc_html_e('💡 This section appears when Pagination Type is "Load More Button" or "Infinite Scroll" in the General tab.', 'post-product-filter'); ?>
    </p>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Load More Background Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="load_more_bg_color" value="<?php echo esc_attr($load_more_bg_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Load More Text Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="load_more_text_color" value="<?php echo esc_attr($load_more_text_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php esc_html_e('Load More Hover Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="load_more_hover_color" value="<?php echo esc_attr($load_more_hover_color); ?>" class="color-picker">
            </td>
        </tr>
    </table>
    </div>
    
    <div data-section="product-styling">
    <h3><?php esc_html_e('Product Styling', 'post-product-filter'); ?></h3>
    <p class="description" style="margin-top: 0; padding: 10px; background: #e7f3ff; border-left: 3px solid #2271b1;">
        <?php esc_html_e('💡 This section appears when Preset Type is "WooCommerce Products" AND at least one product display option is enabled.', 'post-product-filter'); ?>
    </p>
    <table class="form-table">
        <tr data-row="price-color">
            <th scope="row"><?php esc_html_e('Price Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="price_color" value="<?php echo esc_attr($price_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr data-row="sale-price-color">
            <th scope="row"><?php esc_html_e('Sale Price Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="sale_price_color" value="<?php echo esc_attr($sale_price_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr data-row="add-to-cart-bg">
            <th scope="row"><?php esc_html_e('Add to Cart Background', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="add_to_cart_bg_color" value="<?php echo esc_attr($add_to_cart_bg_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr data-row="add-to-cart-text">
            <th scope="row"><?php esc_html_e('Add to Cart Text Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="add_to_cart_text_color" value="<?php echo esc_attr($add_to_cart_text_color); ?>" class="color-picker">
            </td>
        </tr>
        
        <tr data-row="add-to-cart-hover">
            <th scope="row"><?php esc_html_e('Add to Cart Hover Color', 'post-product-filter'); ?></th>
            <td>
                <input type="text" name="add_to_cart_hover_color" value="<?php echo esc_attr($add_to_cart_hover_color); ?>" class="color-picker">
            </td>
        </tr>
    </table>
    </div>
    
    <h3><?php esc_html_e('Custom CSS', 'post-product-filter'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Additional CSS', 'post-product-filter'); ?></th>
            <td>
                <textarea name="custom_css" rows="10" class="large-text code" maxlength="5000"><?php echo esc_textarea($custom_css); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Add custom CSS for advanced styling. Security: Only safe CSS properties are allowed. Maximum 5000 characters.', 'post-product-filter'); ?>
                    <br>
                    <strong><?php esc_html_e('Note:', 'post-product-filter'); ?></strong> <?php esc_html_e('url(), @import, and external resources are blocked for security.', 'post-product-filter'); ?>
                </p>
            </td>
        </tr>
    </table>
    
    <script>
    jQuery(document).ready(function($) {
        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker();
        }
    });
    </script>
    
    <?php
}
