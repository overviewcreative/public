<?php
/**
 * MLS and Fair Housing Compliance Class
 *
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

class HP_Compliance {
    /**
     * MLS Logo requirements by state
     */
    private $mls_logos = array(
        'DE' => array(
            'bright' => array(
                'path' => '/assets/images/bright-mls-logo.png',
                'required' => true
            ),
            'daar' => array(
                'path' => '/assets/images/daar-logo.png',
                'required' => false
            )
        ),
        'MD' => array(
            'bright' => array(
                'path' => '/assets/images/bright-mls-logo.png',
                'required' => true
            ),
            'mris' => array(
                'path' => '/assets/images/mris-logo.png',
                'required' => false
            )
        )
    );

    /**
     * Fair Housing notice requirements
     */
    private $fair_housing_notice = array(
        'logo_path' => '/assets/images/fair-housing-logo.png',
        'text' => 'We are committed to the letter and spirit of the Fair Housing Act, which prohibits discrimination in the sale, rental, and financing of dwellings based on race, color, religion, sex, national origin, familial status, and disability.',
        'required_disclosure' => true
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'add_compliance_footer'));
        add_filter('the_content', array($this, 'add_compliance_notices'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_compliance_assets'));
    }

    /**
     * Add compliance footer
     */
    public function add_compliance_footer() {
        if (!is_singular('property')) {
            return;
        }

        $state = get_field('property_state');
        if (!$state) {
            return;
        }

        $logos = isset($this->mls_logos[$state]) ? $this->mls_logos[$state] : array();
        
        echo '<div class="hph-compliance-footer">';
        
        // MLS Logos
        if (!empty($logos)) {
            echo '<div class="hph-mls-logos">';
            foreach ($logos as $key => $logo) {
                if ($logo['required']) {
                    echo '<img src="' . esc_url(HP_PLUGIN_URL . $logo['path']) . '" alt="' . esc_attr(strtoupper($key) . ' MLS Logo') . '" class="hph-mls-logo">';
                }
            }
            echo '</div>';
        }

        // Fair Housing Logo and Notice
        if ($this->fair_housing_notice['required_disclosure']) {
            echo '<div class="hph-fair-housing">';
            echo '<img src="' . esc_url(HP_PLUGIN_URL . $this->fair_housing_notice['logo_path']) . '" alt="Fair Housing Logo" class="hph-fair-housing-logo">';
            echo '<p class="hph-fair-housing-notice">' . esc_html($this->fair_housing_notice['text']) . '</p>';
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Add compliance notices to property content
     */
    public function add_compliance_notices($content) {
        if (!is_singular('property')) {
            return $content;
        }

        $notices = array();
        
        // Add MLS disclaimer
        $notices[] = '<div class="hph-mls-disclaimer">' . 
            esc_html__('The data relating to real estate for sale on this website appears in part through the BRIGHT Internet Data Exchange program, a voluntary cooperative exchange of property listing data between licensed real estate brokerage firms.', 'happy-place') .
            '</div>';

        // Add copyright notice
        $notices[] = '<div class="hph-copyright-notice">' .
            esc_html__('Copyright ' . date('Y') . ' BRIGHT, All Rights Reserved. Information Deemed Reliable But Not Guaranteed.', 'happy-place') .
            '</div>';

        return $content . implode('', $notices);
    }

    /**
     * Enqueue compliance-related assets
     */
    public function enqueue_compliance_assets() {
        if (!is_singular('property')) {
            return;
        }

        wp_enqueue_style(
            'hph-compliance',
            HP_PLUGIN_URL . 'assets/css/compliance.css',
            array(),
            HP_VERSION
        );
    }

    /**
     * Validate property data for MLS compliance
     */
    public static function validate_property($post_id) {
        $required_fields = array(
            'property_price' => __('Price is required for MLS compliance', 'happy-place'),
            'property_address' => __('Property address is required for MLS compliance', 'happy-place'),
            'property_state' => __('State is required for MLS compliance', 'happy-place'),
            'property_type' => __('Property type is required for MLS compliance', 'happy-place')
        );

        $errors = array();

        foreach ($required_fields as $field => $message) {
            if (empty(get_field($field, $post_id))) {
                $errors[] = $message;
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Get required disclaimers for print version
     */
    public static function get_print_disclaimers($post_id) {
        $state = get_field('property_state', $post_id);
        $disclaimers = array(
            'mls' => sprintf(
                __('Listed by %s through %s MLS on %s', 'happy-place'),
                get_field('listing_agent', $post_id),
                $state === 'DE' ? 'DAAR' : 'BRIGHT',
                get_the_date('m/d/Y', $post_id)
            ),
            'fair_housing' => __('This property is offered in compliance with Federal Fair Housing Law', 'happy-place'),
            'copyright' => sprintf(
                __('© %d BRIGHT MLS. All Rights Reserved. Data deemed reliable but not guaranteed.', 'happy-place'),
                date('Y')
            )
        );

        return $disclaimers;
    }
}
