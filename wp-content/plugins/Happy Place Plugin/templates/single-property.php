<?php
/**
 * Template Name: Single Property
 * 
 * @package HappyPlace
 */

get_header();

while (have_posts()) : the_post();
    $gallery = get_field('property_gallery');
    $features = get_field('property_features');
    $details = get_field('property_details');
    $location = get_field('property_location');
    $documents = get_field('property_documents');
    $agent = get_field('property_agent');
?>

<div class="hph-breadcrumbs">
    <div class="hph-container">
        <div class="hph-breadcrumbs-list">
            <div class="hph-breadcrumb-item">
                <a href="<?php echo home_url('/properties'); ?>" class="hph-breadcrumb-link">Properties</a>
            </div>
            <div class="hph-breadcrumb-item">
                <?php the_title(); ?>
            </div>
        </div>
    </div>
</div>

<!-- Hero Section -->
<section class="hph-listing-hero">
    <?php if ($gallery && isset($gallery[0])) : ?>
        <img src="<?php echo esc_url($gallery[0]['url']); ?>" alt="<?php the_title(); ?>" class="hph-listing-hero-image">
    <?php endif; ?>
    <div class="hph-listing-hero-overlay">
        <div class="hph-listing-hero-price">
            <?php echo esc_html(get_field('property_price')); ?>
        </div>
        <div class="hph-listing-hero-address">
            <?php echo esc_html(get_field('property_address')); ?>
        </div>
        <div class="hph-listing-hero-stats">
            <?php if ($details['bedrooms']) : ?>
                <span><?php echo esc_html($details['bedrooms']); ?> beds</span>
            <?php endif; ?>
            <?php if ($details['bathrooms']) : ?>
                <span><?php echo esc_html($details['bathrooms']); ?> baths</span>
            <?php endif; ?>
            <?php if ($details['square_footage']) : ?>
                <span><?php echo number_format($details['square_footage']); ?> sq ft</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Open Houses -->
<?php
$today = date('Y-m-d H:i:s');
$open_houses = get_field('property_open_houses');

if ($open_houses) {
    // Separate upcoming and past open houses
    $upcoming_open_houses = array_filter($open_houses, function($oh) use ($today) {
        return strtotime($oh['date'] . ' ' . $oh['start_time']) > strtotime($today);
    });
    
    $past_open_houses = array_filter($open_houses, function($oh) use ($today) {
        return strtotime($oh['date'] . ' ' . $oh['start_time']) <= strtotime($today);
    });
    
    // Sort upcoming open houses by date
    usort($upcoming_open_houses, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
    // Sort past open houses by date, most recent first
    usort($past_open_houses, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<?php if (!empty($upcoming_open_houses)) : ?>
<div class="hph-container">
    <div class="hph-open-houses">
        <h2 class="hph-section-title">Upcoming Open Houses</h2>
        <div class="hph-open-houses-grid">
            <?php foreach ($upcoming_open_houses as $open_house) : ?>
                <div class="hph-open-house-item">
                    <div class="hph-open-house-date">
                        <?php 
                        $date = DateTime::createFromFormat('Y-m-d', $open_house['date']);
                        echo $date ? $date->format('l, F j, Y') : '';
                        ?>
                    </div>
                    <div class="hph-open-house-time">
                        <?php 
                        echo esc_html($open_house['start_time']);
                        if (!empty($open_house['end_time'])) {
                            echo ' - ' . esc_html($open_house['end_time']);
                        }
                        ?>
                    </div>
                    <?php if (!empty($open_house['host'])) : ?>
                        <div class="hph-open-house-host">
                            Hosted by: <?php echo esc_html($open_house['host']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($open_house['notes'])) : ?>
                        <div class="hph-open-house-notes">
                            <?php echo esc_html($open_house['notes']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($past_open_houses)) : ?>
<div class="hph-container">
    <div class="hph-open-houses hph-open-houses--past">
        <h2 class="hph-section-title">Past Open Houses</h2>
        <div class="hph-open-houses-grid">
            <?php foreach ($past_open_houses as $open_house) : ?>
                <div class="hph-open-house-item hph-open-house-item--past">
                    <div class="hph-open-house-date">
                        <?php 
                        $date = DateTime::createFromFormat('Y-m-d', $open_house['date']);
                        echo $date ? $date->format('l, F j, Y') : '';
                        ?>
                    </div>
                    <div class="hph-open-house-time">
                        <?php 
                        echo esc_html($open_house['start_time']);
                        if (!empty($open_house['end_time'])) {
                            echo ' - ' . esc_html($open_house['end_time']);
                        }
                        ?>
                    </div>
                    <?php if (!empty($open_house['host'])) : ?>
                        <div class="hph-open-house-host">
                            Hosted by: <?php echo esc_html($open_house['host']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Gallery Section -->
<?php if ($gallery) : ?>
<div class="hph-gallery">
    <div class="hph-gallery-grid">
        <?php foreach (array_slice($gallery, 0, 5) as $index => $image) : ?>
            <div class="hph-gallery-item <?php echo $index === 0 ? 'hph-gallery-main' : ''; ?>">
                <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                <?php if ($index === 4 && count($gallery) > 5) : ?>
                    <div class="hph-gallery-count">+<?php echo count($gallery) - 5; ?> photos</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Property Meta -->
<div class="hph-container">
    <div class="hph-property-meta">
        <div class="hph-meta-grid">
            <?php if ($details) : foreach ($details as $key => $value) : if ($value) : ?>
                <div class="hph-meta-item">
                    <div class="hph-meta-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></div>
                    <div class="hph-meta-value"><?php echo esc_html($value); ?></div>
                </div>
            <?php endif; endforeach; endif; ?>
        </div>
    </div>

    <!-- Open Houses -->
    <?php if (have_rows('property_open_houses')) : ?>
    <div class="hph-open-houses">
        <h2>Upcoming Open Houses</h2>
        <div class="hph-open-house-grid">
            <?php 
            while (have_rows('property_open_houses')) : the_row();
                $date = get_sub_field('date');
                $start_time = get_sub_field('start_time');
                $end_time = get_sub_field('end_time');
                $host = get_sub_field('host');
                
                // Only show future open houses
                if (strtotime($date) >= strtotime('today')) :
            ?>
                <div class="hph-open-house-item">
                    <div class="hph-open-house-date">
                        <i class="fas fa-calendar"></i>
                        <?php echo esc_html(date('l, F j, Y', strtotime($date))); ?>
                    </div>
                    <div class="hph-open-house-time">
                        <i class="fas fa-clock"></i>
                        <?php echo esc_html($start_time . ' - ' . $end_time); ?>
                    </div>
                    <?php if ($host) : ?>
                    <div class="hph-open-house-host">
                        <i class="fas fa-user"></i>
                        Hosted by: <?php echo esc_html($host); ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php 
                endif;
            endwhile; 
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Property Description -->
    <div class="hph-property-description">
        <div class="hph-description-content">
            <?php the_content(); ?>
        </div>
    </div>

    <!-- Features Section -->
    <?php if ($features) : ?>
    <div class="hph-features-grid">
        <?php foreach ($features as $feature) : ?>
            <div class="hph-feature-item">
                <div class="hph-feature-icon">
                    <i class="<?php echo esc_attr($feature['icon']); ?>"></i>
                </div>
                <div class="hph-feature-content">
                    <div class="hph-feature-label"><?php echo esc_html($feature['label']); ?></div>
                    <div class="hph-feature-value"><?php echo esc_html($feature['value']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Property Documents -->
    <?php if ($documents) : ?>
    <div class="hph-documents">
        <h2>Property Documents</h2>
        <div class="hph-document-list">
            <?php foreach ($documents as $document) : ?>
                <a href="<?php echo esc_url($document['file']['url']); ?>" class="hph-document-item">
                    <div class="hph-document-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="hph-document-content">
                        <div class="hph-document-title"><?php echo esc_html($document['title']); ?></div>
                        <div class="hph-document-meta"><?php echo size_format(filesize(get_attached_file($document['file']['ID']))); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Property Timeline -->
    <?php if (have_rows('property_timeline')) : ?>
    <div class="hph-timeline">
        <div class="hph-timeline-list">
            <?php while (have_rows('property_timeline')) : the_row(); ?>
                <div class="hph-timeline-item">
                    <div class="hph-timeline-date"><?php echo esc_html(get_sub_field('date')); ?></div>
                    <div class="hph-timeline-title"><?php echo esc_html(get_sub_field('title')); ?></div>
                    <div class="hph-timeline-description"><?php echo esc_html(get_sub_field('description')); ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Agent Contact -->
    <?php if ($agent) : ?>
    <div class="hph-contact-form">
        <h3>Contact Agent</h3>
        <?php
        // Assuming you're using Contact Form 7 or similar
        $contact_form_id = get_field('contact_form_id', 'option');
        if ($contact_form_id) {
            echo do_shortcode('[contact-form-7 id="' . $contact_form_id . '"]');
        }
        ?>
    </div>
    <?php endif; ?>

    <!-- Similar Properties -->
    <?php
    $similar_properties = new WP_Query([
        'post_type' => 'property',
        'posts_per_page' => 3,
        'post__not_in' => [get_the_ID()],
        'meta_query' => [
            [
                'key' => 'property_price',
                'value' => [get_field('property_price') * 0.8, get_field('property_price') * 1.2],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ]
        ]
    ]);

    if ($similar_properties->have_posts()) :
    ?>
    <div class="hph-similar-listings">
        <div class="hph-similar-header">
            <h2 class="hph-similar-title">Similar Properties</h2>
            <p class="hph-similar-subtitle">You might also like these properties</p>
        </div>
        <div class="hph-listing-grid">
            <?php
            while ($similar_properties->have_posts()) : $similar_properties->the_post();
                get_template_part('template-parts/card', 'property');
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
endwhile;
get_footer();
?>
