<?php
/**
 * Dashboard Profile Section Template
 * 
 * @package HappyPlace
 */

$current_user_id = get_current_user_id();
$agent_data = get_field('agent_details', 'user_' . $current_user_id);
?>

<section id="profile" class="hph-dashboard-section">
    <div class="hph-dashboard-header">
        <h2><?php _e('Edit Profile', 'happy-place'); ?></h2>
    </div>

    <?php get_template_part('template-parts/forms/profile-form'); ?>
</section>
