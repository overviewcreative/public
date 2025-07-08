<?php
namespace HappyPlace\Users;

class User_Roles_Manager {
    private static ?self $instance = null;

    // Custom user roles
    private $custom_roles = [
        'hph_client' => [
            'name' => 'Client',
            'capabilities' => [
                'read' => true,
                'view_listings' => true,
                'save_favorites' => true,
                'submit_inquiries' => true,
            ]
        ],
        'hph_agent' => [
            'name' => 'Real Estate Agent',
            'capabilities' => [
                'read' => true,
                'edit_listings' => true,
                'publish_listings' => true,
                'manage_agent_profile' => true,
                'view_client_info' => true,
                'create_transactions' => true,
            ]
        ],
        'hph_broker' => [
            'name' => 'Broker',
            'capabilities' => [
                'read' => true,
                'edit_listings' => true,
                'publish_listings' => true,
                'manage_agents' => true,
                'view_all_transactions' => true,
                'manage_agency_settings' => true,
            ]
        ]
    ];

    // Capability mappings
    private $capability_groups = [
        'listings' => [
            'view_listings',
            'edit_listings',
            'publish_listings',
            'delete_listings'
        ],
        'agents' => [
            'view_agents',
            'edit_agents',
            'manage_agents'
        ],
        'transactions' => [
            'view_transactions',
            'create_transactions',
            'edit_transactions'
        ],
        'clients' => [
            'view_client_info',
            'manage_client_data'
        ]
    ];

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('init', [$this, 'register_custom_roles']);
        add_action('user_register', [$this, 'assign_default_role']);
        add_action('wp_ajax_update_user_role', [$this, 'ajax_update_user_role']);
        add_filter('user_has_cap', [$this, 'filter_user_capabilities'], 10, 3);
    }

    /**
     * Register custom user roles
     */
    public function register_custom_roles(): void {
        foreach ($this->custom_roles as $role_key => $role_info) {
            if (!get_role($role_key)) {
                add_role(
                    $role_key, 
                    $role_info['name'], 
                    $role_info['capabilities']
                );
            }
        }
    }

    /**
     * Assign default role to new users
     */
    public function assign_default_role($user_id): void {
        $user = get_userdata($user_id);
        
        // Default role assignment logic
        if (in_array('customer', $user->roles)) {
            $user->set_role('hph_client');
        }
    }

    /**
     * AJAX handler for role updates
     */
    public function ajax_update_user_role(): void {
        // Verify nonce and user permissions
        check_ajax_referer('hph_user_role_update', 'security');
        
        // Check if current user has permission to change roles
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_id = intval($_POST['user_id']);
        $new_role = sanitize_text_field($_POST['new_role']);

        // Validate role
        if (!array_key_exists($new_role, $this->custom_roles)) {
            wp_send_json_error('Invalid role');
        }

        // Update user role
        $user = get_user_by('ID', $user_id);
        $user->set_role($new_role);

        wp_send_json_success([
            'message' => 'User role updated successfully',
            'new_role' => $new_role
        ]);
    }

    /**
     * Custom capability filtering
     */
    public function filter_user_capabilities($capabilities, $requested_caps, $args): array {
        $user = wp_get_current_user();

        // Custom capability checks
        foreach ($requested_caps as $cap) {
            // Agent-specific capabilities
            if (in_array('hph_agent', $user->roles)) {
                if (strpos($cap, 'edit_listing') !== false) {
                    $capabilities[$cap] = true;
                }
            }

            // Broker-specific capabilities
            if (in_array('hph_broker', $user->roles)) {
                $capabilities[$cap] = true;
            }
        }

        return $capabilities;
    }

    /**
     * Get available roles for user assignment
     */
    public function get_assignable_roles(): array {
        $roles = [];
        foreach ($this->custom_roles as $role_key => $role_info) {
            $roles[$role_key] = $role_info['name'];
        }
        return $roles;
    }

    /**
     * Create user profile extension
     */
    public function extend_user_profile($user): void {
        ?>
        <h3>Happy Place Real Estate Profile</h3>
        <table class="form-table">
            <tr>
                <th><label for="hph_phone">Phone Number</label></th>
                <td>
                    <input 
                        type="tel" 
                        name="hph_phone" 
                        id="hph_phone" 
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'hph_phone', true)); ?>" 
                        class="regular-text"
                    >
                </td>
            </tr>
            <?php if (in_array('hph_agent', $user->roles)): ?>
                <tr>
                    <th><label for="hph_license_number">License Number</label></th>
                    <td>
                        <input 
                            type="text" 
                            name="hph_license_number" 
                            id="hph_license_number" 
                            value="<?php echo esc_attr(get_user_meta($user->ID, 'hph_license_number', true)); ?>" 
                            class="regular-text"
                        >
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Save additional user profile fields
     */
    public function save_user_profile($user_id): void {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        // Save phone number
        if (isset($_POST['hph_phone'])) {
            update_user_meta(
                $user_id, 
                'hph_phone', 
                sanitize_text_field($_POST['hph_phone'])
            );
        }

        // Save license number for agents
        if (isset($_POST['hph_license_number'])) {
            update_user_meta(
                $user_id, 
                'hph_license_number', 
                sanitize_text_field($_POST['hph_license_number'])
            );
        }
    }

    /**
     * Initialize hooks for user profile extensions
     */
    public function init_user_profile_hooks(): void {
        add_action('show_user_profile', [$this, 'extend_user_profile']);
        add_action('edit_user_profile', [$this, 'extend_user_profile']);
        add_action('personal_options_update', [$this, 'save_user_profile']);
        add_action('edit_user_profile_update', [$this, 'save_user_profile']);
    }

    /**
     * Synchronize user with CRM
     */
    public function sync_user_to_crm(\WP_User $user): void {
        // Placeholder for CRM sync logic
        $crm_data = [
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->roles[0] ?? 'client',
            'phone' => get_user_meta($user->ID, 'hph_phone', true)
        ];

        // Implement CRM sync (Follow Up Boss, etc.)
        do_action('hph_user_crm_sync', $user, $crm_data);
    }

    /**
     * Get user dashboard based on role
     */
    public function get_user_dashboard($user = null): string {
        if (!$user) {
            $user = wp_get_current_user();
        }

        // Dashboard routing based on user role
        switch (true) {
            case in_array('hph_broker', $user->roles):
                return 'broker-dashboard';
            case in_array('hph_agent', $user->roles):
                return 'agent-dashboard';
            case in_array('hph_client', $user->roles):
                return 'client-dashboard';
            default:
                return 'default-dashboard';
        }
    }
}

// Initialize User Roles Manager
$user_roles_manager = User_Roles_Manager::get_instance();
$user_roles_manager->init_user_profile_hooks();