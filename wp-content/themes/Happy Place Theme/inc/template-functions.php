<?php

/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package Happy_Place_Theme
 */

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function happy_place_pingback_header()
{
    if (is_singular() && pings_open()) {
        printf('<link rel="pingback" href="%s">', esc_url(get_bloginfo('pingback_url')));
    }
}
add_action('wp_head', 'happy_place_pingback_header');

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function happy_place_body_classes($classes)
{
    // Adds a class of hfeed to non-singular pages.
    if (!is_singular()) {
        $classes[] = 'hfeed';
    }

    // Adds a class if no sidebar is present.
    if (!is_active_sidebar('sidebar-1')) {
        $classes[] = 'no-sidebar';
    }

    return $classes;
}
add_filter('body_class', 'happy_place_body_classes');

/**
 * Disable emoji support
 */
function happy_place_disable_emojis()
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'happy_place_disable_emojis');

// Dashboard assets are now handled by HPH_Assets_Manager class

/**
 * Get listing data for map marker
 */
function hph_get_listing_map_data($post_id)
{
    $status = get_field('status', $post_id);
    $status = is_array($status) ? $status[0] : $status;

    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'price' => get_field('price', $post_id),
        'bedrooms' => get_field('bedrooms', $post_id),
        'bathrooms' => hph_get_listing_bathrooms($post_id),
        'square_footage' => get_field('square_footage', $post_id),
        'status' => $status,
        'latitude' => floatval(get_field('latitude', $post_id)),
        'longitude' => floatval(get_field('longitude', $post_id)),
        'permalink' => get_permalink($post_id),
        'address' => hph_format_listing_address($post_id),
        'photo' => hph_get_listing_photo($post_id, 'medium'),
        'property_type' => get_field('property_type', $post_id),
        'lot_size' => get_field('lot_size', $post_id),
        'year_built' => get_field('year_built', $post_id),
        'price_per_sqft' => get_field('price_per_sqft', $post_id),
        'highlight_badges' => get_field('highlight_badges', $post_id),
    ];
}

/**
 * Add Dashboard Page Template
 */
function hph_add_dashboard_page_template($templates)
{
    $templates['agent-dashboard.php'] = __('Agent Dashboard', 'happy-place');
    return $templates;
}
add_filter('theme_page_templates', 'hph_add_dashboard_page_template');

/**
 * Get dashboard URL for specific section
 */
function hph_get_dashboard_url($section = 'overview')
{
    $dashboard_page_id = get_option('hph_dashboard_page_id');
    if (!$dashboard_page_id) {
        return home_url('/dashboard/');
    }

    $url = get_permalink($dashboard_page_id);
    if ($section !== 'overview') {
        $url = add_query_arg('section', $section, $url);
    }

    return $url;
}

/**
 * Get current dashboard section
 */
function hph_get_dashboard_section()
{
    return isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'overview';
}

/**
 * Check if user can access dashboard
 */
function hph_can_access_dashboard($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    return user_can($user_id, 'agent') || user_can($user_id, 'administrator');
}

/**
 * Get time-based greeting
 */
function hph_get_time_greeting()
{
    $hour = (int)current_time('H');

    if ($hour < 12) {
        return __('Morning', 'happy-place');
    } elseif ($hour < 17) {
        return __('Afternoon', 'happy-place');
    } else {
        return __('Evening', 'happy-place');
    }
}

/**
 * Check if current page is dashboard
 * 
 * @return bool True if the current page is any type of dashboard page
 */
function hph_is_dashboard(): bool
{
    // Check admin dashboard
    if (is_admin() && function_exists('get_current_screen')) {
        $screen = get_current_screen();
        if ($screen && $screen->base === 'toplevel_page_happy-place-dashboard') {
            return true;
        }
    }

    // Check page templates
    if (is_page_template(['templates/dashboard.php', 'templates/agent-dashboard.php'])) {
        return true;
    }

    // Check URL path for dashboard
    global $wp;
    if ($wp && isset($wp->request)) {
        $current_url = trailingslashit(home_url($wp->request));
        if (strpos($current_url, '/agent-dashboard/') !== false || strpos($current_url, '/dashboard/') !== false) {
            return true;
        }
    }

    // Check query vars
    if (get_query_var('happy_place_dashboard', false) || get_query_var('agent_dashboard', false)) {
        return true;
    }

    return false;
}
