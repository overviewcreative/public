<?php
namespace HappyPlace\Graphics;

use function \wp_enqueue_script;
use function \wp_enqueue_style;
use function \wp_localize_script;
use function \get_field;
use function \get_post_meta;
use function \plugin_dir_url;

/**
 * Flyer Generator Class
 * Handles real estate flyer generation using Fabric.js
 */
class Flyer_Generator {
    private static ?self $instance = null;
    
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_generate_flyer', [$this, 'ajax_generate_flyer']);
        add_action('wp_ajax_nopriv_generate_flyer', [$this, 'ajax_generate_flyer']);
        add_shortcode('listing_flyer_generator', [$this, 'render_flyer_generator']);
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts(): void {
        // Fabric.js library
        wp_enqueue_script(
            'fabric-js',
            'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
            [],
            '5.3.0',
            true
        );

        // Custom flyer generator script
        wp_enqueue_script(
            'flyer-generator',
            plugin_dir_url(__FILE__) . '../assets/js/flyer-generator.js',
            ['fabric-js', 'jquery'],
            '1.0.0',
            true
        );

        // Localize script with WordPress data
        wp_localize_script('flyer-generator', 'flyerAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flyer_generator_nonce')
        ]);

        // Styles
        wp_enqueue_style(
            'flyer-generator-styles',
            plugin_dir_url(__FILE__) . '../assets/css/flyer-generator.css',
            [],
            '1.0.0'
        );
    }

    /**
     * AJAX handler to get listing data
     */
    public function ajax_generate_flyer(): void {
        check_ajax_referer('flyer_generator_nonce', 'nonce');
        
        $listing_id = intval($_POST['listing_id']);
        
        if (!$listing_id) {
            wp_die('Invalid listing ID');
        }

        $listing_data = $this->get_listing_data($listing_id);
        
        wp_send_json_success($listing_data);
    }

    /**
     * Get all listing data for flyer generation
     */
    private function get_listing_data(int $listing_id): array {
        // Get listing fields from ACF
        $listing_fields = [
            'price' => get_field('price', $listing_id),
            'bedrooms' => get_field('bedrooms', $listing_id),
            'bathrooms' => get_field('bathrooms', $listing_id),
            'square_footage' => get_field('square_footage', $listing_id),
            'lot_size' => get_field('lot_size', $listing_id),
            'street_address' => get_field('street_address', $listing_id),
            'city' => get_field('city', $listing_id),
            'region' => get_field('region', $listing_id),
            'zip_code' => get_field('zip_code', $listing_id),
            'property_type' => get_field('property_type', $listing_id),
            'short_description' => get_field('short_description', $listing_id),
            'main_photo' => get_field('main_photo', $listing_id),
            'photo_gallery' => get_field('photo_gallery', $listing_id),
            'mls_number' => get_field('mls_number', $listing_id),
            'status' => get_field('status', $listing_id)
        ];

        // Get agent data
        $agent = get_field('agent', $listing_id);
        $agent_data = [];
        if ($agent) {
            $agent_data = [
                'name' => get_the_title($agent->ID),
                'phone' => get_field('phone', $agent->ID),
                'email' => get_field('email', $agent->ID),
                'license_number' => get_field('license_number', $agent->ID),
                'office_location' => get_field('office_location', $agent->ID),
                'office_address' => get_field('office_address', $agent->ID),
                'office_phone' => get_field('office_phone', $agent->ID),
                'profile_photo' => get_field('profile_photo', $agent->ID)
            ];
        }

        // Get community data
        $community = get_field('community', $listing_id);
        $community_data = [];
        if ($community) {
            $community_data = [
                'name' => get_the_title($community->ID),
                'description' => get_field('community_description', $community->ID),
                'amenities' => get_field('amenities', $community->ID),
                'hoa_fees' => get_field('hoa_fees', $community->ID)
            ];
        }

        return [
            'listing' => $listing_fields,
            'agent' => $agent_data,
            'community' => $community_data,
            'listing_title' => get_the_title($listing_id),
            'listing_url' => get_permalink($listing_id)
        ];
    }

    /**
     * Render the flyer generator shortcode
     */
    public function render_flyer_generator($atts): string {
        $atts = shortcode_atts([
            'listing_id' => 0,
            'template' => 'parker_group'
        ], $atts);

        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/flyer-generator.php';
        return ob_get_clean();
    }
}

// Initialize
Flyer_Generator::get_instance();