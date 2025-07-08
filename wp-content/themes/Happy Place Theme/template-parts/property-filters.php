<?php
/**
 * Property Filters Template Part
 *
 * @package HappyPlace
 */

$property_types = get_terms(array(
    'taxonomy' => 'property_type',
    'hide_empty' => true
));

$property_statuses = get_terms(array(
    'taxonomy' => 'property_status',
    'hide_empty' => true
));

$current_type = get_query_var('property_type');
$current_status = get_query_var('property_status');
$current_min_price = get_query_var('min_price');
$current_max_price = get_query_var('max_price');
$current_beds = get_query_var('bedrooms');
$current_baths = get_query_var('bathrooms');
?>

<form class="hph-listing-filters" method="get">
    <!-- Property Type -->
    <div class="hph-form-group">
        <label class="hph-form-label" for="property_type"><?php _e('Property Type', 'happy-place'); ?></label>
        <select name="property_type" id="property_type" class="hph-form-select">
            <option value=""><?php _e('All Types', 'happy-place'); ?></option>
            <?php foreach ($property_types as $type) : ?>
                <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($current_type, $type->slug); ?>>
                    <?php echo esc_html($type->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Property Status -->
    <div class="hph-form-group">
        <label class="hph-form-label" for="property_status"><?php _e('Status', 'happy-place'); ?></label>
        <select name="property_status" id="property_status" class="hph-form-select">
            <option value=""><?php _e('All Statuses', 'happy-place'); ?></option>
            <?php foreach ($property_statuses as $status) : ?>
                <option value="<?php echo esc_attr($status->slug); ?>" <?php selected($current_status, $status->slug); ?>>
                    <?php echo esc_html($status->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Price Range -->
    <div class="hph-filter-group hph-filter-group--price">
        <label><?php _e('Price Range', 'happy-place'); ?></label>
        <div class="hph-price-inputs">
            <input type="number" name="min_price" placeholder="<?php esc_attr_e('Min Price', 'happy-place'); ?>"
                   value="<?php echo esc_attr($current_min_price); ?>" min="0">
            <span class="hph-price-separator">-</span>
            <input type="number" name="max_price" placeholder="<?php esc_attr_e('Max Price', 'happy-place'); ?>"
                   value="<?php echo esc_attr($current_max_price); ?>" min="0">
        </div>
    </div>

    <!-- Bedrooms -->
    <div class="hph-filter-group">
        <label for="bedrooms"><?php _e('Beds', 'happy-place'); ?></label>
        <select name="bedrooms" id="bedrooms">
            <option value=""><?php _e('Any', 'happy-place'); ?></option>
            <?php for ($i = 1; $i <= 5; $i++) : ?>
                <option value="<?php echo $i; ?>" <?php selected($current_beds, $i); ?>>
                    <?php echo $i . '+'; ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <!-- Bathrooms -->
    <div class="hph-filter-group">
        <label for="bathrooms"><?php _e('Baths', 'happy-place'); ?></label>
        <select name="bathrooms" id="bathrooms">
            <option value=""><?php _e('Any', 'happy-place'); ?></option>
            <?php for ($i = 1; $i <= 5; $i++) : ?>
                <option value="<?php echo $i; ?>" <?php selected($current_baths, $i); ?>>
                    <?php echo $i . '+'; ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <!-- Submit Button -->
    <div class="hph-filter-group hph-filter-group--submit">
        <button type="submit" class="hph-filter-submit">
            <?php _e('Search', 'happy-place'); ?>
        </button>
        <a href="<?php echo get_post_type_archive_link('property'); ?>" class="hph-filter-reset">
            <?php _e('Reset', 'happy-place'); ?>
        </a>
    </div>
</form>
