<?php
/**
 * Single Transaction Template
 *
 * @package HappyPlace
 */

get_header();
?>

<div class="single-transaction">
    <div class="container">
        <?php
        while ( have_posts() ) :
            the_post();
            get_template_part( 'templates/transaction/content', 'transaction' );
        endwhile;
        ?>
    </div>
</div>

<?php
get_footer();
