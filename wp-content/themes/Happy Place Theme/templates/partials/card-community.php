<?php
/**
 * Template part for displaying community cards
 *
 * @package HappyPlace
 */

$community_id = get_the_ID();
$location = get_post_meta($community_id, '_location', true);
$listings_count = count_posts_in_community($community_id);
$stats = get_community_stats($community_id); // Function to get average price, total homes, etc.
?>

<article <?php post_class('community-card'); ?>>
    <div class="community-card__image">
        <a href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('community-card'); ?>
            <?php else : ?>
                <img src="<?php echo get_theme_file_uri('assets/images/placeholder-community.jpg'); ?>" 
                     alt="<?php echo esc_attr(get_the_title()); ?> community image">
            <?php endif; ?>
        </a>

        <?php if ($listings_count > 0) : ?>
            <div class="community-card__badge">
                <?php echo sprintf(_n('%d Listing', '%d Listings', $listings_count, 'happy-place'), $listings_count); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="community-card__content">
        <h3 class="community-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <?php if ($location) : ?>
            <div class="community-card__location">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo esc_html($location); ?>
            </div>
        <?php endif; ?>

        <?php if ($stats) : ?>
            <div class="community-card__stats">
                <?php if (!empty($stats['avg_price'])) : ?>
                    <div class="community-card__stat">
                        <i class="fas fa-home"></i>
                        <span>Avg. <?php echo esc_html($stats['avg_price']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($stats['total_homes'])) : ?>
                    <div class="community-card__stat">
                        <i class="fas fa-building"></i>
                        <span><?php echo sprintf(_n('%d Home', '%d Homes', $stats['total_homes'], 'happy-place'), $stats['total_homes']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($stats['avg_sqft'])) : ?>
                    <div class="community-card__stat">
                        <i class="fas fa-ruler-combined"></i>
                        <span>Avg. <?php echo esc_html($stats['avg_sqft']); ?> sqft</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</article>
