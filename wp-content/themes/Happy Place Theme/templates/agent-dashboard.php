<?php
/**
 * Template Name: Agent Dashboard
 * 
 * @package HappyPlace
 */

// Redirect non-agents to home
if (!current_user_can('agent') && !current_user_can('administrator')) {
    wp_redirect(home_url());
    exit;
}

$current_agent_id = get_current_user_id();
$agent_data = get_field('agent_details', 'user_' . $current_agent_id);

get_header('dashboard');
?>

<div class="hph-dashboard">
    <!-- Dashboard Sidebar -->
    <aside class="hph-dashboard-sidebar">
        <div class="hph-dashboard-user">
            <?php 
            $avatar = get_field('agent_photo', 'user_' . $current_agent_id);
            if ($avatar) :
            ?>
                <img src="<?php echo esc_url($avatar['url']); ?>" alt="<?php echo esc_attr($agent_data['name']); ?>" class="hph-dashboard-avatar">
            <?php endif; ?>
            <div class="hph-dashboard-user-info">
                <h3><?php echo esc_html($agent_data['name']); ?></h3>
                <p><?php echo esc_html($agent_data['title']); ?></p>
            </div>
        </div>

        <nav class="hph-dashboard-nav">
            <a href="#overview" class="hph-dashboard-nav-item hph-dashboard-nav-item--active" data-section="overview">
                <i class="fas fa-home"></i> Overview
            </a>
            <a href="#listings" class="hph-dashboard-nav-item" data-section="listings">
                <i class="fas fa-list"></i> My Listings
            </a>
            <a href="#open-houses" class="hph-dashboard-nav-item" data-section="open-houses">
                <i class="fas fa-calendar"></i> Open Houses
            </a>
            <a href="#stats" class="hph-dashboard-nav-item" data-section="stats">
                <i class="fas fa-chart-bar"></i> Performance
            </a>
            <a href="#team" class="hph-dashboard-nav-item" data-section="team">
                <i class="fas fa-users"></i> Team Listings
            </a>
            <a href="#profile" class="hph-dashboard-nav-item" data-section="profile">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
        </nav>
    </aside>

    <!-- Dashboard Main Content -->
    <main class="hph-dashboard-main">
        <!-- Overview Section -->
        <section id="overview" class="hph-dashboard-section hph-dashboard-section--active">
            <div class="hph-dashboard-header">
                <h1>Dashboard Overview</h1>
                <div class="hph-dashboard-actions">
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=property')); ?>" class="hph-button hph-button--primary">
                        <i class="fas fa-plus"></i> Add New Listing
                    </a>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="hph-stats-grid">
                <div class="hph-stat-card">
                    <div class="hph-stat-icon"><i class="fas fa-home"></i></div>
                    <div class="hph-stat-content">
                        <h4>Active Listings</h4>
                        <div class="hph-stat-value"><?php echo hph_get_agent_active_listings_count($current_agent_id); ?></div>
                    </div>
                </div>

                <div class="hph-stat-card">
                    <div class="hph-stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="hph-stat-content">
                        <h4>Open Houses</h4>
                        <div class="hph-stat-value"><?php echo hph_get_agent_upcoming_open_houses_count($current_agent_id); ?></div>
                    </div>
                </div>

                <div class="hph-stat-card">
                    <div class="hph-stat-icon"><i class="fas fa-eye"></i></div>
                    <div class="hph-stat-content">
                        <h4>Views This Month</h4>
                        <div class="hph-stat-value"><?php echo hph_get_agent_monthly_views($current_agent_id); ?></div>
                    </div>
                </div>

                <div class="hph-stat-card">
                    <div class="hph-stat-icon"><i class="fas fa-envelope"></i></div>
                    <div class="hph-stat-content">
                        <h4>New Inquiries</h4>
                        <div class="hph-stat-value"><?php echo hph_get_agent_new_inquiries_count($current_agent_id); ?></div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="hph-dashboard-section-content">
                <h3>Recent Activity</h3>
                <div class="hph-activity-list">
                    <?php 
                    $recent_activities = hph_get_agent_recent_activities($current_agent_id);
                    foreach ($recent_activities as $activity) :
                    ?>
                        <div class="hph-activity-item">
                            <div class="hph-activity-icon">
                                <i class="<?php echo esc_attr($activity['icon']); ?>"></i>
                            </div>
                            <div class="hph-activity-content">
                                <p><?php echo esc_html($activity['description']); ?></p>
                                <span class="hph-activity-time"><?php echo esc_html($activity['time']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Other sections will be loaded via AJAX -->
    </main>
</div>

<?php get_footer('dashboard'); ?>
