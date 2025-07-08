<?php
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';

// Security check
if (!defined('ABSPATH')) exit;

// Get existing credentials
$credentials = get_option('hph_api_credentials', []);

// Update with new token
$credentials['airtable_api_token'] = 'patw5iSttUZqbYY3U.23af298ca937dcc4caadf053cd13edb6cbbe257f559f42faf14bdf2c58bcdc37';

// Remove old API key if it exists
unset($credentials['airtable_api_key']);

// Save updated credentials
update_option('hph_api_credentials', $credentials);

echo "Token updated successfully!\n";
