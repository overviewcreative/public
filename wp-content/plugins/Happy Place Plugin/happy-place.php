<?php
/**
 * Plugin Name: Happy Place
 * Plugin URI: https://theparkergroup.com
 * Description: Advanced real estate features and MLS compliance for The Parker Group
 * Version: 1.0.0
 * Author: The Parker Group
 * Author URI: https://theparkergroup.com
 * License: GPL v2 or later
 * Text Domain: happy-place
 */

namespace HappyPlace;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin File Path
if ( ! defined( 'HPH_PLUGIN_FILE' ) ) {
    define( 'HPH_PLUGIN_FILE', __FILE__ );
}

// Plugin Version
if ( ! defined( 'HPH_VERSION' ) ) {
    define( 'HPH_VERSION', '1.0.0' );
}

// Plugin Path
if ( ! defined( 'HPH_PATH' ) ) {
    define( 'HPH_PATH', plugin_dir_path( __FILE__ ) );
}

// Plugin URL
if ( ! defined( 'HPH_URL' ) ) {
    define( 'HPH_URL', plugin_dir_url( __FILE__ ) );
}

// Class map for faster lookups
$class_map = [
    // Core Components
    __NAMESPACE__ . '\\Core\\Post_Types' => 'core/class-post-types.php',
    __NAMESPACE__ . '\\Core\\Taxonomies' => 'core/class-taxonomies.php',
    __NAMESPACE__ . '\\Core\\Database' => 'core/class-database.php',
    __NAMESPACE__ . '\\Core\\Listing_Transaction_Sync' => 'core/class-listing-transaction-sync.php',
    
    // Fields
    __NAMESPACE__ . '\\Fields\\ACF_Field_Groups' => 'fields/class-acf-field-groups.php',
    __NAMESPACE__ . '\\Fields\\Enhanced\\Enhanced_ACF_Fields' => 'fields/enhanced/class-enhanced-acf-fields.php',
    __NAMESPACE__ . '\\Fields\\Compliance\\MLS_Compliance_Checker' => 'fields/compliance/class-mls-compliance-checker.php'
];

/**
 * Load a class file and get its instance
 *
 * @param string $class_path Path to the class file relative to includes directory
 * @param string $class_name Full class name including namespace
 * @return object|null Class instance or null on failure
 */
function load_class($class_path, $class_name) {
    $file = HPH_PATH . 'includes/' . $class_path;
    if (!file_exists($file)) {
        error_log('HPH: Class file not found: ' . $file);
        return null;
    }
    require_once $file;
    if (!class_exists($class_name)) {
        error_log('HPH: Class not found after loading file: ' . $class_name);
        return null;
    }
    return $class_name::get_instance();
}

// Custom autoloader
spl_autoload_register(function($class) use ($class_map) {
    // Only handle our namespace
    if (strpos($class, __NAMESPACE__ . '\\') !== 0) {
        return;
    }

    // Check class map first (faster)
    if (isset($class_map[$class])) {
        $file = HPH_PATH . 'includes/' . $class_map[$class];
        if (file_exists($file)) {
            require_once $file;
            return;
        }
        error_log('HPH: Mapped class file not found: ' . $file);
        return;
    }

    // Fallback to dynamic path construction
    $relative_class = substr($class, strlen(__NAMESPACE__ . '\\'));
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);
    $file = HPH_PATH . 'includes' . DIRECTORY_SEPARATOR . 
            strtolower($file) . '.php';

    if (file_exists($file)) {
        require_once $file;
        return;
    }
    error_log('HPH: Class file not found: ' . $file);
});

// Initialize plugin on plugins_loaded
add_action('plugins_loaded', __NAMESPACE__ . '\\init_happy_place');

// Register activation hook
register_activation_hook(__FILE__, function() {
    // Load core classes first
    if ($post_types = load_class('core/class-post-types.php', __NAMESPACE__ . '\\Core\\Post_Types')) {
        // Trigger activation action
        do_action('happy_place_activated');
    } else {
        error_log('HPH: Failed to initialize Post_Types on activation');
    }
});

/**
 * Initialize the plugin
 */
function init_happy_place() {
    try {
        // Load and register core components first
        $core_classes = [
            'core/class-post-types.php' => __NAMESPACE__ . '\\Core\\Post_Types',
            'core/class-taxonomies.php' => __NAMESPACE__ . '\\Core\\Taxonomies',
            'core/class-database.php' => __NAMESPACE__ . '\\Core\\Database',
            'core/class-listing-transaction-sync.php' => __NAMESPACE__ . '\\Core\\Listing_Transaction_Sync'
        ];

        foreach ($core_classes as $path => $class) {
            if (!load_class($path, $class)) {
                error_log('HPH: Failed to load core class: ' . $class);
                return;
            }
        }

        // Initialize fields if ACF is active
        if (class_exists('ACF')) {
            $field_classes = [
                'fields/class-acf-field-groups.php' => __NAMESPACE__ . '\\Fields\\ACF_Field_Groups',
                'fields/enhanced/class-enhanced-acf-fields.php' => __NAMESPACE__ . '\\Fields\\Enhanced\\Enhanced_ACF_Fields',
                'fields/compliance/class-mls-compliance-checker.php' => __NAMESPACE__ . '\\Fields\\Compliance\\MLS_Compliance_Checker'
            ];

            foreach ($field_classes as $path => $class) {
                if (!load_class($path, $class)) {
                    error_log('HPH: Failed to load field class: ' . $class);
                }
            }
        }
        
        // Initialize dashboard
        if (!load_class('dashboard/class-agent-dashboard.php', __NAMESPACE__ . '\\Dashboard\\Agent_Dashboard')) {
            error_log('HPH: Failed to load Agent_Dashboard');
            return;
        }
        
        error_log('HPH: Plugin initialized successfully');
    } catch (\Throwable $e) {
        error_log('HPH: Error during initialization: ' . $e->getMessage());
    }
}
