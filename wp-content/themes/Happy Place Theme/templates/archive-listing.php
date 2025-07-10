<?php
/**
 * Archive Template for Property Listings
 * 
 * This template displays the main property listings archive with advanced filtering,
 * search functionality, view modes, and sorting options.
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get current query vars and sanitize them
$current_filters = [
    'price_min' => isset($_GET['price_min']) ? absint($_GET['price_min']) : '',
    'price_max' => isset($_GET['price_max']) ? absint($_GET['price_max']) : '',
    'bedrooms' => isset($_GET['bedrooms']) ? absint($_GET['bedrooms']) : '',
    'bathrooms' => isset($_GET['bathrooms']) ? absint($_GET['bathrooms']) : '',
    'property_type' => isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '',
    'location' => isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '',
    'features' => isset($_GET['features']) ? array_map('sanitize_text_field', (array)$_GET['features']) : [],
    'sort_by' => isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'date',
    'view_mode' => isset($_GET['view_mode']) ? sanitize_text_field($_GET['view_mode']) : 'cards',
    'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : ''
];

// Get available filter options
$property_types = get_terms([
    'taxonomy' => 'property_type',
    'hide_empty' => true,
]);

$locations = get_terms([
    'taxonomy' => 'listing_location',
    'hide_empty' => true,
]);

// Build query args
$query_args = [
    'post_type' => 'listing',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ?: 1,
    'post_status' => 'publish',
    'meta_query' => ['relation' => 'AND'],
    'tax_query' => ['relation' => 'AND']
];

// Add search query
if (!empty($current_filters['search'])) {
    $query_args['s'] = $current_filters['search'];
}

// Add price filters
if (!empty($current_filters['price_min'])) {
    $query_args['meta_query'][] = [
        'key' => 'price',
        'value' => $current_filters['price_min'],
        'type' => 'NUMERIC',
        'compare' => '>='
    ];
}

if (!empty($current_filters['price_max'])) {
    $query_args['meta_query'][] = [
        'key' => 'price',
        'value' => $current_filters['price_max'],
        'type' => 'NUMERIC',
        'compare' => '<='
    ];
}

// Add bedroom filter
if (!empty($current_filters['bedrooms'])) {
    $query_args['meta_query'][] = [
        'key' => 'bedrooms',
        'value' => $current_filters['bedrooms'],
        'type' => 'NUMERIC',
        'compare' => '>='
    ];
}

// Add bathroom filter
if (!empty($current_filters['bathrooms'])) {
    $query_args['meta_query'][] = [
        'key' => 'bathrooms',
        'value' => $current_filters['bathrooms'],
        'type' => 'NUMERIC',
        'compare' => '>='
    ];
}

// Add property type filter
if (!empty($current_filters['property_type'])) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'property_type',
        'field' => 'slug',
        'terms' => $current_filters['property_type']
    ];
}

// Add location filter
if (!empty($current_filters['location'])) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'listing_location',
        'field' => 'slug',
        'terms' => $current_filters['location']
    ];
}

// Add features filter
if (!empty($current_filters['features'])) {
    $query_args['meta_query'][] = [
        'key' => 'features',
        'value' => serialize($current_filters['features']),
        'compare' => 'LIKE'
    ];
}

// Add sorting
switch ($current_filters['sort_by']) {
    case 'price_low':
        $query_args['meta_key'] = 'price';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'ASC';
        break;
    case 'price_high':
        $query_args['meta_key'] = 'price';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'DESC';
        break;
    case 'newest':
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
    case 'oldest':
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'ASC';
        break;
    case 'featured':
        $query_args['meta_query'][] = [
            'key' => 'highlight_badges',
            'value' => 'featured',
            'compare' => 'LIKE'
        ];
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
    default:
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
}

// Execute query
$listings_query = new WP_Query($query_args);

// Get total count for display
$total_properties = $listings_query->found_posts;
?>

<div class="hph-listings-archive">
    <!-- Hero Search Section -->
    <section class="hph-archive-hero">
        <div class="hph-container">
            <div class="hph-hero-content">
                <h1 class="hph-hero-title">
                    <?php 
                    if (!empty($current_filters['search']) || array_filter($current_filters)) {
                        printf(__('Search Results', 'happy-place'));
                    } else {
                        printf(__('Find Your Perfect Home', 'happy-place'));
                    }
                    ?>
                </h1>
                <p class="hph-hero-subtitle">
                    <?php 
                    printf(
                        _n('%d property available', '%d properties available', $total_properties, 'happy-place'),
                        $total_properties
                    );
                    ?>
                </p>
                
                <!-- Quick Search Bar -->
                <div class="hph-quick-search">
                    <form method="get" class="hph-search-form">
                        <div class="hph-search-input-group">
                            <input type="text" 
                                   name="search" 
                                   placeholder="<?php esc_attr_e('Search by address, city, or ZIP code...', 'happy-place'); ?>"
                                   value="<?php echo esc_attr($current_filters['search']); ?>"
                                   class="hph-search-input">
                            <button type="submit" class="hph-search-btn">
                                <i class="fas fa-search"></i>
                                <span class="sr-only"><?php esc_html_e('Search', 'happy-place'); ?></span>
                            </button>
                        </div>
                        <!-- Preserve other filters -->
                        <?php foreach ($current_filters as $key => $value) : ?>
                            <?php if ($key !== 'search' && !empty($value)) : ?>
                                <?php if (is_array($value)) : ?>
                                    <?php foreach ($value as $val) : ?>
                                        <input type="hidden" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($val); ?>">
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div class="hph-container">
        <div class="hph-listings-layout">
            <!-- Sidebar Filters -->
            <aside class="hph-listings-sidebar">
                <div class="hph-filters-container">
                    <div class="hph-filters-header">
                        <h3><?php esc_html_e('Filter Properties', 'happy-place'); ?></h3>
                        <?php if (array_filter($current_filters)) : ?>
                            <a href="<?php echo get_post_type_archive_link('listing'); ?>" class="hph-clear-filters">
                                <?php esc_html_e('Clear All', 'happy-place'); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <form method="get" class="hph-filters-form" id="listingFilters">
                        <!-- Preserve search query -->
                        <?php if (!empty($current_filters['search'])) : ?>
                            <input type="hidden" name="search" value="<?php echo esc_attr($current_filters['search']); ?>">
                        <?php endif; ?>

                        <!-- Price Range -->
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Price Range', 'happy-place'); ?></h4>
                            <div class="hph-price-inputs">
                                <input type="number" 
                                       name="price_min" 
                                       placeholder="<?php esc_attr_e('Min Price', 'happy-place'); ?>"
                                       value="<?php echo esc_attr($current_filters['price_min']); ?>"
                                       class="hph-price-input">
                                <span class="hph-price-separator">—</span>
                                <input type="number" 
                                       name="price_max" 
                                       placeholder="<?php esc_attr_e('Max Price', 'happy-place'); ?>"
                                       value="<?php echo esc_attr($current_filters['price_max']); ?>"
                                       class="hph-price-input">
                            </div>
                        </div>

                        <!-- Property Type -->
                        <?php if (!empty($property_types)) : ?>
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Property Type', 'happy-place'); ?></h4>
                            <select name="property_type" class="hph-filter-select">
                                <option value=""><?php esc_html_e('Any Type', 'happy-place'); ?></option>
                                <?php foreach ($property_types as $type) : ?>
                                    <option value="<?php echo esc_attr($type->slug); ?>" 
                                            <?php selected($current_filters['property_type'], $type->slug); ?>>
                                        <?php echo esc_html($type->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Location -->
                        <?php if (!empty($locations)) : ?>
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Location', 'happy-place'); ?></h4>
                            <select name="location" class="hph-filter-select">
                                <option value=""><?php esc_html_e('Any Location', 'happy-place'); ?></option>
                                <?php foreach ($locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location->slug); ?>" 
                                            <?php selected($current_filters['location'], $location->slug); ?>>
                                        <?php echo esc_html($location->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Bedrooms -->
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Bedrooms', 'happy-place'); ?></h4>
                            <div class="hph-bedroom-options">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <label class="hph-bedroom-option <?php echo $current_filters['bedrooms'] == $i ? 'active' : ''; ?>">
                                        <input type="radio" 
                                               name="bedrooms" 
                                               value="<?php echo $i; ?>" 
                                               <?php checked($current_filters['bedrooms'], $i); ?>>
                                        <span><?php echo $i; ?>+</span>
                                    </label>
                                <?php endfor; ?>
                                <label class="hph-bedroom-option <?php echo empty($current_filters['bedrooms']) ? 'active' : ''; ?>">
                                    <input type="radio" 
                                           name="bedrooms" 
                                           value="" 
                                           <?php checked($current_filters['bedrooms'], ''); ?>>
                                    <span><?php esc_html_e('Any', 'happy-place'); ?></span>
                                </label>
                            </div>
                        </div>

                        <!-- Bathrooms -->
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Bathrooms', 'happy-place'); ?></h4>
                            <div class="hph-bathroom-options">
                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                    <label class="hph-bathroom-option <?php echo $current_filters['bathrooms'] == $i ? 'active' : ''; ?>">
                                        <input type="radio" 
                                               name="bathrooms" 
                                               value="<?php echo $i; ?>" 
                                               <?php checked($current_filters['bathrooms'], $i); ?>>
                                        <span><?php echo $i; ?>+</span>
                                    </label>
                                <?php endfor; ?>
                                <label class="hph-bathroom-option <?php echo empty($current_filters['bathrooms']) ? 'active' : ''; ?>">
                                    <input type="radio" 
                                           name="bathrooms" 
                                           value="" 
                                           <?php checked($current_filters['bathrooms'], ''); ?>>
                                    <span><?php esc_html_e('Any', 'happy-place'); ?></span>
                                </label>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="hph-filter-group">
                            <h4 class="hph-filter-title"><?php esc_html_e('Features', 'happy-place'); ?></h4>
                            <div class="hph-features-checkboxes">
                                <?php 
                                $available_features = [
                                    'pool' => __('Swimming Pool', 'happy-place'),
                                    'garage' => __('Garage', 'happy-place'),
                                    'fireplace' => __('Fireplace', 'happy-place'),
                                    'deck_patio' => __('Deck/Patio', 'happy-place'),
                                    'basement' => __('Basement', 'happy-place'),
                                    'hardwood_floors' => __('Hardwood Floors', 'happy-place'),
                                    'updated_kitchen' => __('Updated Kitchen', 'happy-place'),
                                    'walk_in_closet' => __('Walk-in Closet', 'happy-place')
                                ];
                                
                                foreach ($available_features as $feature_key => $feature_label) :
                                    $checked = in_array($feature_key, $current_filters['features']);
                                ?>
                                    <label class="hph-feature-checkbox">
                                        <input type="checkbox" 
                                               name="features[]" 
                                               value="<?php echo esc_attr($feature_key); ?>"
                                               <?php checked($checked, true); ?>>
                                        <span class="hph-checkmark"></span>
                                        <span class="hph-feature-label"><?php echo esc_html($feature_label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="hph-filter-actions">
                            <button type="submit" class="hph-btn hph-btn--primary hph-btn--full">
                                <?php esc_html_e('Apply Filters', 'happy-place'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="hph-listings-main">
                <!-- Results Header -->
                <div class="hph-results-header">
                    <div class="hph-results-info">
                        <h2 class="hph-results-count">
                            <?php 
                            printf(
                                _n('%d Property Found', '%d Properties Found', $total_properties, 'happy-place'),
                                $total_properties
                            );
                            ?>
                        </h2>
                        
                        <!-- Active Filters -->
                        <?php if (array_filter($current_filters)) : ?>
                            <div class="hph-active-filters">
                                <?php if (!empty($current_filters['search'])) : ?>
                                    <span class="hph-filter-tag">
                                        <?php printf(__('Search: "%s"', 'happy-place'), esc_html($current_filters['search'])); ?>
                                        <a href="<?php echo remove_query_arg('search'); ?>" class="hph-remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($current_filters['price_min']) || !empty($current_filters['price_max'])) : ?>
                                    <span class="hph-filter-tag">
                                        <?php 
                                        if (!empty($current_filters['price_min']) && !empty($current_filters['price_max'])) {
                                            printf(__('Price: $%s - $%s', 'happy-place'), 
                                                   number_format($current_filters['price_min']), 
                                                   number_format($current_filters['price_max']));
                                        } elseif (!empty($current_filters['price_min'])) {
                                            printf(__('Price: $%s+', 'happy-place'), number_format($current_filters['price_min']));
                                        } else {
                                            printf(__('Price: Under $%s', 'happy-place'), number_format($current_filters['price_max']));
                                        }
                                        ?>
                                        <a href="<?php echo remove_query_arg(['price_min', 'price_max']); ?>" class="hph-remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($current_filters['bedrooms'])) : ?>
                                    <span class="hph-filter-tag">
                                        <?php printf(__('%d+ Bedrooms', 'happy-place'), $current_filters['bedrooms']); ?>
                                        <a href="<?php echo remove_query_arg('bedrooms'); ?>" class="hph-remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($current_filters['bathrooms'])) : ?>
                                    <span class="hph-filter-tag">
                                        <?php printf(__('%d+ Bathrooms', 'happy-place'), $current_filters['bathrooms']); ?>
                                        <a href="<?php echo remove_query_arg('bathrooms'); ?>" class="hph-remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="hph-results-controls">
                        <!-- View Mode Toggle -->
                        <div class="hph-view-toggle">
                            <a href="<?php echo add_query_arg('view_mode', 'cards'); ?>" 
                               class="hph-view-btn <?php echo $current_filters['view_mode'] === 'cards' ? 'active' : ''; ?>"
                               title="<?php esc_attr_e('Card View', 'happy-place'); ?>">
                                <i class="fas fa-th-large"></i>
                            </a>
                            <a href="<?php echo add_query_arg('view_mode', 'list'); ?>" 
                               class="hph-view-btn <?php echo $current_filters['view_mode'] === 'list' ? 'active' : ''; ?>"
                               title="<?php esc_attr_e('List View', 'happy-place'); ?>">
                                <i class="fas fa-list"></i>
                            </a>
                            <a href="<?php echo add_query_arg('view_mode', 'map'); ?>" 
                               class="hph-view-btn <?php echo $current_filters['view_mode'] === 'map' ? 'active' : ''; ?>"
                               title="<?php esc_attr_e('Map View', 'happy-place'); ?>">
                                <i class="fas fa-map"></i>
                            </a>
                        </div>

                        <!-- Sort Options -->
                        <div class="hph-sort-controls">
                            <label for="sortBy" class="sr-only"><?php esc_html_e('Sort by', 'happy-place'); ?></label>
                            <select name="sort_by" id="sortBy" class="hph-sort-select" onchange="this.form.submit()">
                                <option value="newest" <?php selected($current_filters['sort_by'], 'newest'); ?>>
                                    <?php esc_html_e('Newest First', 'happy-place'); ?>
                                </option>
                                <option value="price_low" <?php selected($current_filters['sort_by'], 'price_low'); ?>>
                                    <?php esc_html_e('Price: Low to High', 'happy-place'); ?>
                                </option>
                                <option value="price_high" <?php selected($current_filters['sort_by'], 'price_high'); ?>>
                                    <?php esc_html_e('Price: High to Low', 'happy-place'); ?>
                                </option>
                                <option value="featured" <?php selected($current_filters['sort_by'], 'featured'); ?>>
                                    <?php esc_html_e('Featured First', 'happy-place'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Results Content -->
                <div class="hph-results-content">
                    <?php if ($listings_query->have_posts()) : ?>
                        
                        <?php if ($current_filters['view_mode'] === 'map') : ?>
                            <!-- Map View -->
                            <div class="hph-map-container">
                                <div id="listingsMap" 
                                     class="hph-listings-map"
                                     data-markers='<?php
                                        $markers = [];
                                        while ($listings_query->have_posts()) {
                                            $listings_query->the_post();
                                            $lat = get_field('latitude');
                                            $lng = get_field('longitude');
                                            if ($lat && $lng) {
                                                $markers[] = [
                                                    'id' => get_the_ID(),
                                                    'lat' => floatval($lat),
                                                    'lng' => floatval($lng),
                                                    'title' => get_the_title(),
                                                    'price' => get_field('price'),
                                                    'address' => get_field('full_address'),
                                                    'beds' => get_field('bedrooms'),
                                                    'baths' => get_field('bathrooms'),
                                                    'sqft' => get_field('square_footage'),
                                                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                                                    'url' => get_permalink()
                                                ];
                                            }
                                        }
                                        wp_reset_postdata();
                                        echo esc_attr(json_encode($markers));
                                     ?>'
                                     data-clusterer="true"
                                     data-fit-bounds="true">
                                </div>
                                <div class="hph-map-listings">
                                    <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                                        <div class="hph-map-listing-card" data-listing-id="<?php echo get_the_ID(); ?>">
                                            <?php 
                                            get_template_part('template-parts/cards/listing-swipe-card', null, [
                                                'post_id' => get_the_ID(),
                                                'size' => 'small'
                                            ]); 
                                            ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            
                        <?php elseif ($current_filters['view_mode'] === 'list') : ?>
                            <!-- List View -->
                            <div class="hph-listings-list">
                                <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                                    <article class="hph-listing-list-item">
                                        <?php 
                                        get_template_part('template-parts/cards/listing-list-card', null, [
                                            'post_id' => get_the_ID()
                                        ]); 
                                        ?>
                                    </article>
                                <?php endwhile; ?>
                            </div>
                            
                        <?php else : ?>
                            <!-- Card View (Default) -->
                            <div class="hph-listings-grid">
                                <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                                    <div class="hph-listing-card-wrapper">
                                        <?php 
                                        get_template_part('template-parts/cards/listing-swipe-card', null, [
                                            'post_id' => get_the_ID(),
                                            'size' => 'default'
                                        ]); 
                                        ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Pagination -->
                        <div class="hph-pagination-wrapper">
                            <?php
                            $pagination_args = [
                                'total' => $listings_query->max_num_pages,
                                'current' => max(1, get_query_var('paged')),
                                'format' => '?paged=%#%',
                                'show_all' => false,
                                'end_size' => 1,
                                'mid_size' => 2,
                                'prev_next' => true,
                                'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'happy-place'),
                                'next_text' => __('Next', 'happy-place') . ' <i class="fas fa-chevron-right"></i>',
                                'add_args' => array_filter($current_filters),
                                'class' => 'hph-pagination'
                            ];
                            
                            echo paginate_links($pagination_args);
                            ?>
                        </div>

                    <?php else : ?>
                        <!-- No Results -->
                        <div class="hph-no-results">
                            <div class="hph-no-results-content">
                                <i class="fas fa-search hph-no-results-icon"></i>
                                <h3 class="hph-no-results-title"><?php esc_html_e('No Properties Found', 'happy-place'); ?></h3>
                                <p class="hph-no-results-message">
                                    <?php esc_html_e('We couldn\'t find any properties matching your search criteria. Try adjusting your filters or search terms.', 'happy-place'); ?>
                                </p>
                                <div class="hph-no-results-actions">
                                    <a href="<?php echo get_post_type_archive_link('listing'); ?>" class="hph-btn hph-btn--primary">
                                        <?php esc_html_e('View All Properties', 'happy-place'); ?>
                                    </a>
                                    <button type="button" class="hph-btn hph-btn--secondary" onclick="document.getElementById('listingFilters').reset();">
                                        <?php esc_html_e('Clear Filters', 'happy-place'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Save Search Modal -->
<div id="saveSearchModal" class="hph-modal" style="display: none;">
    <div class="hph-modal-content">
        <div class="hph-modal-header">
            <h3><?php esc_html_e('Save This Search', 'happy-place'); ?></h3>
            <button type="button" class="hph-modal-close">&times;</button>
        </div>
        <div class="hph-modal-body">
            <form id="saveSearchForm">
                <div class="hph-form-group">
                    <label for="searchName"><?php esc_html_e('Search Name', 'happy-place'); ?></label>
                    <input type="text" id="searchName" name="search_name" class="hph-form-input" required>
                </div>
                <div class="hph-form-group">
                    <label class="hph-checkbox">
                        <input type="checkbox" name="email_alerts" checked>
                        <span class="hph-checkmark"></span>
                        <?php esc_html_e('Email me when new properties match this search', 'happy-place'); ?>
                    </label>
                </div>
                <div class="hph-modal-actions">
                    <button type="button" class="hph-btn hph-btn--secondary" data-dismiss="modal">
                        <?php esc_html_e('Cancel', 'happy-place'); ?>
                    </button>
                    <button type="submit" class="hph-btn hph-btn--primary">
                        <?php esc_html_e('Save Search', 'happy-place'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
?>