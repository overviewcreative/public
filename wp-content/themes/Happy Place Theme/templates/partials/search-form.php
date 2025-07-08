<?php
/**
 * Template Part: Search Form
 * 
 * This template part displays the global property search form.
 * 
 * @package HappyPlace
 */

$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : '';
$beds = isset($_GET['beds']) ? intval($_GET['beds']) : '';
$baths = isset($_GET['baths']) ? intval($_GET['baths']) : '';
$property_type = isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '';
$location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
?>

<form role="search" method="get" class="property-search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="hidden" name="post_type" value="listing">
    
    <div class="search-fields">
        <div class="search-field">
            <label for="location" class="screen-reader-text">Location</label>
            <input type="text" id="location" name="location" value="<?php echo esc_attr($location); ?>" placeholder="City, State, or ZIP" autocomplete="off">
        </div>

        <div class="search-field search-field--price">
            <label for="min_price" class="screen-reader-text">Minimum Price</label>
            <input type="number" id="min_price" name="min_price" value="<?php echo esc_attr($min_price); ?>" placeholder="Min Price">
            
            <label for="max_price" class="screen-reader-text">Maximum Price</label>
            <input type="number" id="max_price" name="max_price" value="<?php echo esc_attr($max_price); ?>" placeholder="Max Price">
        </div>

        <div class="search-field">
            <label for="beds" class="screen-reader-text">Bedrooms</label>
            <select id="beds" name="beds">
                <option value="">Any Beds</option>
                <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php selected($beds, $i); ?>>
                        <?php echo $i . '+' . _n(' Bed', ' Beds', $i, 'happy-place'); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="search-field">
            <label for="baths" class="screen-reader-text">Bathrooms</label>
            <select id="baths" name="baths">
                <option value="">Any Baths</option>
                <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php selected($baths, $i); ?>>
                        <?php echo $i . '+' . _n(' Bath', ' Baths', $i, 'happy-place'); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="search-field">
            <label for="property_type" class="screen-reader-text">Property Type</label>
            <select id="property_type" name="property_type">
                <option value="">Any Type</option>
                <?php
                $property_types = get_terms(array(
                    'taxonomy' => 'property_type',
                    'hide_empty' => true
                ));

                if (!is_wp_error($property_types)) :
                    foreach ($property_types as $type) :
                        ?>
                        <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($property_type, $type->slug); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                        <?php
                    endforeach;
                endif;
                ?>
            </select>
        </div>

        <div class="search-field search-field--submit">
            <button type="submit" class="search-submit">
                <span class="screen-reader-text">Search</span>
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    <div class="search-advanced">
        <button type="button" class="search-advanced__toggle">
            Advanced Search
            <i class="fas fa-chevron-down"></i>
        </button>

        <div class="search-advanced__content" style="display: none;">
            <?php
            // Add additional search fields like:
            // - Square footage range
            // - Year built range
            // - Lot size range
            // - Features/amenities checkboxes
            // - Community/subdivision search
            ?>
        </div>
    </div>
</form>

<script>
jQuery(document).ready(function($) {
    // Toggle advanced search
    $('.search-advanced__toggle').on('click', function() {
        $('.search-advanced__content').slideToggle();
        $(this).toggleClass('active');
    });

    // Location autocomplete
    $('#location').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: happy_place.ajax_url,
                dataType: "json",
                data: {
                    action: 'happy_place_location_search',
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2
    });
});
</script>
