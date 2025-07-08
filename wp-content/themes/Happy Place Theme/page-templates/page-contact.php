<?php
/**
 * Template Name: Contact
 * Description: A custom template for the contact page
 */

get_header();
?>

<div class="hph-container">
    <div class="hph-contact-page">
        <div class="hph-contact-info">
            <h2><?php esc_html_e('Get in Touch', 'happy-place'); ?></h2>
            <?php if ($phone = get_theme_mod('contact_phone')) : ?>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:<?php echo esc_attr($phone); ?>"><?php echo esc_html($phone); ?></a>
                </div>
            <?php endif; ?>

            <?php if ($email = get_theme_mod('contact_email')) : ?>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                </div>
            <?php endif; ?>

            <?php if ($address = get_theme_mod('contact_address')) : ?>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <address><?php echo wp_kses_post($address); ?></address>
                </div>
            <?php endif; ?>

            <?php if ($hours = get_theme_mod('business_hours')) : ?>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <div class="business-hours"><?php echo wp_kses_post($hours); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="hph-contact-form">
            <h2><?php esc_html_e('Send Us a Message', 'happy-place'); ?></h2>
            <?php
            if (function_exists('wpcf7_contact_form')) {
                $contact_form_id = get_theme_mod('contact_form_id');
                if ($contact_form_id) {
                    echo do_shortcode('[contact-form-7 id="' . esc_attr($contact_form_id) . '"]');
                }
            }
            ?>
        </div>

        <?php if ($map_embed = get_theme_mod('google_maps_embed')) : ?>
            <div class="hph-contact-map">
                <h2><?php esc_html_e('Our Location', 'happy-place'); ?></h2>
                <div class="map-container">
                    <?php echo wp_kses_post($map_embed); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
