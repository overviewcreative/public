<?php
/**
 * Single Property Template
 */

get_header();
?>

<main class="site-main">
    <div class="hph-container">
        <?php while (have_posts()) : the_post(); ?>
            <article class="property-single" style="margin: var(--hph-spacing-2xl) 0;">
                <header style="margin-bottom: var(--hph-spacing-xl);">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <h1 style="margin-bottom: var(--hph-spacing-xs);"><?php the_title(); ?></h1>
                        <div style="text-align: right;">
                            <span class="hph-badge hph-badge-primary" style="margin-bottom: var(--hph-spacing-xs); display: block;">
                                <?php echo esc_html(get_post_meta(get_the_ID(), '_property_type', true)); ?>
                            </span>
                            <div style="font-size: var(--hph-font-size-2xl); font-weight: var(--hph-font-bold); color: var(--hph-color-primary);">
                                <?php echo esc_html(get_post_meta(get_the_ID(), '_price', true)); ?>
                            </div>
                        </div>
                    </div>
                    <p style="color: var(--hph-color-gray-600); font-size: var(--hph-font-size-lg);">
                        <?php echo esc_html(get_post_meta(get_the_ID(), '_address', true)); ?>
                    </p>
                </header>

                <div class="property-gallery" style="margin-bottom: var(--hph-spacing-xl);">
                    <?php 
                    $gallery = get_post_meta(get_the_ID(), '_gallery_images', true);
                    if ($gallery) :
                        echo '<div class="hph-grid hph-grid-2" style="gap: var(--hph-spacing-md);">';
                        foreach ($gallery as $image_id) :
                            echo wp_get_attachment_image($image_id, 'large', false, array('class' => 'hph-card-image'));
                        endforeach;
                        echo '</div>';
                    endif;
                    ?>
                </div>

                <div class="property-details hph-grid hph-grid-2" style="margin-bottom: var(--hph-spacing-xl);">
                    <div class="property-features">
                        <h2 style="margin-bottom: var(--hph-spacing-lg);">Property Features</h2>
                        <div class="hph-grid hph-grid-3" style="gap: var(--hph-spacing-md);">
                            <div>
                                <strong>Bedrooms:</strong>
                                <span><?php echo esc_html(get_post_meta(get_the_ID(), '_bedrooms', true)); ?></span>
                            </div>
                            <div>
                                <strong>Bathrooms:</strong>
                                <span><?php echo esc_html(get_post_meta(get_the_ID(), '_bathrooms', true)); ?></span>
                            </div>
                            <div>
                                <strong>Square Feet:</strong>
                                <span><?php echo esc_html(get_post_meta(get_the_ID(), '_square_feet', true)); ?></span>
                            </div>
                            <div>
                                <strong>Year Built:</strong>
                                <span><?php echo esc_html(get_post_meta(get_the_ID(), '_year_built', true)); ?></span>
                            </div>
                            <div>
                                <strong>Lot Size:</strong>
                                <span><?php echo esc_html(get_post_meta(get_the_ID(), '_lot_size', true)); ?></span>
                            </div>
                            <div>
                                <strong>Garage:</strong>
                                <span><?php echo esc_html(get_post_meta(get_the_ID(), '_garage', true)); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="property-description">
                        <h2 style="margin-bottom: var(--hph-spacing-lg);">Description</h2>
                        <div style="color: var(--hph-color-gray-700);">
                            <?php the_content(); ?>
                        </div>
                    </div>
                </div>

                <div class="property-agent">
                    <h2 style="margin-bottom: var(--hph-spacing-lg);">Contact Agent</h2>
                    <?php
                    $agent_id = get_post_meta(get_the_ID(), '_agent_id', true);
                    if ($agent_id) {
                        get_template_part('templates/partials/agent-contact-card', null, array('agent_id' => $agent_id));
                    }
                    ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer(); ?>
