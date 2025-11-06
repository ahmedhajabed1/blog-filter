jQuery(document).ready(function($) {
    'use strict';
    
    let currentPage = 1;
    let isLoading = false;
    let maxPages = 1;
    
    // Get configuration from wrapper - READ ALL SETTINGS
    const $wrapper = $('#post-product-filter-wrapper');
    
    if (!$wrapper.length) {
        return; // No filter on this page
    }
    
    const presetType = $wrapper.data('preset-type') || 'posts';
    const presetSlug = $wrapper.data('preset-slug') || ''; // CRITICAL: Get preset slug
    const lazyLoad = $wrapper.data('lazy-load') !== false;
    const paginationType = $wrapper.data('pagination-type') || 'pagination';
    const postsPerPage = $wrapper.data('posts-per-page') || 6;
    const loadMoreText = $wrapper.data('load-more-text') || 'Load More';
    const loadingText = $wrapper.data('loading-text') || 'Loading...';
    const showExcerpt = $wrapper.data('show-excerpt') === true;
    const showReadMore = $wrapper.data('show-read-more') === true;
    const showMeta = $wrapper.data('show-meta') === true;
    const showCategories = $wrapper.data('show-categories') === true;
    const readMoreText = $wrapper.data('read-more-text') || 'Read More';
    const addToCartText = $wrapper.data('add-to-cart-text') || 'Add to Cart';
    const hideOutOfStock = $wrapper.data('hide-out-of-stock') === true;
    
    console.log('Filter Settings Loaded:', {
        presetType: presetType,
        presetSlug: presetSlug, // Log preset slug
        postsPerPage: postsPerPage,
        paginationType: paginationType,
        lazyLoad: lazyLoad,
        showExcerpt: showExcerpt,
        showReadMore: showReadMore,
        showMeta: showMeta,
        showCategories: showCategories
    });
    
    // Initialize - Load all posts on page load
    loadPosts();
    
    // Auto-apply filters on checkbox change
    $('.auto-filter').on('change', function() {
        currentPage = 1;
        loadPosts();
        updateActiveFilters();
    });
    
    // Category search functionality
    $('#category-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.filter-option').each(function() {
            const categoryName = $(this).data('category-name');
            if (categoryName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Pagination click handler
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const page = getParameterByName('paged', url) || 1;
        currentPage = parseInt(page);
        loadPosts();
        
        // Scroll to top of posts
        $('html, body').animate({
            scrollTop: $('#posts-grid').offset().top - 100
        }, 500);
    });
    
    // Load More button handler
    $(document).on('click', '.load-more-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        currentPage = parseInt($btn.data('page'));
        loadPosts(true); // true = append mode
    });
    
    // Infinite Scroll
    if (paginationType === 'infinite') {
        let scrollTimeout;
        $(window).on('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                if (isLoading || currentPage >= maxPages) return;
                
                const scrollPosition = $(window).scrollTop() + $(window).height();
                const triggerPosition = $(document).height() - 500;
                
                if (scrollPosition > triggerPosition) {
                    currentPage++;
                    loadPosts(true); // append mode
                }
            }, 200);
        });
    }
    
    // Main function to load posts via AJAX
    function loadPosts(appendMode = false) {
        if (isLoading) return;
        
        isLoading = true;
        
        // Get selected categories
        const selectedCategories = [];
        $('input[name="category_filter"]:checked').each(function() {
            selectedCategories.push($(this).val());
        });
        
        // Show loading overlay
        if (!appendMode) {
            $('.loading-overlay').fadeIn(200);
            $('#posts-grid').addClass('loading');
        } else {
            // Show loading in load more button
            $('.load-more-btn').prop('disabled', true).html('<span class="spinner"></span> ' + loadingText);
        }
        
        console.log('AJAX Request:', {
            categories: selectedCategories,
            paged: currentPage,
            posts_per_page: postsPerPage,
            pagination_type: paginationType,
            preset_type: presetType,
            preset_slug: presetSlug // CRITICAL: Send preset slug
        });
        
        // AJAX request with ALL settings INCLUDING preset_slug
        $.ajax({
            url: postProductFilter.ajaxurl,
            type: 'POST',
            data: {
                action: 'filter_posts',
                nonce: postProductFilter.nonce,
                categories: selectedCategories,
                preset_slug: presetSlug, // CRITICAL: Pass preset slug
                paged: currentPage,
                posts_per_page: postsPerPage,
                lazy_load: lazyLoad,
                pagination_type: paginationType,
                preset_type: presetType,
                show_excerpt: showExcerpt,
                show_read_more: showReadMore,
                show_meta: showMeta,
                show_categories: showCategories,
                read_more_text: readMoreText,
                add_to_cart_text: addToCartText,
                hide_out_of_stock: hideOutOfStock,
                load_more_text: loadMoreText
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                
                if (response.success) {
                    maxPages = response.data.max_pages;
                    
                    if (appendMode) {
                        // Append new posts
                        $('#posts-grid').append(response.data.posts);
                        
                        // Initialize lazy load for new images
                        if (lazyLoad) {
                            initLazyLoad();
                        }
                        
                        // Update pagination/load more
                        $('#posts-pagination').html(response.data.pagination);
                        
                        // Animate new posts
                        const newPosts = $('.post-item, .product-item').slice(-postsPerPage);
                        newPosts.each(function(index) {
                            $(this).css('opacity', '0').delay(index * 50).animate({
                                opacity: 1
                            }, 300);
                        });
                    } else {
                        // Replace posts
                        $('#posts-grid').fadeOut(200, function() {
                            $(this).html(response.data.posts).fadeIn(400);
                            
                            // Update pagination
                            $('#posts-pagination').html(response.data.pagination);
                            
                            // Update results count
                            $('#results-count').text(response.data.found_posts);
                            
                            // Hide loading overlay
                            $('.loading-overlay').fadeOut(200);
                            $('#posts-grid').removeClass('loading');
                            
                            // Initialize lazy load
                            if (lazyLoad) {
                                initLazyLoad();
                            }
                            
                            // Animate posts entrance
                            $('.post-item, .product-item').each(function(index) {
                                $(this).css('opacity', '0').delay(index * 50).animate({
                                    opacity: 1
                                }, 300);
                            });
                        });
                    }
                    
                    isLoading = false;
                } else {
                    console.error('AJAX Error:', response.data);
                    handleAjaxError(appendMode);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                handleAjaxError(appendMode);
            }
        });
    }
    
    // Handle AJAX errors
    function handleAjaxError(appendMode) {
        $('.loading-overlay').fadeOut(200);
        $('#posts-grid').removeClass('loading');
        
        if (!appendMode) {
            $('#posts-grid').html(
                '<div class="error-message" style="padding: 40px 20px; text-align: center; background: #f8d7da; border: 1px solid #dc3545; border-radius: 8px; color: #721c24;">' +
                '<p style="margin: 0; font-size: 16px;"><strong>⚠️ An error occurred</strong></p>' +
                '<p style="margin: 10px 0 0 0;">Please try again or refresh the page.</p>' +
                '</div>'
            );
        } else {
            $('.load-more-btn').prop('disabled', false).text(loadMoreText);
        }
        
        isLoading = false;
    }
    
    // Lazy Load Images
    function initLazyLoad() {
        if (!lazyLoad) return;
        
        // Native lazy loading support
        if ('loading' in HTMLImageElement.prototype) {
            const images = document.querySelectorAll('img.lazy-load');
            images.forEach(img => {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                    }
                    img.classList.remove('lazy-load');
                    img.classList.add('lazy-loaded');
                }
            });
        } else {
            // Intersection Observer for older browsers
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            if (img.dataset.srcset) {
                                img.srcset = img.dataset.srcset;
                            }
                            img.classList.remove('lazy-load');
                            img.classList.add('lazy-loaded');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            document.querySelectorAll('img.lazy-load').forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    }
    
    // Update active filters display
    function updateActiveFilters() {
        const activeFilters = [];
        
        $('input[name="category_filter"]:checked').each(function() {
            const label = $(this).siblings('.filter-label').clone();
            label.find('.filter-count').remove();
            const categoryName = label.text().trim();
            const categoryId = $(this).val();
            
            activeFilters.push({
                id: categoryId,
                name: categoryName
            });
        });
        
        if (activeFilters.length > 0) {
            let html = '';
            activeFilters.forEach(function(filter) {
                html += `
                    <span class="active-filter-tag" data-category-id="${filter.id}">
                        ${filter.name}
                        <button class="remove-filter" data-category-id="${filter.id}">×</button>
                    </span>
                `;
            });
            
            $('#active-filters-list').html(html);
            $('.active-filters').slideDown(200);
        } else {
            $('.active-filters').slideUp(200);
        }
    }
    
    // Remove individual filter
    $(document).on('click', '.remove-filter', function(e) {
        e.preventDefault();
        const categoryId = $(this).data('category-id');
        $(`input[name="category_filter"][value="${categoryId}"]`).prop('checked', false).trigger('change');
    });
    
    // Helper function to get URL parameter
    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, '\\$&');
        const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
        const results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
    
    // Initialize lazy load on page load
    if (lazyLoad) {
        $(window).on('load', function() {
            initLazyLoad();
        });
    }
    
    // Initialize active filters on page load
    updateActiveFilters();
});
