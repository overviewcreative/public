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
 * Network: false
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
define('HPH_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Optimized autoloader with comprehensive class mapping
 */
spl_autoload_register(function($class) {
    // Only handle our namespace
    if (strpos($class, 'HappyPlace\\') !== 0) {
        return;
    }

    // Complete class mapping for better performance
    $class_map = [
        // Core Components
        'HappyPlace\\Core\\Post_Types' => 'includes/core/class-post-types.php',
        'HappyPlace\\Core\\Taxonomies' => 'includes/core/class-taxonomies.php',
        'HappyPlace\\Core\\Database' => 'includes/class-database.php',
        
        // Fields
        'HappyPlace\\Fields\\ACF_Field_Groups' => 'includes/fields/class-acf-field-groups.php',
        
        // Admin Components
        'HappyPlace\\Admin\\Admin_Menu' => 'includes/admin/class-admin-menu.php',
        'HappyPlace\\Admin\\CSV_Import_Tool' => 'includes/admin/class-csv-import-tool.php',
        'HappyPlace\\Admin\\Dashboard\\Admin_Dashboard' => 'includes/admin/dashboard/class-admin-dashboard.php',
        
        // Utilities
        'HappyPlace\\Utilities\\PDF_Generator' => 'includes/utilities/class-pdf-generator.php',
        
        // Forms
        'HappyPlace\\Forms\\Inquiry_Form_Handler' => 'includes/forms/class-inquiry-form-handler.php',
        
        // Integrations
        'HappyPlace\\Integrations\\Airtable_Sync' => 'includes/integrations/class-airtable-sync.php',
        
        // Graphics
        'HappyPlace\\Graphics\\Flyer_Generator' => 'includes/graphics/class-flyer-generator.php',
        
        // Users
        'HappyPlace\\Users\\User_Roles_Manager' => 'includes/users/class-user-roles-manager.php',
        'HappyPlace\\Users\\User_Registration_Manager' => 'includes/users/class-user-registration-manager.php',
        'HappyPlace\\Users\\User_Dashboard_Manager' => 'includes/users/class-user-dashboard.php',
        
        // Search
        'HappyPlace\\Search\\Search_Filter_Handler' => 'includes/search/class-search-filter-handler.php',
        
        // Other
        'HappyPlace\\Compliance' => 'includes/class-compliance.php',
    ];

    // Check direct mapping first (faster)
    if (isset($class_map[$class])) {
        $file_path = HPH_PLUGIN_DIR . $class_map[$class];
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
    }

    // Fallback: dynamic path construction
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
 * 
 * Handles plugin initialization, activation, deactivation and core functionality
 */
final class Plugin {
    private static ?self $instance = null;
    private bool $initialized = false;

    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->setup_hooks();
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks(): void {
        add_action('plugins_loaded', [$this, 'init'], 0);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Add uninstall hook
        register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
    }

    /**
     * Initialize plugin components
     */
    public function init(): void {
        // Prevent multiple initialization
        if ($this->initialized) {
            return;
        }

        // Check minimum requirements
        if (!$this->check_requirements()) {
            return;
        }

        // Load text domain for translations
        $this->load_textdomain();

        // Initialize components in order
        $this->init_core_components();
        $this->init_admin_components();
        $this->init_frontend_components();
        $this->init_integrations();
        
        // Setup additional hooks
        $this->setup_additional_hooks();
        
        $this->initialized = true;
        
        // Fire action for other plugins/themes to hook into
        do_action('happy_place_initialized');
    }

    /**
     * Check minimum requirements
     */
    private function check_requirements(): bool {
        global $wp_version;
        
        $requirements = [
            'php_version' => '7.4',
            'wp_version' => '5.8'
        ];

        if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
            add_action('admin_notices', function() use ($requirements) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __('Happy Place Plugin requires PHP %s or higher. You are running %s.', 'happy-place'),
                    $requirements['php_version'],
                    PHP_VERSION
                );
                echo '</p></div>';
            });
            return false;
        }

        if (version_compare($wp_version, $requirements['wp_version'], '<')) {
            add_action('admin_notices', function() use ($requirements) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    __('Happy Place Plugin requires WordPress %s or higher. You are running %s.', 'happy-place'),
                    $requirements['wp_version'],
                    $GLOBALS['wp_version']
                );
                echo '</p></div>';
            });
            return false;
        }

        return true;
    }

    /**
     * Load plugin text domain
     */
    private function load_textdomain(): void {
        load_plugin_textdomain(
            'happy-place',
            false,
            dirname(HPH_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Initialize core components
     */
    private function init_core_components(): void {
        $core_components = [
            Core\Post_Types::class,
            Core\Taxonomies::class,
            Core\Database::class,
            Fields\ACF_Field_Groups::class,
            Compliance::class,
        ];

        foreach ($core_components as $component) {
            if (class_exists($component)) {
                $component::get_instance();
            }
        }
    }

    /**
     * Initialize admin components
     */
    private function init_admin_components(): void {
        if (!is_admin()) {
            return;
        }

        $admin_components = [
            Admin\Admin_Menu::class,
            Admin\CSV_Import_Tool::class,
        ];

        foreach ($admin_components as $component) {
            if (class_exists($component)) {
                $component::get_instance();
            }
        }

        // Setup admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Initialize frontend components
     */
    private function init_frontend_components(): void {
        if (is_admin()) {
            return;
        }

        $frontend_components = [
            Forms\Inquiry_Form_Handler::class,
            Search\Search_Filter_Handler::class,
        ];

        foreach ($frontend_components as $component) {
            if (class_exists($component)) {
                $component::get_instance();
            }
        }

        // Setup frontend assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Initialize integrations
     */
    private function init_integrations(): void {
        $integrations = [
            Utilities\PDF_Generator::class,
            Graphics\Flyer_Generator::class,
        ];

        foreach ($integrations as $integration) {
            if (class_exists($integration)) {
                $integration::get_instance();
            }
        }
    }

    /**
     * Setup additional WordPress hooks
     */
    private function setup_additional_hooks(): void {
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 999);
        add_action('wp_head', [$this, 'add_meta_tags']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(string $hook): void {
        // Only load on plugin pages
        $plugin_pages = [
            'toplevel_page_happy-place',
            'happy-place_page_happy-place-settings',
            'happy-place_page_happy-place-import',
        ];

        if (!in_array($hook, $plugin_pages)) {
            return;
        }

        wp_enqueue_style(
            'happy-place-admin',
            HPH_PLUGIN_URL . 'assets/css/admin/dashboard.css',
            [],
            HPH_VERSION
        );

        wp_enqueue_script(
            'happy-place-admin',
            HPH_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            HPH_VERSION,
            true
        );

        wp_localize_script('happy-place-admin', 'hphAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('happy_place_admin'),
            'strings' => [
                'loading' => __('Loading...', 'happy-place'),
                'error' => __('An error occurred', 'happy-place'),
                'success' => __('Operation completed successfully', 'happy-place'),
            ]
        ]);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void {
        // Only load on relevant pages
        if (!is_singular(['listing', 'agent', 'community']) && !is_post_type_archive(['listing', 'agent'])) {
            return;
        }

        wp_enqueue_style(
            'happy-place-frontend',
            HPH_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            HPH_VERSION
        );

        wp_enqueue_script(
            'happy-place-frontend',
            HPH_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            HPH_VERSION,
            true
        );
    }

    /**
     * Add meta tags for SEO
     */
    public function add_meta_tags(): void {
        if (is_singular('listing')) {
            $listing_id = get_the_ID();
            $price = get_field('price', $listing_id);
            $bedrooms = get_field('bedrooms', $listing_id);
            $bathrooms = get_field('bathrooms', $listing_id);
            
            if ($price) {
                echo '<meta property="product:price:amount" content="' . esc_attr($price) . '">' . "\n";
            }
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
     * Plugin activation handler
     */
    public function activate(): void {
        // Check requirements before activation
        if (!$this->check_requirements()) {
            deactivate_plugins(HPH_PLUGIN_BASENAME);
            wp_die(__('Happy Place Plugin cannot be activated due to unmet requirements.', 'happy-place'));
        }

        // Force load components during activation
        $this->init_core_components();
        
        // Create database tables
        if (class_exists(Core\Database::class)) {
            Core\Database::get_instance()->install();
        }
        
        // Set default options
        $this->set_default_options();
        
        // Create necessary directories and files
        $this->create_plugin_directories();
        $this->create_csv_template();
        
        // Schedule rewrite rules flush
        update_option('hph_flush_rewrite_rules', 'yes');
        
        // Set activation flag for welcome screen
        update_option('hph_activation_redirect', 'yes');
        
        // Fire activation action
        do_action('happy_place_activated');
    }

    /**
     * Set default plugin options
     */
    private function set_default_options(): void {
        // API settings
        $default_api_settings = [
            'followupboss_default_source' => 'Website',
            'mailchimp_server_prefix' => 'us1',
            'google_maps_api_key' => '',
        ];
        
        $existing_settings = get_option('hph_api_credentials', []);
        $merged_settings = array_merge($default_api_settings, $existing_settings);
        update_option('hph_api_credentials', $merged_settings);
        
        // Sync settings
        $default_sync_settings = [
            'airtable_frequency' => 'daily',
            'followupboss_frequency' => 'daily',
            'google_places_frequency' => 'weekly',
            'enable_auto_sync' => true,
        ];
        
        if (!get_option('hph_sync_settings')) {
            update_option('hph_sync_settings', $default_sync_settings);
        }

        // General settings
        $default_general_settings = [
            'currency_symbol' => '$',
            'default_country' => 'US',
            'enable_inquiries' => true,
            'enable_pdf_generation' => true,
        ];

        if (!get_option('hph_general_settings')) {
            update_option('hph_general_settings', $default_general_settings);
        }
    }

    /**
     * Create necessary plugin directories
     */
    private function create_plugin_directories(): void {
        $directories = [
            'templates',
            'uploads/happy-place-pdfs',
            'uploads/happy-place-imports',
        ];

        foreach ($directories as $dir) {
            $full_path = HPH_PLUGIN_DIR . $dir;
            if (!file_exists($full_path)) {
                wp_mkdir_p($full_path);
                
                // Add index.php for security
                $index_file = $full_path . '/index.php';
                if (!file_exists($index_file)) {
                    file_put_contents($index_file, '<?php // Silence is golden');
                }
            }
        }
    }

    /**
     * Create CSV template file
     */
    private function create_csv_template(): void {
        $template_file = HPH_PLUGIN_DIR . 'templates/listings-template.csv';
        
        if (!file_exists($template_file)) {
            $headers = [
                'title', 'price', 'bedrooms', 'bathrooms', 'square_footage', 'lot_size',
                'year_built', 'street_address', 'city', 'region', 'zip_code', 'property_type',
                'status', 'short_description', 'interior_features', 'exterior_features',
                'utility_features', 'latitude', 'longitude', 'main_photo_url', 'agent_email',
                'virtual_tour_link', 'mls_number'
            ];
            
            $sample_data = [
                '"Beautiful Colonial Home"', '"450000"', '"4"', '"3"', '"2200"', '"0.5"',
                '"1995"', '"123 Main Street"', '"Wilmington"', '"DE"', '"19801"', '"Single Family"',
                '"Active"', '"Stunning 4-bedroom colonial in desirable neighborhood."',
                '"Updated kitchen, hardwood floors"', '"Two-car garage, large deck"',
                '"Central air, gas heat"', '"39.744655"', '"-75.546962"',
                '"https://example.com/photos/123main.jpg"', '"john.agent@realtor.com"',
                '"https://virtualtour.com/123main"', '"DE12345678"'
            ];
            
            $csv_content = implode(',', $headers) . "\n" . implode(',', $sample_data) . "\n";
            file_put_contents($template_file, $csv_content);
        }
    }

    /**
     * Plugin deactivation handler
     */
    public function deactivate(): void {
        // Clear scheduled hooks
        $scheduled_hooks = [
            'hph_sync_airtable',
            'hph_sync_followupboss',
            'hph_sync_google_places',
            'hph_cleanup_temp_files',
        ];

        foreach ($scheduled_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Fire deactivation action
        do_action('happy_place_deactivated');
    }

    /**
     * Plugin uninstall handler (static method)
     */
    public static function uninstall(): void {
        // Only run if user has proper permissions
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Remove options
        $options_to_remove = [
            'hph_api_credentials',
            'hph_sync_settings',
            'hph_general_settings',
            'hph_flush_rewrite_rules',
            'hph_activation_redirect',
        ];

        foreach ($options_to_remove as $option) {
            delete_option($option);
        }

        // Remove custom tables (optional - comment out if you want to preserve data)
        // global $wpdb;
        // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hph_inquiries");
        // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hph_property_views");
        
        // Fire uninstall action
        do_action('happy_place_uninstalled');
    }
}

// Initialize the plugin
Plugin::get_instance();

/**
 * Helper function to get plugin instance
 */
function happy_place(): Plugin {
    return Plugin::get_instance();
}

/**
 * Helper function to format price
 */
function hph_format_price($price): string {
    if (!is_numeric($price)) {
        return '';
    }
    
    $settings = get_option('hph_general_settings', []);
    $currency_symbol = $settings['currency_symbol'] ?? '$';
    
    return $currency_symbol . number_format((float)$price);
}

/**
 * Helper function to check if plugin is fully loaded
 */
function hph_is_loaded(): bool {
    return happy_place()->initialized ?? false;
}