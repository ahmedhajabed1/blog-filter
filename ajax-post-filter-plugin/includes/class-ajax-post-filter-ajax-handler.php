<?php
/**
 * AJAX Handler for post filtering
 *
 * @package Ajax_Post_Filter
 * @author  Ahmed haj abed
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Post_Filter_Ajax_Handler {
    
    public function filter_posts() {
        // Verify nonce
        check_ajax_referer('post_filter_nonce', 'nonce');
        
        // Get selected categories
        $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 9;
        $lazy_load = isset($_POST['lazy_load']) && $_POST['lazy_load'] === 'true';
        $pagination_type = isset($_POST['pagination_type']) ? sanitize_text_field($_POST['pagination_type']) : 'pagination';
        
        // Query arguments
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Add category filter if categories are selected
        if (!empty($categories)) {
            $args['category__in'] = $categories;
        }
        
        $query = new WP_Query($args);
        
        $response = array(
            'posts' => '',
            'pagination' => '',
            'found_posts' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'current_page' => $paged
        );
        
        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                
                // Get thumbnail
                $thumbnail_id = get_post_thumbnail_id();
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
                $thumbnail_srcset = $thumbnail_id ? wp_get_attachment_image_srcset($thumbnail_id, 'medium') : '';
                ?>
                <article class="post-item" data-post-id="<?php echo get_the_ID(); ?>">
                    <?php if ($thumbnail_url) : ?>
                        <div class="post-thumbnail">
                            <a href="<?php the_permalink(); ?>">
                                <?php if ($lazy_load) : ?>
                                    <img 
                                        class="lazy-load" 
                                        data-src="<?php echo esc_url($thumbnail_url); ?>"
                                        <?php if ($thumbnail_srcset) : ?>
                                        data-srcset="<?php echo esc_attr($thumbnail_srcset); ?>"
                                        <?php endif; ?>
                                        alt="<?php echo esc_attr(get_the_title()); ?>"
                                        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'%3E%3C/svg%3E">
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
                    
                    <div class="post-content">
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
                        
                        <h2 class="post-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        
                        <div class="post-meta">
                            <span class="post-date"><?php echo get_the_date(); ?></span>
                            <span class="post-author">by <?php the_author(); ?></span>
                        </div>
                        
                        <div class="post-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <a href="<?php the_permalink(); ?>" class="read-more">
                            <?php echo esc_html(get_option('ajax_post_filter_read_more_text', 'Read More')); ?>
                        </a>
                    </div>
                </article>
                <?php
            }
            $response['posts'] = ob_get_clean();
            
            // Pagination or Load More
            if ($query->max_num_pages > 1) {
                ob_start();
                
                if ($pagination_type === 'load_more') {
                    // Load More Button
                    if ($paged < $query->max_num_pages) {
                        $load_more_text = get_option('ajax_post_filter_load_more_text', 'Load More');
                        ?>
                        <div class="load-more-wrapper">
                            <button type="button" class="button load-more-btn" data-page="<?php echo ($paged + 1); ?>">
                                <?php echo esc_html($load_more_text); ?>
                            </button>
                        </div>
                        <?php
                    }
                } else if ($pagination_type === 'pagination') {
                    // Standard Pagination with SEO
                    echo '<div class="pagination">';
                    echo paginate_links(array(
                        'total' => $query->max_num_pages,
                        'current' => $paged,
                        'format' => '?paged=%#%',
                        'prev_text' => '&laquo; Previous',
                        'next_text' => 'Next &raquo;',
                    ));
                    echo '</div>';
                }
                // Infinite scroll doesn't need pagination HTML
                
                $response['pagination'] = ob_get_clean();
            }
        } else {
            $response['posts'] = '<div class="no-posts">No posts found matching your criteria.</div>';
        }
        
        wp_reset_postdata();
        
        wp_send_json_success($response);
    }
}
