<?php
namespace HappyPlace\Fields;

use HappyPlace\Fields\Enhanced\Enhanced_ACF_Fields;
use HappyPlace\Fields\Compliance\MLS_Compliance_Checker;
use HappyPlace\Models\Listing;

use const \HPH_VERSION;
use const \HPH_PATH;
use const \HPH_URL;
use const \ABSPATH;

use function \add_action;
use function \add_filter;
use function \get_file_data;
use function \do_action;
use function \plugin_dir_path;
use function \plugin_dir_url;
use function \wp_enqueue_script;
use function \wp_enqueue_style;
use function \wp_localize_script;
use function \wp_create_nonce;
use function \admin_url;
use function \get_current_screen;
use function \get_post_type;
use function \get_field;
use function \update_field;
use function \get_post;
use function \get_the_ID;
use function \get_the_title;
use function \get_post_meta;
use function \update_post_meta;
use function \delete_post_meta;
use function \wp_upload_dir;
use function \wp_mkdir_p;
use function \is_email;
use function \check_ajax_referer;
use function \wp_send_json_success;
use function \sanitize_text_field;
use function \wp_schedule_single_event;
use function \acf_add_local_field_group;
use function \error_log;
use function \file_exists;
use function \file_get_contents;
use function \json_decode;
use function \esc_html;

/**
 * Class ACF_Field_Groups
 * Manages ACF field groups and their registration
 * 
 * @package HappyPlace\Fields
 */
class ACF_Field_Groups {
    /**
     * Instance of this class
     *
     * @var self
     */
    private static ?self $instance = null;

    /**
     * Enhanced fields handler
     *
     * @var Enhanced_ACF_Fields
     */
    private $enhanced_fields;

    /**
     * MLS compliance checker
     *
     * @var MLS_Compliance_Checker
     */
    private $mls_compliance;

    /**
     * Field group files to load
     *
     * @var array
     */
    private $field_group_files = [
        'group_listing_details.json',
        'group_transaction_details.json',
        'group_agent_details.json',
        'group_community_details.json',
        'group_city_details.json'
    ];

    /**
     * Whether ACF JSON directory has been checked
     *
     * @var bool
     */
    private $json_dir_checked = false;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Get the singleton instance of this class
     *
     * @return self
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Protected to enforce singleton pattern.
     */
    protected function __construct() {
        // Initialize version
        $this->version = $this->get_version();

        // Check if ACF is active
        if (!class_exists('ACF')) {
            add_action('admin_notices', [$this, 'acf_missing_notice']);
            return;
        }

        // Set up ACF JSON directory
        $this->ensure_acf_json_directory();

        // Initialize enhanced fields and MLS compliance
        $this->enhanced_fields = Enhanced_ACF_Fields::get_instance();
        $this->mls_compliance = MLS_Compliance_Checker::get_instance();
        
        // Register field groups
        add_action('acf/init', [$this, 'register_field_groups']);
        
        // Set up field validations
        add_action('init', [$this, 'add_field_validations']);
        
        // Ensure ACF JSON directory exists
        add_action('admin_init', [$this, 'ensure_acf_json_directory']);
        
        // Add ACF JSON sync locations
        add_filter('acf/settings/load_paths', [$this, 'add_acf_json_sync_locations']);

        // Register and enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'register_public_assets']);
    }

    /**
     * Register admin assets
     */
    public function register_admin_assets() {
        if (!$this->is_happy_place_screen()) {
            return;
        }

        wp_enqueue_style(
            'happy-place-admin',
            HPH_URL . 'assets/css/admin.css',
            [],
            HPH_VERSION
        );

        wp_enqueue_script(
            'happy-place-admin',
            HPH_URL . 'assets/js/admin.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('happy-place-admin', 'hphAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('happy_place_admin')
        ]);
    }

    /**
     * Register public assets
     */
    public function register_public_assets() {
        // Only load on single listings or archive pages
        if (!is_singular('listing') && !is_post_type_archive('listing')) {
            return;
        }

        wp_enqueue_style(
            'happy-place-public',
            HPH_URL . 'assets/css/public.css',
            [],
            HPH_VERSION
        );

        wp_enqueue_script(
            'happy-place-public',
            HPH_URL . 'assets/js/public.js',
            ['jquery'],
            HPH_VERSION,
            true
        );
    }

    /**
     * Check if current screen is a Happy Place screen
     *
     * @return bool
     */
    private function is_happy_place_screen(): bool {
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        $post_types = ['listing', 'agent', 'community', 'transaction'];
        return in_array($screen->post_type, $post_types);
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

    /**
     * Process enhanced fields on save
     */
    public function process_enhanced_fields($post_id) {
        if (get_post_type($post_id) !== 'listing') {
            return;
        }

        // Calculate derived fields
        $this->calculate_price_per_sqft($post_id);
        $this->generate_full_address($post_id);
        
        // Process custom features
        $this->process_custom_features($post_id);
        
        // Queue location intelligence gathering if needed
        $this->queue_location_intelligence($post_id);
    }

    /**
     * Check MLS compliance on save
     */
    public function check_mls_compliance($post_id) {
        if (get_post_type($post_id) !== 'listing') {
            return;
        }

        // Check required fields
        $errors = [];
        $required_fields = ['price', 'bedrooms', 'bathrooms', 'square_feet', 'mls_number'];
        foreach ($required_fields as $field) {
            if (empty(get_field($field, $post_id))) {
                $errors[] = sprintf(__('Required field missing: %s', 'happy-place'), $field);
            }
        }

        // Check for fair housing violations in content
        $content_to_check = array_filter([
            get_the_title($post_id),
            get_field('description', $post_id),
            get_field('features', $post_id)
        ]);

        foreach ($content_to_check as $content) {
            $violations = $this->mls_compliance->validate_fair_housing_content($content);
            $errors = array_merge($errors, $violations);
        }
        
        // Store results
        if (!empty($errors)) {
            update_post_meta($post_id, '_compliance_errors', $errors);
            update_field('compliance_notes', implode('; ', $errors), $post_id);
        } else {
            delete_post_meta($post_id, '_compliance_errors');
            update_field('compliance_notes', 'All compliance checks passed', $post_id);
        }
    }

    /**
     * Show MLS compliance notices in admin
     */
    public function show_mls_compliance_notices() {
        global $post;
        
        if (!$post || get_post_type($post) !== 'listing') {
            return;
        }
        
        $compliance_errors = get_post_meta($post->ID, '_compliance_errors', true);
        
        if (!empty($compliance_errors)) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>MLS Compliance Issues:</strong></p>';
            echo '<ul>';
            foreach ($compliance_errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }

    /**
     * Handle feature autocomplete AJAX requests
     */
    public function handle_feature_autocomplete() {
        check_ajax_referer('hph_admin_nonce', 'nonce');
        
        $term = sanitize_text_field($_POST['term'] ?? '');
        $suggestions = $this->enhanced_fields->get_feature_suggestions($term);
        
        wp_send_json_success($suggestions);
    }

    /**
     * Calculate price per square foot
     */
    private function calculate_price_per_sqft($post_id): void {
        $price = get_field('price', $post_id);
        $sqft = get_field('square_footage', $post_id);
        
        if ($price && $sqft && $sqft > 0) {
            $price_per_sqft = round($price / $sqft, 2);
            update_field('price_per_sqft', $price_per_sqft, $post_id);
        }
    }

    /**
     * Generate full address from components
     */
    private function generate_full_address($post_id): void {
        $components = [
            get_field('street_address', $post_id),
            get_field('unit_number', $post_id),
            get_field('city', $post_id),
            get_field('state', $post_id),
            get_field('zip_code', $post_id)
        ];
        
        $full_address = implode(', ', array_filter($components));
        update_field('full_address', $full_address, $post_id);
    }

    /**
     * Process custom features on save
     */
    private function process_custom_features($post_id): void {
        $custom_features = get_field('custom_features', $post_id) ?: [];
        $highlight_features = [];
        
        foreach ($custom_features as $feature) {
            if (!empty($feature['is_highlight'])) {
                $highlight_features[] = [
                    'name' => $feature['feature_name'],
                    'category' => $feature['feature_category']
                ];
            }
        }
        
        // Store highlight features for quick access
        update_post_meta($post_id, '_highlight_features', $highlight_features);
    }

    /**
     * Queue location intelligence gathering
     */
    private function queue_location_intelligence($post_id): void {
        $lat = get_field('latitude', $post_id);
        $lng = get_field('longitude', $post_id);
        
        if ($lat && $lng) {
            wp_schedule_single_event(
                time() + 60, // Wait for geocoding to complete
                'hph_gather_location_intelligence',
                [$post_id, $lat, $lng]
            );
        }
    }

    /**
     * Validate enhanced fields
     */
    public function validate_enhanced_fields($valid, $value, $field, $input) {
        // Only validate on listing post type
        if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'listing') {
            return $valid;
        }
        
        $field_name = $field['name'];
        
        // Fair Housing content validation
        if (in_array($field_name, ['short_description', 'post_title', 'custom_features'])) {
            // For custom_features, check each feature's description
            if ($field_name === 'custom_features' && is_array($value)) {
                foreach ($value as $feature) {
                    if (isset($feature['feature_description'])) {
                        $fh_errors = $this->mls_compliance->validate_fair_housing_content($feature['feature_description']);
                        if (!empty($fh_errors)) {
                            return $fh_errors[0]; // Return first error
                        }
                    }
                }
            } else {
                // For other fields, check the direct value
                $fh_errors = $this->mls_compliance->validate_fair_housing_content($value);
                if (!empty($fh_errors)) {
                    return $fh_errors[0]; // Return first error
                }
            }
        }
        
        // Custom feature validation
        if ($field_name === 'custom_features' && is_array($value)) {
            foreach ($value as $index => $feature) {
                if (empty($feature['feature_name'])) {
                    return "Feature name is required (row " . ($index + 1) . ")";
                }
                if (strlen($feature['feature_name']) > 100) {
                    return "Feature name too long (row " . ($index + 1) . ")";
                }
            }
        }
        
        return $valid;
    }

    /**
     * Get the plugin version for asset versioning
     */
    private function get_version(): string {
        static $version = null;
        
        if ($version === null) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $version = (string)time(); // Cache busting in debug mode
            } else {
                $plugin_data = get_file_data(
                    dirname(__DIR__, 2) . '/happy-place-plugin.php',
                    ['Version' => 'Version']
                );
                $version = $plugin_data['Version'] ?: '1.0.0';
            }
        }
        
        return $version;
    }

    /**
     * Display admin notice if ACF is not active
     */
    public function acf_missing_notice(): void {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Happy Place Plugin requires Advanced Custom Fields PRO to be installed and activated.', 'happy-place'); ?></p>
        </div>
        <?php
    }

    /**
     * Set the ACF JSON save point
     */
    public function set_acf_json_save_point(string $path): string {
        return HPH_PATH . 'includes/fields/acf-json';
    }

    /**
     * Add the ACF JSON load point
     */
    public function add_acf_json_load_point(array $paths): array {
        $paths[] = HPH_PATH . 'includes/fields/acf-json';
        return $paths;
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