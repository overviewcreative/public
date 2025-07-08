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
     *
     * @param int $post_id The post ID
     * @param \WP_Post $post The post object
     * @param bool $update Whether this is an existing post being updated
     */
    public function maybe_geocode_address( $post_id, $post, $update ): void {
        // Skip autosaves
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Skip revisions
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Verify post type
        if ( 'listing' !== $post->post_type ) {
            return;
        }

        // Get the full address
        $street = get_post_meta( $post_id, '_listing_address', true );
        $city = get_post_meta( $post_id, '_listing_city', true );
        $state = get_post_meta( $post_id, '_listing_state', true );
        $zip = get_post_meta( $post_id, '_listing_zip', true );

        // Build full address
        $full_address = implode( ' ', array_filter( [ $street, $city, $state, $zip ] ) );

        if ( empty( $full_address ) ) {
            return;
        }

        // Try to geocode the address
        $location = \HappyPlace\hph_geocode_address( $full_address );

        if ( $location && isset( $location['latitude'], $location['longitude'] ) ) {
            update_post_meta( $post_id, '_listing_latitude', $location['latitude'] );
            update_post_meta( $post_id, '_listing_longitude', $location['longitude'] );
        }
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
