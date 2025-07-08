<?php
namespace HappyPlace\core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Types Manager
 * 
 * Handles registration and management of all custom post types including:
 * - Listings
 * - Agents
 * - Communities
 * - Cities
 * - Open Houses
 * - Transactions
 * - Clients
 * - Local Spots (Get Local)
 */
class Post_Types {
    private static ?self $instance = null;
    private array $post_types;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->init_post_types();
        add_action('init', [$this, 'register_post_types'], 0); // Higher priority (0) to ensure it runs first
        add_action('init', [$this, 'register_taxonomies'], 0);
        add_action('admin_menu', [$this, 'customize_admin_menus'], 99); // Lower priority to ensure it runs after menu creation
        add_filter('manage_posts_columns', [$this, 'custom_post_type_columns'], 10, 2);
        add_action('manage_posts_custom_column', [$this, 'populate_custom_columns'], 10, 2);
        
        // Ensure proper column management for each post type
        add_action('init', function() {
            foreach ($this->post_types as $post_type => $args) {
                add_filter("manage_{$post_type}_posts_columns", [$this, 'custom_post_type_columns'], 10, 2);
                add_action("manage_{$post_type}_posts_custom_column", [$this, 'populate_custom_columns'], 10, 2);
            }
        }, 20);
    }

    private function init_post_types() {
        $this->post_types = [
            'listings' => [
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
            ],
            'agents' => [
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
            ],
            'get-local' => [
                'labels' => [
                    'name' => __('Local Spots', 'happy-place'),
                    'singular_name' => __('Local Spot', 'happy-place'),
                    'add_new' => __('Add New', 'happy-place'),
                    'add_new_item' => __('Add New Local Spot', 'happy-place'),
                    'edit_item' => __('Edit Local Spot', 'happy-place'),
                    'new_item' => __('New Local Spot', 'happy-place'),
                    'view_item' => __('View Local Spot', 'happy-place'),
                    'search_items' => __('Search Local Spots', 'happy-place'),
                    'not_found' => __('No local spots found', 'happy-place'),
                    'not_found_in_trash' => __('No local spots found in Trash', 'happy-place'),
                ],
                'public' => true,
                'has_archive' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-location',
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
                'rewrite' => ['slug' => 'get-local']
            ],
            'open-houses' => [
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
                'show_in_menu' => 'edit.php?post_type=listings',
                'show_in_rest' => true,
                'supports' => ['title', 'editor'],
                'rewrite' => ['slug' => 'open-houses']
            ],
            'transactions' => [
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
                'show_in_menu' => 'edit.php?post_type=listings',
                'show_in_rest' => true,
                'supports' => ['title', 'editor'],
                'rewrite' => ['slug' => 'transactions']
            ],
            'clients' => [
                'labels' => [
                    'name' => __('Clients', 'happy-place'),
                    'singular_name' => __('Client', 'happy-place'),
                    'add_new' => __('Add New', 'happy-place'),
                    'add_new_item' => __('Add New Client', 'happy-place'),
                    'edit_item' => __('Edit Client', 'happy-place'),
                    'new_item' => __('New Client', 'happy-place'),
                    'view_item' => __('View Client', 'happy-place'),
                    'search_items' => __('Search Clients', 'happy-place'),
                    'not_found' => __('No clients found', 'happy-place'),
                    'not_found_in_trash' => __('No clients found in Trash', 'happy-place'),
                ],
                'public' => true,
                'show_in_menu' => 'edit.php?post_type=agents',
                'show_in_rest' => true,
                'supports' => ['title', 'editor'],
                'rewrite' => ['slug' => 'clients']
            ]
        ];
    }

    public function register_post_types(): void {
        foreach ($this->post_types as $post_type => $args) {
            register_post_type($post_type, $args);
        }
    }

    public function register_taxonomies(): void {
        // Property Types Taxonomy
        register_taxonomy('property_type', ['listings'], [
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

        // Listing Status Taxonomy
        register_taxonomy('listing_status', ['listings'], [
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

        // Local Spot Categories
        register_taxonomy('local_spot_category', ['get-local'], [
            'hierarchical' => true,
            'labels' => [
                'name' => _x('Local Spot Categories', 'taxonomy general name', 'happy-place'),
                'singular_name' => _x('Local Spot Category', 'taxonomy singular name', 'happy-place'),
                'search_items' => __('Search Categories', 'happy-place'),
                'all_items' => __('All Categories', 'happy-place'),
                'parent_item' => __('Parent Category', 'happy-place'),
                'parent_item_colon' => __('Parent Category:', 'happy-place'),
                'edit_item' => __('Edit Category', 'happy-place'),
                'update_item' => __('Update Category', 'happy-place'),
                'add_new_item' => __('Add New Category', 'happy-place'),
                'new_item_name' => __('New Category Name', 'happy-place'),
                'menu_name' => __('Categories', 'happy-place')
            ],
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'local-category']
        ]);

        // Register listing-specific taxonomies
        register_taxonomy('listing-type', ['listings'], [
            'label' => 'Listing Types',
            'labels' => [
                'name' => 'Listing Types',
                'singular_name' => 'Listing Type',
                'menu_name' => 'Listing Types',
                'all_items' => 'All Listing Types',
                'edit_item' => 'Edit Listing Type',
                'view_item' => 'View Listing Type',
                'update_item' => 'Update Listing Type',
                'add_new_item' => 'Add New Listing Type',
                'new_item_name' => 'New Listing Type Name',
                'search_items' => 'Search Listing Types'
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'listing-type']
        ]);

        // Register get-local taxonomies
        register_taxonomy('local-category', ['get-local'], [
            'label' => 'Local Categories',
            'labels' => [
                'name' => 'Local Categories',
                'singular_name' => 'Local Category',
                'menu_name' => 'Local Categories',
                'all_items' => 'All Local Categories',
                'edit_item' => 'Edit Local Category',
                'view_item' => 'View Local Category',
                'update_item' => 'Update Local Category',
                'add_new_item' => 'Add New Local Category',
                'new_item_name' => 'New Local Category Name',
                'search_items' => 'Search Local Categories'
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'local-category']
        ]);
    }

    public function customize_admin_menus(): void {
        global $submenu;

        // Set up the listings menu slug
        $listing_menu = 'edit.php?post_type=listings';
        
        // Add Open Houses and Transactions as submenu items under Listings
        if (isset($this->post_types['listings'])) {
            if (isset($this->post_types['open-houses'])) {
                add_submenu_page(
                    $listing_menu,
                    __('Open Houses', 'happy-place'),
                    __('Open Houses', 'happy-place'),
                    'edit_posts',
                    'edit.php?post_type=open-houses',
                    null
                );
            }
            
            if (isset($this->post_types['transactions'])) {
                add_submenu_page(
                    $listing_menu,
                    __('Transactions', 'happy-place'),
                    __('Transactions', 'happy-place'),
                    'edit_posts',
                    'edit.php?post_type=transactions',
                    null
                );
            }
        }

        // Add Clients as submenu item under Agents
        if (isset($this->post_types['agents'])) {
            if (isset($this->post_types['clients'])) {
                add_submenu_page(
                    'edit.php?post_type=agents',
                    __('Clients', 'happy-place'),
                    __('Clients', 'happy-place'),
                    'edit_posts',
                    'edit.php?post_type=clients',
                    null
                );
            }
        }

        // Remove duplicate menu items
        if (isset($submenu[$listing_menu])) {
            foreach ($submenu[$listing_menu] as $key => $item) {
                if ($item[2] === 'edit.php?post_type=open-houses' || 
                    $item[2] === 'edit.php?post_type=transactions') {
                    unset($submenu[$listing_menu][$key]);
                }
            }
        }
    }

    public function custom_post_type_columns($columns, $post_type): array {
        switch($post_type) {
            case 'listings':
                $columns['price'] = __('Price', 'happy-place');
                $columns['status'] = __('Status', 'happy-place');
                break;
            case 'agents':
                $columns['email'] = __('Email', 'happy-place');
                $columns['phone'] = __('Phone', 'happy-place');
                break;
            case 'get-local':
                $columns['category'] = __('Category', 'happy-place');
                $columns['author'] = __('Added By', 'happy-place');
                $columns['rating'] = __('Rating', 'happy-place');
                break;
        }
        return $columns;
    }

    public function populate_custom_columns($column, $post_id): void {
        switch($column) {
            case 'price':
                $price = get_field('price', $post_id);
                echo $price ? '$' . number_format($price) : '—';
                break;
            case 'status':
                $status = get_field('status', $post_id);
                echo $status ?: '—';
                break;
            case 'email':
                $email = get_field('email', $post_id);
                echo $email ? "<a href='mailto:{$email}'>{$email}</a>" : '—';
                break;
            case 'phone':
                $phone = get_field('phone', $post_id);
                echo $phone ? "<a href='tel:{$phone}'>{$phone}</a>" : '—';
                break;
            case 'category':
                $terms = get_the_terms($post_id, 'local_spot_category');
                if ($terms && !is_wp_error($terms)) {
                    echo implode(', ', wp_list_pluck($terms, 'name'));
                } else {
                    echo '—';
                }
                break;
            case 'rating':
                $rating = get_field('rating', $post_id);
                echo $rating ? str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) : '—';
                break;
        }
    }
}

// Initialize the Post Types
Post_Types::get_instance();