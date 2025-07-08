<?php
/**
 * Content Template for Transactions
 *
 * @package HappyPlace
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'transaction-item' ); ?>>
    <header class="entry-header">
        <?php
        if ( is_singular() ) :
            the_title( '<h1 class="entry-title">', '</h1>' );
        else :
            the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
        endif;

        // Transaction meta information
        $transaction_date = get_post_meta( get_the_ID(), 'transaction_date', true );
        $transaction_type = get_post_meta( get_the_ID(), 'transaction_type', true );
        $transaction_amount = get_post_meta( get_the_ID(), 'transaction_amount', true );
        ?>
        
        <div class="transaction-meta">
            <?php if ( $transaction_date ) : ?>
                <span class="transaction-date"><?php echo esc_html( $transaction_date ); ?></span>
            <?php endif; ?>
            
            <?php if ( $transaction_type ) : ?>
                <span class="transaction-type"><?php echo esc_html( $transaction_type ); ?></span>
            <?php endif; ?>
            
            <?php if ( $transaction_amount ) : ?>
                <span class="transaction-amount"><?php echo esc_html( $transaction_amount ); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="transaction-image">
            <?php the_post_thumbnail( 'large' ); ?>
        </div>
    <?php endif; ?>

    <div class="entry-content">
        <?php
        if ( is_singular() ) :
            the_content();
        else :
            the_excerpt();
        endif;
        ?>
    </div>
</article>
