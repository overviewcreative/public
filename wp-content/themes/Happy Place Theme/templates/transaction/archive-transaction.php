<?php
/**
 * Archive Template for Transactions
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="archive-transactions">
    <div class="container">
        <header class="page-header">
            <h1 class="page-title"><?php post_type_archive_title(); ?></h1>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="transactions-grid">
                <?php
                while ( have_posts() ) :
                    the_post();
                    get_template_part( 'templates/transaction/content', 'transaction' );
                endwhile;
                ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No transactions found.', 'happy-place' ); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
