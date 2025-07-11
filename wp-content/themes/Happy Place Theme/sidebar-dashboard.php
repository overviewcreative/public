<?php
/**
 * The sidebar containing the dashboard widgets area
 *
 * @package Happy_Place_Theme
 */

if (!is_active_sidebar('dashboard-sidebar')) {
    return;
}
?>

<aside id="secondary" class="widget-area dashboard-sidebar">
    <?php dynamic_sidebar('dashboard-sidebar'); ?>
</aside>
