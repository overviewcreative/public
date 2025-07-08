🔌 Happy Place Plugin File Structure
happy-place-plugin/
│
├── includes/                    # Core plugin functionality
│   ├── admin/                   # Administration-related classes
│   │   ├── class-integrations-manager.php
│   │   ├── class-dashboard-manager.php
│   │   └── class-listing-actions.php   # PDF generation UI and handlers
│   │
│   ├── core/                    # Core plugin components
│   │   ├── class-post-types.php
│   │   └── class-taxonomies.php
│   │
│   ├── crm/                     # Customer Relationship Management
│   │   ├── class-crm-sync-manager.php
│   │   ├── class-lead-manager.php
│   │   └── class-client-tracker.php
│   │
│   ├── integrations/            # Third-party integrations
│   │   ├── class-airtable-sync.php
│   │   ├── class-followupboss-sync.php
│   │   ├── class-dotloop-integration.php
│   │   └── class-marketing-integrations.php
│   │
│   ├── search/                  # Search functionality
│   │   └── class-search-filter-handler.php
│   │
│   ├── utilities/               # Utility classes
│   │   ├── class-pdf-generator.php     # DomPDF-based PDF generation
│   │   ├── class-geocoding.php
│   │   └── class-data-sanitizer.php
│   │
│   ├── front/                  # Frontend functionality
│   │   └── class-assets.php    # Frontend asset management
│   │
│   └── class-happy-place-plugin.php  # Main plugin initialization
│
├── fields/                      # ACF Field configurations
│   └── json/
│       ├── group_listing_details.json
│       ├── group_agent_details.json
│       ├── group_community_details.json
│       └── group_city_details.json
│
├── assets/                      # Plugin assets
│   ├── css/
│   │   ├── admin.css
│   │   ├── integrations.css
│   │   └── pdf-button.css      # PDF download button styles
│   │
│   └── js/
│       ├── admin.js
│       └── integrations.js
│
├── languages/                   # Translation files
│   ├── happy-place-en_US.po
│   └── happy-place-en_US.mo
│
├── templates/                   # Plugin templates
│   ├── admin/
│   │   ├── dashboard.php
│   │   └── settings.php
│   │
│   └── emails/
│       ├── lead-notification.php
│       └── listing-inquiry.php
│
├── vendor/                      # Composer dependencies
│   └── dompdf/                 # PDF generation library
│
├── wp-stubs.php                # WordPress function stubs for static analysis
├── .gitignore                  # Git ignore rules
├── composer.json               # Dependency management (dompdf, guzzle)
├── composer.lock
│
└── happy-place-plugin.php       # Main plugin file

