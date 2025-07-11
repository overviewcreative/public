<?php
namespace HappyPlace\Fields\Enhanced;

/**
 * Enhanced ACF Fields handler
 * 
 * Provides extended functionality for ACF fields including feature autocomplete,
 * derived field calculation, and custom field processing.
 */
class Enhanced_ACF_Fields {
    /**
     * Instance of this class
     *
     * @var self
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Protected to enforce singleton pattern.
     */
    protected function __construct() {
        // Add any initialization here
        add_action( 'wp_ajax_get_feature_suggestions', array( $this, 'ajax_get_feature_suggestions' ) );
        add_action( 'acf/validate_value/type=text', array( $this, 'validate_field_value' ), 10, 4 );
    }

    /**
     * AJAX handler for feature suggestions
     */
    public function ajax_get_feature_suggestions() {
        if ( ! isset( $_POST['term'] ) || ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error();
        }

        $partial_term = sanitize_text_field( $_POST['term'] );
        $suggestions = $this->get_feature_suggestions( $partial_term );
        wp_send_json_success( array( 'suggestions' => $suggestions ) );
    }

    /**
     * Get feature suggestions based on partial input
     *
     * @param string $partial_term The partial feature term to match against
     * @return array Array of matching feature suggestions
     */
    public function get_feature_suggestions( $partial_term ) {
        // Default feature list - can be extended via filter
        $default_features = array(
            'Hardwood Floors',
            'Granite Countertops',
            'Stainless Steel Appliances',
            'Central Air',
            'Walk-in Closets',
            'Garden Tub',
            'Crown Molding',
            'High Ceilings',
            'Open Floor Plan',
            'Fireplace'
        );

        $features = apply_filters( 'happy_place_feature_suggestions', $default_features );
        
        // Filter features that match the partial term
        return array_filter( $features, function( $feature ) use ( $partial_term ) {
            return stripos( $feature, $partial_term ) !== false;
        });
    }

    /**
     * Validate a field value
     * 
     * @param mixed $valid Whether the value is valid
     * @param mixed $value The value to validate
     * @param array $field The field array containing all settings
     * @param string $input The $_POST key for the value
     * @return mixed
     */
    public function validate_field_value( $valid, $value, $field, $input ) {
        // Add any field-specific validation here
        return $valid;
    }

    /**
     * Validate a listing's fields
     *
     * @param array  $fields   The field values to validate
     * @param int    $post_id  The post ID of the listing
     * @return array Validation results with any errors found
     */
    public function validate_listing( $fields, $post_id ) {
        $errors = array();

        // Basic field validation
        $required_fields = array( 'price', 'bedrooms', 'bathrooms', 'square_feet' );
        foreach ( $required_fields as $field ) {
            if ( empty( $fields[$field] ) ) {
                $errors[] = sprintf( __( '%s is required', 'happy-place' ), ucfirst( $field ) );
            }
        }

        // Validate price format and range
        if ( ! empty( $fields['price'] ) ) {
            if ( ! is_numeric( $fields['price'] ) || $fields['price'] < 0 ) {
                $errors[] = __( 'Price must be a positive number', 'happy-place' );
            }
        }

        // Allow additional validation via filter
        return apply_filters( 'happy_place_listing_validation', $errors, $fields, $post_id );
    }
}
