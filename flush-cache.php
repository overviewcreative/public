<?php

/**
 * Cache Flush Script - Web Accessible
 * 
 * This script clears various WordPress caches
 * Access via: http://yoursite.local/flush-cache.php
 */

// Security check - only allow in development
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    die('Add ?confirm=yes to the URL to run cache flush');
}

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

if (!defined('ABSPATH')) {
    die('WordPress not loaded properly');
}

// Start output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <title>Cache Flush Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        .success {
            color: green;
        }

        .info {
            color: blue;
        }

        h1 {
            color: #333;
        }
    </style>
</head>

<body>
    <h1>Cache Flush Results</h1>

    <?php

    echo "<p class='info'>Starting cache flush...</p>\n";

    // Clear WordPress object cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        echo "<p class='success'>✓ WordPress object cache flushed</p>\n";
    }

    // Clear all transients
    global $wpdb;
    $transients = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
    foreach ($transients as $transient) {
        delete_option($transient->option_name);
    }
    echo "<p class='success'>✓ All transients cleared (" . count($transients) . " transients)</p>\n";

    // Clear all site transients
    $site_transients = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
    foreach ($site_transients as $transient) {
        delete_option($transient->option_name);
    }
    echo "<p class='success'>✓ All site transients cleared (" . count($site_transients) . " site transients)</p>\n";

    // Clear rewrite rules
    flush_rewrite_rules();
    echo "<p class='success'>✓ Rewrite rules flushed</p>\n";

    // Clear any theme-specific caches
    if (function_exists('hph_clear_all_caches')) {
        hph_clear_all_caches();
        echo "<p class='success'>✓ Happy Place theme caches cleared</p>\n";
    }

    // Update cache timestamp
    update_option('hph_cache_flush_timestamp', time());
    echo "<p class='success'>✓ Cache timestamp updated</p>\n";

    // Clear any plugin caches
    do_action('hph_flush_cache');
    echo "<p class='success'>✓ Plugin caches flushed via action hook</p>\n";

    // Clear theme customizer cache
    if (function_exists('wp_cache_delete')) {
        wp_cache_delete('theme_mods_' . get_stylesheet(), 'options');
        echo "<p class='success'>✓ Theme customizer cache cleared</p>\n";
    }

    // Clear WordPress menu cache
    wp_cache_delete('wp_nav_menu_options', 'options');
    echo "<p class='success'>✓ WordPress menu cache cleared</p>\n";

    echo "<p class='info'><strong>Cache flush completed successfully!</strong></p>\n";
    echo "<p class='info'>Timestamp: " . date('Y-m-d H:i:s') . "</p>\n";

    ?>

    <hr>
    <p><a href="javascript:history.back()">← Go Back</a></p>
    <p><strong>Note:</strong> You may also want to:</p>
    <ul>
        <li>Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)</li>
        <li>Clear any CDN cache if you're using one</li>
        <li>Restart your local development server if needed</li>
    </ul>

    <p><small>Remember to delete this file (flush-cache.php) when done for security.</small></p>
</body>

</html>