<?php
namespace HappyPlace\Core;

// Class for managing custom taxonomies

class Taxonomies {
    private static ?self $instance = null;

    // Define taxonomy configurations
    private $taxonomies = [
        'property_type' => [
            'post_types' => ['listing'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
            ],
            'labels' => [
                'name' => 'Property Types',
                'singular_name' => 'Property Type',
                'menu_name' => 'Property Types',
                'search_items' => 'Search Property Types',
                'all_items' => 'All Property Types',
                'edit_item' => 'Edit Property Type',
                'update_item' => 'Update Property Type',
                'add_new_item' => 'Add New Property Type',
                'new_item_name' => 'New Property Type Name',
                'separate_items_with_commas' => 'Separate property types with commas',
                'add_or_remove_items' => 'Add or remove property types',
                'choose_from_most_used' => 'Choose from the most used property types',
            ]
        ],
        'listing_status' => [
            'post_types' => ['listing'],
            'args' => [
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
            ],
            'labels' => [
                'name' => 'Listing Statuses',
                'singular_name' => 'Listing Status',
                'menu_name' => 'Listing Statuses',
                'search_items' => 'Search Listing Statuses',
                'all_items' => 'All Listing Statuses',
                'edit_item' => 'Edit Listing Status',
                'update_item' => 'Update Listing Status',
                'add_new_item' => 'Add New Listing Status',
                'new_item_name' => 'New Listing Status Name',
                'separate_items_with_commas' => 'Separate listing statuses with commas',
                'add_or_remove_items' => 'Add or remove listing statuses',
                'choose_from_most_used' => 'Choose from the most used listing statuses',
            ]
        ],
        'agent_specialty' => [
            'post_types' => ['agent'],
            'args' => [
                'hierarchical' => false,
                'public' => true,
                'show_in_rest' => true,
            ],
            'labels' => [
                'name' => 'Agent Specialties',
                'singular_name' => 'Agent Specialty',
                'menu_name' => 'Agent Specialties',
                'search_items' => 'Search Specialties',
                'all_items' => 'All Specialties',
                'edit_item' => 'Edit Specialty',
                'update_item' => 'Update Specialty',
                'add_new_item' => 'Add New Specialty',
                'new_item_name' => 'New Specialty Name',
                'separate_items_with_commas' => 'Separate specialties with commas',
                'add_or_remove_items' => 'Add or remove specialties',
                'choose_from_most_used' => 'Choose from the most used specialties',
            ]
        ],
        'community_amenities' => [
            'post_types' => ['community'],
            'args' => [
                'hierarchical' => false,
                'public' => true,
                'show_in_rest' => true,
            ],
            'labels' => [
                'name' => 'Community Amenities',
                'singular_name' => 'Community Amenity',
                'menu_name' => 'Community Amenities',
                'search_items' => 'Search Amenities',
                'all_items' => 'All Amenities',
                'edit_item' => 'Edit Amenity',
                'update_item' => 'Update Amenity',
                'add_new_item' => 'Add New Amenity',
                'new_item_name' => 'New Amenity Name',
                'separate_items_with_commas' => 'Separate amenities with commas',
                'add_or_remove_items' => 'Add or remove amenities',
                'choose_from_most_used' => 'Choose from the most used amenities',
            ]
        ],
        'city_highlights' => [
            'post_types' => ['city'],
            'args' => [
                'hierarchical' => false,
                'public' => true,
                'show_in_rest' => true,
            ],
            'labels' => [
                'name' => 'City Highlights',
                'singular_name' => 'City Highlight',
                'menu_name' => 'City Highlights',
                'search_items' => 'Search Highlights',
                'all_items' => 'All Highlights',
                'edit_item' => 'Edit Highlight',
                'update_item' => 'Update Highlight',
                'add_new_item' => 'Add New Highlight',
                'new_item_name' => 'New Highlight Name',
                'separate_items_with_commas' => 'Separate highlights with commas',
                'add_or_remove_items' => 'Add or remove highlights',
                'choose_from_most_used' => 'Choose from the most used highlights',
            ]
        ]
    ];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        \add_action('init', [$this, 'register_taxonomies'], 10);
        \add_action('init', [$this, 'add_taxonomy_filters'], 20);
    }

    /**
     * Register all custom taxonomies
     */
    public function register_taxonomies(): void {
        foreach ($this->taxonomies as $taxonomy => $config) {
            $labels = $config['labels'];
            $args = array_merge([
                'labels' => $labels,
                'rewrite' => ['slug' => $taxonomy],
            ], $config['args']);

            register_taxonomy($taxonomy, $config['post_types'], $args);
        }
    }

    /**
     * Add taxonomy filters to admin screens
     */
    public function add_taxonomy_filters(): void {
        $taxonomy_filters = [
            'listing' => ['property_type', 'listing_status'],
            'agent' => ['agent_specialty'],
            'community' => ['community_amenities'],
            'city' => ['city_highlights']
        ];

        foreach ($taxonomy_filters as $post_type => $taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                add_filter("manage_edit-{$post_type}_columns", function($columns) use ($taxonomy) {
                    $columns[$taxonomy] = get_taxonomy($taxonomy)->labels->singular_name;
                    return $columns;
                });

                add_filter("manage_{$post_type}_posts_custom_column", function($column_name, $post_id) use ($taxonomy) {
                    if ($column_name === $taxonomy) {
                        $terms = get_the_terms($post_id, $taxonomy);
                        if ($terms && !is_wp_error($terms)) {
                            $term_names = array_map(function($term) {
                                return $term->name;
                            }, $terms);
                            echo implode(', ', $term_names);
                        } else {
                            echo '—';
                        }
                    }
                }, 10, 2);
            }
        }
    }

    /**
     * Create default terms for important taxonomies
     */
    public function create_default_terms(): void {
        $default_terms = [
            'property_type' => [
                'Single Family Home',
                'Townhouse',
                'Condo',
                'Multi-Family',
                'Vacant Land'
            ],
            'listing_status' => [
                'Active',
                'Pending',
                'Contingent',
                'Sold',
                'Expired'
            ],
            'agent_specialty' => [
                'Residential',
                'Commercial',
                'Luxury',
                'First-Time Buyers',
                'Waterfront Properties'
            ]
        ];

        foreach ($default_terms as $taxonomy => $terms) {
            foreach ($terms as $term) {
                if (!term_exists($term, $taxonomy)) {
                    wp_insert_term($term, $taxonomy);
                }
            }
        }
    }

    /**
     * Add taxonomy meta fields
     */
    public function add_taxonomy_meta_fields(): void {
        // Add meta fields to property type taxonomy
        $taxonomy = 'property_type';
        add_action("{$taxonomy}_add_form_fields", [$this, 'add_property_type_meta_fields']);
        add_action("{$taxonomy}_edit_form_fields", [$this, 'edit_property_type_meta_fields'], 10, 2);
        add_action("edited_{$taxonomy}", [$this, 'save_property_type_meta_fields'], 10, 2);
        add_action("create_{$taxonomy}", [$this, 'save_property_type_meta_fields'], 10, 2);
    }

    /**
     * Add meta fields when creating a new term
     */
    public function add_property_type_meta_fields($taxonomy): void {
        wp_nonce_field('property_type_meta', 'property_type_meta_nonce');
        ?>
        <div class="form-field">
            <label for="property_type_icon">Icon URL</label>
            <input type="text" name="property_type_icon" id="property_type_icon" />
            <p class="description">Enter an icon URL for this property type</p>
        </div>
        <?php
    }

    /**
     * Edit meta fields for existing terms
     */
    public function edit_property_type_meta_fields($term, $taxonomy): void {
        wp_nonce_field('property_type_meta', 'property_type_meta_nonce');
        $icon = get_term_meta($term->term_id, 'property_type_icon', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="property_type_icon">Icon URL</label></th>
            <td>
                <input type="text" name="property_type_icon" id="property_type_icon" value="<?php echo esc_attr($icon); ?>" />
                <p class="description">Enter an icon URL for this property type</p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save taxonomy meta fields
     */
    public function save_property_type_meta_fields($term_id, $taxonomy): void {
        // Verify nonce
        if (!isset($_POST['property_type_meta_nonce']) || 
            !wp_verify_nonce($_POST['property_type_meta_nonce'], 'property_type_meta')) {
            return;
        }

        // Check user capabilities
        if (!current_user_can('manage_categories')) {
            return;
        }

        if (isset($_POST['property_type_icon'])) {
            $icon = sanitize_text_field($_POST['property_type_icon']);
            if (filter_var($icon, FILTER_VALIDATE_URL)) {
                update_term_meta($term_id, 'property_type_icon', $icon);
            }
        }
    }
}

// Initialize the Taxonomies
Taxonomies::get_instance();