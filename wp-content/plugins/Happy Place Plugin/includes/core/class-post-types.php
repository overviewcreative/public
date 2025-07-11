<?php
/**
 * File: includes/core/class-post-types.php
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
        // Register post types on init with priority 5 (after taxonomies typically register at 0)
        add_action('init', [$this, 'register_post_types'], 5);
        error_log('HPH: Post_Types constructor called');
        
        // Set up activation hook handler
        add_action('happy_place_activated', [$this, 'flush_rules_on_activation']);
    }

    /**
     * Handle rewrite rules flushing on activation
     */
    public function flush_rules_on_activation(): void {
        // Register post types first
        $this->register_post_types();
        // Then flush the rules
        error_log('HPH: Flushing rewrite rules on activation');
        flush_rewrite_rules();
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
        if (is_wp_error($listing_registered)) {
            error_log('HPH: Error registering listing post type: ' . $listing_registered->get_error_message());
        } else {
            error_log('HPH: Listing post type registered successfully');
        }

        if (is_wp_error($agent_registered)) {
            error_log('HPH: Error registering agent post type: ' . $agent_registered->get_error_message());
        } else {
            error_log('HPH: Agent post type registered successfully');
        }

        if (is_wp_error($community_registered)) {
            error_log('HPH: Error registering community post type: ' . $community_registered->get_error_message());
        } else {
            error_log('HPH: Community post type registered successfully');
        }

        if (is_wp_error($city_registered)) {
            error_log('HPH: Error registering city post type: ' . $city_registered->get_error_message());
        } else {
            error_log('HPH: City post type registered successfully');
        }

        if (is_wp_error($transaction_registered)) {
            error_log('HPH: Error registering transaction post type: ' . $transaction_registered->get_error_message());
        } else {
            error_log('HPH: Transaction post type registered successfully');
        }

        if (is_wp_error($open_house_registered)) {
            error_log('HPH: Error registering open house post type: ' . $open_house_registered->get_error_message());
        } else {
            error_log('HPH: Open house post type registered successfully');
        }

        if (is_wp_error($local_place_registered)) {
            error_log('HPH: Error registering local place post type: ' . $local_place_registered->get_error_message());
        } else {
            error_log('HPH: Local place post type registered successfully');
        }

        if (is_wp_error($team_registered)) {
            error_log('HPH: Error registering team post type: ' . $team_registered->get_error_message());
        } else {
            error_log('HPH: Team post type registered successfully');
        }

        // Log all registered post types
        $registered_types = get_post_types(['_builtin' => false], 'names');
        error_log('HPH: All registered custom post types: ' . implode(', ', $registered_types));
    }
}