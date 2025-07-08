<?php
/**
 * Template part for displaying a message when posts cannot be found
 *
 * @package Happy_Place_Theme
 */
?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e('Nothing Found', 'happy-place-theme'); ?></h1>
    </header>

    <div class="page-content">
        <?php
        if (is_search()) :
            ?>
            <p><?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'happy-place-theme'); ?></p>
            <?php
            get_search_form();
        else :
            ?>
            <p><?php esc_html_e('It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'happy-place-theme'); ?></p>
            <?php
            get_search_form();
        endif;
        ?>
    </div>
</section>
