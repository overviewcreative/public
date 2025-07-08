<?php
/**
 * Community form handler
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Community_Form_Handler extends Form_Handler {
    /**
     * Get the form action name
     *
     * @return string
     */
    protected function get_action() {
        return 'submit_community';
    }

    /**
     * Handle the form submission
     */
    public function handle_submission() {
        $this->validate_nonce('submit_community_nonce', 'submit_community_action');

        $data = $this->sanitize_form_data($_POST);
        
        // Create post
        $post_data = [
            'post_title' => $data['community_name'],
            'post_type' => 'community',
            'post_status' => 'pending',
        ];

        if (!empty($data['community_description'])) {
            $post_data['post_content'] = $data['community_description'];
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $this->set_error_message(__('Failed to create community.', 'happyplace'));
            $this->redirect_back();
        }

        // Handle featured image upload
        $featured_image_id = $this->handle_file_upload('community_featured_image');
        if ($featured_image_id) {
            set_post_thumbnail($post_id, $featured_image_id);
        }

        // Handle gallery images
        $gallery_ids = $this->handle_multiple_file_uploads('community_gallery');
        if (!empty($gallery_ids)) {
            update_post_meta($post_id, '_community_gallery', $gallery_ids);
        }

        // Save meta data
        update_post_meta($post_id, '_community_city', $data['community_city']);
        
        if (!empty($data['community_amenities'])) {
            $amenities = array_filter(array_map('trim', explode("\n", $data['community_amenities'])));
            update_post_meta($post_id, '_community_amenities', $amenities);
        }

        $this->set_success_message(__('Community submitted successfully!', 'happyplace'));
        $this->redirect_back();
    }
}
