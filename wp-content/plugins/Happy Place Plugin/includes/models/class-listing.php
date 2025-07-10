<?php
/**
 * Listing Class
 *
 * @package HappyPlace
 */

namespace HappyPlace\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Listing model class
 * Handles listing-specific functionality and data management
 */
class Listing {
    private $post;
    private $id;

    public function __construct( $post = null ) {
        if ( is_numeric( $post ) ) {
            $this->post = get_post( $post );
        } elseif ( $post instanceof \WP_Post ) {
            $this->post = $post;
        }
        
        if ( $this->post ) {
            $this->id = $this->post->ID;
        }

        // Add save hook for geocoding
        add_action( 'save_post_listing', [ $this, 'maybe_geocode_address' ], 10, 3 );
    }

    /**
     * Get listing data
     *
     * @return array
     */
    public function get_data(): array {
        if ( ! $this->id ) {
            return [];
        }

        return [
            'price' => get_post_meta( $this->id, '_listing_price', true ),
            'address' => get_post_meta( $this->id, '_listing_address', true ),
            'bedrooms' => get_post_meta( $this->id, '_listing_bedrooms', true ),
            'bathrooms' => get_post_meta( $this->id, '_listing_bathrooms', true ),
            'square_feet' => get_post_meta( $this->id, '_listing_square_feet', true ),
            'lot_size' => get_post_meta( $this->id, '_listing_lot_size', true ),
            'year_built' => get_post_meta( $this->id, '_listing_year_built', true ),
            'features' => get_post_meta( $this->id, '_listing_features', true ),
            'gallery' => get_post_meta( $this->id, '_listing_gallery', true ),
            'location' => [
                'latitude' => get_post_meta( $this->id, '_listing_latitude', true ),
                'longitude' => get_post_meta( $this->id, '_listing_longitude', true )
            ],
            'community' => get_post_meta( $this->id, '_listing_community', true ),
            'agent' => get_post_meta( $this->id, '_listing_agent', true )
        ];
    }

    /**
     * Get listing location
     *
     * @return array|null Location data with lat/lng or null if not set
     */
    public function get_location(): ?array {
        $lat = get_post_meta( $this->id, '_listing_latitude', true );
        $lng = get_post_meta( $this->id, '_listing_longitude', true );

        if ( ! empty( $lat ) && ! empty( $lng ) ) {
            return [
                'latitude' => $lat,
                'longitude' => $lng
            ];
        }

        return null;
    }

    /**
     * Maybe geocode address when listing is saved
     */
    public function maybe_geocode_address( $post_id, $post, $update ): void {
        error_log('Geocoding attempt for listing #' . $post_id);

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log('Skipping geocode - autosave');
            return;
        }

        // Get ACF field values
        $street = get_field('street_address', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('region', $post_id);
        $zip = get_field('zip_code', $post_id);

        $address = implode(' ', array_filter([$street, $city, $state, $zip]));

        if (empty($address)) {
            error_log('No address to geocode');
            return;
        }

        error_log('Attempting to geocode address: ' . $address);

        $api_key = get_option('hph_google_maps_api_key');
        if (!$api_key) {
            error_log('No Google Maps API key found');
            return;
        }

        $url = add_query_arg([
            'address' => urlencode($address),
            'key' => $api_key
        ], 'https://maps.googleapis.com/maps/api/geocode/json');

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            error_log('Geocoding API error: ' . $response->get_error_message());
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] !== 'OK' || empty($data['results'][0]['geometry']['location'])) {
            error_log('Geocoding failed. Status: ' . $data['status']);
            return;
        }

        $location = $data['results'][0]['geometry']['location'];
        
        // Validate coordinates
        if (!is_numeric($location['lat']) || !is_numeric($location['lng'])) {
            error_log('Invalid coordinates received');
            return;
        }

        // Round coordinates to 6 decimal places for consistency
        $lat = round((float)$location['lat'], 6);
        $lng = round((float)$location['lng'], 6);
        
        error_log('Geocoding successful. Updating lat/lng fields.');

        // Update ACF fields - make sure these match your ACF field names exactly
        update_field('field_latitude', $lat, $post_id);  // Use your actual field key
        update_field('field_longitude', $lng, $post_id); // Use your actual field key
        update_field('field_formatted_address', $data['results'][0]['formatted_address'], $post_id);

        // Store in post meta as backup
        update_post_meta($post_id, '_listing_latitude', $lat);
        update_post_meta($post_id, '_listing_longitude', $lng);
        
        error_log(sprintf(
            'Updated coordinates to: %f, %f',
            $lat,
            $lng
        ));

        // Verify the save worked
        $saved_lat = get_field('field_latitude', $post_id);
        $saved_lng = get_field('field_longitude', $post_id);
        
        error_log(sprintf(
            'Verified saved coordinates: %s, %s',
            $saved_lat,
            $saved_lng
        ));
    }

    /**
     * Format price with currency symbol
     *
     * @return string
     */
    public function get_formatted_price(): string {
        $price = get_post_meta( $this->id, '_listing_price', true );
        return empty( $price ) ? '' : '$' . number_format( (float) $price );
    }
}