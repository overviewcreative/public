<?php
/**
 * PDF Generator Class
 *
 * Handles the generation of PDFs for listings and agent profiles using DomPDF.
 *
 * @package HappyPlace
 * @subpackage Utilities
 */

namespace HappyPlace\Utilities;

use Dompdf\Dompdf;
use Dompdf\Options;
use WP_Post;

class PDF_Generator {
    /**
     * Instance of this class
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Default PDF generation configurations
     *
     * @var array
     */
    private $default_config = [
        'paper_size' => 'letter',
        'orientation' => 'portrait',
        'template_dir' => null
    ];

    /**
     * Get instance of this class
     *
     * @return self
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Ensure DomPDF is loaded
        $this->load_dependencies();

        // Register cleanup hook
        add_action('wp_scheduled_delete', [$this, 'cleanup_old_pdfs']);
    }

    /**
     * Load PDF generation dependencies
     */
    private function load_dependencies(): void {
        if (!class_exists('Dompdf\Dompdf')) {
            require_once plugin_dir_path(dirname(__DIR__)) . 'vendor/autoload.php';
        }
    }

    /**
     * Generate PDF for a listing
     *
     * @param int $listing_id The listing post ID
     * @param array $config Optional configuration overrides
     * @return string|null URL of the generated PDF or null on failure
     */
    public function generate_listing_pdf(int $listing_id, array $config = []): ?string {
        // Merge default config with provided config
        $config = array_merge($this->default_config, $config);

        // Get listing data
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_type !== 'listing') {
            return null;
        }

        // Prepare PDF content
        $html = $this->generate_listing_html($listing);

        // Generate PDF
        return $this->generate_pdf($html, $config);
    }

    /**
     * Generate HTML for listing PDF
     *
     * @param WP_Post $listing The listing post object
     * @return string Generated HTML
     */
    private function generate_listing_html(WP_Post $listing): string {
        // Gather listing metadata
        $price = get_field('price', $listing->ID);
        $bedrooms = get_field('bedrooms', $listing->ID);
        $bathrooms = get_field('bathrooms', $listing->ID);
        $square_footage = get_field('square_footage', $listing->ID);
        $main_photo = get_field('main_photo', $listing->ID);
        $gallery = get_field('photo_gallery', $listing->ID);

        // Start output buffering
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .listing-header { 
                    text-align: center;
                    margin-bottom: 30px;
                }
                .listing-header h1 {
                    color: #1a73e8;
                    margin: 0 0 10px;
                }
                .listing-details { 
                    margin: 20px 0;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 5px;
                }
                .listing-image { 
                    max-width: 100%;
                    height: auto;
                    margin-bottom: 20px;
                }
                .listing-gallery {
                    margin: 30px 0;
                }
                .listing-gallery img {
                    max-width: 45%;
                    margin: 10px;
                    border: 1px solid #ddd;
                }
                .listing-description {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 2px solid #eee;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                th, td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                th {
                    background-color: #f8f9fa;
                }
            </style>
        </head>
        <body>
            <div class="listing-header">
                <h1><?php echo esc_html($listing->post_title); ?></h1>
            </div>

            <div class="listing-image">
                <?php if ($main_photo): ?>
                    <img src="<?php echo esc_url($main_photo); ?>" class="listing-image">
                <?php endif; ?>
            </div>

            <div class="listing-details">
                <h2>Property Details</h2>
                <table>
                    <tr>
                        <th>Price</th>
                        <td>$<?php echo number_format($price); ?></td>
                    </tr>
                    <tr>
                        <th>Bedrooms</th>
                        <td><?php echo esc_html($bedrooms); ?></td>
                    </tr>
                    <tr>
                        <th>Bathrooms</th>
                        <td><?php echo esc_html($bathrooms); ?></td>
                    </tr>
                    <tr>
                        <th>Square Footage</th>
                        <td><?php echo number_format($square_footage); ?> sq ft</td>
                    </tr>
                </table>
            </div>

            <?php if ($gallery): ?>
                <div class="listing-gallery">
                    <h2>Additional Photos</h2>
                    <?php foreach ($gallery as $image): ?>
                        <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($listing->post_title); ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="listing-description">
                <h2>Description</h2>
                <?php echo wp_kses_post($listing->post_content); ?>
            </div>

            <footer style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
                <p>Generated on <?php echo date('F j, Y'); ?> by <?php echo esc_html(get_bloginfo('name')); ?></p>
            </footer>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate PDF from HTML
     *
     * @param string $html The HTML content to convert
     * @param array $config PDF configuration options
     * @return string|null URL of the generated PDF or null on failure
     */
    private function generate_pdf(string $html, array $config): ?string {
        try {
            // Configure PDF options
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('chroot', [
                get_template_directory(),
                WP_CONTENT_DIR
            ]);

            // Create DomPDF instance
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper($config['paper_size'], $config['orientation']);
            $dompdf->render();

            // Generate unique filename
            $filename = 'listing-' . uniqid() . '.pdf';
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . '/happy-place-pdfs/' . $filename;

            // Ensure directory exists
            wp_mkdir_p(dirname($file_path));

            // Save PDF
            file_put_contents($file_path, $dompdf->output());

            // Return relative URL
            return $upload_dir['baseurl'] . '/happy-place-pdfs/' . $filename;
        } catch (\Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate PDF for agent profile
     *
     * @param int $agent_id The agent post ID
     * @param array $config Optional configuration overrides
     * @return string|null URL of the generated PDF or null on failure
     */
    public function generate_agent_pdf(int $agent_id, array $config = []): ?string {
        // Similar to listing PDF, but with agent-specific details
        // Implementation left as an exercise
        return null;
    }

    /**
     * Clean up old PDF files
     */
    public function cleanup_old_pdfs(): void {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/happy-place-pdfs/';

        if (!is_dir($pdf_dir)) {
            return;
        }

        $files = glob($pdf_dir . '*.pdf');
        $now = time();

        foreach ($files as $file) {
            // Delete PDFs older than 7 days
            if ($now - filemtime($file) >= 7 * 24 * 60 * 60) {
                unlink($file);
            }
        }
    }
}

// Initialize PDF Generator
add_action('init', function() {
    PDF_Generator::get_instance();
});
