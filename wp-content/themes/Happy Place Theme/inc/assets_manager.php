<?php
/**
 * Assets Manager
 * 
 * Handles all theme asset loading - EXACT REPLICA of original behavior
 * 
 * @package HappyPlace
 * @since 1.0.0
 */

class HPH_Assets_Manager {
    private static ?self $instance = null;
    
    public static function instance(): self {
        return self::$instance ??= new self();
    }
    
    private function __construct() {
        // Use same action as original
        add_action('wp_enqueue_scripts', [$this, 'happyplace_enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('script_loader_tag', [$this, 'add_async_defer'], 10, 2);
    }
    
    /**
     * Main asset enqueue function - EXACT REPLICA of original
     */
    public function happyplace_enqueue_assets(): void {
        $theme_version = HPH_THEME_VERSION;
        $theme_uri = get_template_directory_uri();
        $fa_version = '6.4.2'; // Font Awesome version

        // Font Awesome - EXACT SAME AS ORIGINAL
        wp_enqueue_style('font-awesome', 
            "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/{$fa_version}/css/all.min.css",
            [],
            $fa_version
        );

        // Core styles - EXACT SAME AS ORIGINAL  
        wp_enqueue_style('happyplace-main', $theme_uri . '/style.css', ['font-awesome'], $theme_version);

        // Listing related styles - EXACT SAME AS ORIGINAL
        wp_enqueue_style('happyplace-archive-listing', $theme_uri . '/assets/css/archive-listing.css', ['happyplace-main'], $theme_version);
        wp_enqueue_style('happyplace-listing-swipe-card', $theme_uri . '/assets/css/listing-swipe-card.css', ['happyplace-main'], $theme_version);
        wp_enqueue_style('happyplace-single-listing', $theme_uri . '/assets/css/single-listing.css', ['happyplace-main'], $theme_version);

        // Listing related scripts - EXACT SAME AS ORIGINAL
        wp_enqueue_script('happyplace-listing-swipe', $theme_uri . '/assets/js/listing-swipe-card.js', ['jquery'], $theme_version, true);
        wp_enqueue_script('happyplace-archive-listing', $theme_uri . '/assets/js/archive-listing.js', ['jquery'], $theme_version, true);
        wp_enqueue_script('happyplace-single-listing', $theme_uri . '/assets/js/single-listing.js', ['jquery'], $theme_version, true);

        // Additional conditional assets (improved but maintaining compatibility)
        $this->enqueue_conditional_assets();
        $this->localize_scripts();
    }
    
    /**
     * Enqueue conditional assets - simplified to maintain original behavior
     */
    private function enqueue_conditional_assets(): void {
        $theme_uri = get_template_directory_uri();
        $version = HPH_THEME_VERSION;
        
        // Dashboard assets (if needed)
        if (hph_is_dashboard()) {
            wp_enqueue_style('happyplace-dashboard', $theme_uri . '/assets/css/dashboard.css', ['happyplace-main'], $version);
            wp_enqueue_script('happyplace-dashboard', $theme_uri . '/assets/js/dashboard.js', ['jquery'], $version, true);
        }
        
        // Additional assets can be added here as needed
        // But keeping it simple to avoid breaking existing functionality
    }
    
    /**
     * Localize scripts - simplified version
     */
    private function localize_scripts(): void {
        // Main theme script localization (same as original concept)
        wp_localize_script('happyplace-listing-swipe', 'happyplace', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_search_nonce'),
            'theme_uri' => get_template_directory_uri()
        ]);
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
                get_template_directory_uri() . '/assets/js/admin/listing-admin.js',
                ['jquery', 'acf-input'],
                HPH_THEME_VERSION,
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