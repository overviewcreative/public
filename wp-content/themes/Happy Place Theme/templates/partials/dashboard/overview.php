<?php
/**
 * Template part for displaying the dashboard overview
 */

$current_user = wp_get_current_user();
?>

<div class="hph-dashboard-overview">
    <h1><?php printf(__('Welcome, %s!', 'happy-place'), esc_html($current_user->display_name)); ?></h1>

    <div class="hph-dashboard-stats">
        <div class="stat-card">
            <h3><?php esc_html_e('Favorite Properties', 'happy-place'); ?></h3>
            <?php
            $favorites = get_user_meta($current_user->ID, 'favorites', true);
            $favorites_count = is_array($favorites) ? count($favorites) : 0;
            ?>
            <div class="stat-number"><?php echo esc_html($favorites_count); ?></div>
            <a href="<?php echo esc_url(add_query_arg('view', 'favorites', get_permalink())); ?>" class="hph-btn hph-btn-secondary">
                <?php esc_html_e('View All', 'happy-place'); ?>
            </a>
        </div>

        <div class="stat-card">
            <h3><?php esc_html_e('Saved Searches', 'happy-place'); ?></h3>
            <?php
            $saved_searches = get_user_meta($current_user->ID, 'saved_searches', true);
            $searches_count = is_array($saved_searches) ? count($saved_searches) : 0;
            ?>
            <div class="stat-number"><?php echo esc_html($searches_count); ?></div>
            <a href="<?php echo esc_url(add_query_arg('view', 'saved-searches', get_permalink())); ?>" class="hph-btn hph-btn-secondary">
                <?php esc_html_e('View All', 'happy-place'); ?>
            </a>
        </div>

        <div class="stat-card">
            <h3><?php esc_html_e('Recent Activity', 'happy-place'); ?></h3>
            <?php
            $activity = get_user_meta($current_user->ID, 'recent_activity', true);
            if (!empty($activity) && is_array($activity)) :
                $recent = array_slice($activity, 0, 3);
            ?>
                <ul class="activity-list">
                    <?php foreach ($recent as $item) : ?>
                        <li>
                            <span class="activity-date"><?php echo esc_html(human_time_diff(strtotime($item['date']), current_time('timestamp'))); ?> ago</span>
                            <span class="activity-desc"><?php echo esc_html($item['description']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No recent activity', 'happy-place'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (current_user_can('edit_posts')) : ?>
        <div class="hph-dashboard-actions">
            <h2><?php esc_html_e('Quick Actions', 'happy-place'); ?></h2>
            <div class="action-buttons">
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=listing')); ?>" class="hph-btn hph-btn-primary">
                    <?php esc_html_e('Add New Listing', 'happy-place'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=listing')); ?>" class="hph-btn hph-btn-secondary">
                    <?php esc_html_e('Manage Listings', 'happy-place'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
