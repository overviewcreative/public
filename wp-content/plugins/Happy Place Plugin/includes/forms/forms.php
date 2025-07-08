<?php
/**
 * Forms initialization and management
 */

namespace HappyPlace\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Forms class to manage form handlers
 */
class Forms {
    /**
     * @var array Form handlers
     */
    private $handlers = [];

    /**
     * Initialize form handlers
     */
    public function init() {
        $this->register_handlers();
        $this->init_handlers();
        add_action('wp_footer', [$this, 'display_messages']);
    }

    /**
     * Register form handlers
     */
    private function register_handlers() {
        $this->handlers = [
            new Agent_Form_Handler(),
        ];
    }

    /**
     * Initialize all handlers
     */
    private function init_handlers() {
        foreach ($this->handlers as $handler) {
            $handler->init();
        }
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
