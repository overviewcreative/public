<?php
namespace HappyPlace\Fields\Compliance;

/**
 * MLS Compliance Checker
 * 
 * Validates listing content for MLS compliance and fair housing regulations
 */
class MLS_Compliance_Checker {
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
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Protected to enforce singleton pattern.
     */
    protected function __construct() {
        // Add initialization here if needed
        add_filter('acf/validate_value/type=textarea', array($this, 'validate_field_content'), 10, 4);
        add_filter('acf/validate_value/type=text', array($this, 'validate_field_content'), 10, 4);
    }

    /**
     * Prohibited terms that may violate fair housing laws
     *
     * @var array
     */
    private $prohibited_terms = array(
        'exclusive',
        'bachelor',
        'adult',
        'no children',
        'Christian',
        'Jewish',
        'Muslim',
        'preferred',
        'integrated',
        'traditional',
        'private',
        'restricted',
        'retired',
        'safe',
        'secure'
    );

    /**
     * Validate field content for compliance
     *
     * @param mixed $valid Whether the value is valid
     * @param mixed $value The value to check
     * @param array $field The field array containing all settings
     * @param string $input The $_POST key for the value
     * @return mixed
     */
    public function validate_field_content($valid, $value, $field, $input) {
        if ($valid !== true) {
            return $valid;
        }

        $violations = $this->validate_fair_housing_content($value);
        if (!empty($violations)) {
            return sprintf(
                'Content may violate fair housing laws. Please review these terms: %s',
                implode(', ', $violations)
            );
        }

        return $valid;
    }

    /**
     * Validate content for fair housing compliance
     *
     * @param string $content The content to check
     * @return array Array of any violations found
     */
    public function validate_fair_housing_content( $content ) {
        $violations = array();
        
        // Handle array input (like from ACF)
        if (is_array($content)) {
            if (isset($content['value'])) {
                $content = $content['value'];
            } else {
                return array(); // No text content to check
            }
        }
        
        // Ensure we're working with a string
        $content = (string) $content;
        $content = strtolower($content);

        foreach ( $this->prohibited_terms as $term ) {
            if ( stripos( $content, $term ) !== false ) {
                $violations[] = sprintf(
                    __( 'Found potentially problematic term: "%s". This may violate fair housing laws.', 'happy-place' ),
                    $term
                );
            }
        }

        // Allow additional checks via filter
        return apply_filters( 'happy_place_fair_housing_violations', $violations, $content );
    }

    /**
     * Get the list of prohibited terms
     *
     * @return array Array of prohibited terms
     */
    public function get_prohibited_terms() {
        return apply_filters( 'happy_place_prohibited_terms', $this->prohibited_terms );
    }
}
