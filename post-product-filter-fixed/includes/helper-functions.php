<?php
/**
 * Helper Functions - SECURITY HARDENED v1.1.0
 * - FIXED: CSS injection with whitelist-based parser
 * - FIXED: Enhanced category validation
 * - ADDED: Security logging
 * - ADDED: More styling options (all secured)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fallback for sanitize_hex_color if not available
 */
if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color) {
        if ('' === $color) {
            return '';
        }
        
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        
        return '';
    }
}

/**
 * SECURITY HARDENED: Sanitize CSS with WHITELIST approach
 * This prevents ALL CSS injection attacks including encoded variants
 */
function post_product_filter_sanitize_css($css) {
    if (empty($css)) {
        return '';
    }
    
    // Strip all tags first
    $css = wp_strip_all_tags($css);
    
    // Remove ALL potentially dangerous patterns (comprehensive list)
    $dangerous_patterns = array(
        // JavaScript execution
        '/javascript\s*:/i',
        '/j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*:/i', // spaced
        '/\x6a\x61\x76\x61\x73\x63\x72\x69\x70\x74:/i', // hex encoded
        '/&#[x]?[0-9a-f]+;/i', // HTML entities
        
        // Expression/behavior (IE)
        '/expression\s*\(/i',
        '/behaviour\s*:/i',
        '/binding\s*:/i',
        '/-moz-binding/i',
        
        // VBScript
        '/vbscript\s*:/i',
        
        // Data URIs (all forms)
        '/url\s*\(\s*["\']?\s*data:/i',
        '/src\s*=\s*["\']?\s*data:/i',
        
        // Import statements
        '/@import/i',
        '/@charset/i',
        
        // External resources (ALL forms)
        '/url\s*\(\s*["\']?https?:/i',
        '/url\s*\(\s*["\']?\/\//i',
        
        // Base64
        '/base64/i',
        
        // Font-face with external URLs
        '/@font-face[^}]*url\s*\(/i',
    );
    
    foreach ($dangerous_patterns as $pattern) {
        $css = preg_replace($pattern, '', $css);
    }
    
    // Remove ALL url() functions completely
    $css = preg_replace('/url\s*\([^)]*\)/i', '', $css);
    
    // Whitelist: Only allow safe CSS properties
    $allowed_properties = array(
        'color', 'background-color', 'background', 'border-color',
        'font-size', 'font-weight', 'font-family', 'font-style',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'border', 'border-width', 'border-style', 'border-radius',
        'width', 'height', 'max-width', 'max-height', 'min-width', 'min-height',
        'display', 'position', 'top', 'right', 'bottom', 'left',
        'text-align', 'text-decoration', 'text-transform',
        'line-height', 'letter-spacing',
        'opacity', 'visibility',
        'flex', 'flex-direction', 'justify-content', 'align-items',
        'grid', 'grid-template-columns', 'gap',
        'transition', 'transform',
        'box-shadow', 'text-shadow',
        'overflow', 'overflow-x', 'overflow-y'
    );
    
    // Parse CSS and validate each rule
    $validated_css = '';
    $rules = explode('}', $css);
    
    foreach ($rules as $rule) {
        if (empty(trim($rule))) {
            continue;
        }
        
        $parts = explode('{', $rule, 2);
        if (count($parts) !== 2) {
            continue;
        }
        
        $selector = trim($parts[0]);
        $declarations = trim($parts[1]);
        
        // Validate selector (only allow simple selectors)
        if (!preg_match('/^[a-zA-Z0-9\s\-_#.,:>+~\[\]="\'*()]+$/', $selector)) {
            continue;
        }
        
        // Validate declarations
        $validated_declarations = array();
        $declaration_parts = explode(';', $declarations);
        
        foreach ($declaration_parts as $declaration) {
            if (empty(trim($declaration))) {
                continue;
            }
            
            $prop_value = explode(':', $declaration, 2);
            if (count($prop_value) !== 2) {
                continue;
            }
            
            $property = trim($prop_value[0]);
            $value = trim($prop_value[1]);
            
            // Only allow whitelisted properties
            if (!in_array(strtolower($property), $allowed_properties, true)) {
                continue;
            }
            
            // Validate value (no scripts, expressions, or functions)
            if (preg_match('/expression|javascript|vbscript|data:|@import/i', $value)) {
                continue;
            }
            
            // Allow only safe values
            if (preg_match('/^[a-zA-Z0-9\s\-_#%.,()!]+$/', $value)) {
                $validated_declarations[] = $property . ': ' . $value;
            }
        }
        
        if (!empty($validated_declarations)) {
            $validated_css .= $selector . ' { ' . implode('; ', $validated_declarations) . '; } ';
        }
    }
    
    return sanitize_textarea_field($validated_css);
}

/**
 * Log security events
 */
function post_product_filter_log_security_event($event_type, $details = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ppf_security_log';
    
    $user_id = get_current_user_id();
    $ip_address = post_product_filter_get_user_ip();
    
    $wpdb->insert(
        $table_name,
        array(
            'event_type' => sanitize_key($event_type),
            'user_id' => $user_id > 0 ? $user_id : null,
            'ip_address' => $ip_address,
            'details' => sanitize_text_field($details)
        ),
        array('%s', '%d', '%s', '%s')
    );
}

/**
 * Get user IP address securely
 */
function post_product_filter_get_user_ip() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Get first IP if multiple
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // Validate IP
    $ip = filter_var($ip, FILTER_VALIDATE_IP);
    return $ip ? $ip : '0.0.0.0';
}

/**
 * SECURITY ENHANCED: Save preset with comprehensive validation
 */
function post_product_filter_save_preset() {
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], POST_PRODUCT_FILTER_SAVE_NONCE)) {
        post_product_filter_log_security_event('save_preset_failed', 'Invalid nonce');
        return false;
    }
    
    // Verify POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        post_product_filter_log_security_event('save_preset_failed', 'Invalid request method');
        return false;
    }
    
    // Verify capabilities
    if (!current_user_can('manage_options')) {
        post_product_filter_log_security_event('save_preset_failed', 'Insufficient permissions');
        return false;
    }
    
    $presets = get_option('post_product_filter_presets', array());
    
    // Limit number of presets to prevent DoS
    if (count($presets) >= 50 && empty($_POST['preset_slug'])) {
        return false;
    }
    
    // SECURITY ENHANCED: Sanitize and validate preset slug
    $preset_slug = '';
    
    if (isset($_POST['preset_slug']) && !empty($_POST['preset_slug'])) {
        // Editing existing preset - use existing slug
        $preset_slug = sanitize_key($_POST['preset_slug']);
    } elseif (isset($_POST['preset_slug_field']) && !empty($_POST['preset_slug_field'])) {
        // New preset with manual slug
        $preset_slug = sanitize_key($_POST['preset_slug_field']);
        
        // Additional validation for slug
        if (!preg_match('/^[a-z0-9\-]+$/', $preset_slug)) {
            post_product_filter_log_security_event('invalid_slug_attempt', 'Invalid characters in slug: ' . $preset_slug);
            return false;
        }
        
        // Check if slug already exists
        if (isset($presets[$preset_slug])) {
            post_product_filter_log_security_event('duplicate_slug_attempt', 'Slug already exists: ' . $preset_slug);
            return false;
        }
        
        // Length validation
        if (strlen($preset_slug) < 3 || strlen($preset_slug) > 50) {
            post_product_filter_log_security_event('invalid_slug_length', 'Slug length invalid: ' . strlen($preset_slug));
            return false;
        }
    } else {
        // Fallback: generate from name
        $preset_name_temp = isset($_POST['preset_name']) ? sanitize_text_field($_POST['preset_name']) : '';
        if (!empty($preset_name_temp)) {
            $preset_slug = sanitize_key(str_replace(' ', '-', strtolower($preset_name_temp)));
        }
    }
    
    // Final slug validation
    if (empty($preset_slug) || !preg_match('/^[a-z0-9\-]{3,50}$/', $preset_slug)) {
        post_product_filter_log_security_event('preset_save_failed', 'Invalid or empty slug');
        return false;
    }
    
    // Sanitize and validate preset name
    $preset_name = isset($_POST['preset_name']) && !empty($_POST['preset_name']) 
        ? substr(sanitize_text_field($_POST['preset_name']), 0, 100)
        : '';
    
    if (empty($preset_name)) {
        post_product_filter_log_security_event('preset_save_failed', 'Empty preset name');
        return false;
    }
    
    // SECURITY ENHANCEMENT: Check for suspicious patterns in preset name
    if (preg_match('/<script|javascript:|on\w+\s*=|data:|vbscript:/i', $preset_name)) {
        post_product_filter_log_security_event('suspicious_preset_name', 'Potential XSS attempt blocked');
        return false;
    }
    
    // Validate preset type
    $preset_type = isset($_POST['preset_type']) ? sanitize_key($_POST['preset_type']) : 'posts';
    $allowed_types = array('posts', 'products');
    if (!in_array($preset_type, $allowed_types, true)) {
        $preset_type = 'posts';
    }
    
    // Validate pagination type
    $pagination_type = isset($_POST['pagination_type']) ? sanitize_key($_POST['pagination_type']) : 'pagination';
    $allowed_pagination = array('pagination', 'load_more', 'infinite');
    if (!in_array($pagination_type, $allowed_pagination, true)) {
        $pagination_type = 'pagination';
    }
    
    // Validate columns
    $columns = isset($_POST['columns']) ? sanitize_key($_POST['columns']) : '2';
    $allowed_columns = array('2', '3', '4');
    if (!in_array($columns, $allowed_columns, true)) {
        $columns = '2';
    }
    
    // SECURITY ENHANCED: Validate selected categories with database check
    $selected_categories = isset($_POST['selected_categories']) && is_array($_POST['selected_categories']) 
        ? array_map('absint', $_POST['selected_categories']) 
        : array();
    
    $valid_categories = array();
    if (!empty($selected_categories)) {
        $taxonomy = ($preset_type === 'products' && class_exists('WooCommerce')) ? 'product_cat' : 'category';
        
        foreach ($selected_categories as $cat_id) {
            // Enhanced validation
            if ($cat_id > 0 && $cat_id < PHP_INT_MAX) {
                $term = get_term($cat_id, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $valid_categories[] = $cat_id;
                }
            }
        }
    }
    
    // Validate and bound numeric inputs
    $posts_per_page = isset($_POST['posts_per_page']) ? absint($_POST['posts_per_page']) : 6;
    $posts_per_page = max(1, min(100, $posts_per_page));
    
    $title_font_size = isset($_POST['title_font_size']) ? absint($_POST['title_font_size']) : 20;
    $title_font_size = max(10, min(60, $title_font_size));
    
    $container_padding = isset($_POST['container_padding']) ? absint($_POST['container_padding']) : 40;
    $container_padding = max(0, min(100, $container_padding));
    
    $item_spacing = isset($_POST['item_spacing']) ? absint($_POST['item_spacing']) : 30;
    $item_spacing = max(0, min(100, $item_spacing));
    
    $category_font_size = isset($_POST['category_font_size']) ? absint($_POST['category_font_size']) : 12;
    $category_font_size = max(10, min(24, $category_font_size));
    
    $result_count_font_size = isset($_POST['result_count_font_size']) ? absint($_POST['result_count_font_size']) : 16;
    $result_count_font_size = max(12, min(24, $result_count_font_size));
    
    $filter_title_font_size = isset($_POST['filter_title_font_size']) ? absint($_POST['filter_title_font_size']) : 18;
    $filter_title_font_size = max(12, min(32, $filter_title_font_size));
    
    // Sanitize text fields with length limits
    $form_title = isset($_POST['form_title']) ? substr(sanitize_text_field($_POST['form_title']), 0, 100) : 'Filter by Categories';
    $load_more_text = isset($_POST['load_more_text']) ? substr(sanitize_text_field($_POST['load_more_text']), 0, 50) : 'Load More';
    $loading_text = isset($_POST['loading_text']) ? substr(sanitize_text_field($_POST['loading_text']), 0, 50) : 'Loading...';
    $read_more_text = isset($_POST['read_more_text']) ? substr(sanitize_text_field($_POST['read_more_text']), 0, 50) : 'Read More';
    $add_to_cart_text = isset($_POST['add_to_cart_text']) ? substr(sanitize_text_field($_POST['add_to_cart_text']), 0, 50) : 'Add to Cart';
    
    // SECURITY HARDENED: Sanitize custom CSS with whitelist parser
    $custom_css = isset($_POST['custom_css']) ? post_product_filter_sanitize_css($_POST['custom_css']) : '';
    
    // Build settings array
    $settings = array(
        'preset_type' => $preset_type,
        'posts_per_page' => $posts_per_page,
        'pagination_type' => $pagination_type,
        'columns' => $columns,
        'lazy_load' => isset($_POST['lazy_load']),
        'show_search' => isset($_POST['show_search']),
        'show_count' => isset($_POST['show_count']),
        'show_excerpt' => isset($_POST['show_excerpt']),
        'show_read_more' => isset($_POST['show_read_more']),
        'show_meta' => isset($_POST['show_meta']),
        'show_categories' => isset($_POST['show_categories']),
        'selected_categories' => $valid_categories,
        'form_title' => $form_title,
        'hide_out_of_stock' => isset($_POST['hide_out_of_stock']),
        'show_price' => isset($_POST['show_price']),
        'show_add_to_cart' => isset($_POST['show_add_to_cart']),
        
        // Typography (all bounded and validated)
        'title_font_size' => $title_font_size,
        'category_font_size' => $category_font_size,
        'result_count_font_size' => $result_count_font_size,
        'filter_title_font_size' => $filter_title_font_size,
        
        // Colors (all validated as hex)
        'title_color' => isset($_POST['title_color']) ? sanitize_hex_color($_POST['title_color']) : '#333333',
        'title_hover_color' => isset($_POST['title_hover_color']) ? sanitize_hex_color($_POST['title_hover_color']) : '#2271b1',
        'price_color' => isset($_POST['price_color']) ? sanitize_hex_color($_POST['price_color']) : '#333333',
        'sale_price_color' => isset($_POST['sale_price_color']) ? sanitize_hex_color($_POST['sale_price_color']) : '#ff0000',
        
        // Read More button styling
        'button_color' => isset($_POST['button_color']) ? sanitize_hex_color($_POST['button_color']) : '#2271b1',
        'button_text_color' => isset($_POST['button_text_color']) ? sanitize_hex_color($_POST['button_text_color']) : '#ffffff',
        'button_hover_color' => isset($_POST['button_hover_color']) ? sanitize_hex_color($_POST['button_hover_color']) : '#135e96',
        
        // NEW: Load More button styling (SECURE)
        'load_more_bg_color' => isset($_POST['load_more_bg_color']) ? sanitize_hex_color($_POST['load_more_bg_color']) : '#2271b1',
        'load_more_text_color' => isset($_POST['load_more_text_color']) ? sanitize_hex_color($_POST['load_more_text_color']) : '#ffffff',
        'load_more_hover_color' => isset($_POST['load_more_hover_color']) ? sanitize_hex_color($_POST['load_more_hover_color']) : '#135e96',
        
        // NEW: Category filter styling (SECURE)
        'filter_bg_color' => isset($_POST['filter_bg_color']) ? sanitize_hex_color($_POST['filter_bg_color']) : '#ffffff',
        'filter_border_color' => isset($_POST['filter_border_color']) ? sanitize_hex_color($_POST['filter_border_color']) : '#e5e5e5',
        'filter_title_color' => isset($_POST['filter_title_color']) ? sanitize_hex_color($_POST['filter_title_color']) : '#333333',
        'category_item_bg_color' => isset($_POST['category_item_bg_color']) ? sanitize_hex_color($_POST['category_item_bg_color']) : '#f0f0f0',
        'category_item_text_color' => isset($_POST['category_item_text_color']) ? sanitize_hex_color($_POST['category_item_text_color']) : '#666666',
        'category_item_hover_bg' => isset($_POST['category_item_hover_bg']) ? sanitize_hex_color($_POST['category_item_hover_bg']) : '#f8f9fa',
        
        // NEW: Result count styling (SECURE)
        'result_count_color' => isset($_POST['result_count_color']) ? sanitize_hex_color($_POST['result_count_color']) : '#666666',
        
        // Add to Cart styling
        'add_to_cart_bg_color' => isset($_POST['add_to_cart_bg_color']) ? sanitize_hex_color($_POST['add_to_cart_bg_color']) : '#2271b1',
        'add_to_cart_text_color' => isset($_POST['add_to_cart_text_color']) ? sanitize_hex_color($_POST['add_to_cart_text_color']) : '#ffffff',
        'add_to_cart_hover_color' => isset($_POST['add_to_cart_hover_color']) ? sanitize_hex_color($_POST['add_to_cart_hover_color']) : '#135e96',
        
        // NEW: Spacing/Padding (SECURE - bounded)
        'container_padding' => $container_padding,
        'item_spacing' => $item_spacing,
        
        // Button text
        'load_more_text' => $load_more_text,
        'loading_text' => $loading_text,
        'read_more_text' => $read_more_text,
        'add_to_cart_text' => $add_to_cart_text,
        
        // Custom CSS (sanitized with whitelist parser)
        'custom_css' => $custom_css
    );
    
    $presets[$preset_slug] = array(
        'name' => $preset_name,
        'slug' => $preset_slug,
        'settings' => $settings
    );
    
    update_option('post_product_filter_presets', $presets);
    
    post_product_filter_log_security_event('preset_saved', 'Preset: ' . $preset_slug);
    
    return true;
}

/**
 * Delete preset with enhanced security
 */
function post_product_filter_delete_preset($preset_slug) {
    if (!current_user_can('manage_options')) {
        post_product_filter_log_security_event('delete_preset_failed', 'Insufficient permissions');
        return false;
    }
    
    $preset_slug = sanitize_key($preset_slug);
    $presets = get_option('post_product_filter_presets', array());
    
    if (isset($presets[$preset_slug])) {
        unset($presets[$preset_slug]);
        update_option('post_product_filter_presets', $presets);
        post_product_filter_log_security_event('preset_deleted', 'Preset: ' . $preset_slug);
        return true;
    }
    
    return false;
}

/**
 * Output custom CSS with NEW STYLING OPTIONS (ALL SECURE)
 */
function post_product_filter_custom_css() {
    $presets = get_option('post_product_filter_presets', array());
    
    if (empty($presets)) {
        return;
    }
    
    echo '<style type="text/css" id="post-product-filter-custom-css">';
    
    foreach ($presets as $slug => $preset) {
        if (!isset($preset['settings'])) {
            continue;
        }
        
        $settings = $preset['settings'];
        $selector = '.post-product-filter-' . esc_attr($slug);
        
        // Container padding
        if (isset($settings['container_padding'])) {
            echo esc_html($selector) . ' #post-product-filter-wrapper { padding: ' . absint($settings['container_padding']) . 'px !important; }';
        }
        
        // Item spacing
        if (isset($settings['item_spacing'])) {
            echo esc_html($selector) . ' .posts-grid { gap: ' . absint($settings['item_spacing']) . 'px !important; }';
        }
        
        // Title styling
        if (!empty($settings['title_font_size'])) {
            echo esc_html($selector) . ' .post-title, ' . esc_html($selector) . ' .product-title { font-size: ' . absint($settings['title_font_size']) . 'px !important; }';
        }
        if (!empty($settings['title_color'])) {
            echo esc_html($selector) . ' .post-title a, ' . esc_html($selector) . ' .product-title a { color: ' . sanitize_hex_color($settings['title_color']) . ' !important; }';
        }
        if (!empty($settings['title_hover_color'])) {
            echo esc_html($selector) . ' .post-title a:hover, ' . esc_html($selector) . ' .product-title a:hover { color: ' . sanitize_hex_color($settings['title_hover_color']) . ' !important; }';
        }
        
        // Filter widget styling
        if (isset($settings['filter_title_font_size'])) {
            echo esc_html($selector) . ' .filter-title { font-size: ' . absint($settings['filter_title_font_size']) . 'px !important; }';
        }
        if (!empty($settings['filter_bg_color'])) {
            echo esc_html($selector) . ' .filter-widget { background-color: ' . sanitize_hex_color($settings['filter_bg_color']) . ' !important; }';
        }
        if (!empty($settings['filter_border_color'])) {
            echo esc_html($selector) . ' .filter-widget { border-color: ' . sanitize_hex_color($settings['filter_border_color']) . ' !important; }';
        }
        if (!empty($settings['filter_title_color'])) {
            echo esc_html($selector) . ' .filter-title { color: ' . sanitize_hex_color($settings['filter_title_color']) . ' !important; }';
        }
        
        // Category badge styling
        if (isset($settings['category_font_size'])) {
            echo esc_html($selector) . ' .post-category, ' . esc_html($selector) . ' .product-category { font-size: ' . absint($settings['category_font_size']) . 'px !important; }';
        }
        if (!empty($settings['category_item_bg_color'])) {
            echo esc_html($selector) . ' .post-category, ' . esc_html($selector) . ' .product-category { background-color: ' . sanitize_hex_color($settings['category_item_bg_color']) . ' !important; }';
        }
        if (!empty($settings['category_item_text_color'])) {
            echo esc_html($selector) . ' .post-category, ' . esc_html($selector) . ' .product-category { color: ' . sanitize_hex_color($settings['category_item_text_color']) . ' !important; }';
        }
        if (!empty($settings['category_item_hover_bg'])) {
            echo esc_html($selector) . ' .filter-option:hover { background-color: ' . sanitize_hex_color($settings['category_item_hover_bg']) . ' !important; }';
        }
        
        // Result count styling
        if (isset($settings['result_count_font_size'])) {
            echo esc_html($selector) . ' .results-count { font-size: ' . absint($settings['result_count_font_size']) . 'px !important; }';
        }
        if (!empty($settings['result_count_color'])) {
            echo esc_html($selector) . ' .results-count, ' . esc_html($selector) . ' #results-count { color: ' . sanitize_hex_color($settings['result_count_color']) . ' !important; }';
        }
        
        // Read More button
        if (!empty($settings['button_color'])) {
            echo esc_html($selector) . ' .read-more { background-color: ' . sanitize_hex_color($settings['button_color']) . ' !important; }';
        }
        if (!empty($settings['button_text_color'])) {
            echo esc_html($selector) . ' .read-more { color: ' . sanitize_hex_color($settings['button_text_color']) . ' !important; }';
        }
        if (!empty($settings['button_hover_color'])) {
            echo esc_html($selector) . ' .read-more:hover { background-color: ' . sanitize_hex_color($settings['button_hover_color']) . ' !important; }';
        }
        
        // Load More button
        if (!empty($settings['load_more_bg_color'])) {
            echo esc_html($selector) . ' .load-more-btn { background-color: ' . sanitize_hex_color($settings['load_more_bg_color']) . ' !important; }';
        }
        if (!empty($settings['load_more_text_color'])) {
            echo esc_html($selector) . ' .load-more-btn { color: ' . sanitize_hex_color($settings['load_more_text_color']) . ' !important; }';
        }
        if (!empty($settings['load_more_hover_color'])) {
            echo esc_html($selector) . ' .load-more-btn:hover { background-color: ' . sanitize_hex_color($settings['load_more_hover_color']) . ' !important; }';
        }
        
        // Price styling
        if (!empty($settings['price_color'])) {
            echo esc_html($selector) . ' .product-price { color: ' . sanitize_hex_color($settings['price_color']) . ' !important; }';
        }
        if (!empty($settings['sale_price_color'])) {
            echo esc_html($selector) . ' .product-price ins { color: ' . sanitize_hex_color($settings['sale_price_color']) . ' !important; }';
        }
        
        // Add to Cart button
        if (!empty($settings['add_to_cart_bg_color'])) {
            echo esc_html($selector) . ' .add_to_cart_button { background-color: ' . sanitize_hex_color($settings['add_to_cart_bg_color']) . ' !important; }';
        }
        if (!empty($settings['add_to_cart_text_color'])) {
            echo esc_html($selector) . ' .add_to_cart_button { color: ' . sanitize_hex_color($settings['add_to_cart_text_color']) . ' !important; }';
        }
        if (!empty($settings['add_to_cart_hover_color'])) {
            echo esc_html($selector) . ' .add_to_cart_button:hover { background-color: ' . sanitize_hex_color($settings['add_to_cart_hover_color']) . ' !important; }';
        }
        
        // Custom CSS (already sanitized)
        if (!empty($settings['custom_css'])) {
            echo "\n" . wp_kses_post($settings['custom_css']) . "\n";
        }
    }
    
    echo '</style>';
}

require_once POST_PRODUCT_FILTER_PATH . 'includes/helper-functions-render.php';
