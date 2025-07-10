/**
 * Happy Place Archive Listing JavaScript
 * 
 * @package HappyPlace
 */

class HPHListingsArchive {
    constructor() {
        this.filtersForm = document.getElementById('listingFilters');
        this.searchForm = document.querySelector('.hph-search-form');
        this.resultsContainer = document.querySelector('.hph-results-content');
        this.sortSelect = document.getElementById('sortBy');
        this.viewButtons = document.querySelectorAll('.hph-view-btn');
        this.modal = document.getElementById('saveSearchModal');
        
        this.currentFilters = this.getUrlParams();
        this.isLoading = false;
        this.debounceTimer = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateFilterUI();
        this.initializeMap();
        this.setupAutoComplete();
    }
    
    bindEvents() {
        // Form submissions
        if (this.filtersForm) {
            this.filtersForm.addEventListener('submit', this.handleFilterSubmit.bind(this));
            this.filtersForm.addEventListener('change', this.handleFilterChange.bind(this));
        }
        
        if (this.searchForm) {
            this.searchForm.addEventListener('submit', this.handleSearchSubmit.bind(this));
        }
        
        // Sort change
        if (this.sortSelect) {
            this.sortSelect.addEventListener('change', this.handleSortChange.bind(this));
        }
        
        // View mode buttons
        this.viewButtons.forEach(btn => {
            btn.addEventListener('click', this.handleViewChange.bind(this));
        });
        
        // Filter interactions
        this.bindFilterInteractions();
        
        // Modal events
        this.bindModalEvents();
        
        // Remove filter tags
        document.querySelectorAll('.hph-remove-filter').forEach(btn => {
            btn.addEventListener('click', this.handleRemoveFilter.bind(this));
        });
        
        // Pagination
        this.bindPaginationEvents();
        
        // Real-time search
        const searchInput = document.querySelector('.hph-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.handleSearchInput.bind(this));
        }
    }
    
    bindFilterInteractions() {
        // Bedroom/bathroom options
        document.querySelectorAll('.hph-bedroom-option, .hph-bathroom-option').forEach(option => {
            const input = option.querySelector('input');
            if (input) {
                input.addEventListener('change', () => {
                    // Update active states
                    const group = option.closest('.hph-bedroom-options, .hph-bathroom-options');
                    group.querySelectorAll('.hph-bedroom-option, .hph-bathroom-option').forEach(opt => {
                        opt.classList.remove('active');
                    });
                    if (input.checked) {
                        option.classList.add('active');
                    }
                });
            }
        });
        
        // Price inputs with validation
        document.querySelectorAll('.hph-price-input').forEach(input => {
            input.addEventListener('blur', this.validatePriceRange.bind(this));
        });
        
        // Feature checkboxes
        document.querySelectorAll('.hph-feature-checkbox input').forEach(checkbox => {
            checkbox.addEventListener('change', this.updateFeatureCount.bind(this));
        });
    }
    
    bindModalEvents() {
        if (!this.modal) return;
        
        // Open modal
        const saveSearchBtn = document.querySelector('.hph-save-search-btn');
        if (saveSearchBtn) {
            saveSearchBtn.addEventListener('click', this.openSaveSearchModal.bind(this));
        }
        
        // Close modal
        const closeBtn = this.modal.querySelector('.hph-modal-close');
        const cancelBtn = this.modal.querySelector('[data-dismiss="modal"]');
        
        if (closeBtn) closeBtn.addEventListener('click', this.closeModal.bind(this));
        if (cancelBtn) cancelBtn.addEventListener('click', this.closeModal.bind(this));
        
        // Modal overlay click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });
        
        // Save search form
        const saveForm = document.getElementById('saveSearchForm');
        if (saveForm) {
            saveForm.addEventListener('submit', this.handleSaveSearch.bind(this));
        }
        
        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.closeModal();
            }
        });
    }
    
    bindPaginationEvents() {
        document.querySelectorAll('.hph-pagination a').forEach(link => {
            link.addEventListener('click', this.handlePaginationClick.bind(this));
        });
    }
    
    handleFilterSubmit(e) {
        e.preventDefault();
        this.applyFilters();
    }
    
    handleFilterChange(e) {
        // Auto-apply filters for certain inputs
        if (e.target.type === 'radio' || e.target.type === 'checkbox') {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.applyFilters();
            }, 500);
        }
    }
    
    handleSearchSubmit(e) {
        e.preventDefault();
        this.applyFilters();
    }
    
    handleSearchInput(e) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            if (e.target.value.length >= 3 || e.target.value.length === 0) {
                this.showSearchSuggestions(e.target.value);
            }
        }, 300);
    }
    
    handleSortChange(e) {
        this.updateUrl({ sort_by: e.target.value, paged: 1 });
        this.loadResults();
    }
    
    handleViewChange(e) {
        e.preventDefault();
        const viewMode = e.currentTarget.getAttribute('href').split('view_mode=')[1];
        this.updateUrl({ view_mode: viewMode });
        this.switchView(viewMode);
    }
    
    handleRemoveFilter(e) {
        e.preventDefault();
        const url = e.currentTarget.getAttribute('href');
        window.location.href = url;
    }
    
    handlePaginationClick(e) {
        e.preventDefault();
        const url = new URL(e.currentTarget.href);
        const page = url.searchParams.get('paged') || 1;
        this.updateUrl({ paged: page });
        this.loadResults();
        
        // Scroll to top of results
        this.resultsContainer.scrollIntoView({ behavior: 'smooth' });
    }
    
    applyFilters() {
        if (this.isLoading) return;
        
        const formData = new FormData(this.filtersForm);
        const searchData = new FormData(this.searchForm);
        
        const filters = {};
        
        // Get all form data
        for (let [key, value] of formData.entries()) {
            if (value) {
                if (filters[key]) {
                    if (Array.isArray(filters[key])) {
                        filters[key].push(value);
                    } else {
                        filters[key] = [filters[key], value];
                    }
                } else {
                    filters[key] = value;
                }
            }
        }
        
        // Add search query
        const searchQuery = searchData.get('search');
        if (searchQuery) {
            filters.search = searchQuery;
        }
        
        // Reset to first page when filtering
        filters.paged = 1;
        
        this.updateUrl(filters);
        this.loadResults();
    }
    
    loadResults() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        const params = new URLSearchParams(window.location.search);
        params.append('ajax', '1');
        
        fetch(window.location.pathname + '?' + params.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            this.updateResults(html);
            this.hideLoading();
            this.isLoading = false;
        })
        .catch(error => {
            console.error('Error loading results:', error);
            this.showMessage('Error loading results. Please try again.', 'error');
            this.hideLoading();
            this.isLoading = false;
        });
    }
    
    updateResults(html) {
        // Parse the response and update the results container
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newResults = doc.querySelector('.hph-results-content');
        const newHeader = doc.querySelector('.hph-results-header');
        
        if (newResults) {
            this.resultsContainer.innerHTML = newResults.innerHTML;
            
            // Re-initialize swipe cards
            const cards = this.resultsContainer.querySelectorAll('.hph-swipe-card');
            cards.forEach(card => {
                if (window.HPHSwipeCard) {
                    new window.HPHSwipeCard(card);
                }
            });
        }
        
        if (newHeader) {
            const currentHeader = document.querySelector('.hph-results-header');
            if (currentHeader) {
                currentHeader.innerHTML = newHeader.innerHTML;
                this.bindPaginationEvents();
            }
        }
        
        // Update browser history
        window.history.pushState(null, '', window.location.href);
    }
    
    switchView(viewMode) {
        // Update active view button
        this.viewButtons.forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = document.querySelector(`[href*="view_mode=${viewMode}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
        
        // Handle map view initialization
        if (viewMode === 'map') {
            setTimeout(() => {
                this.initializeMap();
            }, 100);
        }
    }
    
    initializeMap() {
        const mapContainer = document.getElementById('listingsMap');
        if (!mapContainer || typeof google === 'undefined') return;
        
        // Initialize Google Maps
        const map = new google.maps.Map(mapContainer, {
            zoom: 12,
            center: { lat: 39.7391536, lng: -75.5397878 }, // Wilmington, DE
            styles: [
                {
                    featureType: 'all',
                    elementType: 'geometry.fill',
                    stylers: [{ color: '#f5f5f5' }]
                }
            ]
        });
        
        // Add markers for listings
        const listings = document.querySelectorAll('.hph-map-listing-card');
        listings.forEach(listing => {
            const listingId = listing.dataset.listingId;
            // You would fetch coordinates from your listing data
            // This is a placeholder implementation
            this.addMapMarker(map, listingId, listing);
        });
    }
    
    addMapMarker(map, listingId, listingElement) {
        // Placeholder coordinates - you'd get these from your listing data
        const lat = 39.7391536 + (Math.random() - 0.5) * 0.1;
        const lng = -75.5397878 + (Math.random() - 0.5) * 0.1;
        
        const marker = new google.maps.Marker({
            position: { lat, lng },
            map: map,
            title: `Listing ${listingId}`
        });
        
        marker.addListener('click', () => {
            // Highlight corresponding listing card
            this.highlightMapListing(listingElement);
        });
    }
    
    highlightMapListing(listingElement) {
        // Remove previous highlights
        document.querySelectorAll('.hph-map-listing-card').forEach(card => {
            card.classList.remove('highlighted');
        });
        
        // Highlight selected listing
        listingElement.classList.add('highlighted');
        listingElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    showSearchSuggestions(query) {
        if (!query) {
            this.hideSuggestions();
            return;
        }
        
        // Make AJAX request for suggestions
        fetch(`${window.location.origin}/wp-admin/admin-ajax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hph_search_suggestions&query=${encodeURIComponent(query)}&nonce=${window.hphAjax?.nonce || ''}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displaySuggestions(data.data);
            }
        })
        .catch(error => {
            console.error('Error fetching suggestions:', error);
        });
    }
    
    displaySuggestions(suggestions) {
        let suggestionsContainer = document.querySelector('.hph-search-suggestions');
        
        if (!suggestionsContainer) {
            suggestionsContainer = document.createElement('div');
            suggestionsContainer.className = 'hph-search-suggestions';
            this.searchForm.appendChild(suggestionsContainer);
        }
        
        if (suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        const html = suggestions.map(suggestion => 
            `<div class="hph-suggestion-item" data-value="${suggestion.value}">
                <i class="fas fa-map-marker-alt"></i>
                <span>${suggestion.label}</span>
            </div>`
        ).join('');
        
        suggestionsContainer.innerHTML = html;
        suggestionsContainer.style.display = 'block';
        
        // Bind click events
        suggestionsContainer.querySelectorAll('.hph-suggestion-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const value = e.currentTarget.dataset.value;
                document.querySelector('.hph-search-input').value = value;
                this.hideSuggestions();
                this.applyFilters();
            });
        });
    }
    
    hideSuggestions() {
        const suggestionsContainer = document.querySelector('.hph-search-suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }
    }
    
    setupAutoComplete() {
        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.hph-search-form')) {
                this.hideSuggestions();
            }
        });
    }
    
    validatePriceRange() {
        const minInput = document.querySelector('input[name="price_min"]');
        const maxInput = document.querySelector('input[name="price_max"]');
        
        if (minInput && maxInput && minInput.value && maxInput.value) {
            const min = parseInt(minInput.value);
            const max = parseInt(maxInput.value);
            
            if (min > max) {
                this.showMessage('Minimum price cannot be greater than maximum price.', 'warning');
                minInput.focus();
                return false;
            }
        }
        return true;
    }
    
    updateFeatureCount() {
        const checkedFeatures = document.querySelectorAll('.hph-feature-checkbox input:checked').length;
        const featureTitle = document.querySelector('.hph-filter-group:has(.hph-features-checkboxes) .hph-filter-title');
        
        if (featureTitle) {
            const baseText = 'Features';
            featureTitle.textContent = checkedFeatures > 0 ? `${baseText} (${checkedFeatures})` : baseText;
        }
    }
    
    updateFilterUI() {
        // Update bedroom/bathroom active states
        document.querySelectorAll('input[name="bedrooms"]:checked, input[name="bathrooms"]:checked').forEach(input => {
            const option = input.closest('.hph-bedroom-option, .hph-bathroom-option');
            if (option) {
                option.classList.add('active');
            }
        });
        
        // Update feature count
        this.updateFeatureCount();
    }
    
    openSaveSearchModal() {
        if (this.modal) {
            this.modal.classList.add('active');
            this.modal.style.display = 'flex';
            
            // Focus on name input
            const nameInput = this.modal.querySelector('#searchName');
            if (nameInput) {
                setTimeout(() => nameInput.focus(), 100);
            }
        }
    }
    
    closeModal() {
        if (this.modal) {
            this.modal.classList.remove('active');
            setTimeout(() => {
                this.modal.style.display = 'none';
            }, 300);
        }
    }
    
    handleSaveSearch(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const searchName = formData.get('search_name');
        const emailAlerts = formData.get('email_alerts') === 'on';
        
        if (!searchName.trim()) {
            this.showMessage('Please enter a name for your search.', 'warning');
            return;
        }
        
        // Save search via AJAX
        fetch(`${window.location.origin}/wp-admin/admin-ajax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=hph_save_search&search_name=${encodeURIComponent(searchName)}&email_alerts=${emailAlerts}&filters=${encodeURIComponent(JSON.stringify(this.currentFilters))}&nonce=${window.hphAjax?.nonce || ''}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showMessage('Search saved successfully!', 'success');
                this.closeModal();
            } else {
                this.showMessage(data.data || 'Error saving search.', 'error');
            }
        })
        .catch(error => {
            console.error('Error saving search:', error);
            this.showMessage('Error saving search. Please try again.', 'error');
        });
    }
    
    updateUrl(params) {
        const url = new URL(window.location);
        
        Object.keys(params).forEach(key => {
            if (params[key]) {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        
        window.history.replaceState(null, '', url.toString());
        this.currentFilters = this.getUrlParams();
    }
    
    getUrlParams() {
        const params = new URLSearchParams(window.location.search);
        const filters = {};
        
        for (let [key, value] of params.entries()) {
            filters[key] = value;
        }
        
        return filters;
    }
    
    showLoading() {
        if (this.resultsContainer) {
            this.resultsContainer.classList.add('loading');
        }
    }
    
    hideLoading() {
        if (this.resultsContainer) {
            this.resultsContainer.classList.remove('loading');
        }
    }
    
    showMessage(message, type = 'info') {
        // Remove existing messages
        document.querySelectorAll('.hph-message').forEach(msg => msg.remove());
        
        const messageEl = document.createElement('div');
        messageEl.className = `hph-message hph-message--${type}`;
        messageEl.textContent = message;
        
        // Style the message
        messageEl.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: ${type === 'error' ? '#dc2626' : type === 'warning' ? '#d97706' : type === 'success' ? '#059669' : 'rgba(8, 47, 73, 0.95)'};
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            z-index: 10000;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideDown 0.3s ease;
            max-width: 400px;
            text-align: center;
        `;
        
        document.body.appendChild(messageEl);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.style.animation = 'slideUp 0.3s ease forwards';
                setTimeout(() => {
                    if (messageEl.parentNode) {
                        messageEl.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on listings archive page
    if (document.querySelector('.hph-listings-archive')) {
        new HPHListingsArchive();
    }
    
    // Add CSS animations for messages if not already present
    if (!document.querySelector('#hph-archive-animations')) {
        const style = document.createElement('style');
        style.id = 'hph-archive-animations';
        style.textContent = `
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
            }
            @keyframes slideUp {
                from {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-20px);
                }
            }
            
            .hph-search-suggestions {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                z-index: 100;
                max-height: 300px;
                overflow-y: auto;
                display: none;
            }
            
            .hph-suggestion-item {
                padding: 12px 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                cursor: pointer;
                border-bottom: 1px solid #f3f4f6;
                transition: background-color 0.15s ease;
            }
            
            .hph-suggestion-item:hover {
                background: #f9fafb;
            }
            
            .hph-suggestion-item:last-child {
                border-bottom: none;
            }
            
            .hph-suggestion-item i {
                color: #6b7280;
                width: 14px;
            }
            
            .hph-map-listing-card.highlighted {
                transform: scale(1.02);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                border: 2px solid var(--hph-color-primary-400);
            }
        `;
        document.head.appendChild(style);
    }
});

// Export for use in other scripts
window.HPHListingsArchive = HPHListingsArchive;