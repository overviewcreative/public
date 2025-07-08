<?php
/**
 * Plugin Name: Happy Place Real Estate Platform
 * Description: Comprehensive real estate management solution for WordPress
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

// Autoloader for plugin classes
spl_autoload_register(function($class) {
    // Only autoload our plugin classes
    if (strpos($class, 'HappyPlace\\') === 0) {
        // Convert namespace to path and add class- prefix for classes
        $path = substr($class, strlen('HappyPlace\\'));
        $parts = explode('\\', $path);
        $className = array_pop($parts);
        
        // Convert class name to kebab case with class- prefix
        $fileName = 'class-' . strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
        
        // Build the final path
        $path = strtolower(implode(DIRECTORY_SEPARATOR, $parts));
        if ($path) {
            $path .= DIRECTORY_SEPARATOR;
        }
        
        $file = HPH_PLUGIN_DIR . 'includes/' . $path . $fileName . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

class Plugin {
    private static ?self $instance = null;

    // Plugin components
    private $components = [
        'post_types'     => '\Core\PostTypes',
        'taxonomies'     => '\Core\Taxonomies',
        'field_groups'   => '\Fields\AcfFieldGroups',
        'form_handler'   => '\Forms\FormHandler',
        'property'       => '\PostTypes\Property'
    ];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->setup_hooks();
    }

    /**
     * Initialize plugin components
     */
    public function init_components(): void {
        foreach ($this->components as $name => $class) {
            $full_class = __NAMESPACE__ . $class;
            if (class_exists($full_class)) {
                $full_class::get_instance();
            }
        }
    }

    /**
     * Set up plugin-wide hooks
     */
    private function setup_hooks(): void {
        // Core WordPress hooks
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init_components'], 0);
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 20);
        add_action('admin_notices', [$this, 'check_dependencies']);
        
        // Plugin activation/deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'happy-place', 
            false, 
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Check plugin dependencies
     */
    public function check_dependencies(): void {
        // Check for required plugins
        $required_plugins = [
            'advanced-custom-fields-pro/acf.php' => 'Advanced Custom Fields PRO'
        ];

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ($required_plugins as $plugin => $name) {
            if (!is_plugin_active($plugin)) {
                $this->show_dependency_notice($name);
            }
        }
    }

    /**
     * Display dependency notice
     */
    private function show_dependency_notice(string $plugin_name): void {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                printf(
                    __('%s is required for the Happy Place Real Estate Platform to function properly.', 'happy-place'), 
                    '<strong>' . esc_html($plugin_name) . '</strong>'
                ); 
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Plugin activation routine
     */
    public function activate(): void {
        // Initialize components to ensure everything is registered
        $this->init_components();
        
        // Explicitly register post types
        \HappyPlace\Core\PostTypes::get_instance()->register_post_types();
        
        // Set flag to flush rewrite rules
        update_option('hph_flush_rewrite_rules', 'yes');
    }

    /**
     * Hook into init to flush rewrite rules if needed
     */
    public function maybe_flush_rewrite_rules(): void {
        if (get_option('hph_flush_rewrite_rules') === 'yes') {
            flush_rewrite_rules();
            delete_option('hph_flush_rewrite_rules');
        }
    }

    /**
     * Plugin deactivation routine
     */
    public function deactivate(): void {
        // Clean up
        flush_rewrite_rules();
    }
}

// Initialize the plugin
Plugin::get_instance();