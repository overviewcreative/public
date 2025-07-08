<?php
/**
 * PDF Generation Class
 *
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

class HP_PDF_Generator {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_pdf_scripts'));
        add_action('wp_footer', array($this, 'add_print_button'));
        add_action('wp_ajax_generate_property_pdf', array($this, 'generate_property_pdf'));
        add_action('wp_ajax_nopriv_generate_property_pdf', array($this, 'generate_property_pdf'));
    }

    /**
     * Enqueue PDF generation scripts
     */
    public function enqueue_pdf_scripts() {
        if (!is_singular('property')) {
            return;
        }

        wp_enqueue_script(
            'html2pdf',
            'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
            array(),
            '0.10.1',
            true
        );

        wp_enqueue_script(
            'hph-pdf',
            HP_PLUGIN_URL . 'assets/js/pdf-generator.js',
            array('jquery', 'html2pdf'),
            HP_VERSION,
            true
        );

        wp_localize_script('hph-pdf', 'hphPDF', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('generate_property_pdf'),
            'propertyId' => get_the_ID()
        ));
    }

    /**
     * Add print button to property pages
     */
    public function add_print_button() {
        if (!is_singular('property')) {
            return;
        }

        ?>
        <button id="hph-print-property" class="hph-print-button">
            <i class="fas fa-print"></i>
            <?php _e('Print Property Details', 'happy-place'); ?>
        </button>
        <?php
    }

    /**
     * Generate PDF for property
     */
    public function generate_property_pdf() {
        check_ajax_referer('generate_property_pdf', 'nonce');

        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        if (!$property_id) {
            wp_send_json_error('Invalid property ID');
        }

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            wp_send_json_error('Property not found');
        }

        // Get property data
        $data = array(
            'title' => get_the_title($property_id),
            'price' => get_field('property_price', $property_id),
            'address' => get_field('property_address', $property_id),
            'details' => get_field('property_details', $property_id),
            'features' => get_field('property_features', $property_id),
            'gallery' => get_field('property_gallery', $property_id),
            'agent' => get_field('property_agent', $property_id),
            'disclaimers' => HP_Compliance::get_print_disclaimers($property_id)
        );

        // Generate PDF content
        ob_start();
        include HP_PLUGIN_DIR . 'templates/pdf/property-pdf.php';
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'filename' => sanitize_title($data['title']) . '-details.pdf'
        ));
    }
}
