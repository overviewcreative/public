<?php
/**
 * Template part for displaying filter chips
 */

$current_filters = array(
    'price_range' => get_query_var('price_range', 'any'),
    'bedrooms' => get_query_var('bedrooms', 'any'),
    'property_type' => get_query_var('property_type', 'all'),
    'features' => (array) get_query_var('features', array())
);
?>

<div class="hph-filter-chips">
    <div class="hph-filter-group">
        <h4>Price Range</h4>
        <div class="hph-chip-container">
            <?php
            $price_ranges = array(
                'any' => 'Any',
                'under-500k' => 'Under $500K',
                '500k-800k' => '$500K - $800K',
                '800k-1m' => '$800K - $1M',
                'over-1m' => 'Over $1M'
            );
            foreach ($price_ranges as $value => $label) :
            ?>
                <button class="hph-filter-chip <?php echo $current_filters['price_range'] === $value ? 'active' : ''; ?>" 
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
            $bedroom_options = array(
                'any' => 'Any',
                '1' => '1+',
                '2' => '2+',
                '3' => '3+',
                '4' => '4+'
            );
            foreach ($bedroom_options as $value => $label) :
            ?>
                <button class="hph-filter-chip <?php echo $current_filters['bedrooms'] === $value ? 'active' : ''; ?>" 
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
            $property_types = array(
                'all' => 'All Types',
                'single-family' => 'Single Family',
                'townhouse' => 'Townhouse',
                'condo' => 'Condo'
            );
            foreach ($property_types as $value => $label) :
            ?>
                <button class="hph-filter-chip <?php echo $current_filters['property_type'] === $value ? 'active' : ''; ?>" 
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
            $features = array(
                'pool' => 'Pool',
                'garage' => 'Garage',
                'fireplace' => 'Fireplace',
                'waterfront' => 'Waterfront'
            );
            foreach ($features as $value => $label) :
            ?>
                <button class="hph-filter-chip <?php echo in_array($value, $current_filters['features']) ? 'active' : ''; ?>" 
                        data-filter="features" 
                        data-value="<?php echo esc_attr($value); ?>"
                        data-multi="true">
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="hph-active-filters">
    <?php
    $active_count = 0;
    foreach ($current_filters as $type => $value) {
        if ($type === 'features') {
            $active_count += count($value);
        } elseif ($value !== 'any' && $value !== 'all') {
            $active_count++;
        }
    }
    
    if ($active_count > 0) :
    ?>
        <div class="hph-active-filters-header">
            <span><?php echo sprintf(__('%d Active Filters', 'happy-place'), $active_count); ?></span>
            <button type="reset" class="hph-btn hph-btn-text" form="listings-search-form">
                <?php esc_html_e('Clear All', 'happy-place'); ?>
            </button>
        </div>
        
        <div class="hph-active-filters-list">
            <?php
            // Display price range filter
            if ($current_filters['price_range'] !== 'any') {
                echo sprintf(
                    '<span class="hph-active-filter">%s <button type="button" data-clear="price">×</button></span>',
                    esc_html($price_ranges[$current_filters['price_range']])
                );
            }
            
            // Display bedrooms filter
            if ($current_filters['bedrooms'] !== 'any') {
                echo sprintf(
                    '<span class="hph-active-filter">%s Bedrooms <button type="button" data-clear="bedrooms">×</button></span>',
                    esc_html($bedroom_options[$current_filters['bedrooms']])
                );
            }
            
            // Display property type filter
            if ($current_filters['property_type'] !== 'all') {
                echo sprintf(
                    '<span class="hph-active-filter">%s <button type="button" data-clear="property-type">×</button></span>',
                    esc_html($property_types[$current_filters['property_type']])
                );
            }
            
            // Display feature filters
            foreach ($current_filters['features'] as $feature) {
                if (isset($features[$feature])) {
                    echo sprintf(
                        '<span class="hph-active-filter">%s <button type="button" data-clear="features" data-value="%s">×</button></span>',
                        esc_html($features[$feature]),
                        esc_attr($feature)
                    );
                }
            }
            ?>
        </div>
    <?php endif; ?>
</div>
