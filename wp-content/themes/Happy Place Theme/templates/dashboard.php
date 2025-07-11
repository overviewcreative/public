<?php
/**
 * Template Name: Agent Dashboard
 * 
 * This template displays the agent dashboard on the front-end.
 * 
 * @package Happy_Place_Theme
 */

// Redirect non-logged in users to login
if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

// Verify user has permission to access dashboard
if (!current_user_can('edit_posts')) {
    wp_die(__('You do not have permission to access this page.', 'happy-place'));
}

get_header();
?>

<div class="dashboard-wrapper">
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <div class="happy-place-dashboard">
                <div class="dashboard-header">
                    <h1><?php echo esc_html__('Agent Dashboard', 'happy-place'); ?></h1>
                    <div class="dashboard-actions">
                        <button class="add-listing-btn"><?php echo esc_html__('Add New Listing', 'happy-place'); ?></button>
                        <button class="add-transaction-btn"><?php echo esc_html__('Add Transaction', 'happy-place'); ?></button>
                    </div>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card active-listings">
                        <h3><?php echo esc_html__('Active Listings', 'happy-place'); ?></h3>
                        <div class="stat-value" data-stat="active_listings">--</div>
                    </div>
                    <div class="stat-card pending">
                        <h3><?php echo esc_html__('Pending', 'happy-place'); ?></h3>
                        <div class="stat-value" data-stat="pending_listings">--</div>
                    </div>
                    <div class="stat-card sold">
                        <h3><?php echo esc_html__('Sold (This Year)', 'happy-place'); ?></h3>
                        <div class="stat-value" data-stat="sold_listings">--</div>
                    </div>
                    <div class="stat-card coming-soon">
                        <h3><?php echo esc_html__('Coming Soon', 'happy-place'); ?></h3>
                        <div class="stat-value" data-stat="coming_soon">--</div>
                    </div>
                </div>

                <div class="dashboard-content">
                    <div class="dashboard-section listings">
                        <h2><?php echo esc_html__('My Listings', 'happy-place'); ?></h2>
                        <div class="listing-filters">
                            <select class="status-filter">
                                <option value=""><?php echo esc_html__('All Statuses', 'happy-place'); ?></option>
                                <option value="Active"><?php echo esc_html__('Active', 'happy-place'); ?></option>
                                <option value="Pending"><?php echo esc_html__('Pending', 'happy-place'); ?></option>
                                <option value="Sold"><?php echo esc_html__('Sold', 'happy-place'); ?></option>
                                <option value="Coming Soon"><?php echo esc_html__('Coming Soon', 'happy-place'); ?></option>
                            </select>
                            <input type="search" class="search-filter" placeholder="<?php echo esc_attr__('Search listings...', 'happy-place'); ?>">
                        </div>
                        <div class="listings-grid"></div>
                        <div class="pagination"></div>
                    </div>

                    <div class="dashboard-section transactions">
                        <h2><?php echo esc_html__('Recent Transactions', 'happy-place'); ?></h2>
                        <div class="transactions-list"></div>
                    </div>
                </div>
            </div>

            <?php 
            // Load modals for adding/editing listings and transactions
            get_template_part('template-parts/dashboard/modal', 'listing');
            get_template_part('template-parts/dashboard/modal', 'transaction');
            ?>
        </main>
    </div>

    <?php get_sidebar('dashboard'); ?>
</div>

<?php
get_footer();
