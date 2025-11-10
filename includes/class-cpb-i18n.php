<?php
/**
 * Define the internationalization functionality
 *
 * @package Codex_Plugin_Boilerplate
 */

class CPB_I18n {

    public function load_textdomain() {
        load_plugin_textdomain(
            'codex-plugin-boilerplate',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}
