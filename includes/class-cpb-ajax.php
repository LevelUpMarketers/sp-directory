<?php
/**
 * Handle Ajax operations with configurable minimum execution time.
 *
 * @package Codex_Plugin_Boilerplate
 */

class CPB_Ajax {

    public function register() {
        add_action( 'wp_ajax_cpb_save_main_entity', array( $this, 'save_main_entity' ) );
        add_action( 'wp_ajax_cpb_delete_main_entity', array( $this, 'delete_main_entity' ) );
        add_action( 'wp_ajax_cpb_read_main_entity', array( $this, 'read_main_entity' ) );
        add_action( 'wp_ajax_cpb_save_email_template', array( $this, 'save_email_template' ) );
        add_action( 'wp_ajax_cpb_send_test_email', array( $this, 'send_test_email' ) );
        add_action( 'wp_ajax_cpb_clear_email_log', array( $this, 'clear_email_log' ) );
    }

    private function maybe_delay( $start, $minimum_time = CPB_MIN_EXECUTION_TIME ) {
        if ( $minimum_time <= 0 ) {
            return;
        }

        $elapsed = microtime( true ) - $start;

        if ( $elapsed < $minimum_time ) {
            usleep( ( $minimum_time - $elapsed ) * 1000000 );
        }
    }

    public function save_main_entity() {
        $start = microtime( true );
        check_ajax_referer( 'cpb_ajax_nonce' );
        global $wpdb;
        $table = $wpdb->prefix . 'cpb_main_entity';
        $id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $now   = current_time( 'mysql' );

        $name = $this->sanitize_text_value( 'name' );

        if ( '' === $name ) {
            $name = $this->sanitize_text_value( 'placeholder_1' );
        }

        $state_options          = $this->get_us_states();
        $extended_state_options = $this->get_us_states_and_territories();
        $opt_in_keys            = array(
            'opt_in_marketing_email',
            'opt_in_marketing_sms',
            'opt_in_event_update_email',
            'opt_in_event_update_sms',
        );

        $data = array(
            'name'                        => $name,
            'placeholder_1'               => $this->sanitize_text_value( 'placeholder_1' ),
            'placeholder_2'               => $this->sanitize_text_value( 'placeholder_2' ),
            'placeholder_3'               => $this->sanitize_date_value( 'placeholder_3' ),
            'placeholder_4'               => $this->sanitize_select_value( 'placeholder_4', array( '0', '1' ) ),
            'placeholder_5'               => $this->sanitize_time_value( 'placeholder_5' ),
            'placeholder_6'               => $this->sanitize_time_value( 'placeholder_6' ),
            'placeholder_7'               => $this->sanitize_select_value( 'placeholder_7', array( '0', '1' ) ),
            'placeholder_8'               => $this->sanitize_text_value( 'placeholder_8' ),
            'placeholder_9'               => $this->sanitize_text_value( 'placeholder_9' ),
            'placeholder_10'              => $this->sanitize_text_value( 'placeholder_10' ),
            'placeholder_11'              => $this->sanitize_state_value( 'placeholder_11', $state_options ),
            'placeholder_12'              => $this->sanitize_text_value( 'placeholder_12' ),
            'placeholder_13'              => $this->sanitize_text_value( 'placeholder_13' ),
            'placeholder_14'              => $this->sanitize_url_value( 'placeholder_14' ),
            'placeholder_15'              => $this->sanitize_select_value( 'placeholder_15', array( 'option1', 'option2', 'option3' ) ),
            'placeholder_16'              => $this->sanitize_decimal_value( 'placeholder_16' ),
            'placeholder_17'              => $this->sanitize_decimal_value( 'placeholder_17' ),
            'placeholder_18'              => $this->sanitize_decimal_value( 'placeholder_18' ),
            'placeholder_19'              => $this->sanitize_select_value( 'placeholder_19', array( '0', '1' ) ),
            'placeholder_20'              => $this->sanitize_select_value( 'placeholder_20', array( '0', '1' ) ),
            'placeholder_21'              => $this->sanitize_state_value( 'placeholder_21', $extended_state_options ),
            'placeholder_22'              => $this->sanitize_text_value( 'placeholder_22' ),
            'placeholder_23'              => $this->sanitize_select_value( 'placeholder_23', array( 'option1', 'option2', 'option3' ) ),
            'placeholder_24'              => $this->sanitize_opt_in_summary( $opt_in_keys ),
            'placeholder_25'              => $this->sanitize_items_value( 'placeholder_25' ),
            'placeholder_26'              => $this->sanitize_color_value( 'placeholder_26' ),
            'placeholder_27'              => $this->sanitize_image_value( 'placeholder_27' ),
            'placeholder_28'              => $this->sanitize_editor_value( 'placeholder_28' ),
            'resource_logo_id'            => $this->sanitize_image_value( 'resource_logo_id' ),
            'resource_gallery_ids'        => $this->sanitize_gallery_value( 'resource_gallery_ids' ),
            'opt_in_marketing_email'      => $this->sanitize_checkbox_value( 'opt_in_marketing_email' ),
            'opt_in_marketing_sms'        => $this->sanitize_checkbox_value( 'opt_in_marketing_sms' ),
            'opt_in_event_update_email'   => $this->sanitize_checkbox_value( 'opt_in_event_update_email' ),
            'opt_in_event_update_sms'     => $this->sanitize_checkbox_value( 'opt_in_event_update_sms' ),
            'updated_at'                  => $now,
        );

        $formats = array_fill( 0, count( $data ), '%s' );

        if ( $id > 0 ) {
            $result  = $wpdb->update( $table, $data, array( 'id' => $id ), $formats, array( '%d' ) );
            $message = __( 'Changes saved.', 'codex-plugin-boilerplate' );

            if ( false === $result && $wpdb->last_error ) {
                $this->maybe_delay( $start );
                wp_send_json_error(
                    array(
                        'message' => __( 'Unable to save changes. Please try again.', 'codex-plugin-boilerplate' ),
                    )
                );
            }
        } else {
            $data['created_at'] = $now;
            $formats[]          = '%s';
            $result             = $wpdb->insert( $table, $data, $formats );
            $message            = __( 'Saved', 'codex-plugin-boilerplate' );

            if ( false === $result ) {
                $this->maybe_delay( $start );
                wp_send_json_error(
                    array(
                        'message' => __( 'Unable to save the record. Please try again.', 'codex-plugin-boilerplate' ),
                    )
                );
            }
        }

        $this->maybe_delay( $start );
        wp_send_json_success( array( 'message' => $message ) );
    }

    public function delete_main_entity() {
        $start = microtime( true );
        check_ajax_referer( 'cpb_ajax_nonce' );
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        global $wpdb;
        $table = $wpdb->prefix . 'cpb_main_entity';
        $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
        $this->maybe_delay( $start );
        wp_send_json_success( array( 'message' => __( 'Deleted', 'codex-plugin-boilerplate' ) ) );
    }

    public function read_main_entity() {
        $start = microtime( true );
        check_ajax_referer( 'cpb_ajax_nonce' );
        global $wpdb;
        $table    = $wpdb->prefix . 'cpb_main_entity';
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
            $opt_in_keys = array(
                'opt_in_marketing_email',
                'opt_in_marketing_sms',
                'opt_in_event_update_email',
                'opt_in_event_update_sms',
            );

            $entities = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table ORDER BY placeholder_1 ASC, id ASC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );

            foreach ( $entities as &$entity ) {
                $entity['placeholder_3']  = $this->format_date_for_response( $entity['placeholder_3'] );
                $entity['placeholder_5']  = $this->format_time_for_response( $entity['placeholder_5'] );
                $entity['placeholder_6']  = $this->format_time_for_response( $entity['placeholder_6'] );
                $entity['placeholder_16'] = $this->format_decimal_for_response( $entity['placeholder_16'] );
                $entity['placeholder_17'] = $this->format_decimal_for_response( $entity['placeholder_17'] );
                $entity['placeholder_18'] = $this->format_decimal_for_response( $entity['placeholder_18'] );
                $entity['placeholder_24'] = $this->format_json_field( $entity['placeholder_24'] );
                $entity['placeholder_25'] = $this->format_json_field( $entity['placeholder_25'] );
                $entity['placeholder_26'] = $this->format_color_for_response( $entity['placeholder_26'] );
                $entity['placeholder_27'] = (string) absint( $entity['placeholder_27'] );
                $entity['placeholder_27_url'] = $this->get_attachment_url( $entity['placeholder_27'] );
                $entity['placeholder_28'] = $this->format_editor_content_for_response( $entity['placeholder_28'] );
                $logo_id = isset( $entity['resource_logo_id'] ) ? $entity['resource_logo_id'] : 0;
                $entity['resource_logo_id'] = (string) absint( $logo_id );
                $entity['resource_logo_id_url'] = $this->get_attachment_url( $entity['resource_logo_id'] );
                $gallery_ids = $this->normalize_gallery_ids_value( isset( $entity['resource_gallery_ids'] ) ? $entity['resource_gallery_ids'] : '' );
                $entity['resource_gallery_ids'] = wp_json_encode( $gallery_ids );
                $entity['resource_gallery_ids_items'] = $this->get_gallery_items_for_response( $gallery_ids );

                foreach ( array( 'placeholder_4', 'placeholder_7', 'placeholder_19', 'placeholder_20', 'opt_in_marketing_email', 'opt_in_marketing_sms', 'opt_in_event_update_email', 'opt_in_event_update_sms' ) as $bool_key ) {
                    if ( isset( $entity[ $bool_key ] ) ) {
                        $entity[ $bool_key ] = (string) ( (int) $entity[ $bool_key ] );
                    }
                }

                if ( ! isset( $entity['placeholder_21'] ) ) {
                    $entity['placeholder_21'] = '';
                }

                if ( ! isset( $entity['placeholder_22'] ) ) {
                    $entity['placeholder_22'] = '';
                }

                if ( ! isset( $entity['placeholder_23'] ) ) {
                    $entity['placeholder_23'] = '';
                }

                if ( ! isset( $entity['placeholder_24'] ) ) {
                    $entity['placeholder_24'] = wp_json_encode( array() );
                }

                if ( ! isset( $entity['placeholder_25'] ) ) {
                    $entity['placeholder_25'] = wp_json_encode( array() );
                }

                foreach ( $opt_in_keys as $opt_in_key ) {
                    if ( ! isset( $entity[ $opt_in_key ] ) ) {
                        $entity[ $opt_in_key ] = '0';
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

    public function save_email_template() {
        $start = microtime( true );
        check_ajax_referer( 'cpb_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'You are not allowed to perform this action.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        $template_id = isset( $_POST['template_id'] ) ? sanitize_key( wp_unslash( $_POST['template_id'] ) ) : '';

        if ( '' === $template_id ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'Invalid template selection.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        $from_name = isset( $_POST['from_name'] ) ? CPB_Email_Template_Helper::sanitize_from_name( wp_unslash( $_POST['from_name'] ) ) : '';
        $from_email = isset( $_POST['from_email'] ) ? CPB_Email_Template_Helper::sanitize_from_email( wp_unslash( $_POST['from_email'] ) ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
        $body    = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : '';
        $sms     = isset( $_POST['sms'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sms'] ) ) : '';

        CPB_Email_Template_Helper::update_template_settings(
            $template_id,
            array(
                'from_name'  => $from_name,
                'from_email' => $from_email,
                'subject'    => $subject,
                'body'       => $body,
                'sms'        => $sms,
            )
        );

        $this->maybe_delay( $start );
        wp_send_json_success(
            array(
                'message' => __( 'Template saved.', 'codex-plugin-boilerplate' ),
            )
        );
    }

    public function send_test_email() {
        $start = microtime( true );
        check_ajax_referer( 'cpb_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'You are not allowed to perform this action.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        $template_id = isset( $_POST['template_id'] ) ? sanitize_key( wp_unslash( $_POST['template_id'] ) ) : '';

        if ( '' === $template_id ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'Invalid template selection.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        $to_email = isset( $_POST['to_email'] ) ? sanitize_email( wp_unslash( $_POST['to_email'] ) ) : '';

        if ( ! $to_email || ! is_email( $to_email ) ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'Please provide a valid email address.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        $from_name = isset( $_POST['from_name'] ) ? CPB_Email_Template_Helper::sanitize_from_name( wp_unslash( $_POST['from_name'] ) ) : '';
        $from_email = isset( $_POST['from_email'] ) ? CPB_Email_Template_Helper::sanitize_from_email( wp_unslash( $_POST['from_email'] ) ) : '';
        $subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
        $body    = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : '';

        $stored_settings = CPB_Email_Template_Helper::get_template_settings( $template_id );

        if ( '' === $from_name && isset( $stored_settings['from_name'] ) ) {
            $from_name = CPB_Email_Template_Helper::sanitize_from_name( $stored_settings['from_name'] );
        }

        if ( '' === $from_email && isset( $stored_settings['from_email'] ) ) {
            $from_email = CPB_Email_Template_Helper::sanitize_from_email( $stored_settings['from_email'] );
        }

        $from_name  = CPB_Email_Template_Helper::resolve_from_name( $from_name );
        $from_email = CPB_Email_Template_Helper::resolve_from_email( $from_email );

        $tokens = CPB_Main_Entity_Helper::get_first_preview_data();

        if ( ! empty( $tokens ) ) {
            $subject = $this->replace_template_tokens( $subject, $tokens );
            $body    = $this->replace_template_tokens( $body, $tokens );
        }

        $rendered_body = $body;

        if ( $rendered_body && ! preg_match( '/<[a-z][\s\S]*>/i', $rendered_body ) ) {
            $rendered_body = nl2br( esc_html( $rendered_body ) );
        }

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        $from_header = CPB_Email_Template_Helper::build_from_header( $from_name, $from_email );

        if ( $from_header ) {
            $headers[] = $from_header;
        }
        $sent    = wp_mail( $to_email, $subject, $rendered_body, $headers );

        $this->maybe_delay( $start );

        if ( ! $sent ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Unable to send the test email. Please try again.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        $current_user = wp_get_current_user();
        $triggered_by = '';

        if ( $current_user instanceof WP_User && $current_user->exists() ) {
            $name = $current_user->display_name ? $current_user->display_name : $current_user->user_login;
            $identifier = $current_user->user_login;

            if ( $identifier && $identifier !== $name ) {
                $name .= ' (' . $identifier . ')';
            }

            if ( $current_user->user_email ) {
                $name .= ' <' . $current_user->user_email . '>';
            }

            $triggered_by = $name;
        }

        CPB_Email_Log_Helper::log_email(
            array(
                'template_id'    => $template_id,
                'template_title' => CPB_Email_Template_Helper::get_template_label( $template_id ),
                'recipient'      => $to_email,
                'from_name'      => $from_name,
                'from_email'     => $from_email,
                'subject'        => $subject,
                'body'           => $rendered_body,
                'context'        => __( 'Test email', 'codex-plugin-boilerplate' ),
                'triggered_by'   => $triggered_by,
            )
        );

        wp_send_json_success(
            array(
                'message' => __( 'Test email sent.', 'codex-plugin-boilerplate' ),
            )
        );
    }

    public function clear_email_log() {
        $start = microtime( true );
        check_ajax_referer( 'cpb_ajax_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'You are not allowed to perform this action.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        if ( ! CPB_Email_Log_Helper::is_log_available() ) {
            $this->maybe_delay( $start );
            wp_send_json_error(
                array(
                    'message' => __( 'Email logging is unavailable. Check directory permissions and try again.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        $cleared = CPB_Email_Log_Helper::clear_log();

        $this->maybe_delay( $start );

        if ( ! $cleared ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Unable to clear the email log. Please try again.', 'codex-plugin-boilerplate' ),
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Email log cleared.', 'codex-plugin-boilerplate' ),
            )
        );
    }

    private function get_email_templates_option_name() {
        /**
         * Filter the option name used to store email template settings.
         *
         * @param string $option_name Default option name.
         */
        return CPB_Email_Template_Helper::get_option_name();
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

    private function sanitize_date_value( $key ) {
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

        $date = date_create_from_format( 'Y-m-d', $value );

        if ( ! $date ) {
            return '';
        }

        return $date->format( 'Y-m-d' );
    }

    private function sanitize_time_value( $key ) {
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

        if ( preg_match( '/^(\d{2}):(\d{2})$/', $value, $matches ) ) {
            $hours = (int) $matches[1];
            $mins  = (int) $matches[2];

            $hours = max( 0, min( 23, $hours ) );
            $mins  = max( 0, min( 59, $mins ) );

            return sprintf( '%02d:%02d', $hours, $mins );
        }

        return '';
    }

    private function sanitize_decimal_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '0.00';
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        $value = trim( (string) $value );

        if ( '' === $value ) {
            return '0.00';
        }

        $normalized = preg_replace( '/[^0-9\-\.]/', '', $value );

        if ( '' === $normalized ) {
            return '0.00';
        }

        return number_format( (float) $normalized, 2, '.', '' );
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

    private function sanitize_checkbox_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '0';
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return ! empty( $value ) ? '1' : '0';
    }

    private function sanitize_opt_in_summary( $keys ) {
        $selected = array();

        foreach ( $keys as $key ) {
            if ( '1' === $this->sanitize_checkbox_value( $key ) ) {
                $selected[] = $key;
            }
        }

        return wp_json_encode( $selected );
    }

    private function sanitize_items_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return wp_json_encode( array() );
        }

        $items = array();

        if ( is_array( $value ) ) {
            foreach ( $value as $item ) {
                $item = sanitize_text_field( $item );

                if ( '' !== $item ) {
                    $items[] = $item;
                }
            }
        } else {
            $value = sanitize_textarea_field( $value );
            $split = preg_split( '/\r?\n/', $value );

            if ( is_array( $split ) ) {
                foreach ( $split as $item ) {
                    $item = trim( $item );

                    if ( '' !== $item ) {
                        $items[] = $item;
                    }
                }
            }
        }

        return wp_json_encode( $items );
    }

    private function sanitize_color_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        $value = sanitize_hex_color( $value );

        return $value ? $value : '';
    }

    private function sanitize_image_value( $key ) {
        $value = $this->get_post_value( $key );

        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return (string) absint( $value );
    }

    private function sanitize_gallery_value( $key ) {
        $value = $this->get_post_value( $key );
        $ids   = $this->normalize_gallery_ids_value( $value );

        return wp_json_encode( $ids );
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

    private function normalize_gallery_ids_value( $value ) {
        if ( null === $value ) {
            return array();
        }

        $ids = array();

        if ( is_array( $value ) ) {
            $ids = $value;
        } else {
            $string_value = trim( (string) $value );

            if ( '' === $string_value ) {
                return array();
            }

            $decoded = json_decode( $string_value, true );

            if ( is_array( $decoded ) ) {
                $ids = $decoded;
            } else {
                $ids = explode( ',', $string_value );
            }
        }

        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );
        $ids = array_values( array_unique( $ids ) );

        return $ids;
    }

    private function format_date_for_response( $value ) {
        if ( empty( $value ) || '0000-00-00' === $value ) {
            return '';
        }

        $date = date_create( $value );

        if ( ! $date ) {
            return '';
        }

        return $date->format( 'Y-m-d' );
    }

    private function format_time_for_response( $value ) {
        if ( empty( $value ) || '00:00:00' === $value ) {
            return '';
        }

        if ( preg_match( '/^(\d{2}:\d{2})/', $value, $matches ) ) {
            return $matches[1];
        }

        return '';
    }

    private function format_decimal_for_response( $value ) {
        if ( null === $value || '' === $value ) {
            return '0.00';
        }

        return number_format( (float) $value, 2, '.', '' );
    }

    private function format_json_field( $value ) {
        if ( empty( $value ) ) {
            return wp_json_encode( array() );
        }

        if ( is_array( $value ) ) {
            return wp_json_encode( array_values( $value ) );
        }

        $decoded = json_decode( $value, true );

        if ( is_array( $decoded ) ) {
            return wp_json_encode( array_values( $decoded ) );
        }

        return wp_json_encode( array() );
    }

    private function format_color_for_response( $value ) {
        $value = sanitize_hex_color( $value );

        if ( ! $value ) {
            return '';
        }

        return $value;
    }

    private function format_editor_content_for_response( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        return wp_kses_post( $value );
    }

    private function get_gallery_items_for_response( $ids ) {
        if ( ! is_array( $ids ) ) {
            $ids = $this->normalize_gallery_ids_value( $ids );
        }

        if ( empty( $ids ) ) {
            return array();
        }

        $items = array();

        foreach ( $ids as $id ) {
            $url = $this->get_attachment_url( $id );

            if ( ! $url ) {
                continue;
            }

            $items[] = array(
                'id'  => (string) absint( $id ),
                'url' => $url,
            );
        }

        return $items;
    }

    private function get_attachment_url( $attachment_id ) {
        $attachment_id = absint( $attachment_id );

        if ( ! $attachment_id ) {
            return '';
        }

        $url = wp_get_attachment_url( $attachment_id );

        if ( ! $url ) {
            return '';
        }

        return esc_url_raw( $url );
    }

    private function get_us_states() {
        return array(
            __( 'Alabama', 'codex-plugin-boilerplate' ),
            __( 'Alaska', 'codex-plugin-boilerplate' ),
            __( 'Arizona', 'codex-plugin-boilerplate' ),
            __( 'Arkansas', 'codex-plugin-boilerplate' ),
            __( 'California', 'codex-plugin-boilerplate' ),
            __( 'Colorado', 'codex-plugin-boilerplate' ),
            __( 'Connecticut', 'codex-plugin-boilerplate' ),
            __( 'Delaware', 'codex-plugin-boilerplate' ),
            __( 'Florida', 'codex-plugin-boilerplate' ),
            __( 'Georgia', 'codex-plugin-boilerplate' ),
            __( 'Hawaii', 'codex-plugin-boilerplate' ),
            __( 'Idaho', 'codex-plugin-boilerplate' ),
            __( 'Illinois', 'codex-plugin-boilerplate' ),
            __( 'Indiana', 'codex-plugin-boilerplate' ),
            __( 'Iowa', 'codex-plugin-boilerplate' ),
            __( 'Kansas', 'codex-plugin-boilerplate' ),
            __( 'Kentucky', 'codex-plugin-boilerplate' ),
            __( 'Louisiana', 'codex-plugin-boilerplate' ),
            __( 'Maine', 'codex-plugin-boilerplate' ),
            __( 'Maryland', 'codex-plugin-boilerplate' ),
            __( 'Massachusetts', 'codex-plugin-boilerplate' ),
            __( 'Michigan', 'codex-plugin-boilerplate' ),
            __( 'Minnesota', 'codex-plugin-boilerplate' ),
            __( 'Mississippi', 'codex-plugin-boilerplate' ),
            __( 'Missouri', 'codex-plugin-boilerplate' ),
            __( 'Montana', 'codex-plugin-boilerplate' ),
            __( 'Nebraska', 'codex-plugin-boilerplate' ),
            __( 'Nevada', 'codex-plugin-boilerplate' ),
            __( 'New Hampshire', 'codex-plugin-boilerplate' ),
            __( 'New Jersey', 'codex-plugin-boilerplate' ),
            __( 'New Mexico', 'codex-plugin-boilerplate' ),
            __( 'New York', 'codex-plugin-boilerplate' ),
            __( 'North Carolina', 'codex-plugin-boilerplate' ),
            __( 'North Dakota', 'codex-plugin-boilerplate' ),
            __( 'Ohio', 'codex-plugin-boilerplate' ),
            __( 'Oklahoma', 'codex-plugin-boilerplate' ),
            __( 'Oregon', 'codex-plugin-boilerplate' ),
            __( 'Pennsylvania', 'codex-plugin-boilerplate' ),
            __( 'Rhode Island', 'codex-plugin-boilerplate' ),
            __( 'South Carolina', 'codex-plugin-boilerplate' ),
            __( 'South Dakota', 'codex-plugin-boilerplate' ),
            __( 'Tennessee', 'codex-plugin-boilerplate' ),
            __( 'Texas', 'codex-plugin-boilerplate' ),
            __( 'Utah', 'codex-plugin-boilerplate' ),
            __( 'Vermont', 'codex-plugin-boilerplate' ),
            __( 'Virginia', 'codex-plugin-boilerplate' ),
            __( 'Washington', 'codex-plugin-boilerplate' ),
            __( 'West Virginia', 'codex-plugin-boilerplate' ),
            __( 'Wisconsin', 'codex-plugin-boilerplate' ),
            __( 'Wyoming', 'codex-plugin-boilerplate' ),
        );
    }

    private function get_us_states_and_territories() {
        return array_merge(
            $this->get_us_states(),
            array(
                __( 'District of Columbia', 'codex-plugin-boilerplate' ),
                __( 'American Samoa', 'codex-plugin-boilerplate' ),
                __( 'Guam', 'codex-plugin-boilerplate' ),
                __( 'Northern Mariana Islands', 'codex-plugin-boilerplate' ),
                __( 'Puerto Rico', 'codex-plugin-boilerplate' ),
                __( 'U.S. Virgin Islands', 'codex-plugin-boilerplate' ),
            )
        );
    }
}
