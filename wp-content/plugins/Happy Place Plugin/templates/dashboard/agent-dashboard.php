<?php
/**
 * Template Name: Agent Dashboard
 * 
 * @package HappyPlace
 */

// Ensure only agents can access this page
if (!current_user_can('edit_properties')) {
    wp_redirect(home_url());
    exit;
}

get_header('dashboard');

// Get agent data
$current_user = wp_get_current_user();
$agent_id = get_user_meta($current_user->ID, 'agent_profile_id', true);
$agent_stats = HappyPlace_Agent_Dashboard::get_agent_stats($agent_id);
?>

<div class="hph-dashboard">
    <!-- Dashboard Header -->
    <header class="hph-dashboard-header">
        <div class="hph-dashboard-welcome">
            <h1>Welcome, <?php echo esc_html($current_user->display_name); ?></h1>
            <p class="hph-dashboard-date"><?php echo date('l, F j, Y'); ?></p>
        </div>
        <div class="hph-dashboard-actions">
            <button class="hph-btn hph-btn--primary" id="new-listing-btn">Add New Listing</button>
            <button class="hph-btn" id="new-open-house-btn">Schedule Open House</button>
        </div>
    </header>

    <!-- Quick Stats -->
    <div class="hph-dashboard-stats">
        <div class="hph-stat-card">
            <h3>Active Listings</h3>
            <div class="hph-stat-value"><?php echo esc_html($agent_stats['active_listings']); ?></div>
        </div>
        <div class="hph-stat-card">
            <h3>Open Houses</h3>
            <div class="hph-stat-value"><?php echo esc_html($agent_stats['upcoming_open_houses']); ?></div>
        </div>
        <div class="hph-stat-card">
            <h3>New Leads</h3>
            <div class="hph-stat-value"><?php echo esc_html($agent_stats['new_leads']); ?></div>
        </div>
        <div class="hph-stat-card">
            <h3>Property Views</h3>
            <div class="hph-stat-value"><?php echo esc_html($agent_stats['property_views']); ?></div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="hph-dashboard-content">
        <div class="hph-dashboard-main">
            <!-- Recent Activity -->
            <section class="hph-dashboard-section">
                <h2>Recent Activity</h2>
                <div class="hph-activity-feed" id="activity-feed">
                    <!-- Dynamically loaded via AJAX -->
                </div>
            </section>

            <!-- Upcoming Open Houses -->
            <section class="hph-dashboard-section">
                <h2>Upcoming Open Houses</h2>
                <div class="hph-open-houses-list" id="open-houses">
                    <!-- Dynamically loaded via AJAX -->
                </div>
            </section>

            <!-- Recent Leads -->
            <section class="hph-dashboard-section">
                <h2>Recent Leads</h2>
                <div class="hph-leads-list" id="recent-leads">
                    <!-- Dynamically loaded via AJAX -->
                </div>
            </section>
        </div>

        <div class="hph-dashboard-sidebar">
            <!-- Quick Actions -->
            <section class="hph-dashboard-section">
                <h2>Quick Actions</h2>
                <div class="hph-quick-actions">
                    <a href="#" class="hph-quick-action" data-action="edit-profile">
                        <i class="fas fa-user-edit"></i>
                        Edit Profile
                    </a>
                    <a href="#" class="hph-quick-action" data-action="team-chat">
                        <i class="fas fa-comments"></i>
                        Team Chat
                    </a>
                    <a href="#" class="hph-quick-action" data-action="market-report">
                        <i class="fas fa-chart-line"></i>
                        Market Report
                    </a>
                    <a href="#" class="hph-quick-action" data-action="documents">
                        <i class="fas fa-folder"></i>
                        Documents
                    </a>
                </div>
            </section>

            <!-- Team Activity -->
            <section class="hph-dashboard-section">
                <h2>Team Activity</h2>
                <div class="hph-team-activity" id="team-activity">
                    <!-- Dynamically loaded via AJAX -->
                </div>
            </section>
        </div>
    </div>
</div>

<?php get_footer('dashboard'); ?>
