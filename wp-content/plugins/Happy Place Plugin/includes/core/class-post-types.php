<?php
/**
 * File: includes/core/class-post-types.php
 * Cleaned Post Types Registration (removed redundant 'property' CPT)
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Post_Types {
    private static ?self $instance = null;
    
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_types'], 0);
        error_log('HPH: Post_Types constructor called');
    }

    public function register_post_types(): void {
        error_log('HPH: register_post_types() called');

        // MAIN: Listing post type (primary property listings)
        $listing_registered = register_post_type('listing', [
            'labels' => [
                'name'               => __('Listings', 'happy-place'),
                'singular_name'      => __('Listing', 'happy-place'),
                'menu_name'         => __('Listings', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Listing', 'happy-place'),
                'edit_item'         => __('Edit Listing', 'happy-place'),
                'new_item'          => __('New Listing', 'happy-place'),
                'view_item'         => __('View Listing', 'happy-place'),
                'search_items'      => __('Search Listings', 'happy-place'),
                'not_found'         => __('No listings found', 'happy-place'),
                'not_found_in_trash'=> __('No listings found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'menu_icon'           => 'dashicons-building',
            'rewrite'            => ['slug' => 'listings'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 5,
        ]);

        // Agent post type
        $agent_registered = register_post_type('agent', [
            'labels' => [
                'name'               => __('Agents', 'happy-place'),
                'singular_name'      => __('Agent', 'happy-place'),
                'menu_name'         => __('Agents', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Agent', 'happy-place'),
                'edit_item'         => __('Edit Agent', 'happy-place'),
                'new_item'          => __('New Agent', 'happy-place'),
                'view_item'         => __('View Agent', 'happy-place'),
                'search_items'      => __('Search Agents', 'happy-place'),
                'not_found'         => __('No agents found', 'happy-place'),
                'not_found_in_trash'=> __('No agents found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-businessperson',
            'rewrite'            => ['slug' => 'agents'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 6,
        ]);

        // Community post type
        $community_registered = register_post_type('community', [
            'labels' => [
                'name'               => __('Communities', 'happy-place'),
                'singular_name'      => __('Community', 'happy-place'),
                'menu_name'         => __('Communities', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Community', 'happy-place'),
                'edit_item'         => __('Edit Community', 'happy-place'),
                'new_item'          => __('New Community', 'happy-place'),
                'view_item'         => __('View Community', 'happy-place'),
                'search_items'      => __('Search Communities', 'happy-place'),
                'not_found'         => __('No communities found', 'happy-place'),
                'not_found_in_trash'=> __('No communities found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-location',
            'rewrite'            => ['slug' => 'communities'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 7,
        ]);

        // City post type
        $city_registered = register_post_type('city', [
            'labels' => [
                'name'               => __('Cities', 'happy-place'),
                'singular_name'      => __('City', 'happy-place'),
                'menu_name'         => __('Cities', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New City', 'happy-place'),
                'edit_item'         => __('Edit City', 'happy-place'),
                'new_item'          => __('New City', 'happy-place'),
                'view_item'         => __('View City', 'happy-place'),
                'search_items'      => __('Search Cities', 'happy-place'),
                'not_found'         => __('No cities found', 'happy-place'),
                'not_found_in_trash'=> __('No cities found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-location-alt',
            'rewrite'            => ['slug' => 'cities'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 8,
        ]);

        // Transaction post type (admin only)
        $transaction_registered = register_post_type('transaction', [
            'labels' => [
                'name'               => __('Transactions', 'happy-place'),
                'singular_name'      => __('Transaction', 'happy-place'),
                'menu_name'         => __('Transactions', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Transaction', 'happy-place'),
                'edit_item'         => __('Edit Transaction', 'happy-place'),
                'new_item'          => __('New Transaction', 'happy-place'),
                'view_item'         => __('View Transaction', 'happy-place'),
                'search_items'      => __('Search Transactions', 'happy-place'),
                'not_found'         => __('No transactions found', 'happy-place'),
                'not_found_in_trash'=> __('No transactions found in Trash', 'happy-place'),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor'],
            'menu_icon'           => 'dashicons-money-alt',
            'rewrite'            => ['slug' => 'transactions'],
            'capability_type'    => 'post',
            'menu_position'      => 9,
        ]);

        // Open Houses post type
        $open_house_registered = register_post_type('open-house', [
            'labels' => [
                'name'               => __('Open Houses', 'happy-place'),
                'singular_name'      => __('Open House', 'happy-place'),
                'menu_name'         => __('Open Houses', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Open House', 'happy-place'),
                'edit_item'         => __('Edit Open House', 'happy-place'),
                'new_item'          => __('New Open House', 'happy-place'),
                'view_item'         => __('View Open House', 'happy-place'),
                'search_items'      => __('Search Open Houses', 'happy-place'),
                'not_found'         => __('No open houses found', 'happy-place'),
                'not_found_in_trash'=> __('No open houses found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail'],
            'menu_icon'           => 'dashicons-calendar-alt',
            'rewrite'            => ['slug' => 'open-houses'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 9,
        ]);

        // Local Places post type (businesses, restaurants, etc.)
        $local_place_registered = register_post_type('local-place', [
            'labels' => [
                'name'               => __('Local Places', 'happy-place'),
                'singular_name'      => __('Local Place', 'happy-place'),
                'menu_name'         => __('Local Places', 'happy-place'),
                'add_new'           => __('Add New', 'happy-place'),
                'add_new_item'      => __('Add New Local Place', 'happy-place'),
                'edit_item'         => __('Edit Local Place', 'happy-place'),
                'new_item'          => __('New Local Place', 'happy-place'),
                'view_item'         => __('View Local Place', 'happy-place'),
                'search_items'      => __('Search Local Places', 'happy-place'),
                'not_found'         => __('No local places found', 'happy-place'),
                'not_found_in_trash'=> __('No local places found in Trash', 'happy-place'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'           => 'dashicons-location',
            'rewrite'            => ['slug' => 'local-places'],
            'capability_type'    => 'post',
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'menu_position'      => 10,
        ]);

        // Team post type
        $team_registered = register_post_type('team', [
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
            'menu_position'      => 11,
        ]);

        // Log registration results
        error_log('HPH: Listing registered: ' . ($listing_registered ? 'SUCCESS' : 'FAILED'));
        error_log('HPH: Agent registered: ' . ($agent_registered ? 'SUCCESS' : 'FAILED'));
        error_log('HPH: Community registered: ' . ($community_registered ? 'SUCCESS' : 'FAILED'));
        error_log('HPH: City registered: ' . ($city_registered ? 'SUCCESS' : 'FAILED'));
        error_log('HPH: Transaction registered: ' . ($transaction_registered ? 'SUCCESS' : 'FAILED'));
        error_log('HPH: Open House registered: ' . ($open_house_registered ? 'SUCCESS' : 'FAILED'));
        error_log('HPH: Local Place registered: ' . ($local_place_registered ? 'SUCCESS' : 'FAILED'));
        error_log('HPH: Team registered: ' . ($team_registered ? 'SUCCESS' : 'FAILED'));
    }
}