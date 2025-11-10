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
                    'message' => __( 'Please provide a Resource / Company / Vendor Name.', 'super-directory' ),
                )
            );
        }

        $state_options          = $this->get_us_states();
        $extended_state_options = $this->get_us_states_and_territories();
        $category_keys          = $this->get_directory_category_keys();
        $service_model_keys     = $this->get_service_model_keys();

        $data = array(
            'name'                     => $name,
            'category'                 => $this->sanitize_select_value( 'category', $category_keys ),
            'industry_vertical'        => $this->sanitize_text_value( 'industry_vertical' ),
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
            'updated_at'               => $now,
        );

        $formats = array_fill( 0, count( $data ), '%s' );

        if ( $id > 0 ) {
            $result  = $wpdb->update( $table, $data, array( 'id' => $id ), $formats, array( '%d' ) );
            $message = __( 'Changes saved.', 'super-directory' );

            if ( false === $result && $wpdb->last_error ) {
                $this->maybe_delay( $start );
                wp_send_json_error(
                    array(
                        'message' => __( 'Unable to save changes. Please try again.', 'super-directory' ),
                    )
                );
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
        }

        $this->maybe_delay( $start );
        wp_send_json_success( array( 'message' => $message ) );
    }

    public function delete_main_entity() {
        $start = microtime( true );
        check_ajax_referer( 'sd_ajax_nonce' );
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        global $wpdb;
        $table = $wpdb->prefix . 'sd_main_entity';
        $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
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

    private function format_editor_content_for_response( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        return wp_kses_post( $value );
    }

    private function get_directory_category_keys() {
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

        $keys = array();

        foreach ( $options as $value => $label ) {
            $keys[] = sanitize_key( $value );
        }

        return array_values( array_unique( $keys ) );
    }

    private function get_service_model_keys() {
        $options = array(
            'local'  => __( 'Local Customers Only', 'super-directory' ),
            'virtual' => __( 'Virtual / National', 'super-directory' ),
            'both'    => __( 'Both Local & National', 'super-directory' ),
        );

        $options = apply_filters( 'sd_directory_service_models', $options );

        $keys = array();

        foreach ( $options as $value => $label ) {
            $keys[] = sanitize_key( $value );
        }

        return array_values( array_unique( $keys ) );
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
