# SuperDirectory

SuperDirectory is a WordPress plugin that streamlines the process of collecting and publishing Home Services industry resources. Administrators can curate a catalogue of companies, tools, and service providers from a single dashboard and later expose those records as public-facing directory listings.

## Features
- Guided admin experience with tabbed navigation for adding and editing directory listings.
- Opinionated data model that captures business identifiers, contact channels, coverage areas, and marketing narratives tailored to Home Services listings.
- Automatic creation of dedicated WordPress pages for each listing using the bundled SuperDirectory template and layout.
- Responsive front-end styling that highlights each listing's overview, contact channels, address, and social profiles.
- Log management tools that surface generated content history for quick auditing and cleanup.
- Gutenberg block and shortcode for rendering directory listings on the front end.
- Bulk CSV/TSV importer on the SuperDirectory Settings page for loading multiple resources in one upload.

## Installation
1. Upload the `sp-directory` folder to the `/wp-content/plugins/` directory or install via the WordPress admin dashboard.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **SuperDirectory Directory** in the admin menu to begin adding listings.

## Development
- Run `find . -name "*.php" -not -path "*/vendor/*" -print0 | xargs -0 -n1 php -l` to verify PHP syntax.
- JavaScript and CSS assets live under `assets/` and are prepped for modular enhancements.
- All user-facing strings leverage the `super-directory` text domain; update `languages/super-directory.pot` when adding translations.

## Bulk upload

Use **SuperDirectory Settings → General Settings** to upload a CSV or TSV file that follows the expected column order. The importer maps your spreadsheet to the directory table and creates individual listing pages automatically. Each row should supply the following headings in order:

1. Resource/Company/Vendor Name
2. Category
3. Website URL
4. Phone
5. Email
6. Related Trade/Industry/Vertical
7. Serving Only Local Customers, Virtual/National, or Both?
8. State
9. City
10. Street Address
11. Zip Code
12. Short description/lead-in teaser sentence about the company (98 characters max)
13. What [COMPANY NAME] Does (770 characters max)
14. Why We Recommend [COMPANY NAME] (770 characters max)
15. Facebook URL
16. Instagram URL
17. YouTube URL
18. LinkedIn URL
19. Google Business Listing URL
20. Logo WordPress media library attachment ID #
21. Homepage Screenshot WordPress media library attachment ID #

## Directory entry fields

SuperDirectory groups the directory authoring experience into clear sections so administrators can capture consistent, high-value information for each listing:

### Listing basics
- Resource Name
- Category
- Industry
- Local, National, Both?

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
- What This Resource Does
- Why We Recommend This Resource

### Social & listings
- Facebook URL
- Instagram URL
- YouTube URL
- LinkedIn URL
- GBP URL

## Front-end output

Every time a listing is saved, SuperDirectory ensures its generated page uses the plugin's template. The template automatically
renders the listing's descriptions, location, contact links, and social profiles while keeping any additional page content you
publish beneath the structured layout. Styles specific to the template ship with the plugin, so the page looks polished without
requiring theme overrides.

## Roadmap
- Expand the listing template with richer media support and testimonials sourced from future data fields.
- Deliver a searchable and filterable front-end directory archive.
- Introduce import and synchronization tools for populating listings from external CRMs.

## License
Distributed under the same license as WordPress. See the plugin header for details.
