# SuperDirectory

SuperDirectory is a WordPress plugin that streamlines the process of collecting and publishing Home Services industry resources. Administrators can curate a catalogue of companies, tools, and service providers from a single dashboard and later expose those records as public-facing directory listings.

## Features
- Guided admin experience with tabbed navigation for adding and editing directory listings.
- Placeholder-driven data model that demonstrates how to capture addresses, contact information, marketing preferences, and service details.
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

## Roadmap
- Automate WordPress page generation for each directory listing.
- Deliver a searchable and filterable front-end directory archive.
- Replace placeholder form fields with production-ready Home Services data requirements.

## License
Distributed under the same license as WordPress. See the plugin header for details.
