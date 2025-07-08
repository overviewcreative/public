<?php
/**
 * Property Card Template Part
 *
 * @package HappyPlace
 */

$price = get_field('property_price');
$address = get_field('property_address');
$details = get_field('property_details');
$gallery = get_field('property_gallery');
$status = wp_get_post_terms(get_the_ID(), 'property_status', array('fields' => 'names'));
?>

<article class="hph-property-card">
    <div class="hph-property-card-image">
        <?php if ($gallery && isset($gallery[0])) : ?>
            <img src="<?php echo esc_url($gallery[0]['url']); ?>" alt="<?php the_title(); ?>">
        <?php endif; ?>
        <?php if (!empty($status)) : ?>
            <div class="hph-property-status">
                <?php echo esc_html($status[0]); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="hph-property-card-content">
        <h3 class="hph-property-card-price">
            <?php echo esc_html(hph_format_price($price)); ?>
        </h3>
        
        <div class="hph-property-card-address">
            <?php echo esc_html($address); ?>
        </div>

        <div class="hph-property-card-meta">
            <?php if (!empty($details['bedrooms'])) : ?>
                <div class="hph-property-meta-item">
                    <i class="fas fa-bed"></i>
                    <span><?php echo esc_html($details['bedrooms']); ?> <?php _e('beds', 'happy-place'); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($details['bathrooms'])) : ?>
                <div class="hph-property-meta-item">
                    <i class="fas fa-bath"></i>
                    <span><?php echo esc_html($details['bathrooms']); ?> <?php _e('baths', 'happy-place'); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($details['square_footage'])) : ?>
                <div class="hph-property-meta-item">
                    <i class="fas fa-vector-square"></i>
                    <span><?php echo number_format($details['square_footage']); ?> <?php _e('sq ft', 'happy-place'); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <a href="<?php the_permalink(); ?>" class="hph-property-card-link" aria-label="<?php esc_attr_e('View property details', 'happy-place'); ?>"></a>
</article>
