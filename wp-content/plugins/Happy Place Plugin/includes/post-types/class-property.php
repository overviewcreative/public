<?php
namespace HappyPlace\Post_Types;

/**
 * Property Model Class
 * 
 * Handles property-specific functionality and data management.
 */
class Property {
    /**
     * Property ID
     * @var int
     */
    private $id;

    /**
     * Property data
     * @var array
     */
    private $data;

    /**
     * Constructor
     *
     * @param int $property_id Post ID of the property
     */
    public function __construct(int $property_id) {
        $this->id = $property_id;
        $this->load_property_data();
    }

    /**
     * Load property data from post meta
     */
    private function load_property_data(): void {
        $this->data = array(
            'price' => get_post_meta($this->id, 'property_price', true),
            'address' => get_post_meta($this->id, 'property_address', true),
            'bedrooms' => get_post_meta($this->id, 'property_bedrooms', true),
            'bathrooms' => get_post_meta($this->id, 'property_bathrooms', true),
            'square_footage' => get_post_meta($this->id, 'property_square_footage', true),
            'lot_size' => get_post_meta($this->id, 'property_lot_size', true),
            'year_built' => get_post_meta($this->id, 'property_year_built', true),
            'features' => get_post_meta($this->id, 'property_features', true),
            'gallery' => get_post_meta($this->id, 'property_gallery', true),
            'location' => array(
                'latitude' => get_post_meta($this->id, 'property_latitude', true),
                'longitude' => get_post_meta($this->id, 'property_longitude', true)
            )
        );
    }

    /**
     * Get property ID
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get formatted price
     *
     * @return string Formatted price with currency symbol
     */
    public function get_formatted_price(): string {
        return empty($this->data['price']) ? '' : '$' . number_format((float) $this->data['price']);
    }

    /**
     * Get property address
     *
     * @return string
     */
    public function get_address(): string {
        return $this->data['address'] ?? '';
    }

    /**
     * Get property features
     *
     * @return array
     */
    public function get_features(): array {
        return is_array($this->data['features']) ? $this->data['features'] : array();
    }

    /**
     * Get property gallery images
     *
     * @return array Array of image IDs or URLs
     */
    public function get_gallery(): array {
        return is_array($this->data['gallery']) ? $this->data['gallery'] : array();
    }

    /**
     * Get property location coordinates
     *
     * @return array{latitude: string, longitude: string}
     */
    public function get_location(): array {
        return $this->data['location'];
    }

    /**
     * Get property type terms
     *
     * @return array WP_Term objects
     */
    public function get_property_types(): array {
        return wp_get_post_terms($this->id, 'property_type', array('fields' => 'all'));
    }

    /**
     * Get property status terms
     *
     * @return array WP_Term objects
     */
    public function get_property_status(): array {
        return wp_get_post_terms($this->id, 'property_status', array('fields' => 'all'));
    }

    /**
     * Get basic property details
     *
     * @return array
     */
    public function get_details(): array {
        return array(
            'bedrooms' => $this->data['bedrooms'],
            'bathrooms' => $this->data['bathrooms'],
            'square_footage' => $this->data['square_footage'],
            'lot_size' => $this->data['lot_size'],
            'year_built' => $this->data['year_built']
        );
    }

    /**
     * Save property data
     *
     * @param array $data Property data to save
     * @return bool True on success, false on failure
     */
    public function save(array $data): bool {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                if ($key === 'location' && is_array($value)) {
                    update_post_meta($this->id, 'property_latitude', $value['latitude'] ?? '');
                    update_post_meta($this->id, 'property_longitude', $value['longitude'] ?? '');
                } else {
                    update_post_meta($this->id, 'property_' . $key, $value);
                }
            }
        }

        $this->load_property_data(); // Refresh data
        return true;
    }

    /**
     * Delete property and associated data
     *
     * @param bool $force_delete Whether to bypass trash and force deletion
     * @return bool True on success, false on failure
     */
    public function delete(bool $force_delete = false): bool {
        return (bool) wp_delete_post($this->id, $force_delete);
    }
}
