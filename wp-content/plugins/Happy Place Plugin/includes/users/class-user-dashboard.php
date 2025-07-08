<?php
namespace HappyPlace\Users;

class User_Dashboard_Manager {
    private static ?self $instance = null;

    // Dashboard configurations
    private $dashboard_configs = [
        'hph_client' => [
            'title' => 'Client Dashboard',
            'sections' => [
                'saved_listings' => [
                    'label' => 'Saved Listings',
                    'capability' => 'view_saved_listings'
                ],
                'inquiries' => [
                    'label' => 'My Inquiries',
                    'capability' => 'view_inquiries'
                ],
                'profile' => [
                    'label' => 'Profile Settings',
                    'capability' => 'edit_profile'
                ]
            ]
        ],
        'hph_agent' => [
            'title' => 'Agent Dashboard',
            'sections' => [
                'my_listings' => [
                    'label' => 'My Listings',
                    'capability' => 'edit_listings'
                ],
                'leads' => [
                    'label' => 'Lead Management',
                    'capability' => 'manage_leads'
                ],
                'transactions' => [
                    'label' => 'Transactions',
                    'capability' => 'create_transactions'
                ],
                'analytics' => [
                    'label' => 'Performance Analytics',
                    'capability' => 'view_agent_analytics'
                ]
            ]
        ],
        'hph_broker' => [
            'title' => 'Broker Dashboard',
            'sections' => [
                'agency_listings' => [
                    'label' => 'Agency Listings',
                    'capability' => 'manage_agency_listings'
                ],
                'agent_management' => [
                    'label' => 'Agent Management',
                    'capability' => 'manage_agents'
                ],
                'reports' => [
                    'label' => 'Agency Reports',
                    'capability' => 'view_agency_reports'
                ]
            ]
        ]
    ];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('init', [$this, 'register_dashboard_shortcodes']);
    }

    /**
     * Register dashboard shortcodes
     */
    public function register_dashboard_shortcodes(): void {
        add_shortcode('hph_user_dashboard', [$this, 'render_user_dashboard']);
    }

    /**
     * Render user dashboard
     */
    public function render_user_dashboard($atts = []): string {
        // Ensure user is logged in
        if (!is_user_logged_in()) {
            return $this->render_login_prompt();
        }

        $current_user = wp_get_current_user();
        $user_role = $current_user->roles[0] ?? 'subscriber';

        // Check if role has a configured dashboard
        if (!isset($this->dashboard_configs[$user_role])) {
            return $this->render_default_dashboard($current_user);
        }

        $dashboard_config = $this->dashboard_configs[$user_role];

        ob_start();
        ?>
        <div class="hph-user-dashboard">
            <div class="dashboard-header">
                <h1><?php echo esc_html($dashboard_config['title']); ?></h1>
                <div class="user-info">
                    <img 
                        src="<?php echo esc_url(get_avatar_url($current_user->ID, ['size' => 100])); ?>" 
                        alt="<?php echo esc_attr($current_user->display_name); ?>"
                        class="user-avatar"
                    >
                    <div class="user-details">
                        <h2><?php echo esc_html($current_user->display_name); ?></h2>
                        <p><?php echo esc_html(ucfirst(str_replace('hph_', '', $user_role))); ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-content">
                <?php foreach ($dashboard_config['sections'] as $section_key => $section_info): ?>
                    <?php if (current_user_can($section_info['capability'])): ?>
                        <div class="dashboard-section" id="<?php echo esc_attr($section_key); ?>">
                            <h3><?php echo esc_html($section_info['label']); ?></h3>
                            <?php $this->render_dashboard_section($user_role, $section_key); ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render specific dashboard section
     */
    private function render_dashboard_section(string $user_role, string $section_key): void {
        $current_user = wp_get_current_user();

        switch ($user_role) {
            case 'hph_client':
                $this->render_client_section($section_key, $current_user);
                break;
            case 'hph_agent':
                $this->render_agent_section($section_key, $current_user);
                break;
            case 'hph_broker':
                $this->render_broker_section($section_key, $current_user);
                break;
        }
    }

    /**
     * Render client dashboard sections
     */
    private function render_client_section(string $section_key, \WP_User $user): void {
        switch ($section_key) {
            case 'saved_listings':
                $this->render_saved_listings($user);
                break;
            case 'inquiries':
                $this->render_client_inquiries($user);
                break;
            case 'profile':
                $this->render_user_profile($user);
                break;
        }
    }

    /**
     * Render agent dashboard sections
     */
    private function render_agent_section(string $section_key, \WP_User $user): void {
        switch ($section_key) {
            case 'my_listings':
                $this->render_agent_listings($user);
                break;
            case 'leads':
                $this->render_agent_leads($user);
                break;
            case 'transactions':
                $this->render_agent_transactions($user);
                break;
            case 'analytics':
                $this->render_agent_analytics($user);
                break;
        }
    }

    /**
     * Render broker dashboard sections
     */
    private function render_broker_section(string $section_key, \WP_User $user): void {
        switch ($section_key) {
            case 'agency_listings':
                $this->render_agency_listings($user);
                break;
            case 'agent_management':
                $this->render_agent_management($user);
                break;
            case 'reports':
                $this->render_agency_reports($user);
                break;
        }
    }

    /**
     * Render saved listings for client
     */
    private function render_saved_listings(\WP_User $user): void {
        $saved_listings = get_user_meta($user->ID, 'saved_listings', true);
        
        if (empty($saved_listings)) {
            echo '<p>You have no saved listings.</p>';
            return;
        }

        ?>
        <div class="saved-listings-grid">
            <?php 
            foreach ($saved_listings as $listing_id) {
                $listing = get_post($listing_id);
                if ($listing) {
                    $this->render_listing_card($listing);
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render client inquiries
     */
    private function render_client_inquiries(\WP_User $user): void {
        $inquiries = $this->get_user_inquiries($user->ID);
        
        if (empty($inquiries)) {
            echo '<p>You have no recent inquiries.</p>';
            return;
        }

        ?>
        <table class="inquiries-table">
            <thead>
                <tr>
                    <th>Listing</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inquiries as $inquiry): ?>
                    <tr>
                        <td><?php echo esc_html($inquiry->listing_title); ?></td>
                        <td><?php echo esc_html($inquiry->inquiry_date); ?></td>
                        <td><?php echo esc_html($inquiry->status); ?></td>
                        <td>
                            <a href="#" class="view-inquiry" data-id="<?php echo esc_attr($inquiry->id); ?>">
                                View Details
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render user profile section
     */
    private function render_user_profile(\WP_User $user): void {
        ?>
        <form id="user-profile-form" class="hph-form">
            <input type="hidden" name="action" value="update_user_profile">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
            <?php wp_nonce_field('update_user_profile', 'profile_nonce'); ?>

            <div class="hph-form-group">
                <label for="first_name">First Name</label>
                <input 
                    type="text" 
                    name="first_name" 
                    id="first_name" 
                    value="<?php echo esc_attr($user->first_name); ?>" 
                    class="hph-form-input"
                >
            </div>

            <div class="hph-form-group">
                <label for="last_name">Last Name</label>
                <input 
                    type="text" 
                    name="last_name" 
                    id="last_name" 
                    value="<?php echo esc_attr($user->last_name); ?>" 
                    class="hph-form-input"
                >
            </div>

            <div class="hph-form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    value="<?php echo esc_attr($user->user_email); ?>" 
                    class="hph-form-input"
                >
            </div>

            <div class="hph-form-group">
                <label for="phone">Phone Number</label>
                <input 
                    type="tel" 
                    name="phone" 
                    id="phone" 
                    value="<?php echo esc_attr(get_user_meta($user->ID, 'hph_phone', true)); ?>" 
                    class="hph-form-input"
                >
            </div>

            <div class="hph-form-group">
                <button type="submit" class="hph-btn hph-btn-primary">
                    Update Profile
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Render agent listings
     */
    private function render_agent_listings(\WP_User $user): void {
        $listings = $this->get_agent_listings($user->ID);
        
        if (empty($listings)) {
            echo '<p>You have no active listings.</p>';
            return;
        }

        ?>
        <div class="agent-listings-grid">
            <?php 
            foreach ($listings as $listing) {
                $this->render_listing_card($listing);
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render login prompt
     */
    private function render_login_prompt(): string {
        ob_start();
        ?>
        <div class="login-prompt">
            <h2>Please Log In</h2>
            <p>You must be logged in to view your dashboard.</p>
            <?php 
            // Render login form shortcode
            echo do_shortcode('[hph_login_form]'); 
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render default dashboard for unrecognized roles
     */
    private function render_default_dashboard(\WP_User $user): string {
        ob_start();
        ?>
        <div class="default-dashboard">
            <h1>Welcome, <?php echo esc_html($user->display_name); ?></h1>
            <p>Your dashboard is currently being set up.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a listing card
     */
    private function render_listing_card(\WP_Post $listing): void {
        ?>
        <div class="listing-card">
            <?php 
            $main_photo = get_field('main_photo', $listing->ID);
            if ($main_photo): 
            ?>
                <img 
                    src="<?php echo esc_url($main_photo); ?>" 
                    alt="<?php echo esc_attr($listing->post_title); ?>"
                    class="listing-card-image"
                >
            <?php endif; ?>

            <div class="listing-card-content">
                <h3><?php echo esc_html($listing->post_title); ?></h3>
                <p>
                    <?php 
                    $price = get_field('price', $listing->ID);
                    echo '$' . number_format($price); 
                    ?>
                </p>
                <a 
                    href="<?php echo esc_url(get_permalink($listing->ID)); ?>" 
                    class="hph-btn hph-btn-primary"
                >
                    View Details
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Helper method to get user inquiries
     */
    private function get_user_inquiries(int $user_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_inquiries';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE user_id = %d 
            ORDER BY inquiry_date DESC",
            $user_id
        ));
    }

    /**
     * Helper method to get agent listings
     */
    private function get_agent_listings(int $user_id): array {
        return get_posts([
            'post_type' => 'listing',
            'meta_key' => 'agent',
            'meta_value' => $user_id,
            'posts_per_page' => -1
        ]);
    }

    /**
     * Initialize dashboard-related hooks
     */
    public function init(): void {
        // Register dashboard shortcode
        add_shortcode('hph_user_dashboard', [$this, 'render_user_dashboard']);

        // AJAX handlers for dashboard interactions
        add_action('wp_ajax_update_user_profile', [$this, 'ajax_update_user_profile']);
        add_action('wp_ajax_get_inquiry_details', [$this, 'ajax_get_inquiry_details']);
    }

    /**
     * AJAX handler for profile updates
     */
    public function ajax_update_user_profile(): void {
        // Implementation for profile update
        // (Similar to previous registration validation logic)
    }

    /**
     * AJAX handler for inquiry details
     */
    public function ajax_get_inquiry_details(): void {
        // Fetch and return specific inquiry details
    }
}

// Initialize User Dashboard Manager
$user_dashboard_manager = User_Dashboard_Manager::get_instance();
$user_dashboard_manager->init();