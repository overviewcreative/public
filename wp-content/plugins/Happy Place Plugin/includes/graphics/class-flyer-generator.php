<?php
namespace HappyPlace\Graphics;

class Flyer_Generator {
    private static ?self $instance = null;
    
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_generate_flyer', [$this, 'ajax_generate_flyer']);
        add_action('wp_ajax_nopriv_generate_flyer', [$this, 'ajax_generate_flyer']);
        add_shortcode('listing_flyer_generator', [$this, 'render_flyer_generator']);
    }

    public function enqueue_scripts(): void {
        wp_enqueue_script(
            'fabric-js',
            'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
            [],
            '5.3.0',
            true
        );

        wp_enqueue_script(
            'flyer-generator',
            HPH_PLUGIN_URL . 'includes/assets/js/flyer-generator.js',
            ['fabric-js', 'jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('flyer-generator', 'flyerAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flyer_generator_nonce')
        ]);

        wp_enqueue_style(
            'flyer-generator',
            HPH_PLUGIN_URL . 'includes/assets/css/flyer-generator.css',
            [],
            HPH_VERSION
        );
    }

    public function ajax_generate_flyer() {
        // Check nonce for security
        check_ajax_referer('flyer_generator_nonce', 'nonce');

        // Get the posted data
        $data = isset($_POST['data']) ? $_POST['data'] : '';

        // Process the data and generate the flyer
        // ...

        // For demonstration, let's assume the flyer is generated as a base64 string
        $flyer_image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA...';

        // Send the response back
        wp_send_json_success(['image' => $flyer_image]);
    }

    public function render_flyer_generator() {
        ob_start();
        ?>
        <div id="flyer-generator-app">
            <!-- Your HTML structure for the flyer generator -->
            <div id="canvas-container">
                <!-- Fabric.js canvas will be injected here -->
            </div>
            <button id="save-flyer">Save Flyer</button>
        </div>
        <?php
        return ob_get_clean();
    }
}
