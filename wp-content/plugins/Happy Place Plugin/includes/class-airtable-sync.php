<?php
/**
 * Airtable Sync functionality
 *
 * @package Happy_Place_Plugin
 */

defined('ABSPATH') || exit;

class Happy_Place_Airtable_Sync {
    private $base_id;
    private $api_key;
    private $rate_limiter;

    public function __construct() {
        $this->base_id = get_option('happy_place_airtable_base_id');
        $this->api_key = get_option('happy_place_airtable_api_key');
        $this->rate_limiter = new Happy_Place_Rate_Limiter();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('happy_place_sync_cron', array($this, 'sync_all_cpts'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=happy_place',
            'Airtable Sync Settings',
            'Airtable Sync',
            'manage_options',
            'happy-place-airtable',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('happy-place-airtable', 'happy_place_airtable_base_id');
        register_setting('happy-place-airtable', 'happy_place_airtable_api_key');
        register_setting('happy-place-airtable', 'happy_place_airtable_sync_interval');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Airtable Sync Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('happy-place-airtable');
                do_settings_sections('happy-place-airtable');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Airtable Base ID</th>
                        <td>
                            <input type="text" name="happy_place_airtable_base_id" value="<?php echo esc_attr(get_option('happy_place_airtable_base_id')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Airtable API Key</th>
                        <td>
                            <input type="password" name="happy_place_airtable_api_key" value="<?php echo esc_attr(get_option('happy_place_airtable_api_key')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sync Interval (hours)</th>
                        <td>
                            <input type="number" name="happy_place_airtable_sync_interval" value="<?php echo esc_attr(get_option('happy_place_airtable_sync_interval', '24')); ?>" min="1" max="168" class="small-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr>
            <h2>Manual Sync</h2>
            <p>
                <button class="button button-primary" id="happy-place-manual-sync">Sync Now</button>
                <span class="spinner" style="float: none; margin-left: 10px;"></span>
            </p>
            <div id="sync-status"></div>
        </div>
        <?php
    }

    public function sync_all_cpts() {
        if (empty($this->base_id) || empty($this->api_key)) {
            return new WP_Error('missing_credentials', 'Airtable credentials are not configured.');
        }

        $post_types = array('happy_place'); // Add more CPTs as needed
        
        foreach ($post_types as $post_type) {
            $this->sync_post_type($post_type);
        }
    }

    private function sync_post_type($post_type) {
        // Get all posts of this type
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));

        $records = array();
        foreach ($posts as $post) {
            $record = $this->prepare_record_from_post($post);
            $records[] = $record;
        }

        // Chunk records into groups of 10 (Airtable's limit)
        $chunks = array_chunk($records, 10);

        foreach ($chunks as $chunk) {
            $this->rate_limiter->throttle();
            $this->send_records_to_airtable($chunk, $post_type);
        }
    }

    private function prepare_record_from_post($post) {
        $record = array(
            'fields' => array(
                'Post ID' => $post->ID,
                'Title' => $post->post_title,
                'Content' => $post->post_content,
                'Status' => $post->post_status,
                'Modified' => $post->post_modified_gmt
            )
        );

        // Add custom fields
        $custom_fields = get_post_custom($post->ID);
        foreach ($custom_fields as $key => $values) {
            if (strpos($key, '_') !== 0) { // Skip hidden fields
                $record['fields'][$key] = is_array($values) ? reset($values) : $values;
            }
        }

        return $record;
    }

    private function send_records_to_airtable($records, $table_name) {
        $url = sprintf('https://api.airtable.com/v0/%s/%s', $this->base_id, urlencode($table_name));
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('records' => $records)),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('Airtable sync error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body;
    }
}

class Happy_Place_Rate_Limiter {
    private $requests = array();
    private $max_requests_per_second = 5;

    public function throttle() {
        $now = time();
        $this->requests = array_filter($this->requests, function($time) use ($now) {
            return ($now - $time) < 1;
        });

        if (count($this->requests) >= $this->max_requests_per_second) {
            sleep(1);
            return $this->throttle();
        }

        $this->requests[] = $now;
    }
}

// Initialize the class
new Happy_Place_Airtable_Sync();
