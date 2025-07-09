<?php
namespace HappyPlace\Admin\Dashboard;

/**
 * Main admin dashboard class
 */
class Admin_Dashboard {
    private static ?self $instance = null;
    private array $subpages = [];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_dashboard_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
        add_filter('admin_body_class', [$this, 'add_dashboard_body_class']);
        add_action('wp_dashboard_setup', [$this, 'remove_default_dashboard_widgets'], 999);
        
        // Initialize subpages
        $this->init_subpages();
    }

    /**
     * Register dashboard pages
     */
    public function register_dashboard_pages(): void {
        // Remove default menu items for cleaner interface
        $this->remove_default_menu_items();

        // Add main dashboard
        add_menu_page(
            __('Dashboard', 'happy-place'),
            __('Happy Place', 'happy-place'),
            'edit_posts',
            'happy-place-dashboard',
            [$this, 'render_main_dashboard'],
            'dashicons-store',
            2
        );

        // Register subpages
        foreach ($this->subpages as $subpage) {
            add_submenu_page(
                'happy-place-dashboard',
                $subpage['title'],
                $subpage['menu_title'],
                $subpage['capability'],
                $subpage['slug'],
                $subpage['callback']
            );
        }
    }

    /**
     * Initialize dashboard subpages
     */
    private function init_subpages(): void {
        $this->subpages = [
            [
                'title' => __('Leads', 'happy-place'),
                'menu_title' => __('Leads', 'happy-place'),
                'capability' => 'edit_posts',
                'slug' => 'happy-place-leads',
                'callback' => [$this, 'render_leads_page']
            ],
            [
                'title' => __('Team', 'happy-place'),
                'menu_title' => __('Team', 'happy-place'),
                'capability' => 'edit_posts',
                'slug' => 'happy-place-team',
                'callback' => [$this, 'render_team_page']
            ],
            [
                'title' => __('Analytics', 'happy-place'),
                'menu_title' => __('Analytics', 'happy-place'),
                'capability' => 'edit_posts',
                'slug' => 'happy-place-analytics',
                'callback' => [$this, 'render_analytics_page']
            ],
            [
                'title' => __('Syncs', 'happy-place'),
                'menu_title' => __('Syncs', 'happy-place'),
                'capability' => 'manage_options',
                'slug' => 'happy-place-syncs',
                'callback' => [$this, 'render_syncs_page']
            ],
            [
                'title' => __('Utilities', 'happy-place'),
                'menu_title' => __('Utilities', 'happy-place'),
                'capability' => 'manage_options',
                'slug' => 'happy-place-utilities',
                'callback' => [$this, 'render_utilities_page']
            ]
        ];
    }

    /**
     * Remove default WordPress dashboard widgets
     */
    public function remove_default_dashboard_widgets(): void {
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
    }

    /**
     * Remove default menu items for cleaner interface
     */
    private function remove_default_menu_items(): void {
        if (!current_user_can('manage_options')) {
            remove_menu_page('index.php');                  // Dashboard
            remove_menu_page('edit.php');                   // Posts
            remove_menu_page('upload.php');                 // Media
            remove_menu_page('edit.php?post_type=page');    // Pages
            remove_menu_page('edit-comments.php');          // Comments
            remove_menu_page('themes.php');                 // Appearance
            remove_menu_page('plugins.php');                // Plugins
            remove_menu_page('users.php');                  // Users
            remove_menu_page('tools.php');                  // Tools
            remove_menu_page('options-general.php');        // Settings
        }
    }

    /**
     * Add custom body class to dashboard pages
     */
    public function add_dashboard_body_class(string $classes): string {
        global $current_screen;
        if (strpos($current_screen->id, 'happy-place') !== false) {
            $classes .= ' happy-place-dashboard';
        }
        return $classes;
    }

    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets(string $hook): void {
        if (strpos($hook, 'happy-place') === false) {
            return;
        }

        wp_enqueue_style(
            'happy-place-dashboard',
            HPH_PLUGIN_URL . 'assets/css/admin/dashboard.css',
            [],
            HPH_VERSION
        );

        wp_enqueue_script(
            'happy-place-dashboard',
            HPH_PLUGIN_URL . 'assets/js/admin/dashboard.js',
            ['jquery', 'wp-util'],
            HPH_VERSION,
            true
        );

        wp_localize_script('happy-place-dashboard', 'hphDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('happy_place_dashboard'),
            'i18n' => [
                'confirmDelete' => __('Are you sure you want to delete this item?', 'happy-place'),
                'error' => __('An error occurred', 'happy-place'),
                'success' => __('Operation completed successfully', 'happy-place')
            ]
        ]);
    }

    /**
     * Render main dashboard page
     */
    public function render_main_dashboard(): void {
        require_once HPH_PLUGIN_DIR . 'includes/admin/dashboard/views/main-dashboard.php';
    }

    /**
     * Render leads page
     */
    public function render_leads_page(): void {
        require_once HPH_PLUGIN_DIR . 'includes/admin/dashboard/views/leads.php';
    }

    /**
     * Render team page
     */
    public function render_team_page(): void {
        require_once HPH_PLUGIN_DIR . 'includes/admin/dashboard/views/team.php';
    }

    /**
     * Render analytics page
     */
    public function render_analytics_page(): void {
        require_once HPH_PLUGIN_DIR . 'includes/admin/dashboard/views/analytics.php';
    }

    /**
     * Render syncs page
     */
    public function render_syncs_page(): void {
        require_once HPH_PLUGIN_DIR . 'includes/admin/dashboard/views/syncs.php';
    }

    /**
     * Render utilities page
     */
    public function render_utilities_page(): void {
        require_once HPH_PLUGIN_DIR . 'includes/admin/dashboard/views/utilities.php';
    }
}
