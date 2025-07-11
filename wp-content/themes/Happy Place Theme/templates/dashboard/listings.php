<?php
/**
 * Dashboard Listings Section Template
 * 
 * @package HappyPlace
 */

$current_user_id = get_current_user_id();
$listings = hph_get_agent_listings($current_user_id);
?>

<section id="listings" class="hph-dashboard-section">
    <div class="hph-dashboard-header">
        <h2><?php _e('My Listings', 'happy-place'); ?></h2>
        <div class="hph-dashboard-actions">
            <button type="button" class="hph-button hph-button--primary" data-modal="add-listing">
                <i class="fas fa-plus"></i> <?php _e('Add New Listing', 'happy-place'); ?>
            </button>
        </div>
    </div>

    <div class="hph-dashboard-filters">
        <div class="hph-filter-group">
            <label for="listing-status"><?php _e('Status', 'happy-place'); ?></label>
            <select id="listing-status" class="hph-filter">
                <option value=""><?php _e('All Statuses', 'happy-place'); ?></option>
                <option value="active"><?php _e('Active', 'happy-place'); ?></option>
                <option value="pending"><?php _e('Pending', 'happy-place'); ?></option>
                <option value="sold"><?php _e('Sold', 'happy-place'); ?></option>
                <option value="coming-soon"><?php _e('Coming Soon', 'happy-place'); ?></option>
            </select>
        </div>

        <div class="hph-filter-group">
            <label for="listing-search"><?php _e('Search', 'happy-place'); ?></label>
            <input type="text" id="listing-search" class="hph-filter" placeholder="<?php esc_attr_e('Search listings...', 'happy-place'); ?>">
        </div>
    </div>

    <div class="hph-listings-grid" id="listings-grid">
        <?php foreach ($listings as $listing) : ?>
            <div class="hph-listing-card" data-id="<?php echo esc_attr($listing->ID); ?>">
                <div class="hph-listing-card__image">
                    <?php echo get_the_post_thumbnail($listing->ID, 'medium'); ?>
                    <div class="hph-listing-card__status">
                        <?php echo esc_html(get_field('status', $listing->ID)); ?>
                    </div>
                </div>
                <div class="hph-listing-card__content">
                    <h3><?php echo esc_html($listing->post_title); ?></h3>
                    <div class="hph-listing-card__price">
                        <?php echo esc_html(hph_format_price(get_field('price', $listing->ID))); ?>
                    </div>
                    <div class="hph-listing-card__meta">
                        <?php 
                        $beds = get_field('bedrooms', $listing->ID);
                        $baths = get_field('bathrooms', $listing->ID);
                        $sqft = get_field('square_feet', $listing->ID);
                        ?>
                        <?php if ($beds) : ?>
                            <span><i class="fas fa-bed"></i> <?php echo esc_html($beds); ?></span>
                        <?php endif; ?>
                        <?php if ($baths) : ?>
                            <span><i class="fas fa-bath"></i> <?php echo esc_html($baths); ?></span>
                        <?php endif; ?>
                        <?php if ($sqft) : ?>
                            <span><i class="fas fa-vector-square"></i> <?php echo number_format($sqft); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hph-listing-card__actions">
                    <button type="button" class="hph-button hph-button--icon" data-action="edit" title="<?php esc_attr_e('Edit Listing', 'happy-place'); ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="hph-button hph-button--icon" data-action="delete" title="<?php esc_attr_e('Delete Listing', 'happy-place'); ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                    <a href="<?php echo esc_url(get_permalink($listing->ID)); ?>" class="hph-button hph-button--icon" title="<?php esc_attr_e('View Listing', 'happy-place'); ?>" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php get_template_part('template-parts/forms/listing-form'); ?>
</section>
