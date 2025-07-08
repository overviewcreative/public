<?php
namespace HappyPlace\Admin;

/**
 * Class Admin_Menu
 * Handles the plugin's admin menu and settings pages
 */
class Admin_Menu {
    private static ?self $instance = null;
    private string $parent_slug = 'happy-place';

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register admin menus
     */
    public function register_menus(): void {
        // Main Menu
        add_menu_page(
            __('Happy Place', 'happy-place'),
            __('Happy Place', 'happy-place'),
            'manage_options',
            $this->parent_slug,
            [$this, 'render_dashboard_page'],
            'dashicons-admin-home',
            20
        );

        // Dashboard Submenu
        add_submenu_page(
            $this->parent_slug,
            __('Dashboard', 'happy-place'),
            __('Dashboard', 'happy-place'),
            'manage_options',
            $this->parent_slug,
            [$this, 'render_dashboard_page']
        );

        // Integrations Submenu
        add_submenu_page(
            $this->parent_slug,
            __('Integrations', 'happy-place'),
            __('Integrations', 'happy-place'),
            'manage_options',
            $this->parent_slug . '-integrations',
            [$this, 'render_integrations_page']
        );

        // Tools Submenu
        add_submenu_page(
            $this->parent_slug,
            __('Tools', 'happy-place'),
            __('Tools', 'happy-place'),
            'manage_options',
            $this->parent_slug . '-tools',
            [$this, 'render_tools_page']
        );

        // Settings Submenu
        add_submenu_page(
            $this->parent_slug,
            __('Settings', 'happy-place'),
            __('Settings', 'happy-place'),
            'manage_options',
            $this->parent_slug . '-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings(): void {
        register_setting('happy_place_settings', 'happy_place_options');

        // Add settings sections and fields here
        add_settings_section(
            'happy_place_main_section',
            __('Main Settings', 'happy-place'),
            [$this, 'render_section_description'],
            'happy_place_settings'
        );
    }

    /**
     * Render the dashboard page
     */
    public function render_dashboard_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="happy-place-dashboard">
                <div class="dashboard-stats">
                    <h2><?php _e('Overview', 'happy-place'); ?></h2>
                    <!-- Add dashboard widgets and statistics here -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the integrations page
     */
    public function render_integrations_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="happy-place-integrations">
                <h2><?php _e('Available Integrations', 'happy-place'); ?></h2>
                <div class="integration-cards">
                    <?php
                    $integrations = [
                        'airtable' => [
                            'title' => 'Airtable',
                            'description' => __('Sync your listings and contacts with Airtable', 'happy-place'),
                            'status' => 'inactive'
                        ],
                        'followupboss' => [
                            'title' => 'Follow Up Boss',
                            'description' => __('Integrate with Follow Up Boss CRM', 'happy-place'),
                            'status' => 'inactive'
                        ],
                        'dotloop' => [
                            'title' => 'DotLoop',
                            'description' => __('Streamline transaction management with DotLoop', 'happy-place'),
                            'status' => 'inactive'
                        ]
                    ];

                    foreach ($integrations as $key => $integration) {
                        $this->render_integration_card($key, $integration);
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the tools page
     */
    public function render_tools_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="happy-place-tools">
                <div class="tool-cards">
                    <div class="tool-card">
                        <h3><?php _e('Import Listings', 'happy-place'); ?></h3>
                        <p><?php _e('Import listings from CSV or XML files', 'happy-place'); ?></p>
                        <button class="button button-primary"><?php _e('Import', 'happy-place'); ?></button>
                    </div>
                    <div class="tool-card">
                        <h3><?php _e('Export Data', 'happy-place'); ?></h3>
                        <p><?php _e('Export listings, contacts, and other data', 'happy-place'); ?></p>
                        <button class="button button-primary"><?php _e('Export', 'happy-place'); ?></button>
                    </div>
                    <div class="tool-card">
                        <h3><?php _e('Clean Database', 'happy-place'); ?></h3>
                        <p><?php _e('Remove old data and optimize tables', 'happy-place'); ?></p>
                        <button class="button button-primary"><?php _e('Clean', 'happy-place'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the settings page
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('happy_place_settings');
                do_settings_sections('happy_place_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render an integration card
     */
    private function render_integration_card(string $key, array $integration): void {
        ?>
        <div class="integration-card" data-integration="<?php echo esc_attr($key); ?>">
            <h3><?php echo esc_html($integration['title']); ?></h3>
            <p><?php echo esc_html($integration['description']); ?></p>
            <div class="integration-status <?php echo esc_attr($integration['status']); ?>">
                <?php echo esc_html(ucfirst($integration['status'])); ?>
            </div>
            <button class="button button-primary configure-integration" data-integration="<?php echo esc_attr($key); ?>">
                <?php _e('Configure', 'happy-place'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render section description
     */
    public function render_section_description(): void {
        echo '<p>' . esc_html__('Configure your Happy Place plugin settings below.', 'happy-place') . '</p>';
    }
}
