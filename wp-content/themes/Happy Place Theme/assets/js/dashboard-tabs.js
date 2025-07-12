/**
 * Dashboard Tabs Functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const DashboardTabs = {
        init: function() {
            this.navItems = document.querySelectorAll('.hph-dashboard-nav-item');
            this.sections = document.querySelectorAll('.hph-dashboard-section');
            this.currentSection = this.getCurrentSection();
            
            this.bindEvents();
            this.initFromHash();
            this.showSection(this.currentSection);
        },

        bindEvents: function() {
            this.navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const section = item.dataset.section;
                    this.switchToSection(section);
                });
            });

            // Handle browser back/forward buttons and hash changes
            window.addEventListener('popstate', (e) => {
                const section = this.getCurrentSectionFromHash();
                if (section) {
                    this.showSection(section, false);
                }
            });

            window.addEventListener('hashchange', (e) => {
                const section = this.getCurrentSectionFromHash();
                if (section) {
                    this.showSection(section, true);
                }
            });
        },

        initFromHash: function() {
            const hashSection = this.getCurrentSectionFromHash();
            if (hashSection) {
                this.currentSection = hashSection;
            }
        },

        getCurrentSectionFromHash: function() {
            const hash = window.location.hash.replace('#', '');
            return hash || 'overview';
        },

        getCurrentSection: function() {
            // First try hash
            const hashSection = this.getCurrentSectionFromHash();
            if (hashSection) {
                return hashSection;
            }

            // Then try URL params
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('dashboard_section') || urlParams.get('section');
            return sectionParam || 'overview';
        },

        switchToSection: function(section) {
            if (section === this.currentSection) return;

            // Update URL without page reload
            const newUrl = this.updateURL(section);
            history.pushState({section: section}, '', newUrl);

            // Load the section
            this.showSection(section);
        },

        showSection: function(section, updateNav = true) {
            // Update navigation
            if (updateNav) {
                this.updateNavigation(section);
            }

            // Hide all sections
            this.sections.forEach(sec => {
                sec.classList.remove('hph-dashboard-section--active');
            });

            // Show target section
            const targetSection = document.getElementById(section);
            if (targetSection) {
                // Check if section content needs to be loaded
                if (targetSection.innerHTML.trim() === '' || targetSection.dataset.needsRefresh === 'true') {
                    this.loadSectionContent(section, targetSection);
                } else {
                    targetSection.classList.add('hph-dashboard-section--active');
                }
            }

            this.currentSection = section;
        },

        updateNavigation: function(activeSection) {
            this.navItems.forEach(item => {
                if (item.dataset.section === activeSection) {
                    item.classList.add('hph-dashboard-nav-item--active');
                    item.setAttribute('aria-selected', 'true');
                } else {
                    item.classList.remove('hph-dashboard-nav-item--active');
                    item.setAttribute('aria-selected', 'false');
                }
            });
        },

        loadSectionContent: function(section, targetElement) {
            // Show loading state
            targetElement.classList.add('hph-dashboard-section--loading');
            targetElement.classList.add('hph-dashboard-section--active');

            // Load content via AJAX
            fetch(dashboardAjax.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_load_dashboard_section',
                    section: section,
                    nonce: dashboardAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    targetElement.innerHTML = data.data.content;
                    targetElement.classList.remove('hph-dashboard-section--loading');
                    
                    // Initialize any section-specific JavaScript
                    this.initSectionFeatures(section);
                } else {
                    targetElement.innerHTML = '<div class="hph-error">Error loading section. Please refresh the page.</div>';
                    targetElement.classList.remove('hph-dashboard-section--loading');
                }
            })
            .catch(error => {
                console.error('Dashboard section load error:', error);
                targetElement.innerHTML = '<div class="hph-error">Error loading section. Please refresh the page.</div>';
                targetElement.classList.remove('hph-dashboard-section--loading');
            });
        },

        initSectionFeatures: function(section) {
            // Initialize section-specific features
            if (window.HphDashboard && HphDashboard['init' + section.charAt(0).toUpperCase() + section.slice(1) + 'Features']) {
                HphDashboard['init' + section.charAt(0).toUpperCase() + section.slice(1) + 'Features']();
            }
        },

        updateURL: function(section) {
            const url = new URL(window.location);
            // Remove section from query params if it exists
            url.searchParams.delete('section');
            url.searchParams.delete('dashboard_section');
            // Set the hash without triggering the hashchange event
            if (url.hash !== '#' + section) {
                url.hash = section;
            }
            return url.toString();
        }
    };

    // Initialize dashboard tabs
    DashboardTabs.init();

    // Make it globally available
    window.DashboardTabs = DashboardTabs;
});
