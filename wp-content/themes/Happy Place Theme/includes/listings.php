<?php
/**
 * Listing Functions
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modify the main query on the listings archive
 */
function hph_modify_listings_query($query) {
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('listing')) {
        // Default sort order
        $sort = get_query_var('sort', 'newest');
        switch ($sort) {
            case 'price-low':
                $query->set('meta_key', 'price');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'ASC');
                break;
            case 'price-high':
                $query->set('meta_key', 'price');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                break;
            case 'largest':
                $query->set('meta_key', 'square_footage');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                break;
            default: // newest
                $query->set('orderby', 'date');
                $query->set('order', 'DESC');
        }

        // Property type filter
        $property_type = get_query_var('property_type');
        if ($property_type && $property_type !== 'all') {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'property_type',
                    'field'    => 'slug',
                    'terms'    => $property_type
                )
            ));
        }

        // Price range filter
        $price_range = get_query_var('price_range');
        if ($price_range && $price_range !== 'any') {
            $price_query = array();
            switch ($price_range) {
                case 'under-500k':
                    $price_query = array(
                        'key' => 'price',
                        'value' => 500000,
                        'type' => 'NUMERIC',
                        'compare' => '<='
                    );
                    break;
                case '500k-800k':
                    $price_query = array(
                        'key' => 'price',
                        'value' => array(500000, 800000),
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    );
                    break;
                case '800k-1m':
                    $price_query = array(
                        'key' => 'price',
                        'value' => array(800000, 1000000),
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    );
                    break;
                case 'over-1m':
                    $price_query = array(
                        'key' => 'price',
                        'value' => 1000000,
                        'type' => 'NUMERIC',
                        'compare' => '>='
                    );
                    break;
            }
            
            if ($price_query) {
                $query->set('meta_query', array($price_query));
            }
        }

        // Bedrooms filter
        $bedrooms = get_query_var('bedrooms');
        if ($bedrooms && $bedrooms !== 'any') {
            $beds_query = array(
                'key' => 'bedrooms',
                'value' => intval($bedrooms),
                'type' => 'NUMERIC',
                'compare' => '>='
            );
            
            $meta_query = $query->get('meta_query');
            if (!$meta_query) {
                $meta_query = array();
            }
            $meta_query[] = $beds_query;
            $query->set('meta_query', $meta_query);
        }

        // Features filter
        $features = (array) get_query_var('features', array());
        if (!empty($features)) {
            $tax_query = $query->get('tax_query');
            if (!$tax_query) {
                $tax_query = array();
            }
            $tax_query[] = array(
                'taxonomy' => 'property_feature',
                'field'    => 'slug',
                'terms'    => $features,
                'operator' => 'AND'
            );
            $query->set('tax_query', $tax_query);
        }

        // Search query
        $search = get_query_var('search');
        if ($search) {
            $query->set('s', $search);
        }
    }
}
add_action('pre_get_posts', 'hph_modify_listings_query');

/**
 * Register custom query vars for filtering listings
 */
function hph_add_listing_query_vars($vars) {
    $vars[] = 'price_range';
    $vars[] = 'property_type';
    $vars[] = 'bedrooms';
    $vars[] = 'features';
    $vars[] = 'sort';
    $vars[] = 'search';
    return $vars;
}
add_filter('query_vars', 'hph_add_listing_query_vars');

/**
 * Format price for display
 */
function hph_format_price($price) {
    if (!$price) {
        return '';
    }
    return '$' . number_format(floatval($price));
}
