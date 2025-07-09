<?php
namespace HappyPlace\Setup;

class Plugin_Setup {
    public static function create_directories(): void {
        $directories = [
            'includes/graphics',
            'includes/integrations',
            'includes/templates',
            'includes/assets/js',
            'includes/assets/css',
        ];

        foreach ($directories as $dir) {
            $full_path = HPH_PLUGIN_DIR . $dir;
            if (!file_exists($full_path)) {
                wp_mkdir_p($full_path);
            }
        }
    }

    public static function verify_dependencies(): bool {
        // Verify Fabric.js is accessible
        $fabric_url = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js';
        $response = wp_remote_head($fabric_url);
        
        if (is_wp_error($response)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo 'Happy Place Plugin: Unable to access Fabric.js library. Some features may not work correctly.';
                echo '</p></div>';
            });
            return false;
        }

        return true;
    }
}
