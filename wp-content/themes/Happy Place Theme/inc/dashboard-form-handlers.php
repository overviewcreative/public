<?php

/**
 * Dashboard Form Handlers
 * 
 * Handles form submissions for listings, open houses, and leads
 *
 * @package HappyPlace
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handlers for form submissions
 */
add_action('wp_ajax_save_listing', 'hph_handle_listing_form_submission');
add_action('wp_ajax_save_open_house', 'hph_handle_open_house_form_submission');
add_action('wp_ajax_save_lead', 'hph_handle_lead_form_submission');

/**
 * Handle listing form submission
 */
add_action('admin_post_save_listing', 'hph_handle_listing_form_submission');
add_action('admin_post_nopriv_save_listing', 'hph_handle_listing_form_submission');

function hph_handle_listing_form_submission()
{
    // Verify nonce
    if (!wp_verify_nonce($_POST['listing_nonce'], 'save_listing_action')) {
        wp_die(__('Security check failed', 'happy-place'));
    }

    // Check user permissions
    if (!is_user_logged_in() || (!current_user_can('agent') && !current_user_can('administrator'))) {
        wp_die(__('You do not have permission to perform this action', 'happy-place'));
    }

    $listing_id = intval($_POST['listing_id'] ?? 0);
    $is_editing = $listing_id > 0;

    // Prepare post data
    $post_data = [
        'post_title' => sanitize_text_field($_POST['listing_title'] ?? ''),
        'post_content' => wp_kses_post($_POST['listing_description'] ?? ''),
        'post_type' => 'listing',
        'post_status' => sanitize_text_field($_POST['listing_status'] ?? 'draft'),
        'post_author' => get_current_user_id(),
    ];

    if ($is_editing) {
        $post_data['ID'] = $listing_id;
        $result = wp_update_post($post_data);
    } else {
        $result = wp_insert_post($post_data);
    }

    if (is_wp_error($result)) {
        wp_die(__('Failed to save listing', 'happy-place'));
    }

    $listing_id = $is_editing ? $listing_id : $result;

    // Save custom fields
    $custom_fields = [
        'property_type' => sanitize_text_field($_POST['property_type'] ?? ''),
        'listing_price' => floatval($_POST['listing_price'] ?? 0),
        'bedrooms' => intval($_POST['bedrooms'] ?? 0),
        'bathrooms' => floatval($_POST['bathrooms'] ?? 0),
        'square_feet' => intval($_POST['square_feet'] ?? 0),
        'lot_size' => floatval($_POST['lot_size'] ?? 0),
        'year_built' => intval($_POST['year_built'] ?? 0),
        'address' => sanitize_text_field($_POST['address'] ?? ''),
        'city' => sanitize_text_field($_POST['city'] ?? ''),
        'state' => sanitize_text_field($_POST['state'] ?? ''),
        'zip_code' => sanitize_text_field($_POST['zip_code'] ?? ''),
        'mls_number' => sanitize_text_field($_POST['mls_number'] ?? ''),
        'listing_status' => sanitize_text_field($_POST['listing_status_custom'] ?? 'active'),
        'virtual_tour_url' => esc_url_raw($_POST['virtual_tour_url'] ?? ''),
        'property_features' => array_map('sanitize_text_field', $_POST['property_features'] ?? []),
    ];

    foreach ($custom_fields as $key => $value) {
        update_post_meta($listing_id, $key, $value);
    }

    // Handle featured image
    if (!empty($_POST['featured_image_id'])) {
        set_post_thumbnail($listing_id, intval($_POST['featured_image_id']));
    }

    // Redirect back to listings with success message
    $redirect_url = add_query_arg([
        'section' => 'listings',
        'message' => $is_editing ? 'updated' : 'created'
    ], get_permalink());

    wp_redirect($redirect_url);
    exit;
}

/**
 * Handle open house form submission
 */
add_action('admin_post_save_open_house', 'hph_handle_open_house_form_submission');
add_action('admin_post_nopriv_save_open_house', 'hph_handle_open_house_form_submission');

function hph_handle_open_house_form_submission()
{
    // Verify nonce
    if (!wp_verify_nonce($_POST['open_house_nonce'], 'save_open_house_action')) {
        wp_die(__('Security check failed', 'happy-place'));
    }

    // Check user permissions
    if (!is_user_logged_in() || (!current_user_can('agent') && !current_user_can('administrator'))) {
        wp_die(__('You do not have permission to perform this action', 'happy-place'));
    }

    $open_house_id = intval($_POST['open_house_id'] ?? 0);
    $is_editing = $open_house_id > 0;

    // Prepare post data
    $post_data = [
        'post_title' => sanitize_text_field($_POST['open_house_title'] ?? ''),
        'post_content' => wp_kses_post($_POST['open_house_description'] ?? ''),
        'post_type' => 'open_house',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ];

    if ($is_editing) {
        $post_data['ID'] = $open_house_id;
        $result = wp_update_post($post_data);
    } else {
        $result = wp_insert_post($post_data);
    }

    if (is_wp_error($result)) {
        wp_die(__('Failed to save open house', 'happy-place'));
    }

    $open_house_id = $is_editing ? $open_house_id : $result;

    // Save custom fields
    $custom_fields = [
        'related_listing' => intval($_POST['related_listing'] ?? 0),
        'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
        'end_date' => sanitize_text_field($_POST['end_date'] ?? ''),
        'start_time' => sanitize_text_field($_POST['start_time'] ?? ''),
        'end_time' => sanitize_text_field($_POST['end_time'] ?? ''),
        'special_instructions' => sanitize_textarea_field($_POST['special_instructions'] ?? ''),
        'registration_required' => !empty($_POST['registration_required']) ? 1 : 0,
        'max_attendees' => intval($_POST['max_attendees'] ?? 0),
    ];

    foreach ($custom_fields as $key => $value) {
        update_post_meta($open_house_id, $key, $value);
    }

    // Redirect back to listings with success message
    $redirect_url = add_query_arg([
        'section' => 'listings',
        'message' => $is_editing ? 'open_house_updated' : 'open_house_created'
    ], get_permalink());

    wp_redirect($redirect_url);
    exit;
}

/**
 * Handle lead form submission
 */
add_action('admin_post_save_lead', 'hph_handle_lead_form_submission');
add_action('admin_post_nopriv_save_lead', 'hph_handle_lead_form_submission');

function hph_handle_lead_form_submission()
{
    // Verify nonce
    if (!wp_verify_nonce($_POST['lead_nonce'], 'save_lead_action')) {
        wp_die(__('Security check failed', 'happy-place'));
    }

    // Check user permissions
    if (!is_user_logged_in() || (!current_user_can('agent') && !current_user_can('administrator'))) {
        wp_die(__('You do not have permission to perform this action', 'happy-place'));
    }

    $lead_id = intval($_POST['lead_id'] ?? 0);
    $is_editing = $lead_id > 0;

    // Create lead title from first and last name
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $lead_title = trim($first_name . ' ' . $last_name);

    // Prepare post data
    $post_data = [
        'post_title' => $lead_title,
        'post_content' => wp_kses_post($_POST['lead_notes'] ?? ''),
        'post_type' => 'lead',
        'post_status' => 'private',
        'post_author' => get_current_user_id(),
    ];

    if ($is_editing) {
        $post_data['ID'] = $lead_id;
        $result = wp_update_post($post_data);
    } else {
        $result = wp_insert_post($post_data);
    }

    if (is_wp_error($result)) {
        wp_die(__('Failed to save lead', 'happy-place'));
    }

    $lead_id = $is_editing ? $lead_id : $result;

    // Save custom fields
    $custom_fields = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => sanitize_email($_POST['email'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'lead_status' => sanitize_text_field($_POST['lead_status'] ?? 'new'),
        'lead_source' => sanitize_text_field($_POST['lead_source'] ?? ''),
        'property_type_interest' => sanitize_text_field($_POST['property_type_interest'] ?? ''),
        'budget_min' => floatval($_POST['budget_min'] ?? 0),
        'budget_max' => floatval($_POST['budget_max'] ?? 0),
        'preferred_location' => sanitize_text_field($_POST['preferred_location'] ?? ''),
        'timeline' => sanitize_text_field($_POST['timeline'] ?? ''),
        'financing' => sanitize_text_field($_POST['financing'] ?? ''),
        'first_time_buyer' => !empty($_POST['first_time_buyer']) ? 1 : 0,
        'lead_notes' => sanitize_textarea_field($_POST['lead_notes'] ?? ''),
        'created_date' => current_time('Y-m-d H:i:s'),
    ];

    foreach ($custom_fields as $key => $value) {
        update_post_meta($lead_id, $key, $value);
    }

    // Redirect back to leads with success message
    $redirect_url = add_query_arg([
        'section' => 'leads',
        'message' => $is_editing ? 'updated' : 'created'
    ], get_permalink());

    wp_redirect($redirect_url);
    exit;
}

/**
 * Handle draft saving via AJAX
 */
add_action('wp_ajax_save_lead_draft', 'hph_save_lead_draft');
add_action('wp_ajax_save_listing_draft', 'hph_save_listing_draft');
add_action('wp_ajax_save_open_house_draft', 'hph_save_open_house_draft');

function hph_save_lead_draft()
{
    // Basic security check
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    // Save form data to user meta for later retrieval
    $draft_data = $_POST;
    unset($draft_data['action']); // Remove the action parameter

    $user_id = get_current_user_id();
    update_user_meta($user_id, 'lead_draft_data', $draft_data);

    wp_send_json_success('Draft saved');
}

function hph_save_listing_draft()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    $draft_data = $_POST;
    unset($draft_data['action']);

    $user_id = get_current_user_id();
    update_user_meta($user_id, 'listing_draft_data', $draft_data);

    wp_send_json_success('Draft saved');
}

function hph_save_open_house_draft()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    $draft_data = $_POST;
    unset($draft_data['action']);

    $user_id = get_current_user_id();
    update_user_meta($user_id, 'open_house_draft_data', $draft_data);

    wp_send_json_success('Draft saved');
}

/**
 * Display success/error messages
 */
add_action('wp_head', 'hph_display_form_messages');

function hph_display_form_messages()
{
    if (!isset($_GET['message'])) {
        return;
    }

    $message = sanitize_key($_GET['message']);
    $messages = [
        'created' => __('Item created successfully!', 'happy-place'),
        'updated' => __('Item updated successfully!', 'happy-place'),
        'open_house_created' => __('Open house created successfully!', 'happy-place'),
        'open_house_updated' => __('Open house updated successfully!', 'happy-place'),
        'deleted' => __('Item deleted successfully!', 'happy-place'),
        'error' => __('An error occurred. Please try again.', 'happy-place'),
    ];

    if (isset($messages[$message])) {
        $type = ($message === 'error') ? 'error' : 'success';
?>
        <script>
            jQuery(document).ready(function($) {
                showToast('<?php echo esc_js($type); ?>', '<?php echo esc_js($messages[$message]); ?>');
            });
        </script>
<?php
    }
}
