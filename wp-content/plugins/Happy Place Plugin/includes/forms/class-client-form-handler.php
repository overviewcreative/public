<?php
/**
 * Client form handler
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Client_Form_Handler extends Form_Handler {
    /**
     * Get the form action name
     *
     * @return string
     */
    protected function get_action() {
        return 'submit_client';
    }

    /**
     * Handle the form submission
     */
    public function handle_submission() {
        $this->validate_nonce('submit_client_nonce', 'submit_client_action');

        $data = $this->sanitize_form_data($_POST);
        
        // Create post
        $post_data = [
            'post_title' => $data['client_first_name'] . ' ' . $data['client_last_name'],
            'post_type' => 'client',
            'post_status' => 'pending',
        ];

        if (!empty($data['client_notes'])) {
            $post_data['post_content'] = $data['client_notes'];
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $this->set_error_message(__('Failed to create client.', 'happyplace'));
            $this->redirect_back();
        }

        // Save meta data
        update_post_meta($post_id, '_client_first_name', $data['client_first_name']);
        update_post_meta($post_id, '_client_last_name', $data['client_last_name']);
        update_post_meta($post_id, '_client_email', $data['client_email']);
        update_post_meta($post_id, '_client_phone', $data['client_phone']);
        update_post_meta($post_id, '_client_type', $data['client_type']);

        if (!empty($data['client_address'])) {
            update_post_meta($post_id, '_client_address', $data['client_address']);
        }
        
        if (!empty($data['client_city'])) {
            update_post_meta($post_id, '_client_city', $data['client_city']);
        }
        
        if (!empty($data['client_state'])) {
            update_post_meta($post_id, '_client_state', $data['client_state']);
        }
        
        if (!empty($data['client_zip'])) {
            update_post_meta($post_id, '_client_zip', $data['client_zip']);
        }

        if (!empty($data['client_agent'])) {
            update_post_meta($post_id, '_client_agent', $data['client_agent']);
        }

        $this->set_success_message(__('Client submitted successfully!', 'happyplace'));
        $this->redirect_back();
    }
}
