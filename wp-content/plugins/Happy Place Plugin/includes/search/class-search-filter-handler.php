<?php
/**
 * Search Filter Handler Class
 *
 * Handles advanced search functionality for listings, agents, and communities.
 * Provides AJAX-powered search suggestions and filters.
 *
 * @package HappyPlace
 * @subpackage Search
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

class Search_Filter_Handler {
    /**
     * Instance of this class
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Cache group for search queries
     *
     * @var string
     */
    private const CACHE_GROUP = 'hph_search';

    /**
     * Rate limit for search requests (in seconds)
     *
     * @var int
     */
    private const RATE_LIMIT = 2;

    /**
     * Get instance of this class
     *
     * @return self
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get search suggestions via AJAX
     *
     * @return void
     */
    public function get_search_suggestions(): void {
        try {
            // Verify nonce and check rate limit
            check_ajax_referer('hph_search_nonce', 'security');
            $this->check_rate_limit();

            // Validate inputs
            $query = $this->validate_search_query($_POST['query'] ?? '');
            $post_type = $this->validate_post_type($_POST['post_type'] ?? 'listing');

            if (empty($query)) {
                throw new \Exception('Search query is required');
            }

            // Get cached results if available
            $cache_key = md5($query . $post_type);
            $suggestions = wp_cache_get($cache_key, self::CACHE_GROUP);

            if (false === $suggestions) {
                $suggestions = [];

                switch ($post_type) {
                    case 'listing':
                        $suggestions = array_merge(
                            $this->search_listings_by_address($query),
                            $this->search_listings_by_city($query)
                        );
                        break;
                    case 'agent':
                        $suggestions = $this->search_agents_by_name($query);
                        break;
                    case 'community':
                        $suggestions = $this->search_communities_by_name($query);
                        break;
                }

                // Cache results for 5 minutes
                wp_cache_set($cache_key, $suggestions, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
            }

            wp_send_json_success($suggestions);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate search query
     *
     * @param string $query Raw search query
     * @return string Sanitized search query
     */
    private function validate_search_query(string $query): string {
        return (string) sanitize_text_field(wp_unslash((string) $query));
    }

    /**
     * Validate post type
     *
     * @param string $post_type Raw post type
     * @return string Validated post type
     * @throws \Exception If post type is invalid
     */
    private function validate_post_type(string $post_type): string {
        $valid_types = ['listing', 'agent', 'community'];
        $post_type = sanitize_key($post_type);

        if (!in_array($post_type, $valid_types, true)) {
            throw new \Exception('Invalid post type');
        }

        return $post_type;
    }

    /**
     * Check rate limit for search requests
     *
     * @throws \Exception If rate limit is exceeded
     */
    private function check_rate_limit(): void {
        $user_id = get_current_user_id();
        $cache_key = 'search_rate_' . ($user_id ?: md5($_SERVER['REMOTE_ADDR']));
        $last_request = get_transient($cache_key);

        if (false !== $last_request) {
            throw new \Exception('Please wait before making another search request');
        }

        set_transient($cache_key, time(), self::RATE_LIMIT);
    }

    /**
     * Get meta field safely
     *
     * @param string $field ACF field name
     * @param mixed $default Default value if field doesn't exist
     * @return mixed Field value or default
     */
    private function get_field_value(string $field, $default = '') {
        // Fix: Always return $default if meta is empty string or null
        if (function_exists('get_field')) {
            $value = get_field($field);
            return ($value !== '' && $value !== null) ? $value : $default;
        }
        $meta = get_post_meta(get_the_ID(), $field, true);
        return ($meta !== '' && $meta !== null) ? $meta : $default;
    }

    /**
     * Search listings by address
     *
     * @param string $query Search query
     * @return array Search results
     */
    private function search_listings_by_address(string $query): array {
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => 5,
            'meta_query' => [
                [
                    'key' => 'street_address',
                    'value' => $query,
                    'compare' => 'LIKE'
                ]
            ],
            'no_found_rows' => true, // Improves performance
            'update_post_meta_cache' => false, // We don't need all meta
            'update_post_term_cache' => false // We don't need terms
        ];

        return $this->execute_search_query($args, 'street_address');
    }

    /**
     * Search listings by city
     *
     * @param string $query Search query
     * @return array Search results
     */
    private function search_listings_by_city(string $query): array {
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => 5,
            'meta_query' => [
                [
                    'key' => 'city',
                    'value' => $query,
                    'compare' => 'LIKE'
                ]
            ],
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ];

        return $this->execute_search_query($args, 'city');
    }

    /**
     * Search agents by name
     *
     * @param string $query Search query
     * @return array Search results
     */
    private function search_agents_by_name(string $query): array {
        $args = [
            'post_type' => 'agent',
            'posts_per_page' => 5,
            's' => $query,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ];

        return $this->execute_search_query($args, 'license_number');
    }

    /**
     * Search communities by name
     *
     * @param string $query Search query
     * @return array Search results
     */
    private function search_communities_by_name(string $query): array {
        $args = [
            'post_type' => 'community',
            'posts_per_page' => 5,
            's' => $query,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ];

        return $this->execute_search_query($args, 'location_description');
    }

    /**
     * Execute a search query and format results
     *
     * @param array $args WP_Query arguments
     * @param string $subtitle_field Field to use for subtitle
     * @return array Formatted search results
     */
    private function execute_search_query(array $args, string $subtitle_field): array {
        $query = new \WP_Query($args);
        $suggestions = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                // Fix: Check meta_query key existence and type
                $title = get_the_title();
                if (!empty($args['meta_query']) && is_array($args['meta_query']) && isset($args['meta_query'][0]['key'])) {
                    $meta_title = $this->get_field_value($args['meta_query'][0]['key']);
                    if ($meta_title) {
                        $title = $meta_title;
                    }
                }
                $suggestions[] = [
                    'id' => get_the_ID(),
                    'title' => $title,
                    'subtitle' => $this->get_field_value($subtitle_field),
                    'url' => get_permalink()
                ];
            }
            wp_reset_postdata();
        }

        return $suggestions;
    }

    /**
     * Render search filter form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered form HTML
     */
    public function render_search_filter_form($atts = []): string {
        // Enqueue required assets
        $this->enqueue_search_assets();

        // Localize script with AJAX data
        wp_localize_script('hph-search', 'hphSearch', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_search_nonce')
        ]);

        // Start output buffering
        ob_start();
        ?>
        <div class="hph-search-filter">
            <form id="hph-search-filter-form">
                <div class="filter-group search-main">
                    <div class="search-input-wrapper">
                        <input type="text" 
                               name="search_query" 
                               class="hph-form-input" 
                               placeholder="Search by address, city, or community..."
                               autocomplete="off">
                        <div id="search-suggestions" class="search-suggestions"></div>
                    </div>
                </div>

                <div class="filter-group">
                    <label for="price_min" class="hph-form-label">Min Price</label>
                    <select name="price_min" id="price_min" class="hph-form-select">
                        <option value="">Any</option>
                        <?php
                        $price_ranges = [100000, 200000, 300000, 400000, 500000, 750000, 1000000, 1500000, 2000000];
                        foreach ($price_ranges as $price) {
                            printf(
                                '<option value="%d">$%s</option>',
                                $price,
                                number_format($price)
                            );
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="price_max" class="hph-form-label">Max Price</label>
                    <select name="price_max" id="price_max" class="hph-form-select">
                        <option value="">Any</option>
                        <?php
                        foreach ($price_ranges as $price) {
                            printf(
                                '<option value="%d">$%s</option>',
                                $price,
                                number_format($price)
                            );
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="beds" class="hph-form-label">Bedrooms</label>
                    <select name="beds" id="beds" class="hph-form-select">
                        <option value="">Any</option>
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            printf(
                                '<option value="%d">%d%s</option>',
                                $i,
                                $i,
                                $i === 5 ? '+ Beds' : ' Beds'
                            );
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="baths" class="hph-form-label">Bathrooms</label>
                    <select name="baths" id="baths" class="hph-form-select">
                        <option value="">Any</option>
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            printf(
                                '<option value="%d">%d%s</option>',
                                $i,
                                $i,
                                $i === 5 ? '+ Baths' : ' Baths'
                            );
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <button type="submit" class="hph-btn hph-btn-primary">Search Properties</button>
                </div>
            </form>

            <div id="search-results-container" class="hph-grid hph-grid-3"></div>
            <div id="search-pagination" class="search-pagination"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue search-related assets
     */
    private function enqueue_search_assets(): void {
        // Register and enqueue styles
        wp_register_style(
            'hph-search',
            plugins_url('assets/css/search.css', dirname(__FILE__)),
            [],
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/search.css')
        );

        // Register and enqueue scripts
        wp_register_script(
            'hph-search',
            plugins_url('assets/js/search.js', dirname(__FILE__)),
            ['jquery'],
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/js/search.js'),
            true
        );

        wp_enqueue_style('hph-search');
        wp_enqueue_script('hph-search');
    }

    /**
     * AJAX handler for listing searches
     */
    public function ajax_search_listings(): void {
        try {
            check_ajax_referer('hph_search_nonce', 'security');

            $page = absint($_POST['page'] ?? 1);
            $per_page = absint($_POST['per_page'] ?? 12);
            $query = $this->validate_search_query($_POST['search_query'] ?? '');
            
            $args = [
                'post_type' => 'listing',
                'posts_per_page' => $per_page,
                'paged' => $page,
                's' => $query,
                'meta_query' => []
            ];

            // Price range filter
            $price_min = absint($_POST['price_min'] ?? 0);
            $price_max = absint($_POST['price_max'] ?? 0);
            if ($price_min || $price_max) {
                $price_query = ['key' => '_price', 'type' => 'NUMERIC'];
                if ($price_min) $price_query['min'] = $price_min;
                if ($price_max) $price_query['max'] = $price_max;
                $args['meta_query'][] = $price_query;
            }

            // Beds/Baths filter
            $beds = absint($_POST['beds'] ?? 0);
            if ($beds) {
                $args['meta_query'][] = [
                    'key' => '_bedrooms',
                    'value' => $beds,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ];
            }

            $baths = absint($_POST['baths'] ?? 0);
            if ($baths) {
                $args['meta_query'][] = [
                    'key' => '_bathrooms',
                    'value' => $baths,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ];
            }

            $query = new \WP_Query($args);
            $posts = [];

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $posts[] = [
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'permalink' => get_permalink(),
                        'price' => $this->get_field_value('_price', 0),
                        'bedrooms' => $this->get_field_value('_bedrooms', 0),
                        'bathrooms' => $this->get_field_value('_bathrooms', 0),
                        'square_footage' => $this->get_field_value('_square_feet', 0),
                        'main_photo' => get_the_post_thumbnail_url(null, 'large') ?: plugins_url('assets/images/placeholder.jpg', dirname(__FILE__)),
                        'property_types' => wp_get_post_terms(get_the_ID(), 'property_type', ['fields' => 'names'])
                    ];
                }
                wp_reset_postdata();
            }

            wp_send_json_success([
                'posts' => $posts,
                'total_pages' => $query->max_num_pages,
                'current_page' => $page
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Initialize all search-related hooks
     */
    public function init(): void {
        // Register AJAX actions
        add_action('wp_ajax_hph_search_listings', [$this, 'ajax_search_listings']);
        add_action('wp_ajax_nopriv_hph_search_listings', [$this, 'ajax_search_listings']);
        add_action('wp_ajax_hph_search_suggestions', [$this, 'get_search_suggestions']);
        add_action('wp_ajax_nopriv_hph_search_suggestions', [$this, 'get_search_suggestions']);

        // Register shortcodes
        add_shortcode('hph_search_filter', [$this, 'render_search_filter_form']);

        // Add rate limiting headers
        add_action('send_headers', function() {
            header('X-RateLimit-Limit: ' . self::RATE_LIMIT);
        });
    }
}

// Initialize the Search Filter Handler
$search_filter_handler = Search_Filter_Handler::get_instance();
$search_filter_handler->init();