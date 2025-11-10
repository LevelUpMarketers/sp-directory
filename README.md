# SuperDirectory

SuperDirectory is a WordPress plugin that streamlines the process of collecting and publishing Home Services industry resources. Administrators can curate a catalogue of companies, tools, and service providers from a single dashboard and later expose those records as public-facing directory listings.

## Features
- Guided admin experience with tabbed navigation for adding and editing directory listings.
- Opinionated data model that captures business identifiers, contact channels, coverage areas, and marketing narratives tailored to Home Services listings.
- Automatic creation of dedicated WordPress pages for each listing using the bundled SuperDirectory template.
- Log management tools that surface generated content history for quick auditing and cleanup.
- Gutenberg block and shortcode for rendering directory listings on the front end.

## Installation
1. Upload the `sp-directory` folder to the `/wp-content/plugins/` directory or install via the WordPress admin dashboard.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **SuperDirectory Directory** in the admin menu to begin adding listings.

## Development
- Run `find . -name "*.php" -not -path "*/vendor/*" -print0 | xargs -0 -n1 php -l` to verify PHP syntax.
- JavaScript and CSS assets live under `assets/` and are prepped for modular enhancements.
- All user-facing strings leverage the `super-directory` text domain; update `languages/super-directory.pot` when adding translations.

## Directory entry fields

SuperDirectory groups the directory authoring experience into clear sections so administrators can capture consistent, high-value information for each listing:

### Listing basics
- Resource / Company / Vendor Name
- Category
- Related Industry / Vertical
- Serving Only Local Customers, Virtual/National, or Both?

### Contact & web presence
- Website URL
- Phone Number
- Email

### Location & coverage
- Street Address
- City
- State
- Zip Code
- Country

### Descriptions & messaging
- Short Description
- Long Description 1
- Long Description 2

### Social & listings
- Facebook URL
- Instagram URL
- YouTube URL
- LinkedIn URL
- Google Business Listing URL

## Roadmap
- Build dynamic front-end templates that render full directory listing details on the generated pages.
- Deliver a searchable and filterable front-end directory archive.
- Introduce import and synchronization tools for populating listings from external CRMs.

## License
Distributed under the same license as WordPress. See the plugin header for details.
