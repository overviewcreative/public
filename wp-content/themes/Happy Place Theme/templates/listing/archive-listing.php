<?php
/**
 * Archive Template for Property Listings
 * 
 * This template controls the display of the property listings archive page,
 * including the filters, sorting options, and display modes (grid/map view).
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get filter values
$price_min = isset($_GET['price_min']) ? intval($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? intval($_GET['price_max']) : '';
$bedrooms = isset($_GET['bedrooms']) ? intval($_GET['bedrooms']) : '';
$bathrooms = isset($_GET['bathrooms']) ? intval($_GET['bathrooms']) : '';
$property_type = isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '';
?>

<div class="hph-container">
    <div class="hph-listings-archive">
        <div class="hph-search-section">
            <header class="hph-archive-header">
                <h1><?php _e('Find Your Perfect Property', 'happy-place'); ?></h1>
                <p class="hph-archive-description">
                    <?php _e('Browse our selection of properties or use filters to narrow down your search.', 'happy-place'); ?>
                </p>
            </header>

            <div class="hph-listing-filters">
                <form method="get" class="hph-form" id="property-filters">
                    <div class="hph-grid hph-grid-3">
                        <div class="hph-form-group">
                            <label class="hph-form-label"><?php _e('Price Range', 'happy-place'); ?></label>
                            <div class="hph-form-row">
                                <input type="number" name="price_min" class="hph-form-input" 
                                       placeholder="<?php _e('Min', 'happy-place'); ?>"
                                       value="<?php echo esc_attr($price_min); ?>">
                                <input type="number" name="price_max" class="hph-form-input" 
                                       placeholder="<?php _e('Max', 'happy-place'); ?>"
                                       value="<?php echo esc_attr($price_max); ?>">
                            </div>
                        </div>
                        
                        <div class="hph-form-group">
                            <label class="hph-form-label"><?php _e('Bedrooms', 'happy-place'); ?></label>
                            <select name="bedrooms" class="hph-form-select">
                                <option value=""><?php _e('Any', 'happy-place'); ?></option>
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <option value="<?php echo $i; ?>" <?php selected($bedrooms, $i); ?>>
                                        <?php echo $i . '+'; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="hph-form-group">
                            <label class="hph-form-label"><?php _e('Bathrooms', 'happy-place'); ?></label>
                            <select name="bathrooms" class="hph-form-select">
                                <option value=""><?php _e('Any', 'happy-place'); ?></option>
                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                    <option value="<?php echo $i; ?>" <?php selected($bathrooms, $i); ?>>
                                        <?php echo $i . '+'; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="hph-form-group">
                            <label class="hph-form-label"><?php _e('Property Type', 'happy-place'); ?></label>
                            <select name="property_type" class="hph-form-select">
                                <option value=""><?php _e('All Types', 'happy-place'); ?></option>
                                <?php 
                                $types = get_terms([
                                    'taxonomy' => 'property_type',
                                    'hide_empty' => true
                                ]);
                                foreach ($types as $type) : ?>
                                    <option value="<?php echo esc_attr($type->slug); ?>" 
                                            <?php selected($property_type, $type->slug); ?>>
                                        <?php echo esc_html($type->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="hph-form-actions">
                        <button type="submit" class="hph-btn hph-btn-primary">
                            <i class="fas fa-search"></i> <?php _e('Search', 'happy-place'); ?>
                        </button>
                        <a href="<?php echo get_post_type_archive_link('listing'); ?>" 
                           class="hph-btn hph-btn-secondary">
                            <?php _e('Reset Filters', 'happy-place'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (have_posts()) : ?>
            <div class="hph-listings-grid">
                <?php while (have_posts()) : the_post(); 
                    $price = get_field('price');
                    $bedrooms = get_field('bedrooms');
                    $bathrooms = get_field('bathrooms');
                    $square_feet = get_field('square_feet');
                    ?>
                    
                    <article <?php post_class('hph-listing-card'); ?>>
                        <a href="<?php the_permalink(); ?>" class="hph-listing-thumbnail">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('listing-thumb'); ?>
                            <?php else : ?>
                                <img src="<?php echo HPH_THEME_URI; ?>/assets/images/placeholder.jpg" 
                                     alt="<?php _e('Property Image', 'happy-place'); ?>">
                            <?php endif; ?>
                            
                            <?php if (get_field('featured')) : ?>
                                <span class="hph-badge hph-badge-primary">
                                    <?php _e('Featured', 'happy-place'); ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <div class="hph-listing-details">
                            <h2 class="hph-listing-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            
                            <div class="hph-listing-meta">
                                <?php if ($price) : ?>
                                    <div class="hph-badge hph-badge-primary">
                                        <?php echo HPH_Theme::format_price($price); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="hph-listing-specs">
                                    <?php if ($bedrooms) : ?>
                                        <span><i class="fas fa-bed"></i> <?php echo $bedrooms; ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($bathrooms) : ?>
                                        <span><i class="fas fa-bath"></i> <?php echo $bathrooms; ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($square_feet) : ?>
                                        <span><i class="fas fa-ruler-combined"></i> 
                                            <?php echo number_format($square_feet); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php 
                            $excerpt = get_the_excerpt();
                            if ($excerpt) : ?>
                                <div class="hph-listing-excerpt">
                                    <?php echo wp_trim_words($excerpt, 20); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <div class="hph-pagination">
                <?php 
                the_posts_pagination([
                    'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'happy-place'),
                    'next_text' => __('Next', 'happy-place') . ' <i class="fas fa-chevron-right"></i>',
                    'class' => 'hph-pagination'
                ]); 
                ?>
            </div>

        <?php else : ?>
            <div class="hph-card hph-no-results">
                <p><?php _e('No properties found matching your criteria.', 'happy-place'); ?></p>
                <a href="<?php echo get_post_type_archive_link('listing'); ?>" 
                   class="hph-btn hph-btn-secondary">
                    <?php _e('Reset Search', 'happy-place'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
