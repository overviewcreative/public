<?php
/**
 * PDF Generation Service
 *
 * Handles generation of PDFs for property listings, reports, etc.
 * using Dompdf library.
 *
 * @package HappyPlace
 * @subpackage Services
 */

namespace HappyPlace\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

use function WordPress\{
    get_bloginfo,
    get_post,
    get_post_meta,
    get_the_post_thumbnail_url,
    get_the_title,
    sanitize_title,
    wp_die,
    wp_kses_post
};

class PDF_Service {
    /**
     * Instance of Dompdf
     *
     * @var Dompdf
     */
    private Dompdf $dompdf;

    /**
     * Constructor
     */
    public function __construct() {
        // Configure Dompdf options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        // Initialize Dompdf
        $this->dompdf = new Dompdf($options);
    }

    /**
     * Generate PDF for a property listing
     *
     * @param int    $post_id Property post ID
     * @param string $output  Output type ('stream' or 'download')
     * @return void
     */
    public function generate_property_pdf(int $post_id, string $output = 'download'): void {
        $property = get_post($post_id);
        if (!$property || $property->post_type !== 'property') {
            wp_die('Invalid property');
        }

        // Get property data
        $data = [
            'title' => get_the_title($post_id),
            'price' => get_post_meta($post_id, '_price', true),
            'address' => get_post_meta($post_id, '_address', true),
            'description' => $property->post_content,
            'features' => [
                'bedrooms' => get_post_meta($post_id, '_bedrooms', true),
                'bathrooms' => get_post_meta($post_id, '_bathrooms', true),
                'square_feet' => get_post_meta($post_id, '_square_feet', true),
                'lot_size' => get_post_meta($post_id, '_lot_size', true),
                'year_built' => get_post_meta($post_id, '_year_built', true)
            ],
            'image' => get_the_post_thumbnail_url($post_id, 'large'),
            'agent' => $this->get_agent_info($post_id)
        ];

        // Generate HTML
        $html = $this->get_property_pdf_template($data);

        // Load HTML into Dompdf
        $this->dompdf->loadHtml($html);

        // Set paper size and orientation
        $this->dompdf->setPaper('A4', 'portrait');

        // Render PDF
        $this->dompdf->render();

        // Output PDF
        if ($output === 'stream') {
            $this->dompdf->stream(sanitize_title($data['title']) . '.pdf', [
                'Attachment' => false
            ]);
        } else {
            $this->dompdf->stream(sanitize_title($data['title']) . '.pdf');
        }
    }

    /**
     * Get agent information
     *
     * @param int $property_id Property post ID
     * @return array Agent information
     */
    private function get_agent_info(int $property_id): array {
        $agent_id = get_post_meta($property_id, '_agent_id', true);
        if (!$agent_id) {
            return [];
        }

        return [
            'name' => get_the_title($agent_id),
            'phone' => get_post_meta($agent_id, '_phone', true),
            'email' => get_post_meta($agent_id, '_email', true),
            'license' => get_post_meta($agent_id, '_license_number', true),
            'photo' => get_the_post_thumbnail_url($agent_id, 'thumbnail')
        ];
    }

    /**
     * Get property PDF template
     *
     * @param array $data Property data
     * @return string HTML template
     */
    private function get_property_pdf_template(array $data): string {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body {
                    font-family: Helvetica, Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .property-image {
                    width: 100%;
                    max-height: 300px;
                    object-fit: cover;
                    margin-bottom: 20px;
                }
                .price {
                    font-size: 24px;
                    color: #0ea5e9;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .address {
                    font-size: 18px;
                    margin-bottom: 20px;
                }
                .features {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    border-top: 1px solid #ddd;
                    border-bottom: 1px solid #ddd;
                    padding: 15px 0;
                }
                .feature {
                    text-align: center;
                }
                .description {
                    margin-bottom: 30px;
                }
                .agent-info {
                    background: #f9fafb;
                    padding: 20px;
                    border-radius: 5px;
                }
                .agent-info img {
                    width: 100px;
                    height: 100px;
                    border-radius: 50%;
                    object-fit: cover;
                    float: left;
                    margin-right: 20px;
                }
                footer {
                    text-align: center;
                    margin-top: 50px;
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($data['title']); ?></h1>
            </div>

            <?php if ($data['image']) : ?>
                <img src="<?php echo esc_url($data['image']); ?>" alt="<?php echo esc_attr($data['title']); ?>" class="property-image">
            <?php endif; ?>

            <div class="price">$<?php echo number_format($data['price']); ?></div>
            <div class="address"><?php echo esc_html($data['address']); ?></div>

            <div class="features">
                <div class="feature">
                    <strong><?php echo esc_html($data['features']['bedrooms']); ?></strong><br>
                    Bedrooms
                </div>
                <div class="feature">
                    <strong><?php echo esc_html($data['features']['bathrooms']); ?></strong><br>
                    Bathrooms
                </div>
                <div class="feature">
                    <strong><?php echo number_format($data['features']['square_feet']); ?></strong><br>
                    Sq Ft
                </div>
                <div class="feature">
                    <strong><?php echo esc_html($data['features']['year_built']); ?></strong><br>
                    Year Built
                </div>
            </div>

            <div class="description">
                <?php echo wp_kses_post($data['description']); ?>
            </div>

            <?php if (!empty($data['agent'])) : ?>
                <div class="agent-info">
                    <?php if ($data['agent']['photo']) : ?>
                        <img src="<?php echo esc_url($data['agent']['photo']); ?>" alt="<?php echo esc_attr($data['agent']['name']); ?>">
                    <?php endif; ?>
                    <h3>Contact Agent</h3>
                    <p>
                        <strong><?php echo esc_html($data['agent']['name']); ?></strong><br>
                        License #: <?php echo esc_html($data['agent']['license']); ?><br>
                        Phone: <?php echo esc_html($data['agent']['phone']); ?><br>
                        Email: <?php echo esc_html($data['agent']['email']); ?>
                    </p>
                </div>
            <?php endif; ?>

            <footer>
                Generated on <?php echo date('F j, Y'); ?><br>
                <?php echo esc_html(get_bloginfo('name')); ?>
            </footer>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
