<?php
/**
 * Define the internationalization functionality
 *
 * @package SuperDirectory
 */

class SD_I18n {

    public function load_textdomain() {
        load_plugin_textdomain(
            'super-directory',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}
