<?php
/**
 * Agent Dashboard Functionality
 *
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

class HP_Agent_Dashboard {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_agent_role'));
        add_action('wp_ajax_hph_load_dashboard_section', array($this, 'load_dashboard_section'));
        add_action('wp_ajax_hph_update_agent_profile', array($this, 'update_agent_profile'));
        add_action('wp_ajax_hph_get_listing_stats', array($this, 'get_listing_stats'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
    }

    /**
     * Register agent role and capabilities
     */
    public function register_agent_role() {
        add_role('agent', 'Agent', array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'upload_files' => true,
            'publish_posts' => true,
            'edit_published_posts' => true,
            'delete_published_posts' => true,
            'edit_property' => true,
            'edit_properties' => true,
            'publish_properties' => true,
            'delete_properties' => true,
            'manage_open_houses' => true,
        ));
    }

    /**
     * Get agent's active listings count
     */
    public function get_agent_active_listings_count($agent_id) {
        $args = array(
            'post_type' => 'property',
            'post_status' => 'publish',
            'author' => $agent_id,
            'meta_query' => array(
                array(
                    'key' => 'property_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        );

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get agent's upcoming open houses count
     */
    public function get_agent_upcoming_open_houses_count($agent_id) {
        $count = 0;
        $listings = get_posts(array(
            'post_type' => 'property',
            'author' => $agent_id,
            'posts_per_page' => -1
        ));

        foreach ($listings as $listing) {
            if ($open_houses = get_field('property_open_houses', $listing->ID)) {
                foreach ($open_houses as $open_house) {
                    if (strtotime($open_house['date']) > time()) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Get agent's monthly listing views
     */
    public function get_agent_monthly_views($agent_id) {
        global $wpdb;
        
        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');
        
        $views = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(meta_value) 
            FROM $wpdb->postmeta pm
            JOIN $wpdb->posts p ON p.ID = pm.post_id
            WHERE p.post_author = %d 
            AND p.post_type = 'property'
            AND pm.meta_key = 'property_views'
            AND pm.meta_value > 0
            AND p.post_date BETWEEN %s AND %s",
            $agent_id,
            $month_start,
            $month_end
        ));

        return intval($views);
    }

    /**
     * Get agent's new inquiries count
     */
    public function get_agent_new_inquiries_count($agent_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->prefix}hph_inquiries
            WHERE agent_id = %d 
            AND status = 'new'",
            $agent_id
        ));

        return intval($count);
    }

    /**
     * Get agent's recent activities
     */
    public function get_agent_recent_activities($agent_id) {
        $activities = array();
        
        // Get recent listings
        $recent_listings = get_posts(array(
            'post_type' => 'property',
            'author' => $agent_id,
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        foreach ($recent_listings as $listing) {
            $activities[] = array(
                'type' => 'listing',
                'icon' => 'fas fa-home',
                'description' => sprintf('Added new listing: %s', get_the_title($listing->ID)),
                'time' => human_time_diff(get_post_time('U', false, $listing->ID), current_time('timestamp')) . ' ago'
            );
        }

        // Get recent inquiries
        $recent_inquiries = $this->get_recent_inquiries($agent_id);
        foreach ($recent_inquiries as $inquiry) {
            $activities[] = array(
                'type' => 'inquiry',
                'icon' => 'fas fa-envelope',
                'description' => sprintf('New inquiry for %s', get_the_title($inquiry->property_id)),
                'time' => human_time_diff(strtotime($inquiry->created_at), current_time('timestamp')) . ' ago'
            );
        }

        // Sort by time
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Load dashboard section via AJAX
     */
    public function load_dashboard_section() {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');

        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : '';
        if (!$section) {
            wp_send_json_error('Invalid section');
        }

        ob_start();
        include HP_PLUGIN_DIR . 'templates/dashboard/' . $section . '.php';
        $content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $content
        ));
    }

    /**
     * Update agent profile
     */
    public function update_agent_profile() {
        check_ajax_referer('hph_profile_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }

        $fields = array(
            'name' => sanitize_text_field($_POST['name']),
            'title' => sanitize_text_field($_POST['title']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'bio' => wp_kses_post($_POST['bio']),
            'social' => array(
                'facebook' => esc_url_raw($_POST['facebook']),
                'twitter' => esc_url_raw($_POST['twitter']),
                'linkedin' => esc_url_raw($_POST['linkedin']),
                'instagram' => esc_url_raw($_POST['instagram'])
            )
        );

        update_field('agent_details', $fields, 'user_' . $user_id);

        // Handle photo upload
        if (!empty($_FILES['photo'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('photo', 0);
            if (!is_wp_error($attachment_id)) {
                update_field('agent_photo', $attachment_id, 'user_' . $user_id);
            }
        }

        wp_send_json_success('Profile updated successfully');
    }

    /**
     * Get listing statistics
     */
    public function get_listing_stats() {
        check_ajax_referer('hph_stats_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not logged in');
        }

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';
        $stats = $this->calculate_listing_stats($user_id, $period);

        wp_send_json_success($stats);
    }

    /**
     * Calculate listing statistics
     */
    private function calculate_listing_stats($agent_id, $period) {
        // Implementation will vary based on how you track statistics
        return array(
            'views' => array(
                'total' => $this->get_agent_monthly_views($agent_id),
                'chart_data' => array(/* Daily view counts */)
            ),
            'inquiries' => array(
                'total' => $this->get_agent_new_inquiries_count($agent_id),
                'chart_data' => array(/* Daily inquiry counts */)
            ),
            'listings' => array(
                'active' => $this->get_agent_active_listings_count($agent_id),
                'total' => wp_count_posts('property')->publish
            )
        );
    }

    /**
     * Get recent inquiries
     */
    private function get_recent_inquiries($agent_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hph_inquiries 
            WHERE agent_id = %d 
            ORDER BY created_at DESC 
            LIMIT 5",
            $agent_id
        ));
    }

    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets() {
        if (!is_page_template('templates/agent-dashboard.php')) {
            return;
        }

        wp_enqueue_style(
            'hph-dashboard',
            HP_PLUGIN_URL . 'assets/css/dashboard.css',
            array(),
            HP_VERSION
        );

        wp_enqueue_script(
            'hph-dashboard',
            HP_PLUGIN_URL . 'assets/js/dashboard.js',
            array('jquery', 'wp-util'),
            HP_VERSION,
            true
        );

        wp_localize_script('hph-dashboard', 'hphDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_dashboard_nonce'),
            'profileNonce' => wp_create_nonce('hph_profile_nonce'),
            'statsNonce' => wp_create_nonce('hph_stats_nonce')
        ));
    }
}
