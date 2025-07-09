<div id="flyer-generator-container" class="flyer-generator">
    <div class="flyer-controls">
        <div class="control-group">
            <label for="listing-select">Select Listing:</label>
            <select id="listing-select" name="listing_id">
                <option value="">Choose a listing...</option>
                <?php
                $listings = get_posts([
                    'post_type' => 'listing',
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ]);
                
                foreach ($listings as $listing) {
                    $address = get_field('street_address', $listing->ID);
                    $city = get_field('city', $listing->ID);
                    $price = get_field('price', $listing->ID);
                    $display = $address . ', ' . $city . ' - $' . number_format($price);
                    
                    echo '<option value="' . $listing->ID . '">' . esc_html($display) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="control-group">
            <label for="template-select">Template:</label>
            <select id="template-select" name="template">
                <option value="parker_group">Parker Group Style</option>
                <option value="luxury">Luxury Template</option>
                <option value="modern">Modern Template</option>
            </select>
        </div>
        
        <div class="control-group">
            <button id="generate-flyer" class="btn btn-primary">Generate Flyer</button>
            <button id="download-flyer" class="btn btn-secondary" style="display:none;">Download PNG</button>
            <button id="download-pdf" class="btn btn-secondary" style="display:none;">Download PDF</button>
        </div>
    </div>

    <div class="flyer-preview">
        <canvas id="flyer-canvas" width="850" height="1100"></canvas>
    </div>
    
    <div class="flyer-loading" style="display:none;">
        <p>Generating your flyer...</p>
    </div>
</div>