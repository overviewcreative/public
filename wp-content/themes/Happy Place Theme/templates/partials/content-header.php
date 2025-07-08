<?php
/**
 * Template part for displaying the page header content
 */
?>

<header class="hph-content-header">
    <?php if (is_archive()) : ?>
        <h1 class="page-title">
            <?php
            if (is_post_type_archive('listing')) {
                esc_html_e('Properties', 'happy-place');
            } elseif (is_post_type_archive('agent')) {
                esc_html_e('Our Agents', 'happy-place');
            } elseif (is_post_type_archive('community')) {
                esc_html_e('Communities', 'happy-place');
            } elseif (is_post_type_archive('city')) {
                esc_html_e('Cities', 'happy-place');
            } else {
                the_archive_title();
            }
            ?>
        </h1>
        <?php the_archive_description(); ?>
    <?php elseif (is_search()) : ?>
        <h1 class="page-title">
            <?php
            printf(
                /* translators: %s: search query */
                esc_html__('Search Results for: %s', 'happy-place'),
                '<span>' . get_search_query() . '</span>'
            );
            ?>
        </h1>
    <?php elseif (is_404()) : ?>
        <h1 class="page-title"><?php esc_html_e('Page Not Found', 'happy-place'); ?></h1>
    <?php else : ?>
        <h1 class="page-title"><?php the_title(); ?></h1>
    <?php endif; ?>
</header>
