/**
 * Dashboard Listings JavaScript
 */
(function($) {
    'use strict';

    // Elements
    const $listingsGrid = $('.hph-listings-grid');
    const $listingModal = $('#listing-modal');
    const $statusModal = $('#status-modal');
    const $listingForm = $('#listing-form');
    const $statusForm = $('#status-form');
    const $addListingButton = $('#add-listing-button, #add-first-listing');
    
    // Initialize
    function init() {
        bindEvents();
        initializeDropdowns();
        initializePhotoUpload();
    }

    // Bind Events
    function bindEvents() {
        // Add Listing
        $addListingButton.on('click', showAddListingModal);
        
        // Edit Listing
        $listingsGrid.on('click', '.edit-listing', function(e) {
            const listingId = $(this).data('id');
            showEditListingModal(listingId);
        });

        // View Listing
        $listingsGrid.on('click', '.view-listing', function(e) {
            const listingId = $(this).data('id');
            window.open($(this).data('url'), '_blank');
        });

        // Status Change
        $listingsGrid.on('click', '.change-status', function(e) {
            const listingId = $(this).data('id');
            showStatusModal(listingId);
        });

        // Listing Menu
        $listingsGrid.on('click', '.hph-listing-menu-trigger', function(e) {
            e.stopPropagation();
            const $menu = $(this).closest('.hph-listing-menu');
            $('.hph-listing-menu').not($menu).removeClass('active');
            $menu.toggleClass('active');
        });

        // Close menus on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.hph-listing-menu').length) {
                $('.hph-listing-menu').removeClass('active');
            }
        });

        // Form Submission
        $listingForm.on('submit', handleListingSubmit);
        $statusForm.on('submit', handleStatusSubmit);

        // Modal Close
        $('.hph-modal-close').on('click', function() {
            $(this).closest('.hph-modal').removeClass('active');
        });

        // Filters
        $('#listing-status-filter, #listing-sort').on('change', filterListings);
    }

    // Initialize Dropdowns
    function initializeDropdowns() {
        // Add any custom dropdown initialization here
    }

    // Initialize Photo Upload
    function initializePhotoUpload() {
        let mediaUploader;

        $('#add-photos').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: hphDashboard.translations.uploadMedia,
                button: {
                    text: hphDashboard.translations.useMedia
                },
                multiple: true
            });

            mediaUploader.on('select', function() {
                const attachments = mediaUploader.state().get('selection').toJSON();
                handlePhotoSelection(attachments);
            });

            mediaUploader.open();
        });

        // Remove photo
        $('#photo-preview').on('click', '.remove-photo', function() {
            $(this).closest('.hph-photo-preview-item').remove();
        });
    }

    // Show Add Listing Modal
    function showAddListingModal() {
        $listingForm[0].reset();
        $('#listing-modal-title').text(hphDashboard.translations.addNewListing);
        $listingModal.addClass('active');
    }

    // Show Edit Listing Modal
    function showEditListingModal(listingId) {
        // Fetch listing data
        wp.apiRequest({
            path: `/happyplace/v1/listings/${listingId}`,
            method: 'GET'
        }).then(function(response) {
            populateListingForm(response);
            $('#listing-modal-title').text(hphDashboard.translations.editListing);
            $listingModal.addClass('active');
        });
    }

    // Show Status Modal
    function showStatusModal(listingId) {
        $statusForm.data('listing-id', listingId);
        $statusModal.addClass('active');
        
        // Show/hide date fields based on selected status
        updateStatusDateFields($('#new-status').val());
    }

    // Handle Photo Selection
    function handlePhotoSelection(attachments) {
        const $preview = $('#photo-preview');
        
        attachments.forEach(function(attachment) {
            const $item = $(`
                <div class="hph-photo-preview-item">
                    <img src="${attachment.url}" alt="">
                    <input type="hidden" name="photos[]" value="${attachment.id}">
                    <button type="button" class="remove-photo">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            $preview.append($item);
        });
    }

    // Handle Listing Form Submit
    function handleListingSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData($listingForm[0]);
        const listingId = $listingForm.data('listing-id');
        const method = listingId ? 'POST' : 'PUT';
        const path = listingId 
            ? `/happyplace/v1/listings/${listingId}` 
            : '/happyplace/v1/listings';

        wp.apiRequest({
            path: path,
            method: method,
            data: formData,
            processData: false,
            contentType: false
        }).then(function(response) {
            $listingModal.removeClass('active');
            refreshListings();
            showNotification('success', hphDashboard.translations.saveSuccess);
        }).catch(function(error) {
            showNotification('error', error.message || hphDashboard.translations.saveError);
        });
    }

    // Handle Status Form Submit
    function handleStatusSubmit(e) {
        e.preventDefault();
        
        const listingId = $statusForm.data('listing-id');
        const formData = new FormData($statusForm[0]);

        wp.apiRequest({
            path: `/happyplace/v1/listings/${listingId}/status`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).then(function(response) {
            $statusModal.removeClass('active');
            refreshListings();
            showNotification('success', hphDashboard.translations.statusUpdated);
        }).catch(function(error) {
            showNotification('error', error.message || hphDashboard.translations.saveError);
        });
    }

    // Filter Listings
    function filterListings() {
        const status = $('#listing-status-filter').val();
        const sort = $('#listing-sort').val();
        
        refreshListings({ status, sort });
    }

    // Refresh Listings
    function refreshListings(filters = {}) {
        wp.apiRequest({
            path: '/happyplace/v1/listings',
            data: filters
        }).then(function(response) {
            updateListingsGrid(response);
        });
    }

    // Update Listings Grid
    function updateListingsGrid(listings) {
        $listingsGrid.empty();
        
        if (listings.length === 0) {
            $listingsGrid.append(`
                <div class="hph-no-listings">
                    <i class="fas fa-home"></i>
                    <h3>${hphDashboard.translations.noListings}</h3>
                    <p>${hphDashboard.translations.addFirstListing}</p>
                    <button type="button" class="hph-button hph-button-primary" id="add-first-listing">
                        ${hphDashboard.translations.addListing}
                    </button>
                </div>
            `);
            return;
        }

        listings.forEach(function(listing) {
            const $card = createListingCard(listing);
            $listingsGrid.append($card);
        });
    }

    // Create Listing Card
    function createListingCard(listing) {
        return $(`
            <div class="hph-listing-card" data-id="${listing.id}">
                <div class="hph-listing-card-image">
                    <img src="${listing.thumbnail}" alt="${listing.address}">
                    <div class="hph-listing-card-status ${listing.status}">
                        ${listing.status}
                    </div>
                </div>
                <div class="hph-listing-card-content">
                    <h3 class="hph-listing-card-address">${listing.address}</h3>
                    <div class="hph-listing-card-price">${listing.price}</div>
                    <div class="hph-listing-card-stats">
                        <span><i class="fas fa-eye"></i> ${listing.views}</span>
                        <span><i class="fas fa-envelope"></i> ${listing.inquiries}</span>
                    </div>
                    <div class="hph-listing-card-actions">
                        <button type="button" class="hph-button edit-listing" data-id="${listing.id}">
                            <i class="fas fa-edit"></i> ${hphDashboard.translations.edit}
                        </button>
                        <button type="button" class="hph-button view-listing" data-id="${listing.id}" 
                                data-url="${listing.url}">
                            <i class="fas fa-external-link-alt"></i> ${hphDashboard.translations.view}
                        </button>
                        <div class="hph-listing-menu">
                            <button type="button" class="hph-button hph-listing-menu-trigger">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="hph-listing-menu-dropdown">
                                <li>
                                    <button type="button" class="change-status" data-id="${listing.id}">
                                        <i class="fas fa-exchange-alt"></i> ${hphDashboard.translations.changeStatus}
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="schedule-open-house" data-id="${listing.id}">
                                        <i class="fas fa-calendar-plus"></i> ${hphDashboard.translations.scheduleOpenHouse}
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="generate-report" data-id="${listing.id}">
                                        <i class="fas fa-chart-bar"></i> ${hphDashboard.translations.generateReport}
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="duplicate-listing" data-id="${listing.id}">
                                        <i class="fas fa-copy"></i> ${hphDashboard.translations.duplicate}
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="delete-listing" data-id="${listing.id}">
                                        <i class="fas fa-trash-alt"></i> ${hphDashboard.translations.delete}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    // Show Notification
    function showNotification(type, message) {
        // Use your notification system here
        console.log(type, message);
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
