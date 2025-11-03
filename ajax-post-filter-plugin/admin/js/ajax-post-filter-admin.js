jQuery(document).ready(function($) {
    'use strict';
    
    // Sidebar collapse functionality
    $('#collapse-sidebar').on('click', function(e) {
        e.preventDefault();
        $('.admin-sidebar').toggleClass('collapsed');
        
        if ($('.admin-sidebar').hasClass('collapsed')) {
            $(this).find('.dashicons').removeClass('dashicons-arrow-left-alt2').addClass('dashicons-arrow-right-alt2');
            $(this).find('span:not(.dashicons)').text('Expand');
        } else {
            $(this).find('.dashicons').removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-left-alt2');
            $(this).find('span:not(.dashicons)').text('Collapse');
        }
    });
    
    // Open Add Preset Modal
    $('#add-preset-btn').on('click', function() {
        $('#modal-title').text('Add New Preset');
        $('#preset-form')[0].reset();
        $('#preset_slug').val('');
        $('#preset-modal').fadeIn(200);
        $('body').addClass('modal-open');
    });
    
    // Edit Preset
    $('.edit-preset-btn').on('click', function() {
        const presetSlug = $(this).data('preset');
        
        // In a real implementation, you would fetch preset data via AJAX
        // For now, we'll just open the modal
        $('#modal-title').text('Edit Preset');
        $('#preset_slug').val(presetSlug);
        
        // Populate form with preset data (would come from AJAX in real implementation)
        const presetName = $(this).closest('tr').find('.preset-name strong').text();
        $('#preset_name').val(presetName);
        
        $('#preset-modal').fadeIn(200);
        $('body').addClass('modal-open');
    });
    
    // Close Modal
    $('.close-modal, .preset-modal').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Prevent modal content clicks from closing modal
    $('.preset-modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Close modal with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#preset-modal').is(':visible')) {
            closeModal();
        }
    });
    
    function closeModal() {
        $('#preset-modal').fadeOut(200);
        $('body').removeClass('modal-open');
        $('#preset-form')[0].reset();
    }
    
    // Copy Shortcode to Clipboard
    $('.copy-shortcode-btn').on('click', function() {
        const shortcode = $(this).data('shortcode');
        const button = $(this);
        
        // Create temporary input element
        const tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(shortcode).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Visual feedback
        const originalText = button.html();
        button.html('<span class="dashicons dashicons-yes"></span> Copied!');
        button.addClass('button-primary');
        
        setTimeout(function() {
            button.html(originalText);
            button.removeClass('button-primary');
        }, 2000);
    });
    
    // Toggle Switch Animation
    $('.toggle-switch input').on('change', function() {
        if ($(this).is(':checked')) {
            showNotice('Preset enabled', 'success');
        } else {
            showNotice('Preset disabled', 'info');
        }
    });
    
    // Form Validation
    $('#preset-form').on('submit', function(e) {
        const presetName = $('#preset_name').val().trim();
        
        if (presetName === '') {
            e.preventDefault();
            showNotice('Please enter a preset name', 'error');
            $('#preset_name').focus();
            return false;
        }
        
        // Show loading state
        $(this).find('button[type="submit"]').addClass('loading').prop('disabled', true);
    });
    
    // Auto-generate slug from preset name
    $('#preset_name').on('input', function() {
        if ($('#preset_slug').val() === '') {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
            $('#preset_slug').val(slug);
        }
    });
    
    // Search functionality for category select
    if ($('#selected_categories').length) {
        const searchInput = $('<input>', {
            type: 'text',
            class: 'widefat',
            placeholder: 'Search categories...',
            css: { marginBottom: '10px' }
        });
        
        searchInput.insertBefore('#selected_categories');
        
        searchInput.on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#selected_categories option').each(function() {
                const optionText = $(this).text().toLowerCase();
                $(this).toggle(optionText.includes(searchTerm));
            });
        });
    }
    
    // Color Picker Enhancement
    if ($('.color-picker').length) {
        $('.color-picker').each(function() {
            const colorPicker = $(this);
            const colorPreview = $('<div>', {
                class: 'color-preview',
                css: {
                    width: '30px',
                    height: '30px',
                    borderRadius: '4px',
                    border: '1px solid #dcdcde',
                    backgroundColor: colorPicker.val(),
                    display: 'inline-block',
                    marginLeft: '10px',
                    verticalAlign: 'middle'
                }
            });
            
            colorPicker.after(colorPreview);
            
            colorPicker.on('input change', function() {
                colorPreview.css('backgroundColor', $(this).val());
            });
        });
    }
    
    // Confirm before deleting preset
    $('.delete-preset-btn').on('click', function(e) {
        const presetName = $(this).closest('tr').find('.preset-name strong').text();
        
        if (!confirm('Are you sure you want to delete the preset "' + presetName + '"? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Reset Defaults Button
    $('button:contains("Reset Defaults")').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
            showNotice('Resetting to defaults...', 'info');
            
            // In a real implementation, this would make an AJAX call
            setTimeout(function() {
                showNotice('Settings reset to defaults successfully!', 'success');
            }, 1000);
        }
    });
    
    // Show Notice Function
    function showNotice(message, type) {
        type = type || 'info';
        
        const noticeClass = 'notice-' + type;
        const notice = $('<div>', {
            class: 'notice ' + noticeClass + ' is-dismissible',
            html: '<p>' + message + '</p>'
        });
        
        // Remove existing notices
        $('.notice').remove();
        
        // Add new notice
        if ($('.admin-header').length) {
            $('.admin-header').after(notice);
        } else {
            $('.admin-content').prepend(notice);
        }
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to notice
        $('html, body').animate({
            scrollTop: notice.offset().top - 100
        }, 300);
    }
    
    // Make notice dismissible
    $(document).on('click', '.notice.is-dismissible', function() {
        $(this).fadeOut(300, function() {
            $(this).remove();
        });
    });
    
    // Responsive table wrapper
    function makeTablesResponsive() {
        if ($(window).width() < 768) {
            $('.wp-list-table').each(function() {
                if (!$(this).parent().hasClass('table-responsive')) {
                    $(this).wrap('<div class="table-responsive"></div>');
                }
            });
        }
    }
    
    makeTablesResponsive();
    $(window).on('resize', makeTablesResponsive);
    
    // Mobile menu toggle
    if ($(window).width() < 992) {
        const mobileMenuToggle = $('<button>', {
            class: 'mobile-menu-toggle',
            html: '<span class="dashicons dashicons-menu"></span> Menu',
            css: {
                display: 'none',
                position: 'fixed',
                bottom: '20px',
                right: '20px',
                padding: '12px 20px',
                background: '#2271b1',
                color: '#fff',
                border: 'none',
                borderRadius: '4px',
                cursor: 'pointer',
                zIndex: '9999',
                boxShadow: '0 2px 8px rgba(0,0,0,0.2)'
            }
        });
        
        $('body').append(mobileMenuToggle);
        
        if ($(window).width() < 992) {
            mobileMenuToggle.show();
        }
        
        mobileMenuToggle.on('click', function() {
            $('.admin-sidebar').slideToggle(300);
        });
    }
    
    // Handle window resize
    $(window).on('resize', function() {
        if ($(window).width() >= 992) {
            $('.admin-sidebar').show();
            $('.mobile-menu-toggle').hide();
        } else {
            $('.mobile-menu-toggle').show();
        }
    });
    
    // Save button loading state
    $('.settings-form').on('submit', function() {
        $(this).find('.button-primary').addClass('loading').prop('disabled', true);
    });
    
    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Auto-save indicator
    let saveTimeout;
    $('.settings-form input, .settings-form select, .settings-form textarea').on('change', function() {
        clearTimeout(saveTimeout);
        
        const indicator = $('<span>', {
            class: 'save-indicator',
            text: 'Unsaved changes',
            css: {
                color: '#d63638',
                fontSize: '13px',
                marginLeft: '10px',
                fontWeight: '600'
            }
        });
        
        $('.admin-header h1 .save-indicator').remove();
        $('.admin-header h1').append(indicator);
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('.settings-form').submit();
            showNotice('Settings saved!', 'success');
        }
    });
    
    // Tooltips
    $('[data-tooltip]').each(function() {
        $(this).css('cursor', 'help');
    });
    
    // Form field character counter
    $('input[type="text"], textarea').each(function() {
        const maxLength = $(this).attr('maxlength');
        if (maxLength) {
            const counter = $('<span>', {
                class: 'character-counter',
                css: {
                    fontSize: '12px',
                    color: '#646970',
                    marginTop: '4px',
                    display: 'block'
                }
            });
            
            $(this).after(counter);
            
            const updateCounter = function() {
                const remaining = maxLength - $(this).val().length;
                counter.text(remaining + ' characters remaining');
                
                if (remaining < 10) {
                    counter.css('color', '#d63638');
                } else {
                    counter.css('color', '#646970');
                }
            };
            
            $(this).on('input', updateCounter);
            updateCounter.call(this);
        }
    });
    
    // Initialize on page load
    $(window).on('load', function() {
        // Add loaded class to wrapper for animations
        $('.ajax-post-filter-admin-wrapper').addClass('loaded');
        
        // Check for URL hash and scroll to section
        if (window.location.hash) {
            const target = $(window.location.hash);
            if (target.length) {
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }, 100);
            }
        }
    });
    
    // Prevent accidental navigation away
    let formChanged = false;
    
    $('.settings-form input, .settings-form select, .settings-form textarea').on('change', function() {
        formChanged = true;
    });
    
    $('.settings-form').on('submit', function() {
        formChanged = false;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    // Add animation to preset items
    $('.presets-table-wrapper tbody tr').each(function(index) {
        $(this).css({
            opacity: 0,
            transform: 'translateY(20px)'
        }).delay(index * 50).animate({
            opacity: 1
        }, 300, function() {
            $(this).css('transform', 'translateY(0)');
        });
    });
    
    // Success message animation
    $('.notice-success').hide().slideDown(300);
    
    console.log('Ajax Post Filter Admin initialized successfully');
});
