<?php
namespace HappyPlace\Integrations;

class FollowUpBoss_Sync {
    private $api_key;
    private $default_source;

    public function __construct() {
        $integrations = get_option('hph_integrations', []);
        $this->api_key = $integrations['follow_up_boss']['api_key'] ?? '';
        $this->default_source = $integrations['follow_up_boss']['lead_source'] ?? 'Website';
    }

    /**
     * Import records to Follow Up Boss
     */
    public function import_records(string $type, array $records): array {
        if (!$this->api_key) {
            throw new \Exception('Follow Up Boss API credentials not configured');
        }

        $imported_leads = [];

        foreach ($records as $record) {
            try {
                $lead_data = $this->transform_record($type, $record);
                $imported_lead = $this->create_lead($lead_data);
                $imported_leads[] = $imported_lead;
            } catch (\Exception $e) {
                error_log("FUB Import Error: " . $e->getMessage());
            }
        }

        return $imported_leads;
    }

    /**
     * Transform record for Follow Up Boss
     */
    private function transform_record(string $type, array $record): array {
        switch ($type) {
            case 'listings':
                return [
                    'source' => $this->default_source,
                    'contactType' => 'lead',
                    'name' => $record['Title'] ?? '',
                    'email' => $record['Contact Email'] ?? '',
                    'phone' => $record['Contact Phone'] ?? '',
                    'address' => [
                        'street' => $record['Street Address'] ?? '',
                        'city' => $record['City'] ?? '',
                        'state' => $record['State'] ?? '',
                        'zip' => $record['Zip Code'] ?? ''
                    ],
                    'customFields' => [
                        'listing_price' => $record['Price'] ?? '',
                        'listing_type' => $record['Property Type'] ?? ''
                    ]
                ];

            case 'clients':
                return [
                    'source' => $this->default_source,
                    'contactType' => 'client',
                    'name' => $record['Client Name'] ?? '',
                    'email' => $record['Email'] ?? '',
                    'phone' => $record['Phone'] ?? '',
                    'customFields' => [
                        'client_status' => $record['Status'] ?? ''
                    ]
                ];

            default:
                return [];
        }
    }

    /**
     * Create lead in Follow Up Boss
     */
    private function create_lead(array $lead_data): array {
        $url = 'https://api.followupboss.com/v1/leads';

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($lead_data)
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Follow Up Boss API request failed: ' . $response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Retrieve leads from Follow Up Boss
     */
    public function get_leads(array $filters = []): array {
        $url = 'https://api.followupboss.com/v1/leads';

        $response = wp_remote_get(add_query_arg($filters, $url), [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
                'Content-Type] => 'application/json'
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Follow Up Boss API request failed: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Update lead in Follow Up Boss
     */
    public function update_lead(int $lead_id, array $update_data): array {
        $url = "https://api.followupboss.com/v1/leads/{$lead_id}";

        $response = wp_remote_request($url, [
            'method' => 'PATCH',
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($update_data)
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Follow Up Boss API update failed: ' . $response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Sync WordPress user to Follow Up Boss contact
     */
    public function sync_wordpress_user(\WP_User $user): array {
        $lead_data = [
            'source' => $this->default_source,
            'contactType' => 'client',
            'name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => get_user_meta($user->ID, 'phone_number', true),
            'customFields' => [
                'wordpress_user_id' => $user->ID,
                'user_registered' => $user->user_registered
            ]
        ];

        try {
            return $this->create_lead($lead_data);
        } catch (\Exception $e) {
            error_log("FUB User Sync Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Track lead interactions
     */
    public function log_lead_interaction(array $lead_data, string $interaction_type): void {
        $url = 'https://api.followupboss.com/v1/interactions';

        $interaction_data = [
            'leadId' => $lead_data['id'],
            'type' => $interaction_type,
            'details' => json_encode($lead_data),
            'timestamp' => current_time('mysql')
        ];

        wp_remote_post($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($interaction_data)
        ]);
    }

    /**
     * Validate API credentials
     */
    public function validate_credentials(): bool {
        try {
            $response = wp_remote_get('https://api.followupboss.com/v1/me', [
                'headers' => [
                    'Authorization' => "Bearer {$this->api_key}",
                    'Content-Type' => 'application/json'
                ]
            ]);

            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        } catch (\Exception $e) {
            error_log("FUB Credential Validation Error: " . $e->getMessage());
            return false;
        }
    }
}