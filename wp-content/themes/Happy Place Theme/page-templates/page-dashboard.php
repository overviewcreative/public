<?php
/**
 * Template Name: Dashboard
 * Description: A custom template for the user dashboard
 */

// Redirect to login if user is not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();
?>

<div class="hph-container">
    <div class="hph-dashboard">
        <aside class="hph-dashboard-sidebar">
            <nav class="hph-dashboard-nav">
                <ul>
                    <li class="<?php echo empty($_GET['view']) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(get_permalink()); ?>">
                            <?php esc_html_e('Dashboard', 'happy-place'); ?>
                        </a>
                    </li>
                    <li class="<?php echo isset($_GET['view']) && $_GET['view'] === 'favorites' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('view', 'favorites', get_permalink())); ?>">
                            <?php esc_html_e('Favorites', 'happy-place'); ?>
                        </a>
                    </li>
                    <li class="<?php echo isset($_GET['view']) && $_GET['view'] === 'saved-searches' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('view', 'saved-searches', get_permalink())); ?>">
                            <?php esc_html_e('Saved Searches', 'happy-place'); ?>
                        </a>
                    </li>
                    <li class="<?php echo isset($_GET['view']) && $_GET['view'] === 'profile' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('view', 'profile', get_permalink())); ?>">
                            <?php esc_html_e('Profile', 'happy-place'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="hph-dashboard-content">
            <?php
            $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';

            switch ($view) {
                case 'favorites':
                    get_template_part('templates/partials/dashboard/favorites');
                    break;

                case 'saved-searches':
                    get_template_part('templates/partials/dashboard/saved-searches');
                    break;

                case 'profile':
                    get_template_part('templates/partials/dashboard/profile');
                    break;

                default:
                    get_template_part('templates/partials/dashboard/overview');
                    break;
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
