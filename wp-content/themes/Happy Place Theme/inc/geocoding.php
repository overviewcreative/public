<?php
function hph_geocode_address() {
    check_ajax_referer('hph_geocode_nonce', 'security');

    $post_id = intval($_POST['post_id']);
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    $street = get_field('street_address', $post_id);
    $city = get_field('city', $post_id);
    $state = get_field('region', $post_id);
    $zip = get_field('zip_code', $post_id);

    $address = implode(' ', array_filter([$street, $city, $state, $zip]));
    if (empty($address)) {
        wp_send_json_error('No address provided');
    }

    $api_key = get_option('hph_google_maps_api_key');
    if (!$api_key) {
        wp_send_json_error('No API key configured');
    }

    $url = add_query_arg([
        'address' => urlencode($address),
        'key' => $api_key
    ], 'https://maps.googleapis.com/maps/api/geocode/json');

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($data['status'] !== 'OK') {
        wp_send_json_error('Geocoding failed: ' . $data['status']);
    }

    $location = $data['results'][0]['geometry']['location'];
    
    update_field('latitude', $location['lat'], $post_id);
    update_field('longitude', $location['lng'], $post_id);

    wp_send_json_success([
        'lat' => $location['lat'],
        'lng' => $location['lng']
    ]);
}
add_action('wp_ajax_hph_geocode_address', 'hph_geocode_address');

function hph_register_map_settings() {
    add_settings_section(
        'hph_maps_section',
        'Google Maps Settings',
        'hph_maps_section_callback',
        'general'
    );

    add_settings_field(
        'hph_google_maps_api_key',
        'Google Maps API Key',
        'hph_api_key_field_callback',
        'general',
        'hph_maps_section'
    );

    register_setting(
        'general',
        'hph_google_maps_api_key',
        [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]
    );
}
add_action('admin_init', 'hph_register_map_settings');

function hph_maps_section_callback() {
    echo '<p>Enter your Google Maps API key. <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">Learn how to get an API key</a>.</p>';
    echo '<p>Required APIs:</p>';
    echo '<ul style="list-style-type: disc; margin-left: 20px;">';
    echo '<li>Maps JavaScript API</li>';
    echo '<li>Geocoding API</li>';
    echo '<li>Places API</li>';
    echo '</ul>';
}

function hph_api_key_field_callback() {
    $key = get_option('hph_google_maps_api_key');
    echo '<input type="text" name="hph_google_maps_api_key" value="' . esc_attr($key) . '" class="regular-text">';
    
    if (!empty($key)) {
        echo '<p class="description">API Key is set. Make sure it has access to Maps JavaScript, Geocoding, and Places APIs.</p>';
    } else {
        echo '<p class="description">Please enter your Google Maps API key to enable mapping features.</p>';
    }
}
