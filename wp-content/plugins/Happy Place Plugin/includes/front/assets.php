<?php
namespace HappyPlace\Front;

class Assets {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets(): void {
        if (is_singular('listing')) {
            wp_enqueue_style(
                'happy-place-pdf-button',
                plugins_url('assets/css/pdf-button.css', dirname(__DIR__)),
                [],
                filemtime(plugin_dir_path(dirname(__DIR__)) . 'assets/css/pdf-button.css')
            );

            wp_enqueue_script('jquery');
        }
    }
}

// Initialize Assets
add_action('init', function() {
    Assets::get_instance();
});
