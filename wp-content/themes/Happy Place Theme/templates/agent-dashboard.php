<?php
/**
 * Template Name: Agent Dashboard
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize dashboard state
$current_section = hph_get_dashboard_section();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect non-agents
if (!current_user_can('agent') && !current_user_can('administrator')) {
    wp_redirect(home_url());
    exit;
}

// Get agent data
$current_agent_id = get_current_user_id();
$agent_data = get_field('agent_details', 'user_' . $current_agent_id);

// Get initial stats for the overview section
$stats = hph_get_agent_stats($current_agent_id);

// Force proper header
remove_action('wp_head', '_wp_render_title_tag', 1);
add_action('wp_head', function() {
    echo '<title>' . wp_get_document_title() . '</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
}, 1);

get_header();
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
            <?php 
            $nav_items = [
                'overview' => ['icon' => 'fa-home', 'label' => __('Overview', 'happy-place')],
                'listings' => ['icon' => 'fa-list', 'label' => __('My Listings', 'happy-place')],
                'open-houses' => ['icon' => 'fa-calendar', 'label' => __('Open Houses', 'happy-place')],
                'stats' => ['icon' => 'fa-chart-bar', 'label' => __('Performance', 'happy-place')],
                'team' => ['icon' => 'fa-users', 'label' => __('Team Listings', 'happy-place')],
                'profile' => ['icon' => 'fa-user-edit', 'label' => __('Edit Profile', 'happy-place')]
            ];

            foreach ($nav_items as $section => $item) :
                $url = hph_get_dashboard_url($section);
                $is_active = $current_section === $section;
            ?>
                <a href="<?php echo esc_url($url); ?>" 
                   class="hph-dashboard-nav-item<?php echo $is_active ? ' hph-dashboard-nav-item--active' : ''; ?>" 
                   data-section="<?php echo esc_attr($section); ?>">
                    <i class="fas <?php echo esc_attr($item['icon']); ?>"></i> <?php echo esc_html($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Dashboard Main Content -->
    <main class="hph-dashboard-main">
        <!-- Overview Section -->
        <?php include get_template_directory() . '/templates/dashboard/' . $current_section . '.php'; ?>
    </main>

    <!-- Modal Container -->
    <div id="hph-modal-container" class="hph-modal-container"></div>

    <!-- Toast Container -->
    <div id="hph-toast-container" class="hph-toast-container"></div>
</div>

<?php get_footer(); ?>
