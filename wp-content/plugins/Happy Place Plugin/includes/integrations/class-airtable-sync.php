<?php
namespace HappyPlace\Integrations;

class Airtable_Sync {
    private $api_key;
    private $base_id;

    public function __construct() {
        $integrations = get_option('hph_integrations', []);
        $this->api_key = $integrations['airtable']['api_key'] ?? '';
        $this->base_id = $integrations['airtable']['base_id'] ?? '';
    }

    /**
     * Fetch records from Airtable
     */
    public function fetch_records(string $base_id, string $table_name): array {
        if (!$this->api_key || !$base_id) {
            throw new \Exception('Airtable API credentials not configured');
        }

        $url = "https://api.airtable.com/v0/{$base_id}/{$table_name}";

        $response = wp_remote_get($url, [
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

        return $this->transform_records($data['records'] ?? []);
    }

    /**
     * Transform Airtable records
     */
    private function transform_records(array $records): array {
        $transformed = [];

        foreach ($records as $record) {
            $transformed[] = $record['fields'] ?? [];
        }

        return $transformed;
    }

    /**
     * Create or update records in Airtable
     */
    public function create_or_update_record(
        string $base_id, 
        string $table_name, 
        array $record_data
    ): array {
        $url = "https://api.airtable.com/v0/{$base_id}/{$table_name}";

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => "Bearer {$this->api_key}",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'records' => [
                    [
                        'fields' => $record_data
                    ]
                ]
            ])
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Airtable API request failed: ' . $response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}