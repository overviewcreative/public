/**
 * Listings Archive Interactions
 */
(function($) {
    const ListingsArchive = {
        // Configuration
        config: {
            ajaxUrl: hphTheme.ajaxurl,
            nonce: hphTheme.nonce,
            googleMapsApiKey: hphTheme.googleMapsApiKey
        },

        // DOM Elements
        elements: {
            searchForm: '#listings-search-form',
            locationSearch: '#location-search',
            locationSuggestions: '#location-suggestions',
            listingsContainer: '#listings-container',
            mapContainer: '#listings-map',
            mapListingsPreview: '#map-listings-preview',
            filterChips: '.hph-filter-chip',
            sortSelect: '#sort-listings'
        },

        // State
        state: {
            currentView: 'grid',
            currentFilters: {},
            map: null,
            markers: [],
            clusters: null
        },

        // Initialize
        init: function() {
            this.bindEvents();
            this.setupAutocomplete();
            this.loadInitialListings();
        },

        // Bind Events
        bindEvents: function() {
            $(this.elements.searchForm).on('submit', this.handleSearch.bind(this));
            $(this.elements.filterChips).on('click', this.handleFilterToggle.bind(this));
            $(this.elements.sortSelect).on('change', this.handleSorting.bind(this));
            
            // Favorite toggle
            $(document).on('click', '.hph-btn-favorite', this.toggleFavorite.bind(this));
        },

        // Setup Autocomplete
        setupAutocomplete: function() {
            const $input = $(this.elements.locationSearch);
            const $suggestions = $(this.elements.locationSuggestions);

            $input.on('input', this.debounce(function() {
                const query = $(this).val();
                if (query.length < 2) {
                    $suggestions.empty().removeClass('is-visible');
                    return;
                }

                ListingsArchive.fetchLocationSuggestions(query);
            }, 300));

            // Handle suggestion selection
            $suggestions.on('click', '.hph-autocomplete-item', function() {
                const selectedLocation = $(this).data('location');
                $input.val(selectedLocation);
                $suggestions.empty().removeClass('is-visible');
                ListingsArchive.searchListings();
            });

            // Close suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest(ListingsArchive.elements.locationSearch + ', ' + ListingsArchive.elements.locationSuggestions).length) {
                    $suggestions.empty().removeClass('is-visible');
                }
            });
        },

        // Fetch Location Suggestions
        fetchLocationSuggestions: function(query) {
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'hph_location_suggestions',
                    security: this.config.nonce,
                    query: query
                },
                success: function(response) {
                    if (response.success) {
                        const $suggestions = $(ListingsArchive.elements.locationSuggestions);
                        $suggestions.empty();

                        response.data.forEach(location => {
                            $('<div>', {
                                class: 'hph-autocomplete-item',
                                'data-location': location.name,
                                text: location.name
                            }).appendTo($suggestions);
                        });

                        $suggestions.addClass('is-visible');
                    }
                }
            });
        },

        // Load Initial Listings
        loadInitialListings: function() {
            this.searchListings();
        },

        // Handle Search Submission
        handleSearch: function(e) {
            e.preventDefault();
            this.searchListings();
        },

        // Handle Filter Toggling
        handleFilterToggle: function(e) {
            const $chip = $(e.currentTarget);
            const filterGroup = $chip.closest('.hph-filter-group');
            
            // Remove active from all chips in this group
            filterGroup.find('.hph-filter-chip').removeClass('active');
            $chip.addClass('active');

            // Update filters
            const filterType = $chip.data('filter');
            const filterValue = $chip.data('value');

            this.state.currentFilters[filterType] = filterValue;
            this.searchListings();
        },

        // Handle Sorting
        handleSorting: function() {
            const sortValue = $(this.elements.sortSelect).val();
            this.state.currentSort = sortValue;
            this.searchListings();
        },

        // Search Listings
        searchListings: function() {
            const $container = $(this.elements.listingsContainer);
            const $mapContainer = $(this.elements.mapContainer);

            // Show loading state
            $container.html(`
                <div class="hph-listings-loading">
                    <div class="hph-loading-spinner"></div>
                </div>
            `);

            // Prepare search data
            const searchData = {
                action: 'hph_search_listings',
                security: this.config.nonce,
                location: $(this.elements.locationSearch).val(),
                filters: this.state.currentFilters,
                sort: this.state.currentSort,
                view: this.state.currentView
            };

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: searchData,
                success: this.handleSearchResults.bind(this),
                error: this.handleSearchError.bind(this)
            });
        },

        // Handle Search Results
        handleSearchResults: function(response) {
            if (!response.success) {
                this.handleSearchError(response);
                return;
            }

            const $container = $(this.elements.listingsContainer);
            const $mapContainer = $(this.elements.mapContainer);

            // Update results count
            $('.hph-results-count span').text(response.data.total);

            // Clear previous results
            $container.empty();

            // No results handling
            if (response.data.listings.length === 0) {
                $container.html(`
                    <div class="hph-no-results">
                        <div class="hph-no-results-icon">🏠</div>
                        <h3 class="hph-no-results-title">No Properties Found</h3>
                        <p class="hph-no-results-description">Try adjusting your search or filters.</p>
                    </div>
                `);
                return;
            }

            // Render listings
            response.data.listings.forEach(listing => {
                const $listingCard = this.createListingCard(listing);
                $container.append($listingCard);
            });

            // Initialize map if in map or split view
            if (this.state.currentView === 'map' || this.state.currentView === 'split') {
                this.initializeMap(response.data.listings);
            }
        },

        // Create Listing Card
        createListingCard: function(listing) {
            return $('<div>', {
                class: 'hph-listing-card',
                'data-id': listing.id
            }).html(`
                <div class="hph-listing-image">
                    <img src="${listing.main_photo}" alt="${listing.title}">
                    <div class="hph-listing-price">$${this.formatPrice(listing.price)}</div>
                </div>
                <div class="hph-listing-details">
                    <h3>${listing.title}</h3>
                    <div class="hph-listing-meta">
                        <span>${listing.bedrooms} BD</span>
                        <span>${listing.bathrooms} BA</span>
                        <span>${listing.square_footage} Ft²</span>
                    </div>
                    <div class="hph-listing-actions">
                        <a href="${listing.permalink}" class="hph-btn hph-btn-secondary">View Details</a>
                        <button class="hph-btn-favorite" data-id="${listing.id}">
                            <i class="icon-heart"></i>
                        </button>
                    </div>
                </div>
            `);
        },

        // Initialize Google Maps
        initializeMap: function(listings) {
            if (!window.google || !window.google.maps) {
                this.loadGoogleMapsScript(listings);
                return;
            }

            const mapOptions = {
                center: { 
                    lat: listings[0].latitude || 38.9072, 
                    lng: listings[0].longitude || -77.0369 
                },
                zoom: 10,
                styles: this.getMapStyles()
            };

            this.state.map = new google.maps.Map(
                document.getElementById('listings-map'), 
                mapOptions
            );

            this.addMapMarkers(listings);
        },

        // Load Google Maps Script
        loadGoogleMapsScript: function(listings) {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${this.config.googleMapsApiKey}&callback=ListingsArchive.initializeMap`;
            script.async = true;
            script.defer = true;
            window.ListingsArchive = this;
            document.head.appendChild(script);
        },

        // Add Map Markers
        addMapMarkers: function(listings) {
            // Clear existing markers
            this.state.markers.forEach(marker => marker.setMap(null));
            this.state.markers = [];

            // Create marker cluster
            const markerClusterer = new MarkerClusterer(
                this.state.map, 
                this.createMarkers(listings),
                {
                    imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m',
                    gridSize: 50,
                    maxZoom: 15,
                    styles: [{
                        url: 'path/to/cluster-icon.png',
                        width: 50,
                        height: 50,
                        textColor: '#ffffff',
                        textSize: 12
                    }]
                }
            );

            // Populate map preview
            this.populateMapListingsPreview(listings);
        },

        // Create Map Markers
        createMarkers: function(listings) {
            return listings.map(listing => {
                const marker = new google.maps.Marker({
                    position: { 
                        lat: listing.latitude, 
                        lng: listing.longitude 
                    },
                    map: this.state.map,
                    title: listing.title,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: '#0ea5e9',
                        fillOpacity: 1,
                        strokeWeight: 2,
                        strokeColor: 'white'
                    }
                });

                // Add info window
                const infoWindow = new google.maps.InfoWindow({
                    content: this.createMapInfoWindowContent(listing)
                });

                marker.addListener('click', () => {
                    // Close any open info windows
                    this.state.markers.forEach(m => m.infoWindow?.close());
                    
                    // Open this info window
                    infoWindow.open(this.state.map, marker);
                    
                    // Attach info window to marker for tracking
                    marker.infoWindow = infoWindow;
                });

                this.state.markers.push(marker);
                return marker;
            });
        },

        // Create Map Info Window Content
        createMapInfoWindowContent: function(listing) {
            return `
                <div class="map-info-window">
                    <img src="${listing.main_photo}" alt="${listing.title}" style="max-width:200px;height:auto;">
                    <h3>${listing.title}</h3>
                    <p>$${this.formatPrice(listing.price)}</p>
                    <div class="map-listing-meta">
                        <span>${listing.bedrooms} BD</span>
                        <span>${listing.bathrooms} BA</span>
                    </div>
                    <a href="${listing.permalink}" class="hph-btn hph-btn-primary">View Details</a>
                </div>
            `;
        },

        // Populate Map Listings Preview
        populateMapListingsPreview: function(listings) {
            const $preview = $(this.elements.mapListingsPreview);
            $preview.empty();

            listings.slice(0, 5).forEach(listing => {
                const $previewCard = $('<div>', {
                    class: 'map-preview-card',
                    'data-id': listing.id
                }).html(`
                    <img src="${listing.main_photo}" alt="${listing.title}">
                    <div class="map-preview-details">
                        <h4>${listing.title}</h4>
                        <p>$${this.formatPrice(listing.price)}</p>
                        <div class="map-preview-meta">
                            <span>${listing.bedrooms} BD</span>
                            <span>${listing.bathrooms} BA</span>
                        </div>
                        <a href="${listing.permalink}" class="hph-btn hph-btn-secondary">View</a>
                    </div>
                `);

                $preview.append($previewCard);
            });
        },

        // Toggle Favorite Listing
        toggleFavorite: function(e) {
            const $button = $(e.currentTarget);
            const listingId = $button.data('id');

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'hph_toggle_favorite',
                    security: this.config.nonce,
                    listing_id: listingId
                },
                success: function(response) {
                    if (response.success) {
                        $button.toggleClass('is-favorited');
                        
                        // Show notification
                        ListingsArchive.showNotification(
                            response.data.message, 
                            response.data.type
                        );
                    }
                },
                error: function() {
                    ListingsArchive.showNotification(
                        'Unable to update favorites. Please try again.', 
                        'error'
                    );
                }
            });
        },

        // Handle Search Error
        handleSearchError: function(response) {
            const $container = $(this.elements.listingsContainer);
            $container.html(`
                <div class="hph-no-results">
                    <div class="hph-no-results-icon">⚠️</div>
                    <h3 class="hph-no-results-title">Search Error</h3>
                    <p class="hph-no-results-description">
                        ${response.data?.message || 'An unexpected error occurred. Please try again.'}
                    </p>
                </div>
            `);
        },

        // Show Notification
        showNotification: function(message, type = 'success') {
            const $notification = $('<div>', {
                class: `hph-notification hph-notification-${type}`
            }).text(message);

            $('body').append($notification);
            
            $notification.addClass('is-visible');

            setTimeout(() => {
                $notification.removeClass('is-visible');
                setTimeout(() => $notification.remove(), 300);
            }, 3000);
        },

        // Format Price
        formatPrice: function(price) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        },

        // Debounce Utility
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        },

        // Get Custom Map Styles
        getMapStyles: function() {
            return [
                {
                    featureType: 'administrative',
                    elementType: 'labels.text.fill',
                    stylers: [{ color: '#444444' }]
                },
                {
                    featureType: 'landscape',
                    elementType: 'all',
                    stylers: [{ color: '#f2f2f2' }]
                },
                {
                    featureType: 'poi',
                    elementType: 'all',
                    stylers: [{ visibility: 'off' }]
                },
                {
                    featureType: 'road',
                    elementType: 'all',
                    stylers: [
                        { saturation: -100 },
                        { lightness: 45 }
                    ]
                },
                {
                    featureType: 'road.highway',
                    elementType: 'all',
                    stylers: [{ visibility: 'simplified' }]
                },
                {
                    featureType: 'water',
                    elementType: 'all',
                    stylers: [
                        { color: '#46bcec' },
                        { visibility: 'on' }
                    ]
                }
            ];
        }
    };

    // Document Ready
    $(document).ready(function() {
        ListingsArchive.init();
    });

})(jQuery);