(function($) {
    'use strict';

    let map;
    let infoWindow;
    let markers = [];

    function initPropertyMap() {
        const mapElement = document.getElementById('property-map');
        if (!mapElement) return;

        const lat = parseFloat(mapElement.dataset.lat);
        const lng = parseFloat(mapElement.dataset.lng);
        const title = mapElement.dataset.title;
        const address = mapElement.dataset.address;

        if (!lat || !lng) return;

        // Initialize map
        map = new google.maps.Map(mapElement, {
            center: { lat, lng },
            zoom: 15,
            styles: [], // Add custom styles here
            mapTypeControl: false
        });

        // Add property marker
        addPropertyMarker(lat, lng, title, address);

        // Setup nearby place buttons
        setupNearbyButtons();
    }

    function addPropertyMarker(lat, lng, title, address) {
        const marker = new google.maps.Marker({
            position: { lat, lng },
            map: map,
            title: title,
            icon: happyplace.markerIcon || null
        });

        infoWindow = new google.maps.InfoWindow({
            content: `<div class="hph-map-info">
                <h4>${title}</h4>
                <p>${address}</p>
            </div>`
        });

        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });
    }

    function setupNearbyButtons() {
        $('.hph-nearby-places-btn').on('click', function() {
            const type = $(this).data('type');
            searchNearbyPlaces(type);
        });
    }

    function searchNearbyPlaces(type) {
        // Clear existing markers
        markers.forEach(marker => marker.setMap(null));
        markers = [];

        const service = new google.maps.places.PlacesService(map);
        const center = map.getCenter();

        service.nearbySearch({
            location: center,
            radius: 1500, // 1.5km
            type: type
        }, (results, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                results.forEach(place => addPlaceMarker(place));
                map.setZoom(14); // Zoom out slightly to show nearby places
            }
        });
    }

    function addPlaceMarker(place) {
        const marker = new google.maps.Marker({
            position: place.geometry.location,
            map: map,
            title: place.name,
            animation: google.maps.Animation.DROP
        });

        markers.push(marker);

        const content = `<div class="hph-map-info">
            <h4>${place.name}</h4>
            <p>${place.vicinity}</p>
            ${place.rating ? `<p>Rating: ${place.rating} ⭐</p>` : ''}
        </div>`;

        marker.addListener('click', () => {
            if (infoWindow) infoWindow.close();
            infoWindow = new google.maps.InfoWindow({ content });
            infoWindow.open(map, marker);
        });
    }

    // Initialize on document ready
    $(document).ready(initPropertyMap);

})(jQuery);
