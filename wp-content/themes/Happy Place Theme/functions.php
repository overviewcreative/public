<?php

/**
 * Happy Place Theme Functions
 * 
 * Main theme functions file for the Happy Place Real Estate Theme.
 * This file initializes the theme and loads all required components.
 * 
 * @package HappyPlace
 * @version 1.0.0
 * @author Happy Place Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// THEME CONSTANTS
// =============================================================================

define('HPH_THEME_VERSION', '1.0.0');
define('HPH_THEME_DIR', get_template_directory());
define('HPH_THEME_URI', get_template_directory_uri());

// =============================================================================
// MAIN THEME CLASS
// =============================================================================

class HPH_Theme
{
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor - Initialize theme
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->setup_hooks();
        $this->add_rewrite_rules();
    }

    /**
     * Load required files
     */
    private function load_dependencies(): void
    {
        $files = [
            'inc/class-theme-setup.php',          // Theme setup and configuration
            'inc/class-assets-manager.php',       // Asset loading and management
            'inc/class-template-loader.php',      // Custom template loading
            'inc/class-ajax-handler.php',         // AJAX request handling
            'inc/dashboard_manager.php',          // Agent dashboard functionality
            'inc/listing-helpers.php',            // Listing utility functions
            'inc/community-helpers.php',          // Community utility functions
            'inc/template-functions.php',         // Template helper functions
            'inc/template-tags.php',              // Custom template tags
            'inc/shortcodes.php',                 // Theme shortcodes
        ];

        foreach ($files as $file) {
            $path = HPH_THEME_DIR . '/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }

        // Include dashboard setup and verification
        require_once get_template_directory() . '/inc/dashboard-setup.php';

        // Include plugin integration
        require_once get_template_directory() . '/inc/plugin-integration.php';

        // Dashboard functions are in template-functions.php
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks(): void
    {
        add_action('after_setup_theme', [$this, 'theme_setup']);
        add_action('init', [$this, 'init_theme_features']);
        add_action('widgets_init', [$this, 'register_sidebars']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'load_custom_templates']);
    }
    // Clean up unnecessary assets
    public function cleanup_wp_assets(): void
    {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_script('wp-embed');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }

    /**
     * Theme setup - runs after WordPress is loaded
     */
    public function theme_setup(): void
    {
        // Theme supports
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', [
            'search-form',
            'gallery',
            'caption',
            'comment-form',
            'comment-list'
        ]);
        add_theme_support('custom-logo');
        add_theme_support('customize-selective-refresh-widgets');

        // Navigation menus
        register_nav_menus([
            'primary' => __('Primary Menu', 'happy-place'),
            'footer-links-1' => __('Footer Links 1', 'happy-place'),
            'footer-links-2' => __('Footer Links 2', 'happy-place'),
            'footer-links-3' => __('Footer Links 3', 'happy-place'),
            'footer-legal' => __('Footer Legal', 'happy-place'),
        ]);
    }

    /**
     * Initialize theme features
     */
    public function init_theme_features(): void
    {
        $this->register_image_sizes();
        $this->setup_google_maps_settings();
    }



    /**
     * Register custom image sizes
     */
    private function register_image_sizes(): void
    {
        // Property images
        add_image_size('listing-thumb', 480, 320, true);
        add_image_size('listing-gallery', 1200, 800, true);
        add_image_size('listing-hero', 1600, 600, true);

        // People images
        add_image_size('agent-avatar', 150, 150, true);
        add_image_size('agent-large', 480, 640, true);

        // Location images
        add_image_size('community-thumb', 480, 320, true);
        add_image_size('community-hero', 1600, 600, true);
        add_image_size('city-thumb', 480, 320, true);
        add_image_size('city-hero', 1600, 600, true);

        // Local places
        add_image_size('place-thumb', 320, 240, true);
        add_image_size('place-feature', 800, 600, true);
        add_image_size('place-map-marker', 64, 64, true);

        // Open houses
        add_image_size('open-house-thumb', 480, 320, true);
        add_image_size('open-house-gallery', 800, 600, true);
    }

    /**
     * Setup Google Maps API settings
     */
    private function setup_google_maps_settings(): void
    {
        add_action('admin_init', function () {
            register_setting('general', 'hph_google_maps_api_key', [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            ]);

            add_settings_section(
                'hph_maps_settings',
                __('Map Settings', 'happy-place'),
                function () {
                    echo '<p>' . __('Configure Google Maps integration.', 'happy-place') . '</p>';
                },
                'general'
            );

            add_settings_field(
                'hph_google_maps_api_key',
                __('Google Maps API Key', 'happy-place'),
                function () {
                    $key = get_option('hph_google_maps_api_key');
                    echo '<input type="text" class="regular-text" name="hph_google_maps_api_key" value="' . esc_attr($key) . '">';
                    echo '<p class="description">' . __('Enter your Google Maps API key.', 'happy-place') . '</p>';
                },
                'general',
                'hph_maps_settings'
            );
        });
    }

    /**
     * Register widget areas
     */
    public function register_sidebars(): void
    {
        // Main sidebar
        register_sidebar([
            'name' => __('Sidebar', 'happy-place'),
            'id' => 'sidebar-1',
            'description' => __('Add widgets here.', 'happy-place'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>',
        ]);

        // Footer widgets
        for ($i = 1; $i <= 4; $i++) {
            register_sidebar([
                'name' => sprintf(__('Footer %d', 'happy-place'), $i),
                'id' => "footer-{$i}",
                'description' => sprintf(__('Footer widget area %d', 'happy-place'), $i),
                'before_widget' => '<div id="%1$s" class="widget %2$s">',
                'after_widget' => '</div>',
                'before_title' => '<h3 class="widget-title">',
                'after_title' => '</h3>',
            ]);
        }
    }

    /**
     * Add custom query variables
     */
    public function add_query_vars($vars): array
    {
        $custom_vars = [
            // Dashboard
            'happy_place_dashboard',
            'agent_dashboard',
            'section',

            // Search & Filters
            'search_term',
            'location',
            'city',
            'community',
            'place',
            'price_min',
            'price_max',
            'bedrooms',
            'bathrooms',
            'property_type',
            'features',
            'sort',
            'view',

            // Dates
            'date_from',
            'date_to',
            'time_from',
            'time_to',

            // Transactions
            'transaction_type',
            'transaction_date'
        ];

        return array_merge($vars, $custom_vars);
    }

    /**
     * Load custom templates
     */
    public function load_custom_templates($template): string
    {
        // Single post types
        if (is_singular()) {
            $post_type = get_post_type();
            if ($post_type) {
                $custom_template = HPH_THEME_DIR . "/templates/{$post_type}/single-{$post_type}.php";
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
        }

        // Archive post types
        if (is_post_type_archive()) {
            $queried_object = get_queried_object();
            if ($queried_object && !empty($queried_object->name)) {
                $custom_template = HPH_THEME_DIR . "/templates/{$queried_object->name}/archive-{$queried_object->name}.php";
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
        }

        return $template;
    }

    /**
     * Add rewrite rules for agent dashboard
     */
    public function add_rewrite_rules(): void
    {
        add_rewrite_tag('%section%', '([^&]+)');
        add_rewrite_rule(
            'agent-dashboard/([^/]+)/?$',
            'index.php?pagename=agent-dashboard&section=$matches[1]',
            'top'
        );
    }

    /**
     * Format price for display
     */
    public static function format_price($price): string
    {
        if (!$price) return '';
        return '$' . number_format(floatval($price));
    }
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Generate HTML for a listing flyer
 * 
 * Generates a printable flyer for a specific listing by ID.
 * Uses the listing-flyer.php template to render the HTML.
 * 
 * @param int $listing_id The ID of the listing post
 * @return string The generated HTML for the flyer
 */
function hph_generate_listing_flyer(int $listing_id): string
{
    global $post;
    $post = get_post($listing_id);
    setup_postdata($post);

    ob_start();
    include get_template_directory() . '/templates/graphics/listing-flyer.php';
    $html = ob_get_clean();

    wp_reset_postdata();
    return $html;
}

// hph_is_dashboard function is now in template-functions.php

// Dashboard functions are now in template-functions.php

/**
 * Count posts in a community
 */
function hph_count_community_listings(int $community_id): int
{
    if (!$community_id) return 0;

    $args = [
        'post_type' => 'listing',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'community',
                'value' => $community_id,
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1,
        'fields' => 'ids'
    ];

    return count(get_posts($args));
}

/**
 * Get community statistics
 */
function hph_get_community_stats(int $community_id): array
{
    if (!$community_id) return [];

    $listings = get_posts([
        'post_type' => 'listing',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'community',
                'value' => $community_id,
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1
    ]);

    if (empty($listings)) return [];

    $total_price = 0;
    $total_sqft = 0;
    $total_homes = count($listings);

    foreach ($listings as $listing) {
        $price = (float) get_field('price', $listing->ID);
        $sqft = (float) get_field('square_feet', $listing->ID);

        if ($price > 0) $total_price += $price;
        if ($sqft > 0) $total_sqft += $sqft;
    }

    return [
        'avg_price' => $total_homes > 0 ? HPH_Theme::format_price($total_price / $total_homes) : 0,
        'total_homes' => $total_homes,
        'avg_sqft' => $total_homes > 0 ? round($total_sqft / $total_homes) : 0
    ];
}

/**
 * Format listing address
 */
function hph_format_listing_address(int $listing_id): string
{
    $full_address = get_field('full_address', $listing_id);

    if (is_string($full_address)) {
        return $full_address;
    }

    if (is_array($full_address)) {
        $components = [];
        if (!empty($full_address['street_address'])) $components[] = $full_address['street_address'];
        if (!empty($full_address['city'])) $components[] = $full_address['city'];
        if (!empty($full_address['region'])) $components[] = $full_address['region'];
        if (!empty($full_address['zip_code'])) $components[] = $full_address['zip_code'];
        return implode(', ', $components);
    }

    return '';
}

/**
 * Get listing bathrooms count
 */
function hph_get_listing_bathrooms(int $listing_id): float
{
    $full_baths = (float) get_field('full_bathrooms', $listing_id);
    $partial_baths = (float) get_field('partial_bathrooms', $listing_id);
    return $full_baths + ($partial_baths * 0.5);
}

/**
 * Get listing main photo
 */
function hph_get_listing_photo(int $listing_id, string $size = 'medium'): string
{
    // Try main photo field
    $main_photo = get_field('main_photo', $listing_id);
    if ($main_photo) {
        return is_array($main_photo) ? ($main_photo['sizes'][$size] ?? $main_photo['url']) : $main_photo;
    }

    // Try gallery
    $gallery = get_field('photo_gallery', $listing_id);
    if ($gallery && !empty($gallery)) {
        $first_image = $gallery[0];
        return is_array($first_image) ? ($first_image['sizes'][$size] ?? $first_image['url']) : $first_image;
    }

    // Try featured image
    if (has_post_thumbnail($listing_id)) {
        return get_the_post_thumbnail_url($listing_id, $size);
    }

    return get_theme_file_uri('assets/images/property-placeholder.jpg');
}

// =============================================================================
// INITIALIZATION
// =============================================================================

/**
 * Initialize the theme
 */
function hph_theme_init(): HPH_Theme
{
    return HPH_Theme::instance();
}

// Start the theme
hph_theme_init();

/**
 * Initialize ACF Options Pages
 */
if (function_exists('acf_add_options_page')) {
    acf_add_options_page([
        'page_title'  => __('Company Settings', 'happy-place'),
        'menu_title'  => __('Company Settings', 'happy-place'),
        'menu_slug'   => 'company-settings',
        'capability'  => 'manage_options',
        'redirect'    => false,
        'position'    => 2
    ]);
}

/**
 * Register ACF field groups
 */
function hph_register_acf_fields(): void
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Agent Details Field Group
    acf_add_local_field_group([
        'key' => 'group_agent_details',
        'title' => 'Agent Details',
        'fields' => [
            [
                'key' => 'field_agent_photo',
                'label' => 'Agent Photo',
                'name' => 'agent_photo',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
            ],
            [
                'key' => 'field_agent_name',
                'label' => 'Full Name',
                'name' => 'name',
                'type' => 'text',
                'required' => 1,
            ],
            [
                'key' => 'field_agent_title',
                'label' => 'Title/Position',
                'name' => 'title',
                'type' => 'text',
            ],
            [
                'key' => 'field_agent_phone',
                'label' => 'Phone Number',
                'name' => 'phone',
                'type' => 'text',
            ],
            [
                'key' => 'field_agent_email',
                'label' => 'Email Address',
                'name' => 'email',
                'type' => 'email',
            ],
            [
                'key' => 'field_agent_bio',
                'label' => 'Biography',
                'name' => 'bio',
                'type' => 'textarea',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'edit',
                ],
                [
                    'param' => 'user_role',
                    'operator' => '==',
                    'value' => 'administrator',
                ],
            ],
            [
                [
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'edit',
                ],
                [
                    'param' => 'user_role',
                    'operator' => '==',
                    'value' => 'hph_agent',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
    ]);
}
add_action('acf/init', 'hph_register_acf_fields');

/**
 * Register custom page templates
 */
function hph_register_page_templates()
{
    add_filter('theme_page_templates', function ($templates) {
        $templates['templates/agent-dashboard.php'] = __('Agent Dashboard', 'happy-place');
        return $templates;
    });
}
add_action('init', 'hph_register_page_templates');

/**
 * Force rewrite rules flush on theme version change
 */
add_action('init', function () {
    $version = get_option('hph_theme_version', '');
    if ($version !== HPH_THEME_VERSION) {
        flush_rewrite_rules();
        update_option('hph_theme_version', HPH_THEME_VERSION);
    }
}, 1);

/**
 * Format price for display
 * 
 * @param mixed $price The price to format
 * @param bool $show_zero Whether to show $0 or return empty string
 * @return string Formatted price with currency symbol
 */
function hph_format_price($price, bool $show_zero = false): string
{
    if (!$price && !$show_zero) {
        return '';
    }
    return '$' . number_format(floatval($price));
}

/**
 * Run dashboard setup on admin init if not already done
 */
function hph_maybe_setup_dashboard()
{
    if (!get_option('hph_dashboard_setup_complete')) {
        hph_setup_dashboard();
        update_option('hph_dashboard_setup_complete', true);
    }
}
add_action('admin_init', 'hph_maybe_setup_dashboard');
