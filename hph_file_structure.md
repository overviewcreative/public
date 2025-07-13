# 🏠 Happy Place Real Estate Platform - Complete File Structure

## Plugin Structure (`wp-content/plugins/happy-place-plugin/`)

### Core Plugin Files
```
happy-place-plugin/
├── happy-place-plugin.php          # Main plugin file with autoloader
├── composer.json                   # Dependencies (DomPDF, etc.)
├── composer.lock
├── vendor/                        # Composer dependencies
│   └── dompdf/                   # PDF generation
└── readme.md                     # Plugin documentation
```

### Includes Directory (`includes/`)
```
includes/
├── class-happy-place-plugin.php   # Main plugin class
├── class-database.php             # Database schema and operations
├── class-compliance.php           # Legal compliance features
│
├── admin/                         # Admin functionality
│   ├── class-admin-menu.php      # Admin dashboard and menus
│   ├── class-csv-import-tool.php # Listing import functionality
│   ├── class-integrations-manager.php
│   ├── class-dashboard-manager.php
│   ├── class-listing-actions.php  # PDF generation, listing actions
│   └── dashboard/
│       └── class-admin-dashboard.php
│
├── core/                          # Core WordPress integrations
│   ├── class-post-types.php      # Custom post types (listing, agent, etc.)
│   ├── class-taxonomies.php      # Custom taxonomies
│   └── class-meta-boxes.php      # Custom meta boxes
│
├── users/                         # User management
│   ├── class-user-roles-manager.php      # Custom roles and capabilities
│   ├── class-user-registration-manager.php # Registration handling
│   ├── class-user-dashboard.php           # User dashboard logic
│   └── class-agent-profile-manager.php    # Agent-specific functionality
│
├── dashboard/                     # Dashboard system
│   ├── class-dashboard-handler.php       # Main dashboard controller
│   ├── class-ajax-handler.php           # AJAX endpoints for dashboard
│   ├── sections/                         # Individual section handlers
│   │   ├── class-overview-section.php
│   │   ├── class-listings-section.php
│   │   ├── class-leads-section.php
│   │   ├── class-open-houses-section.php
│   │   ├── class-performance-section.php
│   │   ├── class-profile-section.php
│   │   ├── class-settings-section.php
│   │   └── class-cache-section.php
│   └── class-dashboard-assets.php        # Dashboard-specific assets
│
├── api/                           # API endpoints
│   ├── class-rest-api.php        # REST API setup
│   ├── endpoints/
│   │   ├── class-listings-endpoint.php
│   │   ├── class-agents-endpoint.php
│   │   ├── class-search-endpoint.php
│   │   └── class-dashboard-endpoint.php
│   └── class-api-auth.php        # API authentication
│
├── search/                        # Search functionality
│   ├── class-search-filter-handler.php
│   ├── class-search-query-builder.php
│   └── class-search-ajax-handler.php
│
├── forms/                         # Form handlers
│   ├── class-inquiry-form-handler.php
│   ├── class-contact-form-handler.php
│   ├── class-listing-form-handler.php
│   └── class-registration-form-handler.php
│
├── integrations/                  # Third-party integrations
│   ├── class-airtable-sync.php
│   ├── class-followupboss-sync.php
│   ├── class-dotloop-integration.php
│   ├── class-mls-sync.php
│   └── class-marketing-integrations.php
│
├── crm/                          # CRM functionality
│   ├── class-crm-sync-manager.php
│   ├── class-lead-manager.php
│   ├── class-client-tracker.php
│   └── class-inquiry-manager.php
│
├── utilities/                     # Utility classes
│   ├── class-pdf-generator.php   # PDF generation with DomPDF
│   ├── class-geocoding.php       # Address geocoding
│   ├── class-data-sanitizer.php  # Data validation and sanitization
│   ├── class-image-processor.php # Image optimization
│   └── class-cache-manager.php   # Caching utilities
│
├── graphics/                     # Graphics generation
│   ├── class-flyer-generator.php
│   └── class-social-media-graphics.php
│
└── front/                        # Frontend functionality
    ├── class-assets.php          # Frontend asset management
    ├── class-shortcodes.php      # Custom shortcodes
    └── class-frontend-ajax.php   # Frontend AJAX handlers
```

### Fields Directory (`fields/`)
```
fields/
├── json/                         # ACF JSON exports
│   ├── group_listing_details.json
│   ├── group_agent_details.json
│   ├── group_community_details.json
│   ├── group_city_details.json
│   ├── group_open_house_details.json
│   ├── group_lead_details.json
│   └── group_user_profile.json
│
└── class-acf-field-groups.php   # ACF field group registration
```

### Assets Directory (`assets/`)
```
assets/
├── css/
│   ├── admin/
│   │   ├── admin.css             # General admin styles
│   │   ├── dashboard.css         # Admin dashboard styles
│   │   ├── integrations.css      # Integration UI styles
│   │   └── pdf-button.css        # PDF generation button styles
│   │
│   └── frontend/
│       ├── search.css            # Search interface styles
│       └── forms.css             # Form styles
│
├── js/
│   ├── admin/
│   │   ├── admin.js              # General admin functionality
│   │   ├── dashboard.js          # Dashboard functionality
│   │   ├── integrations.js       # Integration management
│   │   └── csv-import.js         # CSV import interface
│   │
│   └── frontend/
│       ├── search.js             # Search functionality
│       ├── forms.js              # Form handling
│       └── map.js                # Map integration
│
└── images/
    ├── icons/                    # UI icons
    └── placeholders/             # Placeholder images
```

### Templates Directory (`templates/`)
```
templates/
├── admin/                        # Admin page templates
│   ├── dashboard.php
│   ├── settings.php
│   ├── integrations.php
│   └── csv-import.php
│
├── dashboard/                    # Agent dashboard templates
│   ├── overview.php
│   ├── listings.php
│   ├── leads.php
│   ├── open-houses.php
│   ├── performance.php
│   ├── profile.php
│   ├── settings.php
│   └── cache.php
│
├── emails/                       # Email templates
│   ├── lead-notification.php
│   ├── listing-inquiry.php
│   ├── open-house-reminder.php
│   └── welcome-agent.php
│
└── pdf/                          # PDF templates
    ├── listing-flyer.php
    ├── market-report.php
    └── agent-profile.php
```

## Theme Structure (`wp-content/themes/happy-place-theme/`)

### Core Theme Files
```
happy-place-theme/
├── style.css                     # Theme metadata
├── functions.php                 # Theme initialization
├── index.php                     # Fallback template
├── header.php                    # Site header
├── footer.php                    # Site footer
├── sidebar.php                   # Default sidebar
└── 404.php                       # 404 error page
```

### Page Templates (`page-templates/`)
```
page-templates/
├── page-agent-dashboard.php      # Agent dashboard template
├── page-client-dashboard.php     # Client dashboard template
├── page-broker-dashboard.php     # Broker dashboard template
├── page-search.php               # Advanced search page
├── page-contact.php              # Contact page
└── page-about.php                # About page
```

### Templates Directory (`templates/`)
```
templates/
├── listing/                      # Listing templates
│   ├── archive-listing.php      # Listing archive
│   ├── single-listing.php       # Single listing
│   └── taxonomy-listing_type.php # Listing type taxonomy
│
├── agent/                        # Agent templates
│   ├── archive-agent.php        # Agent directory
│   └── single-agent.php         # Agent profile
│
├── community/                    # Community templates
│   ├── archive-community.php
│   └── single-community.php
│
├── city/                         # City templates
│   ├── archive-city.php
│   └── single-city.php
│
├── template-parts/               # Reusable components
│   ├── cards/
│   │   ├── listing-swipe-card.php      # Swipe card component
│   │   ├── listing-list-card.php       # List view card
│   │   ├── agent-card.php              # Agent profile card
│   │   └── community-card.php          # Community card
│   │
│   ├── forms/
│   │   ├── search-form.php             # Property search form
│   │   ├── contact-form.php            # Contact form
│   │   ├── inquiry-form.php            # Property inquiry form
│   │   └── registration-form.php       # User registration
│   │
│   ├── navigation/
│   │   ├── main-nav.php               # Main navigation
│   │   ├── breadcrumbs.php            # Breadcrumb navigation
│   │   └── pagination.php             # Pagination component
│   │
│   └── dashboard/                     # Dashboard components
│       ├── section-overview.php       # Overview section
│       ├── section-listings.php       # Listings management
│       ├── section-leads.php          # Lead management
│       ├── section-open-houses.php    # Open house scheduling
│       ├── section-performance.php    # Analytics and performance
│       ├── section-profile.php        # Profile editing
│       ├── section-settings.php       # User settings
│       ├── section-cache.php          # Cache management (admin)
│       └── section-default.php        # Fallback section template
│
├── partials/                     # Smaller partial templates
│   ├── hero-section.php
│   ├── featured-listings.php
│   ├── agent-contact-card.php
│   ├── property-filters.php
│   ├── map-container.php
│   └── no-results.php
│
└── layouts/                      # Layout templates
    ├── full-width.php
    ├── sidebar-left.php
    └── sidebar-right.php
```

### Assets Directory (`assets/`)
```
assets/
├── css/
│   ├── core/
│   │   ├── reset.css                  # CSS reset
│   │   ├── variables.css              # CSS custom properties
│   │   ├── typography.css             # Typography system
│   │   └── utilities.css              # Utility classes
│   │
│   ├── components/
│   │   ├── buttons.css                # Button styles
│   │   ├── forms.css                  # Form styles
│   │   ├── cards.css                  # Card components
│   │   ├── navigation.css             # Navigation styles
│   │   ├── modals.css                 # Modal styles
│   │   ├── swipe-cards.css            # Swipe card system
│   │   └── dashboard.css              # Dashboard components
│   │
│   ├── layouts/
│   │   ├── grid.css                   # Grid system
│   │   ├── header.css                 # Header layout
│   │   ├── footer.css                 # Footer layout
│   │   └── sidebar.css                # Sidebar layout
│   │
│   ├── pages/
│   │   ├── home.css                   # Homepage styles
│   │   ├── search.css                 # Search page styles
│   │   ├── listing.css                # Listing page styles
│   │   ├── agent.css                  # Agent page styles
│   │   └── dashboard/                 # Dashboard styles
│   │       ├── dashboard-structure.css    # Layout structure
│   │       ├── dashboard-core.css         # Core components
│   │       ├── dashboard-sections.css     # Section-specific styles
│   │       ├── dashboard-responsive.css   # Responsive behaviors
│   │       └── dashboard-modals.css       # Modals and overlays
│   │
│   └── theme.css                      # Main compiled stylesheet
│
├── js/
│   ├── core/
│   │   ├── utils.js                   # Utility functions
│   │   ├── ajax.js                    # AJAX helpers
│   │   └── events.js                  # Event handling
│   │
│   ├── components/
│   │   ├── swipe-cards.js             # Swipe card functionality
│   │   ├── search.js                  # Search functionality
│   │   ├── forms.js                   # Form handling
│   │   ├── map.js                     # Map integration
│   │   ├── modals.js                  # Modal functionality
│   │   └── dashboard.js               # Dashboard functionality
│   │
│   ├── pages/
│   │   ├── listing-archive.js         # Archive page functionality
│   │   ├── single-listing.js          # Single listing features
│   │   └── agent-dashboard.js         # Agent dashboard
│   │
│   └── theme.js                       # Main theme JavaScript
│
├── images/
│   ├── icons/                         # SVG icons
│   ├── logos/                         # Brand logos
│   ├── placeholders/                  # Placeholder images
│   └── backgrounds/                   # Background images
│
└── fonts/                             # Custom fonts
    ├── inter/                         # Inter font family
    └── source-sans-pro/               # Source Sans Pro
```

## Critical Integration Points

### 1. Dashboard System Integration
- **Plugin**: Handles data operations, AJAX endpoints, user management
- **Theme**: Provides templates, styling, and user interface
- **Bridge**: `HPH_Dashboard_Handler` class coordinates between plugin and theme

### 2. Custom Post Types & ACF Integration
```php
// Plugin registers CPTs and ACF fields
// Theme templates consume the data
// Dashboard provides management interface
```

### 3. User Role Management
```php
// Plugin: class-user-roles-manager.php defines roles
// Theme: Uses role-based template rendering
// Dashboard: Role-specific sections and permissions
```

### 4. AJAX Communication
```php
// Plugin: AJAX handlers in includes/dashboard/class-ajax-handler.php
// Theme: JavaScript initiates AJAX calls
// Dashboard: Processes and returns formatted responses
```

### 5. Asset Management
```php
// Plugin: Backend functionality assets
// Theme: Frontend presentation assets
// Dashboard: Specialized dashboard assets
```

## Implementation Priority

### Phase 1: Core Foundation
1. Plugin custom post types and ACF fields
2. User roles and capabilities
3. Basic dashboard template structure

### Phase 2: Dashboard Functionality
1. AJAX handlers for dashboard sections
2. Dashboard section templates
3. Asset loading system

### Phase 3: Frontend Features
1. Swipe card system integration
2. Archive page with search/filters
3. Single listing templates

### Phase 4: Advanced Features
1. Map integration
2. PDF generation
3. CRM integrations
4. Performance analytics

This structure ensures clean separation of concerns while maintaining tight integration between the plugin (data/logic) and theme (presentation/templates) for your Happy Place Real Estate Platform.