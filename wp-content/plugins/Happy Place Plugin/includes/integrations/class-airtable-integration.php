<?php
namespace HappyPlace\Integrations;

use function get_option;
use function get_field;
use function get_the_title;
use function get_the_modified_date;
use function get_permalink;
use function wp_remote_post;
use function wp_remote_get;
use function is_wp_error;
use function wp_remote_retrieve_body;
use function add_query_arg;
use function wp_clear_scheduled_hook;
use function wp_next_scheduled;
use function wp_schedule_event;

class Airtable_Integration {
    private $api_key;
    private $base_id;
    private $tables;
    private $sync_schedules;

    /**
     * Initialize Airtable integration
     */
    public function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * Load integration settings
     */
    private function load_settings(): void {
        $integrations = get_option('hph_integrations', []);
        $airtable = $integrations['airtable'] ?? [];

        $this->api_key = $airtable['api_key'] ?? '';
        $this->base_id = $airtable['base_id'] ?? '';
        $this->tables = [
            'listings' => $airtable['tables']['listings'] ?? 'Listings',
            'agents' => $airtable['tables']['agents'] ?? 'Agents',
            'leads' => $airtable['tables']['leads'] ?? 'Leads',
            'openhouses' => $airtable['tables']['openhouses'] ?? 'Open Houses',
            'views' => $airtable['tables']['views'] ?? 'Property Views',
            'saved_searches' => $airtable['tables']['saved_searches'] ?? 'Saved Searches',
            'inquiries' => $airtable['tables']['inquiries'] ?? 'Inquiries',
            'rsvps' => $airtable['tables']['rsvps'] ?? 'Open House RSVPs',
        ];
        $this->sync_schedules = $airtable['sync_schedules'] ?? [];
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Sync hooks for different post types
        add_action('save_post_property', [$this, 'sync_listing'], 10, 3);
        add_action('save_post_agent', [$this, 'sync_agent'], 10, 3);
        add_action('hph_new_lead', [$this, 'sync_lead']);
        add_action('hph_new_open_house', [$this, 'sync_open_house']);
        
        // Event tracking hooks
        add_action('hph_lead_viewed_property', [$this, 'track_property_view']);
        add_action('hph_lead_saved_search', [$this, 'track_saved_search']);
        add_action('hph_property_inquiry', [$this, 'track_property_inquiry']);
        add_action('hph_open_house_rsvp', [$this, 'track_open_house_rsvp']);

        // Schedule periodic syncs
        if (!empty($this->sync_schedules)) {
            foreach ($this->sync_schedules as $type => $schedule) {
                if ($schedule === 'hourly') {
                    if (!wp_next_scheduled('hph_airtable_sync_' . $type)) {
                        wp_schedule_event(time(), 'hourly', 'hph_airtable_sync_' . $type);
                    }
                    add_action('hph_airtable_sync_' . $type, [$this, 'sync_' . $type . '_table']);
                }
            }
        }
    }

    /**
     * Sync listing to Airtable
     */
    public function sync_listing($post_id, $post, $update): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        try {
            $listing_data = $this->prepare_listing_data($post_id);
            $this->upsert_record($this->tables['listings'], $listing_data);
        } catch (\Exception $e) {
            error_log('Airtable Listing Sync Error: ' . $e->getMessage());
        }
    }

    /**
     * Prepare listing data for Airtable
     */
    private function prepare_listing_data($post_id): array {
        $price = get_field('property_price', $post_id);
        $details = get_field('property_details', $post_id);
        $location = get_field('property_location', $post_id);
        $agent = get_field('property_agent', $post_id);

        return [
            'Title' => get_the_title($post_id),
            'Status' => get_field('property_status', $post_id),
            'Price' => $price,
            'Bedrooms' => $details['bedrooms'] ?? '',
            'Bathrooms' => $details['bathrooms'] ?? '',
            'Square Footage' => $details['square_footage'] ?? '',
            'Address' => get_field('property_address', $post_id),
            'City' => $location['city'] ?? '',
            'State' => $location['state'] ?? '',
            'Zip' => $location['zip'] ?? '',
            'Agent' => $agent ? get_the_title($agent) : '',
            'Last Updated' => get_the_modified_date('Y-m-d H:i:s', $post_id),
            'Listing URL' => get_permalink($post_id)
        ];
    }

    /**
     * Upsert record to Airtable
     */
    private function upsert_record(string $table, array $data): array {
        if (!$this->api_key || !$this->base_id) {
            throw new \Exception('Airtable API credentials not configured');
        }

        $url = "https://api.airtable.com/v0/{$this->base_id}/" . urlencode($table);

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'records' => [
                    [
                        'fields' => $data
                    ]
                ]
            ])
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Airtable API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Sync entire table from Airtable
     */
    public function sync_table(string $table_name): array {
        if (!$this->api_key || !$this->base_id) {
            throw new \Exception('Airtable API credentials not configured');
        }

        $records = [];
        $url = "https://api.airtable.com/v0/{$this->base_id}/" . urlencode($table_name);
        $offset = null;

        do {
            $params = ['maxRecords' => 100];
            if ($offset) {
                $params['offset'] = $offset;
            }

            $response = wp_remote_get(add_query_arg($params, $url), [
                'headers' => [
                    'Authorization' => "Bearer {$this->api_key}",
                    'Content-Type' => 'application/json'
                ]
            ]);

            if (is_wp_error($response)) {
                throw new \Exception('Airtable API request failed: ' . $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            $records = array_merge($records, $data['records'] ?? []);
            $offset = $data['offset'] ?? null;

        } while ($offset);

        return $records;
    }

    /**
     * Schedule table sync
     */
    public function schedule_sync(string $type, string $frequency = 'hourly'): void {
        if (!in_array($frequency, ['hourly', 'daily', 'weekly'])) {
            throw new \Exception('Invalid sync frequency');
        }

        $integrations = get_option('hph_integrations', []);
        $integrations['airtable']['sync_schedules'][$type] = $frequency;
        update_option('hph_integrations', $integrations);

        // Clear existing schedule
        $hook = 'hph_airtable_sync_' . $type;
        wp_clear_scheduled_hook($hook);

        // Set new schedule
        if (!wp_next_scheduled($hook)) {
            wp_schedule_event(time(), $frequency, $hook);
        }
    }

    /**
     * Track property view in Airtable
     */
    public function track_property_view($view_data): void {
        try {
            $records = [
                'fields' => [
                    'Lead' => $view_data['lead_id'],
                    'Property' => $view_data['property_id'],
                    'View Date' => date('Y-m-d H:i:s'),
                    'Source' => 'Website'
                ]
            ];
            
            $this->upsert_record($this->tables['views'], $records);
        } catch (\Exception $e) {
            error_log('Airtable Property View Tracking Error: ' . $e->getMessage());
        }
    }

    /**
     * Track saved search in Airtable
     */
    public function track_saved_search($search_data): void {
        try {
            $records = [
                'fields' => [
                    'Lead' => $search_data['lead_id'],
                    'Search Criteria' => json_encode($search_data['criteria']),
                    'Date Saved' => date('Y-m-d H:i:s'),
                    'Status' => 'Active'
                ]
            ];
            
            $this->upsert_record($this->tables['saved_searches'], $records);
        } catch (\Exception $e) {
            error_log('Airtable Saved Search Tracking Error: ' . $e->getMessage());
        }
    }

    /**
     * Track property inquiry in Airtable
     */
    public function track_property_inquiry($inquiry_data): void {
        try {
            $records = [
                'fields' => [
                    'Lead' => $inquiry_data['lead_id'],
                    'Property' => $inquiry_data['property_id'],
                    'Message' => $inquiry_data['message'],
                    'Inquiry Date' => date('Y-m-d H:i:s'),
                    'Status' => 'New'
                ]
            ];
            
            $this->upsert_record($this->tables['inquiries'], $records);
        } catch (\Exception $e) {
            error_log('Airtable Property Inquiry Tracking Error: ' . $e->getMessage());
        }
    }

    /**
     * Track open house RSVP in Airtable
     */
    public function track_open_house_rsvp($rsvp_data): void {
        try {
            $records = [
                'fields' => [
                    'Lead' => $rsvp_data['lead_id'],
                    'Property' => $rsvp_data['property_id'],
                    'Open House Date' => $rsvp_data['date'],
                    'RSVP Date' => date('Y-m-d H:i:s'),
                    'Status' => 'Confirmed',
                    'Notes' => $rsvp_data['notes'] ?? ''
                ]
            ];
            
            $this->upsert_record($this->tables['rsvps'], $records);
        } catch (\Exception $e) {
            error_log('Airtable Open House RSVP Tracking Error: ' . $e->getMessage());
        }
    }

    /**
     * Sync lead to Airtable
     */
    public function sync_lead($lead_data): void {
        try {
            $records = [
                'fields' => [
                    'First Name' => $lead_data['first_name'],
                    'Last Name' => $lead_data['last_name'],
                    'Email' => $lead_data['email'],
                    'Phone' => $lead_data['phone'] ?? '',
                    'Source' => $lead_data['source'] ?? 'Website',
                    'Status' => $lead_data['status'] ?? 'New',
                    'Lead Type' => $lead_data['type'] ?? 'Buyer',
                    'Created Date' => date('Y-m-d H:i:s'),
                    'Notes' => $lead_data['notes'] ?? ''
                ]
            ];
            
            $this->upsert_record($this->tables['leads'], $records);
        } catch (\Exception $e) {
            error_log('Airtable Lead Sync Error: ' . $e->getMessage());
        }
    }

    /**
     * Sync agent to Airtable
     */
    public function sync_agent($post_id, $post, $update): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        try {
            $agent_data = [
                'fields' => [
                    'Name' => get_the_title($post_id),
                    'Email' => get_field('agent_email', $post_id),
                    'Phone' => get_field('agent_phone', $post_id),
                    'Title' => get_field('agent_title', $post_id),
                    'Office' => get_field('agent_office', $post_id),
                    'License' => get_field('agent_license', $post_id),
                    'Specialties' => get_field('agent_specialties', $post_id),
                    'Bio' => get_field('agent_bio', $post_id),
                    'Active Listings' => get_field('agent_active_listings', $post_id),
                    'Last Updated' => date('Y-m-d H:i:s')
                ]
            ];
            
            $this->upsert_record($this->tables['agents'], $agent_data);
        } catch (\Exception $e) {
            error_log('Airtable Agent Sync Error: ' . $e->getMessage());
        }
    }
}
