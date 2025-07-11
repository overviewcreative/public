<?php

/**
 * Template part for displaying the cache management section
 */

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'happy-place'));
}

$cache_manager = new \HappyPlace\Includes\Cache_Manager();
$cache_stats = $cache_manager->get_cache_stats();

// Handle form submission
if (isset($_POST['hph_clear_cache']) && check_admin_referer('hph_clear_cache')) {
    $type = sanitize_text_field($_POST['cache_type']);
    $message = '';
    $type_class = 'success';

    switch ($type) {
        case 'all':
            $results = $cache_manager->clear_all_caches();
            $message = __('All caches have been cleared successfully.', 'happy-place');
            break;
        case 'transients':
            if ($cache_manager->clear_transients()) {
                $message = __('Transients cleared successfully.', 'happy-place');
            }
            break;
        case 'object':
            if ($cache_manager->clear_object_cache()) {
                $message = __('Object cache cleared successfully.', 'happy-place');
            }
            break;
        case 'page':
            if ($cache_manager->clear_page_cache()) {
                $message = __('Page cache cleared successfully.', 'happy-place');
            }
            break;
    }

    if (!empty($message)) {
        echo '<div class="hph-alert hph-alert--' . esc_attr($type_class) . '">' . esc_html($message) . '</div>';
    }

    // Refresh stats after clearing
    $cache_stats = $cache_manager->get_cache_stats();
}
?>

<div class="hph-dashboard-card">
    <div class="hph-dashboard-card-header">
        <h2 class="hph-dashboard-card-title">
            <i class="fas fa-sync-alt"></i>
            <?php esc_html_e('Cache Management', 'happy-place'); ?>
        </h2>
    </div>

    <div class="hph-dashboard-card-body">
        <div class="hph-cache-stats">
            <div class="hph-cache-stat-item">
                <span class="hph-cache-stat-label"><?php esc_html_e('Transients Count:', 'happy-place'); ?></span>
                <span class="hph-cache-stat-value"><?php echo esc_html($cache_stats['transients']); ?></span>
            </div>

            <div class="hph-cache-stat-item">
                <span class="hph-cache-stat-label"><?php esc_html_e('Object Cache:', 'happy-place'); ?></span>
                <span class="hph-cache-stat-value <?php echo $cache_stats['object_cache_enabled'] ? 'active' : 'inactive'; ?>">
                    <?php echo $cache_stats['object_cache_enabled'] ?
                        esc_html__('Enabled', 'happy-place') :
                        esc_html__('Disabled', 'happy-place'); ?>
                </span>
            </div>

            <?php if (!empty($cache_stats['active_cache_plugins'])) : ?>
                <div class="hph-cache-stat-item">
                    <span class="hph-cache-stat-label"><?php esc_html_e('Active Cache Plugins:', 'happy-place'); ?></span>
                    <span class="hph-cache-stat-value">
                        <?php echo esc_html(implode(', ', $cache_stats['active_cache_plugins'])); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" class="hph-cache-form">
            <?php wp_nonce_field('hph_clear_cache'); ?>

            <div class="hph-form-group">
                <label for="cache_type" class="hph-form-label">
                    <?php esc_html_e('Select Cache Type:', 'happy-place'); ?>
                </label>
                <select name="cache_type" id="cache_type" class="hph-form-select">
                    <option value="all"><?php esc_html_e('All Caches', 'happy-place'); ?></option>
                    <option value="transients"><?php esc_html_e('Transients Only', 'happy-place'); ?></option>
                    <option value="object"><?php esc_html_e('Object Cache Only', 'happy-place'); ?></option>
                    <option value="page"><?php esc_html_e('Page Cache Only', 'happy-place'); ?></option>
                </select>
            </div>

            <button type="submit" name="hph_clear_cache" class="hph-btn hph-btn--primary">
                <i class="fas fa-trash-alt"></i>
                <?php esc_html_e('Clear Cache', 'happy-place'); ?>
            </button>
        </form>
    </div>

    <div class="hph-dashboard-card-footer">
        <p class="hph-text-muted">
            <i class="fas fa-info-circle"></i>
            <?php esc_html_e('Clearing cache may temporarily impact site performance while the cache rebuilds.', 'happy-place'); ?>
        </p>
    </div>
</div>

<style>
    .hph-cache-stats {
        margin-bottom: 2rem;
        padding: 1rem;
        background: var(--hph-color-background-light);
        border-radius: var(--hph-border-radius);
    }

    .hph-cache-stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--hph-color-border);
    }

    .hph-cache-stat-item:last-child {
        border-bottom: none;
    }

    .hph-cache-stat-value {
        font-weight: bold;
    }

    .hph-cache-stat-value.active {
        color: var(--hph-color-success);
    }

    .hph-cache-stat-value.inactive {
        color: var(--hph-color-warning);
    }

    .hph-cache-form {
        max-width: 400px;
    }

    .hph-form-group {
        margin-bottom: 1rem;
    }

    .hph-form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .hph-form-select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid var(--hph-color-border);
        border-radius: var(--hph-border-radius);
        margin-bottom: 1rem;
    }

    .hph-alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: var(--hph-border-radius);
    }

    .hph-alert--success {
        background-color: var(--hph-color-success-light);
        color: var(--hph-color-success);
        border: 1px solid var(--hph-color-success);
    }
</style>