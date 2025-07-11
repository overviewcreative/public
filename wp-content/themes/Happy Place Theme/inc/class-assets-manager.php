<?php
/**
 * Assets Manager - Compatible with existing structure
 * 
 * @package HappyPlace
 * @since 1.0.0
 */

// Ensure constants are defined
if (!defined('HAPPY_PLACE_THEME_DIR')) {
    define('HAPPY_PLACE_THEME_DIR', get_template_directory());
}

if (!defined('HAPPY_PLACE_THEME_URI')) {
    define('HAPPY_PLACE_THEME_URI', get_template_directory_uri());
}

class HPH_Assets_Manager {
    private static ?self $instance = null;
    
    public static function instance(): self {
        return self::$instance ??= new self();
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('script_loader_tag', [$this, 'add_async_defer'], 10, 2);
    }
    
    /**
     * Register scripts - matches your existing pattern
     */
    public function register_scripts(): void {
        // Register Google Maps
        $maps_api_key = get_option('hph_google_maps_api_key') ?: get_theme_mod('google_maps_api_key');
        if ($maps_api_key) {
            wp_register_script(
                'google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($maps_api_key) . '&libraries=places',
                [],
                null,
                true
            );

            // Register MarkerClustererPlus
            wp_register_script(
                'markerclustererplus',
                HAPPY_PLACE_THEME_URI . '/assets/js/lib/markerclustererplus.min.js',
                ['google-maps'],
                '1.2.10',
                true
            );
        }
    }
    
    /**
     * Enqueue styles - combines your existing + original theme styles
     */
    public function enqueue_styles(): void {
        // Font Awesome (from original theme)
        wp_enqueue_style('font-awesome', 
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css',
            [],
            '6.4.2'
        );

        // Main theme stylesheet (from original theme)
        wp_enqueue_style(
            'happyplace-main',
            get_stylesheet_uri(),
            ['font-awesome'],
            wp_get_theme()->get('Version')
        );

        // Core styles (your existing)
        if (file_exists(HAPPY_PLACE_THEME_DIR . '/assets/css/core.css')) {
            wp_enqueue_style(
                'happy-place-core',
                HAPPY_PLACE_THEME_URI . '/assets/css/core.css',
                ['happyplace-main'],
                filemtime(HAPPY_PLACE_THEME_DIR . '/assets/css/core.css')
            );
        }

        // Listing styles (your existing)
        if (file_exists(HAPPY_PLACE_THEME_DIR . '/assets/css/listing.css')) {
            wp_enqueue_style(
                'happy-place-listing',
                HAPPY_PLACE_THEME_URI . '/assets/css/listing.css',
                ['happy-place-core'],
                filemtime(HAPPY_PLACE_THEME_DIR . '/assets/css/listing.css')
            );
        }

        // Original theme listing styles (preserve these)
        $original_listing_styles = [
            'happyplace-archive-listing' => '/assets/css/archive-listing.css',
            'happyplace-listing-swipe-card' => '/assets/css/listing-swipe-card.css',
            'happyplace-listing-list-card' => '/assets/css/listing-list-card.css',
            'happyplace-single-listing' => '/assets/css/single-listing.css'
        ];

        foreach ($original_listing_styles as $handle => $path) {
            if (file_exists(HAPPY_PLACE_THEME_DIR . $path)) {
                wp_enqueue_style(
                    $handle,
                    HAPPY_PLACE_THEME_URI . $path,
                    ['happyplace-main'],
                    wp_get_theme()->get('Version')
                );
            }
        }

        // Additional component styles (your existing)
        $component_styles = [
            'happy-place-listing-filters' => '/assets/css/listing-filters.css',
            'happy-place-map-info-window' => '/assets/css/map-info-window.css',
            'happy-place-map-clusters' => '/assets/css/map-clusters.css',
            'happyplace-maps' => '/assets/css/maps.css'
        ];

        foreach ($component_styles as $handle => $path) {
            if (file_exists(HAPPY_PLACE_THEME_DIR . $path)) {
                $deps = ['happy-place-listing'];
                if ($handle === 'happy-place-map-clusters') {
                    $deps[] = 'happy-place-map-info-window';
                }
                
                wp_enqueue_style(
                    $handle,
                    HAPPY_PLACE_THEME_URI . $path,
                    $deps,
                    filemtime(HAPPY_PLACE_THEME_DIR . $path)
                );
            }
        }

        // Dashboard styles (conditional)
        if (hph_is_dashboard() && file_exists(HAPPY_PLACE_THEME_DIR . '/assets/css/dashboard.css')) {
            wp_enqueue_style(
                'happyplace-dashboard',
                HAPPY_PLACE_THEME_URI . '/assets/css/dashboard.css',
                ['happyplace-main'],
                filemtime(HAPPY_PLACE_THEME_DIR . '/assets/css/dashboard.css')
            );
        }
    }
    
    /**
     * Enqueue scripts - combines your existing + original theme scripts
     */
    public function enqueue_scripts(): void {
        // Core scripts (your existing)
        if (file_exists(HAPPY_PLACE_THEME_DIR . '/assets/js/core.js')) {
            wp_enqueue_script(
                'happy-place-core',
                HAPPY_PLACE_THEME_URI . '/assets/js/core.js',
                ['jquery'],
                filemtime(HAPPY_PLACE_THEME_DIR . '/assets/js/core.js'),
                true
            );
        }

        // Theme scripts (your existing)
        if (file_exists(HAPPY_PLACE_THEME_DIR . '/assets/js/listing.js')) {
            wp_enqueue_script(
                'happy-place-listing',
                HAPPY_PLACE_THEME_URI . '/assets/js/listing.js',
                ['jquery'],
                filemtime(HAPPY_PLACE_THEME_DIR . '/assets/js/listing.js'),
                true
            );
        }

        // Original theme scripts (preserve these)
        $original_scripts = [
            'happyplace-listing-swipe' => '/assets/js/listing-swipe-card.js',
            'happyplace-archive-listing' => '/assets/js/archive-listing.js',
            'happyplace-single-listing' => '/assets/js/single-listing.js'
        ];

        foreach ($original_scripts as $handle => $path) {
            if (file_exists(HAPPY_PLACE_THEME_DIR . $path)) {
                wp_enqueue_script(
                    $handle,
                    HAPPY_PLACE_THEME_URI . $path,
                    ['jquery'],
                    wp_get_theme()->get('Version'),
                    true
                );
            }
        }

        // Advanced scripts (your existing)
        $advanced_scripts = [
            'happy-place-listing-filters' => [
                'path' => '/assets/js/listing-filters.js',
                'deps' => ['jquery', 'happy-place-listing']
            ],
            'happy-place-listing-map' => [
                'path' => '/assets/js/listing-map.js',
                'deps' => ['jquery', 'happy-place-listing', 'google-maps']
            ],
            'happy-place-listing-map-clusterer' => [
                'path' => '/assets/js/listing-map-clusterer.js',
                'deps' => ['jquery', 'happy-place-listing-map', 'markerclustererplus']
            ]
        ];

        foreach ($advanced_scripts as $handle => $config) {
            if (file_exists(HAPPY_PLACE_THEME_DIR . $config['path'])) {
                wp_enqueue_script(
                    $handle,
                    HAPPY_PLACE_THEME_URI . $config['path'],
                    $config['deps'],
                    filemtime(HAPPY_PLACE_THEME_DIR . $config['path']),
                    true
                );
            }
        }

        // Dashboard scripts (conditional)
        if (hph_is_dashboard() && file_exists(HAPPY_PLACE_THEME_DIR . '/assets/js/dashboard.js')) {
            wp_enqueue_script(
                'happyplace-dashboard',
                HAPPY_PLACE_THEME_URI . '/assets/js/dashboard.js',
                ['jquery'],
                filemtime(HAPPY_PLACE_THEME_DIR . '/assets/js/dashboard.js'),
                true
            );
        }

        // Localize scripts
        $this->localize_scripts();
    }
    
    /**
     * Localize scripts with theme data
     */
    private function localize_scripts(): void {
        // Localize core script
        if (wp_script_is('happy-place-core', 'enqueued')) {
            wp_localize_script('happy-place-core', 'happyplace', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_search_nonce'),
                'theme_uri' => HAPPY_PLACE_THEME_URI
            ]);
        }

        // Localize original listing scripts
        if (wp_script_is('happyplace-listing-swipe', 'enqueued')) {
            wp_localize_script('happyplace-listing-swipe', 'happyplace', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_search_nonce'),
                'theme_uri' => HAPPY_PLACE_THEME_URI
            ]);
        }

        // Localize map scripts
        if (wp_script_is('happy-place-listing-map', 'enqueued')) {
            wp_localize_script('happy-place-listing-map', 'hphMapSettings', [
                'defaultCenter' => [
                    'lat' => 38.9072,
                    'lng' => -77.0369
                ],
                'defaultZoom' => 12,
                'apiKey' => get_option('hph_google_maps_api_key'),
                'markerUrl' => HAPPY_PLACE_THEME_URI . '/assets/images/map-marker.svg'
            ]);
        }

        // Localize filter scripts
        if (wp_script_is('happy-place-listing-filters', 'enqueued')) {
            wp_localize_script('happy-place-listing-filters', 'hphConfig', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_filter_listings')
            ]);
        }

        // Dashboard localization
        if (wp_script_is('happyplace-dashboard', 'enqueued')) {
            wp_localize_script('happyplace-dashboard', 'hphDashboard', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'translations' => [
                    'saveSuccess' => __('Changes saved successfully.', 'happy-place'),
                    'saveError' => __('Error saving changes.', 'happy-place'),
                    'confirmDelete' => __('Are you sure you want to delete this item?', 'happy-place'),
                ]
            ]);
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        if (!$screen) return;
        
        if ($screen->post_type === 'listing') {
            wp_enqueue_script(
                'hph-listing-admin',
                HAPPY_PLACE_THEME_URI . '/assets/js/admin/listing-admin.js',
                ['jquery', 'acf-input'],
                filemtime(HAPPY_PLACE_THEME_DIR . '/assets/js/admin/listing-admin.js'),
                true
            );
            
            wp_localize_script('hph-listing-admin', 'hphAdmin', [
                'nonce' => wp_create_nonce('hph_admin_nonce')
            ]);
        }
    }
    
    /**
     * Add async/defer attributes to specific scripts
     */
    public function add_async_defer(string $tag, string $handle): string {
        if ('google-maps' === $handle) {
            return str_replace(' src', ' async defer src', $tag);
        }
        return $tag;
    }
}

// Initialize
HPH_Assets_Manager::instance();