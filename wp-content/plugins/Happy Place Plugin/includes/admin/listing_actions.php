<?php
namespace HappyPlace\Admin;

use HappyPlace\Utilities\PDF_Generator;

use function \add_action;
use function \add_filter;
use function \admin_url;
use function \check_ajax_referer;
use function \current_user_can;
use function \esc_url;
use function \get_the_ID;
use function \is_singular;
use function \wp_create_nonce;
use function \wp_die;
use function \wp_redirect;
use function \wp_send_json_error;
use function \wp_send_json_success;

class Listing_Actions {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        // Add PDF download button to listing edit screen
        add_action('post_submitbox_misc_actions', [$this, 'add_pdf_download_button']);
        
        // Add PDF generation endpoint
        add_action('admin_init', [$this, 'handle_pdf_generation']);
        
        // Add PDF download button to frontend listing
        add_filter('the_content', [$this, 'add_frontend_pdf_button']);
        
        // Add AJAX handler for frontend PDF generation
        add_action('wp_ajax_generate_listing_pdf', [$this, 'handle_ajax_pdf_generation']);
        add_action('wp_ajax_nopriv_generate_listing_pdf', [$this, 'handle_ajax_pdf_generation']);
    }

    /**
     * Add PDF download button to listing edit screen
     */
    public function add_pdf_download_button($post): void {
        if ($post->post_type !== 'listing') {
            return;
        }
        ?>
        <div class="misc-pub-section">
            <a href="<?php echo esc_url(admin_url('admin.php?action=generate_listing_pdf&post=' . $post->ID)); ?>" 
               class="button button-secondary" target="_blank">
                Generate PDF
            </a>
        </div>
        <?php
    }

    /**
     * Handle PDF generation from admin
     */
    public function handle_pdf_generation(): void {
        if (!isset($_GET['action']) || $_GET['action'] !== 'generate_listing_pdf' || !isset($_GET['post'])) {
            return;
        }

        $post_id = intval($_GET['post']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('You do not have permission to generate PDFs.');
        }

        $pdf_url = PDF_Generator::get_instance()->generate_listing_pdf($post_id);
        if ($pdf_url) {
            wp_redirect($pdf_url);
            exit;
        }

        wp_die('Failed to generate PDF. Please try again.');
    }

    /**
     * Add PDF download button to frontend listing
     */
    public function add_frontend_pdf_button($content): string {
        if (!is_singular('listing')) {
            return $content;
        }

        $button = sprintf(
            '<div class="listing-pdf-download">
                <button class="generate-pdf-btn" data-listing-id="%d">
                    Download PDF
                </button>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $(".generate-pdf-btn").on("click", function() {
                    var btn = $(this);
                    btn.prop("disabled", true).text("Generating...");
                    
                    $.ajax({
                        url: "%s",
                        type: "POST",
                        data: {
                            action: "generate_listing_pdf",
                            listing_id: btn.data("listing-id"),
                            nonce: "%s"
                        },
                        success: function(response) {
                            if (response.success && response.data.url) {
                                window.open(response.data.url, "_blank");
                            } else {
                                alert("Failed to generate PDF. Please try again.");
                            }
                        },
                        error: function() {
                            alert("Failed to generate PDF. Please try again.");
                        },
                        complete: function() {
                            btn.prop("disabled", false).text("Download PDF");
                        }
                    });
                });
            });
            </script>',
            get_the_ID(),
            admin_url('admin-ajax.php'),
            wp_create_nonce('generate_listing_pdf')
        );

        return $content . $button;
    }

    /**
     * Handle AJAX PDF generation
     */
    public function handle_ajax_pdf_generation(): void {
        check_ajax_referer('generate_listing_pdf', 'nonce');

        $listing_id = isset($_POST['listing_id']) ? intval($_POST['listing_id']) : 0;
        if (!$listing_id) {
            wp_send_json_error(['message' => 'Invalid listing ID']);
        }

        $pdf_url = PDF_Generator::get_instance()->generate_listing_pdf($listing_id);
        if ($pdf_url) {
            wp_send_json_success(['url' => $pdf_url]);
        }

        wp_send_json_error(['message' => 'Failed to generate PDF']);
    }
}

// Initialize Listing Actions
add_action('init', function() {
    Listing_Actions::get_instance();
});
