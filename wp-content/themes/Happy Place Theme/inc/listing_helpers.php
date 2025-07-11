<?php
/**
 * Listing Helper Functions
 * 
 * Utility functions for working with listing data, formatting,
 * and common operations throughout the theme.
 * 
 * @package HappyPlace
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format listing address consistently
 * 
 * @param int $listing_id The listing post ID
 * @return string Formatted address
 */
function hph_format_listing_address(int $listing_id): string {
    // Try full address text field first
    $full_address = get_field('full_address', $listing_id);
    
    if (is_string($full_address) && !empty($full_address)) {
        return $full_address;
    }
    
    // Fall back to address components if it's an array
    if (is_array($full_address)) {
        $components = [];
        
        $address_fields = ['street_address', 'city', 'region', 'zip_code'];
        foreach ($address_fields as $field) {
            if (!empty($full_address[$field])) {
                $components[] = $full_address[$field];
            }
        }
        
        return implode(', ', $components);
    }
    
    return '';
}

/**
 * Get listing bathrooms count (full + partial)
 * 
 * @param int $listing_id The listing post ID
 * @return float Total bathrooms
 */
function hph_get_listing_bathrooms(int $listing_id): float {
    $full_baths = (float) get_field('full_bathrooms', $listing_id);
    $partial_baths = (float) get_field('partial_bathrooms', $listing_id);
    
    return $full_baths + ($partial_baths * 0.5);
}

/**
 * Get listing main photo URL
 * 
 * @param int $listing_id The listing post ID
 * @param string $size Image size
 * @return string Photo URL
 */
function hph_get_listing_photo(int $listing_id, string $size = 'medium'): string {
    // Try main photo field first
    $main_photo = get_field('main_photo', $listing_id);
    if ($main_photo) {
        if (is_array($main_photo)) {
            return $main_photo['sizes'][$size] ?? $main_photo['url'];
        }
        return $main_photo;
    }
    
    // Try gallery
    $gallery = get_field('photo_gallery', $listing_id);
    if ($gallery && !empty($gallery) && is_array($gallery)) {
        $first_image = $gallery[0];
        if (is_array($first_image)) {
            return $first_image['sizes'][$size] ?? $first_image['url'];
        }
        return $first_image;
    }
    
    // Try featured image
    if (has_post_thumbnail($listing_id)) {
        return get_the_post_thumbnail_url($listing_id, $size);
    }
    
    // Fall back to placeholder
    return get_theme_file_uri('assets/images/property-placeholder.jpg');
}

/**
 * Get listing gallery images
 * 
 * @param int $listing_id The listing post ID
 * @param string $size Image size
 * @return array Array of image URLs
 */
function hph_get_listing_gallery(int $listing_id, string $size = 'listing-gallery'): array {
    $gallery = get_field('photo_gallery', $listing_id);
    $images = [];
    
    if ($gallery && is_array($gallery)) {
        foreach ($gallery as $image) {
            if (is_array($image)) {
                $images[] = $image['sizes'][$size] ?? $image['url'];
            } else {
                $images[] = $image;
            }
        }
    }
    
    return $images;
}

/**
 * Get listing status with proper formatting
 * 
 * @param int $listing_id The listing post ID
 * @return string Formatted status
 */
function hph_get_listing_status(int $listing_id): string {
    $status = get_field('status', $listing_id);
    
    if (!$status) {
        return __('Active', 'happy-place');
    }
    
    $status_labels = [
        'active' => __('Active', 'happy-place'),
        'pending' => __('Pending', 'happy-place'),
        'sold' => __('Sold', 'happy-place'),
        'coming_soon' => __('Coming Soon', 'happy-place'),
        'off_market' => __('Off Market', 'happy-place'),
        'withdrawn' => __('Withdrawn', 'happy-place')
    ];
    
    return $status_labels[$status] ?? ucfirst($status);
}

/**
 * Get listing price with proper formatting
 * 
 * @param int $listing_id The listing post ID
 * @return string Formatted price
 */
function hph_get_listing_price(int $listing_id): string {
    $price = get_field('price', $listing_id);
    
    if (!$price) {
        return __('Price on Request', 'happy-place');
    }
    
    return HPH_Theme::format_price($price);
}

/**
 * Get listing square footage
 * 
 * @param int $listing_id The listing post ID
 * @return string Formatted square footage
 */
function hph_get_listing_square_footage(int $listing_id): string {
    $sqft = get_field('square_feet', $listing_id);
    
    if (!$sqft) {
        return '';
    }
    
    return number_format(intval($sqft)) . ' ' . __('sq ft', 'happy-place');
}

/**
 * Get listing coordinates for mapping
 * 
 * @param int $listing_id The listing post ID
 * @return array|null Latitude and longitude
 */
function hph_get_listing_coordinates(int $listing_id): ?array {
    $lat = get_field('latitude', $listing_id);
    $lng = get_field('longitude', $listing_id);
    
    if ($lat && $lng) {
        return [
            'lat' => floatval($lat),
            'lng' => floatval($lng)
        ];
    }
    
    return null;
}

/**
 * Get listing agent information
 * 
 * @param int $listing_id The listing post ID
 * @return array|null Agent data
 */
function hph_get_listing_agent(int $listing_id): ?array {
    $agent_id = get_field('agent', $listing_id);
    
    if (!$agent_id) {
        return null;
    }
    
    return [
        'id' => $agent_id,
        'name' => get_field('name', $agent_id) ?: get_the_title($agent_id),
        'email' => get_field('email', $agent_id),
        'phone' => get_field('phone', $agent_id),
        'photo' => get_field('agent_photo', $agent_id),
        'title' => get_field('title', $agent_id),
        'bio' => get_field('bio', $agent_id),
        'permalink' => get_permalink($agent_id)
    ];
}

/**
 * Get listing features/amenities
 * 
 * @param int $listing_id The listing post ID
 * @return array List of features
 */
function hph_get_listing_features(int $listing_id): array {
    $features = get_field('features', $listing_id);
    
    if (!$features || !is_array($features)) {
        return [];
    }
    
    return array_filter($features);
}

/**
 * Get listing property type
 * 
 * @param int $listing_id The listing post ID
 * @return string Property type name
 */
function hph_get_listing_property_type(int $listing_id): string {
    $terms = get_the_terms($listing_id, 'property_type');
    
    if ($terms && !is_wp_error($terms)) {
        return $terms[0]->name;
    }
    
    return '';
}

/**
 * Get listing location (city/community)
 * 
 * @param int $listing_id The listing post ID
 * @return string Location name
 */
function hph_get_listing_location(int $listing_id): string {
    // Try community first
    $community = get_field('community', $listing_id);
    if ($community) {
        return get_the_title($community);
    }
    
    // Fall back to city
    $city = get_field('city', $listing_id);
    if ($city) {
        return get_the_title($city);
    }
    
    // Try taxonomy
    $terms = get_the_terms($listing_id, 'listing_location');
    if ($terms && !is_wp_error($terms)) {
        return $terms[0]->name;
    }
    
    return '';
}

/**
 * Check if listing is featured
 * 
 * @param int $listing_id The listing post ID
 * @return bool Whether listing is featured
 */
function hph_is_listing_featured(int $listing_id): bool {
    return (bool) get_field('featured', $listing_id);
}

/**
 * Get listing year built
 * 
 * @param int $listing_id The listing post ID
 * @return string Year built
 */
function hph_get_listing_year_built(int $listing_id): string {
    $year = get_field('year_built', $listing_id);
    return $year ? strval($year) : '';
}

/**
 * Get listing lot size
 * 
 * @param int $listing_id The listing post ID
 * @return string Formatted lot size
 */
function hph_get_listing_lot_size(int $listing_id): string {
    $lot_size = get_field('lot_size', $listing_id);
    $lot_unit = get_field('lot_size_unit', $listing_id) ?: 'acres';
    
    if (!$lot_size) {
        return '';
    }
    
    return number_format(floatval($lot_size), 2) . ' ' . $lot_unit;
}

/**
 * Get listing HOA fee
 * 
 * @param int $listing_id The listing post ID
 * @return string Formatted HOA fee
 */
function hph_get_listing_hoa_fee(int $listing_id): string {
    $hoa_fee = get_field('hoa_fee', $listing_id);
    
    if (!$hoa_fee) {
        return '';
    }
    
    $frequency = get_field('hoa_frequency', $listing_id) ?: 'monthly';
    return HPH_Theme::format_price($hoa_fee) . '/' . $frequency;
}

/**
 * Get listing MLS number
 * 
 * @param int $listing_id The listing post ID
 * @return string MLS number
 */
function hph_get_listing_mls(int $listing_id): string {
    return get_field('mls_number', $listing_id) ?: '';
}

/**
 * Check if user has favorited a listing
 * 
 * @param int $listing_id The listing post ID
 * @param int $user_id User ID (defaults to current user)
 * @return bool Whether listing is favorited
 */
function hph_is_listing_favorited(int $listing_id, int $user_id = 0): bool {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $favorites = get_user_meta($user_id, 'favorite_properties', true) ?: [];
    return in_array($listing_id, $favorites);
}

/**
 * Get listing view count
 * 
 * @param int $listing_id The listing post ID
 * @return int View count
 */
function hph_get_listing_view_count(int $listing_id): int {
    global $wpdb;
    
    $table = $wpdb->prefix . 'hph_listing_views';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        return 0;
    }
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE listing_id = %d",
        $listing_id
    ));
}

/**
 * Track listing view
 * 
 * @param int $listing_id The listing post ID
 * @param int $user_id User ID (optional)
 * @return bool Success
 */
function hph_track_listing_view(int $listing_id, int $user_id = 0): bool {
    global $wpdb;
    
    $table = $wpdb->prefix . 'hph_listing_views';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        return false;
    }
    
    return (bool) $wpdb->insert($table, [
        'listing_id' => $listing_id,
        'user_id' => $user_id ?: null,
        'view_date' => current_time('mysql'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
}

/**
 * Get recent listings
 * 
 * @param int $limit Number of listings to retrieve
 * @return array Recent listings
 */
function hph_get_recent_listings(int $limit = 5): array {
    $args = [
        'post_type' => 'listing',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => 'status',
                'value' => 'active'
            ]
        ]
    ];
    
    return get_posts($args);
}

/**
 * Get featured listings
 * 
 * @param int $limit Number of listings to retrieve
 * @return array Featured listings
 */
function hph_get_featured_listings(int $limit = 5): array {
    $args = [
        'post_type' => 'listing',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'meta_query' => [
            [
                'key' => 'featured',
                'value' => true
            ],
            [
                'key' => 'status',
                'value' => 'active'
            ]
        ]
    ];
    
    return get_posts($args);
}