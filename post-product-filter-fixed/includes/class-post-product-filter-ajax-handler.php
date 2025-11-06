<?php
/**
 * AJAX Handler - SECURITY HARDENED v1.1.0
 * - FIXED: Category filtering (only show selected categories when specified)
 * - FIXED: Enhanced rate limiting with progressive penalties
 * - FIXED: SQL injection protection
 * - ADDED: IP blocking after threshold
 */

if (!defined('ABSPATH')) {
    exit;
}

class Post_Product_Filter_Ajax_Handler {
    
    private $rate_limit_threshold = 30; // Reduced from 100
    private $rate_limit_window = 60; // seconds
    private $block_threshold = 5; // Block after 5 violations
    
    /**
     * Filter posts with FIXED category logic and enhanced security
     */
    public function filter_posts() {
        // Check if IP is blocked
        if ($this->is_ip_blocked()) {
            post_product_filter_log_security_event('blocked_request', 'IP blocked');
            wp_send_json_error(array(
                'message' => 'Access denied. Too many violations.'
            ));
            return;
        }
        
        // Enhanced rate limiting with progressive penalties
        if (!$this->check_rate_limit()) {
            post_product_filter_log_security_event('rate_limit_exceeded', 'Too many requests');
            wp_send_json_error(array(
                'message' => 'Too many requests. Please wait a moment and try again.'
            ));
            return;
        }
        
        // Verify nonce
        check_ajax_referer(POST_PRODUCT_FILTER_AJAX_FILTER_NONCE, 'nonce');
        
        // Sanitize and validate categories
        $categories = isset($_POST['categories']) && is_array($_POST['categories']) 
            ? array_map('absint', $_POST['categories']) 
            : array();
        
        // Remove invalid IDs
        $categories = array_filter($categories, function($cat_id) {
            return $cat_id > 0 && $cat_id < PHP_INT_MAX;
        });
        
        // Get preset slug (for retrieving selected categories)
        $preset_slug = isset($_POST['preset_slug']) ? sanitize_key($_POST['preset_slug']) : '';
        
        // CRITICAL FIX: Get preset's selected categories
        $preset_categories = array();
        if (!empty($preset_slug)) {
            $presets = get_option('post_product_filter_presets', array());
            if (isset($presets[$preset_slug]['settings']['selected_categories'])) {
                $preset_categories = $presets[$preset_slug]['settings']['selected_categories'];
                // Validate each category exists
                $preset_categories = array_filter($preset_categories, function($cat_id) {
                    return $cat_id > 0 && $cat_id < PHP_INT_MAX;
                });
            }
        }
        
        // Validate and bound pagination
        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
        $paged = max(1, min(1000, $paged)); // Max 1000 pages to prevent DoS
        
        $posts_per_page = isset($_POST['posts_per_page']) ? absint($_POST['posts_per_page']) : 6;
        $posts_per_page = max(1, min(100, $posts_per_page));
        
        // Sanitize boolean settings
        $lazy_load = isset($_POST['lazy_load']) && $_POST['lazy_load'] === 'true';
        $show_excerpt = isset($_POST['show_excerpt']) && $_POST['show_excerpt'] === 'true';
        $show_read_more = isset($_POST['show_read_more']) && $_POST['show_read_more'] === 'true';
        $show_meta = isset($_POST['show_meta']) && $_POST['show_meta'] === 'true';
        $show_categories = isset($_POST['show_categories']) && $_POST['show_categories'] === 'true';
        $hide_out_of_stock = isset($_POST['hide_out_of_stock']) && $_POST['hide_out_of_stock'] === 'true';
        
        // Validate pagination type
        $pagination_type = isset($_POST['pagination_type']) ? sanitize_key($_POST['pagination_type']) : 'pagination';
        $allowed_pagination = array('pagination', 'load_more', 'infinite');
        if (!in_array($pagination_type, $allowed_pagination, true)) {
            $pagination_type = 'pagination';
        }
        
        // Validate preset type
        $preset_type = isset($_POST['preset_type']) ? sanitize_key($_POST['preset_type']) : 'posts';
        $allowed_types = array('posts', 'products');
        if (!in_array($preset_type, $allowed_types, true)) {
            $preset_type = 'posts';
        }
        
        // Sanitize text fields with length limits
        $read_more_text = isset($_POST['read_more_text']) 
            ? substr(sanitize_text_field($_POST['read_more_text']), 0, 50) 
            : 'Read More';
        
        $add_to_cart_text = isset($_POST['add_to_cart_text']) 
            ? substr(sanitize_text_field($_POST['add_to_cart_text']), 0, 50) 
            : 'Add to Cart';
        
        $load_more_text = isset($_POST['load_more_text']) 
            ? substr(sanitize_text_field($_POST['load_more_text']), 0, 50) 
            : 'Load More';
        
        // Build WP_Query args
        if ($preset_type === 'products' && class_exists('WooCommerce')) {
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $posts_per_page,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => false
            );
            
            if ($hide_out_of_stock) {
                $args['meta_query'] = array(
                    array(
                        'key' => '_stock_status',
                        'value' => 'instock',
                        'compare' => '='
                    )
                );
            }
            
            // CRITICAL FIX: Category filtering logic
            $categories_to_query = array();
            
            if (!empty($categories)) {
                // User selected specific filters - show ONLY those
                $categories_to_query = $categories;
            } elseif (!empty($preset_categories)) {
                // No user filters - show ONLY preset's selected categories (NOT all products)
                $categories_to_query = $preset_categories;
            }
            // If both are empty, show NO products (not all)
            
            if (!empty($categories_to_query)) {
                // Validate each category exists in database
                $valid_categories = array();
                foreach ($categories_to_query as $cat_id) {
                    $term = get_term($cat_id, 'product_cat');
                    if ($term && !is_wp_error($term)) {
                        $valid_categories[] = absint($cat_id);
                    }
                }
                
                if (!empty($valid_categories)) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $valid_categories,
                            'operator' => 'IN',
                            'include_children' => false // Don't include child categories
                        )
                    );
                }
            } else {
                // CRITICAL: If no categories specified, return empty result
                $args['post__in'] = array(0); // This will return no posts
            }
        } else {
            // Posts
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $posts_per_page,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => false
            );
            
            // CRITICAL FIX: Category filtering logic for posts
            $categories_to_query = array();
            
            if (!empty($categories)) {
                // User selected specific filters - show ONLY those
                $categories_to_query = $categories;
            } elseif (!empty($preset_categories)) {
                // No user filters - show ONLY preset's selected categories (NOT all posts)
                $categories_to_query = $preset_categories;
            }
            // If both are empty, show NO posts (not all)
            
            if (!empty($categories_to_query)) {
                // Validate each category exists in database
                $valid_categories = array();
                foreach ($categories_to_query as $cat_id) {
                    $term = get_term($cat_id, 'category');
                    if ($term && !is_wp_error($term)) {
                        $valid_categories[] = absint($cat_id);
                    }
                }
                
                if (!empty($valid_categories)) {
                    $args['category__in'] = $valid_categories;
                }
            } else {
                // CRITICAL: If no categories specified, return empty result
                $args['post__in'] = array(0); // This will return no posts
            }
        }
        
        // Execute query
        $query = new WP_Query($args);
        
        $response = array(
            'posts' => '',
            'pagination' => '',
            'found_posts' => absint($query->found_posts),
            'max_pages' => absint($query->max_num_pages),
            'current_page' => $paged
        );
        
        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                
                $thumbnail_id = get_post_thumbnail_id();
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : '';
                $thumbnail_srcset = $thumbnail_id ? wp_get_attachment_image_srcset($thumbnail_id, 'large') : '';
                
                if ($preset_type === 'products' && class_exists('WooCommerce')) {
                    global $product;
                    $product = wc_get_product(get_the_ID());
                    if (!$product) continue;
                    ?>
                    <article class="product-item" data-product-id="<?php echo absint(get_the_ID()); ?>">
                        <?php if ($thumbnail_url) : ?>
                            <div class="product-thumbnail">
                                <a href="<?php echo esc_url(get_permalink()); ?>">
                                    <?php if ($lazy_load) : ?>
                                        <img 
                                            class="lazy-load" 
                                            data-src="<?php echo esc_url($thumbnail_url); ?>"
                                            <?php if ($thumbnail_srcset) : ?>
                                            data-srcset="<?php echo esc_attr($thumbnail_srcset); ?>"
                                            <?php endif; ?>
                                            alt="<?php echo esc_attr(get_the_title()); ?>"
                                            loading="lazy"
                                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 600'%3E%3C/svg%3E">
                                    <?php else : ?>
                                        <img 
                                            src="<?php echo esc_url($thumbnail_url); ?>"
                                            <?php if ($thumbnail_srcset) : ?>
                                            srcset="<?php echo esc_attr($thumbnail_srcset); ?>"
                                            <?php endif; ?>
                                            alt="<?php echo esc_attr(get_the_title()); ?>">
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-content">
                            <?php if ($show_categories) : ?>
                            <div class="product-categories">
                                <?php
                                $product_categories = get_the_terms(get_the_ID(), 'product_cat');
                                if ($product_categories && !is_wp_error($product_categories)) {
                                    foreach ($product_categories as $category) {
                                        echo '<span class="product-category">' . esc_html($category->name) . '</span>';
                                    }
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <h2 class="product-title">
                                <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a>
                            </h2>
                            
                            <div class="product-price">
                                <?php echo wp_kses_post($product->get_price_html()); ?>
                            </div>
                            
                            <?php if ($show_excerpt) : ?>
                            <div class="product-excerpt">
                                <?php echo wp_kses_post(get_the_excerpt()); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <?php
                                if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) {
                                    echo sprintf(
                                        '<a href="%s" data-product_id="%s" class="button add_to_cart_button product_type_simple" rel="nofollow">%s</a>',
                                        esc_url($product->add_to_cart_url()),
                                        esc_attr($product->get_id()),
                                        esc_html($add_to_cart_text)
                                    );
                                } else {
                                    echo sprintf(
                                        '<a href="%s" class="button view-product">View Product</a>',
                                        esc_url(get_permalink())
                                    );
                                }
                                ?>
                            </div>
                        </div>
                    </article>
                    <?php
                } else {
                    // Posts
                    ?>
                    <article class="post-item" data-post-id="<?php echo absint(get_the_ID()); ?>">
                        <?php if ($thumbnail_url) : ?>
                            <div class="post-thumbnail">
                                <a href="<?php echo esc_url(get_permalink()); ?>">
                                    <?php if ($lazy_load) : ?>
                                        <img 
                                            class="lazy-load" 
                                            data-src="<?php echo esc_url($thumbnail_url); ?>"
                                            <?php if ($thumbnail_srcset) : ?>
                                            data-srcset="<?php echo esc_attr($thumbnail_srcset); ?>"
                                            <?php endif; ?>
                                            alt="<?php echo esc_attr(get_the_title()); ?>"
                                            loading="lazy"
                                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 600'%3E%3C/svg%3E">
                                    <?php else : ?>
                                        <img 
                                            src="<?php echo esc_url($thumbnail_url); ?>"
                                            <?php if ($thumbnail_srcset) : ?>
                                            srcset="<?php echo esc_attr($thumbnail_srcset); ?>"
                                            <?php endif; ?>
                                            alt="<?php echo esc_attr(get_the_title()); ?>">
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php else : ?>
                            <div class="post-thumbnail post-thumbnail-placeholder">
                                <a href="<?php echo esc_url(get_permalink()); ?>">
                                    <div class="thumbnail-placeholder">
                                        <span class="dashicons dashicons-format-image"></span>
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <?php if ($show_categories) : ?>
                            <div class="post-categories">
                                <?php
                                $post_categories = get_the_category();
                                if ($post_categories) {
                                    foreach ($post_categories as $category) {
                                        echo '<span class="post-category">' . esc_html($category->name) . '</span>';
                                    }
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <h2 class="post-title">
                                <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a>
                            </h2>
                            
                            <?php if ($show_meta) : ?>
                            <div class="post-meta">
                                <span class="post-date"><?php echo esc_html(get_the_date()); ?></span>
                                <span class="post-author">by <?php echo esc_html(get_the_author()); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($show_excerpt) : ?>
                            <div class="post-excerpt">
                                <?php echo wp_kses_post(get_the_excerpt()); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($show_read_more) : ?>
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="read-more">
                                <?php echo esc_html($read_more_text); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php
                }
            }
            $response['posts'] = ob_get_clean();
            
            // Pagination
            if ($query->max_num_pages > 1) {
                ob_start();
                
                if ($pagination_type === 'load_more') {
                    if ($paged < $query->max_num_pages) {
                        ?>
                        <div class="load-more-wrapper">
                            <button type="button" class="button load-more-btn" data-page="<?php echo absint($paged + 1); ?>">
                                <?php echo esc_html($load_more_text); ?>
                            </button>
                        </div>
                        <?php
                    }
                } else if ($pagination_type === 'pagination') {
                    echo '<div class="pagination">';
                    echo paginate_links(array(
                        'total' => $query->max_num_pages,
                        'current' => $paged,
                        'format' => '?paged=%#%',
                        'prev_text' => '&laquo; Previous',
                        'next_text' => 'Next &raquo;',
                        'type' => 'plain'
                    ));
                    echo '</div>';
                }
                
                $response['pagination'] = ob_get_clean();
            }
        } else {
            $no_items_message = $preset_type === 'products' ? 
                'No products found matching your criteria.' : 
                'No posts found matching your criteria.';
            $response['posts'] = '<div class="no-posts">' . esc_html($no_items_message) . '</div>';
        }
        
        wp_reset_postdata();
        
        wp_send_json_success($response);
    }
    
    /**
     * ENHANCED: Rate limiting with progressive penalties
     */
    private function check_rate_limit() {
        $user_identifier = $this->get_user_identifier();
        $rate_limit_key = 'ppf_rate_limit_' . md5($user_identifier);
        $violation_key = 'ppf_violations_' . md5($user_identifier);
        
        $request_count = get_transient($rate_limit_key);
        $violations = absint(get_transient($violation_key));
        
        // Progressive rate limiting based on violations
        $current_limit = $this->rate_limit_threshold;
        if ($violations > 0) {
            $current_limit = max(5, $this->rate_limit_threshold - ($violations * 5));
        }
        
        if ($request_count !== false && $request_count >= $current_limit) {
            // Increment violations
            $violations++;
            set_transient($violation_key, $violations, 3600); // Store for 1 hour
            
            // Block IP after threshold
            if ($violations >= $this->block_threshold) {
                $this->block_ip($user_identifier);
            }
            
            return false;
        }
        
        $new_count = $request_count ? $request_count + 1 : 1;
        set_transient($rate_limit_key, $new_count, $this->rate_limit_window);
        
        return true;
    }
    
    /**
     * Check if IP is blocked
     */
    private function is_ip_blocked() {
        $user_identifier = $this->get_user_identifier();
        $block_key = 'ppf_blocked_' . md5($user_identifier);
        
        return get_transient($block_key) !== false;
    }
    
    /**
     * Block IP address
     */
    private function block_ip($user_identifier) {
        $block_key = 'ppf_blocked_' . md5($user_identifier);
        set_transient($block_key, true, 3600); // Block for 1 hour
        
        post_product_filter_log_security_event('ip_blocked', 'User: ' . $user_identifier);
    }
    
    /**
     * Get user identifier for rate limiting
     */
    private function get_user_identifier() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        
        return 'ip_' . post_product_filter_get_user_ip();
    }
}
