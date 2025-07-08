<?php
/**
 * Integrations Manager
 * 
 * Centralized management for all third-party API integrations
 * File: includes/integrations/class-integrations-manager.php
 */

namespace HappyPlace\Integrations;

use function get_option;
use function update_option;
use function wp_remote_get;
use function wp_remote_post;
use function wp_remote_request;
use function is_wp_error;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_response_code;
use function json_decode;
use function json_encode;
use function error_log;
use function add_action;
use function wp_schedule_event;
use function wp_next_scheduled;

class Integrations_Manager {
    private static ?self $instance = null;

    // Integration configurations
    private $integrations_config = [];
    private $api_credentials = [];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->load_configurations();
        $this->init_hooks();
    }

    /**
     * Load all integration configurations
     */
    private function load_configurations(): void {
        $this->api_credentials = get_option('hph_api_credentials', []);
        
        $this->integrations_config = [
            'google' => [
                'name' => 'Google Services',
                'apis' => [
                    'maps' => [
                        'name' => 'Google Maps',
                        'endpoint' => 'https://maps.googleapis.com/maps/api',
                        'required_key' => 'google_maps_api_key'
                    ],
                    'places' => [
                        'name' => 'Google Places',
                        'endpoint' => 'https://maps.googleapis.com/maps/api/place',
                        'required_key' => 'google_places_api_key'
                    ],
                    'geocoding' => [
                        'name' => 'Google Geocoding',
                        'endpoint' => 'https://maps.googleapis.com/maps/api/geocode',
                        'required_key' => 'google_geocoding_api_key'
                    ]
                ]
            ],
            'airtable' => [
                'name' => 'Airtable',
                'endpoint' => 'https://api.airtable.com/v0',
                'required_keys' => ['airtable_api_key', 'airtable_base_id']
            ],
            'followupboss' => [
                'name' => 'Follow Up Boss',
                'endpoint' => 'https://api.followupboss.com/v1',
                'required_keys' => ['followupboss_api_key']
            ],
            'dotloop' => [
                'name' => 'DotLoop',
                'endpoint' => 'https://api-gateway.dotloop.com/public/v2',
                'required_keys' => ['dotloop_api_key', 'dotloop_client_id']
            ],
            'mailchimp' => [
                'name' => 'Mailchimp',
                'endpoint' => 'https://api.mailchimp.com/3.0',
                'required_keys' => ['mailchimp_api_key', 'mailchimp_server_prefix']
            ]
        ];
    }

    /**
     * Initialize hooks and schedulers
     */
    private function init_hooks(): void {
        // Schedule periodic syncs
        add_action('init', [$this, 'schedule_periodic_syncs']);
        
        // Sync hooks
        add_action('hph_sync_airtable', [$this, 'sync_airtable_data']);
        add_action('hph_sync_followupboss', [$this, 'sync_followupboss_data']);
        add_action('hph_geocode_address', [$this, 'geocode_address_hook'], 10, 2);
        add_action('hph_sync_google_places', [$this, 'sync_google_places_data']);
        
        // WordPress hooks
        add_action('save_post_listing', [$this, 'on_listing_save'], 10, 3);
        add_action('save_post_local-place', [$this, 'on_local_place_save'], 10, 3);
    }

    /**
     * Schedule periodic sync jobs
     */
    public function schedule_periodic_syncs(): void {
        $sync_settings = get_option('hph_sync_settings', [
            'airtable_frequency' => 'hourly',
            'followupboss_frequency' => 'daily',
            'google_places_frequency' => 'weekly'
        ]);

        foreach ($sync_settings as $service => $frequency) {
            $hook = "hph_sync_{$service}";
            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), $frequency, $hook);
            }
        }
    }

    // ========================================
    // GOOGLE MAPS & PLACES INTEGRATION
    // ========================================

    /**
     * Geocode an address using Google Geocoding API
     */
    public function geocode_address(string $address): ?array {
        $api_key = $this->get_api_key('google_geocoding_api_key');
        if (!$api_key) {
            error_log('HPH: Google Geocoding API key not configured');
            return null;
        }

        $endpoint = $this->integrations_config['google']['apis']['geocoding']['endpoint'] . '/json';
        $url = add_query_arg([
            'address' => urlencode($address),
            'key' => $api_key
        ], $endpoint);

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'Happy Place Real Estate Plugin'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('HPH: Geocoding API error: ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $result = $data['results'][0];
            return [
                'latitude' => $result['geometry']['location']['lat'],
                'longitude' => $result['geometry']['location']['lng'],
                'formatted_address' => $result['formatted_address'],
                'place_id' => $result['place_id']
            ];
        }

        error_log('HPH: Geocoding failed for address: ' . $address);
        return null;
    }

    /**
     * Get place details from Google Places API
     */
    public function get_place_details(string $place_id): ?array {
        $api_key = $this->get_api_key('google_places_api_key');
        if (!$api_key) {
            error_log('HPH: Google Places API key not configured');
            return null;
        }

        $endpoint = $this->integrations_config['google']['apis']['places']['endpoint'] . '/details/json';
        $url = add_query_arg([
            'place_id' => $place_id,
            'fields' => 'name,rating,user_ratings_total,formatted_phone_number,website,opening_hours,photos,reviews,price_level',
            'key' => $api_key
        ], $endpoint);

        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            error_log('HPH: Places API error: ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['status'] === 'OK') {
            return $data['result'];
        }

        return null;
    }

    /**
     * Search for places near a location
     */
    public function search_nearby_places(float $lat, float $lng, string $type = 'restaurant', int $radius = 5000): array {
        $api_key = $this->get_api_key('google_places_api_key');
        if (!$api_key) {
            return [];
        }

        $endpoint = $this->integrations_config['google']['apis']['places']['endpoint'] . '/nearbysearch/json';
        $url = add_query_arg([
            'location' => $lat . ',' . $lng,
            'radius' => $radius,
            'type' => $type,
            'key' => $api_key
        ], $endpoint);

        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data['status'] === 'OK' ? $data['results'] : [];
    }

    // ========================================
    // AIRTABLE INTEGRATION
    // ========================================

    /**
     * Sync data with Airtable
     */
    public function sync_airtable_data(): void {
        $api_key = $this->get_api_key('airtable_api_key');
        $base_id = $this->get_api_key('airtable_base_id');
        
        if (!$api_key || !$base_id) {
            error_log('HPH: Airtable credentials not configured');
            return;
        }

        // Sync listings
        $this->sync_listings_to_airtable($api_key, $base_id);
        
        // Sync agents
        $this->sync_agents_to_airtable($api_key, $base_id);
        
        // Sync open houses
        $this->sync_open_houses_to_airtable($api_key, $base_id);
    }

    /**
     * Sync listings to Airtable
     */
    private function sync_listings_to_airtable(string $api_key, string $base_id): void {
        $listings = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => 50,
            'post_status' => 'publish'
        ]);

        foreach ($listings as $listing) {
            $data = [
                'Title' => $listing->post_title,
                'Price' => get_field('price', $listing->ID),
                'Bedrooms' => get_field('bedrooms', $listing->ID),
                'Bathrooms' => get_field('bathrooms', $listing->ID),
                'Square_Footage' => get_field('square_footage', $listing->ID),
                'Address' => get_field('street_address', $listing->ID),
                'City' => get_field('city', $listing->ID),
                'Status' => get_field('status', $listing->ID),
                'Last_Updated' => get_the_modified_date('Y-m-d H:i:s', $listing->ID),
                'Listing_URL' => get_permalink($listing->ID)
            ];

            $this->send_to_airtable($api_key, $base_id, 'Listings', $data);
        }
    }

    /**
     * Send data to Airtable
     */
    private function send_to_airtable(string $api_key, string $base_id, string $table, array $data): bool {
        $url = $this->integrations_config['airtable']['endpoint'] . "/{$base_id}/{$table}";

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => "Bearer {$api_key}",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'records' => [
                    ['fields' => $data]
                ]
            ]),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('HPH: Airtable sync error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
    }

    // ========================================
    // FOLLOW UP BOSS INTEGRATION
    // ========================================

    /**
     * Sync data with Follow Up Boss
     */
    public function sync_followupboss_data(): void {
        $api_key = $this->get_api_key('followupboss_api_key');
        if (!$api_key) {
            error_log('HPH: Follow Up Boss API key not configured');
            return;
        }

        // Import leads from Follow Up Boss
        $this->import_followupboss_leads($api_key);
        
        // Export inquiries to Follow Up Boss
        $this->export_inquiries_to_followupboss($api_key);
    }

    /**
     * Create lead in Follow Up Boss
     */
    public function create_followupboss_lead(array $lead_data): bool {
        $api_key = $this->get_api_key('followupboss_api_key');
        if (!$api_key) {
            return false;
        }

        $url = $this->integrations_config['followupboss']['endpoint'] . '/people';

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($lead_data),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('HPH: Follow Up Boss lead creation error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
    }

    /**
     * Import leads from Follow Up Boss
     */
    private function import_followupboss_leads(string $api_key): void {
        $url = $this->integrations_config['followupboss']['endpoint'] . '/people';

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('HPH: Follow Up Boss import error: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!empty($data['people'])) {
            foreach ($data['people'] as $person) {
                $this->process_followupboss_lead($person);
            }
        }
    }

    /**
     * Process a lead from Follow Up Boss
     */
    private function process_followupboss_lead(array $person): void {
        // Check if lead already exists
        $existing_lead = get_posts([
            'post_type' => 'lead',
            'meta_query' => [
                [
                    'key' => '_followupboss_id',
                    'value' => $person['id'],
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);

        if (!empty($existing_lead)) {
            return; // Lead already exists
        }

        // Create new lead post
        $lead_data = [
            'post_title' => $person['name'] ?? 'Unknown Lead',
            'post_type' => 'lead',
            'post_status' => 'publish',
            'meta_input' => [
                '_followupboss_id' => $person['id'],
                '_lead_email' => $person['emails'][0]['value'] ?? '',
                '_lead_phone' => $person['phones'][0]['value'] ?? '',
                '_lead_source' => $person['source'] ?? 'Follow Up Boss',
                '_lead_status' => $person['stage'] ?? 'new'
            ]
        ];

        wp_insert_post($lead_data);
    }

    // ========================================
    // MAILCHIMP INTEGRATION
    // ========================================

    /**
     * Add subscriber to Mailchimp list
     */
    public function add_mailchimp_subscriber(string $email, string $first_name = '', string $last_name = '', array $merge_fields = []): bool {
        $api_key = $this->get_api_key('mailchimp_api_key');
        $server_prefix = $this->get_api_key('mailchimp_server_prefix');
        $list_id = $this->get_api_key('mailchimp_list_id');

        if (!$api_key || !$server_prefix || !$list_id) {
            error_log('HPH: Mailchimp credentials not configured');
            return false;
        }

        $url = "https://{$server_prefix}.api.mailchimp.com/3.0/lists/{$list_id}/members";

        $data = [
            'email_address' => $email,
            'status' => 'subscribed',
            'merge_fields' => array_merge([
                'FNAME' => $first_name,
                'LNAME' => $last_name
            ], $merge_fields)
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('anystring:' . $api_key),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            error_log('HPH: Mailchimp error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
    }

    // ========================================
    // WEBHOOK HANDLERS
    // ========================================

    /**
     * Handle listing save
     */
    public function on_listing_save($post_id, $post, $update): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Geocode address if not already done
        $latitude = get_field('latitude', $post_id);
        if (empty($latitude)) {
            $address = $this->build_full_address($post_id);
            if ($address) {
                $this->geocode_address_hook($post_id, $address);
            }
        }

        // Sync to Airtable (async)
        wp_schedule_single_event(time() + 60, 'hph_sync_single_listing_airtable', [$post_id]);

        // Create Follow Up Boss lead if inquiry
        if ($update && !empty($_POST['inquiry_email'])) {
            $this->create_inquiry_lead($post_id, $_POST);
        }
    }

    /**
     * Handle local place save
     */
    public function on_local_place_save($post_id, $post, $update): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Sync with Google Places if place_id exists
        $place_id = get_field('google_place_id', $post_id);
        if ($place_id) {
            wp_schedule_single_event(time() + 30, 'hph_sync_single_place_google', [$post_id, $place_id]);
        }
    }

    /**
     * Geocode address hook
     */
    public function geocode_address_hook($post_id, string $address): void {
        $coordinates = $this->geocode_address($address);
        if ($coordinates) {
            update_field('latitude', $coordinates['latitude'], $post_id);
            update_field('longitude', $coordinates['longitude'], $post_id);
            
            if (!empty($coordinates['place_id'])) {
                update_field('google_place_id', $coordinates['place_id'], $post_id);
            }
        }
    }

    /**
     * Sync Google Places data
     */
    public function sync_google_places_data(): void {
        // Get all local places that need updating
        $places = get_posts([
            'post_type' => 'local-place',
            'posts_per_page' => 50,
            'meta_query' => [
                [
                    'key' => 'google_place_id',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ]);

        foreach ($places as $place) {
            $place_id = get_field('google_place_id', $place->ID);
            if ($place_id) {
                $place_data = $this->get_place_details($place_id);
                if ($place_data) {
                    $this->update_local_place_with_google_data($place->ID, $place_data);
                }
                // Rate limit - sleep 1 second between requests
                sleep(1);
            }
        }
    }

    // ========================================
    // UTILITY METHODS
    // ========================================

    /**
     * Get API key from stored credentials
     */
    private function get_api_key(string $key): ?string {
        return $this->api_credentials[$key] ?? null;
    }

    /**
     * Build full address from listing fields
     */
    private function build_full_address(int $post_id): ?string {
        $street = get_field('street_address', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('region', $post_id);
        $zip = get_field('zip_code', $post_id);

        if (!$street || !$city) {
            return null;
        }

        return trim("{$street}, {$city}, {$state} {$zip}");
    }

    /**
     * Update local place with Google Places data
     */
    private function update_local_place_with_google_data(int $post_id, array $place_data): void {
        $google_data = [
            'google_rating' => $place_data['rating'] ?? null,
            'google_reviews_count' => $place_data['user_ratings_total'] ?? null,
            'business_phone' => $place_data['formatted_phone_number'] ?? '',
            'business_website' => $place_data['website'] ?? '',
            'last_api_sync' => current_time('mysql')
        ];

        foreach ($google_data as $field => $value) {
            if ($value !== null) {
                update_field($field, $value, $post_id);
            }
        }

        // Update business hours if available
        if (!empty($place_data['opening_hours']['weekday_text'])) {
            update_field('business_hours', implode("\n", $place_data['opening_hours']['weekday_text']), $post_id);
        }
    }

    /**
     * Create inquiry lead in Follow Up Boss
     */
    private function create_inquiry_lead(int $listing_id, array $inquiry_data): void {
        $lead_data = [
            'person' => [
                'firstName' => $inquiry_data['inquiry_name'] ?? '',
                'emails' => [['value' => $inquiry_data['inquiry_email'] ?? '']],
                'phones' => [['value' => $inquiry_data['inquiry_phone'] ?? '']]
            ],
            'source' => 'Website Property Inquiry',
            'property' => [
                'address' => get_field('street_address', $listing_id),
                'price' => get_field('price', $listing_id),
                'url' => get_permalink($listing_id)
            ],
            'message' => $inquiry_data['inquiry_message'] ?? ''
        ];

        $this->create_followupboss_lead($lead_data);
    }

    /**
     * Test Airtable connection
     */
    public function test_airtable_connection(): bool {
        $api_key = $this->get_api_key('airtable_api_key');
        $base_id = $this->get_api_key('airtable_base_id');
        
        if (!$api_key || !$base_id) {
            return false;
        }

        $url = $this->integrations_config['airtable']['endpoint'] . "/{$base_id}/Test";

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => "Bearer {$api_key}",
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
    }

    /**
     * Test Follow Up Boss connection
     */
    public function test_followupboss_connection(): bool {
        $api_key = $this->get_api_key('followupboss_api_key');
        if (!$api_key) {
            return false;
        }

        $url = $this->integrations_config['followupboss']['endpoint'] . '/me';

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
    }

    /**
     * Test Mailchimp connection
     */
    public function test_mailchimp_connection(): bool {
        $api_key = $this->get_api_key('mailchimp_api_key');
        $server_prefix = $this->get_api_key('mailchimp_server_prefix');

        if (!$api_key || !$server_prefix) {
            return false;
        }

        $url = "https://{$server_prefix}.api.mailchimp.com/3.0/ping";

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('anystring:' . $api_key),
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
    }

    /**
     * Sync agents to Airtable
     */
    private function sync_agents_to_airtable(string $api_key, string $base_id): void {
        $agents = get_posts([
            'post_type' => 'agent',
            'posts_per_page' => 50,
            'post_status' => 'publish'
        ]);

        foreach ($agents as $agent) {
            $data = [
                'Name' => $agent->post_title,
                'Email' => get_field('email', $agent->ID),
                'Phone' => get_field('phone', $agent->ID),
                'License_Number' => get_field('license_number', $agent->ID),
                'Active_Listings' => count(get_posts([
                    'post_type' => 'listing',
                    'meta_query' => [
                        [
                            'key' => 'agent',
                            'value' => $agent->ID,
                            'compare' => '='
                        ]
                    ],
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ])),
                'Last_Updated' => get_the_modified_date('Y-m-d H:i:s', $agent->ID)
            ];

            $this->send_to_airtable($api_key, $base_id, 'Agents', $data);
        }
    }

    /**
     * Sync open houses to Airtable
     */
    private function sync_open_houses_to_airtable(string $api_key, string $base_id): void {
        $open_houses = get_posts([
            'post_type' => 'open-house',
            'posts_per_page' => 100,
            'post_status' => 'publish'
        ]);

        foreach ($open_houses as $open_house) {
            $related_listing = get_field('related_listing', $open_house->ID);
            $hosting_agent = get_field('hosting_agent', $open_house->ID);

            $data = [
                'Title' => $open_house->post_title,
                'Date' => get_field('open_house_date', $open_house->ID),
                'Start_Time' => get_field('start_time', $open_house->ID),
                'End_Time' => get_field('end_time', $open_house->ID),
                'Status' => get_field('open_house_status', $open_house->ID),
                'Property' => $related_listing ? $related_listing->post_title : '',
                'Host_Agent' => $hosting_agent ? $hosting_agent->post_title : '',
                'RSVP_Required' => get_field('rsvp_required', $open_house->ID) ? 'Yes' : 'No',
                'Last_Updated' => get_the_modified_date('Y-m-d H:i:s', $open_house->ID)
            ];

            $this->send_to_airtable($api_key, $base_id, 'Open_Houses', $data);
        }
    }

    /**
     * Export inquiries to Follow Up Boss
     */
    private function export_inquiries_to_followupboss(string $api_key): void {
        // Get recent inquiries that haven't been synced
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_inquiries';
        $inquiries = $wpdb->get_results(
            "SELECT * FROM {$table_name} 
             WHERE status = 'new' 
             AND followupboss_synced = 0 
             ORDER BY created_at DESC 
             LIMIT 50"
        );

        foreach ($inquiries as $inquiry) {
            $property = get_post($inquiry->property_id);
            
            $lead_data = [
                'person' => [
                    'firstName' => $inquiry->name,
                    'emails' => [['value' => $inquiry->email]],
                    'phones' => !empty($inquiry->phone) ? [['value' => $inquiry->phone]] : []
                ],
                'source' => 'Website Property Inquiry',
                'type' => 'buyer',
                'property' => [
                    'address' => get_field('street_address', $property->ID),
                    'price' => get_field('price', $property->ID),
                    'url' => get_permalink($property->ID)
                ],
                'message' => $inquiry->message
            ];

            if ($this->create_followupboss_lead($lead_data)) {
                // Mark as synced
                $wpdb->update(
                    $table_name,
                    ['followupboss_synced' => 1],
                    ['id' => $inquiry->id],
                    ['%d'],
                    ['%d']
                );
            }
        }
    }

    /**
     * Get integration status for all services
     */
    public function get_integration_status(): array {
        $status = [];

        // Test each integration
        $status['google'] = $this->get_api_key('google_maps_api_key') ? 'configured' : 'not_configured';
        $status['airtable'] = ($this->get_api_key('airtable_api_key') && $this->get_api_key('airtable_base_id')) ? 'configured' : 'not_configured';
        $status['followupboss'] = $this->get_api_key('followupboss_api_key') ? 'configured' : 'not_configured';
        $status['mailchimp'] = ($this->get_api_key('mailchimp_api_key') && $this->get_api_key('mailchimp_server_prefix')) ? 'configured' : 'not_configured';

        return $status;
    }

    /**
     * Get sync statistics
     */
    public function get_sync_stats(): array {
        $stats = get_option('hph_sync_stats', []);
        
        return [
            'last_airtable_sync' => $stats['last_airtable_sync'] ?? 'Never',
            'last_followupboss_sync' => $stats['last_followupboss_sync'] ?? 'Never',
            'last_google_places_sync' => $stats['last_google_places_sync'] ?? 'Never',
            'total_synced_listings' => $stats['total_synced_listings'] ?? 0,
            'total_synced_agents' => $stats['total_synced_agents'] ?? 0,
            'total_synced_leads' => $stats['total_synced_leads'] ?? 0
        ];
    }

    /**
     * Update sync statistics
     */
    private function update_sync_stats(string $service, array $data = []): void {
        $stats = get_option('hph_sync_stats', []);
        $stats["last_{$service}_sync"] = current_time('Y-m-d H:i:s');
        
        foreach ($data as $key => $value) {
            $stats[$key] = $value;
        }
        
        update_option('hph_sync_stats', $stats);
    }
}
