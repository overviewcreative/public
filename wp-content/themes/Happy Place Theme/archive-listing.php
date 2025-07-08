<?php
/**
 * The template for displaying listing archives
 *
 * @package Happy_Place_Theme
 */

get_header();
?>

<div class="container">
    <div class="listings-header">
        <h1 class="page-title">
            <?php
            if (is_tax()) {
                single_term_title();
            } else {
                post_type_archive_title();
            }
            ?>
        </h1>
        
        <div class="listings-filters">
            <?php get_template_part('template-parts/listings-filter'); ?>
        </div>
    </div>

    <?php if (have_posts()) : ?>
        <div class="listings-grid">
            <?php
            while (have_posts()) :
                the_post();
                get_template_part('template-parts/content', 'listing-card');
            endwhile;
            ?>
        </div>

        <?php
        the_posts_pagination(array(
            'prev_text' => __('Previous page', 'happy-place-theme'),
            'next_text' => __('Next page', 'happy-place-theme'),
        ));
        ?>

    <?php else : ?>
        <?php get_template_part('template-parts/content', 'none'); ?>
    <?php endif; ?>
</div>

<?php
get_footer();
