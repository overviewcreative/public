/**
 * Dashboard Open Houses JavaScript
 */
(function($) {
    'use strict';

    // Elements
    const $openHousesGrid = $('.hph-open-houses-grid');
    const $openHouseModal = $('#open-house-modal');
    const $openHouseForm = $('#open-house-form');
    const $addOpenHouseButton = $('#add-open-house-button, #schedule-first-open-house');
    
    // Initialize
    function init() {
        bindEvents();
        initializeDatePickers();
        setupFilters();
    }

    // Bind Events
    function bindEvents() {
        // Add Open House
        $addOpenHouseButton.on('click', showAddOpenHouseModal);
        
        // Edit Open House
        $openHousesGrid.on('click', '.edit-open-house', function(e) {
            const openHouseId = $(this).data('id');
            showEditOpenHouseModal(openHouseId);
        });

        // View Listing
        $openHousesGrid.on('click', '.view-listing', function(e) {
            const listingId = $(this).data('id');
            window.open($(this).data('url'), '_blank');
        });

        // View Report
        $openHousesGrid.on('click', '.view-report', function(e) {
            const openHouseId = $(this).data('id');
            showOpenHouseReport(openHouseId);
        });

        // Cancel Open House
        $openHousesGrid.on('click', '.cancel-open-house', function(e) {
            const openHouseId = $(this).data('id');
            confirmCancelOpenHouse(openHouseId);
        });

        // Delete Open House
        $openHousesGrid.on('click', '.delete-open-house', function(e) {
            const openHouseId = $(this).data('id');
            confirmDeleteOpenHouse(openHouseId);
        });

        // Duplicate Open House
        $openHousesGrid.on('click', '.duplicate-open-house', function(e) {
            const openHouseId = $(this).data('id');
            duplicateOpenHouse(openHouseId);
        });

        // Form Submission
        $openHouseForm.on('submit', handleOpenHouseSubmit);

        // Time Input Validation
        $('#open-house-start-time, #open-house-end-time').on('change', validateTimes);
    }

    // Initialize Date Pickers
    function initializeDatePickers() {
        const today = new Date().toISOString().split('T')[0];
        $('#open-house-date').attr('min', today);
    }

    // Setup Filters
    function setupFilters() {
        $('#open-house-filter').on('change', function() {
            const status = $(this).val();
            filterOpenHouses(status);
        });
    }

    // Show Add Open House Modal
    function showAddOpenHouseModal() {
        $openHouseForm[0].reset();
        $('#open-house-modal-title').text(hphDashboard.translations.scheduleOpenHouse);
        $openHouseModal.addClass('active');
        initializeDatePickers();
    }

    // Show Edit Open House Modal
    function showEditOpenHouseModal(openHouseId) {
        wp.apiRequest({
            path: `/happyplace/v1/open-houses/${openHouseId}`,
            method: 'GET'
        }).then(function(response) {
            populateOpenHouseForm(response);
            $('#open-house-modal-title').text(hphDashboard.translations.editOpenHouse);
            $openHouseModal.addClass('active');
        });
    }

    // Populate Open House Form
    function populateOpenHouseForm(data) {
        $('#open-house-listing').val(data.listing_id);
        $('#open-house-date').val(data.date);
        $('#open-house-start-time').val(data.start_time);
        $('#open-house-end-time').val(data.end_time);
        $('#open-house-notes').val(data.notes);
        $openHouseForm.data('open-house-id', data.id);
    }

    // Handle Open House Form Submit
    function handleOpenHouseSubmit(e) {
        e.preventDefault();
        
        if (!validateTimes()) {
            return;
        }

        const formData = new FormData($openHouseForm[0]);
        const openHouseId = $openHouseForm.data('open-house-id');
        const method = openHouseId ? 'POST' : 'PUT';
        const path = openHouseId 
            ? `/happyplace/v1/open-houses/${openHouseId}` 
            : '/happyplace/v1/open-houses';

        wp.apiRequest({
            path: path,
            method: method,
            data: formData,
            processData: false,
            contentType: false
        }).then(function(response) {
            $openHouseModal.removeClass('active');
            refreshOpenHouses();
            showNotification('success', hphDashboard.translations.saveSuccess);
        }).catch(function(error) {
            showNotification('error', error.message || hphDashboard.translations.saveError);
        });
    }

    // Validate Times
    function validateTimes() {
        const startTime = $('#open-house-start-time').val();
        const endTime = $('#open-house-end-time').val();
        
        if (startTime && endTime && startTime >= endTime) {
            showNotification('error', hphDashboard.translations.invalidTimes);
            return false;
        }
        
        return true;
    }

    // Filter Open Houses
    function filterOpenHouses(status) {
        if (status === 'all') {
            $('.hph-open-house-card').show();
            return;
        }
        
        $('.hph-open-house-card').each(function() {
            $(this).toggle($(this).data('status') === status);
        });
    }

    // Confirm Cancel Open House
    function confirmCancelOpenHouse(openHouseId) {
        if (confirm(hphDashboard.translations.confirmCancel)) {
            wp.apiRequest({
                path: `/happyplace/v1/open-houses/${openHouseId}/cancel`,
                method: 'POST'
            }).then(function(response) {
                refreshOpenHouses();
                showNotification('success', hphDashboard.translations.cancelSuccess);
            });
        }
    }

    // Confirm Delete Open House
    function confirmDeleteOpenHouse(openHouseId) {
        if (confirm(hphDashboard.translations.confirmDelete)) {
            wp.apiRequest({
                path: `/happyplace/v1/open-houses/${openHouseId}`,
                method: 'DELETE'
            }).then(function(response) {
                refreshOpenHouses();
                showNotification('success', hphDashboard.translations.deleteSuccess);
            });
        }
    }

    // Duplicate Open House
    function duplicateOpenHouse(openHouseId) {
        wp.apiRequest({
            path: `/happyplace/v1/open-houses/${openHouseId}/duplicate`,
            method: 'POST'
        }).then(function(response) {
            refreshOpenHouses();
            showNotification('success', hphDashboard.translations.duplicateSuccess);
        });
    }

    // Show Open House Report
    function showOpenHouseReport(openHouseId) {
        // Implementation depends on reporting system
        console.log('Show report for open house:', openHouseId);
    }

    // Refresh Open Houses
    function refreshOpenHouses() {
        wp.apiRequest({
            path: '/happyplace/v1/open-houses'
        }).then(function(response) {
            updateOpenHousesGrid(response);
        });
    }

    // Update Open Houses Grid
    function updateOpenHousesGrid(openHouses) {
        $openHousesGrid.empty();
        
        if (openHouses.length === 0) {
            $openHousesGrid.append(`
                <div class="hph-no-open-houses">
                    <i class="fas fa-home"></i>
                    <h3>${hphDashboard.translations.noOpenHouses}</h3>
                    <p>${hphDashboard.translations.scheduleFirstOpenHouse}</p>
                    <button type="button" class="hph-button hph-button-primary" id="schedule-first-open-house">
                        ${hphDashboard.translations.scheduleOpenHouse}
                    </button>
                </div>
            `);
            return;
        }

        openHouses.forEach(function(openHouse) {
            const $card = createOpenHouseCard(openHouse);
            $openHousesGrid.append($card);
        });

        // Re-apply current filter
        filterOpenHouses($('#open-house-filter').val());
    }

    // Create Open House Card
    function createOpenHouseCard(openHouse) {
        return $(`
            <div class="hph-open-house-card" data-id="${openHouse.id}" data-status="${openHouse.status}">
                <div class="hph-open-house-card-image">
                    <img src="${openHouse.listing.thumbnail}" alt="${openHouse.listing.address}">
                    <div class="hph-open-house-card-status ${openHouse.status}">
                        ${openHouse.status}
                    </div>
                </div>
                <div class="hph-open-house-card-content">
                    <h3>${openHouse.listing.address}</h3>
                    <div class="hph-open-house-card-details">
                        <div class="hph-open-house-card-date">
                            <i class="fas fa-calendar"></i>
                            ${openHouse.date_formatted}
                        </div>
                        <div class="hph-open-house-card-time">
                            <i class="fas fa-clock"></i>
                            ${openHouse.time_formatted}
                        </div>
                        ${openHouse.status === 'past' ? `
                            <div class="hph-open-house-card-visitors">
                                <i class="fas fa-users"></i>
                                ${openHouse.visitors} ${hphDashboard.translations.visitors}
                            </div>
                        ` : ''}
                    </div>
                    ${openHouse.notes ? `
                        <div class="hph-open-house-card-notes">
                            ${openHouse.notes}
                        </div>
                    ` : ''}
                    <div class="hph-open-house-card-actions">
                        <button type="button" class="hph-button edit-open-house" data-id="${openHouse.id}">
                            <i class="fas fa-edit"></i> ${hphDashboard.translations.edit}
                        </button>
                        ${openHouse.status === 'upcoming' ? `
                            <button type="button" class="hph-button view-listing" 
                                    data-id="${openHouse.listing.id}" 
                                    data-url="${openHouse.listing.url}">
                                <i class="fas fa-home"></i> ${hphDashboard.translations.viewListing}
                            </button>
                        ` : `
                            <button type="button" class="hph-button view-report" data-id="${openHouse.id}">
                                <i class="fas fa-chart-bar"></i> ${hphDashboard.translations.viewReport}
                            </button>
                        `}
                        <div class="hph-listing-menu">
                            <button type="button" class="hph-button hph-listing-menu-trigger">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="hph-listing-menu-dropdown">
                                ${openHouse.status === 'upcoming' ? `
                                    <li>
                                        <button type="button" class="cancel-open-house" data-id="${openHouse.id}">
                                            <i class="fas fa-ban"></i> ${hphDashboard.translations.cancel}
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="duplicate-open-house" data-id="${openHouse.id}">
                                            <i class="fas fa-copy"></i> ${hphDashboard.translations.duplicate}
                                        </button>
                                    </li>
                                ` : ''}
                                <li>
                                    <button type="button" class="delete-open-house" data-id="${openHouse.id}">
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

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
