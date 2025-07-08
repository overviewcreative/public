<?php
/**
 * Template part for displaying the map view
 * 
 * @package HappyPlace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="map-container" class="hph-map-view">
    <div id="listings-map" class="hph-full-map"></div>
    <div id="map-listings-preview" class="hph-map-listings-preview">
        <?php if (have_posts()) : while (have_posts()) : the_post(); 
            $price = get_field('price');
            $beds = get_field('bedrooms');
            $baths = get_field('bathrooms');
            $sqft = get_field('square_footage');
            $lat = get_field('latitude');
            $lng = get_field('longitude');

            $thumbnail = '';
            if (function_exists('has_post_thumbnail') && has_post_thumbnail()) {
                $thumbnail = get_the_post_thumbnail(get_the_ID(), 'thumbnail');
            }
        ?>
            <div class="map-preview-card" data-id="<?php echo get_the_ID(); ?>" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>">
                <div class="map-preview-image">
                    <?php if ($thumbnail) : ?>
                        <?php echo $thumbnail; ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/placeholder.jpg'); ?>" alt="No image available">
                    <?php endif; ?>
                </div>
                <div class="map-preview-details">
                    <h4><?php echo get_the_title(); ?></h4>
                    <?php if ($price) : ?>
                        <div class="map-preview-price">
                            $<?php echo number_format(floatval($price)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="map-preview-meta">
                        <?php if ($beds) : ?>
                            <span><?php echo esc_html($beds); ?> BD</span>
                        <?php endif; ?>
                        <?php if ($baths) : ?>
                            <span><?php echo esc_html($baths); ?> BA</span>
                        <?php endif; ?>
                        <?php if ($sqft) : ?>
                            <span><?php echo number_format(floatval($sqft)); ?> Ft²</span>
                        <?php endif; ?>
                    </div>
                    <div class="map-preview-actions">
                        <a href="<?php echo get_permalink(); ?>" class="hph-btn hph-btn-sm hph-btn-secondary">View Details</a>
                        <?php if (function_exists('is_user_logged_in') && is_user_logged_in()) : ?>
                            <button class="hph-btn-favorite" data-listing-id="<?php echo get_the_ID(); ?>">
                                <i class="icon-heart"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>
</div>
