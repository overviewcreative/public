<?php
/**
 * Template Part: Listing Card
 * 
 * This template part displays a single listing in card format.
 * 
 * @package HappyPlace
 */
?>

<article <?php post_class('listing-card'); ?>>
    <div class="listing-card__image">
        <?php if (has_post_thumbnail()) : ?>
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('listing-card'); ?>
            </a>
        <?php endif; ?>
        
        <div class="listing-card__price">
            <?php echo esc_html(get_field('price')); ?>
        </div>
        
        <?php if ($status = get_field('status')) : ?>
            <div class="listing-card__status listing-card__status--<?php echo esc_attr($status); ?>">
                <?php echo esc_html($status); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="listing-card__content">
        <h2 class="listing-card__title">
            <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
            </a>
        </h2>

        <?php if ($address = get_field('address')) : ?>
            <p class="listing-card__address">
                <?php echo esc_html($address); ?>
            </p>
        <?php endif; ?>

        <div class="listing-card__meta">
            <?php if ($beds = get_field('bedrooms')) : ?>
                <span class="listing-card__beds">
                    <i class="fas fa-bed"></i>
                    <?php echo esc_html($beds); ?> Beds
                </span>
            <?php endif; ?>

            <?php if ($baths = get_field('bathrooms')) : ?>
                <span class="listing-card__baths">
                    <i class="fas fa-bath"></i>
                    <?php echo esc_html($baths); ?> Baths
                </span>
            <?php endif; ?>

            <?php if ($sqft = get_field('square_footage')) : ?>
                <span class="listing-card__sqft">
                    <i class="fas fa-vector-square"></i>
                    <?php echo number_format($sqft); ?> Sq Ft
                </span>
            <?php endif; ?>
        </div>

        <div class="listing-card__actions">
            <a href="<?php the_permalink(); ?>" class="btn btn-primary">View Details</a>
            
            <?php if (is_user_logged_in()) : ?>
                <button class="btn btn-icon favorite-toggle" data-listing="<?php the_ID(); ?>" aria-label="Add to favorites">
                    <i class="far fa-heart"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>
</article>
