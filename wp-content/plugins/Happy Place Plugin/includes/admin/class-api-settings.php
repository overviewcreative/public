<?php
/**
 * API Settings Admin Page
 * 
 * File: includes/admin/class-api-settings.php
 */

namespace HappyPlace\Admin;

use HappyPlace\Integrations\Integrations_Manager;

class API_Settings {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_hph_test_integration', [$this, 'test_integration']);
        add_action('wp_ajax_hph_sync_integration', [$this, 'manual_sync']);
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page(): void {
        add_submenu_page(
            'happy-place',
            'API Integrations',
            'API Settings',
            'manage_options',
            'hph-api-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting('hph_api_credentials', 'hph_api_credentials', [
            'sanitize_callback' => [$this, 'sanitize_credentials']
        ]);

        register_setting('hph_sync_settings', 'hph_sync_settings', [
            'sanitize_callback' => [$this, 'sanitize_sync_settings']
        ]);

        // Google APIs Section
        add_settings_section(
            'hph_google_apis',
            'Google Services',
            [$this, 'render_google_section'],
            'hph-api-settings'
        );

        // Airtable Section
        add_settings_section(
            'hph_airtable',
            'Airtable Integration',
            [$this, 'render_airtable_section'],
            'hph-api-settings'
        );

        // Follow Up Boss Section
        add_settings_section(
            'hph_followupboss',
            'Follow Up Boss CRM',
            [$this, 'render_followupboss_section'],
            'hph-api-settings'
        );

        // Mailchimp Section
        add_settings_section(
            'hph_mailchimp',
            'Mailchimp Marketing',
            [$this, 'render_mailchimp_section'],
            'hph-api-settings'
        );

        // Sync Settings Section
        add_settings_section(
            'hph_sync_settings',
            'Sync Settings',
            [$this, 'render_sync_section'],
            'hph-api-settings'
        );

        $this->add_all_fields();
    }

    /**
     * Add all setting fields
     */
    private function add_all_fields(): void {
        $fields = [
            // Google APIs
            'google_maps_api_key' => [
                'title' => 'Google Maps API Key',
                'section' => 'hph_google_apis',
                'type' => 'password',
                'description' => 'Required for displaying maps and geocoding addresses'
            ],
            'google_places_api_key' => [
                'title' => 'Google Places API Key',
                'section' => 'hph_google_apis',
                'type' => 'password',
                'description' => 'Required for local business data and reviews'
            ],
            'google_geocoding_api_key' => [
                'title' => 'Google Geocoding API Key',
                'section' => 'hph_google_apis',
                'type' => 'password',
                'description' => 'Required for converting addresses to coordinates'
            ],

            // Airtable
            'airtable_api_key' => [
                'title' => 'Airtable API Key',
                'section' => 'hph_airtable',
                'type' => 'password',
                'description' => 'Get from your Airtable account settings'
            ],
            'airtable_base_id' => [
                'title' => 'Airtable Base ID',
                'section' => 'hph_airtable',
                'type' => 'text',
                'description' => 'Base ID for your Airtable workspace'
            ],

            // Follow Up Boss
            'followupboss_api_key' => [
                'title' => 'Follow Up Boss API Key',
                'section' => 'hph_followupboss',
                'type' => 'password',
                'description' => 'Get from your Follow Up Boss settings'
            ],
            'followupboss_default_source' => [
                'title' => 'Default Lead Source',
                'section' => 'hph_followupboss',
                'type' => 'text',
                'description' => 'Default source for website leads (e.g., "Website")',
                'default' => 'Website'
            ],

            // Mailchimp
            'mailchimp_api_key' => [
                'title' => 'Mailchimp API Key',
                'section' => 'hph_mailchimp',
                'type' => 'password',
                'description' => 'Get from your Mailchimp account'
            ],
            'mailchimp_server_prefix' => [
                'title' => 'Server Prefix',
                'section' => 'hph_mailchimp',
                'type' => 'text',
                'description' => 'Server prefix from your API key (e.g., us1, us2)'
            ],
            'mailchimp_list_id' => [
                'title' => 'Default List ID',
                'section' => 'hph_mailchimp',
                'type' => 'text',
                'description' => 'Default mailing list for new subscribers'
            ]
        ];

        foreach ($fields as $field_id => $field) {
            add_settings_field(
                $field_id,
                $field['title'],
                [$this, 'render_field'],
                'hph-api-settings',
                $field['section'],
                array_merge($field, ['field_id' => $field_id])
            );
        }
    }

    /**
     * Render the settings page
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="hph-api-settings-container">
                <div class="hph-settings-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('hph_api_credentials');
                        settings_fields('hph_sync_settings');
                        do_settings_sections('hph-api-settings');
                        submit_button('Save API Settings');
                        ?>
                    </form>
                </div>

                <div class="hph-settings-sidebar">
                    <div class="hph-integration-status">
                        <h3>Integration Status</h3>
                        <div id="integration-status-list">
                            <div class="integration-item" data-integration="google">
                                <span class="integration-name">Google Services</span>
                                <span class="integration-status unknown">Unknown</span>
                                <button class="button test-integration" data-integration="google">Test</button>
                            </div>
                            <div class="integration-item" data-integration="airtable">
                                <span class="integration-name">Airtable</span>
                                <span class="integration-status unknown">Unknown</span>
                                <button class="button test-integration" data-integration="airtable">Test</button>
                            </div>
                            <div class="integration-item" data-integration="followupboss">
                                <span class="integration-name">Follow Up Boss</span>
                                <span class="integration-status unknown">Unknown</span>
                                <button class="button test-integration" data-integration="followupboss">Test</button>
                            </div>
                            <div class="integration-item" data-integration="mailchimp">
                                <span class="integration-name">Mailchimp</span>
                                <span class="integration-status unknown">Unknown</span>
                                <button class="button test-integration" data-integration="mailchimp">Test</button>
                            </div>
                        </div>
                    </div>

                    <div class="hph-sync-actions">
                        <h3>Manual Sync</h3>
                        <p>Force synchronization with external services:</p>
                        <button class="button button-secondary sync-integration" data-sync="airtable">
                            Sync Airtable Now
                        </button>
                        <button class="button button-secondary sync-integration" data-sync="followupboss">
                            Sync Follow Up Boss Now
                        </button>
                        <button class="button button-secondary sync-integration" data-sync="google_places">
                            Update Google Places Data
                        </button>
                    </div>

                    <div class="hph-api-help">
                        <h3>Getting API Keys</h3>
                        <ul>
                            <li><a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></li>
                            <li><a href="https://airtable.com/developers/web/api/introduction" target="_blank">Airtable API</a></li>
                            <li><a href="https://www.followupboss.com/api/" target="_blank">Follow Up Boss API</a></li>
                            <li><a href="https://mailchimp.com/developer/marketing/guides/quick-start/" target="_blank">Mailchimp API</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .hph-api-settings-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .hph-settings-main {
            flex: 2;
        }
        .hph-settings-sidebar {
            flex: 1;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        .integration-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .integration-status.connected { color: #46b450; }
        .integration-status.error { color: #dc3232; }
        .integration-status.unknown { color: #666; }
        .hph-sync-actions button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
        .hph-api-help ul {
            list-style: none;
            padding: 0;
        }
        .hph-api-help li {
            margin-bottom: 8px;
        }
        .hph-api-help a {
            text-decoration: none;
        }
        </style>
        <?php
    }

    /**
     * Render individual field
     */
    public function render_field($args): void {
        $credentials = get_option('hph_api_credentials', []);
        $value = $credentials[$args['field_id']] ?? ($args['default'] ?? '');
        $field_id = $args['field_id'];
        $type = $args['type'] ?? 'text';

        echo '<div class="hph-field-container">';
        
        if ($type === 'password') {
            echo '<input type="password" id="' . esc_attr($field_id) . '" name="hph_api_credentials[' . esc_attr($field_id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        } else {
            echo '<input type="text" id="' . esc_attr($field_id) . '" name="hph_api_credentials[' . esc_attr($field_id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        }
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        
        echo '</div>';
    }

    /**
     * Render section descriptions
     */
    public function render_google_section(): void {
        echo '<p>Configure Google APIs for maps, geocoding, and places data.</p>';
    }

    public function render_airtable_section(): void {
        echo '<p>Sync your listings, agents, and leads with Airtable.</p>';
    }

    public function render_followupboss_section(): void {
        echo '<p>Integrate with Follow Up Boss CRM for lead management.</p>';
    }

    public function render_mailchimp_section(): void {
        echo '<p>Connect with Mailchimp for email marketing campaigns.</p>';
    }

    public function render_sync_section(): void {
        echo '<p>Configure how often data syncs with external services.</p>';
        
        $sync_settings = get_option('hph_sync_settings', []);
        
        $frequencies = [
            'hourly' => 'Every Hour',
            'twicedaily' => 'Twice Daily',
            'daily' => 'Daily',
            'weekly' => 'Weekly'
        ];

        $services = [
            'airtable' => 'Airtable Sync',
            'followupboss' => 'Follow Up Boss Sync',
            'google_places' => 'Google Places Update'
        ];

        echo '<table class="form-table">';
        foreach ($services as $service => $label) {
            $current = $sync_settings[$service . '_frequency'] ?? 'daily';
            echo '<tr>';
            echo '<th scope="row">' . esc_html($label) . '</th>';
            echo '<td>';
            echo '<select name="hph_sync_settings[' . esc_attr($service) . '_frequency]">';
            foreach ($frequencies as $freq => $freq_label) {
                echo '<option value="' . esc_attr($freq) . '"' . selected($current, $freq, false) . '>' . esc_html($freq_label) . '</option>';
            }
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    /**
     * Sanitize credentials
     */
    public function sanitize_credentials($input): array {
        $sanitized = [];
        
        if (!is_array($input)) {
            return $sanitized;
        }

        foreach ($input as $key => $value) {
            $sanitized_key = sanitize_key($key);
            $sanitized[$sanitized_key] = sanitize_text_field($value);
        }

        return $sanitized;
    }

    /**
     * Sanitize sync settings
     */
    public function sanitize_sync_settings($input): array {
        $sanitized = [];
        $valid_frequencies = ['hourly', 'twicedaily', 'daily', 'weekly'];
        
        if (!is_array($input)) {
            return $sanitized;
        }

        foreach ($input as $key => $value) {
            if (in_array($value, $valid_frequencies)) {
                $sanitized[sanitize_key($key)] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook): void {
        if ($hook !== 'happy-place_page_hph-api-settings') {
            return;
        }

        wp_enqueue_script(
            'hph-api-settings',
            HPH_PLUGIN_URL . 'assets/js/admin/api-settings.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('hph-api-settings', 'hphApiSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_api_settings'),
            'strings' => [
                'testing' => 'Testing...',
                'syncing' => 'Syncing...',
                'connected' => 'Connected',
                'error' => 'Error',
                'success' => 'Success'
            ]
        ]);
    }

    /**
     * Test integration via AJAX
     */
    public function test_integration(): void {
        check_ajax_referer('hph_api_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $integration = sanitize_text_field($_POST['integration']);
        $integrations_manager = Integrations_Manager::get_instance();

        try {
            switch ($integration) {
                case 'google':
                    $result = $integrations_manager->geocode_address('123 Main St, Wilmington, DE');
                    $success = !empty($result);
                    break;

                case 'airtable':
                    $success = $integrations_manager->test_airtable_connection();
                    break;

                case 'followupboss':
                    $success = $integrations_manager->test_followupboss_connection();
                    break;

                case 'mailchimp':
                    $success = $integrations_manager->test_mailchimp_connection();
                    break;

                default:
                    wp_send_json_error('Invalid integration');
            }

            if ($success) {
                wp_send_json_success('Connection successful');
            } else {
                wp_send_json_error('Connection failed');
            }
        } catch (\Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Manual sync via AJAX
     */
    public function manual_sync(): void {
        check_ajax_referer('hph_api_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $sync_type = sanitize_text_field($_POST['sync']);
        $integrations_manager = Integrations_Manager::get_instance();

        try {
            switch ($sync_type) {
                case 'airtable':
                    $integrations_manager->sync_airtable_data();
                    break;

                case 'followupboss':
                    $integrations_manager->sync_followupboss_data();
                    break;

                case 'google_places':
                    $integrations_manager->sync_google_places_data();
                    break;

                default:
                    wp_send_json_error('Invalid sync type');
            }

            wp_send_json_success('Sync completed successfully');
        } catch (\Exception $e) {
            wp_send_json_error('Sync failed: ' . $e->getMessage());
        }
    }
}