<?php
/**
 * Manage deep link records for the directory.
 *
 * @package SuperDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SD_Deep_Link_Manager {

    /**
     * Get the deep link table name.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;

        return $wpdb->prefix . 'sd_deep_links';
    }

    /**
     * Record links for a given category/industry combination.
     *
     * @param string $category Category slug.
     * @param string $industry Industry slug.
     */
    public static function record_links_for_values( $category = '', $industry = '' ) {
        $category = sanitize_key( $category );
        $industry = sanitize_key( $industry );

        if ( '' !== $category ) {
            self::upsert_link( $category, '' );
        }

        if ( '' !== $industry ) {
            self::upsert_link( '', $industry );
        }

        if ( '' !== $category && '' !== $industry ) {
            self::upsert_link( $category, $industry );
        }
    }

    /**
     * Retrieve all stored deep links.
     *
     * @return array
     */
    public static function get_links() {
        $table_name = self::get_table_name();

        if ( ! self::table_exists( $table_name ) ) {
            return array();
        }

        global $wpdb;

        $results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY category ASC, industry ASC", ARRAY_A );

        if ( empty( $results ) ) {
            return array();
        }

        $links = array();

        foreach ( $results as $row ) {
            $links[] = array(
                'category' => isset( $row['category'] ) ? sanitize_key( $row['category'] ) : '',
                'industry' => isset( $row['industry'] ) ? sanitize_key( $row['industry'] ) : '',
                'url'      => isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '',
            );
        }

        return $links;
    }

    /**
     * Build a directory deep link.
     *
     * @param string $category Category slug.
     * @param string $industry Industry slug.
     *
     * @return string
     */
    public static function build_url( $category = '', $industry = '' ) {
        $base_url = self::get_directory_base_url();
        $args     = array();

        if ( '' !== $category ) {
            $args['category'] = sanitize_key( $category );
        }

        if ( '' !== $industry ) {
            $args['industry'] = sanitize_key( $industry );
        }

        if ( empty( $args ) ) {
            return esc_url_raw( $base_url );
        }

        return esc_url_raw( add_query_arg( $args, $base_url ) );
    }

    /**
     * Insert or update a deep link record.
     *
     * @param string $category Category slug.
     * @param string $industry Industry slug.
     */
    private static function upsert_link( $category, $industry ) {
        $table_name = self::get_table_name();

        if ( ! self::table_exists( $table_name ) ) {
            return;
        }

        global $wpdb;

        $category = sanitize_key( $category );
        $industry = sanitize_key( $industry );

        if ( '' === $category && '' === $industry ) {
            return;
        }

        $now = current_time( 'mysql' );
        $url = self::build_url( $category, $industry );

        $wpdb->replace(
            $table_name,
            array(
                'category'   => $category,
                'industry'   => $industry,
                'url'        => $url,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Locate the Resources page URL.
     *
     * @return string
     */
    private static function get_directory_base_url() {
        $pages = get_posts(
            array(
                'post_type'      => 'page',
                'posts_per_page' => 1,
                'meta_key'       => '_wp_page_template',
                'meta_value'     => SD_DIRECTORY_PARENT_TEMPLATE_SLUG,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'no_found_rows'  => true,
                'fields'         => 'ids',
            )
        );

        if ( ! empty( $pages ) ) {
            return get_permalink( (int) $pages[0] );
        }

        return home_url( '/resources/' );
    }

    /**
     * Determine whether the deep link table exists.
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
}
