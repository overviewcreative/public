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
if (! defined('ABSPATH')) {
    exit;
}

// Plugin File Path
if (! defined('HPH_PLUGIN_FILE')) {
    define('HPH_PLUGIN_FILE', __FILE__);
}

// Plugin Version
if (! defined('HPH_VERSION')) {
    define('HPH_VERSION', '1.0.0');
}

// Plugin Path
if (! defined('HPH_PATH')) {
    define('HPH_PATH', plugin_dir_path(__FILE__));
}

// Plugin URL
if (! defined('HPH_URL')) {
    define('HPH_URL', plugin_dir_url(__FILE__));
}

// Class map for faster lookups
$class_map = [
    // Core Components
    __NAMESPACE__ . '\\Core\\Post_Types' => 'core/class-post-types.php',
    __NAMESPACE__ . '\\Core\\Taxonomies' => 'core/class-taxonomies.php',
    __NAMESPACE__ . '\\Core\\Database' => 'core/class-database.php',
    __NAMESPACE__ . '\\Core\\Listing_Transaction_Sync' => 'core/class-listing-transaction-sync.php',

    // User Management
    __NAMESPACE__ . '\\Users\\User_Roles_Manager' => 'users/class-user-roles-manager.php',
    __NAMESPACE__ . '\\Users\\User_Registration_Manager' => 'users/class-user-registration-manager.php',
    __NAMESPACE__ . '\\Users\\User_Dashboard_Manager' => 'users/class-user-dashboard.php',
    __NAMESPACE__ . '\\Users\\User_Agent_Sync' => 'users/class-user-agent-sync.php',

    // Dashboard
    __NAMESPACE__ . '\\Dashboard\\Agent_Dashboard_Data' => 'dashboard/class-agent-dashboard-data.php',

    // Fields
    __NAMESPACE__ . '\\Fields\\ACF_Field_Groups' => 'fields/class-acf-field-groups.php',
    __NAMESPACE__ . '\\Fields\\Enhanced\\Enhanced_ACF_Fields' => 'fields/enhanced/class-enhanced-acf-fields.php',
    __NAMESPACE__ . '\\Fields\\Compliance\\MLS_Compliance_Checker' => 'fields/compliance/class-mls-compliance-checker.php',

    // Users
    __NAMESPACE__ . '\\Users\\User_Roles_Manager' => 'users/class-user-roles-manager.php',
    __NAMESPACE__ . '\\Users\\User_Registration_Manager' => 'users/class-user-registration-manager.php',
    __NAMESPACE__ . '\\Users\\User_Dashboard_Manager' => 'users/class-user-dashboard-manager.php',

    // Dashboard
    __NAMESPACE__ . '\\Dashboard\\Agent_Dashboard' => 'dashboard/class-agent-dashboard.php'
];

/**
 * Load a class file and get its instance
 *
 * @param string $class_path Path to the class file relative to includes directory
 * @param string $class_name Full class name including namespace
 * @return object|null Class instance or null on failure
 */
function load_class($class_path, $class_name)
{
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
spl_autoload_register(function ($class) use ($class_map) {
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

// Initialize core functionality early
add_action('init', function () {
    // Load core classes that need to register things with WordPress
    $core_classes = [
        'core/class-post-types.php' => __NAMESPACE__ . '\\Core\\Post_Types',
        'core/class-taxonomies.php' => __NAMESPACE__ . '\\Core\\Taxonomies'
    ];

    foreach ($core_classes as $path => $class) {
        if (!load_class($path, $class)) {
            error_log('HPH: Failed to load core class: ' . $class);
        }
    }
}, 5); // Priority 5 to run early

// Add ACF Support
add_action('init', function () {
    if (!class_exists('ACF')) {
        return;
    }

    // Make post types available in ACF
    add_filter('acf/get_post_types', function ($post_types) {
        $list_type = get_post_type_object('listing');
        $oh_type = get_post_type_object('open_house');

        if ($list_type) {
            $post_types['listing'] = $list_type->labels->singular_name;
        }
        if ($oh_type) {
            $post_types['open_house'] = $oh_type->labels->singular_name;
        }
        return $post_types;
    }, 10);

    // Add post types to ACF location rules
    add_filter('acf/location/rule_values/post_type', function ($choices) {
        $list_type = get_post_type_object('listing');
        $oh_type = get_post_type_object('open_house');

        if ($list_type) {
            $choices['listing'] = $list_type->labels->singular_name;
        }
        if ($oh_type) {
            $choices['open_house'] = $oh_type->labels->singular_name;
        }
        return $choices;
    }, 10);

    // Add taxonomies to ACF location rules
    add_filter('acf/location/rule_values/taxonomy', function ($choices) {
        $taxonomies = ['property_type', 'listing_location'];
        foreach ($taxonomies as $tax_name) {
            $tax = get_taxonomy($tax_name);
            if ($tax) {
                $choices[$tax_name] = $tax->labels->singular_name;
            }
        }
        return $choices;
    }, 10);
}, 15); // After post types are registered

// Initialize the rest of the plugin
add_action('plugins_loaded', __NAMESPACE__ . '\\init_happy_place');

// Add test log entry
error_log('Happy Place Plugin: Debug logging test ' . date('Y-m-d H:i:s'));

// Initialize core components with debug logging
add_action('plugins_loaded', function () {
    error_log('HPH: Initializing core components');

    // Explicitly require the Post_Types class
    require_once HPH_PATH . 'includes/core/class-post-types.php';

    // Initialize Post Types
    if (class_exists('\\HappyPlace\\Core\\Post_Types')) {
        \HappyPlace\Core\Post_Types::initialize();
        error_log('HPH: Post_Types initialized successfully');
    } else {
        error_log('HPH: Post_Types class not found!');
    }
}, 5);

// Register activation hook
register_activation_hook(__FILE__, function () {
    error_log('HPH: Plugin activation hook triggered');

    // Add dashboard rewrite rules
    add_rewrite_rule(
        '^agent-dashboard/?$',
        'index.php?pagename=agent-dashboard',
        'top'
    );
    add_rewrite_rule(
        '^agent-dashboard/([^/]+)/?$',
        'index.php?pagename=agent-dashboard&section=$matches[1]',
        'top'
    );

    // Flush rewrite rules
    flush_rewrite_rules(true);

    do_action('happy_place_activated');
});

// Load plugin text domain
add_action('init', function () {
    load_plugin_textdomain('happy-place', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Create dashboard page if it doesn't exist
add_action('init', function () {
    $page = get_page_by_path('agent-dashboard');
    if (!$page) {
        wp_insert_post([
            'post_title' => 'Agent Dashboard',
            'post_name' => 'agent-dashboard',
            'post_type' => 'page',
            'post_status' => 'publish',
            'meta_input' => [
                '_wp_page_template' => 'templates/template-dashboard.php'
            ]
        ]);
        error_log('HPH: Created agent dashboard page');
    }
}, 20); // After post types are registered

/**
 * Initialize the plugin
 */
function init_happy_place()
{
    try {
        // Load remaining core components
        $core_classes = [
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

        // Initialize user management first (required for dashboard)
        if (!($roles_manager = load_class('users/class-user-roles-manager.php', __NAMESPACE__ . '\\Users\\User_Roles_Manager'))) {
            error_log('HPH: Failed to load User_Roles_Manager');
            return;
        }

        if (!($registration_manager = load_class('users/class-user-registration-manager.php', __NAMESPACE__ . '\\Users\\User_Registration_Manager'))) {
            error_log('HPH: Failed to load User_Registration_Manager');
            return;
        }

        if (!($dashboard_manager = load_class('users/class-user-dashboard-manager.php', __NAMESPACE__ . '\\Users\\User_Dashboard_Manager'))) {
            error_log('HPH: Failed to load User_Dashboard_Manager');
            return;
        }

        // Initialize dashboard after user management
        if (!($agent_dashboard = load_class('dashboard/class-agent-dashboard.php', __NAMESPACE__ . '\\Dashboard\\Agent_Dashboard'))) {
            error_log('HPH: Failed to load Agent_Dashboard');
            return;
        }

        // Initialize CSV Import Manager
        if (!($csv_import_manager = load_class('admin/class-csv-import-manager.php', __NAMESPACE__ . '\\Admin\\CSV_Import_Manager'))) {
            error_log('HPH: Failed to load CSV_Import_Manager');
            return;
        }

        // Initialize Admin Menu
        if (!($admin_menu = load_class('admin/class-admin-menu.php', __NAMESPACE__ . '\\Admin\\Admin_Menu'))) {
            error_log('HPH: Failed to load Admin_Menu');
            return;
        }

        // Initialize Admin Dashboard
        if (!($admin_dashboard = load_class('admin/class-admin-dashboard.php', __NAMESPACE__ . '\\Admin\\Admin_Dashboard'))) {
            error_log('HPH: Failed to load Admin_Dashboard');
            return;
        }

        // Initialize Settings Page
        if (!($settings_page = load_class('admin/class-settings-page.php', __NAMESPACE__ . '\\Admin\\Settings_Page'))) {
            error_log('HPH: Failed to load Settings_Page');
            return;
        }

        // Add rewrite rules for dashboard
        add_action('init', function () {
            add_rewrite_rule(
                '^agent-dashboard/?$',
                'index.php?pagename=agent-dashboard',
                'top'
            );
            add_rewrite_rule(
                '^agent-dashboard/([^/]+)/?$',
                'index.php?pagename=agent-dashboard&section=$matches[1]',
                'top'
            );
        });

        // Add query vars
        add_filter('query_vars', function ($vars) {
            $vars[] = 'section';
            return $vars;
        });

        // Filter the template hierarchy to load plugin or theme templates
        add_filter('template_include', function ($template) {
            if (is_page()) {
                $page_template = get_post_meta(get_the_ID(), '_wp_page_template', true);

                // Check for both template names
                if ($page_template === 'agent-dashboard.php' || $page_template === 'templates/template-dashboard.php') {
                    // First check theme template
                    $theme_template = get_template_directory() . '/templates/template-dashboard.php';
                    if (file_exists($theme_template)) {
                        return $theme_template;
                    }

                    // Fallback to plugin template
                    $plugin_template = plugin_dir_path(__FILE__) . 'templates/agent-dashboard.php';
                    if (file_exists($plugin_template)) {
                        return $plugin_template;
                    }
                }
            }
            return $template;
        });

        error_log('HPH: Plugin initialized successfully');
    } catch (\Throwable $e) {
        error_log('HPH: Error during initialization: ' . $e->getMessage());
    }
}

require_once plugin_dir_path(__FILE__) . 'includes/class-cache-manager.php';
