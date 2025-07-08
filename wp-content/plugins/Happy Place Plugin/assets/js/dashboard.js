/**
 * Agent Dashboard Functionality
 */
(function($) {
    'use strict';

    const HPHDashboard = {
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.setupImagePreviews();
        },

        bindEvents: function() {
            // Navigation
            $('.hph-dashboard-nav-item').on('click', this.handleNavigation.bind(this));
            
            // Profile Form
            $('#hph-profile-form').on('submit', this.handleProfileUpdate.bind(this));
            
            // Stats Period Selection
            $('.hph-stats-period').on('change', this.updateStats.bind(this));
            
            // Listing Actions
            $('.hph-listing-action').on('click', this.handleListingAction.bind(this));
            
            // Open House Form
            $('#hph-open-house-form').on('submit', this.handleOpenHouseSubmit.bind(this));
        },

        handleNavigation: function(e) {
            e.preventDefault();
            const $link = $(e.currentTarget);
            const section = $link.data('section');

            // Update active state
            $('.hph-dashboard-nav-item').removeClass('hph-dashboard-nav-item--active');
            $link.addClass('hph-dashboard-nav-item--active');

            // Load section content
            this.loadSection(section);
        },

        loadSection: function(section) {
            const $main = $('.hph-dashboard-main');
            
            $main.addClass('hph-dashboard-main--loading');

            wp.ajax.post('hph_load_dashboard_section', {
                nonce: hphDashboard.nonce,
                section: section
            })
            .done(function(response) {
                $main.html(response.content);
                this.initSectionFeatures(section);
            }.bind(this))
            .fail(function() {
                alert('Error loading section. Please try again.');
            })
            .always(function() {
                $main.removeClass('hph-dashboard-main--loading');
            });
        },

        initSectionFeatures: function(section) {
            switch (section) {
                case 'stats':
                    this.initCharts();
                    break;
                case 'listings':
                    this.initListingsTable();
                    break;
                case 'open-houses':
                    this.initDatepicker();
                    break;
            }
        },

        handleProfileUpdate: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $submit = $form.find('button[type="submit"]');
            const formData = new FormData($form[0]);

            $submit.prop('disabled', true);

            wp.ajax.post('hph_update_agent_profile', {
                nonce: hphDashboard.profileNonce,
                ...formData
            })
            .done(function(response) {
                alert('Profile updated successfully!');
            })
            .fail(function() {
                alert('Error updating profile. Please try again.');
            })
            .always(function() {
                $submit.prop('disabled', false);
            });
        },

        updateStats: function(e) {
            const period = $(e.currentTarget).val();
            this.loadStats(period);
        },

        loadStats: function(period) {
            const $stats = $('.hph-stats-section');
            
            $stats.addClass('hph-stats-section--loading');

            wp.ajax.post('hph_get_listing_stats', {
                nonce: hphDashboard.statsNonce,
                period: period
            })
            .done(function(response) {
                this.updateCharts(response);
            }.bind(this))
            .fail(function() {
                alert('Error loading statistics. Please try again.');
            })
            .always(function() {
                $stats.removeClass('hph-stats-section--loading');
            });
        },

        initCharts: function() {
            if (!window.Chart) {
                return;
            }

            // Views Chart
            const viewsCtx = document.getElementById('viewsChart');
            if (viewsCtx) {
                new Chart(viewsCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Daily Views',
                            data: [],
                            borderColor: '#2563eb',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            // Inquiries Chart
            const inquiriesCtx = document.getElementById('inquiriesChart');
            if (inquiriesCtx) {
                new Chart(inquiriesCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Inquiries',
                            data: [],
                            backgroundColor: '#3b82f6'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },

        updateCharts: function(data) {
            const viewsChart = Chart.getChart('viewsChart');
            const inquiriesChart = Chart.getChart('inquiriesChart');

            if (viewsChart) {
                viewsChart.data.labels = data.views.chart_data.map(d => d.date);
                viewsChart.data.datasets[0].data = data.views.chart_data.map(d => d.count);
                viewsChart.update();
            }

            if (inquiriesChart) {
                inquiriesChart.data.labels = data.inquiries.chart_data.map(d => d.date);
                inquiriesChart.data.datasets[0].data = data.inquiries.chart_data.map(d => d.count);
                inquiriesChart.update();
            }

            // Update summary stats
            $('.hph-stat-value--views').text(data.views.total);
            $('.hph-stat-value--inquiries').text(data.inquiries.total);
            $('.hph-stat-value--listings').text(data.listings.active);
        },

        setupImagePreviews: function() {
            $('#agent-photo').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('.hph-profile-photo-preview').html(`
                            <img src="${e.target.result}" alt="Profile preview">
                        `);
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        initListingsTable: function() {
            if ($.fn.DataTable) {
                $('.hph-listings-table').DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    responsive: true
                });
            }
        },

        initDatepicker: function() {
            if ($.fn.datepicker) {
                $('.hph-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0
                });

                $('.hph-timepicker').timepicker({
                    timeFormat: 'h:mm p',
                    interval: 30,
                    minTime: '8',
                    maxTime: '8:00pm',
                    dynamic: false,
                    dropdown: true,
                    scrollbar: true
                });
            }
        },

        handleListingAction: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const action = $button.data('action');
            const listingId = $button.data('listing-id');

            if (confirm('Are you sure you want to ' + action + ' this listing?')) {
                wp.ajax.post('hph_listing_action', {
                    nonce: hphDashboard.nonce,
                    action: action,
                    listing_id: listingId
                })
                .done(function(response) {
                    window.location.reload();
                })
                .fail(function() {
                    alert('Error performing action. Please try again.');
                });
            }
        },

        handleOpenHouseSubmit: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $submit = $form.find('button[type="submit"]');

            $submit.prop('disabled', true);

            wp.ajax.post('hph_save_open_house', {
                nonce: hphDashboard.nonce,
                ...new FormData($form[0])
            })
            .done(function(response) {
                alert('Open house saved successfully!');
                window.location.reload();
            })
            .fail(function() {
                alert('Error saving open house. Please try again.');
            })
            .always(function() {
                $submit.prop('disabled', false);
            });
        }
    };

    $(document).ready(function() {
        HPHDashboard.init();
    });

})(jQuery);
