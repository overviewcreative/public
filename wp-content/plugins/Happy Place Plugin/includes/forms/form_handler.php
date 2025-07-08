<?php
/**
 * Base form handler
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract base class for form handlers
 */
abstract class Form_Handler {
    /**
     * Initialize the form handler
     */
    public function init() {
        add_action('admin_post_' . $this->get_action(), [$this, 'handle_submission']);
        add_action('admin_post_nopriv_' . $this->get_action(), [$this, 'handle_submission']);
    }

    /**
     * Get the form action name
     *
     * @return string
     */
    abstract protected function get_action();

    /**
     * Handle form submission
     */
    abstract public function handle_submission();

    /**
     * Validate the nonce
     *
     * @param string $nonce_name
     * @param string $nonce_action
     * @return bool
     */
    protected function validate_nonce($nonce_name, $nonce_action) {
        if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $nonce_action)) {
            wp_die('Invalid nonce specified', 'Error', [
                'response' => 403,
                'back_link' => true,
            ]);
        }
        return true;
    }

    /**
     * Set success message
     *
     * @param string $message
     */
    protected function set_success_message($message) {
        if (!session_id()) {
            session_start();
        }
        $_SESSION['form_success_message'] = $message;
    }

    /**
     * Set error message
     *
     * @param string $message
     */
    protected function set_error_message($message) {
        if (!session_id()) {
            session_start();
        }
        $_SESSION['form_error_message'] = $message;
    }

    /**
     * Redirect back
     *
     * @param string $url Optional URL to redirect to
     */
    protected function redirect_back($url = '') {
        if (empty($url)) {
            $url = wp_get_referer();
        }
        wp_safe_redirect($url);
        exit;
    }

    /**
     * Handle file upload
     *
     * @param string $file_key
     * @return int Attachment ID or 0 on failure
     */
    protected function handle_file_upload($file_key) {
        if (!isset($_FILES[$file_key]) || empty($_FILES[$file_key]['name'])) {
            return 0;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload($file_key, 0);

        return is_wp_error($attachment_id) ? 0 : $attachment_id;
    }
}
