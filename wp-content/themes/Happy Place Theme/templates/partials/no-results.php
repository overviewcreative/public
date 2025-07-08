<?php
/**
 * Template part for displaying a message when posts are not found
 */
?>

<div class="hph-no-results">
    <h2><?php esc_html_e('Nothing Found', 'happy-place'); ?></h2>
    
    <?php if (is_search()) : ?>
        <p><?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'happy-place'); ?></p>
        <?php get_template_part('templates/partials/search-form'); ?>
    <?php elseif (is_archive()) : ?>
        <p><?php esc_html_e('Sorry, but there are no items to display at this time.', 'happy-place'); ?></p>
    <?php else : ?>
        <p><?php esc_html_e('It seems we can\'t find what you\'re looking for. Perhaps searching can help.', 'happy-place'); ?></p>
        <?php get_template_part('templates/partials/search-form'); ?>
    <?php endif; ?>
</div>
