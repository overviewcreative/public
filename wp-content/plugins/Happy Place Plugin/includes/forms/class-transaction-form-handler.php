<?php
/**
 * Transaction form handler
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Transaction_Form_Handler extends Form_Handler {
    /**
     * Get the form action name
     *
     * @return string
     */
    protected function get_action() {
        return 'submit_transaction';
    }

    /**
     * Handle the form submission
     */
    public function handle_submission() {
        $this->validate_nonce('submit_transaction_nonce', 'submit_transaction_action');

        $data = $this->sanitize_form_data($_POST);

        // Get related post titles
        $property_title = get_the_title($data['transaction_property']);
        $client_name = get_the_title($data['transaction_client']);
        
        // Create post
        $post_data = [
            'post_title' => sprintf(
                __('%s - %s - %s', 'happyplace'),
                ucfirst($data['transaction_type']),
                $property_title,
                $client_name
            ),
            'post_type' => 'transaction',
            'post_status' => 'pending',
        ];

        if (!empty($data['transaction_notes'])) {
            $post_data['post_content'] = $data['transaction_notes'];
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $this->set_error_message(__('Failed to create transaction.', 'happyplace'));
            $this->redirect_back();
        }

        // Save meta data
        update_post_meta($post_id, '_transaction_type', $data['transaction_type']);
        update_post_meta($post_id, '_transaction_property', $data['transaction_property']);
        update_post_meta($post_id, '_transaction_client', $data['transaction_client']);
        update_post_meta($post_id, '_transaction_agent', $data['transaction_agent']);
        update_post_meta($post_id, '_transaction_amount', floatval($data['transaction_amount']));
        update_post_meta($post_id, '_transaction_date', $data['transaction_date']);
        update_post_meta($post_id, '_transaction_status', $data['transaction_status']);

        $this->set_success_message(__('Transaction submitted successfully!', 'happyplace'));
        $this->redirect_back();
    }
}
