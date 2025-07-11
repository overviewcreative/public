<?php

/**
 * Template Name: Agent Dashboard
 * 
 * Updated template for the Happy Place Real Estate Platform Agent Dashboard
 * Designed to work with the new CSS design system
 * 
 * @package HappyPlace
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Security check - redirect non-agents
if (!current_user_can('agent') && !current_user_can('administrator')) {
    wp_redirect(home_url());
    exit;
}

// Initialize dashboard state
$current_section = function_exists('hph_get_dashboard_section') ? hph_get_dashboard_section() : 'overview';
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get current agent data
$current_agent_id = get_current_user_id();
$current_user = wp_get_current_user();
$agent_data = [];

// Get ACF fields if available
if (function_exists('get_fields')) {
    $user_fields = get_fields('user_' . $current_agent_id);
    $agent_data = is_array($user_fields) ? $user_fields : [];
}

// Set up agent display data with fallbacks
$agent_name = $agent_data['name'] ?? $agent_data['first_name'] ?? $current_user->display_name ?? 'Agent';
$agent_title = $agent_data['title'] ?? $agent_data['position'] ?? __('Real Estate Agent', 'happy-place');
$agent_email = $agent_data['email'] ?? $current_user->user_email;
$agent_phone = $agent_data['phone'] ?? '';
$agent_photo = $agent_data['agent_photo'] ?? $agent_data['photo'] ?? '';

// Get dashboard stats
$stats = [];
if (function_exists('hph_get_agent_stats')) {
    $stats = hph_get_agent_stats($current_agent_id);
} else {
    // Fallback stats
    $stats = [
        'active_listings' => get_posts(['author' => $current_agent_id, 'post_type' => 'listing', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']),
        'pending_listings' => get_posts(['author' => $current_agent_id, 'post_type' => 'listing', 'post_status' => 'pending', 'numberposts' => -1, 'fields' => 'ids']),
        'total_views' => get_user_meta($current_agent_id, 'total_listing_views', true) ?: 0,
        'leads_this_month' => get_user_meta($current_agent_id, 'leads_this_month', true) ?: 0,
    ];

    $stats['active_listings'] = is_array($stats['active_listings']) ? count($stats['active_listings']) : 0;
    $stats['pending_listings'] = is_array($stats['pending_listings']) ? count($stats['pending_listings']) : 0;
}

// Navigation items configuration
$nav_items = [
    'overview' => [
        'icon' => 'fa-tachometer-alt',
        'label' => __('Overview', 'happy-place'),
        'description' => __('Dashboard overview and quick stats', 'happy-place')
    ],
    'listings' => [
        'icon' => 'fa-home',
        'label' => __('My Listings', 'happy-place'),
        'description' => __('Manage your property listings', 'happy-place')
    ],
    'open-houses' => [
        'icon' => 'fa-calendar-alt',
        'label' => __('Open Houses', 'happy-place'),
        'description' => __('Schedule and manage open houses', 'happy-place')
    ],
    'performance' => [
        'icon' => 'fa-chart-line',
        'label' => __('Performance', 'happy-place'),
        'description' => __('View analytics and performance metrics', 'happy-place')
    ],
    'leads' => [
        'icon' => 'fa-users',
        'label' => __('Leads', 'happy-place'),
        'description' => __('Manage leads and inquiries', 'happy-place')
    ],
    'team' => [
        'icon' => 'fa-user-friends',
        'label' => __('Team', 'happy-place'),
        'description' => __('Collaborate with team members', 'happy-place')
    ],
    'profile' => [
        'icon' => 'fa-user-edit',
        'label' => __('Profile', 'happy-place'),
        'description' => __('Edit your agent profile', 'happy-place')
    ]
];

// Add cache management for administrators
if (current_user_can('manage_options')) {
    $nav_items['cache'] = [
        'icon' => 'fa-sync-alt',
        'label' => __('Cache Management', 'happy-place'),
        'description' => __('Clear various WordPress caches', 'happy-place')
    ];
}

// Remove header/footer for clean dashboard experience
remove_action('wp_head', '_wp_render_title_tag', 1);
add_action('wp_head', function () use ($current_section, $nav_items) {
    $section_title = $nav_items[$current_section]['label'] ?? __('Dashboard', 'happy-place');
    echo '<title>' . esc_html($section_title) . ' - ' . get_bloginfo('name') . '</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="robots" content="noindex, nofollow">';
}, 1);

get_header();

// Ensure dashboard assets are loaded
do_action('hph_before_dashboard');
?>

<!-- Dashboard Container -->
<div class="hph-dashboard" data-current-section="<?php echo esc_attr($current_section); ?>">
    <!-- Mobile Overlay -->
    <div class="hph-mobile-overlay"></div>

    <!-- Mobile Header -->
    <div class="hph-mobile-header hph-d-desktop-none">
        <button class="hph-mobile-menu-btn" aria-label="<?php esc_attr_e('Open navigation menu', 'happy-place'); ?>">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="hph-mobile-title">
            <?php echo esc_html($nav_items[$current_section]['label'] ?? __('Dashboard', 'happy-place')); ?>
        </h1>
        <div class="hph-mobile-actions">
            <!-- Add mobile-specific actions here -->
        </div>
    </div>

    <!-- Dashboard Sidebar -->
    <aside class="hph-dashboard-sidebar" role="navigation" aria-label="<?php esc_attr_e('Dashboard navigation', 'happy-place'); ?>">

        <!-- User Profile Section -->
        <div class="hph-dashboard-user">
            <?php if ($agent_photo && isset($agent_photo['url'])) : ?>
                <img src="<?php echo esc_url($agent_photo['url']); ?>"
                    alt="<?php echo esc_attr($agent_name); ?>"
                    class="hph-dashboard-avatar"
                    loading="lazy">
            <?php else : ?>
                <div class="hph-dashboard-avatar hph-dashboard-avatar--placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>

            <div class="hph-dashboard-user-info">
                <h3><?php echo esc_html($agent_name); ?></h3>
                <?php if ($agent_title) : ?>
                    <p><?php echo esc_html($agent_title); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="hph-dashboard-nav" role="menu">
            <?php foreach ($nav_items as $section => $item) :
                $url = function_exists('hph_get_dashboard_url') ? hph_get_dashboard_url($section) : add_query_arg('section', $section);
                $is_active = $current_section === $section;
            ?>
                <a href="<?php echo esc_url($url); ?>"
                    class="hph-dashboard-nav-item<?php echo $is_active ? ' hph-dashboard-nav-item--active' : ''; ?>"
                    data-section="<?php echo esc_attr($section); ?>"
                    role="menuitem"
                    aria-current="<?php echo $is_active ? 'page' : 'false'; ?>"
                    title="<?php echo esc_attr($item['description']); ?>">
                    <i class="fas <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></i>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="hph-dashboard-sidebar-footer">
            <div class="hph-quick-stats">
                <div class="hph-quick-stat">
                    <div class="hph-quick-stat-value"><?php echo esc_html($stats['active_listings'] ?? '0'); ?></div>
                    <div class="hph-quick-stat-label"><?php esc_html_e('Active Listings', 'happy-place'); ?></div>
                </div>
                <div class="hph-quick-stat">
                    <div class="hph-quick-stat-value"><?php echo esc_html($stats['leads_this_month'] ?? '0'); ?></div>
                    <div class="hph-quick-stat-label"><?php esc_html_e('New Leads', 'happy-place'); ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Dashboard Main Content -->
    <main class="hph-dashboard-main" role="main">

        <!-- Dashboard Header -->
        <header class="hph-dashboard-header">
            <div class="hph-overview-greeting">
                <h1 class="hph-dashboard-title">
                    <?php
                    printf(
                        /* translators: %s: Current time of day greeting */
                        esc_html__('Good %s, %s', 'happy-place'),
                        esc_html(hph_get_time_greeting()),
                        esc_html($agent_name)
                    );
                    ?>
                </h1>
                <p class="hph-dashboard-subtitle">
                    <?php echo esc_html($nav_items[$current_section]['description'] ?? __('Welcome to your dashboard', 'happy-place')); ?>
                </p>
            </div>
            <div class="hph-overview-date">
                <div class="hph-current-date">
                    <?php echo esc_html(wp_date('l, F j, Y')); ?>
                </div>
                <div class="hph-current-time" data-live-time="true">
                    <?php echo esc_html(wp_date('g:i A')); ?>
                </div>
            </div>
        </header>

        <!-- Dashboard Content Sections -->

        <!-- Overview Section -->
        <section id="overview" class="hph-dashboard-section<?php echo $current_section === 'overview' ? ' hph-dashboard-section--active' : ''; ?>" role="tabpanel" aria-labelledby="nav-overview">
            <?php include get_template_directory() . '/templates/dashboard/overview.php'; ?>
        </section>

        <!-- Listings Section -->
        <section id="listings" class="hph-dashboard-section<?php echo $current_section === 'listings' ? ' hph-dashboard-section--active' : ''; ?>" role="tabpanel" aria-labelledby="nav-listings">
            <?php include get_template_directory() . '/templates/dashboard/listings.php'; ?>
        </section>

        <!-- Open Houses Section -->
        <section id="open-houses" class="hph-dashboard-section<?php echo $current_section === 'open-houses' ? ' hph-dashboard-section--active' : ''; ?>" role="tabpanel" aria-labelledby="nav-open-houses">
            <?php include get_template_directory() . '/templates/dashboard/open-houses.php'; ?>
        </section>

        <!-- Performance Section -->
        <section id="performance" class="hph-dashboard-section<?php echo $current_section === 'performance' ? ' hph-dashboard-section--active' : ''; ?>" role="tabpanel" aria-labelledby="nav-performance">
            <?php include get_template_directory() . '/templates/dashboard/performance.php'; ?>
        </section>

        <!-- Leads Section -->
        <section id="leads" class="hph-dashboard-section<?php echo $current_section === 'leads' ? ' hph-dashboard-section--active' : ''; ?>" role="tabpanel" aria-labelledby="nav-leads">
            <?php include get_template_directory() . '/templates/dashboard/leads.php'; ?>
        </section>

        <!-- Team Section -->
        <section id="team" class="hph-dashboard-section<?php echo $current_section === 'team' ? ' hph-dashboard-section--active' : ''; ?>" role="tabpanel" aria-labelledby="nav-team">
            <?php include get_template_directory() . '/templates/dashboard/team.php'; ?>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="hph-dashboard-section<?php echo $current_section === 'profile' ? ' hph-dashboard-section--active' : ''; ?>" role="tabpanel" aria-labelledby="nav-profile">
            <?php include get_template_directory() . '/templates/dashboard/profile.php'; ?>
        </section>

        <!-- Cache Management Section (Admin Only) -->
        <?php if (current_user_can('manage_options')) : ?>
            <section id="cache" class="hph-dashboard-section<?php echo $current_section === 'cache' ? ' hph-dashboard-section--active' : ''; ?>" role="tabpanel" aria-labelledby="nav-cache">
                <?php include plugin_dir_path(HPH_PLUGIN_FILE) . 'templates/dashboard/cache-management.php'; ?>
            </section>
        <?php endif; ?>

        <!-- Error Section (for unknown sections) -->
        <?php if (!array_key_exists($current_section, $nav_items)) : ?>
            <section class="hph-dashboard-section hph-dashboard-section--active">
                <div class="hph-empty-state">
                    <div class="hph-empty-state-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="hph-empty-state-title"><?php esc_html_e('Section Not Found', 'happy-place'); ?></h2>
                    <p class="hph-empty-state-description">
                        <?php esc_html_e('The requested dashboard section could not be found. Please try navigating to a different section.', 'happy-place'); ?>
                    </p>
                    <a href="<?php echo esc_url(add_query_arg('section', 'overview')); ?>" class="hph-btn hph-btn--primary">
                        <i class="fas fa-home"></i>
                        <?php esc_html_e('Go to Overview', 'happy-place'); ?>
                    </a>
                </div>
            </section>
        <?php endif; ?>

    </main>
</div>

<?php
do_action('hph_after_dashboard');
get_footer();
?>

<!-- Loading Overlay -->
<div class="hph-loading-overlay" id="hph-loading-overlay" style="display: none;" aria-hidden="true">
    <div class="hph-loading-content">
        <div class="hph-loading-spinner"></div>
        <h3 class="hph-loading-title"><?php esc_html_e('Loading...', 'happy-place'); ?></h3>
        <p class="hph-loading-message"><?php esc_html_e('Please wait while we load your dashboard.', 'happy-place'); ?></p>
    </div>
</div>

<!-- Modal Container -->
<div id="hph-modal-container" class="hph-modal-container"></div>

<!-- Toast Notifications Container -->
<div id="hph-toast-container" class="hph-toast-container"></div>

<!-- Dashboard JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Initialize Dashboard
        const HphDashboard = {

            // Mobile navigation toggle
            initMobileNav: function() {
                const menuBtn = document.querySelector('.hph-mobile-menu-btn');
                const sidebar = document.querySelector('.hph-dashboard-sidebar');
                const overlay = document.querySelector('.hph-mobile-overlay');

                if (menuBtn && sidebar && overlay) {
                    menuBtn.addEventListener('click', function() {
                        sidebar.classList.toggle('hph-dashboard-sidebar--open');
                        overlay.classList.toggle('hph-mobile-overlay--active');
                        document.body.classList.toggle('hph-modal-open');
                    });

                    overlay.addEventListener('click', function() {
                        sidebar.classList.remove('hph-dashboard-sidebar--open');
                        overlay.classList.remove('hph-mobile-overlay--active');
                        document.body.classList.remove('hph-modal-open');
                    });
                }
            },

            // Section navigation
            initSectionNav: function() {
                const navItems = document.querySelectorAll('.hph-dashboard-nav-item');

                navItems.forEach(item => {
                    item.addEventListener('click', function(e) {
                        // For AJAX section loading (optional)
                        if (window.hphAjaxEnabled) {
                            e.preventDefault();
                            const section = this.dataset.section;
                            HphDashboard.loadSection(section);
                        }
                    });
                });
            },

            // AJAX section loading (optional)
            loadSection: function(section) {
                const loadingOverlay = document.getElementById('hph-loading-overlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'flex';
                }

                // Add your AJAX loading logic here
                console.log('Loading section:', section);

                // Hide loading after delay (replace with actual AJAX completion)
                setTimeout(() => {
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                }, 1000);
            },

            // Live time update
            initLiveTime: function() {
                const timeElement = document.querySelector('[data-live-time="true"]');
                if (timeElement) {
                    setInterval(() => {
                        const now = new Date();
                        timeElement.textContent = now.toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    }, 60000); // Update every minute
                }
            },

            // Toast notifications
            showToast: function(message, type = 'info') {
                const container = document.getElementById('hph-toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = `hph-toast hph-toast--${type} hph-toast--entering`;

                const icon = {
                    success: 'fa-check-circle',
                    error: 'fa-exclamation-circle',
                    warning: 'fa-exclamation-triangle',
                    info: 'fa-info-circle'
                } [type] || 'fa-info-circle';

                toast.innerHTML = `
                <div class="hph-toast-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="hph-toast-content">
                    <div class="hph-toast-message">${message}</div>
                </div>
                <button class="hph-toast-close" aria-label="Close notification">
                    <i class="fas fa-times"></i>
                </button>
            `;

                container.appendChild(toast);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    toast.classList.add('hph-toast--exiting');
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, 5000);

                // Close button functionality
                const closeBtn = toast.querySelector('.hph-toast-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => {
                        toast.classList.add('hph-toast--exiting');
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    });
                }
            },

            // Initialize all features
            init: function() {
                // Hide loading overlay immediately
                const loadingOverlay = document.getElementById('hph-loading-overlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }

                this.initMobileNav();
                this.initSectionNav();
                this.initLiveTime();

                console.log('Dashboard initialized');
            }
        };

        // Initialize the dashboard
        HphDashboard.init();

        // Make dashboard object globally available
        window.HphDashboard = HphDashboard;
    });
</script>

// Function hph_get_time_greeting() is now in template-functions.php