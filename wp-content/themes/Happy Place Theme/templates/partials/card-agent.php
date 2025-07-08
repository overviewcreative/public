<?php
/**
 * Template part for displaying agent cards
 *
 * @package HappyPlace
 */

$agent_id = get_the_ID();
$phone = get_post_meta($agent_id, '_phone', true);
$email = get_post_meta($agent_id, '_email', true);
$listings_count = count_user_posts($agent_id, 'listing');
?>

<article <?php post_class('agent-card'); ?>>
    <div class="agent-card__image">
        <?php if (has_post_thumbnail()) : ?>
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('agent-thumbnail'); ?>
            </a>
        <?php else : ?>
            <img src="<?php echo get_theme_file_uri('assets/images/placeholder-agent.jpg'); ?>" alt="Agent placeholder">
        <?php endif; ?>
    </div>

    <div class="agent-card__content">
        <h3 class="agent-card__name">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <?php if ($license = get_post_meta($agent_id, '_license_number', true)) : ?>
            <div class="agent-card__license">License #: <?php echo esc_html($license); ?></div>
        <?php endif; ?>

        <div class="agent-card__meta">
            <?php if ($phone) : ?>
                <div class="agent-card__contact">
                    <i class="fas fa-phone"></i>
                    <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                </div>
            <?php endif; ?>

            <?php if ($email) : ?>
                <div class="agent-card__contact">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                </div>
            <?php endif; ?>

            <div class="agent-card__listings">
                <i class="fas fa-home"></i>
                <?php echo sprintf(_n('%d Listing', '%d Listings', $listings_count, 'happy-place'), $listings_count); ?>
            </div>
        </div>
    </div>
</article>
