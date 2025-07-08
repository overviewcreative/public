# 🎨 Happy Place Theme Documentation

## File Structure

```
happy-place-theme/
│
├── templates/                   # Theme templates
│   ├── listing/                # Listing templates
│   │   ├── archive-listing.php
│   │   └── single-listing.php
│   │
│   ├── agent/                  # Agent templates
│   │   ├── archive-agent.php
│   │   └── single-agent.php
│   │
│   ├── community/              # Community templates
│   │   ├── archive-community.php
│   │   └── single-community.php
│   │
│   ├── city/                   # City templates
│   │   ├── archive-city.php
│   │   └── single-city.php
│   │
│   └── partials/              # Reusable template parts
│       ├── header.php
│       ├── footer.php
│       ├── search-form.php
│       ├── property-filters.php
│       ├── pagination.php
│       ├── no-results.php
│       ├── content-header.php
│       ├── card-listing.php
│       ├── agent-contact-card.php
│       └── dashboard/          # Dashboard template parts
│           ├── overview.php
│           ├── favorites.php
│           ├── saved-searches.php
│           └── profile.php
│
├── page-templates/            # Custom page templates
│   ├── page-dashboard.php
│   ├── page-search.php
│   └── page-contact.php
│
├── assets/                    # Theme assets
│   ├── css/
│   │   ├── core/
│   │   │   ├── reset.css          # CSS reset and normalization
│   │   │   ├── variables.css      # Design tokens and custom properties
│   │   │   └── typography.css     # Base typography styles
│   │   │
│   │   ├── components/
│   │   │   ├── buttons.css        # Button variations
│   │   │   ├── forms.css         # Form element styles
│   │   │   ├── cards.css         # Card component styles
│   │   │   ├── navigation.css    # Navigation styles
│   │   │   └── modals.css        # Modal and overlay styles
│   │   │
│   │   ├── layout/
│   │   │   └── grid.css          # Grid system
│   │   │
│   │   ├── templates/
│   │   │   ├── dashboard.css     # Dashboard page styles
│   │   │   ├── search.css       # Search page styles
│   │   │   └── contact.css      # Contact page styles
│   │   │
│   │   ├── core.css             # Core styles (imports)
│   │   ├── listing.css          # Listing-specific styles
│   │   └── theme.css            # Theme-wide styles
│   │
│   └── js/                     # JavaScript files
│       ├── core.js
│       └── theme.js
│
├── functions.php              # Theme initialization
└── style.css                 # Theme metadata
```
        │   ├── listing.css             # Listing-specific styles
        │   ├── agent.css               # Agent page styles
        │   ├── dashboard.css           # User dashboard styles
        │   └── search.css              # Search results styles
        │
        └── theme.css                  # Main stylesheet that imports all others