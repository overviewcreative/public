<?php
/**
 * Plugin Name: Happy Place Real Estate Platform
 * Description: Comprehensive real estate management solution
 * Version: 1.0.0
 * Author: Happy Place Team
 * Text Domain: happy-place
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

namespace HappyPlace;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HPH_VERSION', '1.0.0');
define('HPH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HPH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HPH_PLUGIN_FILE', __FILE__);

/**
 * Fixed autoloader with direct class mapping
 */
spl_autoload_register(function($class) {
    // Only handle our namespace
    if (strpos($class, 'HappyPlace\\') !== 0) {
        return;
    }

    // Map of class names to actual file paths
    $class_map = [
        'HappyPlace\\Core\\Post_Types' => 'includes/core/class-post-types.php',
        'HappyPlace\\Core\\Taxonomies' => 'includes/core/class-taxonomies.php',
        'HappyPlace\\Fields\\ACF_Field_Groups' => 'includes/fields/class-acf-field-groups.php',
        'HappyPlace\\Compliance' => 'includes/class-compliance.php',
        'HappyPlace\\Core\\Database' => 'includes/class-database.php',
        'HappyPlace\\Admin\\Admin_Menu' => 'includes/admin/class-admin-menu.php',
        'HappyPlace\\Admin\\CSV_Import_Tool' => 'includes/admin/class-csv-import-tool.php',
        'HappyPlace\\Utilities\\PDF_Generator' => 'includes/utilities/class-pdf-generator.php',
    ];

    // Check if we have a direct mapping
    if (isset($class_map[$class])) {
        $file_path = HPH_PLUGIN_DIR . $class_map[$class];
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
    }

    // Fallback: try to construct path automatically
    $class_name = substr($class, strlen('HappyPlace\\'));
    $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
    $path_parts = explode(DIRECTORY_SEPARATOR, $class_path);
    $file_name = array_pop($path_parts);
    
    // Convert PascalCase to kebab-case
    $file_name = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $file_name));
    $file_name = 'class-' . $file_name . '.php';
    
    $directory = !empty($path_parts) ? strtolower(implode(DIRECTORY_SEPARATOR, $path_parts)) . DIRECTORY_SEPARATOR : '';
    $file_path = HPH_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . $directory . $file_name;
    
    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

/**
 * Main Plugin Class
 */
class Plugin {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init'], 0);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Initialize plugin components
     */
    public function init(): void {
        // Load text domain
        load_plugin_textdomain(
            'happy-place', 
            false, 
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );

        // Initialize core components
        $this->init_core_components();
        $this->init_admin_components();
        $this->init_pdf_generator();
        
        // Schedule rewrite flush if needed
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 999);
    }

    /**
     * Initialize core components
     */
    private function init_core_components(): void {
        // Post Types
        if (class_exists('HappyPlace\\Core\\Post_Types')) {
            Core\Post_Types::get_instance();
        }

        // Taxonomies
        if (class_exists('HappyPlace\\Core\\Taxonomies')) {
            Core\Taxonomies::get_instance();
        }

        // ACF Field Groups
        if (class_exists('HappyPlace\\Fields\\ACF_Field_Groups')) {
            Fields\ACF_Field_Groups::get_instance();
        }

        // Compliance
        if (class_exists('HappyPlace\\Compliance')) {
            Compliance::get_instance();
        }

        // Database
        if (class_exists('HappyPlace\\Core\\Database')) {
            Core\Database::get_instance();
        }
    }

    /**
     * Initialize admin components
     */
    private function init_admin_components(): void {
        if (!is_admin()) {
            return;
        }

        // Initialize all admin components
        $admin_components = [
            'HappyPlace\\Admin\\Admin_Menu',
            'HappyPlace\\Admin\\CSV_Import_Tool'
        ];

        foreach ($admin_components as $component) {
            if (class_exists($component)) {
                $component::get_instance();
            }
        }

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook): void {
        $plugin_pages = [
            'happy-place_page_happy-place-api-settings',
            'happy-place_page_happy-place-integrations',
            'happy-place_page_happy-place-tools',
            'toplevel_page_happy-place'
        ];

        // Only load on plugin pages
        if (!in_array($hook, $plugin_pages)) {
            return;
        }

        // Enqueue main admin styles
        wp_enqueue_style(
            'happy-place-admin',
            HPH_PLUGIN_URL . 'assets/css/admin/admin.css',
            [],
            HPH_VERSION
        );

        // Enqueue main admin script
        wp_enqueue_script(
            'happy-place-admin',
            HPH_PLUGIN_URL . 'assets/js/admin/admin.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        // Enqueue integrations script on the integrations page
        if ($hook === 'happy-place_page_happy-place-integrations') {
            wp_enqueue_script(
                'happy-place-integrations',
                HPH_PLUGIN_URL . 'assets/js/admin/integrations.js',
                ['jquery'],
                HPH_VERSION,
                true
            );

            wp_localize_script('happy-place-integrations', 'hphIntegrations', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('happy_place_integrations'),
                'strings' => [
                    'testingConnection' => __('Testing connection...', 'happy-place'),
                    'connectionSuccess' => __('Connection successful!', 'happy-place'),
                    'connectionFailed' => __('Connection failed', 'happy-place'),
                    'savingSettings' => __('Saving settings...', 'happy-place'),
                    'settingsSaved' => __('Settings saved successfully!', 'happy-place'),
                    'settingsFailed' => __('Failed to save settings', 'happy-place')
                ]
            ]);
        }
    }

    /**
     * PDF Generator initialization
     */
    private function init_pdf_generator(): void {
        if (class_exists('HappyPlace\\Utilities\\PDF_Generator')) {
            Utilities\PDF_Generator::get_instance();
        }
    }

    /**
     * Plugin activation
     */
    public function activate(): void {
        // Force load components during activation
        $this->init_core_components();
        
        // Create database tables
        if (class_exists('HappyPlace\\Core\\Database')) {
            Core\Database::get_instance()->install();
        }
        
        // Set flag to flush rewrite rules
        update_option('hph_flush_rewrite_rules', 'yes');
        
        // Set default API settings
        $default_api_settings = [
            'followupboss_default_source' => 'Website',
            'mailchimp_server_prefix' => 'us1'
        ];
        
        $existing_settings = get_option('hph_api_credentials', []);
        foreach ($default_api_settings as $key => $value) {
            if (!isset($existing_settings[$key])) {
                $existing_settings[$key] = $value;
            }
        }
        update_option('hph_api_credentials', $existing_settings);
        
        // Set default sync settings
        $default_sync_settings = [
            'airtable_frequency' => 'daily',
            'followupboss_frequency' => 'daily',
            'google_places_frequency' => 'weekly'
        ];
        
        if (!get_option('hph_sync_settings')) {
            update_option('hph_sync_settings', $default_sync_settings);
        }

        // Create CSV template directory and file
        $this->create_csv_template();
    }

    /**
     * Create CSV template file
     */
    private function create_csv_template(): void {
        $template_dir = HPH_PLUGIN_DIR . 'templates/';
        if (!file_exists($template_dir)) {
            wp_mkdir_p($template_dir);
        }

        $template_file = $template_dir . 'listings-template.csv';
        if (!file_exists($template_file)) {
            $csv_content = 'title,price,bedrooms,bathrooms,square_footage,lot_size,year_built,street_address,city,region,zip_code,property_type,status,short_description,interior_features,exterior_features,utility_features,latitude,longitude,main_photo_url,agent_email,virtual_tour_link,mls_number' . "\n";
            $csv_content .= '"Beautiful Colonial Home","450000","4","3","2200","0.5","1995","123 Main Street","Wilmington","DE","19801","Single Family","Active","Stunning 4-bedroom colonial in desirable neighborhood with updated kitchen and spacious yard.","Updated kitchen with granite counters, hardwood floors throughout, master suite with walk-in closet, finished basement","Two-car garage, large deck, mature landscaping, private backyard","Central air, gas heat, washer/dryer included","39.744655","-75.546962","https://example.com/photos/123main.jpg","john.agent@realtor.com","https://virtualtour.com/123main","DE12345678"' . "\n";
            
            file_put_contents($template_file, $csv_content);
        }
    }

    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules(): void {
        if (get_option('hph_flush_rewrite_rules') === 'yes') {
            flush_rewrite_rules();
            delete_option('hph_flush_rewrite_rules');
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate(): void {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('hph_sync_airtable');
        wp_clear_scheduled_hook('hph_sync_followupboss');
        wp_clear_scheduled_hook('hph_sync_google_places');
        
        flush_rewrite_rules();
    }
}

// Initialize the plugin
Plugin::get_instance();

// Add some helper functions for easy access to integrations
function hph_geocode_address(string $address): ?array {
    if (class_exists('HappyPlace\\Integrations\\Integrations_Manager')) {
        return \HappyPlace\Integrations\Integrations_Manager::get_instance()->geocode_address($address);
    }
    return null;
}

function hph_create_followupboss_lead(array $lead_data): bool {
    if (class_exists('HappyPlace\\Integrations\\Integrations_Manager')) {
        return \HappyPlace\Integrations\Integrations_Manager::get_instance()->create_followupboss_lead($lead_data);
    }
    return false;
}

function hph_add_mailchimp_subscriber(string $email, string $first_name = '', string $last_name = '', array $merge_fields = []): bool {
    if (class_exists('HappyPlace\\Integrations\\Integrations_Manager')) {
        return \HappyPlace\Integrations\Integrations_Manager::get_instance()->add_mailchimp_subscriber($email, $first_name, $last_name, $merge_fields);
    }
    return false;
}

function hph_get_place_details(string $place_id): ?array {
    if (class_exists('HappyPlace\\Integrations\\Integrations_Manager')) {
        return \HappyPlace\Integrations\Integrations_Manager::get_instance()->get_place_details($place_id);
    }
    return null;
}

// Helper function to format price
function hph_format_price($price): string {
    return '$' . number_format((float)$price);
}