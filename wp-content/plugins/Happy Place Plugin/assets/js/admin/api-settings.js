/**
 * API Settings Admin JavaScript
 * File: assets/js/admin/api-settings.js
 */

jQuery(document).ready(function($) {
    'use strict';

    const ApiSettings = {
        init() {
            this.bindEvents();
            this.checkAllIntegrations();
        },

        bindEvents() {
            // Test integration buttons
            $('.test-integration').on('click', this.testIntegration.bind(this));
            
            // Manual sync buttons
            $('.sync-integration').on('click', this.manualSync.bind(this));
            
            // Auto-test when API keys are changed
            $('input[type="password"]').on('blur', this.autoTestOnChange.bind(this));
        },

        testIntegration(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const integration = $button.data('integration');
            const $status = $button.siblings('.integration-status');
            const originalText = $button.text();
            
            // Update UI
            $button.prop('disabled', true).text(hphApiSettings.strings.testing);
            $status.removeClass('connected error').addClass('unknown').text('Testing...');
            
            // Make AJAX request
            $.ajax({
                url: hphApiSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_test_integration',
                    integration: integration,
                    nonce: hphApiSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $status.removeClass('unknown error').addClass('connected')
                               .text(hphApiSettings.strings.connected);
                        this.showNotice('success', `${integration} connection successful!`);
                    } else {
                        $status.removeClass('unknown connected').addClass('error')
                               .text(hphApiSettings.strings.error);
                        this.showNotice('error', response.data || 'Connection failed');
                    }
                },
                error: () => {
                    $status.removeClass('unknown connected').addClass('error')
                           .text(hphApiSettings.strings.error);
                    this.showNotice('error', 'Connection test failed');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        manualSync(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const syncType = $button.data('sync');
            const originalText = $button.text();
            
            // Update UI
            $button.prop('disabled', true).text(hphApiSettings.strings.syncing);
            
            // Make AJAX request
            $.ajax({
                url: hphApiSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hph_sync_integration',
                    sync: syncType,
                    nonce: hphApiSettings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', `${syncType} sync completed successfully!`);
                    } else {
                        this.showNotice('error', response.data || 'Sync failed');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Sync request failed');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        autoTestOnChange(e) {
            const $input = $(e.currentTarget);
            const fieldName = $input.attr('name');
            
            // Determine which integration this field belongs to
            let integration = null;
            if (fieldName.includes('google')) {
                integration = 'google';
            } else if (fieldName.includes('airtable')) {
                integration = 'airtable';
            } else if (fieldName.includes('followupboss')) {
                integration = 'followupboss';
            } else if (fieldName.includes('mailchimp')) {
                integration = 'mailchimp';
            }
            
            if (integration && $input.val().length > 10) {
                // Wait a moment for the user to finish typing
                setTimeout(() => {
                    const $testButton = $(`.test-integration[data-integration="${integration}"]`);
                    if ($testButton.length) {
                        $testButton.trigger('click');
                    }
                }, 1000);
            }
        },

        checkAllIntegrations() {
            // Test all integrations on page load if credentials exist
            $('.test-integration').each((index, button) => {
                const integration = $(button).data('integration');
                if (this.hasCredentials(integration)) {
                    setTimeout(() => {
                        $(button).trigger('click');
                    }, index * 500); // Stagger the tests
                }
            });
        },

        hasCredentials(integration) {
            let hasKeys = false;
            
            switch (integration) {
                case 'google':
                    hasKeys = $('input[name*="google_maps_api_key"]').val().length > 10;
                    break;
                case 'airtable':
                    hasKeys = $('input[name*="airtable_api_key"]').val().length > 10 &&
                             $('input[name*="airtable_base_id"]').val().length > 10;
                    break;
                case 'followupboss':
                    hasKeys = $('input[name*="followupboss_api_key"]').val().length > 10;
                    break;
                case 'mailchimp':
                    hasKeys = $('input[name*="mailchimp_api_key"]').val().length > 10;
                    break;
            }
            
            return hasKeys;
        },

        showNotice(type, message) {
            // Create WordPress-style admin notice
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const $notice = $(`
                <div class="notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            // Add to page
            $('.wrap h1').after($notice);
            
            // Handle dismiss
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeTo(100, 0, function() {
                    $notice.slideUp(100, function() {
                        $notice.remove();
                    });
                });
            });
            
            // Auto-dismiss success notices
            if (type === 'success') {
                setTimeout(() => {
                    $notice.find('.notice-dismiss').trigger('click');
                }, 3000);
            }
        }
    };

    // Initialize when DOM is ready
    ApiSettings.init();

    // Additional utility functions
    window.hphApiSettings = window.hphApiSettings || {};
    window.hphApiSettings.testIntegration = function(integration) {
        $(`.test-integration[data-integration="${integration}"]`).trigger('click');
    };

    window.hphApiSettings.syncIntegration = function(syncType) {
        $(`.sync-integration[data-sync="${syncType}"]`).trigger('click');
    };
});