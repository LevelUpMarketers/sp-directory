<?php
/**
 * Fired during plugin deactivation
 *
 * @package SuperDirectory
 */

class SD_Deactivator {

    public static function deactivate() {
        $cron_array = _get_cron_array();

        if ( empty( $cron_array ) || ! is_array( $cron_array ) ) {
            return;
        }

        foreach ( $cron_array as $timestamp => $hooks ) {
            foreach ( $hooks as $hook => $instances ) {
                if ( 0 !== strpos( $hook, SD_Cron_Manager::HOOK_PREFIX ) ) {
                    continue;
                }

                foreach ( $instances as $instance ) {
                    $args = isset( $instance['args'] ) ? (array) $instance['args'] : array();
                    wp_unschedule_event( $timestamp, $hook, $args );
                }
            }
        }

        delete_option( 'sd_demo_cron_last_run' );
    }
}
