<?php
/**
 * Template Name: Single Agent
 * 
 * This is the template for displaying a single agent profile.
 * 
 * @package HappyPlace
 */

get_header();
?>

<main id="primary" class="site-main">
    <article id="post-<?php the_ID(); ?>" <?php post_class('agent-single'); ?>>
        <div class="agent-hero">
            <div class="container">
                <div class="agent-hero__content">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="agent-hero__image">
                            <?php the_post_thumbnail('agent-large'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="agent-hero__info">
                        <h1 class="agent-hero__name"><?php the_title(); ?></h1>
                        
                        <?php if ($title = get_field('title')) : ?>
                            <p class="agent-hero__title"><?php echo esc_html($title); ?></p>
                        <?php endif; ?>
                        
                        <div class="agent-hero__contact">
                            <?php if ($phone = get_field('phone')) : ?>
                                <p class="agent-hero__phone">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>">
                                        <?php echo esc_html($phone); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($email = get_field('email')) : ?>
                                <p class="agent-hero__email">
                                    <i class="fas fa-envelope"></i>
                                    <a href="mailto:<?php echo esc_attr($email); ?>">
                                        <?php echo esc_html($email); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($social = get_field('social_media')) : ?>
                                <div class="agent-hero__social">
                                    <?php foreach ($social as $platform => $url) : ?>
                                        <?php if ($url) : ?>
                                            <a href="<?php echo esc_url($url); ?>" class="social-link social-link--<?php echo esc_attr($platform); ?>" target="_blank" rel="noopener noreferrer">
                                                <i class="fab fa-<?php echo esc_attr($platform); ?>"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="agent-content">
                <div class="agent-content__main">
                    <?php if ($bio = get_field('biography')) : ?>
                        <div class="agent-bio">
                            <h2>About <?php the_title(); ?></h2>
                            <?php echo wp_kses_post($bio); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($specialties = get_field('specialties')) : ?>
                        <div class="agent-specialties">
                            <h2>Specialties</h2>
                            <ul class="agent-specialties__list">
                                <?php foreach ($specialties as $specialty) : ?>
                                    <li><?php echo esc_html($specialty); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Get agent's active listings
                    $listings_args = array(
                        'post_type' => 'listing',
                        'posts_per_page' => 6,
                        'meta_query' => array(
                            array(
                                'key' => 'listing_agent',
                                'value' => get_the_ID(),
                                'compare' => '='
                            )
                        )
                    );
                    
                    $listings_query = new WP_Query($listings_args);
                    
                    if ($listings_query->have_posts()) :
                        ?>
                        <div class="agent-listings">
                            <h2>Active Listings</h2>
                            <div class="listings-grid">
                                <?php
                                while ($listings_query->have_posts()) :
                                    $listings_query->the_post();
                                    get_template_part('templates/partials/card', 'listing');
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                            <p class="text-center">
                                <a href="<?php echo esc_url(add_query_arg('agent', get_the_ID(), get_post_type_archive_link('listing'))); ?>" class="btn btn-primary">
                                    View All Listings
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="agent-content__sidebar">
                    <?php get_template_part('templates/partials/contact-form', 'agent'); ?>
                </aside>
            </div>
        </div>
    </article>
</main>

<?php
get_footer();
