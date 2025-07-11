<?php

/**
 * AJAX Handler
 * 
 * 
 * @package HappyPlace
 * @since 1.0.0
 */

class HPH_Ajax_Handler
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->register_ajax_handlers();
        $this->ensure_dependencies();
    }

    /**
     * Register all AJAX handlers with exact same action names as original
     */
    private function register_ajax_handlers(): void
    {
        $handlers = [
            // Maintain exact compatibility with existing frontend
            'hph_search_suggestions' => 'search_suggestions',
            'hph_filter_listings' => 'filter_listings',
            'hph_toggle_favorite' => 'toggle_favorite',
            'hph_get_favorites' => 'get_favorites',
            'hph_save_search' => 'save_search',
            'hph_contact_agent' => 'contact_agent',
            'hph_get_map_markers' => 'get_map_markers',

            // Additional dashboard functionality
            'hph_load_dashboard_section' => 'load_dashboard_section',
            'hph_update_agent_profile' => 'update_agent_profile',
            'hph_get_listing_stats' => 'get_listing_stats',
            'hph_auto_save' => 'auto_save',
            'hph_auto_save' => 'auto_save',
        ];

        foreach ($handlers as $action => $method) {
            add_action("wp_ajax_{$action}", [$this, $method]);
            add_action("wp_ajax_nopriv_{$action}", [$this, $method]);
        }
    }

    /**
     * Ensure required dependencies are loaded
     */
    private function ensure_dependencies(): void
    {
        $listing_helper_path = get_template_directory() . '/inc/class-listing-helper.php';
        if (!class_exists('Happy_Place_Listing_Helper') && file_exists($listing_helper_path)) {
            require_once $listing_helper_path;
        }
    }

    /**
     * Search suggestions for autocomplete - EXACT COMPATIBILITY
     */
    public function search_suggestions(): void
    {
        check_ajax_referer('hph_search_nonce', 'nonce');

        $term = sanitize_text_field($_POST['term'] ?? '');
        $suggestions = [];

        if (strlen($term) >= 2) {
            // Search cities
            $cities = $this->search_cities($term);
            $suggestions = array_merge($suggestions, $cities);

            // Search neighborhoods if helper class exists
            if (class_exists('Happy_Place_Listing_Helper')) {
                $listing_helper = Happy_Place_Listing_Helper::get_instance();
                if (method_exists($listing_helper, 'search_neighborhoods')) {
                    $neighborhoods = $listing_helper->search_neighborhoods($term);
                    $suggestions = array_merge($suggestions, $neighborhoods);
                }
            }

            // Search zip codes
            $zips = $this->search_zip_codes($term);
            $suggestions = array_merge($suggestions, $zips);
        }

        wp_send_json_success($suggestions);
    }

    /**
     * Filter listings - IMPROVED with exact compatibility
     */
    public function filter_listings(): void
    {
        check_ajax_referer('hph_filter_nonce', 'nonce');

        try {
            $filters = [
                'price_min' => $this->sanitize_number_input($_POST['price_min'] ?? 0),
                'price_max' => $this->sanitize_number_input($_POST['price_max'] ?? 0),
                'bedrooms' => $this->sanitize_number_input($_POST['bedrooms'] ?? 0, true),
                'bathrooms' => $this->sanitize_number_input($_POST['bathrooms'] ?? 0),
                'property_type' => sanitize_text_field($_POST['property_type'] ?? ''),
                'location' => sanitize_text_field($_POST['location'] ?? ''),
                'features' => $this->sanitize_array_input($_POST['features'] ?? []),
                'status' => sanitize_text_field($_POST['status'] ?? 'Active'),
                'sort' => sanitize_text_field($_POST['sort'] ?? 'price_desc'),
                'view' => sanitize_text_field($_POST['view'] ?? 'list'),
                'page' => $this->sanitize_number_input($_POST['page'] ?? 1, true),
                'per_page' => $this->sanitize_number_input($_POST['per_page'] ?? 12, true)
            ];

            $results = $this->get_filtered_listings($filters);

            wp_send_json_success([
                'listings' => $results['listings'],
                'total' => $results['total'],
                'pages' => $results['pages'],
                'current_page' => $filters['page']
            ]);
        } catch (Exception $e) {
            error_log('Error in filter_listings: ' . $e->getMessage());
            wp_send_json_error('Error processing listing filters');
        }
    }

    /**
     * Toggle favorite listing - EXACT COMPATIBILITY
     */
    public function toggle_favorite(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to save favorites');
        }

        check_ajax_referer('hph_favorites_nonce', 'nonce');

        $listing_id = $this->sanitize_number_input($_POST['listing_id'] ?? 0, true);
        $user_id = get_current_user_id();

        if (!$listing_id) {
            wp_send_json_error('Invalid listing ID');
        }

        $favorites = get_user_meta($user_id, 'hph_favorite_listings', true) ?: [];

        if (in_array($listing_id, $favorites)) {
            $favorites = array_diff($favorites, [$listing_id]);
            $action = 'removed';
        } else {
            $favorites[] = $listing_id;
            $action = 'added';
        }

        update_user_meta($user_id, 'hph_favorite_listings', array_values($favorites));

        wp_send_json_success([
            'action' => $action,
            'count' => count($favorites)
        ]);
    }

    /**
     * Get user's favorite listings - EXACT COMPATIBILITY
     */
    public function get_favorites(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to view favorites');
        }

        check_ajax_referer('hph_favorites_nonce', 'nonce');

        $user_id = get_current_user_id();
        $favorites = get_user_meta($user_id, 'hph_favorite_listings', true) ?: [];

        wp_send_json_success([
            'favorites' => $favorites,
            'count' => count($favorites)
        ]);
    }

    /**
     * Save search criteria - EXACT COMPATIBILITY
     */
    public function save_search(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to save searches');
        }

        check_ajax_referer('hph_search_nonce', 'nonce');

        $search_data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'criteria' => [
                'price_min' => $this->sanitize_number_input($_POST['price_min'] ?? 0),
                'price_max' => $this->sanitize_number_input($_POST['price_max'] ?? 0),
                'bedrooms' => $this->sanitize_number_input($_POST['bedrooms'] ?? 0, true),
                'bathrooms' => $this->sanitize_number_input($_POST['bathrooms'] ?? 0),
                'property_type' => sanitize_text_field($_POST['property_type'] ?? ''),
                'location' => sanitize_text_field($_POST['location'] ?? ''),
                'features' => $this->sanitize_array_input($_POST['features'] ?? [])
            ],
            'email_alerts' => !empty($_POST['email_alerts']),
            'created' => current_time('mysql')
        ];

        if (empty($search_data['name'])) {
            wp_send_json_error('Please provide a name for your saved search');
        }

        $user_id = get_current_user_id();
        $saved_searches = get_user_meta($user_id, 'hph_saved_searches', true) ?: [];
        $saved_searches[] = $search_data;

        update_user_meta($user_id, 'hph_saved_searches', $saved_searches);

        wp_send_json_success('Search saved successfully');
    }

    /**
     * Contact agent - IMPROVED with better validation
     */
    public function contact_agent(): void
    {
        check_ajax_referer('hph_contact_nonce', 'nonce');

        $data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'message' => sanitize_textarea_field($_POST['message'] ?? ''),
            'listing_id' => $this->sanitize_number_input($_POST['listing_id'] ?? 0, true)
        ];

        // Enhanced validation
        $validation_errors = $this->validate_contact_form($data);
        if (!empty($validation_errors)) {
            wp_send_json_error(implode('. ', $validation_errors));
        }

        // Get agent info - improved error handling
        $agent_id = get_field('agent', $data['listing_id']);
        $agent_email = get_field('agent_email', $agent_id) ?: get_field('email', $agent_id);

        if (!$agent_email) {
            error_log('Agent email not found for listing: ' . $data['listing_id']);
            wp_send_json_error('Unable to contact agent');
        }

        // Send email with improved formatting
        $subject = 'New Inquiry for ' . get_the_title($data['listing_id']);
        $email_content = $this->format_contact_email($data);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>'
        ];

        $sent = wp_mail($agent_email, $subject, $email_content, $headers);

        if ($sent) {
            // Log successful contact with better data
            $this->log_contact_attempt($data['listing_id'], $agent_id, $data['email'], true, $data);
            wp_send_json_success('Message sent successfully');
        } else {
            // Log failed contact
            $this->log_contact_attempt($data['listing_id'], $agent_id, $data['email'], false, $data);
            wp_send_json_error('Failed to send message');
        }
    }

    /**
     * Get map markers - EXACT COMPATIBILITY
     */
    public function get_map_markers(): void
    {
        check_ajax_referer('hph_map_nonce', 'nonce');

        try {
            $filters = [
                'price_min' => $this->sanitize_number_input($_POST['price_min'] ?? 0),
                'price_max' => $this->sanitize_number_input($_POST['price_max'] ?? 0),
                'bedrooms' => $this->sanitize_number_input($_POST['bedrooms'] ?? 0, true),
                'bathrooms' => $this->sanitize_number_input($_POST['bathrooms'] ?? 0),
                'property_type' => sanitize_text_field($_POST['property_type'] ?? ''),
                'status' => sanitize_text_field($_POST['status'] ?? 'Active')
            ];

            if (class_exists('Happy_Place_Listing_Helper')) {
                $listing_helper = Happy_Place_Listing_Helper::get_instance();
                if (method_exists($listing_helper, 'get_listing_markers')) {
                    $markers = $listing_helper->get_listing_markers($filters);
                    wp_send_json_success($markers);
                    return;
                }
            }

            // Fallback implementation if helper class not available
            $markers = $this->get_listing_markers_fallback($filters);
            wp_send_json_success($markers);
        } catch (Exception $e) {
            error_log('Error getting map markers: ' . $e->getMessage());
            wp_send_json_error('Error loading map markers');
        }
    }

    /**
     * ADDITIONAL: Dashboard section loading
     */
    public function load_dashboard_section(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hph_dashboard_nonce')) {
            wp_die(__('Security check failed.', 'happy-place'));
        }

        // Check user permissions
        if (!hph_can_access_dashboard()) {
            wp_send_json_error(__('Access denied.', 'happy-place'));
        }

        $section = sanitize_text_field($_POST['section']);
        $allowed_sections = ['overview', 'listings', 'open-houses', 'performance', 'leads', 'team', 'profile'];

        if (!in_array($section, $allowed_sections)) {
            wp_send_json_error(__('Invalid section.', 'happy-place'));
        }

        // Load section template
        ob_start();
        $template_path = get_template_directory() . "/templates/dashboard/{$section}.php";

        if (file_exists($template_path)) {
            include $template_path;
            $html = ob_get_clean();
            wp_send_json_success(['html' => $html]);
        } else {
            ob_end_clean();
            wp_send_json_error(__('Template not found.', 'happy-place'));
        }
    }

    /**
     * ADDITIONAL: Agent profile updates
     */
    public function update_agent_profile(): void
    {
        check_ajax_referer('hph_profile_nonce', 'nonce');
        $this->check_user_permissions();

        $agent_id = get_current_user_id();
        $allowed_fields = [
            'name',
            'title',
            'bio',
            'phone',
            'email',
            'facebook',
            'twitter',
            'linkedin',
            'instagram'
        ];

        $updated = [];
        foreach ($allowed_fields as $field) {
            if (isset($_POST[$field])) {
                $value = $field === 'bio'
                    ? sanitize_textarea_field($_POST[$field])
                    : sanitize_text_field($_POST[$field]);

                update_field($field, $value, "user_{$agent_id}");
                $updated[$field] = $value;
            }
        }

        wp_send_json_success($updated);
    }

    /**
     * ADDITIONAL: Get listing statistics
     */
    public function get_listing_stats(): void
    {
        check_ajax_referer('hph_stats_nonce', 'nonce');
        $this->check_user_permissions();

        $agent_id = get_current_user_id();
        $range = sanitize_text_field($_POST['range'] ?? '30d');

        $stats = $this->calculate_agent_stats($agent_id, $range);
        wp_send_json_success($stats);
    }

    /**
     * Utility Methods - IMPROVED VERSIONS
     */

    private function sanitize_number_input($value, bool $integer = false)
    {
        if ($value === '' || $value === null) {
            return '';
        }

        if ($integer) {
            return filter_var($value, FILTER_VALIDATE_INT) !== false
                ? intval($value)
                : '';
        }

        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false
            ? floatval($value)
            : '';
    }

    private function sanitize_array_input($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map('sanitize_text_field', $value);
    }

    private function validate_contact_form(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!is_email($data['email'])) {
            $errors[] = 'Please provide a valid email address';
        }

        if (empty($data['message'])) {
            $errors[] = 'Message is required';
        }

        if (!$data['listing_id']) {
            $errors[] = 'Invalid listing ID';
        }

        return $errors;
    }

    private function check_user_permissions(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in');
        }

        if (!current_user_can('agent') && !current_user_can('administrator')) {
            wp_send_json_error('Insufficient permissions');
        }
    }

    /**
     * EXACT COMPATIBILITY METHODS - Same as original
     */

    private function get_filtered_listings($filters)
    {
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => $filters['per_page'],
            'paged' => $filters['page'],
            'meta_query' => [],
            'tax_query' => []
        ];

        // Apply all filters exactly as original
        if ($filters['price_min'] > 0) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => $filters['price_min'],
                'type' => 'NUMERIC',
                'compare' => '>='
            ];
        }

        if ($filters['price_max'] > 0) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => $filters['price_max'],
                'type' => 'NUMERIC',
                'compare' => '<='
            ];
        }

        if ($filters['bedrooms'] > 0) {
            $args['meta_query'][] = [
                'key' => 'bedrooms',
                'value' => $filters['bedrooms'],
                'type' => 'NUMERIC',
                'compare' => '>='
            ];
        }

        if ($filters['bathrooms'] > 0) {
            $args['meta_query'][] = [
                'key' => 'bathrooms',
                'value' => $filters['bathrooms'],
                'type' => 'NUMERIC',
                'compare' => '>='
            ];
        }

        if ($filters['status']) {
            $args['meta_query'][] = [
                'key' => 'status',
                'value' => $filters['status'],
                'compare' => '='
            ];
        }

        if ($filters['property_type']) {
            $args['tax_query'][] = [
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $filters['property_type']
            ];
        }

        if (!empty($filters['features'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'feature',
                'field' => 'slug',
                'terms' => $filters['features']
            ];
        }

        if ($filters['location']) {
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key' => 'city',
                    'value' => $filters['location'],
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'zip_code',
                    'value' => $filters['location'],
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'neighborhood',
                    'value' => $filters['location'],
                    'compare' => 'LIKE'
                ]
            ];
        }

        // Sorting exactly as original
        switch ($filters['sort']) {
            case 'price_asc':
                $args['meta_key'] = 'price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_desc':
                $args['meta_key'] = 'price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'newest':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            case 'size_desc':
                $args['meta_key'] = 'square_footage';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
        }

        $query = new WP_Query($args);
        $listings = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                if ($filters['view'] === 'list') {
                    ob_start();
                    get_template_part('template-parts/cards/listing-list-card', null, [
                        'post_id' => get_the_ID(),
                        'size' => 'default'
                    ]);
                    $listings[] = ob_get_clean();
                } else {
                    ob_start();
                    get_template_part('template-parts/cards/listing-swipe-card', null, [
                        'post_id' => get_the_ID(),
                        'size' => 'default'
                    ]);
                    $listings[] = ob_get_clean();
                }
            }
            wp_reset_postdata();
        }

        return [
            'listings' => $listings,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ];
    }

    private function search_cities($term)
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT meta_value as city 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'city' 
            AND meta_value LIKE %s 
            LIMIT 10
        ", '%' . $wpdb->esc_like($term) . '%'));

        return array_map(function ($result) {
            return [
                'label' => $result->city,
                'value' => $result->city,
                'type' => 'city'
            ];
        }, $results);
    }

    private function search_zip_codes($term)
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT meta_value as zip 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'zip_code' 
            AND meta_value LIKE %s 
            LIMIT 5
        ", $wpdb->esc_like($term) . '%'));

        return array_map(function ($result) {
            return [
                'label' => $result->zip,
                'value' => $result->zip,
                'type' => 'zip'
            ];
        }, $results);
    }

    private function format_contact_email(array $data): string
    {
        $listing_title = get_the_title($data['listing_id']);
        $listing_url = get_permalink($data['listing_id']);
        $price = get_field('price', $data['listing_id']);
        $formatted_price = $price ? '$' . number_format($price) : 'Price not available';

        return "
            <h2>New Property Inquiry</h2>
            <h3>Listing Details</h3>
            <p><strong>Property:</strong> {$listing_title}</p>
            <p><strong>Price:</strong> {$formatted_price}</p>
            <p><strong>URL:</strong> <a href='{$listing_url}'>{$listing_url}</a></p>
            
            <h3>Inquirer Information</h3>
            <p><strong>Name:</strong> {$data['name']}</p>
            <p><strong>Email:</strong> {$data['email']}</p>
            <p><strong>Phone:</strong> {$data['phone']}</p>
            
            <h3>Message</h3>
            <p>" . nl2br(esc_html($data['message'])) . "</p>
            
            <hr>
            <p><small>This inquiry was sent from " . get_bloginfo('name') . "</small></p>
        ";
    }

    private function log_contact_attempt($listing_id, $agent_id, $inquirer_email, $success, $data = [])
    {
        global $wpdb;

        // Try to use custom table if it exists
        $table = $wpdb->prefix . 'hph_contact_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
            $wpdb->insert($table, [
                'listing_id' => $listing_id,
                'agent_id' => $agent_id,
                'inquirer_email' => $inquirer_email,
                'inquirer_name' => $data['name'] ?? '',
                'inquirer_phone' => $data['phone'] ?? '',
                'success' => $success ? 1 : 0,
                'created_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } else {
            // Fallback to post meta
            $log_data = [
                'post_type' => 'contact_log',
                'post_title' => 'Contact Attempt - ' . get_the_title($listing_id),
                'post_status' => 'publish',
                'meta_input' => [
                    'listing_id' => $listing_id,
                    'agent_id' => $agent_id,
                    'inquirer_email' => $inquirer_email,
                    'success' => $success,
                    'datetime' => current_time('mysql')
                ]
            ];

            wp_insert_post($log_data);
        }
    }

    private function get_listing_markers_fallback($filters): array
    {
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => 100, // Limit for performance
            'meta_query' => []
        ];

        // Apply basic filters
        if ($filters['price_min'] > 0) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => $filters['price_min'],
                'type' => 'NUMERIC',
                'compare' => '>='
            ];
        }

        if ($filters['price_max'] > 0) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => $filters['price_max'],
                'type' => 'NUMERIC',
                'compare' => '<='
            ];
        }

        $query = new WP_Query($args);
        $markers = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $lat = get_field('latitude', get_the_ID());
                $lng = get_field('longitude', get_the_ID());

                if ($lat && $lng) {
                    $markers[] = [
                        'id' => get_the_ID(),
                        'lat' => floatval($lat),
                        'lng' => floatval($lng),
                        'title' => get_the_title(),
                        'price' => get_field('price', get_the_ID()),
                        'url' => get_permalink(),
                        'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
                    ];
                }
            }
            wp_reset_postdata();
        }

        return $markers;
    }

    private function calculate_agent_stats($agent_id, $range): array
    {
        // Implementation for agent statistics
        return [
            'listings_count' => count(get_posts([
                'post_type' => 'listing',
                'author' => $agent_id,
                'post_status' => 'publish'
            ])),
            'active_listings' => count(get_posts([
                'post_type' => 'listing',
                'author' => $agent_id,
                'meta_query' => [['key' => 'status', 'value' => 'Active']]
            ])),
            // Add more stats as needed
        ];
    }
}

// Initialize - EXACT SAME WAY
HPH_Ajax_Handler::instance();
