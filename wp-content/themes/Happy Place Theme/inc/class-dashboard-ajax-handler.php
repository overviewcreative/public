<?php

/**
 * Dashboard AJAX Handler
 * 
 * Handles AJAX requests for loading dashboard sections dynamically.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class HPH_Dashboard_AJAX_Handler
{
    /**
     * Initialize the AJAX handler
     */
    public static function init()
    {
        add_action('wp_ajax_hph_load_dashboard_section', [__CLASS__, 'handle_load_section']);
    }

    /**
     * Handle loading a dashboard section
     */
    public static function handle_load_section()
    {
        // Verify nonce
        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'happy-place')
            ]);
        }

        // Get the requested section
        $section = sanitize_key($_POST['section'] ?? '');
        if (empty($section)) {
            wp_send_json_error([
                'message' => __('No section specified.', 'happy-place')
            ]);
        }

        // Get the content for the section
        $content = self::get_section_content($section);
        if (is_wp_error($content)) {
            wp_send_json_error([
                'message' => $content->get_error_message()
            ]);
        }

        wp_send_json_success([
            'content' => $content
        ]);
    }

    /**
     * Get the content for a dashboard section
     *
     * @param string $section The section to load
     * @return string|WP_Error The section content or WP_Error on failure
     */
    private static function get_section_content($section)
    {
        // Start output buffering
        ob_start();

        // Load the appropriate template part based on the section
        $template_path = 'template-parts/dashboard/section-' . $section;
        $found = locate_template($template_path . '.php', true, false);

        if (!$found) {
            return new WP_Error(
                'template_not_found',
                sprintf(__('Template for section "%s" not found.', 'happy-place'), $section)
            );
        }

        // Get the buffered content
        $content = ob_get_clean();

        if (empty($content)) {
            return new WP_Error(
                'empty_content',
                __('No content found for this section.', 'happy-place')
            );
        }

        return $content;
    }

    /**
     * Get the template data for a section
     *
     * @param string $section The section identifier
     * @return array The template data
     */
    public static function get_section_data($section)
    {
        $data = [];

        switch ($section) {
            case 'overview':
                $data = self::get_overview_data();
                break;
            case 'listings':
                $data = self::get_listings_data();
                break;
            case 'leads':
                $data = self::get_leads_data();
                break;
            case 'profile':
                $data = self::get_profile_data();
                break;
            case 'settings':
                $data = self::get_settings_data();
                break;
        }

        return apply_filters('hph_dashboard_section_data', $data, $section);
    }

    /**
     * Get data for the overview section
     *
     * @return array
     */
    private static function get_overview_data()
    {
        // Get overview stats and data
        return [
            'active_listings' => 0, // TODO: Implement
            'pending_leads' => 0,   // TODO: Implement
            'recent_activity' => [], // TODO: Implement
            'notifications' => [],   // TODO: Implement
        ];
    }

    /**
     * Get data for the listings section
     *
     * @return array
     */
    private static function get_listings_data()
    {
        // Get listings data
        return [
            'listings' => [], // TODO: Implement
            'filters' => [],  // TODO: Implement
            'stats' => [],    // TODO: Implement
        ];
    }

    /**
     * Get data for the leads section
     *
     * @return array
     */
    private static function get_leads_data()
    {
        // Get leads data
        return [
            'leads' => [],     // TODO: Implement
            'filters' => [],   // TODO: Implement
            'stats' => [],     // TODO: Implement
        ];
    }

    /**
     * Get data for the profile section
     *
     * @return array
     */
    private static function get_profile_data()
    {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            return [];
        }

        return [
            'user_id' => $user_id,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'avatar' => get_avatar_url($user_id),
            'role' => array_shift($user->roles),
            'meta' => [
                'phone' => get_user_meta($user_id, 'phone', true),
                'title' => get_user_meta($user_id, 'title', true),
                'bio' => get_user_meta($user_id, 'description', true),
                // Add more meta fields as needed
            ],
        ];
    }

    /**
     * Get data for the settings section
     *
     * @return array
     */
    private static function get_settings_data()
    {
        $user_id = get_current_user_id();

        return [
            'notification_settings' => [
                'email' => get_user_meta($user_id, 'notification_email', true),
                'sms' => get_user_meta($user_id, 'notification_sms', true),
                'push' => get_user_meta($user_id, 'notification_push', true),
            ],
            'privacy_settings' => [
                'profile_visibility' => get_user_meta($user_id, 'profile_visibility', true),
                'contact_visibility' => get_user_meta($user_id, 'contact_visibility', true),
            ],
            // Add more settings as needed
        ];
    }
}

// Initialize the AJAX handler
HPH_Dashboard_AJAX_Handler::init();
