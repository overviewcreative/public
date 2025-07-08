<?php
/**
 * Theme Functions for HPH Theme
 * Author: The Happy Place Team
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('HPH_THEME_VERSION', '1.0.0');
define('HPH_THEME_DIR', get_template_directory());
define('HPH_THEME_URI', get_template_directory_uri());

/**
 * Count the number of listing posts associated with a community
 *
 * @param int $community_id The ID of the community post
 * @return int The number of listings in the community
 */
function count_posts_in_community(int $community_id): int {
    if (!$community_id) {
        return 0;
    }

    $args = array(
        'post_type' => 'listing',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'community',
                'value' => $community_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1,
        'fields' => 'ids' // Only get post IDs for better performance
    );

    $listings = get_posts($args);
    return count($listings);
}

/**
 * Get statistics for a community including average price, total homes, etc.
 *
 * @param int $community_id The ID of the community post
 * @return array Statistics for the community
 */
function get_community_stats(int $community_id): array {
    if (!$community_id) {
        return array();
    }

    $args = array(
        'post_type' => 'listing',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'community',
                'value' => $community_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    );

    $listings = get_posts($args);
    
    if (empty($listings)) {
        return array();
    }

    $total_price = 0;
    $total_sqft = 0;
    $total_homes = count($listings);

    foreach ($listings as $listing) {
        $price = (float)get_post_meta($listing->ID, 'price', true);
        $sqft = (float)get_post_meta($listing->ID, 'square_feet', true);
        
        if ($price > 0) {
            $total_price += $price;
        }
        
        if ($sqft > 0) {
            $total_sqft += $sqft;
        }
    }

    return array(
        'avg_price' => $total_homes > 0 ? HPH_Theme::format_price($total_price / $total_homes) : 0,
        'total_homes' => $total_homes,
        'avg_sqft' => $total_homes > 0 ? round($total_sqft / $total_homes) : 0
    );
}

class HPH_Theme {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->setup_theme();
        $this->register_assets();
        $this->setup_ajax_handlers();
        $this->register_widgets();
        $this->setup_cache_tools(); // Add this line
    }

    private function setup_theme(): void {
        add_action('after_setup_theme', [$this, 'theme_supports']);
        add_action('init', [$this, 'register_image_sizes']);
        add_action('init', [$this, 'register_nav_menus']);
        
        // Add template loader
        add_filter('template_include', [$this, 'load_custom_templates']);

        // Load includes
        $this->load_includes();
    }

    private function load_includes(): void {
        $files = [
            'inc/template-functions.php',
            'inc/template-tags.php',
            'inc/shortcodes.php'
        ];

        foreach ($files as $file) {
            $path = HPH_THEME_DIR . '/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Load templates from custom directories
     */
    public function load_custom_templates($template): string {
        $post_type = get_post_type();
        
        if (is_singular() && !empty($post_type)) {
            $custom_template = HPH_THEME_DIR . "/templates/{$post_type}/single-{$post_type}.php";
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive() && !empty($post_type)) {
            $custom_template = HPH_THEME_DIR . "/templates/{$post_type}/archive-{$post_type}.php";
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }

    public function theme_supports(): void {
        // Core theme supports
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

        // Custom post type support
        add_theme_support('post-type-listing');
        add_theme_support('post-type-agent');
        add_theme_support('post-type-open-house');    
        add_theme_support('post-type-local-place');   
        add_theme_support('post-type-transaction');
        add_theme_support('post-type-community');
        add_theme_support('post-type-city');
        add_theme_support('post-type-team');
    }

    public function register_nav_menus(): void {
        register_nav_menus([
            'primary' => __('Primary Menu', 'happy-place'),
            'footer-links-1' => __('Footer Links 1', 'happy-place'),
            'footer-links-2' => __('Footer Links 2', 'happy-place'),
            'footer-links-3' => __('Footer Links 3', 'happy-place'),
            'footer-legal' => __('Footer Legal', 'happy-place'),
        ]);
    }

    private function register_widgets(): void {
        add_action('widgets_init', [$this, 'register_widget_areas']);
    }

    public function register_widget_areas(): void {
        // Main sidebar
        register_sidebar([
            'name'          => __('Sidebar', 'happy-place'),
            'id'            => 'sidebar-1',
            'description'   => __('Add widgets here.', 'happy-place'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
        
        // Footer widgets
        for ($i = 1; $i <= 4; $i++) {
            register_sidebar([
                'name'          => sprintf(__('Footer %d', 'happy-place'), $i),
                'id'            => "footer-{$i}",
                'description'   => sprintf(__('Footer widget area %d', 'happy-place'), $i),
                'before_widget' => '<div id="%1$s" class="widget %2$s">',
                'after_widget'  => '</div>',
                'before_title'  => '<h3 class="widget-title">',
                'after_title'   => '</h3>',
            ]);
        }
    }

    public function register_image_sizes(): void {
        // Property related sizes
        add_image_size('listing-thumb', 480, 320, true);
        add_image_size('listing-gallery', 1200, 800, true);
        
        // People related sizes
        add_image_size('agent-avatar', 150, 150, true);
        add_image_size('agent-large', 480, 640, true);
        
        // Location related sizes
        add_image_size('community-thumb', 480, 320, true);
        add_image_size('community-hero', 1600, 600, true);
        add_image_size('city-thumb', 480, 320, true);
        add_image_size('city-hero', 1600, 600, true);
        
        // Local places sizes
        add_image_size('place-thumb', 320, 240, true);     
        add_image_size('place-feature', 800, 600, true);   
        add_image_size('place-map-marker', 64, 64, true);  
        
        // Open house sizes
        add_image_size('open-house-thumb', 480, 320, true); 
        add_image_size('open-house-gallery', 800, 600, true); 
    }

    private function register_assets(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_theme_assets']);
    }

    public function enqueue_theme_assets(): void {
        $theme = wp_get_theme();
        $uri = get_template_directory_uri();
        $ver = $theme->get('Version');

        // Main theme stylesheet and scripts
        wp_enqueue_style('hph-theme', get_stylesheet_uri(), [], $ver);
        wp_enqueue_script('hph-core', $uri . '/assets/js/core.js', ['jquery'], $ver, true);
        wp_enqueue_script('hph-theme', $uri . '/assets/js/theme.js', ['jquery', 'hph-core'], $ver, true);
        
        // Dequeue unnecessary styles
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');

        // Load post type specific styles
        $post_type = get_post_type();
        if ($post_type) {
            $style_path = "/assets/css/post-types/{$post_type}.css";
            if (file_exists(HPH_THEME_DIR . $style_path)) {
                wp_enqueue_style(
                    "hph-{$post_type}", 
                    $uri . $style_path,
                    ['hph-theme'],
                    $ver
                );
            }
        }

        // Property related pages
        if (is_post_type_archive(['listing', 'openhouse']) || 
            is_singular(['listing', 'openhouse'])) {
            wp_enqueue_script('hph-listing', $uri . '/assets/js/listing.js', ['jquery', 'hph-core'], $ver, true);
            
            // Google Maps integration
            $maps_api_key = get_option('hph_google_maps_api_key', '');
            if ($maps_api_key) {
                wp_enqueue_script('google-maps', 
                    "https://maps.googleapis.com/maps/api/js?key={$maps_api_key}", 
                    [], null, true);
            }
        }

        // Gallery pages - Slick Carousel for property galleries
        if (is_singular(['listing', 'community', 'city', 'place'])) {
            wp_enqueue_style('slick', 
                'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', 
                ['hph-theme'], '1.8.1');
            wp_enqueue_style('slick-theme', 
                'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', 
                ['slick', 'hph-theme'], '1.8.1');
            wp_enqueue_script('slick', 
                'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', 
                ['jquery', 'hph-core'], '1.8.1', true);
        }

        // Script localization
        wp_localize_script('hph-core', 'happyplace', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_search_nonce'),
            'markerIcon' => $uri . '/assets/images/marker.png'
        ]);
    }

    public function cleanup_assets(): void {
        // Remove unnecessary WordPress core assets
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_script('wp-embed');
        
        // Remove emoji scripts
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }

    private function setup_ajax_handlers(): void {
        add_action('wp_ajax_hph_search_properties', [$this, 'ajax_search_properties']);
        add_action('wp_ajax_nopriv_hph_search_properties', [$this, 'ajax_search_properties']);
        
        add_action('wp_ajax_hph_contact_agent', [$this, 'ajax_contact_agent']);
        add_action('wp_ajax_nopriv_hph_contact_agent', [$this, 'ajax_contact_agent']);
    }

    public function ajax_search_properties(): void {
        check_ajax_referer('hph_search_nonce', 'security');

        $args = [
            'post_type' => 'listing',
            'posts_per_page' => 50,
            'meta_query' => ['relation' => 'AND']
        ];

        // Price Filter
        if (!empty($_POST['filters']['price_min']) || !empty($_POST['filters']['price_max'])) {
            $args['meta_query'][] = [
                'key' => 'price',
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
                'value' => [
                    !empty($_POST['filters']['price_min']) ? intval($_POST['filters']['price_min']) : 0,
                    !empty($_POST['filters']['price_max']) ? intval($_POST['filters']['price_max']) : 999999999
                ]
            ];
        }

        // Additional filters can be added here

        $query = new \WP_Query($args);
        $properties = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $properties[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'price' => get_field('price'),
                    'bedrooms' => get_field('bedrooms'),
                    'bathrooms' => get_field('bathrooms'),
                    'permalink' => get_permalink(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium')
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json_success([
            'properties' => $properties,
            'total' => $query->found_posts
        ]);
    }

    public function ajax_contact_agent(): void {
        check_ajax_referer('hph_search_nonce', 'security');

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $message = sanitize_textarea_field($_POST['message']);
        $property_id = intval($_POST['property_id']);

        $agent_email = get_field('email', get_field('agent', $property_id));

        if (!$agent_email) {
            wp_send_json_error('No agent contact found');
        }

        $email_subject = "Property Inquiry: " . get_the_title($property_id);
        $email_body = "Name: {$name}\n";
        $email_body .= "Email: {$email}\n";
        $email_body .= "Message:\n{$message}\n";
        $email_body .= "Property Link: " . get_permalink($property_id);

        $result = wp_mail($agent_email, $email_subject, $email_body, [
            "From: {$name} <{$email}>"
        ]);

        $result 
            ? wp_send_json_success('Message sent')
            : wp_send_json_error('Failed to send message');
    }

    private function setup_cache_tools(): void {
        add_action('admin_menu', [$this, 'add_cache_tool_page']);
        add_action('admin_init', [$this, 'handle_cache_clear']);
        add_action('admin_notices', [$this, 'display_cache_notices']);
    }

    public function add_cache_tool_page(): void {
        add_management_page(
            __('Cache Tools', 'happy-place'),
            __('Cache Tools', 'happy-place'),
            'manage_options',
            'hph-cache-tools',
            [$this, 'render_cache_tool_page']
        );
    }

    public function render_cache_tool_page(): void {
        ?>
        <div class="wrap">
            <h1><?php _e('Cache Tools', 'happy-place'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Clear Cache', 'happy-place'); ?></h2>
                <p><?php _e('Clear various types of cache to refresh site content and settings.', 'happy-place'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('hph_clear_cache', 'hph_cache_nonce'); ?>
                    
                    <p><label>
                        <input type="checkbox" name="cache_types[]" value="transients" checked>
                        <?php _e('Clear Transients', 'happy-place'); ?>
                    </label></p>
                    
                    <p><label>
                        <input type="checkbox" name="cache_types[]" value="object">
                        <?php _e('Clear Object Cache', 'happy-place'); ?>
                    </label></p>
                    
                    <p><label>
                        <input type="checkbox" name="cache_types[]" value="permalinks">
                        <?php _e('Flush Permalinks', 'happy-place'); ?>
                    </label></p>

                    <p><label>
                        <input type="checkbox" name="cache_types[]" value="property">
                        <?php _e('Clear Property Cache', 'happy-place'); ?>
                    </label></p>
                    
                    <?php submit_button(__('Clear Selected Cache', 'happy-place')); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_cache_clear(): void {
        if (!isset($_POST['hph_cache_nonce']) || 
            !wp_verify_nonce($_POST['hph_cache_nonce'], 'hph_clear_cache')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $cache_types = $_POST['cache_types'] ?? [];
        $cleared = [];

        foreach ($cache_types as $type) {
            switch ($type) {
                case 'transients':
                    global $wpdb;
                    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
                    $cleared[] = __('Transients', 'happy-place');
                    break;

                case 'object':
                    wp_cache_flush();
                    $cleared[] = __('Object Cache', 'happy-place');
                    break;

                case 'permalinks':
                    flush_rewrite_rules();
                    $cleared[] = __('Permalinks', 'happy-place');
                    break;

                case 'property':
                    global $wpdb;
                    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_property_%'");
                    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_listing_%'");
                    $cleared[] = __('Property Cache', 'happy-place');
                    break;
            }
        }

        if (!empty($cleared)) {
            set_transient('hph_cache_cleared', $cleared, 30);
        }
    }

    public function display_cache_notices(): void {
        $cleared = get_transient('hph_cache_cleared');
        if ($cleared) {
            delete_transient('hph_cache_cleared');
            $message = sprintf(
                __('Successfully cleared: %s', 'happy-place'),
                implode(', ', $cleared)
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }

    /**
     * Helper function to format price
     */
    public static function format_price($price): string {
        if (!$price) return '';
        return '$' . number_format(floatval($price));
    }
}

// Initialize the theme
function hph_theme_init() {
    return HPH_Theme::instance();
}
hph_theme_init();

// Add query vars for all post types
function hph_add_query_vars($vars) {
    $search_vars = [
        // Location vars
        'location', 'city', 'community', 'place',
        
        // Property vars
        'price_min', 'price_max', 'bedrooms', 'bathrooms', 
        'property_type', 'features', 
        
        // Open house vars
        'date_from', 'date_to', 'time_from', 'time_to',
        
        // Transaction vars
        'transaction_type', 'transaction_date',
        
        // Common vars
        'search_term', 'sort', 'view'
    ];
    return array_merge($vars, $search_vars);
}
add_filter('query_vars', 'hph_add_query_vars');
