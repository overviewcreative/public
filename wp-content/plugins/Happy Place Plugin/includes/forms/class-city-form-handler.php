<?php
/**
 * City form handler
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class City_Form_Handler extends Form_Handler {
    /**
     * Get the form action name
     *
     * @return string
     */
    protected function get_action() {
        return 'submit_city';
    }

    /**
     * Handle the form submission
     */
    public function handle_submission() {
        $this->validate_nonce('submit_city_nonce', 'submit_city_action');

        $data = $this->sanitize_form_data($_POST);
        
        // Create post
        $post_data = [
            'post_title' => $data['city_name'],
            'post_type' => 'city',
            'post_status' => 'pending',
        ];

        if (!empty($data['city_description'])) {
            $post_data['post_content'] = $data['city_description'];
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $this->set_error_message(__('Failed to create city.', 'happyplace'));
            $this->redirect_back();
        }

        // Handle featured image upload
        $featured_image_id = $this->handle_file_upload('city_featured_image');
        if ($featured_image_id) {
            set_post_thumbnail($post_id, $featured_image_id);
        }

        // Save meta data
        update_post_meta($post_id, '_city_state', $data['city_state']);
        
        if (!empty($data['city_population'])) {
            update_post_meta($post_id, '_city_population', intval($data['city_population']));
        }

        $this->set_success_message(__('City submitted successfully!', 'happyplace'));
        $this->redirect_back();
    }
}
