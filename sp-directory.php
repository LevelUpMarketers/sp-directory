<?php
/**
 * Plugin Name:       Codex Plugin Boilerplate
 * Plugin URI:        https://levelupmarketers.com
 * Description:       A boilerplate plugin demonstrating a modular, performant foundation for future development.
 * Version:           0.1.0
 * Author:            Codex
 * Author URI:        https://levelupmarketers.com
 * Text Domain:       codex-plugin-boilerplate
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Plugin constants.
define( 'CPB_VERSION', '0.1.0' );
define( 'CPB_MIN_EXECUTION_TIME', 4 );
define( 'CPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CPB_PLUGIN_DIR . 'includes/class-cpb-activator.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-deactivator.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-i18n.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-main-entity-helper.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-email-template-helper.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-email-log-helper.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-ajax.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-cron.php';
require_once CPB_PLUGIN_DIR . 'includes/admin/class-cpb-admin.php';
require_once CPB_PLUGIN_DIR . 'includes/shortcodes/class-cpb-shortcode-main-entity.php';
require_once CPB_PLUGIN_DIR . 'includes/blocks/class-cpb-block-main-entity.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-content-logger.php';
require_once CPB_PLUGIN_DIR . 'includes/class-cpb-plugin.php';

register_activation_hook( __FILE__, array( 'CPB_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CPB_Deactivator', 'deactivate' ) );

function run_cpb_plugin() {
    $plugin = new CPB_Plugin();
    $plugin->run();
}
run_cpb_plugin();
