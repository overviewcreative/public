<?php
namespace HappyPlace\Admin;

// WordPress functions
use function \add_submenu_page;
use function \register_setting;
use function \add_settings_section;
use function \add_settings_field;
use function \add_settings_error;
use function \add_action;
use function \settings_errors;
use function \settings_fields;
use function \do_settings_sections;
use function \submit_button;
use function \get_admin_page_title;
use function \get_option;
use function \update_option;
use function \esc_html;
use function \esc_attr;
use function \wp_create_nonce;
use function \wp_enqueue_style;
use function \wp_enqueue_script;
use function \wp_localize_script;
use function \admin_url;
use function \check_admin_referer;
use function \current_user_can;
use function \wp_send_json_error;
use function \wp_send_json_success;
use function \is_wp_error;
use function \checked;

// Internal classes
use HappyPlace\Integrations\Airtable_Integration;
use HappyPlace\Integrations\FollowUpBoss_Integration;

class Integration_Settings {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_hph_test_integration', [$this, 'test_integration']);
        add_action('wp_ajax_hph_save_integration_settings', [$this, 'save_settings']);
    }

    /**
     * Add settings page to menu
     */
    public function add_menu_page(): void {
        add_submenu_page(
            'happy-place',
            'Integrations',
            'Integrations',
            'manage_options',
            'happy-place-integrations',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting('hph_integrations', 'hph_integrations');

        // Airtable Settings
        add_settings_section(
            'hph_airtable_settings',
            'Airtable Settings',
            [$this, 'render_airtable_section'],
            'happy-place-integrations'
        );

        add_settings_field(
            'airtable_api_key',
            'API Key',
            [$this, 'render_text_field'],
            'happy-place-integrations',
            'hph_airtable_settings',
            [
                'label_for' => 'airtable_api_key',
                'name' => 'hph_integrations[airtable][api_key]'
            ]
        );

        add_settings_field(
            'airtable_base_id',
            'Base ID',
            [$this, 'render_text_field'],
            'happy-place-integrations',
            'hph_airtable_settings',
            [
                'label_for' => 'airtable_base_id',
                'name' => 'hph_integrations[airtable][base_id]'
            ]
        );

        // Follow Up Boss Settings
        add_settings_section(
            'hph_fub_settings',
            'Follow Up Boss Settings',
            [$this, 'render_fub_section'],
            'happy-place-integrations'
        );

        add_settings_field(
            'fub_api_key',
            'API Key',
            [$this, 'render_text_field'],
            'happy-place-integrations',
            'hph_fub_settings',
            [
                'label_for' => 'fub_api_key',
                'name' => 'hph_integrations[follow_up_boss][api_key]'
            ]
        );

        add_settings_field(
            'fub_lead_source',
            'Default Lead Source',
            [$this, 'render_text_field'],
            'happy-place-integrations',
            'hph_fub_settings',
            [
                'label_for' => 'fub_lead_source',
                'name' => 'hph_integrations[follow_up_boss][lead_source]',
                'default' => 'Website'
            ]
        );

        add_settings_field(
            'fub_auto_import',
            'Auto Import Leads',
            [$this, 'render_checkbox_field'],
            'happy-place-integrations',
            'hph_fub_settings',
            [
                'label_for' => 'fub_auto_import',
                'name' => 'hph_integrations[follow_up_boss][auto_import]'
            ]
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'hph_messages',
                'hph_message',
                'Settings Saved',
                'updated'
            );
        }

        settings_errors('hph_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('hph_integrations');
                do_settings_sections('happy-place-integrations');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render Airtable section
     */
    public function render_airtable_section($args): void {
        ?>
        <p>Configure your Airtable integration settings. You can find your API key in your <a href="https://airtable.com/account" target="_blank">Airtable account settings</a>.</p>
        <?php
    }

    /**
     * Render Follow Up Boss section
     */
    public function render_fub_section($args): void {
        ?>
        <p>Configure your Follow Up Boss integration settings. You can find your API key in your <a href="https://www.followupboss.com/member/api/" target="_blank">Follow Up Boss API settings</a>.</p>
        <?php
    }

    /**
     * Render text field
     */
    public function render_text_field($args): void {
        $options = get_option('hph_integrations');
        $name_parts = explode('[', str_replace(']', '', $args['name']));
        $value = $options;
        foreach ($name_parts as $part) {
            $value = $value[$part] ?? '';
        }
        ?>
        <input 
            type="text" 
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="<?php echo esc_attr($args['name']); ?>"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
        >
        <?php
    }

    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args): void {
        $options = get_option('hph_integrations');
        $name_parts = explode('[', str_replace(']', '', $args['name']));
        $value = $options;
        foreach ($name_parts as $part) {
            $value = $value[$part] ?? false;
        }
        ?>
        <input 
            type="checkbox" 
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="<?php echo esc_attr($args['name']); ?>"
            <?php checked($value, true); ?>
        >
        <?php
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook): void {
        if ($hook !== 'happy-place_page_happy-place-integrations') {
            return;
        }

        wp_enqueue_style(
            'hph-admin-integrations',
            HPH_PLUGIN_URL . 'assets/css/admin/integrations.css',
            [],
            HPH_VERSION
        );

        wp_enqueue_script(
            'hph-admin-integrations',
            HPH_PLUGIN_URL . 'assets/js/admin/integrations.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('hph-admin-integrations', 'hphIntegrations', [
            'nonce' => wp_create_nonce('hph-integrations'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Test integration connection
     */
    public function test_integration(): void {
        check_admin_referer('hph-integrations', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $integration = $_POST['integration'] ?? '';
        $settings = $_POST['settings'] ?? [];

        if (!$integration || empty($settings)) {
            wp_send_json_error('Invalid request');
        }

        try {
            switch ($integration) {
                case 'airtable':
                    $airtable = new Airtable_Integration();
                    $result = $airtable->test_connection($settings);
                    break;

                case 'followupboss':
                    $fub = new FollowUpBoss_Integration();
                    $result = $fub->test_connection($settings);
                    break;

                default:
                    wp_send_json_error('Invalid integration');
            }

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }

            wp_send_json_success('Connection successful');
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Save integration settings via AJAX
     */
    public function save_settings(): void {
        check_admin_referer('hph-integrations', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $integration = $_POST['integration'] ?? '';
        $settings = $_POST['settings'] ?? [];

        if (!$integration || empty($settings)) {
            wp_send_json_error('Invalid request');
        }

        $integrations = get_option('hph_integrations', []);
        $integrations[$integration] = $settings;

        if (update_option('hph_integrations', $integrations)) {
            wp_send_json_success('Settings saved successfully');
        } else {
            wp_send_json_error('Failed to save settings');
        }
    }
}
