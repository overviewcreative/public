<?php
/**
 * Template part for displaying user profile
 */

$current_user = wp_get_current_user();

if (isset($_POST['update_profile'])) {
    // Handle profile update
    if (isset($_POST['display_name']) && !empty($_POST['display_name'])) {
        wp_update_user(array(
            'ID' => $current_user->ID,
            'display_name' => sanitize_text_field($_POST['display_name'])
        ));
    }

    if (isset($_POST['user_email']) && !empty($_POST['user_email'])) {
        $new_email = sanitize_email($_POST['user_email']);
        if ($new_email !== $current_user->user_email && is_email($new_email)) {
            wp_update_user(array(
                'ID' => $current_user->ID,
                'user_email' => $new_email
            ));
        }
    }

    // Handle notification preferences
    update_user_meta($current_user->ID, 'notification_preferences', array(
        'email_updates' => isset($_POST['email_updates']),
        'new_listings' => isset($_POST['new_listings']),
        'price_changes' => isset($_POST['price_changes']),
        'saved_searches' => isset($_POST['saved_searches'])
    ));

    // Refresh user data
    $current_user = wp_get_current_user();
}

$notification_preferences = get_user_meta($current_user->ID, 'notification_preferences', true);
?>

<div class="hph-profile">
    <h1><?php esc_html_e('My Profile', 'happy-place'); ?></h1>

    <form method="post" class="hph-profile-form">
        <div class="form-section">
            <h2><?php esc_html_e('Personal Information', 'happy-place'); ?></h2>
            
            <div class="form-group">
                <label for="display_name"><?php esc_html_e('Display Name', 'happy-place'); ?></label>
                <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>">
            </div>

            <div class="form-group">
                <label for="user_email"><?php esc_html_e('Email', 'happy-place'); ?></label>
                <input type="email" id="user_email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>">
            </div>
        </div>

        <div class="form-section">
            <h2><?php esc_html_e('Notification Preferences', 'happy-place'); ?></h2>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="email_updates" <?php checked(isset($notification_preferences['email_updates']) && $notification_preferences['email_updates']); ?>>
                    <?php esc_html_e('Receive Email Updates', 'happy-place'); ?>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="new_listings" <?php checked(isset($notification_preferences['new_listings']) && $notification_preferences['new_listings']); ?>>
                    <?php esc_html_e('New Listings Notifications', 'happy-place'); ?>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="price_changes" <?php checked(isset($notification_preferences['price_changes']) && $notification_preferences['price_changes']); ?>>
                    <?php esc_html_e('Price Change Alerts', 'happy-place'); ?>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="saved_searches" <?php checked(isset($notification_preferences['saved_searches']) && $notification_preferences['saved_searches']); ?>>
                    <?php esc_html_e('Saved Search Updates', 'happy-place'); ?>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <?php wp_nonce_field('update_profile_' . $current_user->ID); ?>
            <button type="submit" name="update_profile" class="hph-btn hph-btn-primary">
                <?php esc_html_e('Save Changes', 'happy-place'); ?>
            </button>
        </div>
    </form>

    <div class="form-section">
        <h2><?php esc_html_e('Password', 'happy-place'); ?></h2>
        <p>
            <?php 
            printf(
                __('To change your password, please <a href="%s">click here</a>.', 'happy-place'),
                esc_url(wp_lostpassword_url())
            );
            ?>
        </p>
    </div>
</div>
