<?php

/**
 * Template Name: Agent Dashboard
 * 
 * This template is used for displaying the agent dashboard.
 *
 * @package HappyPlace
 */

get_header();

// Verify user is logged in and has appropriate permissions
if (!is_user_logged_in() || (!current_user_can('agent') && !current_user_can('administrator'))) {
?>
    <div class="hph-container">
        <div class="hph-error">
            <p><?php _e('You must be logged in as an agent to view this page.', 'happy-place'); ?></p>
            <p><a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="hph-button"><?php _e('Log In', 'happy-place'); ?></a></p>
        </div>
    </div>
<?php
    get_footer();
    return;
}

// Get the current tab/section and action
$current_section = isset($_GET['section']) ? sanitize_key($_GET['section']) : 'overview';
$current_action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
$allowed_sections = ['overview', 'listings', 'leads', 'profile', 'settings'];
$allowed_actions = ['new-listing', 'edit-listing', 'new-open-house', 'edit-open-house', 'new-lead', 'edit-lead'];

// Add cache section for administrators
if (current_user_can('manage_options')) {
    $allowed_sections[] = 'cache';
}

if (!in_array($current_section, $allowed_sections)) {
    $current_section = 'overview';
}

// Get current agent data
$current_agent_id = get_current_user_id();
$current_user = wp_get_current_user();
$agent_data = [];

// Get ACF fields if available
if (function_exists('get_fields')) {
    $user_fields = get_fields('user_' . $current_agent_id);
    $agent_data = is_array($user_fields) ? $user_fields : [];
}

// Navigation items configuration
$nav_items = [
    'overview' => [
        'icon' => 'fa-home',
        'label' => __('Overview', 'happy-place'),
        'description' => __('Dashboard overview and quick stats', 'happy-place')
    ],
    'listings' => [
        'icon' => 'fa-list',
        'label' => __('Listings', 'happy-place'),
        'description' => __('Manage your property listings', 'happy-place')
    ],
    'leads' => [
        'icon' => 'fa-users',
        'label' => __('Leads', 'happy-place'),
        'description' => __('Manage leads and inquiries', 'happy-place')
    ],
    'profile' => [
        'icon' => 'fa-user',
        'label' => __('Profile', 'happy-place'),
        'description' => __('Edit your agent profile', 'happy-place')
    ],
    'settings' => [
        'icon' => 'fa-gear',
        'label' => __('Settings', 'happy-place'),
        'description' => __('Manage your preferences', 'happy-place')
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

<div class="hph-dashboard">
    <div class="hph-container">
        <!-- Dashboard Header -->
        <header class="hph-dashboard-header">
            <div class="hph-dashboard-welcome">
                <h1 class="hph-dashboard-title">
                    <?php
                    $greeting = '';
                    $hour = (int)current_time('H');
                    if ($hour < 12) {
                        $greeting = __('Good Morning', 'happy-place');
                    } elseif ($hour < 17) {
                        $greeting = __('Good Afternoon', 'happy-place');
                    } else {
                        $greeting = __('Good Evening', 'happy-place');
                    }
                    echo esc_html($greeting) . ', ' . esc_html(explode(' ', $current_user->display_name)[0]);
                    ?>
                </h1>
                <p class="hph-dashboard-subtitle">
                    <?php _e('Welcome to your agent dashboard', 'happy-place'); ?>
                </p>
            </div>
            <div class="hph-dashboard-agent-info">
                <?php
                $agent_photo = '';
                if (!empty($agent_data['agent_photo'])) {
                    if (is_array($agent_data['agent_photo'])) {
                        $agent_photo = $agent_data['agent_photo']['url'] ?? '';
                    } else {
                        $agent_photo = $agent_data['agent_photo'];
                    }
                }

                if ($agent_photo) : ?>
                    <img src="<?php echo esc_url($agent_photo); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="hph-dashboard-agent-photo">
                <?php else : ?>
                    <div class="hph-dashboard-agent-photo hph-agent-avatar">
                        <?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 1))); ?>
                    </div>
                <?php endif; ?>
                <div class="hph-dashboard-agent-details">
                    <h2><?php echo esc_html($current_user->display_name); ?></h2>
                    <?php if (!empty($agent_data['title'])) : ?>
                        <p><?php echo esc_html($agent_data['title']); ?></p>
                    <?php elseif (!empty($agent_data['agent_title'])) : ?>
                        <p><?php echo esc_html($agent_data['agent_title']); ?></p>
                    <?php else : ?>
                        <p><?php _e('Real Estate Professional', 'happy-place'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Dashboard Navigation -->
        <nav class="hph-dashboard-nav" role="tablist">
            <?php foreach ($nav_items as $section => $item) :
                $is_active = $current_section === $section;
            ?>
                <a href="#<?php echo esc_attr($section); ?>"
                    class="hph-dashboard-nav-item<?php echo $is_active ? ' hph-dashboard-nav-item--active' : ''; ?>"
                    data-section="<?php echo esc_attr($section); ?>"
                    role="tab"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo esc_attr($section); ?>">
                    <i class="fas <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></i>
                    <span><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Dashboard Content -->
        <div class="hph-dashboard-content">
            <?php
            // Check if we're in a form action mode
            if (!empty($current_action) && in_array($current_action, $allowed_actions)) {
                // Handle form actions
                switch ($current_action) {
                    case 'new-listing':
                    case 'edit-listing':
                        get_template_part('template-parts/dashboard/form', 'listing');
                        break;

                    case 'new-open-house':
                    case 'edit-open-house':
                        get_template_part('template-parts/dashboard/form', 'open-house');
                        break;

                    case 'new-lead':
                    case 'edit-lead':
                        get_template_part('template-parts/dashboard/form', 'lead');
                        break;

                    default:
                        // Fallback to section content
                        foreach ($nav_items as $section => $item) :
                            $is_active = $section === $current_section;
            ?>
                            <div id="<?php echo esc_attr($section); ?>"
                                class="hph-dashboard-section<?php echo $is_active ? ' hph-dashboard-section--active' : ''; ?>"
                                role="tabpanel"
                                aria-labelledby="<?php echo esc_attr($section); ?>-tab">
                                <?php
                                if ($is_active) {
                                    get_template_part('template-parts/dashboard/section', $section, [
                                        'section_data' => HPH_Ajax_Handler::get_section_data($section)
                                    ]);
                                }
                                ?>
                            </div>
                    <?php endforeach;
                        break;
                }
            } else {
                // Normal section display
                foreach ($nav_items as $section => $item) :
                    $is_active = $section === $current_section;
                    ?>
                    <div id="<?php echo esc_attr($section); ?>"
                        class="hph-dashboard-section<?php echo $is_active ? ' hph-dashboard-section--active' : ''; ?>"
                        role="tabpanel"
                        aria-labelledby="<?php echo esc_attr($section); ?>-tab">
                        <?php
                        if ($is_active) {
                            get_template_part('template-parts/dashboard/section', $section, [
                                'section_data' => HPH_Ajax_Handler::get_section_data($section)
                            ]);
                        }
                        ?>
                    </div>
            <?php endforeach;
            } ?>
        </div>
    </div>
</div>

<?php
do_action('hph_after_dashboard');
get_footer();
?>

<!-- AJAX Configuration -->
<script>
    var dashboardAjax = {
        ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo wp_create_nonce('hph_dashboard_nonce'); ?>'
    };
</script>

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

<!-- Ensure proper section visibility -->
<style>
    .hph-dashboard-section {
        display: none !important;
    }

    .hph-dashboard-section--active {
        display: block !important;
    }
</style>

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
                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        const section = item.dataset.section;
                        if (!section) return;

                        // Update active states
                        navItems.forEach(nav => {
                            nav.classList.remove('hph-dashboard-nav-item--active');
                            nav.setAttribute('aria-selected', 'false');
                        });
                        item.classList.add('hph-dashboard-nav-item--active');
                        item.setAttribute('aria-selected', 'true');

                        this.loadSection(section);
                    });
                });

                // Handle form button clicks
                this.initFormButtons();

                // Handle initial section display on page load
                this.showInitialSection();
            },

            // Form button navigation
            initFormButtons: function() {
                // Use event delegation to handle dynamically loaded form buttons
                document.addEventListener('click', (e) => {
                    // Check if clicked element is a form button or contains one
                    const formButton = e.target.closest('a[href*="action="]');
                    if (!formButton) return;

                    // Only handle buttons within the dashboard content area
                    const dashboardContent = document.querySelector('.hph-dashboard-content');
                    if (!dashboardContent || !dashboardContent.contains(formButton)) return;

                    e.preventDefault();

                    // Extract action and other parameters from URL
                    const url = new URL(formButton.href);
                    const action = url.searchParams.get('action');

                    if (action) {
                        // Pass the full URL so loadForm can extract any additional parameters
                        this.loadFormFromUrl(action, url);
                    }
                });
            },

            // Load form via AJAX with URL parameters
            loadFormFromUrl: function(action, url) {
                const contentArea = document.querySelector('.hph-dashboard-content-area');
                if (!contentArea) return;

                this.showLoader();

                // Build form data with action and any additional parameters from the URL
                const formData = new URLSearchParams({
                    action: 'load_dashboard_section',
                    section: action,
                    action_type: 'form',
                    nonce: '<?php echo wp_create_nonce('hph_dashboard_nonce'); ?>'
                });

                // Extract additional parameters from the clicked URL
                if (url.searchParams.get('listing_id')) {
                    formData.append('listing_id', url.searchParams.get('listing_id'));
                }
                if (url.searchParams.get('open_house_id')) {
                    formData.append('open_house_id', url.searchParams.get('open_house_id'));
                }
                if (url.searchParams.get('lead_id')) {
                    formData.append('lead_id', url.searchParams.get('lead_id'));
                }

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.hideLoader();

                        if (data.success && data.data.content) {
                            contentArea.innerHTML = data.data.content;

                            // Update page title if provided
                            if (data.data.title) {
                                const titleElement = document.querySelector('.hph-dashboard-section-title');
                                if (titleElement) {
                                    titleElement.textContent = data.data.title;
                                }
                            }

                            // Re-initialize form buttons for newly loaded content
                            this.initFormButtons();
                        } else {
                            this.showToast('Error loading form: ' + (data.data ? data.data : 'Unknown error'), 'error');
                        }
                    })
                    .catch(error => {
                        this.hideLoader();
                        console.error('Form loading error:', error);
                        this.showToast('Failed to load form. Please try again.', 'error');
                    });
            },

            // Show initial section and hide others
            showInitialSection: function() {
                const allSections = document.querySelectorAll('.hph-dashboard-section');
                const activeSection = document.querySelector('.hph-dashboard-section--active');

                // Hide all sections first
                allSections.forEach(section => {
                    if (section !== activeSection) {
                        section.classList.remove('hph-dashboard-section--active');
                    }
                });
            },

            // AJAX section loading
            loadSection: function(section) {
                console.log('Loading section:', section);
                const loadingOverlay = document.getElementById('hph-loading-overlay');
                const allSections = document.querySelectorAll('.hph-dashboard-section');
                const newSection = document.querySelector(`#${section}`);

                if (!newSection) {
                    console.error('Section not found:', section);
                    return;
                }

                // Hide all sections first
                console.log('Hiding all sections');
                allSections.forEach(sec => {
                    sec.classList.remove('hph-dashboard-section--active');
                });

                // Show loading overlay
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'flex';
                }

                // Update URL with hash
                window.location.hash = section;

                // Make AJAX request
                fetch(dashboardAjax.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'hph_load_dashboard_section',
                            section: section,
                            nonce: dashboardAjax.nonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Section loaded successfully:', section);
                            // Update section content
                            newSection.innerHTML = data.data.content;

                            // Show the new section
                            newSection.classList.add('hph-dashboard-section--active');
                            console.log('Section activated:', section);

                            // Update page title
                            document.title = `${data.data.title} - ${document.querySelector('meta[property="og:site_name"]')?.content || 'Happy Place'}`;
                        } else {
                            console.error('AJAX error:', data.data.message);
                            this.showToast(data.data.message || 'Error loading section', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading section:', error);
                        this.showToast('Error loading section. Please try again.', 'error');
                    })
                    .finally(() => {
                        if (loadingOverlay) {
                            loadingOverlay.style.display = 'none';
                        }
                    });
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