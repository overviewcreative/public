<?php
/**
 * Template Name: Single Listing
 * 
 * This is the template for displaying a single listing.
 * 
 * @package HappyPlace
 */

get_header();
?>

<main id="primary" class="site-main">
    <article id="post-<?php the_ID(); ?>" <?php post_class('listing-single'); ?>>
        <?php 
        $price = get_field('price');
        $bedrooms = get_field('bedrooms');
        $bathrooms = get_field('bathrooms');
        $square_footage = get_field('square_footage');
        $lot_size = get_field('lot_size');
        $year_built = get_field('year_built');
        $property_type = get_field('property_type');
        $virtual_tour_link = get_field('virtual_tour_link');
        $interior_features = get_field('interior_features');
        $exterior_features = get_field('exterior_features');
        $utility_features = get_field('utility_features');
        $highlight_badges = get_field('highlight_badges');
        $gallery = get_field('photo_gallery');
        $agent = get_field('agent');
        $community = get_field('community');
        ?>

        <div class="listing-hero">
            <?php if (has_post_thumbnail()) : ?>
                <div class="listing-hero__image">
                    <?php the_post_thumbnail('listing-hero'); ?>
                </div>
            <?php endif; ?>
            
            <div class="listing-hero__content container">
                <?php if (!empty($highlight_badges)) : ?>
                    <div class="listing-hero__badges">
                        <?php foreach ($highlight_badges as $badge) : ?>
                            <span class="hph-badge hph-badge-<?php echo esc_attr($badge); ?>">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $badge))); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <h1 class="listing-hero__title"><?php the_title(); ?></h1>
                <div class="listing-hero__price">
                    $<?php echo number_format($price); ?>
                </div>
            </div>
        </div>

        <?php if ($gallery) : ?>
            <div class="listing-gallery">
                <div class="container">
                    <div class="listing-gallery__grid">
                        <?php foreach ($gallery as $image) : ?>
                            <div class="listing-gallery__item">
                                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="container">
            <div class="listing-content">
                <div class="listing-content__main">
                    <div class="listing-details">
                        <div class="listing-details__grid">
                            <?php if ($bedrooms) : ?>
                                <div class="listing-detail">
                                    <span class="listing-detail__label">Bedrooms</span>
                                    <span class="listing-detail__value"><?php echo esc_html($bedrooms); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($bathrooms) : ?>
                                <div class="listing-detail">
                                    <span class="listing-detail__label">Bathrooms</span>
                                    <span class="listing-detail__value"><?php echo esc_html($bathrooms); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($square_footage) : ?>
                                <div class="listing-detail">
                                    <span class="listing-detail__label">Square Footage</span>
                                    <span class="listing-detail__value"><?php echo number_format($square_footage); ?> Sq Ft</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($lot_size) : ?>
                                <div class="listing-detail">
                                    <span class="listing-detail__label">Lot Size</span>
                                    <span class="listing-detail__value"><?php echo esc_html($lot_size); ?> Acres</span>
                                </div>
                            <?php endif; ?>

                            <?php if ($year_built) : ?>
                                <div class="listing-detail">
                                    <span class="listing-detail__label">Year Built</span>
                                    <span class="listing-detail__value"><?php echo esc_html($year_built); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property_type) : ?>
                                <div class="listing-detail">
                                    <span class="listing-detail__label">Property Type</span>
                                    <span class="listing-detail__value"><?php echo esc_html($property_type); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($virtual_tour_link) : ?>
                            <div class="listing-virtual-tour">
                                <a href="<?php echo esc_url($virtual_tour_link); ?>" class="hph-btn hph-btn-primary" target="_blank">
                                    <i class="icon-360"></i> View Virtual Tour
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="listing-description">
                        <h2>Description</h2>
                        <?php the_content(); ?>
                    </div>

                    <?php if ($interior_features || $exterior_features || $utility_features) : ?>
                        <div class="listing-features">
                            <?php if ($interior_features) : ?>
                                <div class="listing-features__section">
                                    <h3>Interior Features</h3>
                                    <div class="listing-features__content">
                                        <?php echo wp_kses_post($interior_features); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($exterior_features) : ?>
                                <div class="listing-features__section">
                                    <h3>Exterior Features</h3>
                                    <div class="listing-features__content">
                                        <?php echo wp_kses_post($exterior_features); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($utility_features) : ?>
                                <div class="listing-features__section">
                                    <h3>Utility Features</h3>
                                    <div class="listing-features__content">
                                        <?php echo wp_kses_post($utility_features); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="listing-content__sidebar">
                    <?php if ($agent) : ?>
                        <div class="listing-agent">
                            <h3>Listing Agent</h3>
                            <div class="agent-card">
                                <?php if (has_post_thumbnail($agent->ID)) : ?>
                                    <div class="agent-card__image">
                                        <?php echo get_the_post_thumbnail($agent->ID, 'agent-thumbnail'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="agent-card__content">
                                    <h4 class="agent-card__name"><?php echo esc_html($agent->post_title); ?></h4>
                                    <?php if ($phone = get_field('phone', $agent->ID)) : ?>
                                        <p class="agent-card__phone"><?php echo esc_html($phone); ?></p>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo esc_url(get_permalink($agent->ID)); ?>" class="btn btn-primary">
                                        View Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($community) : ?>
                        <div class="listing-community">
                            <h3>Community</h3>
                            <div class="community-card">
                                <?php if (has_post_thumbnail($community->ID)) : ?>
                                    <div class="community-card__image">
                                        <?php echo get_the_post_thumbnail($community->ID, 'community-thumbnail'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="community-card__content">
                                    <h4 class="community-card__name"><?php echo esc_html($community->post_title); ?></h4>
                                    <a href="<?php echo esc_url(get_permalink($community->ID)); ?>" class="btn btn-secondary">
                                        View Community Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php get_template_part('templates/partials/contact-form'); ?>
                </aside>
            </div>
        </div>
    </article>
</main>

<?php
get_footer();
