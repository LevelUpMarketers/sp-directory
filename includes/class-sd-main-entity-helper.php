<?php
/**
 * Shared helper methods for working with Directory Listing data.
 *
 * @package SuperDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SD_Main_Entity_Helper {

    /**
     * Retrieve the first Directory Listing record prepared for template previews.
     *
     * @return array
     */
    public static function get_first_preview_data() {
        static $preview_data = null;

        if ( null !== $preview_data ) {
            return $preview_data;
        }

        $table_name = self::get_main_table_name();

        if ( ! self::table_exists( $table_name ) ) {
            $preview_data = array();
            return $preview_data;
        }

        global $wpdb;

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
     * Retrieve a Directory Listing for use on the public template.
     *
     * @param int $entity_id Entity identifier stored on the generated page.
     *
     * @return array
     */
    public static function get_entity_for_template( $entity_id ) {
        $entity_id = absint( $entity_id );

        if ( ! $entity_id ) {
            return array();
        }

        $table_name = self::get_main_table_name();

        if ( ! self::table_exists( $table_name ) ) {
            return array();
        }

        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $entity_id ),
            ARRAY_A
        );

        if ( ! $row ) {
            return array();
        }

        return self::prepare_template_entity( $row );
    }

    /**
     * Retrieve the stored logo attachment identifier for an entity.
     *
     * Provides a lightweight way for templates to refetch the attachment ID
     * in case cached entity payloads have not yet been refreshed with the
     * latest schema changes.
     *
     * @param int $entity_id Directory Listing identifier.
     *
     * @return int
     */
    public static function get_logo_attachment_id( $entity_id ) {
        $entity_id = absint( $entity_id );

        if ( ! $entity_id ) {
            return 0;
        }

        $table_name = self::get_main_table_name();

        if ( ! self::table_exists( $table_name ) ) {
            return 0;
        }

        global $wpdb;

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT logo_attachment_id FROM $table_name WHERE id = %d",
                $entity_id
            )
        );

        return $value ? absint( $value ) : 0;
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

        if ( in_array( $key, array( 'long_description_primary', 'long_description_secondary' ), true ) ) {
            return wp_kses_post( (string) $value );
        }

        if ( is_scalar( $value ) ) {
            $string_value = (string) $value;

            return wp_kses_post( $string_value );
        }

        return '';
    }

    /**
     * Prepare stored data for template output.
     *
     * @param array $row Raw database row.
     *
     * @return array
     */
    private static function prepare_template_entity( array $row ) {
        $prepared = array();

        $text_fields = array(
            'name',
            'category',
            'industry_vertical',
            'service_model',
            'state',
            'city',
            'street_address',
            'zip_code',
            'country',
        );

        foreach ( $text_fields as $field ) {
            $prepared[ $field ] = isset( $row[ $field ] ) ? sanitize_text_field( (string) $row[ $field ] ) : '';
        }

        $prepared['phone_number']  = isset( $row['phone_number'] ) ? sanitize_text_field( (string) $row['phone_number'] ) : '';
        $prepared['email_address'] = isset( $row['email_address'] ) ? sanitize_email( $row['email_address'] ) : '';
        $prepared['website_url']       = isset( $row['website_url'] ) ? esc_url_raw( $row['website_url'] ) : '';
        $prepared['facebook_url']      = isset( $row['facebook_url'] ) ? esc_url_raw( $row['facebook_url'] ) : '';
        $prepared['instagram_url']     = isset( $row['instagram_url'] ) ? esc_url_raw( $row['instagram_url'] ) : '';
        $prepared['youtube_url']       = isset( $row['youtube_url'] ) ? esc_url_raw( $row['youtube_url'] ) : '';
        $prepared['linkedin_url']      = isset( $row['linkedin_url'] ) ? esc_url_raw( $row['linkedin_url'] ) : '';
        $prepared['google_business_url'] = isset( $row['google_business_url'] ) ? esc_url_raw( $row['google_business_url'] ) : '';

        $prepared['short_description']        = isset( $row['short_description'] ) ? wp_kses_post( (string) $row['short_description'] ) : '';
        $prepared['long_description_primary'] = isset( $row['long_description_primary'] ) ? wp_kses_post( (string) $row['long_description_primary'] ) : '';
        $prepared['long_description_secondary'] = isset( $row['long_description_secondary'] ) ? wp_kses_post( (string) $row['long_description_secondary'] ) : '';

        $prepared['logo_attachment_id'] = isset( $row['logo_attachment_id'] ) ? absint( $row['logo_attachment_id'] ) : 0;
        $prepared['gallery_image_ids']  = isset( $row['gallery_image_ids'] ) ? self::parse_gallery_ids( $row['gallery_image_ids'] ) : array();

        $prepared['id']                = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
        $prepared['directory_page_id'] = isset( $row['directory_page_id'] ) ? absint( $row['directory_page_id'] ) : 0;

        return $prepared;
    }

    /**
     * Retrieve the SuperDirectory listings table name.
     *
     * @return string
     */
    private static function get_main_table_name() {
        global $wpdb;

        return $wpdb->prefix . 'sd_main_entity';
    }

    /**
     * Determine whether the listings table exists.
     *
     * @param string $table_name Table name to validate.
     *
     * @return bool
     */
    private static function table_exists( $table_name ) {
        global $wpdb;

        $like  = $wpdb->esc_like( $table_name );
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );

        return ( $found === $table_name );
    }

    private static function parse_gallery_ids( $value ) {
        if ( empty( $value ) ) {
            return array();
        }

        $ids = is_array( $value ) ? $value : explode( ',', (string) $value );

        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );

        return array_values( array_unique( $ids ) );
    }
}
