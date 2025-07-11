<?php
/**
 * Dashboard Stats Section Template
 * 
 * @package HappyPlace
 */

$current_user_id = get_current_user_id();
$stats = hph_get_agent_stats();
?>

<section id="stats" class="hph-dashboard-section">
    <div class="hph-dashboard-header">
        <h2><?php _e('Performance Statistics', 'happy-place'); ?></h2>
        <div class="hph-dashboard-actions">
            <div class="hph-date-range">
                <select id="stats-range" class="hph-select">
                    <option value="7d"><?php _e('Last 7 Days', 'happy-place'); ?></option>
                    <option value="30d" selected><?php _e('Last 30 Days', 'happy-place'); ?></option>
                    <option value="90d"><?php _e('Last 90 Days', 'happy-place'); ?></option>
                    <option value="1y"><?php _e('Last Year', 'happy-place'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="hph-stats-grid">
        <!-- Listings Stats -->
        <div class="hph-stat-card">
            <div class="hph-stat-icon"><i class="fas fa-home"></i></div>
            <div class="hph-stat-content">
                <h4><?php _e('Active Listings', 'happy-place'); ?></h4>
                <div class="hph-stat-value"><?php echo esc_html($stats['listings']['active']); ?></div>
            </div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="hph-stat-content">
                <h4><?php _e('Properties Sold', 'happy-place'); ?></h4>
                <div class="hph-stat-value"><?php echo esc_html($stats['listings']['sold']); ?></div>
            </div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-icon"><i class="fas fa-eye"></i></div>
            <div class="hph-stat-content">
                <h4><?php _e('Total Views', 'happy-place'); ?></h4>
                <div class="hph-stat-value"><?php echo esc_html($stats['listings']['views']); ?></div>
            </div>
        </div>

        <div class="hph-stat-card">
            <div class="hph-stat-icon"><i class="fas fa-envelope"></i></div>
            <div class="hph-stat-content">
                <h4><?php _e('New Inquiries', 'happy-place'); ?></h4>
                <div class="hph-stat-value"><?php echo esc_html($stats['engagement']['inquiries']); ?></div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="hph-dashboard-section-content">
        <h3><?php _e('Performance Metrics', 'happy-place'); ?></h3>
        
        <div class="hph-metrics-grid">
            <div class="hph-metric-card">
                <h4><?php _e('Average Days on Market', 'happy-place'); ?></h4>
                <div class="hph-metric-value">
                    <?php echo esc_html($stats['performance']['averageDaysOnMarket']); ?>
                    <span class="hph-metric-label"><?php _e('days', 'happy-place'); ?></span>
                </div>
            </div>

            <div class="hph-metric-card">
                <h4><?php _e('Average Sale Price', 'happy-place'); ?></h4>
                <div class="hph-metric-value">
                    <?php echo esc_html(hph_format_price($stats['performance']['averageSalePrice'])); ?>
                </div>
            </div>

            <div class="hph-metric-card">
                <h4><?php _e('Total Sales Volume', 'happy-place'); ?></h4>
                <div class="hph-metric-value">
                    <?php echo esc_html(hph_format_price($stats['performance']['totalVolume'])); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Charts -->
    <div class="hph-dashboard-section-content">
        <h3><?php _e('Activity Overview', 'happy-place'); ?></h3>
        
        <div class="hph-charts-grid">
            <div class="hph-chart-card">
                <h4><?php _e('Views Over Time', 'happy-place'); ?></h4>
                <canvas id="viewsChart"></canvas>
            </div>

            <div class="hph-chart-card">
                <h4><?php _e('Inquiries Over Time', 'happy-place'); ?></h4>
                <canvas id="inquiriesChart"></canvas>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewsCtx = document.getElementById('viewsChart').getContext('2d');
    const inquiriesCtx = document.getElementById('inquiriesChart').getContext('2d');
    
    // Views Chart
    new Chart(viewsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($stats['chart']['labels']); ?>,
            datasets: [{
                label: '<?php _e('Views', 'happy-place'); ?>',
                data: <?php echo json_encode($stats['chart']['views']); ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Inquiries Chart
    new Chart(inquiriesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($stats['chart']['labels']); ?>,
            datasets: [{
                label: '<?php _e('Inquiries', 'happy-place'); ?>',
                data: <?php echo json_encode($stats['chart']['inquiries']); ?>,
                borderColor: 'rgb(153, 102, 255)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>
