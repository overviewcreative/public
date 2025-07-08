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
?>
<div class="hph-listings-archive-container">
    <div class="hph-search-section">
        <div class="hph-search-header">
            <h1>Find Your Perfect Property</h1>
            <div class="hph-view-modes">
                <button class="hph-view-toggle active" data-view="grid">
                    <i class="icon-grid"></i>
                </button>
                <button class="hph-view-toggle" data-view="map">
                    <i class="icon-map"></i>
                </button>
                <button class="hph-view-toggle" data-view="split">
                    <i class="icon-split"></i>
                </button>
            </div>
        </div>

        <form class="hph-search-form" id="listings-search-form">
            <div class="hph-search-input-group">
                <input 
                    type="text" 
                    id="location-search" 
                    class="hph-form-input" 
                    placeholder="Search by address, neighborhood, or MLS#"
                    value="<?php echo esc_attr(get_query_var('search')); ?>"
                    autocomplete="off"
                >
                <div id="location-suggestions" class="hph-autocomplete-suggestions"></div>
            </div>

            <div class="hph-filter-chips">
                <div class="hph-filter-group">
                    <h4>Price Range</h4>
                    <div class="hph-chip-container">
                        <?php
                        $price_range = get_query_var('price_range', 'any');
                        $price_ranges = array(
                            'any' => 'Any',
                            'under-500k' => 'Under $500K',
                            '500k-800k' => '$500K - $800K',
                            '800k-1m' => '$800K - $1M',
                            'over-1m' => 'Over $1M'
                        );
                        foreach ($price_ranges as $value => $label) :
                        ?>
                            <button class="hph-filter-chip <?php echo $price_range === $value ? 'active' : ''; ?>" 
                                    data-filter="price" 
                                    data-value="<?php echo esc_attr($value); ?>">
                                <?php echo esc_html($label); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hph-filter-group">
                    <h4>Bedrooms</h4>
                    <div class="hph-chip-container">
                        <?php
                        $bedrooms = get_query_var('bedrooms', 'any');
                        $bedroom_options = array(
                            'any' => 'Any',
                            '1' => '1+',
                            '2' => '2+',
                            '3' => '3+',
                            '4' => '4+'
                        );
                        foreach ($bedroom_options as $value => $label) :
                        ?>
                            <button class="hph-filter-chip <?php echo $bedrooms === $value ? 'active' : ''; ?>" 
                                    data-filter="bedrooms" 
                                    data-value="<?php echo esc_attr($value); ?>">
                                <?php echo esc_html($label); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hph-filter-group">
                    <h4>Property Type</h4>
                    <div class="hph-chip-container">
                        <?php
                        $property_type = get_query_var('property_type', 'all');
                        $property_types = array(
                            'all' => 'All Types',
                            'single-family' => 'Single Family',
                            'townhouse' => 'Townhouse',
                            'condo' => 'Condo'
                        );
                        foreach ($property_types as $value => $label) :
                        ?>
                            <button class="hph-filter-chip <?php echo $property_type === $value ? 'active' : ''; ?>" 
                                    data-filter="property-type" 
                                    data-value="<?php echo esc_attr($value); ?>">
                                <?php echo esc_html($label); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hph-filter-group">
                    <h4>Features</h4>
                    <div class="hph-chip-container">
                        <?php
                        $selected_features = (array) get_query_var('features', array());
                        $features = array(
                            'pool' => 'Pool',
                            'garage' => 'Garage',
                            'fireplace' => 'Fireplace',
                            'waterfront' => 'Waterfront'
                        );
                        foreach ($features as $value => $label) :
                        ?>
                            <button class="hph-filter-chip <?php echo in_array($value, $selected_features) ? 'active' : ''; ?>" 
                                    data-filter="features" 
                                    data-value="<?php echo esc_attr($value); ?>">
                                <?php echo esc_html($label); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="hph-search-actions">
                <button type="submit" class="hph-btn hph-btn-primary">Search</button>
                <button type="reset" class="hph-btn hph-btn-secondary">Reset Filters</button>
            </div>
        </form>
    </div>

    <div class="hph-listings-display">
        <div class="hph-results-header">
            <div class="hph-results-count">
                <span id="total-properties"><?php echo $wp_query->found_posts; ?></span> Properties Found
            </div>
            <div class="hph-sort-options">
                <?php
                $sort = get_query_var('sort', 'newest');
                ?>
                <select id="sort-listings">
                    <option value="newest" <?php selected($sort, 'newest'); ?>>Newest Listings</option>
                    <option value="price-low" <?php selected($sort, 'price-low'); ?>>Price: Low to High</option>
                    <option value="price-high" <?php selected($sort, 'price-high'); ?>>Price: High to Low</option>
                    <option value="largest" <?php selected($sort, 'largest'); ?>>Largest</option>
                </select>
            </div>
        </div>

        <div id="listings-container" class="hph-listings-container hph-grid-view">
            <?php if (have_posts()) : while (have_posts()) : the_post(); 
                $price = get_field('price');
                $beds = get_field('bedrooms');
                $baths = get_field('bathrooms');
                $sqft = get_field('square_footage');
                $short_description = get_field('short_description');
                $highlight_badges = get_field('highlight_badges');
            ?>
                <div class="hph-listing-card" data-id="<?php echo get_the_ID(); ?>">
                    <div class="hph-listing-image">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('large'); ?>
                        <?php else : ?>
                            <img src="<?php echo get_theme_file_uri('assets/images/placeholder.jpg'); ?>" alt="No image available">
                        <?php endif; ?>
                        <div class="hph-listing-price"><?php echo '$ ' . number_format($price); ?></div>
                        <?php if (!empty($highlight_badges)) : ?>
                            <div class="hph-listing-badges">
                                <?php foreach ($highlight_badges as $badge) : ?>
                                    <span class="hph-badge hph-badge-<?php echo esc_attr($badge); ?>">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $badge))); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hph-listing-details">
                        <h3><?php the_title(); ?></h3>
                        <?php if ($short_description) : ?>
                            <p class="hph-listing-description"><?php echo esc_html($short_description); ?></p>
                        <?php endif; ?>
                        <div class="hph-listing-meta">
                            <?php if ($beds) : ?>
                                <span><?php echo esc_html($beds); ?> BD</span>
                            <?php endif; ?>
                            <?php if ($baths) : ?>
                                <span><?php echo esc_html($baths); ?> BA</span>
                            <?php endif; ?>
                            <?php if ($sqft) : ?>
                                <span><?php echo number_format($sqft); ?> Ft²</span>
                            <?php endif; ?>
                        </div>
                        <div class="hph-listing-actions">
                            <a href="<?php the_permalink(); ?>" class="hph-btn hph-btn-secondary">View Details</a>
                            <?php if (is_user_logged_in()) : ?>
                                <button class="hph-btn-favorite" data-listing-id="<?php echo get_the_ID(); ?>">
                                    <i class="icon-heart"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <?php get_template_part('templates/partials/pagination'); ?>
            
            <?php else : ?>
                <div class="hph-no-results">
                    <p>No properties match your search criteria.</p>
                    <button type="reset" class="hph-btn hph-btn-primary" form="listings-search-form">Reset Filters</button>
                </div>
            <?php endif; ?>
        </div>

        <div id="map-container" class="hph-map-view">
            <div id="listings-map" class="hph-full-map"></div>
            <div id="map-listings-preview" class="hph-map-listings-preview">
                <!-- Map listing previews will be dynamically populated -->
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
