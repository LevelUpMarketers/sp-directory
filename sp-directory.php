<?php
/**
 * Plugin Name:       SuperDirectory
 * Plugin URI:        https://levelupmarketers.com
 * Description:       Administrative toolkit for managing Home Services industry resources and generating a public directory.
 * Version:           0.1.0
 * Author:            Level Up Marketers
 * Author URI:        https://levelupmarketers.com
 * Text Domain:       super-directory
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Plugin constants.
define( 'SD_VERSION', '0.1.0' );
define( 'SD_MIN_EXECUTION_TIME', 4 );
define( 'SD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SD_DIRECTORY_TEMPLATE_SLUG', 'templates/sd-directory-entry.php' );
define( 'SD_DIRECTORY_TEMPLATE_LEGACY_SLUG', 'sd-directory-entry.php' );
define( 'SD_DIRECTORY_PARENT_TEMPLATE_SLUG', 'templates/sd-directory-parent.php' );

require_once SD_PLUGIN_DIR . 'includes/class-sd-activator.php';
require_once SD_PLUGIN_DIR . 'includes/class-sd-deactivator.php';
require_once SD_PLUGIN_DIR . 'includes/class-sd-i18n.php';
require_once SD_PLUGIN_DIR . 'includes/class-sd-main-entity-helper.php';
require_once SD_PLUGIN_DIR . 'includes/class-sd-ajax.php';
require_once SD_PLUGIN_DIR . 'includes/admin/class-sd-admin.php';
require_once SD_PLUGIN_DIR . 'includes/shortcodes/class-sd-shortcode-main-entity.php';
require_once SD_PLUGIN_DIR . 'includes/blocks/class-sd-block-main-entity.php';
require_once SD_PLUGIN_DIR . 'includes/class-sd-content-logger.php';
require_once SD_PLUGIN_DIR . 'includes/class-sd-template-loader.php';
require_once SD_PLUGIN_DIR . 'includes/class-sd-plugin.php';

register_activation_hook( __FILE__, array( 'SD_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SD_Deactivator', 'deactivate' ) );

function run_sd_plugin() {
    $plugin = new SD_Plugin();
    $plugin->run();
}
run_sd_plugin();
