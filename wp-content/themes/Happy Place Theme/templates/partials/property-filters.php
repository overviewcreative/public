<?php
/**
 * Property Filters Template Part
 */
?>

<form class="property-filters-form" action="<?php echo esc_url(get_post_type_archive_link('property')); ?>" method="get">
    <div class="hph-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--hph-spacing-md);">
        <div class="hph-form-group">
            <label for="type" class="hph-form-label">Property Type</label>
            <select name="type" id="type" class="hph-form-select">
                <option value="">All Types</option>
                <?php
                $types = get_terms(array(
                    'taxonomy' => 'property_type',
                    'hide_empty' => true,
                ));
                foreach ($types as $type) {
                    echo '<option value="' . esc_attr($type->slug) . '"' . selected($_GET['type'] ?? '', $type->slug, false) . '>' . 
                         esc_html($type->name) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="hph-form-group">
            <label for="location" class="hph-form-label">Location</label>
            <select name="location" id="location" class="hph-form-select">
                <option value="">All Locations</option>
                <?php
                $locations = get_terms(array(
                    'taxonomy' => 'property_location',
                    'hide_empty' => true,
                ));
                foreach ($locations as $location) {
                    echo '<option value="' . esc_attr($location->slug) . '"' . selected($_GET['location'] ?? '', $location->slug, false) . '>' . 
                         esc_html($location->name) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="hph-form-group">
            <label for="price_min" class="hph-form-label">Min Price</label>
            <input type="number" name="price_min" id="price_min" class="hph-form-input" 
                   value="<?php echo esc_attr($_GET['price_min'] ?? ''); ?>" min="0" step="1000">
        </div>

        <div class="hph-form-group">
            <label for="price_max" class="hph-form-label">Max Price</label>
            <input type="number" name="price_max" id="price_max" class="hph-form-input" 
                   value="<?php echo esc_attr($_GET['price_max'] ?? ''); ?>" min="0" step="1000">
        </div>

        <div class="hph-form-group">
            <label for="beds" class="hph-form-label">Bedrooms</label>
            <select name="beds" id="beds" class="hph-form-select">
                <option value="">Any</option>
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    echo '<option value="' . $i . '"' . selected($_GET['beds'] ?? '', $i, false) . '>' . 
                         $i . ($i === 5 ? '+' : '') . ' Beds</option>';
                }
                ?>
            </select>
        </div>

        <div class="hph-form-group">
            <label for="baths" class="hph-form-label">Bathrooms</label>
            <select name="baths" id="baths" class="hph-form-select">
                <option value="">Any</option>
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    echo '<option value="' . $i . '"' . selected($_GET['baths'] ?? '', $i, false) . '>' . 
                         $i . ($i === 5 ? '+' : '') . ' Baths</option>';
                }
                ?>
            </select>
        </div>

        <div class="hph-form-group" style="align-self: end;">
            <button type="submit" class="hph-btn hph-btn-primary" style="width: 100%;">
                Search Properties
            </button>
        </div>
    </div>
</form>
