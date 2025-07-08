<?php
/**
 * Theme Functions for HPH Theme
 * Author: The Happy Place Team
 */

if (!defined('ABSPATH')) {
    exit;
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
    }

    private function setup_theme(): void {
        add_action('after_setup_theme', [$this, 'theme_supports']);
        add_action('init', [$this, 'register_image_sizes']);

        // Load includes
        $this->load_includes();
    }

    private function load_includes(): void {
        $files = [
            'includes/listings.php',
            'inc/shortcodes.php'
        ];

        foreach ($files as $file) {
            $path = get_template_directory() . '/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public function theme_supports(): void {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', [
            'search-form', 
            'gallery', 
            'caption', 
            'comment-form', 
            'comment-list'
        ]);

        // Custom post type support
        add_theme_support('post-type-listing');
        add_theme_support('post-type-agent');
        add_theme_support('post-type-openhouse');
        add_theme_support('post-type-transaction');
        add_theme_support('post-type-community');
        add_theme_support('post-type-city');
        add_theme_support('post-type-place');
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
        add_image_size('place-thumb', 480, 320, true);
        add_image_size('place-feature', 800, 600, true);
    }

    private function register_assets(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_theme_assets']);
    }

    public function enqueue_theme_assets(): void {
        $theme = wp_get_theme();
        $uri = get_template_directory_uri();
        $ver = $theme->get('Version');

        // Main theme stylesheet and scripts
        wp_enqueue_style('hph-theme', get_stylesheet_uri(), [], $ver); // Load consolidated style.css
        wp_enqueue_script('hph-core', $uri . '/assets/js/core.js', ['jquery'], $ver, true);
        wp_enqueue_script('hph-theme', $uri . '/assets/js/theme.js', ['jquery', 'hph-core'], $ver, true);
        
        // Dequeue unnecessary styles
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');

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
