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
        // Only add the hook if we're not in activation context
        if (!defined('WP_INSTALLING') || !WP_INSTALLING) {
            add_action('init', [$this, 'register_post_types']);
        }
    }

    /**
     * Register all custom post types
     */
    public function register_post_types(): void {
        // Property post type
        register_post_type('property', [
            'labels' => [
                'name'               => __('Properties', 'happy-place'),
                'singular_name'      => __('Property', 'happy-place'),
                'menu_name'         => __('Properties', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Property', 'happy-place'),
                'edit_item'         => __('Edit Property', 'happy-place'),
                'new_item'          => __('New Property', 'happy-place'),
                'view_item'         => __('View Property', 'happy-place'),
                'search_items'      => __('Search Properties', 'happy-place'),
                'not_found'         => __('No properties found', 'happy-place'),
                'not_found_in_trash'=> __('No properties found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'menu_icon'           => 'dashicons-building',
            'rewrite'            => ['slug' => 'properties'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
        ]);

        // Team post type
        register_post_type('team', [
            'labels' => [
                'name'               => __('Teams', 'happy-place'),
                'singular_name'      => __('Team', 'happy-place'),
                'menu_name'         => __('Teams', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Team', 'happy-place'),
                'edit_item'         => __('Edit Team', 'happy-place'),
                'new_item'          => __('New Team', 'happy-place'),
                'view_item'         => __('View Team', 'happy-place'),
                'search_items'      => __('Search Teams', 'happy-place'),
                'not_found'         => __('No teams found', 'happy-place'),
                'not_found_in_trash'=> __('No teams found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-groups',
            'rewrite'            => ['slug' => 'teams'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
        ]);
    }
}

// Initialize Post Types
add_action('plugins_loaded', function() {
    Post_Types::get_instance();
});