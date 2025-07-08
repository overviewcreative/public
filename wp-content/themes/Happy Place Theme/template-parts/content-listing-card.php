<?php
/**
 * Template part for displaying listing cards
 *
 * @package Happy_Place_Theme
 */

$price = get_field('price');
$beds = get_field('bedrooms');
$baths = get_field('bathrooms');
$sqft = get_field('square_feet');
$status = get_field('status');
?>

<article <?php post_class('hph-listing-card'); ?>>
    <div class="hph-listing-thumbnail">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('listing-thumb'); ?>
        <?php endif; ?>
        <?php if ($status) : ?>
            <div class="hph-badge hph-badge-primary">
                <?php echo esc_html($status); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="hph-listing-details">
        <h3>
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        
        <?php if ($price) : ?>
            <div class="hph-listing-price">
                <?php echo esc_html('$' . number_format($price)); ?>
            </div>
        <?php endif; ?>

        <div class="hph-listing-meta">
            <?php if ($beds) : ?>
                <span>
                    <i class="fas fa-bed"></i> <?php echo esc_html($beds); ?> beds
                </span>
            <?php endif; ?>

            <?php if ($baths) : ?>
                <span class="listing-card__detail">
                    <i class="fas fa-bath"></i> <?php echo esc_html($baths); ?> baths
                </span>
            <?php endif; ?>

            <?php if ($sqft) : ?>
                <span class="listing-card__detail">
                    <i class="fas fa-ruler-combined"></i> <?php echo esc_html(number_format($sqft)); ?> sq ft
                </span>
            <?php endif; ?>
        </div>

        <div class="listing-card__footer">
            <?php
            $location = [];
            if ($city = get_field('city')) {
                $location[] = $city;
            }
            if ($state = get_field('state')) {
                $location[] = $state;
            }
            if (!empty($location)) :
            ?>
                <div class="listing-card__location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo esc_html(implode(', ', $location)); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>
