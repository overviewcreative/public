<?php
/**
 * Dashboard Open Houses Section Template
 * 
 * @package HappyPlace
 */

$current_user_id = get_current_user_id();
$open_houses = hph_get_agent_open_houses($current_user_id);
?>

<section id="open-houses" class="hph-dashboard-section">
    <div class="hph-dashboard-header">
        <h2><?php _e('Open Houses', 'happy-place'); ?></h2>
        <div class="hph-dashboard-actions">
            <button type="button" class="hph-button hph-button--primary" data-modal="add-open-house">
                <i class="fas fa-plus"></i> <?php _e('Schedule Open House', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <div class="hph-dashboard-filters">
        <div class="hph-filter-group">
            <label for="open-house-date"><?php _e('Date Range', 'happy-place'); ?></label>
            <select id="open-house-date" class="hph-filter">
                <option value="upcoming"><?php _e('Upcoming', 'happy-place'); ?></option>
                <option value="past"><?php _e('Past', 'happy-place'); ?></option>
                <option value="all"><?php _e('All', 'happy-place'); ?></option>
            </select>
        </div>

        <div class="hph-filter-group">
            <label for="open-house-listing"><?php _e('Listing', 'happy-place'); ?></label>
            <select id="open-house-listing" class="hph-filter">
                <option value=""><?php _e('All Listings', 'happy-place'); ?></option>
                <?php
                $listings = hph_get_agent_listings($current_user_id, ['active', 'coming-soon']);
                foreach ($listings as $listing) :
                ?>
                    <option value="<?php echo esc_attr($listing->ID); ?>">
                        <?php echo esc_html($listing->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="hph-open-houses-grid" id="open-houses-grid">
        <?php foreach ($open_houses as $open_house) : ?>
            <div class="hph-open-house-card" data-id="<?php echo esc_attr($open_house->ID); ?>">
                <div class="hph-open-house-card__image">
                    <?php 
                    $listing_id = get_field('listing', $open_house->ID);
                    echo get_the_post_thumbnail($listing_id, 'medium'); 
                    ?>
                </div>
                <div class="hph-open-house-card__content">
                    <h3><?php echo esc_html(get_the_title($listing_id)); ?></h3>
                    <div class="hph-open-house-card__date">
                        <?php
                        $date = get_field('date', $open_house->ID);
                        $start_time = get_field('start_time', $open_house->ID);
                        $end_time = get_field('end_time', $open_house->ID);
                        echo esc_html(date_i18n('F j, Y', strtotime($date)));
                        echo ' &bull; ';
                        echo esc_html(date_i18n('g:i a', strtotime($start_time)));
                        echo ' - ';
                        echo esc_html(date_i18n('g:i a', strtotime($end_time)));
                        ?>
                    </div>
                    <div class="hph-open-house-card__meta">
                        <?php if (get_field('refreshments', $open_house->ID)) : ?>
                            <span><i class="fas fa-coffee"></i> <?php _e('Refreshments', 'happy-place'); ?></span>
                        <?php endif; ?>
                        <?php
                        $notes = get_field('notes', $open_house->ID);
                        if ($notes) :
                        ?>
                            <span><i class="fas fa-sticky-note"></i> <?php _e('Has Notes', 'happy-place'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hph-open-house-card__actions">
                    <button type="button" class="hph-button hph-button--icon" data-action="edit" title="<?php esc_attr_e('Edit Open House', 'happy-place'); ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="hph-button hph-button--icon" data-action="delete" title="<?php esc_attr_e('Delete Open House', 'happy-place'); ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                    <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" class="hph-button hph-button--icon" title="<?php esc_attr_e('View Listing', 'happy-place'); ?>" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php get_template_part('template-parts/forms/open-house-form'); ?>
</section>
