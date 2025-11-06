jQuery(document).ready(function($) {
    'use strict';
    
    // Tab Navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).attr('href');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target content, hide others
        $('.tab-content').hide();
        $(targetTab).show();
    });
    
    // Initialize color pickers
    if ($.fn.wpColorPicker) {
        $('.color-picker').wpColorPicker({
            change: function(event, ui) {
                // Optional: Preview color changes
                console.log('Color changed:', ui.color.toString());
            }
        });
    }
    
    // Category selector filtering (if search is added)
    $('#category-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        $('.category-selector label').each(function() {
            var categoryName = $(this).text().toLowerCase();
            if (categoryName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // =====================================================
    // COMPREHENSIVE CONDITIONAL DISPLAY SYSTEM
    // Each styling section shows ONLY when its feature is enabled
    // =====================================================
    
    function updateConditionalDisplay() {
        // Get current values
        var presetType = $('#preset_type').val();
        var paginationType = $('select[name="pagination_type"]').val();
        
        // Display tab checkboxes
        var showCategories = $('input[name="show_categories"]').is(':checked');
        var showReadMore = $('input[name="show_read_more"]').is(':checked');
        var showCount = $('input[name="show_count"]').is(':checked');
        var showSearch = $('input[name="show_search"]').is(':checked');
        var showPrice = $('input[name="show_price"]').is(':checked');
        var showAddToCart = $('input[name="show_add_to_cart"]').is(':checked');
        
        // Title Styling - Always visible
        // Filter Widget Styling - Show when search OR count is enabled
        if (showSearch || showCount) {
            $('[data-section="filter-widget"]').slideDown(300);
        } else {
            $('[data-section="filter-widget"]').slideUp(300);
        }
        
        // Category Items Styling - Show when categories are enabled
        if (showCategories) {
            $('[data-section="category-items"]').slideDown(300);
        } else {
            $('[data-section="category-items"]').slideUp(300);
        }
        
        // Result Count Styling - Show when count is enabled
        if (showCount) {
            $('[data-section="result-count"]').slideDown(300);
        } else {
            $('[data-section="result-count"]').slideUp(300);
        }
        
        // Read More Button - Show when read more is enabled
        if (showReadMore) {
            $('[data-section="read-more"]').slideDown(300);
        } else {
            $('[data-section="read-more"]').slideUp(300);
        }
        
        // Load More Button - Show when pagination is load_more or infinite
        if (paginationType === 'load_more' || paginationType === 'infinite') {
            $('[data-section="load-more"]').slideDown(300);
        } else {
            $('[data-section="load-more"]').slideUp(300);
        }
        
        // Product Styling - Show ONLY when:
        // 1. Preset type is products AND
        // 2. At least one product option is enabled (price OR add to cart)
        if (presetType === 'products' && (showPrice || showAddToCart)) {
            $('[data-section="product-styling"]').slideDown(300);
            
            // Show/hide individual product styling rows
            if (showPrice) {
                $('[data-row="price-color"]').show();
                $('[data-row="sale-price-color"]').show();
            } else {
                $('[data-row="price-color"]').hide();
                $('[data-row="sale-price-color"]').hide();
            }
            
            if (showAddToCart) {
                $('[data-row="add-to-cart-bg"]').show();
                $('[data-row="add-to-cart-text"]').show();
                $('[data-row="add-to-cart-hover"]').show();
            } else {
                $('[data-row="add-to-cart-bg"]').hide();
                $('[data-row="add-to-cart-text"]').hide();
                $('[data-row="add-to-cart-hover"]').hide();
            }
        } else {
            $('[data-section="product-styling"]').slideUp(300);
        }
    }
    
    // Attach event listeners to all relevant fields
    $('#preset_type').on('change', updateConditionalDisplay);
    $('select[name="pagination_type"]').on('change', updateConditionalDisplay);
    $('input[name="show_categories"]').on('change', updateConditionalDisplay);
    $('input[name="show_read_more"]').on('change', updateConditionalDisplay);
    $('input[name="show_count"]').on('change', updateConditionalDisplay);
    $('input[name="show_search"]').on('change', updateConditionalDisplay);
    $('input[name="show_price"]').on('change', updateConditionalDisplay);
    $('input[name="show_add_to_cart"]').on('change', updateConditionalDisplay);
    
    // Initialize on page load
    $(window).on('load', function() {
        updateConditionalDisplay();
    });
    
    // Also initialize immediately
    setTimeout(updateConditionalDisplay, 100);
    
    // Form validation
    $('.ppf-preset-form').on('submit', function(e) {
        var presetName = $('#preset_name').val().trim();
        
        if (presetName === '') {
            alert('Please enter a preset name.');
            e.preventDefault();
            return false;
        }
        
        // Add loading state
        $(this).addClass('loading');
        
        return true;
    });
    
    // Confirm delete
    $('.button-link-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this preset? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Select all / Deselect all for categories
    if ($('.category-selector').length > 0) {
        // Add select all/none buttons if needed
        var selectAllBtn = $('<button type="button" class="button button-small" style="margin-bottom: 10px; margin-right: 5px;">Select All</button>');
        var selectNoneBtn = $('<button type="button" class="button button-small" style="margin-bottom: 10px;">Deselect All</button>');
        
        selectAllBtn.on('click', function(e) {
            e.preventDefault();
            $('.category-selector input[type="checkbox"]').prop('checked', true);
        });
        
        selectNoneBtn.on('click', function(e) {
            e.preventDefault();
            $('.category-selector input[type="checkbox"]').prop('checked', false);
        });
        
        $('.category-selector').before(
            $('<div class="category-selector-actions"></div>')
                .append(selectAllBtn)
                .append(selectNoneBtn)
        );
    }
    
    // Auto-generate slug from name (for new presets only)
    if ($('#preset_slug_field').length > 0) {
        // Real-time slug preview
        $('#preset_slug_field').on('input', function() {
            var slug = $(this).val();
            $('#slug-preview').text(slug || 'your-slug');
        });
        
        // Auto-generate button
        $('#generate-slug-btn').on('click', function(e) {
            e.preventDefault();
            var name = $('#preset_name').val();
            if (name) {
                // Generate slug: lowercase, replace spaces with hyphens, remove special chars
                var slug = name.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                    .replace(/\s+/g, '-')          // Replace spaces with hyphens
                    .replace(/-+/g, '-')           // Replace multiple hyphens with single
                    .replace(/^-|-$/g, '');        // Remove leading/trailing hyphens
                
                if (slug) {
                    $('#preset_slug_field').val(slug).trigger('input');
                    
                    // Show feedback
                    var $btn = $(this);
                    var originalText = $btn.text();
                    $btn.text('✓ Generated!').prop('disabled', true);
                    
                    setTimeout(function() {
                        $btn.text(originalText).prop('disabled', false);
                    }, 2000);
                } else {
                    alert('Please enter a valid preset name first.');
                }
            } else {
                alert('Please enter a preset name first.');
                $('#preset_name').focus();
            }
        });
        
        // Auto-generate on preset name blur (optional)
        $('#preset_name').on('blur', function() {
            var currentSlug = $('#preset_slug_field').val();
            if (!currentSlug && $(this).val()) {
                $('#generate-slug-btn').trigger('click');
            }
        });
        
        // Validate slug input in real-time
        $('#preset_slug_field').on('input', function() {
            var slug = $(this).val();
            var validSlug = slug.toLowerCase()
                .replace(/[^a-z0-9\-]/g, ''); // Only allow lowercase letters, numbers, and hyphens
            
            if (slug !== validSlug) {
                $(this).val(validSlug);
            }
        });
    }
    
    // Shortcode copy functionality
    $('.wp-list-table input[readonly]').on('click', function() {
        $(this).select();
        
        // Try to copy to clipboard
        try {
            document.execCommand('copy');
            
            // Show feedback
            var $this = $(this);
            var originalValue = $this.val();
            $this.val('✓ Copied!');
            
            setTimeout(function() {
                $this.val(originalValue);
            }, 2000);
        } catch(err) {
            console.log('Unable to copy');
        }
    });
    
    // Number input validation
    $('input[type="number"]').on('change', function() {
        var $input = $(this);
        var min = parseInt($input.attr('min'));
        var max = parseInt($input.attr('max'));
        var value = parseInt($input.val());
        
        if (value < min) {
            $input.val(min);
            alert('Value must be at least ' + min);
        }
        
        if (value > max) {
            $input.val(max);
            alert('Value cannot exceed ' + max);
        }
    });
    
    // Auto-save indicator (optional enhancement)
    var formChanged = false;
    $('.ppf-preset-form input, .ppf-preset-form select, .ppf-preset-form textarea').on('change', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    $('.ppf-preset-form').on('submit', function() {
        formChanged = false;
    });
    
    // Tooltips for new features
    $('.new-badge').attr('title', 'New feature in v1.1.0!');
    
    // Console message
    console.log('Post/Product Filter Admin v1.1.0 loaded successfully');
    console.log('Security: Hardened ✅');
});
