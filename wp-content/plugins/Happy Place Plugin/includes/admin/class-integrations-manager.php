<?php
namespace HappyPlace\Admin;

/**
 * Integrations Manager Class
 * 
 * Handles all third-party service integrations and their settings
 * 
 * @package HappyPlace
 * @subpackage Admin
 */
class Integrations_Manager {
    private static ?self $instance = null;

    // Available integrations
    private $integrations = [
        'google' => [
            'name' => 'Google Integrations',
            'description' => 'Configure Google APIs for maps, analytics, and more',
            'fields' => [
                'maps_api_key' => [
                    'label' => 'Google Maps API Key',
                    'type' => 'text',
                    'description' => 'API key for Google Maps geocoding and display'
                ],
                'analytics_id' => [
                    'label' => 'Google Analytics ID',
                    'type' => 'text',
                    'description' => 'Universal Analytics or GA4 Measurement ID'
                ],
                'tag_manager_id' => [
                    'label' => 'Google Tag Manager ID',
                    'type' => 'text',
                    'description' => 'Container ID for Google Tag Manager'
                ]
            ]
        ],
        'airtable' => [
            'name' => 'Airtable Sync',
            'description' => 'Synchronize data between WordPress and Airtable',
            'fields' => [
                'api_key' => [
                    'label' => 'Airtable API Key',
                    'type' => 'text',
                    'description' => 'API key for Airtable integration'
                ],
                'base_id' => [
                    'label' => 'Airtable Base ID',
                    'type' => 'text',
                    'description' => 'Unique identifier for your Airtable base'
                ],
                'sync_schedules' => [
                    'label' => 'Sync Schedules',
                    'type' => 'multiselect',
                    'options' => [
                        'listings' => 'Listings',
                        'agents' => 'Agents',
                        'communities' => 'Communities'
                    ],
                    'description' => 'Select post types to sync with Airtable'
                ]
            ]
        ],
        'follow_up_boss' => [
            'name' => 'Follow Up Boss',
            'description' => 'Lead management and CRM integration',
            'fields' => [
                'api_key' => [
                    'label' => 'Follow Up Boss API Key',
                    'type' => 'text',
                    'description' => 'API key for Follow Up Boss integration'
                ],
                'lead_source' => [
                    'label' => 'Default Lead Source',
                    'type' => 'text',
                    'description' => 'Default source for leads imported from the website'
                ],
                'auto_import' => [
                    'label' => 'Auto Import Leads',
                    'type' => 'checkbox',
                    'description' => 'Automatically import leads from website forms'
                ]
            ]
        ],
        'dotloop' => [
            'name' => 'DotLoop Integration',
            'description' => 'Document management and transaction tracking',
            'fields' => [
                'api_key' => [
                    'label' => 'DotLoop API Key',
                    'type' => 'text',
                    'description' => 'API key for DotLoop integration'
                ],
                'default_profile_id' => [
                    'label' => 'Default Profile ID',
                    'type' => 'text',
                    'description' => 'Default profile for new transactions'
                ],
                'sync_transactions' => [
                    'label' => 'Sync Transactions',
                    'type' => 'checkbox',
                    'description' => 'Automatically sync transactions with DotLoop'
                ]
            ]
        ],
        'marketing' => [
            'name' => 'Marketing Integrations',
            'description' => 'Email and marketing platform connections',
            'fields' => [
                'mailchimp_api_key' => [
                    'label' => 'Mailchimp API Key',
                    'type' => 'text',
                    'description' => 'API key for Mailchimp integration'
                ],
                'constant_contact_api_key' => [
                    'label' => 'Constant Contact API Key',
                    'type' => 'text',
                    'description' => 'API key for Constant Contact'
                ],
                'default_list_id' => [
                    'label' => 'Default Mailing List',
                    'type' => 'text',
                    'description' => 'Default list for new subscriber imports'
                ]
            ]
        ]
    ];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        \add_action('admin_menu', [$this, 'add_integrations_page']);
        \add_action('admin_init', [$this, 'register_integration_settings']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Add integrations page to admin menu
     */
    public function add_integrations_page(): void {
        \add_menu_page(
            'Happy Place Integrations',
            'HPH Integrations',
            'manage_options',
            'happy-place-integrations',
            [$this, 'render_integrations_page'],
            'dashicons-networking',
            30
        );
    }

    /**
     * Render integrations admin page
     */
    public function render_integrations_page(): void {
        ?>
        <div class="wrap">
            <h1>Happy Place Integrations</h1>
            
            <form method="post" action="options.php">
                <?php
                \settings_errors();
                \settings_fields('happy_place_integrations');
                \do_settings_sections('happy-place-integrations');
                \submit_button('Save Integrations');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register integration settings
     */
    public function register_integration_settings(): void {
        \register_setting(
            'happy_place_integrations', 
            'hph_integrations',
            ['sanitize_callback' => [$this, 'sanitize_integration_settings']]
        );

        foreach ($this->integrations as $integration_key => $integration) {
            \add_settings_section(
                "hph_{$integration_key}_section", 
                $integration['name'], 
                function() use ($integration) {
                    echo '<p>' . \esc_html($integration['description']) . '</p>';
                }, 
                'happy-place-integrations'
            );

            foreach ($integration['fields'] as $field_key => $field) {
                \add_settings_field(
                    "hph_{$integration_key}_{$field_key}",
                    $field['label'],
                    function() use ($integration_key, $field_key, $field) {
                        $this->render_integration_field(
                            $integration_key, 
                            $field_key, 
                            $field
                        );
                    },
                    'happy-place-integrations',
                    "hph_{$integration_key}_section"
                );
            }
        }
    }

    /**
     * Render individual integration field
     */
    private function render_integration_field(
        string $integration_key, 
        string $field_key, 
        array $field
    ): void {
        $option_name = "hph_integrations[{$integration_key}][{$field_key}]";
        $value = $this->get_integration_option($integration_key, $field_key);

        switch ($field['type']) {
            case 'text':
                ?>
                <input 
                    type="text" 
                    name="<?php echo \esc_attr($option_name); ?>" 
                    value="<?php echo \esc_attr($value); ?>" 
                    class="regular-text"
                >
                <?php
                break;
            case 'checkbox':
                ?>
                <input 
                    type="checkbox" 
                    name="<?php echo \esc_attr($option_name); ?>" 
                    value="1" 
                    <?php \checked(1, $value, true); ?>
                >
                <?php
                break;
            case 'multiselect':
                ?>
                <select 
                    name="<?php echo \esc_attr($option_name); ?>[]" 
                    multiple="multiple"
                    class="hph-multiselect"
                >
                    <?php foreach ($field['options'] as $opt_key => $opt_label): ?>
                        <option 
                            value="<?php echo \esc_attr($opt_key); ?>"
                            <?php \selected(\in_array($opt_key, (array)$value), true); ?>
                        >
                            <?php echo \esc_html($opt_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php
                break;
        }

        if (!empty($field['description'])) {
            echo '<p class="description">' . \esc_html($field['description']) . '</p>';
        }
    }

    /**
     * Get integration option value
     */
    private function get_integration_option(
        string $integration_key, 
        string $field_key
    ): mixed {
        $integrations = \get_option('hph_integrations', []);
        return $integrations[$integration_key][$field_key] ?? '';
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook): void {
        if ($hook !== 'toplevel_page_happy-place-integrations') {
            return;
        }

        \wp_enqueue_style(
            'hph-integrations-admin', 
            \plugin_dir_url(__FILE__) . 'assets/css/integrations-admin.css', 
            [], 
            '1.0.0'
        );

        \wp_enqueue_script(
            'hph-integrations-admin', 
            \plugin_dir_url(__FILE__) . 'assets/js/integrations-admin.js', 
            ['jquery'], 
            '1.0.0', 
            true
        );
    }

    /**
     * Validate and sanitize integration settings
     */
    public function sanitize_integration_settings($input): array {
        $output = [];

        foreach ($this->integrations as $integration_key => $integration) {
            foreach ($integration['fields'] as $field_key => $field) {
                switch ($field['type']) {
                    case 'text':
                        $output[$integration_key][$field_key] = 
                            \sanitize_text_field($input[$integration_key][$field_key] ?? '');
                        break;
                    case 'checkbox':
                        $output[$integration_key][$field_key] = 
                            isset($input[$integration_key][$field_key]) ? 1 : 0;
                        break;
                    case 'multiselect':
                        $output[$integration_key][$field_key] = 
                            array_map('\sanitize_text_field', 
                                $input[$integration_key][$field_key] ?? []
                            );
                        break;
                }
            }
        }

        return $output;
    }
}

// Initialize the Integrations Manager
Integrations_Manager::get_instance();
