<?php
/**
 * Template part for displaying sort options
 */

$sort = get_query_var('sort', 'newest');
$total_posts = $wp_query->found_posts;
?>

<div class="hph-results-header">
    <div class="hph-results-count">
        <span id="total-properties"><?php echo number_format($total_posts); ?></span> 
        <?php echo _n('Property Found', 'Properties Found', $total_posts, 'happy-place'); ?>
    </div>
    <div class="hph-sort-options">
        <select id="sort-listings" class="hph-form-select">
            <option value="newest" <?php selected($sort, 'newest'); ?>>
                <?php esc_html_e('Newest Listings', 'happy-place'); ?>
            </option>
            <option value="price-low" <?php selected($sort, 'price-low'); ?>>
                <?php esc_html_e('Price: Low to High', 'happy-place'); ?>
            </option>
            <option value="price-high" <?php selected($sort, 'price-high'); ?>>
                <?php esc_html_e('Price: High to Low', 'happy-place'); ?>
            </option>
            <option value="largest" <?php selected($sort, 'largest'); ?>>
                <?php esc_html_e('Largest', 'happy-place'); ?>
            </option>
        </select>
    </div>
</div>
