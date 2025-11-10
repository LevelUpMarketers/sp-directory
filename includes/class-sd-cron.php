<?php
/**
 * Cron management utilities for SuperDirectory.
 *
 * @package SuperDirectory
 */

class SD_Cron_Manager {

    const HOOK_PREFIX = 'sd_';
    const DEMO_HOOK   = 'sd_demo_cron_event';

    /**
     * Bootstraps the cron manager.
     */
    public function register() {
        add_action( 'init', array( $this, 'maintain_demo_event' ) );
        add_action( self::DEMO_HOOK, array( $this, 'handle_demo_event' ) );
    }

    /**
     * Ensures the demo cron event remains scheduled without duplicating entries.
     */
    public function maintain_demo_event() {
        $args = array( 'demo' => true );

        $has_valid_event = $this->prune_demo_event_duplicates( $args );

        if ( function_exists( 'wp_get_scheduled_event' ) ) {
            $event = wp_get_scheduled_event( self::DEMO_HOOK, $args );

            if ( $event && ! empty( $event->schedule ) ) {
                wp_unschedule_event( $event->timestamp, self::DEMO_HOOK, $args );
                $event           = false;
                $has_valid_event = false;
            } else {
                $has_valid_event = (bool) $event;
            }
        }

        if ( ! $has_valid_event ) {
            $this->schedule_demo_event( $args );
        }
    }

    /**
     * Removes duplicate demo events so only one sample cron remains.
     *
     * @param array $args Demo event arguments.
     *
     * @return bool Whether a valid demo event remains scheduled.
     */
    private function prune_demo_event_duplicates( $args ) {
        $cron_array = _get_cron_array();

        if ( empty( $cron_array ) || ! is_array( $cron_array ) ) {
            return false;
        }

        $events = array();

        foreach ( $cron_array as $timestamp => $hooks ) {
            if ( empty( $hooks[ self::DEMO_HOOK ] ) ) {
                continue;
            }

            foreach ( $hooks[ self::DEMO_HOOK ] as $instance ) {
                $event_args = isset( $instance['args'] ) ? (array) $instance['args'] : array();

                $events[] = array(
                    'timestamp' => (int) $timestamp,
                    'args'      => $event_args,
                    'schedule'  => isset( $instance['schedule'] ) ? $instance['schedule'] : '',
                );
            }
        }

        if ( empty( $events ) ) {
            return false;
        }

        usort(
            $events,
            function ( $a, $b ) {
                if ( $a['timestamp'] === $b['timestamp'] ) {
                    return 0;
                }

                return ( $a['timestamp'] < $b['timestamp'] ) ? -1 : 1;
            }
        );

        $keep = array_shift( $events );

        foreach ( $events as $event ) {
            wp_unschedule_event( $event['timestamp'], self::DEMO_HOOK, $event['args'] );
        }

        $has_valid_event = true;

        if ( $keep['timestamp'] < time() ) {
            wp_unschedule_event( $keep['timestamp'], self::DEMO_HOOK, $keep['args'] );
            $has_valid_event = false;
        } elseif ( ! empty( $keep['schedule'] ) ) {
            wp_unschedule_event( $keep['timestamp'], self::DEMO_HOOK, $keep['args'] );
            $has_valid_event = false;
        }

        return $has_valid_event;
    }

    /**
     * Schedules the demo event approximately six months in the future.
     *
     * @param array $args Demo event arguments.
     */
    private function schedule_demo_event( $args ) {
        $timestamp = time() + ( 6 * MONTH_IN_SECONDS );

        wp_schedule_single_event( $timestamp, self::DEMO_HOOK, $args );
    }

    /**
     * Handles the demo cron event when it runs.
     *
     * @param array $args Event arguments.
     */
    public function handle_demo_event( $args = array() ) {
        update_option(
            'sd_demo_cron_last_run',
            array(
                'timestamp' => current_time( 'timestamp' ),
                'args'      => $args,
            )
        );
    }

    /**
     * Returns cron events created by the plugin.
     *
     * @return array
     */
    public static function get_plugin_cron_events() {
        $cron_array = _get_cron_array();
        $events     = array();

        if ( empty( $cron_array ) || ! is_array( $cron_array ) ) {
            return $events;
        }

        foreach ( $cron_array as $timestamp => $hooks ) {
            foreach ( $hooks as $hook => $instances ) {
                if ( 0 !== strpos( $hook, self::HOOK_PREFIX ) ) {
                    continue;
                }

                foreach ( $instances as $signature => $data ) {
                    $events[] = array(
                        'hook'      => $hook,
                        'timestamp' => (int) $timestamp,
                        'schedule'  => isset( $data['schedule'] ) ? $data['schedule'] : '',
                        'interval'  => isset( $data['interval'] ) ? (int) $data['interval'] : 0,
                        'args'      => isset( $data['args'] ) ? (array) $data['args'] : array(),
                        'signature' => $signature,
                    );
                }
            }
        }

        usort(
            $events,
            function ( $a, $b ) {
                if ( $a['timestamp'] === $b['timestamp'] ) {
                    return strcmp( $a['hook'], $b['hook'] );
                }

                return ( $a['timestamp'] < $b['timestamp'] ) ? -1 : 1;
            }
        );

        return $events;
    }

    /**
     * Provides metadata for known cron hooks.
     *
     * @return array
     */
    public static function get_known_hooks() {
        return array(
            self::DEMO_HOOK => array(
                'name'        => __( 'Demo Cron Event', 'super-directory' ),
                'description' => __( 'Demonstrates how SuperDirectory cron jobs appear in the Cron Jobs tab.', 'super-directory' ),
            ),
        );
    }

    /**
     * Retrieves display data for a cron hook.
     *
     * @param string $hook Hook name.
     *
     * @return array
     */
    public static function get_hook_display_data( $hook ) {
        $known = self::get_known_hooks();

        if ( isset( $known[ $hook ] ) ) {
            return $known[ $hook ];
        }

        $readable = trim( str_replace( array( '_', '-' ), ' ', $hook ) );
        $readable = preg_replace( '/^' . preg_quote( self::HOOK_PREFIX, '/' ) . '/i', '', $readable );
        $readable = ucwords( $readable );

        return array(
            'name'        => $readable,
            'description' => __( 'Cron event scheduled by SuperDirectory.', 'super-directory' ),
        );
    }

    /**
     * Formats a timestamp for display.
     *
     * @param int $timestamp Unix timestamp.
     *
     * @return string
     */
    public static function format_timestamp( $timestamp ) {
        if ( empty( $timestamp ) ) {
            return __( 'Not scheduled', 'super-directory' );
        }

        return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
    }

    /**
     * Determines if the cron event is recurring.
     *
     * @param string $schedule Schedule slug.
     *
     * @return bool
     */
    public static function is_recurring( $schedule ) {
        return ! empty( $schedule );
    }

    /**
     * Returns the schedule label for the cron event.
     *
     * @param string $schedule Schedule slug.
     * @param int    $interval Interval in seconds.
     *
     * @return string
     */
    public static function get_schedule_label( $schedule, $interval ) {
        if ( empty( $schedule ) ) {
            return __( 'One-off event', 'super-directory' );
        }

        $schedules = wp_get_schedules();

        if ( isset( $schedules[ $schedule ]['display'] ) ) {
            return $schedules[ $schedule ]['display'];
        }

        if ( $interval > 0 ) {
            return sprintf(
                /* translators: %s: number of seconds */
                __( 'Custom schedule (%s seconds)', 'super-directory' ),
                number_format_i18n( $interval )
            );
        }

        return __( 'Recurring event', 'super-directory' );
    }

    /**
     * Creates a human-friendly countdown string for a timestamp.
     *
     * @param int $timestamp Unix timestamp.
     *
     * @return string
     */
    public static function get_countdown( $timestamp ) {
        if ( empty( $timestamp ) ) {
            return __( 'Not scheduled', 'super-directory' );
        }

        $now = current_time( 'timestamp' );
        $diff = $timestamp - $now;

        if ( 0 === $diff ) {
            return __( 'Running now', 'super-directory' );
        }

        $direction = $diff > 0 ? 'until' : 'ago';
        $diff      = abs( $diff );

        if ( 0 === $diff ) {
            return __( 'Due now', 'super-directory' );
        }

        $units = array(
            array( 'label' => _n_noop( '%s week', '%s weeks', 'super-directory' ), 'seconds' => WEEK_IN_SECONDS ),
            array( 'label' => _n_noop( '%s day', '%s days', 'super-directory' ), 'seconds' => DAY_IN_SECONDS ),
            array( 'label' => _n_noop( '%s hour', '%s hours', 'super-directory' ), 'seconds' => HOUR_IN_SECONDS ),
            array( 'label' => _n_noop( '%s minute', '%s minutes', 'super-directory' ), 'seconds' => MINUTE_IN_SECONDS ),
            array( 'label' => _n_noop( '%s second', '%s seconds', 'super-directory' ), 'seconds' => 1 ),
        );

        $parts = array();

        foreach ( $units as $unit ) {
            if ( $diff < $unit['seconds'] ) {
                continue;
            }

            $value = floor( $diff / $unit['seconds'] );
            $diff -= $value * $unit['seconds'];

            if ( $value > 0 ) {
                $parts[] = sprintf( translate_nooped_plural( $unit['label'], $value, 'super-directory' ), number_format_i18n( $value ) );
            }

            if ( count( $parts ) >= 3 ) {
                break;
            }
        }

        if ( empty( $parts ) ) {
            $parts[] = sprintf( _n( '%s second', '%s seconds', $diff, 'super-directory' ), number_format_i18n( max( 1, $diff ) ) );
        }

        $glue      = _x( ', ', 'Countdown delimiter', 'super-directory' );
        $countdown = implode( $glue, $parts );

        if ( 'until' === $direction ) {
            return sprintf(
                /* translators: %s: countdown string */
                __( 'In %s', 'super-directory' ),
                $countdown
            );
        }

        return sprintf(
            /* translators: %s: countdown string */
            __( '%s ago', 'super-directory' ),
            $countdown
        );
    }
}
