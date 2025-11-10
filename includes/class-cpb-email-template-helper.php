<?php
/**
 * Helper utilities for managing email template defaults and storage.
 *
 * @package Codex_Plugin_Boilerplate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CPB_Email_Template_Helper {

    /**
     * Registered template labels for reuse across logging and UI output.
     *
     * @var array
     */
    protected static $template_labels = array();

    /**
     * Get the option name used to persist email templates.
     *
     * @return string
     */
    public static function get_option_name() {
        /**
         * Filter the option name used to store email template settings.
         *
         * @param string $option_name Default option name.
         */
        return apply_filters( 'cpb_email_templates_option_name', 'cpb_email_templates' );
    }

    /**
     * Retrieve stored template settings for the provided template identifier.
     *
     * @param string $template_id Template identifier.
     *
     * @return array
     */
    public static function get_template_settings( $template_id ) {
        $template_id = sanitize_key( $template_id );

        $defaults = self::get_default_settings();

        if ( '' === $template_id ) {
            return $defaults;
        }

        $stored = get_option( self::get_option_name(), array() );

        if ( ! is_array( $stored ) || ! isset( $stored[ $template_id ] ) || ! is_array( $stored[ $template_id ] ) ) {
            return $defaults;
        }

        $settings = wp_parse_args( $stored[ $template_id ], $defaults );

        foreach ( $settings as $key => $value ) {
            $settings[ $key ] = is_string( $value ) ? $value : '';
        }

        return $settings;
    }

    /**
     * Persist template settings for the provided template identifier.
     *
     * @param string $template_id Template identifier.
     * @param array  $settings    Template settings to store.
     *
     * @return bool
     */
    public static function update_template_settings( $template_id, array $settings ) {
        $template_id = sanitize_key( $template_id );

        if ( '' === $template_id ) {
            return false;
        }

        $existing = get_option( self::get_option_name(), array() );

        if ( ! is_array( $existing ) ) {
            $existing = array();
        }

        $defaults = self::get_default_settings();
        $settings = wp_parse_args( $settings, $defaults );

        foreach ( $settings as $key => $value ) {
            $settings[ $key ] = is_string( $value ) ? $value : '';
        }

        $existing[ $template_id ] = $settings;

        return update_option( self::get_option_name(), $existing );
    }

    /**
     * Default settings scaffold for template storage.
     *
     * @return array
     */
    public static function get_default_settings() {
        return array(
            'from_name'  => '',
            'from_email' => '',
            'subject'    => '',
            'body'       => '',
            'sms'        => '',
        );
    }

    /**
     * Sanitize a From name field.
     *
     * @param string $value Raw value.
     *
     * @return string
     */
    public static function sanitize_from_name( $value ) {
        $value = is_string( $value ) ? $value : '';
        $value = wp_strip_all_tags( $value );
        $value = preg_replace( '/[\r\n]+/', ' ', $value );
        $value = trim( $value );

        /**
         * Filter the sanitized From name used for outgoing email.
         *
         * @param string $value Sanitized value.
         */
        return apply_filters( 'cpb_email_template_from_name', $value );
    }

    /**
     * Sanitize a From email field.
     *
     * @param string $value Raw value.
     *
     * @return string
     */
    public static function sanitize_from_email( $value ) {
        $value = is_string( $value ) ? $value : '';
        $value = sanitize_email( $value );

        /**
         * Filter the sanitized From email used for outgoing email.
         *
         * @param string $value Sanitized value.
         */
        return apply_filters( 'cpb_email_template_from_email', $value );
    }

    /**
     * Retrieve the default From name.
     *
     * @return string
     */
    public static function get_default_from_name() {
        $site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $site_name = trim( $site_name );

        if ( '' === $site_name ) {
            $site_name = 'WordPress';
        }

        $site_name = self::sanitize_from_name( $site_name );

        /**
         * Filter the default From name used when an administrator does not supply one.
         *
         * @param string $site_name Default From name.
         */
        return apply_filters( 'cpb_default_from_name', $site_name );
    }

    /**
     * Retrieve the default From email address.
     *
     * @return string
     */
    public static function get_default_from_email() {
        $host = self::get_site_host();

        if ( '' === $host ) {
            $host = 'example.com';
        }

        $email = 'noreply@' . $host;
        $email = self::sanitize_from_email( $email );

        if ( '' === $email ) {
            $email = 'noreply@example.com';
        }

        /**
         * Filter the default From email used when an administrator does not supply one.
         *
         * @param string $email Default From email address.
         */
        return apply_filters( 'cpb_default_from_email', $email );
    }

    /**
     * Resolve the From name ensuring a fallback is returned.
     *
     * @param string $value Potential From name.
     *
     * @return string
     */
    public static function resolve_from_name( $value ) {
        $value = self::sanitize_from_name( $value );

        if ( '' === $value ) {
            $value = self::get_default_from_name();
        }

        return $value;
    }

    /**
     * Resolve the From email ensuring a fallback is returned.
     *
     * @param string $value Potential From email.
     *
     * @return string
     */
    public static function resolve_from_email( $value ) {
        $value = self::sanitize_from_email( $value );

        if ( '' === $value ) {
            $value = self::get_default_from_email();
        }

        return $value;
    }

    /**
     * Prepare the From header string for wp_mail.
     *
     * @param string $name  Display name.
     * @param string $email Email address.
     *
     * @return string
     */
    public static function build_from_header( $name, $email ) {
        $email = self::resolve_from_email( $email );
        $name  = self::sanitize_from_name( $name );

        if ( '' === $email ) {
            return '';
        }

        if ( '' === $name ) {
            return 'From: ' . $email;
        }

        $quoted_name = addcslashes( $name, "\\\"");

        return sprintf( 'From: "%s" <%s>', $quoted_name, $email );
    }

    /**
     * Determine the current site host for generating defaults.
     *
     * @return string
     */
    private static function get_site_host() {
        $urls = array( home_url(), site_url() );

        foreach ( $urls as $url ) {
            $parts = wp_parse_url( $url );

            if ( empty( $parts['host'] ) ) {
                continue;
            }

            $host = strtolower( $parts['host'] );
            $host = preg_replace( '/[^a-z0-9\.-]+/i', '', $host );
            $host = trim( $host );

            if ( '' !== $host ) {
                return $host;
            }
        }

        return '';
    }

    /**
     * Register a human-friendly label for the provided template identifier.
     *
     * @param string $template_id Template identifier.
     * @param string $label       Display label.
     */
    public static function register_template_label( $template_id, $label ) {
        $template_id = sanitize_key( $template_id );

        if ( '' === $template_id ) {
            return;
        }

        $label = is_string( $label ) ? trim( wp_strip_all_tags( $label ) ) : '';

        if ( '' === $label ) {
            return;
        }

        self::$template_labels[ $template_id ] = $label;
    }

    /**
     * Resolve a template label for logging and display contexts.
     *
     * @param string $template_id Template identifier.
     *
     * @return string
     */
    public static function get_template_label( $template_id ) {
        $template_id = sanitize_key( $template_id );

        if ( '' === $template_id ) {
            return '';
        }

        if ( isset( self::$template_labels[ $template_id ] ) ) {
            return self::$template_labels[ $template_id ];
        }

        $label = apply_filters( 'cpb_email_template_label', '', $template_id );

        if ( '' === $label ) {
            $label = preg_replace( '/^cpb[-_]/', '', $template_id );
            $label = str_replace( array( '-', '_' ), ' ', $label );
            $label = ucwords( $label );
        }

        $label = is_string( $label ) ? trim( wp_strip_all_tags( $label ) ) : '';

        if ( '' === $label ) {
            $label = $template_id;
        }

        self::$template_labels[ $template_id ] = $label;

        return $label;
    }
}
