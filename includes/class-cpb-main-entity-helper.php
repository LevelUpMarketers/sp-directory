<?php
/**
 * Shared helper methods for working with Main Entity data.
 *
 * @package Codex_Plugin_Boilerplate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CPB_Main_Entity_Helper {

    /**
     * Retrieve the first Main Entity record prepared for template previews.
     *
     * @return array
     */
    public static function get_first_preview_data() {
        static $preview_data = null;

        if ( null !== $preview_data ) {
            return $preview_data;
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'cpb_main_entity';
        $like       = $wpdb->esc_like( $table_name );
        $found      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );

        if ( $found !== $table_name ) {
            $preview_data = array();
            return $preview_data;
        }

        $row = $wpdb->get_row( "SELECT * FROM $table_name ORDER BY id ASC LIMIT 1", ARRAY_A );

        if ( ! $row ) {
            $preview_data = array();
            return $preview_data;
        }

        $prepared = array();

        foreach ( $row as $key => $value ) {
            $prepared[ $key ] = self::normalize_preview_token_value( $key, $value );
        }

        $preview_data = $prepared;

        return $preview_data;
    }

    /**
     * Normalize a stored value so it can be injected into a template token.
     *
     * @param string $key   Database column key.
     * @param mixed  $value Stored value.
     *
     * @return string
     */
    private static function normalize_preview_token_value( $key, $value ) {
        if ( null === $value ) {
            return '';
        }

        if ( 'placeholder_3' === $key ) {
            $value = (string) $value;

            if ( '' === $value || '0000-00-00' === $value ) {
                return '';
            }

            $date = date_create( $value );

            return $date ? $date->format( 'Y-m-d' ) : '';
        }

        if ( in_array( $key, array( 'placeholder_5', 'placeholder_6' ), true ) ) {
            $value = (string) $value;

            if ( preg_match( '/^(\d{2}:\d{2})/', $value, $matches ) ) {
                return $matches[1];
            }

            return '';
        }

        if ( in_array( $key, array( 'placeholder_16', 'placeholder_17', 'placeholder_18' ), true ) ) {
            return number_format( (float) $value, 2, '.', '' );
        }

        if ( in_array( $key, array( 'placeholder_24', 'placeholder_25' ), true ) ) {
            if ( is_array( $value ) ) {
                $items = $value;
            } else {
                $decoded = json_decode( (string) $value, true );
                $items   = is_array( $decoded ) ? $decoded : array();
            }

            if ( empty( $items ) ) {
                return '';
            }

            $items = array_map( 'strval', $items );
            $items = array_map( 'wp_kses_post', $items );
            $items = array_filter( $items, 'strlen' );

            return implode( ', ', $items );
        }

        if ( 'placeholder_26' === $key ) {
            $color = sanitize_hex_color( (string) $value );
            return $color ? $color : '';
        }

        if ( 'placeholder_27' === $key ) {
            $attachment_id = absint( $value );
            $url           = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';

            return $url ? esc_url_raw( $url ) : '';
        }

        if ( 'placeholder_28' === $key ) {
            return wp_kses_post( (string) $value );
        }

        if ( 'resource_logo_id' === $key ) {
            $attachment_id = absint( $value );
            $url           = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';

            return $url ? esc_url_raw( $url ) : '';
        }

        if ( 'resource_gallery_ids' === $key ) {
            if ( is_array( $value ) ) {
                $ids = $value;
            } else {
                $decoded = json_decode( (string) $value, true );
                $ids     = is_array( $decoded ) ? $decoded : array();
            }

            $ids  = array_map( 'absint', $ids );
            $ids  = array_filter( $ids );
            $urls = array();

            foreach ( $ids as $id ) {
                $url = wp_get_attachment_url( $id );

                if ( $url ) {
                    $urls[] = esc_url_raw( $url );
                }
            }

            return implode( ', ', $urls );
        }

        if ( is_scalar( $value ) ) {
            $string_value = (string) $value;

            return wp_kses_post( $string_value );
        }

        return '';
    }
}
