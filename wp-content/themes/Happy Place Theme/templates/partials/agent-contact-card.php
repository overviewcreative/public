<?php
/**
 * Agent Contact Card Template Part
 * 
 * @param array $args Contains agent_id
 */

if (!isset($args['agent_id'])) {
    return;
}

$agent = get_post($args['agent_id']);
if (!$agent) {
    return;
}
?>

<div class="hph-card" style="max-width: 600px; margin: 0 auto;">
    <div class="hph-card-content">
        <div style="display: flex; gap: var(--hph-spacing-lg); align-items: start;">
            <?php if (has_post_thumbnail($agent)) : ?>
                <div style="flex-shrink: 0; width: 120px; height: 120px; border-radius: var(--hph-border-radius-full); overflow: hidden;">
                    <?php echo get_the_post_thumbnail($agent, 'thumbnail', array('style' => 'width: 100%; height: 100%; object-fit: cover;')); ?>
                </div>
            <?php endif; ?>

            <div style="flex-grow: 1;">
                <h3 style="margin-bottom: var(--hph-spacing-xs);">
                    <?php echo esc_html($agent->post_title); ?>
                </h3>
                
                <?php if ($license = get_post_meta($agent->ID, '_license_number', true)) : ?>
                    <div style="color: var(--hph-color-gray-600); margin-bottom: var(--hph-spacing-xs);">
                        License #: <?php echo esc_html($license); ?>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: var(--hph-spacing-md);">
                    <?php if ($phone = get_post_meta($agent->ID, '_phone', true)) : ?>
                        <a href="tel:<?php echo esc_attr($phone); ?>" class="hph-btn hph-btn-primary" style="margin-right: var(--hph-spacing-xs);">
                            Call Agent
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($email = get_post_meta($agent->ID, '_email', true)) : ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="hph-btn hph-btn-secondary">
                            Email Agent
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if ($bio = get_post_meta($agent->ID, '_short_bio', true)) : ?>
                    <div style="color: var(--hph-color-gray-700); font-size: var(--hph-font-size-sm);">
                        <?php echo wp_kses_post($bio); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
