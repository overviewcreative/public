<?php
namespace HappyPlace\Admin;

class CSV_Import_Tool {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_import_page']);
        add_action('wp_ajax_hph_import_listings_csv', [$this, 'handle_csv_import']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_import_scripts']);
    }

    /**
     * Add CSV import page to admin menu
     */
    public function add_import_page(): void {
        add_submenu_page(
            'happy-place',
            'Import Listings',
            'Import CSV',
            'manage_options',
            'happy-place-import',
            [$this, 'render_import_page']
        );
    }

    /**
     * Render the CSV import page
     */
    public function render_import_page(): void {
        ?>
        <div class="wrap">
            <h1>Import Listings from CSV</h1>
            
            <div class="hph-import-instructions">
                <h2>Instructions</h2>
                <ol>
                    <li>Download the <a href="<?php echo esc_url(HPH_PLUGIN_URL . 'templates/listings-template.csv'); ?>">CSV template</a></li>
                    <li>Fill in your listing data following the template format</li>
                    <li>Upload your completed CSV file below</li>
                    <li>Review the preview and click "Import Listings"</li>
                </ol>
                
                <div class="notice notice-info">
                    <p><strong>Important:</strong> Make sure agent emails in the CSV match existing agent users in WordPress, or the listings will be assigned to the current user.</p>
                </div>
            </div>

            <form id="hph-csv-import-form" enctype="multipart/form-data">
                <?php wp_nonce_field('hph_import_listings_csv', 'hph_csv_import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="csv_file">CSV File</label>
                        </th>
                        <td>
                            <input type="file" 
                                   name="listing_csv" 
                                   id="csv_file" 
                                   accept=".csv"
                                   required />
                            <p class="description">Select your CSV file containing listing data.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="import_mode">Import Mode</label>
                        </th>
                        <td>
                            <select name="import_mode" id="import_mode">
                                <option value="create_only">Create New Listings Only</option>
                                <option value="update_existing">Update Existing by MLS Number</option>
                                <option value="create_and_update">Create New & Update Existing</option>
                            </select>
                            <p class="description">Choose how to handle existing listings.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_status">Default Status</label>
                        </th>
                        <td>
                            <select name="default_status" id="default_status">
                                <option value="draft">Draft (Review Required)</option>
                                <option value="publish">Publish Immediately</option>
                            </select>
                            <p class="description">Status for imported listings.</p>
                        </td>
                    </tr>
                </table>

                <div class="hph-import-actions">
                    <button type="button" id="preview-csv" class="button">Preview CSV Data</button>
                    <button type="submit" id="import-csv" class="button button-primary" disabled>Import Listings</button>
                </div>
            </form>

            <div id="csv-preview" class="hph-csv-preview" style="display: none;">
                <h2>CSV Preview</h2>
                <div id="preview-content"></div>
            </div>

            <div id="import-progress" class="hph-import-progress" style="display: none;">
                <h2>Import Progress</h2>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="progress-text">Preparing import...</div>
            </div>

            <div id="import-results" class="hph-import-results" style="display: none;">
                <h2>Import Results</h2>
                <div id="results-content"></div>
            </div>
        </div>

        <style>
        .hph-import-instructions {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .hph-csv-preview {
            margin-top: 30px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }

        .hph-csv-preview h2 {
            background: #f1f1f1;
            margin: 0;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .preview-table th,
        .preview-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 12px;
        }

        .preview-table th {
            background: #f9f9f9;
            font-weight: 600;
        }

        .preview-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .hph-import-progress {
            margin-top: 30px;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #f1f1f1;
            border-radius: 4px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            height: 100%;
            background: #0073aa;
            transition: width 0.3s ease;
        }

        .progress-text {
            text-align: center;
            font-weight: 600;
        }

        .hph-import-results {
            margin-top: 30px;
        }

        .result-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .result-stat {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .result-stat .number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }

        .result-stat .label {
            color: #666;
            margin-top: 5px;
        }

        .import-log {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }

        .log-error { color: #d63638; }
        .log-warning { color: #dba617; }
        .log-success { color: #00a32a; }
        .log-info { color: #0073aa; }
        </style>
        <?php
    }

    /**
     * Enqueue import scripts
     */
    public function enqueue_import_scripts($hook): void {
        if ($hook !== 'happy-place_page_happy-place-import') {
            return;
        }

        wp_enqueue_script(
            'hph-csv-import',
            HPH_PLUGIN_URL . 'assets/js/csv-import.js',
            ['jquery'],
            HPH_VERSION,
            true
        );

        wp_localize_script('hph-csv-import', 'hphImport', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_import_listings_csv')
        ]);
    }

    /**
     * Handle CSV import via AJAX
     */
    public function handle_csv_import(): void {
        check_ajax_referer('hph_import_listings_csv', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access.']);
            return;
        }

        if (!isset($_FILES['listing_csv'])) {
            wp_send_json_error(['message' => 'No file was uploaded.']);
            return;
        }

        $file = $_FILES['listing_csv'];
        $import_mode = sanitize_text_field($_POST['import_mode'] ?? 'create_only');
        $default_status = sanitize_text_field($_POST['default_status'] ?? 'draft');

        // Basic file validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'File upload failed.']);
            return;
        }

        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
            wp_send_json_error(['message' => 'Please upload a CSV file.']);
            return;
        }

        try {
            // Parse and validate CSV data
            $csv_data = $this->parse_csv($file['tmp_name']);
            
            if (empty($csv_data)) {
                wp_send_json_error(['message' => 'CSV file is empty or invalid.']);
                return;
            }

            // Validate required fields
            $headers = array_keys($csv_data[0]);
            $required_fields = ['title', 'price', 'street_address', 'city'];
            $missing_fields = array_diff($required_fields, $headers);

            if (!empty($missing_fields)) {
                wp_send_json_error([
                    'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
                ]);
                return;
            }

            // Process each row
            $results = [
                'total' => count($csv_data),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'log' => []
            ];

            foreach ($csv_data as $index => $row) {
                try {
                    $result = $this->import_single_listing($row, $import_mode, $default_status);
                    $results[$result['action']]++;
                    $results['log'][] = [
                        'type' => 'success',
                        'message' => "Row " . ($index + 1) . ": {$result['message']}"
                    ];
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['log'][] = [
                        'type' => 'error',
                        'message' => "Row " . ($index + 1) . ": " . $e->getMessage()
                    ];
                }
            }

            wp_send_json_success($results);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Parse CSV file
     */
    private function parse_csv(string $file_path): array {
        if (!file_exists($file_path)) {
            throw new \Exception('CSV file not found');
        }

        $data = [];
        $headers = [];

        if (($handle = fopen($file_path, 'r')) !== false) {
            // Read headers
            if (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $headers = array_map('trim', $row);
            }

            // Read data rows
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, array_map('trim', $row));
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Validate CSV data
     */
    private function validate_csv_data(array $data): array {
        $validation = [
            'valid_rows' => 0,
            'warnings' => [],
            'errors' => []
        ];

        foreach ($data as $index => $row) {
            $row_num = $index + 1;
            $row_valid = true;

            // Check required fields
            if (empty($row['title'])) {
                $validation['errors'][] = "Row {$row_num}: Title is required";
                $row_valid = false;
            }

            if (empty($row['price']) || !is_numeric(str_replace([',', ' '], '', $row['price']))) {
                $validation['errors'][] = "Row {$row_num}: Valid price is required";
                $row_valid = false;
            }

            if (empty($row['street_address'])) {
                $validation['errors'][] = "Row {$row_num}: Street address is required";
                $row_valid = false;
            }

            if (empty($row['city'])) {
                $validation['errors'][] = "Row {$row_num}: City is required";
                $row_valid = false;
            }

            // Check optional but important fields
            if (empty($row['bedrooms']) || !is_numeric($row['bedrooms'])) {
                $validation['warnings'][] = "Row {$row_num}: Bedrooms should be a number";
            }

            if (empty($row['bathrooms']) || !is_numeric($row['bathrooms'])) {
                $validation['warnings'][] = "Row {$row_num}: Bathrooms should be a number";
            }

            // Validate agent email if provided
            if (!empty($row['agent_email']) && !is_email($row['agent_email'])) {
                $validation['warnings'][] = "Row {$row_num}: Invalid agent email format";
            }

            if ($row_valid) {
                $validation['valid_rows']++;
            }
        }

        return $validation;
    }

    /**
     * Import a single listing
     */
    private function import_single_listing(array $row, string $import_mode, string $default_status): array {
        // Check if listing already exists by MLS number
        $existing_post = null;
        if (!empty($row['mls_number'])) {
            $existing_posts = get_posts([
                'post_type' => 'listing',
                'meta_key' => 'mls_number',
                'meta_value' => $row['mls_number'],
                'posts_per_page' => 1,
                'post_status' => ['publish', 'draft', 'pending']
            ]);

            if (!empty($existing_posts)) {
                $existing_post = $existing_posts[0];
            }
        }

        // Handle existing post based on import mode
        if ($existing_post) {
            if ($import_mode === 'create_only') {
                return [
                    'action' => 'skipped',
                    'message' => "Listing with MLS #{$row['mls_number']} already exists"
                ];
            } elseif ($import_mode === 'update_existing' || $import_mode === 'create_and_update') {
                return $this->update_listing($existing_post->ID, $row);
            }
        }

        // Create new listing
        return $this->create_listing($row, $default_status);
    }

    /**
     * Create new listing
     */
    private function create_listing(array $row, string $default_status): array {
        // Find agent by email
        $agent_id = null;
        if (!empty($row['agent_email'])) {
            $agent_user = get_user_by('email', $row['agent_email']);
            if ($agent_user) {
                $agent_id = $agent_user->ID;
            }
        }

        // Prepare post data
        $post_data = [
            'post_title' => $row['title'],
            'post_content' => $row['short_description'] ?? '',
            'post_type' => 'listing',
            'post_status' => $default_status,
            'post_author' => $agent_id ?: get_current_user_id()
        ];

        // Create the post
        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            throw new \Exception('Failed to create listing: ' . $post_id->get_error_message());
        }

        // Add custom fields
        $this->update_listing_fields($post_id, $row);

        return [
            'action' => 'created',
            'message' => "Created listing: {$row['title']} (ID: {$post_id})"
        ];
    }

    /**
     * Update existing listing
     */
    private function update_listing(int $post_id, array $row): array {
        // Update post data
        $post_data = [
            'ID' => $post_id,
            'post_title' => $row['title'],
            'post_content' => $row['short_description'] ?? ''
        ];

        wp_update_post($post_data);

        // Update custom fields
        $this->update_listing_fields($post_id, $row);

        return [
            'action' => 'updated',
            'message' => "Updated listing: {$row['title']} (ID: {$post_id})"
        ];
    }

    /**
     * Update listing custom fields
     */
    private function update_listing_fields(int $post_id, array $row): void {
        // Price and basic details
        if (!empty($row['price'])) {
            $price = (float)str_replace([',', ' '], '', $row['price']);
            update_field('price', $price, $post_id);
        }

        if (!empty($row['bedrooms'])) {
            update_field('bedrooms', (int)$row['bedrooms'], $post_id);
        }

        if (!empty($row['bathrooms'])) {
            update_field('bathrooms', (float)$row['bathrooms'], $post_id);
        }

        if (!empty($row['square_footage'])) {
            update_field('square_footage', (int)$row['square_footage'], $post_id);
        }

        if (!empty($row['lot_size'])) {
            update_field('lot_size', (float)$row['lot_size'], $post_id);
        }

        if (!empty($row['year_built'])) {
            update_field('year_built', (int)$row['year_built'], $post_id);
        }

        // Address fields
        if (!empty($row['street_address'])) {
            update_field('street_address', $row['street_address'], $post_id);
        }

        if (!empty($row['city'])) {
            update_field('city', $row['city'], $post_id);
        }

        if (!empty($row['region'])) {
            update_field('region', $row['region'], $post_id);
        }

        if (!empty($row['zip_code'])) {
            update_field('zip_code', $row['zip_code'], $post_id);
        }

        // Property details
        if (!empty($row['property_type'])) {
            update_field('property_type', $row['property_type'], $post_id);
        }

        if (!empty($row['status'])) {
            update_field('status', $row['status'], $post_id);
        }

        if (!empty($row['mls_number'])) {
            update_field('mls_number', $row['mls_number'], $post_id);
        }

        if (!empty($row['virtual_tour_link'])) {
            update_field('virtual_tour_link', $row['virtual_tour_link'], $post_id);
        }

        // Features
        if (!empty($row['interior_features'])) {
            update_field('interior_features', $row['interior_features'], $post_id);
        }

        if (!empty($row['exterior_features'])) {
            update_field('exterior_features', $row['exterior_features'], $post_id);
        }

        if (!empty($row['utility_features'])) {
            update_field('utility_features', $row['utility_features'], $post_id);
        }

        // Coordinates
        if (!empty($row['latitude']) && !empty($row['longitude'])) {
            update_field('latitude', (float)$row['latitude'], $post_id);
            update_field('longitude', (float)$row['longitude'], $post_id);
        }

        // Main photo
        if (!empty($row['main_photo_url'])) {
            $this->import_photo($post_id, $row['main_photo_url'], 'main_photo');
        }

        // Find and assign agent
        if (!empty($row['agent_email'])) {
            $agent_user = get_user_by('email', $row['agent_email']);
            if ($agent_user) {
                update_field('agent', $agent_user->ID, $post_id);
            }
        }
    }

    /**
     * Import photo from URL
     */
    private function import_photo(int $post_id, string $photo_url, string $field_name): void {
        if (!filter_var($photo_url, FILTER_VALIDATE_URL)) {
            return;
        }

        // Download and attach image
        $upload_dir = wp_upload_dir();
        $image_data = wp_remote_get($photo_url, ['timeout' => 30]);

        if (is_wp_error($image_data) || wp_remote_retrieve_response_code($image_data) !== 200) {
            return;
        }

        $image_content = wp_remote_retrieve_body($image_data);
        $filename = basename(parse_url($photo_url, PHP_URL_PATH));
        
        if (empty($filename)) {
            $filename = 'imported-image-' . time() . '.jpg';
        }

        $file_path = $upload_dir['path'] . '/' . $filename;
        file_put_contents($file_path, $image_content);

        // Create attachment
        $attachment = [
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => wp_check_filetype($filename)['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);

        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);

            // Set as featured image or custom field
            if ($field_name === 'main_photo') {
                set_post_thumbnail($post_id, $attachment_id);
                update_field('main_photo', $attachment_id, $post_id);
            } else {
                update_field($field_name, $attachment_id, $post_id);
            }
        }
    }
}