<?php
namespace HappyPlace\Integrations;

use function \wp_enqueue_script;
use function \wp_localize_script;
use function \add_action;
use function \add_filter;

class Airtable_Sync {
    private static ?self $instance = null;
    private string $base_url = 'https://api.airtable.com/v0/';
    private string $api_token;
    private array $rate_limiter = [];
    
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $options = get_option('happy_place_options', []);
        $this->api_token = $options['airtable_api_key'] ?? '';
        
        add_action('admin_init', [$this, 'setup_admin']);
        add_action('wp_ajax_sync_airtable', [$this, 'handle_sync_request']);
    }

    public function setup_admin(): void {
        // Only load in admin
        if (!is_admin()) return;

        wp_enqueue_script(
            'happy-place-airtable',
            plugin_dir_url(__FILE__) . '../assets/js/airtable-sync.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('happy-place-airtable', 'happyPlaceAirtable', [
            'nonce' => wp_create_nonce('happy_place_airtable'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }

    public function handle_sync_request(): void {
        check_ajax_referer('happy_place_airtable', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        try {
            $options = get_option('happy_place_options', []);
            $base_id = $options['airtable_base_id'] ?? '';
            $table_name = sanitize_text_field($_POST['table_name'] ?? '');
            
            if (!$base_id || !$table_name) {
                throw new \Exception('Missing required parameters');
            }

            $records = $this->fetch_all_records($base_id, $table_name);
            $result = $this->process_records($records);
            
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function fetch_all_records(string $base_id, string $table_name): array {
        $records = [];
        $offset = null;

        do {
            $params = ['pageSize' => 100];
            if ($offset) {
                $params['offset'] = $offset;
            }

            $response = $this->make_request("$base_id/$table_name", $params);
            $data = json_decode($response, true);

            if (!isset($data['records'])) {
                throw new \Exception('Invalid response from Airtable');
            }

            $records = array_merge($records, $data['records']);
            $offset = $data['offset'] ?? null;

        } while ($offset);

        return $records;
    }

    private function make_request(string $endpoint, array $params = [], string $method = 'GET'): string {
        $this->throttle_request();

        $url = $this->base_url . $endpoint;
        if ($params && $method === 'GET') {
            $url .= '?' . http_build_query($params);
        }

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json'
            ]
        ];

        if ($method !== 'GET' && !empty($params)) {
            $args['body'] = json_encode($params);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code === 429) {
            sleep(30);
            return $this->make_request($endpoint, $params, $method);
        }

        if ($code !== 200) {
            throw new \Exception("Airtable API error: $code");
        }

        return wp_remote_retrieve_body($response);
    }

    private function throttle_request(): void {
        $now = microtime(true);
        $this->rate_limiter = array_filter(
            $this->rate_limiter,
            fn($time) => $now - $time < 1
        );

        if (count($this->rate_limiter) >= 5) {
            sleep(1);
            $this->throttle_request();
            return;
        }

        $this->rate_limiter[] = $now;
    }

    private function process_records(array $records): array {
        // Process records according to your data model
        foreach ($records as $record) {
            // Map Airtable fields to WordPress post types/meta
            $this->create_or_update_post($record);
        }
        
        return [
            'processed' => count($records),
            'success' => true
        ];
    }

    private function create_or_update_post(array $record): void {
        // Map Airtable fields to WordPress post data
        $post_data = [
            'post_title' => $record['fields']['Name'] ?? '',
            'post_type' => 'listing', // Adjust based on your needs
            'post_status' => 'publish'
        ];

        // Insert or update post
        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id)) {
            // Update post meta/ACF fields
            foreach ($record['fields'] as $key => $value) {
                update_field($key, $value, $post_id);
            }
        }
    }
}

// Initialize
Airtable_Sync::get_instance();
