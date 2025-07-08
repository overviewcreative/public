<?php
namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Types Manager
 * 
 * Handles registration and management of all custom post types
 */
class Post_Types {
    private static ?self $instance = null;
    
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Use standard priority (10) to avoid conflicts
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
    }

    /**
     * Register all custom post types
     */
    public function register_post_types(): void {
        // Listing post type
        register_post_type('listing', [
            'labels' => [
                'name' => __('Listings', 'happy-place'),
                'singular_name' => __('Listing', 'happy-place'),
                'add_new' => __('Add New', 'happy-place'),
                'add_new_item' => __('Add New Listing', 'happy-place'),
                'edit_item' => __('Edit Listing', 'happy-place'),
                'new_item' => __('New Listing', 'happy-place'),
                'view_item' => __('View Listing', 'happy-place'),
                'search_items' => __('Search Listings', 'happy-place'),
                'not_found' => __('No listings found', 'happy-place'),
                'not_found_in_trash' => __('No listings found in Trash', 'happy-place'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-admin-home',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'rewrite' => ['slug' => 'listings']
        ]);

        // Agent post type
        register_post_type('agent', [
            'labels' => [
                'name' => __('Agents', 'happy-place'),
                'singular_name' => __('Agent', 'happy-place'),
                'add_new' => __('Add New', 'happy-place'),
                'add_new_item' => __('Add New Agent', 'happy-place'),
                'edit_item' => __('Edit Agent', 'happy-place'),
                'new_item' => __('New Agent', 'happy-place'),
                'view_item' => __('View Agent', 'happy-place'),
                'search_items' => __('Search Agents', 'happy-place'),
                'not_found' => __('No agents found', 'happy-place'),
                'not_found_in_trash' => __('No agents found in Trash', 'happy-place'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-businessperson',
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => 'agents']
        ]);

        // Community post type
        register_post_type('community', [
            'labels' => [
                'name' => __('Communities', 'happy-place'),
                'singular_name' => __('Community', 'happy-place'),
                'add_new' => __('Add New', 'happy-place'),
                'add_new_item' => __('Add New Community', 'happy-place'),
                'edit_item' => __('Edit Community', 'happy-place'),
                'new_item' => __('New Community', 'happy-place'),
                'view_item' => __('View Community', 'happy-place'),
                'search_items' => __('Search Communities', 'happy-place'),
                'not_found' => __('No communities found', 'happy-place'),
                'not_found_in_trash' => __('No communities found in Trash', 'happy-place'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-groups',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'rewrite' => ['slug' => 'communities']
        ]);

        // Open House post type
        register_post_type('open-house', [
            'labels' => [
                'name' => __('Open Houses', 'happy-place'),
                'singular_name' => __('Open House', 'happy-place'),
                'add_new' => __('Add New', 'happy-place'),
                'add_new_item' => __('Add New Open House', 'happy-place'),
                'edit_item' => __('Edit Open House', 'happy-place'),
                'new_item' => __('New Open House', 'happy-place'),
                'view_item' => __('View Open House', 'happy-place'),
                'search_items' => __('Search Open Houses', 'happy-place'),
                'not_found' => __('No open houses found', 'happy-place'),
                'not_found_in_trash' => __('No open houses found in Trash', 'happy-place'),
            ],
            'public' => true,
            'show_in_menu' => 'edit.php?post_type=listing',
            'show_in_rest' => true,
            'supports' => ['title', 'editor'],
            'rewrite' => ['slug' => 'open-houses']
        ]);

        // Transaction post type
        register_post_type('transaction', [
            'labels' => [
                'name' => __('Transactions', 'happy-place'),
                'singular_name' => __('Transaction', 'happy-place'),
                'add_new' => __('Add New', 'happy-place'),
                'add_new_item' => __('Add New Transaction', 'happy-place'),
                'edit_item' => __('Edit Transaction', 'happy-place'),
                'new_item' => __('New Transaction', 'happy-place'),
                'view_item' => __('View Transaction', 'happy-place'),
                'search_items' => __('Search Transactions', 'happy-place'),
                'not_found' => __('No transactions found', 'happy-place'),
                'not_found_in_trash' => __('No transactions found in Trash', 'happy-place'),
            ],
            'public' => true,
            'show_in_menu' => 'edit.php?post_type=listing',
            'show_in_rest' => true,
            'supports' => ['title', 'editor'],
            'rewrite' => ['slug' => 'transactions']
        ]);

        // Flush rewrite rules on first run
        if (get_option('hph_flush_rewrite_rules') === 'yes') {
            flush_rewrite_rules();
            delete_option('hph_flush_rewrite_rules');
        }
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies(): void {
        // Property Type taxonomy
        register_taxonomy('property-type', ['listing'], [
            'hierarchical' => true,
            'labels' => [
                'name' => _x('Property Types', 'taxonomy general name', 'happy-place'),
                'singular_name' => _x('Property Type', 'taxonomy singular name', 'happy-place'),
                'search_items' => __('Search Property Types', 'happy-place'),
                'all_items' => __('All Property Types', 'happy-place'),
                'parent_item' => __('Parent Property Type', 'happy-place'),
                'parent_item_colon' => __('Parent Property Type:', 'happy-place'),
                'edit_item' => __('Edit Property Type', 'happy-place'),
                'update_item' => __('Update Property Type', 'happy-place'),
                'add_new_item' => __('Add New Property Type', 'happy-place'),
                'new_item_name' => __('New Property Type Name', 'happy-place'),
                'menu_name' => __('Property Types', 'happy-place')
            ],
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'property-type']
        ]);

        // Listing Status taxonomy
        register_taxonomy('listing-status', ['listing'], [
            'hierarchical' => true,
            'labels' => [
                'name' => _x('Listing Statuses', 'taxonomy general name', 'happy-place'),
                'singular_name' => _x('Listing Status', 'taxonomy singular name', 'happy-place'),
                'search_items' => __('Search Listing Statuses', 'happy-place'),
                'all_items' => __('All Listing Statuses', 'happy-place'),
                'parent_item' => __('Parent Listing Status', 'happy-place'),
                'parent_item_colon' => __('Parent Listing Status:', 'happy-place'),
                'edit_item' => __('Edit Listing Status', 'happy-place'),
                'update_item' => __('Update Listing Status', 'happy-place'),
                'add_new_item' => __('Add New Listing Status', 'happy-place'),
                'new_item_name' => __('New Listing Status Name', 'happy-place'),
                'menu_name' => __('Listing Statuses', 'happy-place')
            ],
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'listing-status']
        ]);
    }
}

// Initialize Post Types
add_action('plugins_loaded', function() {
    Post_Types::get_instance();
});