<?php
// File: includes/admin/class-admin-menu.php

namespace HappyPlace\Admin;

class Admin_Menu {
    private static ?self $instance = null;
    
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'register_menu_pages']);
    }
    
    public function register_menu_pages(): void {
        add_menu_page(
            'Happy Place',
            'Happy Place',
            'manage_options',
            'happy-place',
            [$this, 'render_dashboard'],
            'dashicons-building',
            30
        );
        
        // Add submenus
        add_submenu_page(
            'happy-place',
            'Settings',
            'Settings',
            'manage_options',
            'happy-place-settings',
            [$this, 'render_settings']
        );
    }
    
    public function render_dashboard(): void {
        // Render main dashboard
    }
    
    public function render_settings(): void {
        // Render settings page
    }
}
