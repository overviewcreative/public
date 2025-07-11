/**
 * Dashboard Profile JavaScript
 */
(function($) {
    'use strict';

    // Elements
    const $personalInfoForm = $('#personal-info-form');
    const $professionalDetailsForm = $('#professional-details-form');
    const $socialMediaForm = $('#social-media-form');
    const $accountSettingsForm = $('#account-settings-form');
    
    // Initialize
    function init() {
        bindEvents();
        initializeMediaUploader();
    }

    // Bind Events
    function bindEvents() {
        // Form submissions
        $personalInfoForm.on('submit', handlePersonalInfoSubmit);
        $professionalDetailsForm.on('submit', handleProfessionalDetailsSubmit);
        $socialMediaForm.on('submit', handleSocialMediaSubmit);
        $accountSettingsForm.on('submit', handleAccountSettingsSubmit);

        // Preview profile
        $('.preview-profile').on('click', previewProfile);

        // Password confirmation
        $('#new-password, #confirm-password').on('input', validatePasswords);
    }

    // Initialize Media Uploader
    function initializeMediaUploader() {
        let mediaUploader;

        $('.change-avatar').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: hphDashboard.translations.choosePhoto,
                button: {
                    text: hphDashboard.translations.usePhoto
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                updateProfilePhoto(attachment);
            });

            mediaUploader.open();
        });
    }

    // Handle Personal Info Submit
    function handlePersonalInfoSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData($personalInfoForm[0]);
        
        wp.apiRequest({
            path: '/happyplace/v1/agent-profile/personal',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).then(function(response) {
            showNotification('success', hphDashboard.translations.saveSuccess);
            updateUserInfo(response);
        }).catch(function(error) {
            showNotification('error', error.message || hphDashboard.translations.saveError);
        });
    }

    // Handle Professional Details Submit
    function handleProfessionalDetailsSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData($professionalDetailsForm[0]);
        
        wp.apiRequest({
            path: '/happyplace/v1/agent-profile/professional',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).then(function(response) {
            showNotification('success', hphDashboard.translations.saveSuccess);
        }).catch(function(error) {
            showNotification('error', error.message || hphDashboard.translations.saveError);
        });
    }

    // Handle Social Media Submit
    function handleSocialMediaSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData($socialMediaForm[0]);
        
        wp.apiRequest({
            path: '/happyplace/v1/agent-profile/social',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).then(function(response) {
            showNotification('success', hphDashboard.translations.saveSuccess);
        }).catch(function(error) {
            showNotification('error', error.message || hphDashboard.translations.saveError);
        });
    }

    // Handle Account Settings Submit
    function handleAccountSettingsSubmit(e) {
        e.preventDefault();
        
        if (!validatePasswords()) {
            return;
        }

        const formData = new FormData($accountSettingsForm[0]);
        
        wp.apiRequest({
            path: '/happyplace/v1/agent-profile/account',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).then(function(response) {
            showNotification('success', hphDashboard.translations.saveSuccess);
            $accountSettingsForm[0].reset();
        }).catch(function(error) {
            showNotification('error', error.message || hphDashboard.translations.saveError);
        });
    }

    // Update Profile Photo
    function updateProfilePhoto(attachment) {
        wp.apiRequest({
            path: '/happyplace/v1/agent-profile/photo',
            method: 'POST',
            data: {
                photo_id: attachment.id
            }
        }).then(function(response) {
            $('.hph-profile-avatar img').attr('src', attachment.url);
            $('.hph-dashboard-avatar').attr('src', attachment.url);
            showNotification('success', hphDashboard.translations.photoUpdated);
        }).catch(function(error) {
            showNotification('error', error.message || hphDashboard.translations.saveError);
        });
    }

    // Update User Info
    function updateUserInfo(data) {
        $('.hph-dashboard-user-info h3').text(data.display_name);
        if (data.title) {
            $('.hph-dashboard-user-info p').text(data.title);
        }
    }

    // Validate Passwords
    function validatePasswords() {
        const $newPassword = $('#new-password');
        const $confirmPassword = $('#confirm-password');
        const $submitButton = $accountSettingsForm.find('button[type="submit"]');

        if ($newPassword.val() || $confirmPassword.val()) {
            if ($newPassword.val() !== $confirmPassword.val()) {
                $confirmPassword[0].setCustomValidity(hphDashboard.translations.passwordMismatch);
                $submitButton.prop('disabled', true);
                return false;
            } else {
                $confirmPassword[0].setCustomValidity('');
                $submitButton.prop('disabled', false);
                return true;
            }
        }

        return true;
    }

    // Preview Profile
    function previewProfile() {
        const agentId = hphDashboard.currentUser.agentId;
        window.open(`${hphDashboard.urls.agent}${agentId}`, '_blank');
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
