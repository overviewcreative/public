<?php
/**
 * Open house form handler
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Open_House_Form_Handler extends Form_Handler {
    /**
     * Get the form action name
     *
     * @return string
     */
    protected function get_action() {
        return 'submit_open_house';
    }

    /**
     * Handle the form submission
     */
    public function handle_submission() {
        $this->validate_nonce('submit_open_house_nonce', 'submit_open_house_action');

        $data = $this->sanitize_form_data($_POST);

        // Get the listing title for the open house post title
        $listing_title = get_the_title($data['open_house_listing']);
        
        // Create post
        $post_data = [
            'post_title' => sprintf(
                __('Open House: %s - %s', 'happyplace'),
                $listing_title,
                $data['open_house_date']
            ),
            'post_type' => 'open_house',
            'post_status' => 'pending',
        ];

        if (!empty($data['open_house_notes'])) {
            $post_data['post_content'] = $data['open_house_notes'];
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $this->set_error_message(__('Failed to create open house.', 'happyplace'));
            $this->redirect_back();
        }

        // Save meta data
        update_post_meta($post_id, '_open_house_listing', $data['open_house_listing']);
        update_post_meta($post_id, '_open_house_date', $data['open_house_date']);
        update_post_meta($post_id, '_open_house_start_time', $data['open_house_start_time']);
        update_post_meta($post_id, '_open_house_end_time', $data['open_house_end_time']);
        update_post_meta($post_id, '_open_house_agent', $data['open_house_agent']);

        $this->set_success_message(__('Open house submitted successfully!', 'happyplace'));
        $this->redirect_back();
    }
}
