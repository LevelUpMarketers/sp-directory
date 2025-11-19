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
     * Retrieve the directory category labels.
     *
     * @return array
     */
    public static function get_category_labels() {
        $options = array(
            'crm'                  => __( 'CRM', 'super-directory' ),
            'chatbots'             => __( 'Chatbots', 'super-directory' ),
            'hiring_platform'      => __( 'Hiring Platform', 'super-directory' ),
            'lead_generation'      => __( 'Lead Generation', 'super-directory' ),
            'answering_service'    => __( 'Answering Service', 'super-directory' ),
            'csr_training'         => __( 'CSR Training', 'super-directory' ),
            'business_development' => __( 'Business Development', 'super-directory' ),
            'onboarding_companies' => __( 'Onboarding Companies', 'super-directory' ),
        );

        $options = apply_filters( 'sd_directory_categories', $options );

        $labels = array();

        foreach ( $options as $value => $label ) {
            $labels[ sanitize_key( $value ) ] = wp_strip_all_tags( (string) $label );
        }

        return $labels;
    }

    /**
     * Retrieve the directory industry labels.
     *
     * @return array
     */
    public static function get_industry_labels() {
        $options = array(
            'all'                        => __( 'All Industries', 'super-directory' ),
            'multiple'                   => __( 'Multiple', 'super-directory' ),
            'appliance_repair'           => __( 'Appliance Repair', 'super-directory' ),
            'carpet_cleaning'            => __( 'Carpet Cleaning', 'super-directory' ),
            'concrete_masonry'           => __( 'Concrete & Masonry', 'super-directory' ),
            'deck_patio'                 => __( 'Deck & Patio', 'super-directory' ),
            'electrical'                 => __( 'Electrical', 'super-directory' ),
            'fencing'                    => __( 'Fencing', 'super-directory' ),
            'flooring'                   => __( 'Flooring', 'super-directory' ),
            'garage_door'                => __( 'Garage Door', 'super-directory' ),
            'general_contractor_remodel' => __( 'General Contractor & Remodeling', 'super-directory' ),
            'gutter_services'            => __( 'Gutter Services', 'super-directory' ),
            'handyman'                   => __( 'Handyman', 'super-directory' ),
            'hardscaping'                => __( 'Hardscaping', 'super-directory' ),
            'house_cleaning_maid'        => __( 'House Cleaning & Maid', 'super-directory' ),
            'hvac'                       => __( 'HVAC', 'super-directory' ),
            'insulation'                 => __( 'Insulation', 'super-directory' ),
            'irrigation_sprinklers'      => __( 'Irrigation & Sprinklers', 'super-directory' ),
            'junk_removal'               => __( 'Junk Removal', 'super-directory' ),
            'landscaping'                => __( 'Landscaping', 'super-directory' ),
            'moving_storage'             => __( 'Moving & Storage', 'super-directory' ),
            'painting'                   => __( 'Painting', 'super-directory' ),
            'pest_control'               => __( 'Pest Control', 'super-directory' ),
            'plumbing'                   => __( 'Plumbing', 'super-directory' ),
            'pool_spa_services'          => __( 'Pool & Spa Services', 'super-directory' ),
            'pressure_washing'           => __( 'Pressure Washing', 'super-directory' ),
            'roofing'                    => __( 'Roofing', 'super-directory' ),
            'security_smart_home'        => __( 'Security & Smart Home', 'super-directory' ),
            'siding'                     => __( 'Siding', 'super-directory' ),
            'solar_energy'               => __( 'Solar Energy', 'super-directory' ),
            'tree_services'              => __( 'Tree Services', 'super-directory' ),
            'water_mold_restoration'     => __( 'Water & Mold Damage Restoration', 'super-directory' ),
        );

        $options = apply_filters( 'sd_directory_industries', $options );

        $labels = array();

        foreach ( $options as $value => $label ) {
            $labels[ sanitize_key( $value ) ] = wp_strip_all_tags( (string) $label );
        }

        return $labels;
    }

    /**
     * Convert a slug to a human-readable label.
     *
     * @param string $value Raw slug.
     *
     * @return string
     */
    public static function humanize_value( $value ) {
        $value = sanitize_text_field( (string) $value );
        $value = str_replace( array( '-', '_' ), ' ', $value );

        return ucwords( $value );
    }

    /**
     * Get a friendly label for a stored category.
     *
     * @param string $value Stored value.
     *
     * @return string
     */
    public static function get_category_label( $value ) {
        $labels = self::get_category_labels();
        $key    = sanitize_key( $value );

        if ( isset( $labels[ $key ] ) ) {
            return $labels[ $key ];
        }

        return self::humanize_value( $value );
    }

    /**
     * Get a friendly label for a stored industry vertical.
     *
     * @param string $value Stored value.
     *
     * @return string
     */
    public static function get_industry_label( $value ) {
        $labels = self::get_industry_labels();
        $key    = sanitize_key( $value );

        if ( isset( $labels[ $key ] ) ) {
            return $labels[ $key ];
        }

        return self::humanize_value( $value );
    }

    /**
     * Get distinct stored values for a given column.
     *
     * @param string $column Column key.
     *
     * @return array
     */
    public static function get_distinct_values( $column ) {
        $allowed = array( 'category', 'industry_vertical', 'state' );
        $column  = sanitize_key( $column );

        if ( ! in_array( $column, $allowed, true ) ) {
            return array();
        }

        $table_name = self::get_main_table_name();

        if ( ! self::table_exists( $table_name ) ) {
            return array();
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $values = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT $column FROM $table_name WHERE $column != %s", '' ) );

        $values = array_filter( array_map( 'sanitize_text_field', (array) $values ) );

        sort( $values );

        return array_values( array_unique( $values ) );
    }

    /**
     * Search and paginate directory entities.
     *
     * @param array $args Search arguments.
     *
     * @return array
     */
    public static function search_directory_entries( array $args ) {
        global $wpdb;

        $table_name = self::get_main_table_name();

        if ( ! self::table_exists( $table_name ) ) {
            return array(
                'items'       => array(),
                'total'       => 0,
                'total_pages' => 0,
                'page'        => 1,
                'per_page'    => 0,
            );
        }

        $defaults = array(
            'search'   => '',
            'category' => '',
            'industry' => '',
            'state'    => '',
            'page'     => 1,
            'per_page' => 12,
        );

        $args = wp_parse_args( $args, $defaults );

        $page     = max( 1, absint( $args['page'] ) );
        $per_page = absint( $args['per_page'] );

        if ( $per_page <= 0 ) {
            $per_page = 12;
        }

        $per_page = min( $per_page, 50 );

        $search   = sanitize_text_field( $args['search'] );
        $category = sanitize_key( $args['category'] );
        $industry = sanitize_key( $args['industry'] );
        $state    = sanitize_text_field( (string) $args['state'] );

        $conditions = array(
            "p.ID IS NOT NULL",
        );
        $params     = array();

        if ( '' !== $search ) {
            $conditions[] = 'e.name LIKE %s';
            $params[]     = '%' . $wpdb->esc_like( $search ) . '%';
        }

        if ( '' !== $category ) {
            $conditions[] = 'e.category = %s';
            $params[]     = $category;
        }

        if ( '' !== $industry ) {
            $conditions[] = 'e.industry_vertical = %s';
            $params[]     = $industry;
        }

        if ( '' !== $state ) {
            $conditions[] = 'e.state = %s';
            $params[]     = $state;
        }

        $where_sql = '';

        if ( ! empty( $conditions ) ) {
            $where_sql = ' AND ' . implode( ' AND ', $conditions );
        }

        $join  = "FROM $table_name e INNER JOIN {$wpdb->posts} p ON p.ID = e.directory_page_id AND p.post_status = 'publish' AND p.post_type = 'page'";
        $count = "SELECT COUNT(*) $join WHERE 1=1 $where_sql";

        $total = (int) $wpdb->get_var( $wpdb->prepare( $count, $params ) );

        $total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

        if ( $total_pages < 1 ) {
            $total_pages = $total > 0 ? 1 : 0;
        }

        if ( $page > $total_pages && $total_pages > 0 ) {
            $page = $total_pages;
        }

        $offset = ( $page - 1 ) * $per_page;

        if ( $offset < 0 ) {
            $offset = 0;
        }

        $items = array();

        if ( $total > 0 ) {
            $select = "SELECT e.*, p.ID as page_id $join WHERE 1=1 $where_sql ORDER BY e.created_at DESC, e.id DESC LIMIT %d OFFSET %d";
            $query  = $wpdb->prepare( $select, array_merge( $params, array( $per_page, $offset ) ) );

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $results = $wpdb->get_results( $query, ARRAY_A );

            foreach ( (array) $results as $row ) {
                $entity    = self::prepare_template_entity( $row );
                $permalink = isset( $entity['directory_page_id'] ) && $entity['directory_page_id'] ? get_permalink( (int) $entity['directory_page_id'] ) : '';
                $logo_url  = '';

                if ( ! empty( $entity['logo_attachment_id'] ) ) {
                    $logo_url = wp_get_attachment_image_url( $entity['logo_attachment_id'], 'medium' );
                }

                $items[] = array(
                    'id'               => $entity['id'],
                    'name'             => isset( $entity['name'] ) ? $entity['name'] : '',
                    'category'         => isset( $entity['category'] ) ? $entity['category'] : '',
                    'category_label'   => isset( $entity['category'] ) ? self::get_category_label( $entity['category'] ) : '',
                    'industry'         => isset( $entity['industry_vertical'] ) ? $entity['industry_vertical'] : '',
                    'industry_label'   => isset( $entity['industry_vertical'] ) ? self::get_industry_label( $entity['industry_vertical'] ) : '',
                    'state'            => isset( $entity['state'] ) ? $entity['state'] : '',
                    'logo'             => $logo_url,
                    'permalink'        => $permalink,
                );
            }
        }

        return array(
            'items'       => $items,
            'total'       => $total,
            'total_pages' => $total_pages,
            'page'        => $page,
            'per_page'    => $per_page,
        );
    }

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
     * Retrieve the stored gallery attachment identifiers for an entity.
     *
     * Provides a lightweight way for templates to refetch the gallery data
     * in case cached entity payloads have not yet been refreshed with the
     * latest schema changes.
     *
     * @param int $entity_id Directory Listing identifier.
     *
     * @return array<int>
     */
    public static function get_gallery_image_ids( $entity_id ) {
        $entity_id = absint( $entity_id );

        if ( ! $entity_id ) {
            return array();
        }

        $table_name = self::get_main_table_name();

        if ( ! self::table_exists( $table_name ) ) {
            return array();
        }

        global $wpdb;

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT gallery_image_ids FROM $table_name WHERE id = %d",
                $entity_id
            )
        );

        return self::parse_gallery_ids( $value );
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
