<?php
/**
 * Form handlers initialization
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class Forms {
    /**
     * Initialize form handlers
     */
    public function init() {
        $handlers = [
            new Agent_Form_Handler(),
            new Community_Form_Handler(),
            new City_Form_Handler(),
            new Open_House_Form_Handler(),
            new Transaction_Form_Handler(),
            new Client_Form_Handler(),
        ];

        foreach ($handlers as $handler) {
            $handler->init();
        }

        // Add messages to frontend
        add_action('wp_footer', [$this, 'display_messages']);
    }

    /**
     * Display form messages
     */
    public function display_messages() {
        if (!session_id()) {
            session_start();
        }

        if (!empty($_SESSION['form_success_message'])) {
            echo '<div class="form-message success">' . esc_html($_SESSION['form_success_message']) . '</div>';
            unset($_SESSION['form_success_message']);
        }

        if (!empty($_SESSION['form_error_message'])) {
            echo '<div class="form-message error">' . esc_html($_SESSION['form_error_message']) . '</div>';
            unset($_SESSION['form_error_message']);
        }
    }
}
