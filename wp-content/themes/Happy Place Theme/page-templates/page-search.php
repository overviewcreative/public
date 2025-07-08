<?php
/**
 * Template Name: Search Page
 * Description: A custom template for the property search page with advanced filters
 */

get_header();
?>

<div class="hph-container">
    <div class="hph-search-page">
        <div class="hph-search-filters">
            <?php get_template_part('templates/partials/property-filters'); ?>
        </div>

        <div class="hph-search-results">
            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $search_query = array(
                'post_type' => 'listing',
                'posts_per_page' => get_option('posts_per_page'),
                'paged' => $paged
            );

            // Add filters based on GET parameters
            if (isset($_GET['property_type'])) {
                $search_query['meta_query'][] = array(
                    'key' => 'property_type',
                    'value' => sanitize_text_field($_GET['property_type']),
                    'compare' => '='
                );
            }

            if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
                $search_query['meta_query'][] = array(
                    'key' => 'price',
                    'value' => intval($_GET['min_price']),
                    'type' => 'NUMERIC',
                    'compare' => '>='
                );
            }

            if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
                $search_query['meta_query'][] = array(
                    'key' => 'price',
                    'value' => intval($_GET['max_price']),
                    'type' => 'NUMERIC',
                    'compare' => '<='
                );
            }

            if (isset($_GET['beds']) && !empty($_GET['beds'])) {
                $search_query['meta_query'][] = array(
                    'key' => 'bedrooms',
                    'value' => intval($_GET['beds']),
                    'type' => 'NUMERIC',
                    'compare' => '>='
                );
            }

            if (isset($_GET['baths']) && !empty($_GET['baths'])) {
                $search_query['meta_query'][] = array(
                    'key' => 'bathrooms',
                    'value' => intval($_GET['baths']),
                    'type' => 'NUMERIC',
                    'compare' => '>='
                );
            }

            if (!empty($search_query['meta_query'])) {
                $search_query['meta_query']['relation'] = 'AND';
            }

            $query = new WP_Query($search_query);

            if ($query->have_posts()) :
            ?>
                <div class="hph-grid hph-grid-3">
                    <?php
                    while ($query->have_posts()) :
                        $query->the_post();
                        get_template_part('templates/partials/card', 'listing');
                    endwhile;
                    ?>
                </div>

                <?php get_template_part('templates/partials/pagination'); ?>

            <?php else : ?>
                <?php get_template_part('templates/partials/no-results'); ?>
            <?php
            endif;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
