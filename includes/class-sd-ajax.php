<?php
/**
 * Handle Ajax operations with configurable minimum execution time.
 *
 * @package SuperDirectory
 */

class SD_Ajax {

    public function register() {
        add_action( 'wp_ajax_sd_save_main_entity', array( $this, 'save_main_entity' ) );
        add_action( 'wp_ajax_sd_delete_main_entity', array( $this, 'delete_main_entity' ) );
        add_action( 'wp_ajax_sd_read_main_entity', array( $this, 'read_main_entity' ) );
        add_action( 'wp_ajax_sd_search_directory', array( $this, 'search_directory' ) );
        add_action( 'wp_ajax_nopriv_sd_search_directory', array( $this, 'search_directory' ) );
        add_action( 'wp_ajax_sd_bulk_import_main_entities', array( $this, 'bulk_import_main_entities' ) );
    }

    private function maybe_delay( $start, $minimum_time = SD_MIN_EXECUTION_TIME ) {
        if ( $minimum_time <= 0 ) {
            return;
        }

        $elapsed = microtime( true ) - $start;

        if ( $elapsed < $minimum_time ) {
            $remaining = ( $minimum_time - $elapsed ) * 1000000;

            if ( $remaining > 0 ) {
                usleep( (int) round( $remaining ) );
            }
        }
    }

    public function save_main_entity() {
        $start = microtime( true );
        check_ajax_referer( 'sd_ajax_nonce' );
        global $wpdb;
        $table = $wpdb->prefix . 'sd_main_entity';
        $id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $now   = current_time( 'mysql' );

        $name = $this->sanitize_text_value( 'name' );

        if ( '' === $name ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'Please provide a Resource Name.', 'super-directory' ),
                )
            );
        }

        $state_options          = $this->get_us_states();
        $extended_state_options = $this->get_us_states_and_territories();
        $category_keys          = $this->get_directory_category_keys();
        $industry_keys          = $this->get_directory_industry_keys();
        $service_model_keys     = $this->get_service_model_keys();

        $data = array(
            'name'                     => $name,
            'category'                 => $this->sanitize_select_value( 'category', $category_keys ),
            'industry_vertical'        => $this->sanitize_select_value( 'industry_vertical', $industry_keys ),
            'service_model'            => $this->sanitize_select_value( 'service_model', $service_model_keys ),
            'website_url'              => $this->sanitize_url_value( 'website_url' ),
            'phone_number'             => $this->sanitize_text_value( 'phone_number' ),
            'email_address'            => $this->sanitize_email_value( 'email_address' ),
            'state'                    => $this->sanitize_state_value( 'state', $extended_state_options ),
            'city'                     => $this->sanitize_text_value( 'city' ),
            'street_address'           => $this->sanitize_text_value( 'street_address' ),
            'zip_code'                 => $this->sanitize_text_value( 'zip_code' ),
            'country'                  => $this->sanitize_text_value( 'country' ),
            'short_description'        => $this->sanitize_textarea_value( 'short_description' ),
            'long_description_primary' => $this->sanitize_editor_value( 'long_description_primary' ),
            'long_description_secondary' => $this->sanitize_editor_value( 'long_description_secondary' ),
            'facebook_url'             => $this->sanitize_url_value( 'facebook_url' ),
            'instagram_url'            => $this->sanitize_url_value( 'instagram_url' ),
            'youtube_url'              => $this->sanitize_url_value( 'youtube_url' ),
            'linkedin_url'             => $this->sanitize_url_value( 'linkedin_url' ),
            'google_business_url'      => $this->sanitize_url_value( 'google_business_url' ),
            'logo_attachment_id'       => $this->sanitize_attachment_id_value( 'logo_attachment_id' ),
            'homepage_screenshot_id'   => $this->sanitize_attachment_id_value( 'homepage_screenshot_id' ),
            'gallery_image_ids'        => $this->sanitize_gallery_ids_value( 'gallery_image_ids' ),
            'updated_at'               => $now,
        );

        $formats = array_fill( 0, count( $data ), '%s' );

        if ( $id > 0 ) {
            $existing = $wpdb->get_row( $wpdb->prepare( "SELECT name, directory_page_id FROM $table WHERE id = %d", $id ), ARRAY_A );
            $result   = $wpdb->update( $table, $data, array( 'id' => $id ), $formats, array( '%d' ) );
            $message = __( 'Changes saved.', 'super-directory' );

            if ( false === $result && $wpdb->last_error ) {
                $this->maybe_delay( $start );
                wp_send_json_error(
                    array(
                        'message' => __( 'Unable to save changes. Please try again.', 'super-directory' ),
                    )
                );
            }

            if ( false !== $result ) {
                $existing_page_id = isset( $existing['directory_page_id'] ) ? (int) $existing['directory_page_id'] : 0;
                $page_id          = $this->update_directory_page( $id, $name, $existing_page_id );

                if ( $page_id && $page_id !== $existing_page_id ) {
                    $wpdb->update(
                        $table,
                        array( 'directory_page_id' => $page_id ),
                        array( 'id' => $id ),
                        array( '%d' ),
                        array( '%d' )
                    );
                }
            }
        } else {
            $data['created_at'] = $now;
            $formats[]          = '%s';
            $result             = $wpdb->insert( $table, $data, $formats );
            $message            = __( 'Saved', 'super-directory' );

            if ( false === $result ) {
                $this->maybe_delay( $start );
                wp_send_json_error(
                    array(
                        'message' => __( 'Unable to save the record. Please try again.', 'super-directory' ),
                    )
                );
            }

            if ( false !== $result ) {
                $entity_id = (int) $wpdb->insert_id;
                $page_id   = $this->create_directory_page( $entity_id, $name );

                if ( $page_id ) {
                    $wpdb->update(
                        $table,
                        array( 'directory_page_id' => $page_id ),
                        array( 'id' => $entity_id ),
                        array( '%d' ),
                        array( '%d' )
                    );
                }
            }
        }

        $this->maybe_delay( $start );
        wp_send_json_success( array( 'message' => $message ) );
    }

    public function delete_main_entity() {
        $start = microtime( true );
        check_ajax_referer( 'sd_ajax_nonce' );
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        global $wpdb;
        $table  = $wpdb->prefix . 'sd_main_entity';
        $record = $wpdb->get_row( $wpdb->prepare( "SELECT directory_page_id FROM $table WHERE id = %d", $id ), ARRAY_A );

        $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

        if ( $record && ! empty( $record['directory_page_id'] ) ) {
            wp_delete_post( (int) $record['directory_page_id'], true );
        }

        $this->maybe_delay( $start );
        wp_send_json_success( array( 'message' => __( 'Deleted', 'super-directory' ) ) );
    }

    public function read_main_entity() {
        $start = microtime( true );
        check_ajax_referer( 'sd_ajax_nonce' );
        global $wpdb;
        $table    = $wpdb->prefix . 'sd_main_entity';
        $page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;

        if ( $per_page <= 0 ) {
            $per_page = 20;
        }

        $per_page = min( $per_page, 100 );

        $total       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

        if ( $total_pages < 1 ) {
            $total_pages = 1;
        }

        if ( $page > $total_pages ) {
            $page = $total_pages;
        }

        $offset = ( $page - 1 ) * $per_page;

        if ( $offset < 0 ) {
            $offset = 0;
        }

        $entities = array();

        if ( $total > 0 ) {
            $entities = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table ORDER BY name ASC, id ASC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );

            foreach ( $entities as &$entity ) {
                $entity['long_description_primary'] = $this->format_editor_content_for_response( isset( $entity['long_description_primary'] ) ? $entity['long_description_primary'] : '' );
                $entity['long_description_secondary'] = $this->format_editor_content_for_response( isset( $entity['long_description_secondary'] ) ? $entity['long_description_secondary'] : '' );
                $entity['short_description']        = wp_kses_post( isset( $entity['short_description'] ) ? $entity['short_description'] : '' );

                foreach ( array( 'name', 'category', 'industry_vertical', 'service_model', 'website_url', 'phone_number', 'email_address', 'state', 'city', 'street_address', 'zip_code', 'country', 'facebook_url', 'instagram_url', 'youtube_url', 'linkedin_url', 'google_business_url' ) as $text_key ) {
                    if ( ! isset( $entity[ $text_key ] ) ) {
                        $entity[ $text_key ] = '';
                    }
                }

                $entity['logo_attachment_id'] = isset( $entity['logo_attachment_id'] ) ? absint( $entity['logo_attachment_id'] ) : 0;
                $entity['homepage_screenshot_id'] = isset( $entity['homepage_screenshot_id'] ) ? absint( $entity['homepage_screenshot_id'] ) : 0;
                $entity['gallery_image_ids']  = isset( $entity['gallery_image_ids'] ) ? $this->normalize_gallery_ids_value( $entity['gallery_image_ids'] ) : '';
            }
            unset( $entity );
        }

        $this->maybe_delay( $start, 0 );
        wp_send_json_success(
            array(
                'entities'    => $entities,
                'page'        => $page,
                'per_page'    => $per_page,
                'total'       => $total,
                'total_pages' => $total_pages,
            )
        );
    }

    public function bulk_import_main_entities() {
        $start = microtime( true );
        check_ajax_referer( 'sd_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'You do not have permission to import directory entries.', 'super-directory' ),
                )
            );
        }

        if ( empty( $_FILES['sd_bulk_file'] ) || ! isset( $_FILES['sd_bulk_file']['tmp_name'] ) ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'Please choose a CSV or TSV file to upload.', 'super-directory' ),
                )
            );
        }

        $file    = $_FILES['sd_bulk_file'];
        $allowed = array(
            'csv' => 'text/csv',
            'tsv' => 'text/tab-separated-values',
            'txt' => 'text/plain',
        );

        $upload = wp_handle_upload(
            $file,
            array(
                'test_form' => false,
                'mimes'     => $allowed,
            )
        );

        if ( isset( $upload['error'] ) ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => wp_kses_post( $upload['error'] ),
                )
            );
        }

        if ( empty( $upload['file'] ) || ! file_exists( $upload['file'] ) ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'The uploaded file could not be found. Please try again.', 'super-directory' ),
                )
            );
        }

        $result = $this->process_bulk_import_file( $upload['file'] );

        if ( file_exists( $upload['file'] ) ) {
            wp_delete_file( $upload['file'] );
        }

        $this->maybe_delay( $start );

        if ( $result['imported'] < 1 ) {
            wp_send_json_error(
                array(
                    'message' => $result['message'],
                    'details' => $result['details'],
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => $result['message'],
                'details' => $result['details'],
            )
        );
    }

    /**
     * Search public directory listings for the parent template.
     */
    public function search_directory() {
        $start = microtime( true );
        check_ajax_referer( 'sd_directory_search', 'nonce' );

        $search   = sanitize_text_field( (string) $this->get_post_value( 'search' ) );
        $category = sanitize_key( (string) $this->get_post_value( 'category' ) );
        $industry = sanitize_key( (string) $this->get_post_value( 'industry' ) );
        $state    = sanitize_text_field( (string) $this->get_post_value( 'state' ) );
        $page     = max( 1, absint( $this->get_post_value( 'page' ) ) );
        $per_page = absint( $this->get_post_value( 'per_page' ) );

        if ( $per_page <= 0 ) {
            $per_page = 12;
        }

        $results = SD_Main_Entity_Helper::search_directory_entries(
            array(
                'search'   => $search,
                'category' => $category,
                'industry' => $industry,
                'state'    => $state,
                'page'     => $page,
                'per_page' => $per_page,
            )
        );

        $this->maybe_delay( $start, 0 );

        wp_send_json_success( $results );
    }

    private function get_post_value( $key ) {
        if ( ! isset( $_POST[ $key ] ) ) {
            return null;
        }

        $value = $_POST[ $key ];

        if ( is_array( $value ) ) {
            return array_map( 'wp_unslash', $value );
        }

        return wp_unslash( $value );
    }

    private function create_directory_page( $entity_id, $name ) {
        $parent_id = $this->ensure_resources_parent_page();
        $slug   = $this->prepare_directory_slug( $name );
        $unique = wp_unique_post_slug( $slug, 0, 'publish', 'page', $parent_id );

        $page_id = wp_insert_post(
            array(
                'post_title'   => $name,
                'post_name'    => $unique,
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_parent'  => $parent_id,
                'post_content' => '',
                'meta_input'   => array(
                    '_wp_page_template'  => SD_DIRECTORY_TEMPLATE_SLUG,
                    '_sd_main_entity_id' => absint( $entity_id ),
                ),
            ),
            true
        );

        if ( is_wp_error( $page_id ) ) {
            return 0;
        }

        $this->sync_directory_page_meta( $page_id, $entity_id );

        do_action( 'sd_log_generated_content', $page_id, $entity_id );

        return (int) $page_id;
    }

    private function update_directory_page( $entity_id, $name, $page_id ) {
        $page_id = absint( $page_id );

        if ( $page_id ) {
            $page = get_post( $page_id );

            if ( $page && 'page' === $page->post_type ) {
                $parent_id = $this->ensure_resources_parent_page();
                $slug      = $this->prepare_directory_slug( $name );
                $unique    = wp_unique_post_slug( $slug, $page_id, 'publish', 'page', $parent_id );

                $update = wp_update_post(
                    array(
                        'ID'         => $page_id,
                        'post_title' => $name,
                        'post_name'  => $unique,
                        'post_parent'=> $parent_id,
                    ),
                    true
                );

                if ( ! is_wp_error( $update ) ) {
                    $this->sync_directory_page_meta( $page_id, $entity_id );

                    return $page_id;
                }
            }
        }

        return $this->create_directory_page( $entity_id, $name );
    }

    private function sync_directory_page_meta( $page_id, $entity_id ) {
        update_post_meta( $page_id, '_wp_page_template', SD_DIRECTORY_TEMPLATE_SLUG );
        update_post_meta( $page_id, '_sd_main_entity_id', absint( $entity_id ) );

        $this->maybe_clear_legacy_shortcode_content( $page_id );
    }

    private function maybe_clear_legacy_shortcode_content( $page_id ) {
        $page = get_post( $page_id );

        if ( ! $page || 'page' !== $page->post_type ) {
            return;
        }

        $content = trim( (string) $page->post_content );

        if ( '' === $content ) {
            return;
        }

        if ( preg_match( '/^\[sd-main-entity[^\]]*\]$/', $content ) ) {
            wp_update_post(
                array(
                    'ID'           => $page_id,
                    'post_content' => '',
                )
            );
        }
    }

    private function ensure_resources_parent_page() {
        $existing = get_page_by_path( 'resources' );

        if ( $existing && 'page' === $existing->post_type ) {
            if ( 'publish' !== $existing->post_status ) {
                wp_update_post(
                    array(
                        'ID'          => $existing->ID,
                        'post_status' => 'publish',
                    )
                );
            }

            return (int) $existing->ID;
        }

        $page_id = wp_insert_post(
            array(
                'post_title'  => __( 'Resources', 'super-directory' ),
                'post_name'   => 'resources',
                'post_type'   => 'page',
                'post_status' => 'publish',
            ),
            true
        );

        if ( is_wp_error( $page_id ) ) {
            return 0;
        }

        return (int) $page_id;
    }

    private function prepare_directory_slug( $name ) {
        $base = sanitize_title( $name );

        if ( '' === $base ) {
            $base = 'listing';
        }

        return sanitize_title( $base . '-directory' );
    }

    private function sanitize_text_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = implode( ',', $value );
        }

        return sanitize_text_field( $value );
    }

    private function replace_template_tokens( $content, $tokens ) {
        if ( ! is_string( $content ) || '' === $content || empty( $tokens ) || ! is_array( $tokens ) ) {
            return $content;
        }

        foreach ( $tokens as $key => $value ) {
            if ( ! is_scalar( $value ) ) {
                continue;
            }

            $token = '{' . $key . '}';
            $content = str_replace( $token, (string) $value, $content );
        }

        return $content;
    }

    private function sanitize_select_value( $key, $allowed, $allow_empty = true ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return $allow_empty ? '' : reset( $allowed );
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        $value = sanitize_text_field( $value );

        if ( '' === $value && $allow_empty ) {
            return '';
        }

        if ( in_array( $value, $allowed, true ) ) {
            return $value;
        }

        return $allow_empty ? '' : reset( $allowed );
    }

    private function sanitize_textarea_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = implode( "\n", array_map( 'sanitize_textarea_field', $value ) );
        }

        return sanitize_textarea_field( $value );
    }

    private function sanitize_email_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        $value = sanitize_email( $value );

        return $value ? $value : '';
    }

    private function sanitize_url_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return esc_url_raw( $value );
    }

    private function sanitize_state_value( $key, $allowed_states ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        $value = sanitize_text_field( $value );

        if ( '' === $value ) {
            return '';
        }

        if ( in_array( $value, $allowed_states, true ) ) {
            return $value;
        }

        return '';
    }

    private function sanitize_editor_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return wp_kses_post( $value );
    }

    private function sanitize_attachment_id_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return 0;
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return absint( $value );
    }

    private function sanitize_gallery_ids_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = implode( ',', $value );
        }

        return $this->normalize_gallery_ids_value( $value );
    }

    private function normalize_gallery_ids_value( $value ) {
        if ( null === $value || '' === $value ) {
            return '';
        }

        $ids = is_array( $value ) ? $value : explode( ',', (string) $value );

        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );
        $ids = array_values( array_unique( $ids ) );

        if ( empty( $ids ) ) {
            return '';
        }

        return implode( ',', $ids );
    }

    private function format_editor_content_for_response( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        return wp_kses_post( $value );
    }

    private function get_directory_category_keys() {
        $options = $this->get_directory_categories();

        $keys = array();

        foreach ( $options as $value => $label ) {
            $keys[] = sanitize_key( $value );
        }

        return array_values( array_unique( $keys ) );
    }

    private function get_directory_industry_keys() {
        $options = $this->get_directory_industries();

        $keys = array();

        foreach ( $options as $value => $label ) {
            $keys[] = sanitize_key( $value );
        }

        return array_values( array_unique( $keys ) );
    }

    private function get_service_model_keys() {
        $options = $this->get_directory_service_models();

        $keys = array();

        foreach ( $options as $value => $label ) {
            $keys[] = sanitize_key( $value );
        }

        return array_values( array_unique( $keys ) );
    }

    private function get_directory_categories() {
        $options = array(
            'crm'                  => __( 'CRM', 'super-directory' ),
            'chatbots'             => __( 'Chatbots', 'super-directory' ),
            'hiring_platform'      => __( 'Hiring Platform', 'super-directory' ),
            'lead_generation'      => __( 'Lead Generation', 'super-directory' ),
            'answering_service'    => __( 'Answering Service', 'super-directory' ),
            'csr_training'         => __( 'CSR Training', 'super-directory' ),
            'business_development' => __( 'Business Development', 'super-directory' ),
            'onboarding_companies' => __( 'Onboarding Companies', 'super-directory' ),
            'review_solicitation'  => __( 'Review Solicitation', 'super-directory' ),
        );

        $options = apply_filters( 'sd_directory_categories', $options );

        $normalized = array();

        foreach ( $options as $value => $label ) {
            $normalized[ sanitize_key( $value ) ] = wp_strip_all_tags( (string) $label );
        }

        return $normalized;
    }

    private function get_directory_industries() {
        $options = array(
            'all'                           => __( 'All Industries', 'super-directory' ),
            'multiple'                      => __( 'Multiple Industries', 'super-directory' ),
            'appliance_repair'              => __( 'Appliance Repair', 'super-directory' ),
            'carpet_cleaning'               => __( 'Carpet Cleaning', 'super-directory' ),
            'concrete_masonry'              => __( 'Concrete & Masonry', 'super-directory' ),
            'deck_patio'                    => __( 'Deck & Patio', 'super-directory' ),
            'electrical'                    => __( 'Electrical', 'super-directory' ),
            'fencing'                       => __( 'Fencing', 'super-directory' ),
            'flooring'                      => __( 'Flooring', 'super-directory' ),
            'garage_door'                   => __( 'Garage Door', 'super-directory' ),
            'general_contractor_remodel'    => __( 'General Contractor & Remodeling', 'super-directory' ),
            'gutter_services'               => __( 'Gutter Services', 'super-directory' ),
            'handyman'                      => __( 'Handyman', 'super-directory' ),
            'hardscaping'                   => __( 'Hardscaping', 'super-directory' ),
            'house_cleaning_maid'           => __( 'House Cleaning & Maid', 'super-directory' ),
            'hvac'                          => __( 'HVAC', 'super-directory' ),
            'insulation'                    => __( 'Insulation', 'super-directory' ),
            'irrigation_sprinklers'         => __( 'Irrigation & Sprinklers', 'super-directory' ),
            'junk_removal'                  => __( 'Junk Removal', 'super-directory' ),
            'landscaping'                   => __( 'Landscaping', 'super-directory' ),
            'moving_storage'                => __( 'Moving & Storage', 'super-directory' ),
            'painting'                      => __( 'Painting', 'super-directory' ),
            'pest_control'                  => __( 'Pest Control', 'super-directory' ),
            'plumbing'                      => __( 'Plumbing', 'super-directory' ),
            'pool_spa_services'             => __( 'Pool & Spa Services', 'super-directory' ),
            'pressure_washing'              => __( 'Pressure Washing', 'super-directory' ),
            'roofing'                       => __( 'Roofing', 'super-directory' ),
            'security_smart_home'           => __( 'Security & Smart Home', 'super-directory' ),
            'siding'                        => __( 'Siding', 'super-directory' ),
            'solar_energy'                  => __( 'Solar Energy', 'super-directory' ),
            'tree_services'                 => __( 'Tree Services', 'super-directory' ),
            'water_mold_restoration'        => __( 'Water & Mold Damage Restoration', 'super-directory' ),
        );

        $options = apply_filters( 'sd_directory_industries', $options );

        $normalized = array();

        foreach ( $options as $value => $label ) {
            $normalized[ sanitize_key( $value ) ] = wp_strip_all_tags( (string) $label );
        }

        return $normalized;
    }

    private function get_directory_service_models() {
        $options = array(
            'local'   => __( 'Local Customers Only', 'super-directory' ),
            'virtual' => __( 'Virtual / National', 'super-directory' ),
            'both'    => __( 'Both Local & National', 'super-directory' ),
        );

        $options = apply_filters( 'sd_directory_service_models', $options );

        $normalized = array();

        foreach ( $options as $value => $label ) {
            $normalized[ sanitize_key( $value ) ] = wp_strip_all_tags( (string) $label );
        }

        return $normalized;
    }

    private function process_bulk_import_file( $file_path ) {
        $details  = array();
        $imported = 0;
        $skipped  = 0;

        $handle = fopen( $file_path, 'r' );

        if ( ! $handle ) {
            return array(
                'imported' => 0,
                'skipped'  => 0,
                'message'  => __( 'Unable to read the uploaded file. Please try again.', 'super-directory' ),
                'details'  => array(),
            );
        }

        $first_line = fgets( $handle );

        if ( false === $first_line ) {
            fclose( $handle );

            return array(
                'imported' => 0,
                'skipped'  => 0,
                'message'  => __( 'The uploaded file appears to be empty.', 'super-directory' ),
                'details'  => array(),
            );
        }

        $delimiter = $this->detect_bulk_delimiter( $first_line );
        $headers   = str_getcsv( $first_line, $delimiter );
        $map       = $this->get_bulk_header_map();
        $columns   = array();

        foreach ( $headers as $index => $header ) {
            $normalized = $this->normalize_bulk_header( $header );

            if ( isset( $map[ $normalized ] ) ) {
                $columns[ $map[ $normalized ] ] = (int) $index;
            }
        }

        if ( ! isset( $columns['name'] ) ) {
            fclose( $handle );

            return array(
                'imported' => 0,
                'skipped'  => 0,
                'message'  => __( 'The file is missing the Resource/Company/Vendor Name column.', 'super-directory' ),
                'details'  => array(),
            );
        }

        $line        = 1;
        $categories  = $this->get_directory_categories();
        $industries  = $this->get_directory_industries();
        $services    = $this->get_directory_service_models();
        $states      = $this->get_us_states();
        $full_states = $this->get_us_states_and_territories();

        while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
            $line++;
            $row_data = array();

            foreach ( $columns as $key => $index ) {
                $row_data[ $key ] = isset( $row[ $index ] ) ? $row[ $index ] : '';
            }

            $prepared = $this->prepare_bulk_row( $row_data, $categories, $industries, $services, $states, $full_states );

            if ( ! empty( $prepared['error'] ) ) {
                $skipped++;
                $details[] = sprintf( __( 'Row %1$d skipped: %2$s', 'super-directory' ), $line, $prepared['error'] );
                continue;
            }

            $inserted = $this->insert_bulk_entity( $prepared['data'] );

            if ( ! $inserted ) {
                $skipped++;
                $details[] = sprintf( __( 'Row %d could not be saved.', 'super-directory' ), $line );
                continue;
            }

            $imported++;

            if ( ! empty( $prepared['notices'] ) ) {
                foreach ( $prepared['notices'] as $notice ) {
                    $details[] = sprintf( __( 'Row %1$d note: %2$s', 'super-directory' ), $line, $notice );
                }
            }
        }

        fclose( $handle );

        if ( $imported < 1 ) {
            return array(
                'imported' => 0,
                'skipped'  => $skipped,
                'message'  => __( 'No directory entries were imported.', 'super-directory' ),
                'details'  => $details,
            );
        }

        $summary = sprintf(
            __( 'Imported %1$d entr%s. Skipped %2$d.', 'super-directory' ),
            $imported,
            1 === $imported ? 'y' : 'ies',
            $skipped
        );

        return array(
            'imported' => $imported,
            'skipped'  => $skipped,
            'message'  => $summary,
            'details'  => $details,
        );
    }

    private function insert_bulk_entity( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sd_main_entity';
        $now   = current_time( 'mysql' );

        $record = array_merge(
            array(
                'category'                 => '',
                'industry_vertical'        => '',
                'service_model'            => '',
                'website_url'              => '',
                'phone_number'             => '',
                'email_address'            => '',
                'state'                    => '',
                'city'                     => '',
                'street_address'           => '',
                'zip_code'                 => '',
                'country'                  => '',
                'short_description'        => '',
                'long_description_primary' => '',
                'long_description_secondary' => '',
                'facebook_url'             => '',
                'instagram_url'            => '',
                'youtube_url'              => '',
                'linkedin_url'             => '',
                'google_business_url'      => '',
                'logo_attachment_id'       => 0,
                'homepage_screenshot_id'   => 0,
                'gallery_image_ids'        => '',
                'directory_page_id'        => 0,
            ),
            $data,
            array(
                'created_at' => $now,
                'updated_at' => $now,
            )
        );

        $formats = array(
            '%s', // name
            '%s', // category
            '%s', // industry_vertical
            '%s', // service_model
            '%s', // website_url
            '%s', // phone_number
            '%s', // email_address
            '%s', // state
            '%s', // city
            '%s', // street_address
            '%s', // zip_code
            '%s', // country
            '%s', // short_description
            '%s', // long_description_primary
            '%s', // long_description_secondary
            '%s', // facebook_url
            '%s', // instagram_url
            '%s', // youtube_url
            '%s', // linkedin_url
            '%s', // google_business_url
            '%d', // logo_attachment_id
            '%d', // homepage_screenshot_id
            '%s', // gallery_image_ids
            '%d', // directory_page_id
            '%s', // created_at
            '%s', // updated_at
        );

        $insert = $wpdb->insert(
            $table,
            array(
                'name'                     => isset( $record['name'] ) ? $record['name'] : '',
                'category'                 => $record['category'],
                'industry_vertical'        => $record['industry_vertical'],
                'service_model'            => $record['service_model'],
                'website_url'              => $record['website_url'],
                'phone_number'             => $record['phone_number'],
                'email_address'            => $record['email_address'],
                'state'                    => $record['state'],
                'city'                     => $record['city'],
                'street_address'           => $record['street_address'],
                'zip_code'                 => $record['zip_code'],
                'country'                  => $record['country'],
                'short_description'        => $record['short_description'],
                'long_description_primary' => $record['long_description_primary'],
                'long_description_secondary' => $record['long_description_secondary'],
                'facebook_url'             => $record['facebook_url'],
                'instagram_url'            => $record['instagram_url'],
                'youtube_url'              => $record['youtube_url'],
                'linkedin_url'             => $record['linkedin_url'],
                'google_business_url'      => $record['google_business_url'],
                'logo_attachment_id'       => absint( $record['logo_attachment_id'] ),
                'homepage_screenshot_id'   => absint( $record['homepage_screenshot_id'] ),
                'gallery_image_ids'        => $record['gallery_image_ids'],
                'directory_page_id'        => absint( $record['directory_page_id'] ),
                'created_at'               => $record['created_at'],
                'updated_at'               => $record['updated_at'],
            ),
            $formats
        );

        if ( false === $insert ) {
            return false;
        }

        $entity_id = (int) $wpdb->insert_id;

        if ( $entity_id > 0 ) {
            $page_id = $this->create_directory_page( $entity_id, $record['name'] );

            if ( $page_id ) {
                $wpdb->update(
                    $table,
                    array( 'directory_page_id' => $page_id ),
                    array( 'id' => $entity_id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }

        return true;
    }

    private function prepare_bulk_row( $row_data, $categories, $industries, $services, $states, $extended_states ) {
        $notices = array();

        $name = $this->sanitize_import_text( isset( $row_data['name'] ) ? $row_data['name'] : '' );

        if ( '' === $name ) {
            return array(
                'error' => __( 'Missing resource name.', 'super-directory' ),
            );
        }

        $category          = $this->map_import_option_value( isset( $row_data['category'] ) ? $row_data['category'] : '', $categories );
        $industry_vertical = $this->map_import_option_value( isset( $row_data['industry_vertical'] ) ? $row_data['industry_vertical'] : '', $industries );
        $service_model     = $this->map_service_model_value( isset( $row_data['service_model'] ) ? $row_data['service_model'] : '', $services );

        $state = $this->map_state_value( isset( $row_data['state'] ) ? $row_data['state'] : '', $states, $extended_states );

        $short_description = $this->sanitize_import_textarea( isset( $row_data['short_description'] ) ? $row_data['short_description'] : '' );
        $short_description = $this->enforce_length( $short_description, 98, $notices, __( 'Short description trimmed to 98 characters.', 'super-directory' ) );

        $primary = isset( $row_data['long_description_primary'] ) ? $row_data['long_description_primary'] : '';
        $primary = $this->sanitize_import_editor( $primary );
        $primary = $this->enforce_html_length( $primary, 770, $notices, __( 'What This Resource Does was shortened to 770 characters.', 'super-directory' ) );

        $secondary = isset( $row_data['long_description_secondary'] ) ? $row_data['long_description_secondary'] : '';
        $secondary = $this->sanitize_import_editor( $secondary );
        $secondary = $this->enforce_html_length( $secondary, 770, $notices, __( 'Why We Recommend This Resource was shortened to 770 characters.', 'super-directory' ) );

        $facebook  = $this->sanitize_import_url( isset( $row_data['facebook_url'] ) ? $row_data['facebook_url'] : '' );
        $instagram = $this->sanitize_import_url( isset( $row_data['instagram_url'] ) ? $row_data['instagram_url'] : '' );
        $youtube   = $this->sanitize_import_url( isset( $row_data['youtube_url'] ) ? $row_data['youtube_url'] : '' );
        $linkedin  = $this->sanitize_import_url( isset( $row_data['linkedin_url'] ) ? $row_data['linkedin_url'] : '' );
        $gmb       = $this->sanitize_import_url( isset( $row_data['google_business_url'] ) ? $row_data['google_business_url'] : '' );

        $data = array(
            'name'                     => $name,
            'category'                 => $category,
            'industry_vertical'        => $industry_vertical,
            'service_model'            => $service_model,
            'website_url'              => $this->sanitize_import_url( isset( $row_data['website_url'] ) ? $row_data['website_url'] : '' ),
            'phone_number'             => $this->sanitize_import_text( isset( $row_data['phone_number'] ) ? $row_data['phone_number'] : '' ),
            'email_address'            => $this->sanitize_import_email( isset( $row_data['email_address'] ) ? $row_data['email_address'] : '' ),
            'state'                    => $state,
            'city'                     => $this->sanitize_import_text( isset( $row_data['city'] ) ? $row_data['city'] : '' ),
            'street_address'           => $this->sanitize_import_text( isset( $row_data['street_address'] ) ? $row_data['street_address'] : '' ),
            'zip_code'                 => $this->sanitize_import_text( isset( $row_data['zip_code'] ) ? $row_data['zip_code'] : '' ),
            'country'                  => '',
            'short_description'        => $short_description,
            'long_description_primary' => $primary,
            'long_description_secondary' => $secondary,
            'facebook_url'             => $facebook,
            'instagram_url'            => $instagram,
            'youtube_url'              => $youtube,
            'linkedin_url'             => $linkedin,
            'google_business_url'      => $gmb,
            'logo_attachment_id'       => absint( isset( $row_data['logo_attachment_id'] ) ? $row_data['logo_attachment_id'] : 0 ),
            'homepage_screenshot_id'   => absint( isset( $row_data['homepage_screenshot_id'] ) ? $row_data['homepage_screenshot_id'] : 0 ),
        );

        return array(
            'data'    => $data,
            'notices' => $notices,
        );
    }

    private function enforce_length( $value, $limit, &$notices, $notice_text ) {
        $clean = $value;

        if ( $limit > 0 && function_exists( 'mb_strlen' ) && mb_strlen( $clean ) > $limit ) {
            $clean      = mb_substr( $clean, 0, $limit );
            $notices[] = $notice_text;
        }

        if ( $limit > 0 && ! function_exists( 'mb_strlen' ) && strlen( $clean ) > $limit ) {
            $clean      = substr( $clean, 0, $limit );
            $notices[] = $notice_text;
        }

        return $clean;
    }

    private function enforce_html_length( $value, $limit, &$notices, $notice_text ) {
        $clean = $value;

        if ( $limit > 0 ) {
            $length = function_exists( 'mb_strlen' ) ? mb_strlen( wp_strip_all_tags( $clean ) ) : strlen( wp_strip_all_tags( $clean ) );

            if ( $length > $limit ) {
                $clean     = wp_html_excerpt( $clean, $limit, '' );
                $notices[] = $notice_text;
            }
        }

        return $clean;
    }

    private function sanitize_import_text( $value ) {
        if ( is_array( $value ) ) {
            $value = implode( ',', $value );
        }

        return sanitize_text_field( (string) $value );
    }

    private function sanitize_import_textarea( $value ) {
        if ( is_array( $value ) ) {
            $value = implode( "\n", $value );
        }

        return sanitize_textarea_field( (string) $value );
    }

    private function sanitize_import_email( $value ) {
        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        $email = sanitize_email( $value );

        return $email ? $email : '';
    }

    private function sanitize_import_url( $value ) {
        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        $url = esc_url_raw( $value );

        if ( '' === $url ) {
            return '';
        }

        return $url;
    }

    private function sanitize_import_editor( $value ) {
        if ( is_array( $value ) ) {
            $value = implode( "\n", $value );
        }

        return wp_kses_post( (string) $value );
    }

    private function map_import_option_value( $value, $options ) {
        if ( empty( $value ) ) {
            return '';
        }

        $normalized_value = $this->normalize_bulk_header( $value );

        foreach ( $options as $key => $label ) {
            $key_compare   = $this->normalize_bulk_header( $key );
            $label_compare = $this->normalize_bulk_header( $label );

            if ( $normalized_value === $key_compare || $normalized_value === $label_compare ) {
                return sanitize_key( $key );
            }
        }

        return '';
    }

    private function map_service_model_value( $value, $options ) {
        if ( empty( $value ) ) {
            return '';
        }

        $normalized_value = $this->normalize_bulk_header( $value );

        foreach ( $options as $key => $label ) {
            $key_compare   = $this->normalize_bulk_header( $key );
            $label_compare = $this->normalize_bulk_header( $label );

            if ( $normalized_value === $key_compare || $normalized_value === $label_compare ) {
                return sanitize_key( $key );
            }
        }

        if ( false !== strpos( $normalized_value, 'local' ) ) {
            return 'local';
        }

        if ( false !== strpos( $normalized_value, 'virtual' ) || false !== strpos( $normalized_value, 'national' ) ) {
            return 'virtual';
        }

        if ( false !== strpos( $normalized_value, 'both' ) ) {
            return 'both';
        }

        return '';
    }

    private function map_state_value( $value, $states, $extended_states ) {
        if ( empty( $value ) ) {
            return '';
        }

        $value = trim( (string) $value );

        if ( strlen( $value ) === 2 ) {
            $map = $this->get_state_abbreviation_map();
            $key = strtoupper( $value );

            if ( isset( $map[ $key ] ) ) {
                $value = $map[ $key ];
            }
        }

        if ( in_array( $value, $extended_states, true ) ) {
            return $value;
        }

        foreach ( $states as $state ) {
            if ( $this->normalize_bulk_header( $state ) === $this->normalize_bulk_header( $value ) ) {
                return $state;
            }
        }

        return '';
    }

    private function get_state_abbreviation_map() {
        return array(
            'AL' => __( 'Alabama', 'super-directory' ),
            'AK' => __( 'Alaska', 'super-directory' ),
            'AZ' => __( 'Arizona', 'super-directory' ),
            'AR' => __( 'Arkansas', 'super-directory' ),
            'CA' => __( 'California', 'super-directory' ),
            'CO' => __( 'Colorado', 'super-directory' ),
            'CT' => __( 'Connecticut', 'super-directory' ),
            'DE' => __( 'Delaware', 'super-directory' ),
            'FL' => __( 'Florida', 'super-directory' ),
            'GA' => __( 'Georgia', 'super-directory' ),
            'HI' => __( 'Hawaii', 'super-directory' ),
            'ID' => __( 'Idaho', 'super-directory' ),
            'IL' => __( 'Illinois', 'super-directory' ),
            'IN' => __( 'Indiana', 'super-directory' ),
            'IA' => __( 'Iowa', 'super-directory' ),
            'KS' => __( 'Kansas', 'super-directory' ),
            'KY' => __( 'Kentucky', 'super-directory' ),
            'LA' => __( 'Louisiana', 'super-directory' ),
            'ME' => __( 'Maine', 'super-directory' ),
            'MD' => __( 'Maryland', 'super-directory' ),
            'MA' => __( 'Massachusetts', 'super-directory' ),
            'MI' => __( 'Michigan', 'super-directory' ),
            'MN' => __( 'Minnesota', 'super-directory' ),
            'MS' => __( 'Mississippi', 'super-directory' ),
            'MO' => __( 'Missouri', 'super-directory' ),
            'MT' => __( 'Montana', 'super-directory' ),
            'NE' => __( 'Nebraska', 'super-directory' ),
            'NV' => __( 'Nevada', 'super-directory' ),
            'NH' => __( 'New Hampshire', 'super-directory' ),
            'NJ' => __( 'New Jersey', 'super-directory' ),
            'NM' => __( 'New Mexico', 'super-directory' ),
            'NY' => __( 'New York', 'super-directory' ),
            'NC' => __( 'North Carolina', 'super-directory' ),
            'ND' => __( 'North Dakota', 'super-directory' ),
            'OH' => __( 'Ohio', 'super-directory' ),
            'OK' => __( 'Oklahoma', 'super-directory' ),
            'OR' => __( 'Oregon', 'super-directory' ),
            'PA' => __( 'Pennsylvania', 'super-directory' ),
            'RI' => __( 'Rhode Island', 'super-directory' ),
            'SC' => __( 'South Carolina', 'super-directory' ),
            'SD' => __( 'South Dakota', 'super-directory' ),
            'TN' => __( 'Tennessee', 'super-directory' ),
            'TX' => __( 'Texas', 'super-directory' ),
            'UT' => __( 'Utah', 'super-directory' ),
            'VT' => __( 'Vermont', 'super-directory' ),
            'VA' => __( 'Virginia', 'super-directory' ),
            'WA' => __( 'Washington', 'super-directory' ),
            'WV' => __( 'West Virginia', 'super-directory' ),
            'WI' => __( 'Wisconsin', 'super-directory' ),
            'WY' => __( 'Wyoming', 'super-directory' ),
            'DC' => __( 'District of Columbia', 'super-directory' ),
            'AS' => __( 'American Samoa', 'super-directory' ),
            'GU' => __( 'Guam', 'super-directory' ),
            'MP' => __( 'Northern Mariana Islands', 'super-directory' ),
            'PR' => __( 'Puerto Rico', 'super-directory' ),
            'VI' => __( 'U.S. Virgin Islands', 'super-directory' ),
        );
    }

    private function detect_bulk_delimiter( $sample ) {
        $comma     = substr_count( $sample, ',' );
        $tab       = substr_count( $sample, "\t" );
        $semicolon = substr_count( $sample, ';' );

        $max = max( $comma, $tab, $semicolon );

        if ( $max === $tab ) {
            return "\t";
        }

        if ( $max === $semicolon ) {
            return ';';
        }

        return ',';
    }

    private function normalize_bulk_header( $value ) {
        $value = strtolower( (string) $value );
        $value = preg_replace( '/^\xEF\xBB\xBF/', '', $value );
        $value = preg_replace( '/\s+/', ' ', $value );
        $value = trim( $value );

        return $value;
    }

    private function get_bulk_header_map() {
        return array(
            'resource/company/vendor name' => 'name',
            'category' => 'category',
            'website url' => 'website_url',
            'phone' => 'phone_number',
            'email' => 'email_address',
            'related trade/industry/vertical' => 'industry_vertical',
            'serving only local customers, virtual/national, or both?' => 'service_model',
            'state' => 'state',
            'city' => 'city',
            'street address' => 'street_address',
            'zip code' => 'zip_code',
            'short description/lead-in teaser sentence about the company (no longer thatn 98 characters, including spaces and punctuation)' => 'short_description',
            'what [company name] does - one or two paragraphs of text summarizing the company, what they do, who they help, how they help, the problems and challenges they solve, and a little bit of their history. can be multiple paragraphs and can include bullet points (<ul>), or numbered lists (<ol>). the total amount of text cannot exceed 770 characters, including all spaces and punctuation.' => 'long_description_primary',
            'why we recommend [company name] - more text discussing why we (superpath.com), recommends this company as a trusted and helpful resource for our home services clients. can be multiple paragraphs and can include bullet points (<ul>), or numbered lists (<ol>). the total amount of text cannot exceed 770 characters, including all spaces and punctuation.' => 'long_description_secondary',
            'facebook url' => 'facebook_url',
            'instagram url' => 'instagram_url',
            'youtube url' => 'youtube_url',
            'linkedin url' => 'linkedin_url',
            'google business listing url' => 'google_business_url',
            'logo wordpress media library attachment id #' => 'logo_attachment_id',
            'logo wordpress media library attatchment id #' => 'logo_attachment_id',
            'logo wordpress media library attatchement id #' => 'logo_attachment_id',
            'homepage screenshot wordpress media library attachment id #' => 'homepage_screenshot_id',
            'homepage screenshot wordpress media library attatchment id #' => 'homepage_screenshot_id',
            'homepage screenshot wordpress media library attatchement id #' => 'homepage_screenshot_id',
        );
    }

    private function get_us_states() {
        return array(
            __( 'Alabama', 'super-directory' ),
            __( 'Alaska', 'super-directory' ),
            __( 'Arizona', 'super-directory' ),
            __( 'Arkansas', 'super-directory' ),
            __( 'California', 'super-directory' ),
            __( 'Colorado', 'super-directory' ),
            __( 'Connecticut', 'super-directory' ),
            __( 'Delaware', 'super-directory' ),
            __( 'Florida', 'super-directory' ),
            __( 'Georgia', 'super-directory' ),
            __( 'Hawaii', 'super-directory' ),
            __( 'Idaho', 'super-directory' ),
            __( 'Illinois', 'super-directory' ),
            __( 'Indiana', 'super-directory' ),
            __( 'Iowa', 'super-directory' ),
            __( 'Kansas', 'super-directory' ),
            __( 'Kentucky', 'super-directory' ),
            __( 'Louisiana', 'super-directory' ),
            __( 'Maine', 'super-directory' ),
            __( 'Maryland', 'super-directory' ),
            __( 'Massachusetts', 'super-directory' ),
            __( 'Michigan', 'super-directory' ),
            __( 'Minnesota', 'super-directory' ),
            __( 'Mississippi', 'super-directory' ),
            __( 'Missouri', 'super-directory' ),
            __( 'Montana', 'super-directory' ),
            __( 'Nebraska', 'super-directory' ),
            __( 'Nevada', 'super-directory' ),
            __( 'New Hampshire', 'super-directory' ),
            __( 'New Jersey', 'super-directory' ),
            __( 'New Mexico', 'super-directory' ),
            __( 'New York', 'super-directory' ),
            __( 'North Carolina', 'super-directory' ),
            __( 'North Dakota', 'super-directory' ),
            __( 'Ohio', 'super-directory' ),
            __( 'Oklahoma', 'super-directory' ),
            __( 'Oregon', 'super-directory' ),
            __( 'Pennsylvania', 'super-directory' ),
            __( 'Rhode Island', 'super-directory' ),
            __( 'South Carolina', 'super-directory' ),
            __( 'South Dakota', 'super-directory' ),
            __( 'Tennessee', 'super-directory' ),
            __( 'Texas', 'super-directory' ),
            __( 'Utah', 'super-directory' ),
            __( 'Vermont', 'super-directory' ),
            __( 'Virginia', 'super-directory' ),
            __( 'Washington', 'super-directory' ),
            __( 'West Virginia', 'super-directory' ),
            __( 'Wisconsin', 'super-directory' ),
            __( 'Wyoming', 'super-directory' ),
        );
    }

    private function get_us_states_and_territories() {
        return array_merge(
            $this->get_us_states(),
            array(
                __( 'District of Columbia', 'super-directory' ),
                __( 'American Samoa', 'super-directory' ),
                __( 'Guam', 'super-directory' ),
                __( 'Northern Mariana Islands', 'super-directory' ),
                __( 'Puerto Rico', 'super-directory' ),
                __( 'U.S. Virgin Islands', 'super-directory' ),
            )
        );
    }
}
