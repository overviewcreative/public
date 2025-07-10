<?php
namespace HappyPlace\Fields;

use function \add_action;
use function \add_filter;
use function \do_action;
use function \plugin_dir_path;
use function \wp_upload_dir;
use function \wp_mkdir_p;
use function \is_email;
use function \acf_add_local_field_group;
use function \error_log;
use function \file_exists;
use function \file_get_contents;
use function \json_decode;

/**
 * Class ACF_Field_Groups
 * Manages ACF field groups and their registration
 * 
 * @package HappyPlace\Fields
 */
class ACF_Field_Groups {
    private static ?self $instance = null;
    private $field_group_files = [
        'listing_details' => 'group_listing_details.json',
        'agent_details' => 'group_agent_details.json',
        'community_details' => 'group_community_details.json',
        'city_details' => 'group_city_details.json',
        'transaction_details' => 'group_transaction_details.json',
        'client_details' => 'group_client_details.json',
        'open_house_details' => 'group_open_house_details.json',    
        'local_place_details' => 'group_local_place_details.json'  
    ];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        \add_action('acf/init', [$this, 'register_field_groups']);
        add_action('init', [$this, 'add_field_validations']);
        add_action('admin_init', [$this, 'ensure_acf_json_directory']);
        add_filter('acf/settings/load_paths', [$this, 'add_acf_json_sync_locations']);
        
        // Add address field monitoring
        add_action('acf/save_post', [$this, 'maybe_trigger_geocoding'], 20);
        
        // Allow other parts of the code to hook into field group registration
        do_action('happy_place_acf_init', $this);
    }

    /**
     * Register ACF Field Groups from JSON files
     */
    public function register_field_groups(): void {
        // Ensure ACF is active
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        // Get the directory of the current file
        $base_dir = plugin_dir_path(__FILE__);

        foreach ($this->field_group_files as $key => $filename) {
            $filepath = $base_dir . 'acf-json/' . $filename;

            // Check if file exists
            if (!file_exists($filepath)) {
                error_log("HPH Field Group File Not Found: {$filepath}");
                continue;
            }

            // Read and parse JSON
            $json_content = file_get_contents($filepath);
            $field_group = json_decode($json_content, true);

            // Validate field group
            if (!$field_group || !isset($field_group['fields'])) {
                error_log("Invalid field group JSON: {$filename}");
                continue;
            }

            // Register field group
            acf_add_local_field_group($field_group);
        }
    }

    /**
     * Custom field validation and filtering
     */
    public function add_field_validations(): void {
        // Listing Price Validation
        add_filter('acf/validate_value/name=price', [$this, 'validate_listing_price'], 10, 4);

        // Agent Contact Validation
        add_filter('acf/validate_value/name=email', [$this, 'validate_agent_email'], 10, 4);

        // Open House Date Validation
        add_filter('acf/validate_value/name=open_house_date', [$this, 'validate_open_house_date'], 10, 4);

        // Google Places ID Validation
        add_filter('acf/validate_value/name=google_place_id', [$this, 'validate_google_place_id'], 10, 4);
    }

    /**
     * Validate listing price
     */
    public function validate_listing_price($valid, $value, $field, $input) {
        // Example validation - price must be positive
        if ($value < 0) {
            $valid = 'Price must be a positive number.';
        }
        return $valid;
    }

    /**
     * Validate agent email
     */
    public function validate_agent_email($valid, $value, $field, $input) {
        // Specific email validation for agents
        if (!is_email($value)) {
            $valid = 'Please enter a valid professional email address.';
        }
        return $valid;
    }

    /**
     * Validate open house date
     */
    public function validate_open_house_date($valid, $value, $field, $input) {
        // Ensure open house date is not in the past
        if (strtotime($value) < strtotime('today')) {
            $valid = 'Open house date cannot be in the past.';
        }
        return $valid;
    }

    /**
     * Validate Google Place ID format
     */
    public function validate_google_place_id($valid, $value, $field, $input) {
        // Basic Google Place ID format validation
        if (!empty($value) && !preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            $valid = 'Invalid Google Place ID format.';
        }
        return $valid;
    }

    /**
     * Ensure ACF JSON sync directory exists
     */
    public function ensure_acf_json_directory(): void {
        $upload_dir = wp_upload_dir();
        $acf_json_dir = $upload_dir['basedir'] . '/acf-json';
        
        if (!file_exists($acf_json_dir)) {
            wp_mkdir_p($acf_json_dir);
        }
    }

    /**
     * Add custom sync locations for ACF JSON
     */
    public function add_acf_json_sync_locations($paths) {
        // Add plugin directory as a sync location
        $paths[] = plugin_dir_path(__FILE__) . 'acf-json/';
        return $paths;
    }

    /**
     * Trigger geocoding when address fields change
     */
    public function maybe_trigger_geocoding($post_id) {
        if (get_post_type($post_id) !== 'listing') {
            return;
        }

        $listing = new \HappyPlace\Models\Listing($post_id);
        $listing->maybe_geocode_address($post_id, get_post($post_id), true);
    }

    /**
     * Trigger geocoding when address fields are saved
     */
    public function trigger_geocoding($post_id) {
        // Only handle listing post type
        if (get_post_type($post_id) !== 'listing') {
            return;
        }

        $listing = new \HappyPlace\Models\Listing($post_id);
        $listing->maybe_geocode_address($post_id, get_post($post_id), true);
    }
}

// Initialize the ACF Field Groups
ACF_Field_Groups::get_instance();

// Additional ACF configuration
add_filter('acf/settings/save_json', function($path) {
    // Customize the path where ACF saves JSON files
    return plugin_dir_path(__FILE__) . 'acf-json/';
});

add_filter('acf/settings/load_json', function($paths) {
    // Add additional paths to load ACF JSON files
    $paths[] = plugin_dir_path(__FILE__) . 'acf-json/';
    return $paths;
});