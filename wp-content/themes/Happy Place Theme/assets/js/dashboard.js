/**
 * Happy Place Dashboard JavaScript
 */
(function($) {
    'use strict';

    // Dashboard state
    const state = {
        mediaFrame: null,
        features: new Set(),
        currentSection: 'overview',
        isLoading: false
    };

    // Initialize dashboard
    function initDashboard() {
        initNavigation();
        initForms();
        initMediaUploaders();
        initFeaturesTags();
        initCharts();
        initHistoryHandling();
    }

    // Navigation with proper URL handling
    function initNavigation() {
        // Use event delegation for navigation items
        $(document).on('click', '.hph-dashboard-nav-item', function(e) {
            e.preventDefault();
            const section = $(this).data('section');
            navigateToSection(section);
        });

        // Initialize from current URL
        const section = window.location.pathname
            .replace(hphDashboard.dashboardUrl, '')
            .replace(/^\/|\/$/g, '') || 'overview';
        
        if (section !== state.currentSection) {
            loadSection(section, false); // Don't push state on initial load
        }
    }

    // Handle browser back/forward
    function initHistoryHandling() {
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.section) {
                loadSection(e.state.section, false); // Don't push state on popstate
            }
        });
    }

    function navigateToSection(section) {
        if (state.isLoading || section === state.currentSection) return;
        loadSection(section, true);
    }

    function loadSection(section, updateHistory = true) {
        // Update navigation state
        $('.hph-dashboard-nav-item').removeClass('hph-dashboard-nav-item--active');
        $(`.hph-dashboard-nav-item[data-section="${section}"]`).addClass('hph-dashboard-nav-item--active');
        
        $('.hph-dashboard-section').removeClass('hph-dashboard-section--active');
        
        // Handle browser history
        if (updateHistory) {
            const url = section === 'overview' 
                ? hphDashboard.dashboardUrl 
                : `${hphDashboard.dashboardUrl}/${section}`;
            history.pushState({ section }, '', url);
        }
        
        // Load section content if not already loaded
        const $section = $(`#${section}`);
        if ($section.length === 0) {
            loadSectionContent(section);
        } else {
            $section.addClass('hph-dashboard-section--active');
            initSectionFeatures(section);
        }
        
        state.currentSection = section;
    }

    function loadSectionContent(section) {
        const $main = $('.hph-dashboard-main');
        state.isLoading = true;
        $main.addClass('is-loading');

        $.ajax({
            url: hphDashboard.ajaxUrl,
            method: 'POST',
            data: {
                action: 'hph_load_dashboard_section',
                section: section,
                nonce: hphDashboard.nonce
            }
        }).done(function(response) {
            if (response.success && response.data.html) {
                const $newSection = $(response.data.html);
                $main.append($newSection);
                $newSection.addClass('hph-dashboard-section--active');
                initSectionFeatures(section);
            }
        }).fail(function() {
            console.error('Failed to load dashboard section:', section);
        }).always(function() {
            state.isLoading = false;
            $main.removeClass('is-loading');
        });
    }

    // Modals - using event delegation
    function initModals() {
        // Close button and overlay click handlers
        $(document).on('click', '.hph-modal-close, .hph-modal-overlay', function(e) {
            e.preventDefault();
            closeModal($(this).closest('.hph-modal'));
        });

        // Modal trigger buttons
        $(document).on('click', '[data-modal]', function(e) {
            e.preventDefault();
            const modalId = $(this).data('modal');
            openModal($(`#${modalId}`));
        });
    }

    function openModal($modal) {
        if (!$modal.length) return;
        
        $modal.addClass('is-active');
        $('body').addClass('has-modal');
        
        // Trap focus within modal
        $modal.attr('tabindex', '-1').focus();
    }

    function closeModal($modal) {
        if (!$modal.length) return;
        
        $modal.removeClass('is-active');
        $('body').removeClass('has-modal');
        
        // Return focus to trigger if available
        const $trigger = $(`[data-modal="${$modal.attr('id')}"]`);
        if ($trigger.length) $trigger.focus();
    }

    // Forms - using event delegation
    function initForms() {
        $(document).on('submit', '#hph-listing-form', handleListingSubmit);
        $(document).on('submit', '#hph-open-house-form', handleOpenHouseSubmit);
        $(document).on('submit', '#hph-profile-form', handleProfileSubmit);
    }

    function handleListingSubmit(e) {
        e.preventDefault();
        const $form = $(this);
        const formData = new FormData($form[0]);
        
        // Add features
        formData.append('features', JSON.stringify(Array.from(state.features)));
        
        // Show loading state
        $form.addClass('is-loading');
        
        $.ajax({
            url: hphDashboard.root + 'happy-place/v1/listings',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-WP-Nonce': hphDashboard.nonce
            }
        }).done(function(response) {
            $form.removeClass('is-loading');
            if (response.success) {
                closeModal($form.closest('.hph-modal'));
                loadSection('listings'); // Refresh listings
            }
        });
    }

    function handleOpenHouseSubmit(e) {
        e.preventDefault();
        const $form = $(this);
        const formData = new FormData($form[0]);
        
        $form.addClass('is-loading');
        
        $.ajax({
            url: hphDashboard.root + 'happy-place/v1/open-houses',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', hphDashboard.nonce);
            },
            success: function(response) {
                showNotice('success', hphDashboard.translations.saveSuccess);
                loadSection('open-houses');
            },
            error: function() {
                showNotice('error', hphDashboard.translations.saveError);
            },
            complete: function() {
                $form.removeClass('is-loading');
            }
        });
    }

    function handleProfileSubmit(e) {
        e.preventDefault();
        const $form = $(this);
        const formData = new FormData($form[0]);
        
        $form.addClass('is-loading');
        
        $.ajax({
            url: hphDashboard.root + 'happy-place/v1/agents/profile',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', hphDashboard.nonce);
            },
            success: function() {
                showNotice('success', hphDashboard.translations.saveSuccess);
            },
            error: function() {
                showNotice('error', hphDashboard.translations.saveError);
            },
            complete: function() {
                $form.removeClass('is-loading');
            }
        });
    }

    // Media Uploaders
    function initMediaUploaders() {
        // Listing Photos
        $('#add_listing_photos').on('click', function() {
            openMediaUploader(
                true, 
                '#listing_photos', 
                '#listing_photos_preview',
                'image/*'
            );
        });

        // Agent Photo
        $('#add_agent_photo').on('click', function() {
            openMediaUploader(
                false, 
                '#agent_photo', 
                '#agent_photo_preview',
                'image/*'
            );
        });

        // Remove media
        $(document).on('click', '.hph-remove-media', function() {
            const $item = $(this).closest('.hph-media-item');
            const $input = $item.closest('.hph-media-uploader').find('input[type="hidden"]');
            const ids = JSON.parse($input.val() || '[]');
            const newIds = ids.filter(id => id !== $item.data('id'));
            
            $input.val(JSON.stringify(newIds));
            $item.remove();
        });
    }

    function openMediaUploader(multiple, inputSelector, previewSelector, fileType) {
        if (state.mediaFrame) {
            state.mediaFrame.open();
            return;
        }

        state.mediaFrame = wp.media({
            title: 'Select Media',
            button: {
                text: 'Use this media'
            },
            multiple: multiple,
            library: {
                type: fileType
            }
        });

        state.mediaFrame.on('select', function() {
            const selection = state.mediaFrame.state().get('selection');
            const $input = $(inputSelector);
            const $preview = $(previewSelector);
            
            if (multiple) {
                const ids = selection.map(item => item.id);
                $input.val(JSON.stringify(ids));
                
                $preview.empty();
                selection.each(item => {
                    const url = item.get('sizes').thumbnail.url;
                    $preview.append(`
                        <div class="hph-media-item" data-id="${item.id}">
                            <img src="${url}" alt="">
                            <button type="button" class="hph-remove-media">&times;</button>
                        </div>
                    `);
                });
            } else {
                const item = selection.first();
                const url = item.get('sizes').thumbnail.url;
                $input.val(item.id);
                
                $preview.html(`
                    <div class="hph-media-item" data-id="${item.id}">
                        <img src="${url}" alt="">
                        <button type="button" class="hph-remove-media">&times;</button>
                    </div>
                `);
            }
        });

        state.mediaFrame.open();
    }

    // Features Tags
    function initFeaturesTags() {
        const $input = $('#listing_features_input');
        const $container = $('#listing_features_container');
        const $hidden = $('#listing_features');
        
        // Load existing features
        try {
            const features = JSON.parse($hidden.val() || '[]');
            features.forEach(feature => state.features.add(feature));
            renderFeatures();
        } catch (e) {
            console.error('Error loading features:', e);
        }

        // Add new feature
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const feature = $input.val().trim();
                if (feature) {
                    state.features.add(feature);
                    renderFeatures();
                    $input.val('');
                }
            }
        });

        // Remove feature
        $(document).on('click', '.hph-remove-feature', function() {
            const feature = $(this).parent().text().trim();
            state.features.delete(feature);
            renderFeatures();
        });

        function renderFeatures() {
            $container.empty();
            $hidden.val(JSON.stringify(Array.from(state.features)));
            
            state.features.forEach(feature => {
                $container.append(`
                    <span class="hph-feature-tag">
                        ${feature}
                        <button type="button" class="hph-remove-feature">&times;</button>
                    </span>
                `);
            });
        }
    }

    // Charts (using Chart.js)
    function initCharts() {
        if (!$('#viewsChart').length) return;

        // Views over time
        const ctx = document.getElementById('viewsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: viewsData.labels,
                datasets: [{
                    label: 'Profile Views',
                    data: viewsData.views,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Notifications
    function showNotice(type, message) {
        const $notice = $(`
            <div class="hph-notice hph-notice--${type}">
                ${message}
                <button type="button" class="hph-notice-close">&times;</button>
            </div>
        `);
        
        $('.hph-dashboard').append($notice);
        
        setTimeout(() => {
            $notice.addClass('is-visible');
        }, 10);
        
        setTimeout(() => {
            $notice.removeClass('is-visible');
            setTimeout(() => $notice.remove(), 300);
        }, 5000);
    }

    // Initialize on document ready
    $(document).ready(initDashboard);

})(jQuery);
