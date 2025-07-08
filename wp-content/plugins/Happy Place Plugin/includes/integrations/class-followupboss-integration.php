<?php
namespace HappyPlace\Integrations;

use function get_post;
use function get_field;
use function get_the_title;
use function get_permalink;
use function wp_remote_post;
use function wp_remote_request;
use function wp_remote_get;
use function is_wp_error;
use function wp_remote_retrieve_body;
use function get_option;
use function add_action;
use function base64_encode;
use function json_encode;
use function json_decode;
use function date;
use function strtotime;
use function sprintf;
use function error_log;

class FollowUpBoss_Integration {
    private $api_key;
    private $default_source;
    private $auto_import;
    private $api_url = 'https://api.followupboss.com/v1/';

    /**
     * Initialize Follow Up Boss integration
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
        $fub = $integrations['follow_up_boss'] ?? [];

        $this->api_key = $fub['api_key'] ?? '';
        $this->default_source = $fub['lead_source'] ?? 'Website';
        $this->auto_import = $fub['auto_import'] ?? false;
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        if ($this->auto_import) {
            add_action('hph_new_inquiry', [$this, 'handle_new_inquiry']);
            add_action('hph_new_lead', [$this, 'handle_new_lead']);
            add_action('gform_after_submission', [$this, 'handle_gravity_form_submission'], 10, 2);
            add_action('wpcf7_mail_sent', [$this, 'handle_contact_form_submission']);
            
            // Event tracking hooks
            add_action('hph_lead_viewed_property', [$this, 'track_property_view']);
            add_action('hph_lead_saved_search', [$this, 'track_saved_search']);
            add_action('hph_property_inquiry', [$this, 'track_property_inquiry']);
            add_action('hph_open_house_rsvp', [$this, 'track_open_house_rsvp']);
        }
    }

    /**
     * Handle new property inquiry
     */
    public function handle_new_inquiry($inquiry_id): void {
        try {
            global $wpdb;
            $inquiry = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}hph_inquiries WHERE id = %d",
                $inquiry_id
            ));

            if ($inquiry) {
                $property = get_post($inquiry->property_id);
                $lead_data = [
                    'person' => [
                        'firstName' => $inquiry->name,
                        'emails' => [['value' => $inquiry->email]],
                        'phones' => [['value' => $inquiry->phone]]
                    ],
                    'source' => $this->default_source,
                    'type' => 'buyer',
                    'system' => [
                        'tags' => ['Property Inquiry']
                    ],
                    'property' => [
                        'address' => get_field('property_address', $property->ID),
                        'price' => get_field('property_price', $property->ID),
                        'mlsNumber' => get_field('property_mls_number', $property->ID),
                        'url' => get_permalink($property->ID)
                    ],
                    'message' => $inquiry->message
                ];

                $this->create_lead($lead_data);
            }
        } catch (\Exception $e) {
            error_log('Follow Up Boss Inquiry Sync Error: ' . $e->getMessage());
        }
    }

    /**
     * Create lead in Follow Up Boss
     */
    private function create_lead(array $data): array {
        if (!$this->api_key) {
            throw new \Exception('Follow Up Boss API credentials not configured');
        }

        $response = wp_remote_post($this->api_url . 'people', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->api_key . ':'),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Follow Up Boss API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (!empty($result['error'])) {
            throw new \Exception('Follow Up Boss API Error: ' . $result['error']);
        }

        return $result;
    }

    /**
     * Update lead in Follow Up Boss
     */
    public function update_lead(string $lead_id, array $data): array {
        if (!$this->api_key) {
            throw new \Exception('Follow Up Boss API credentials not configured');
        }

        $response = wp_remote_request($this->api_url . 'people/' . $lead_id, [
            'method' => 'PUT',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->api_key . ':'),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Follow Up Boss API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (!empty($result['error'])) {
            throw new \Exception('Follow Up Boss API Error: ' . $result['error']);
        }

        return $result;
    }

    /**
     * Handle Gravity Forms submission
     */
    public function handle_gravity_form_submission($entry, $form): void {
        // Check if this is a property inquiry form
        if ($form['id'] !== get_option('hph_property_inquiry_form_id')) {
            return;
        }

        try {
            $lead_data = $this->prepare_gravity_form_data($entry, $form);
            $this->create_lead($lead_data);
        } catch (\Exception $e) {
            error_log('Follow Up Boss GF Sync Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle Contact Form 7 submission
     */
    public function handle_contact_form_submission($contact_form): void {
        // Check if this is a property inquiry form
        if ($contact_form->id() !== get_option('hph_property_inquiry_cf7_id')) {
            return;
        }

        try {
            $submission = \WPCF7_Submission::get_instance();
            if ($submission) {
                $lead_data = $this->prepare_cf7_data($submission);
                $this->create_lead($lead_data);
            }
        } catch (\Exception $e) {
            error_log('Follow Up Boss CF7 Sync Error: ' . $e->getMessage());
        }
    }

    /**
     * Track property view
     */
    public function track_property_view($view_data): void {
        try {
            $lead_id = $view_data['lead_id'];
            $property = get_post($view_data['property_id']);

            if (!$property) {
                return;
            }

            $event_data = [
                'person' => $lead_id,
                'type' => 'note',
                'message' => sprintf(
                    'Viewed property: %s (%s)',
                    get_the_title($property),
                    get_permalink($property)
                ),
                'source' => 'Website Property View'
            ];

            $this->create_event($event_data);
        } catch (\Exception $e) {
            error_log('Follow Up Boss Property View Tracking Error: ' . $e->getMessage());
        }
    }

    /**
     * Track saved search
     */
    public function track_saved_search($search_data): void {
        try {
            $lead_id = $search_data['lead_id'];
            $criteria = $search_data['criteria'];

            $event_data = [
                'person' => $lead_id,
                'type' => 'note',
                'message' => sprintf(
                    'Saved property search with criteria: %s',
                    json_encode($criteria)
                ),
                'source' => 'Website Saved Search'
            ];

            $this->create_event($event_data);
        } catch (\Exception $e) {
            error_log('Follow Up Boss Saved Search Tracking Error: ' . $e->getMessage());
        }
    }

    /**
     * Track property inquiry
     */
    public function track_property_inquiry($inquiry_data): void {
        try {
            $lead_id = $inquiry_data['lead_id'];
            $property = get_post($inquiry_data['property_id']);

            if (!$property) {
                return;
            }

            $event_data = [
                'person' => $lead_id,
                'type' => 'note',
                'message' => sprintf(
                    'Inquired about property: %s (%s)\nMessage: %s',
                    get_the_title($property),
                    get_permalink($property),
                    $inquiry_data['message']
                ),
                'source' => 'Website Property Inquiry'
            ];

            $this->create_event($event_data);
        } catch (\Exception $e) {
            error_log('Follow Up Boss Property Inquiry Tracking Error: ' . $e->getMessage());
        }
    }

    /**
     * Track open house RSVP
     */
    public function track_open_house_rsvp($rsvp_data): void {
        try {
            $lead_id = $rsvp_data['lead_id'];
            $property = get_post($rsvp_data['property_id']);

            if (!$property) {
                return;
            }

            $event_data = [
                'person' => $lead_id,
                'type' => 'appointment',
                'message' => sprintf(
                    'RSVP for Open House: %s on %s\nNotes: %s',
                    get_the_title($property),
                    $rsvp_data['date'],
                    $rsvp_data['notes'] ?? ''
                ),
                'source' => 'Website Open House RSVP',
                'dueDate' => date('Y-m-d\TH:i:s\Z', strtotime($rsvp_data['date']))
            ];

            $this->create_event($event_data);

            // Add open house tag to lead
            $this->update_lead($lead_id, [
                'system' => [
                    'tags' => ['Open House RSVP']
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Follow Up Boss Open House RSVP Tracking Error: ' . $e->getMessage());
        }
    }

    /**
     * Create event in Follow Up Boss
     */
    private function create_event(array $event_data): array {
        if (!$this->api_key) {
            throw new \Exception('Follow Up Boss API credentials not configured');
        }

        $response = wp_remote_post($this->api_url . 'events', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->api_key . ':'),
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($event_data)
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Follow Up Boss API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (!empty($result['error'])) {
            throw new \Exception('Follow Up Boss API Error: ' . $result['error']);
        }

        return $result;
    }

    /**
     * Handle new lead creation
     */
    public function handle_new_lead($lead_data): void {
        try {
            $data = [
                'person' => [
                    'firstName' => $lead_data['first_name'],
                    'lastName' => $lead_data['last_name'],
                    'emails' => [['value' => $lead_data['email']]],
                    'phones' => !empty($lead_data['phone']) ? [['value' => $lead_data['phone']]] : []
                ],
                'source' => $lead_data['source'] ?? $this->default_source,
                'type' => $lead_data['type'] ?? 'buyer',
                'stage' => $lead_data['stage'] ?? 'new',
                'system' => [
                    'tags' => $lead_data['tags'] ?? []
                ],
                'message' => $lead_data['notes'] ?? ''
            ];

            $this->create_lead($data);
        } catch (\Exception $e) {
            error_log('Follow Up Boss Lead Creation Error: ' . $e->getMessage());
        }
    }
}
