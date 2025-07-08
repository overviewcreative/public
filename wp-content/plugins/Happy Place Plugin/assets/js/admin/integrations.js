jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        var target = $(this).attr('href').substring(1);
        $('.tab-pane').removeClass('active');
        $('#' + target).addClass('active');
    });
    
    // Form submission
    $('.integration-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var integration = $form.data('integration');
        var $submitButton = $form.find('button[type="submit"]');
        var originalText = $submitButton.text();
        
        $submitButton.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: hphIntegrations.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hph_save_integration_settings',
                nonce: hphIntegrations.nonce,
                integration: integration,
                settings: $form.serializeArray()
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', 'Settings saved successfully');
                } else {
                    showNotice('error', response.data || 'Failed to save settings');
                }
            },
            error: function() {
                showNotice('error', 'Failed to save settings');
            },
            complete: function() {
                $submitButton.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Test connection
    $('.test-connection').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $form = $button.closest('form');
        var integration = $form.data('integration');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: hphIntegrations.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hph_test_integration',
                nonce: hphIntegrations.nonce,
                integration: integration,
                settings: $form.serializeArray()
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', 'Connection successful');
                } else {
                    showNotice('error', response.data || 'Connection failed');
                }
            },
            error: function() {
                showNotice('error', 'Connection test failed');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Show admin notice
    function showNotice(type, message) {
        var $notice = $('<div>')
            .addClass('notice notice-' + type + ' is-dismissible')
            .append($('<p>').text(message))
            .append(
                $('<button>')
                    .attr('type', 'button')
                    .addClass('notice-dismiss')
                    .append($('<span>').addClass('screen-reader-text').text('Dismiss this notice.'))
            );
            
        $('.wrap h1').after($notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeTo(100, 0, function() {
                $notice.slideUp(100, function() {
                    $notice.remove();
                });
            });
        }, 5000);
    }
});
