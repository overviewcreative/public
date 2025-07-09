<?php defined('ABSPATH') || exit; ?>

<div class="wrap happy-place-dashboard-wrap">
    <h1><?php _e('Dashboard', 'happy-place'); ?></h1>

    <div class="happy-place-dashboard-grid">
        <!-- Quick Stats -->
        <div class="dashboard-card stats-card">
            <h2><?php _e('Quick Stats', 'happy-place'); ?></h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($this->get_active_listings_count()); ?></span>
                    <span class="stat-label"><?php _e('Active Listings', 'happy-place'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($this->get_active_leads_count()); ?></span>
                    <span class="stat-label"><?php _e('Active Leads', 'happy-place'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($this->get_total_agents_count()); ?></span>
                    <span class="stat-label"><?php _e('Team Members', 'happy-place'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo esc_html($this->get_pending_tasks_count()); ?></span>
                    <span class="stat-label"><?php _e('Pending Tasks', 'happy-place'); ?></span>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card activity-card">
            <h2><?php _e('Recent Activity', 'happy-place'); ?></h2>
            <div class="activity-list">
                <?php foreach ($this->get_recent_activity() as $activity): ?>
                    <div class="activity-item">
                        <span class="activity-icon <?php echo esc_attr($activity['type']); ?>"></span>
                        <div class="activity-content">
                            <p><?php echo esc_html($activity['message']); ?></p>
                            <span class="activity-time"><?php echo esc_html($activity['time']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card actions-card">
            <h2><?php _e('Quick Actions', 'happy-place'); ?></h2>
            <div class="actions-grid">
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=listing')); ?>" class="action-button">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Add Listing', 'happy-place'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=happy-place-leads&action=new')); ?>" class="action-button">
                    <span class="dashicons dashicons-businessman"></span>
                    <?php _e('Add Lead', 'happy-place'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=happy-place-team&action=new')); ?>" class="action-button">
                    <span class="dashicons dashicons-groups"></span>
                    <?php _e('Add Team Member', 'happy-place'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=happy-place-syncs')); ?>" class="action-button">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Run Sync', 'happy-place'); ?>
                </a>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="dashboard-card performance-card">
            <h2><?php _e('Performance Metrics', 'happy-place'); ?></h2>
            <div class="performance-grid">
                <div class="metric-item">
                    <h3><?php _e('Lead Conversion', 'happy-place'); ?></h3>
                    <div class="metric-chart" id="lead-conversion-chart"></div>
                </div>
                <div class="metric-item">
                    <h3><?php _e('Listing Views', 'happy-place'); ?></h3>
                    <div class="metric-chart" id="listing-views-chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>
