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

<div class="hph-container">
    <article id="post-<?php the_ID(); ?>" <?php post_class('hph-single-listing'); ?>>
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <header class="hph-listing-header">
                <div class="hph-listing-gallery">
                    <?php 
                    $gallery = get_field('gallery');
                    if ($gallery): ?>
                        <div class="property-gallery">
                            <?php foreach ($gallery as $image): ?>
                                <div class="gallery-item">
                                    <img src="<?php echo esc_url($image['url']); ?>" 
                                         alt="<?php echo esc_attr($image['alt']); ?>"
                                         class="hph-listing-image">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="hph-listing-details">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    <div class="hph-listing-meta">
                        <div class="hph-badge hph-badge-primary">
                            <?php echo HPH_Theme::format_price(get_field('price')); ?>
                        </div>
                        <div class="hph-listing-specs">
                            <span><i class="fas fa-bed"></i> <?php echo get_field('bedrooms'); ?> beds</span>
                            <span><i class="fas fa-bath"></i> <?php echo get_field('bathrooms'); ?> baths</span>
                            <span><i class="fas fa-ruler-combined"></i> <?php echo number_format(get_field('square_feet')); ?> sq ft</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="hph-card hph-listing-content">
                <?php the_content(); ?>
                
                <div class="hph-listing-features">
                    <h2><?php _e('Features', 'happy-place'); ?></h2>
                    <?php 
                    $features = get_field('features');
                    if ($features): ?>
                        <ul>
                            <?php foreach ($features as $feature): ?>
                                <li><i class="fas fa-check"></i> <?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <?php if (get_field('location')): ?>
                    <div class="hph-listing-map">
                        <h2><?php _e('Location', 'happy-place'); ?></h2>
                        <div id="property-map" 
                             data-lat="<?php echo esc_attr(get_field('location')['lat']); ?>" 
                             data-lng="<?php echo esc_attr(get_field('location')['lng']); ?>"
                             class="hph-map"></div>
                    </div>
                <?php endif; ?>
            </div>

            <footer class="hph-card">
                <?php 
                $agent_id = get_field('agent');
                if ($agent_id): ?>
                    <div class="hph-listing-agent">
                        <h2><?php _e('Contact Agent', 'happy-place'); ?></h2>
                        <div class="hph-agent-info">
                            <?php 
                            $agent = get_post($agent_id);
                            if ($agent): ?>
                                <div class="hph-agent-avatar">
                                    <?php echo get_the_post_thumbnail($agent->ID, 'agent-avatar'); ?>
                                </div>
                                <div class="hph-agent-details">
                                    <h3><?php echo get_the_title($agent->ID); ?></h3>
                                    <p><i class="fas fa-phone"></i> <?php echo get_field('phone', $agent->ID); ?></p>
                                    <p><i class="fas fa-envelope"></i> <?php echo get_field('email', $agent->ID); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <form class="hph-form" id="agent-contact-form">
                            <?php wp_nonce_field('contact_agent', 'contact_nonce'); ?>
                            <input type="hidden" name="property_id" value="<?php the_ID(); ?>">
                            <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>">
                            
                            <div class="hph-form-group">
                                <label class="hph-form-label"><?php _e('Your Name', 'happy-place'); ?></label>
                                <input type="text" name="name" class="hph-form-input" required>
                            </div>
                            <div class="hph-form-group">
                                <label class="hph-form-label"><?php _e('Your Email', 'happy-place'); ?></label>
                                <input type="email" name="email" class="hph-form-input" required>
                            </div>
                            <div class="hph-form-group">
                                <label class="hph-form-label"><?php _e('Your Message', 'happy-place'); ?></label>
                                <textarea name="message" class="hph-form-textarea" required></textarea>
                            </div>
                            <button type="submit" class="hph-btn hph-btn-primary">
                                <?php _e('Send Message', 'happy-place'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </footer>
        <?php endwhile; endif; ?>
    </article>
</div>

<?php
get_sidebar();
get_footer();
