<?php
/**
 * Agent form handler
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handler for agent submission form
 */
class Agent_Form_Handler extends Form_Handler {
    /**
     * Get the form action name
     *
     * @return string
     */
    protected function get_action() {
        return 'submit_agent';
    }

    /**
     * Handle form submission
     */
    public function handle_submission() {
        $this->validate_nonce('submit_agent_nonce', 'submit_agent_action');

        $data = [
            'agent_name' => sanitize_text_field($_POST['agent_name'] ?? ''),
            'agent_email' => sanitize_email($_POST['agent_email'] ?? ''),
            'agent_phone' => sanitize_text_field($_POST['agent_phone'] ?? ''),
            'agent_title' => sanitize_text_field($_POST['agent_title'] ?? ''),
            'agent_bio' => wp_kses_post($_POST['agent_bio'] ?? ''),
        ];

        // Create post
        $post_data = [
            'post_title' => $data['agent_name'],
            'post_type' => 'agent',
            'post_status' => 'pending',
            'post_content' => $data['agent_bio'],
        ];

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $this->set_error_message(__('Failed to create agent.', 'happyplace'));
            $this->redirect_back();
            return;
        }

        // Handle file upload
        $photo_id = $this->handle_file_upload('agent_photo');
        if ($photo_id) {
            set_post_thumbnail($post_id, $photo_id);
        }

        // Save meta data
        update_post_meta($post_id, '_agent_email', $data['agent_email']);
        update_post_meta($post_id, '_agent_phone', $data['agent_phone']);
        update_post_meta($post_id, '_agent_title', $data['agent_title']);

        // Save social links if provided
        if (!empty($_POST['agent_social_facebook'])) {
            update_post_meta($post_id, '_agent_facebook', esc_url($_POST['agent_social_facebook']));
        }
        
        if (!empty($_POST['agent_social_linkedin'])) {
            update_post_meta($post_id, '_agent_linkedin', esc_url($_POST['agent_social_linkedin']));
        }

        $this->set_success_message(__('Agent submitted successfully!', 'happyplace'));
        $this->redirect_back();
    }
}
