// Happy Place Handbook - Core JavaScript

(function() {
    // Utility functions
    const HPH = {
        // Debounce function to limit rate of function calls
        debounce: function(func, wait = 250) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },

        // Simple element selector
        select: function(selector, context = document) {
            return context.querySelector(selector);
        },

        // Select all matching elements
        selectAll: function(selector, context = document) {
            return Array.from(context.querySelectorAll(selector));
        },

        // Toggle class on an element
        toggleClass: function(element, className) {
            if (!element) return;
            element.classList.toggle(className);
        },

        // Add event listener with optional delegation
        on: function(eventName, selector, callback, context = document) {
            if (typeof selector === 'function') {
                // Direct event listener
                context.addEventListener(eventName, selector);
                return;
            }

            // Delegated event listener
            context.addEventListener(eventName, function(event) {
                const targetElement = event.target.closest(selector);
                if (targetElement) {
                    callback.call(targetElement, event);
                }
            });
        }
    };

    // Mobile menu toggle
    function initMobileMenu() {
        const menuToggle = HPH.select('.hph-mobile-menu-toggle');
        const mobileMenu = HPH.select('.hph-mobile-menu');

        if (menuToggle && mobileMenu) {
            HPH.on('click', '.hph-mobile-menu-toggle', () => {
                HPH.toggleClass(mobileMenu, 'is-active');
                HPH.toggleClass(menuToggle, 'is-active');
            });
        }
    }

    // Smooth scroll for anchor links
    function initSmoothScroll() {
        HPH.on('click', 'a[href^="#"]', function(event) {
            event.preventDefault();
            const targetId = this.getAttribute('href');
            const target = HPH.select(targetId);

            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }

    // Responsive design helpers
    function initResponsiveHelpers() {
        const checkBreakpoint = HPH.debounce(() => {
            const breakpoint = window.getComputedStyle(document.body, ':after').content;
            document.body.setAttribute('data-breakpoint', breakpoint.replace(/['"]/g, ''));
        });

        window.addEventListener('resize', checkBreakpoint);
        checkBreakpoint();
    }

    // Form validation utility
    function initFormValidation() {
        HPH.on('submit', '.hph-form', function(event) {
            const requiredFields = HPH.selectAll('[required]', this);
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                event.preventDefault();
            }
        });
    }

    // Initialize dropdown menus
    function initDropdowns() {
        HPH.on('click', '.hph-dropdown-toggle', function() {
            const dropdown = this.closest('.hph-dropdown');
            HPH.toggleClass(dropdown, 'is-active');
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const activeDropdowns = HPH.selectAll('.hph-dropdown.is-active');
            activeDropdowns.forEach(dropdown => {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove('is-active');
                }
            });
        });
    }

    // Main initialization
    function init() {
        initMobileMenu();
        initSmoothScroll();
        initResponsiveHelpers();
        initFormValidation();
        initDropdowns();
    }

    // Run initialization when DOM is fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose utility functions globally if needed
    window.HPH = HPH;
})();