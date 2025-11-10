<?php
/**
 * Admin pages for SuperDirectory
 *
 * @package SuperDirectory
 */

class SD_Admin {

    public function register() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_sd_delete_generated_content', array( $this, 'handle_delete_generated_content' ) );
        add_action( 'admin_post_sd_delete_cron_event', array( $this, 'handle_delete_cron_event' ) );
        add_action( 'admin_post_sd_run_cron_event', array( $this, 'handle_run_cron_event' ) );
        add_action( 'admin_post_sd_download_email_log', array( $this, 'handle_download_email_log' ) );
    }

    public function add_menu() {
        add_menu_page(
            __( 'SuperDirectory Directory', 'super-directory' ),
            __( 'SuperDirectory Directory', 'super-directory' ),
            'manage_options',
            'sd-main-entity',
            array( $this, 'render_main_entity_page' )
        );

        add_menu_page(
            __( 'SuperDirectory Settings', 'super-directory' ),
            __( 'SuperDirectory Settings', 'super-directory' ),
            'manage_options',
            'sd-settings',
            array( $this, 'render_settings_page' )
        );

        add_menu_page(
            __( 'SuperDirectory Communications', 'super-directory' ),
            __( 'SuperDirectory Communications', 'super-directory' ),
            'manage_options',
            'sd-communications',
            array( $this, 'render_communications_page' )
        );

        add_menu_page(
            __( 'SuperDirectory Logs', 'super-directory' ),
            __( 'SuperDirectory Logs', 'super-directory' ),
            'manage_options',
            'sd-logs',
            array( $this, 'render_logs_page' )
        );
    }

    public function render_communications_page() {
        $tabs = array(
            'email-templates' => __( 'Email Templates', 'super-directory' ),
            'email-logs'      => __( 'Email Logs', 'super-directory' ),
            'sms-templates'   => __( 'SMS Templates', 'super-directory' ),
            'sms-logs'        => __( 'SMS Logs', 'super-directory' ),
        );

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'email-templates';

        if ( ! array_key_exists( $active_tab, $tabs ) ) {
            $active_tab = 'email-templates';
        }

        echo '<div class="wrap"><h1>' . esc_html__( 'SuperDirectory Communications', 'super-directory' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';

        foreach ( $tabs as $tab_slug => $label ) {
            $classes = array( 'nav-tab' );

            if ( $tab_slug === $active_tab ) {
                $classes[] = 'nav-tab-active';
            }

            printf(
                '<a href="%1$s" class="%2$s">%3$s</a>',
                esc_url( add_query_arg( array( 'page' => 'sd-communications', 'tab' => $tab_slug ), admin_url( 'admin.php' ) ) ),
                esc_attr( implode( ' ', $classes ) ),
                esc_html( $label )
            );
        }

        echo '</h2>';

        $this->top_message_center();

        $tab_descriptions = array(
            'email-templates' => __( 'Review placeholder email templates that demonstrate how communications can be grouped for future automation requests.', 'super-directory' ),
            'email-logs'      => __( 'Review detailed delivery history for plugin-generated emails and export the log for troubleshooting.', 'super-directory' ),
            'sms-templates'   => __( 'Prepare SMS templates that mirror your email workflows so every touchpoint stays consistent.', 'super-directory' ),
            'sms-logs'        => __( 'Audit sent SMS messages and spot delivery issues as soon as log data becomes available.', 'super-directory' ),
        );

        $description = isset( $tab_descriptions[ $active_tab ] ) ? $tab_descriptions[ $active_tab ] : '';

        $this->render_tab_intro( $tabs[ $active_tab ], $description );

        if ( 'email-templates' === $active_tab ) {
            $this->render_email_templates_tab();
        } elseif ( 'email-logs' === $active_tab ) {
            $this->render_email_logs_tab();
        } elseif ( 'sms-templates' === $active_tab ) {
            $this->render_communications_placeholder_tab(
                __( 'SMS template management is coming soon.', 'super-directory' )
            );
        } else {
            $this->render_communications_placeholder_tab(
                __( 'SMS log history is coming soon.', 'super-directory' )
            );
        }

        $this->bottom_message_center();
        echo '</div>';
    }

    private function render_email_templates_tab() {
        $templates   = $this->get_sample_email_templates();
        foreach ( $templates as $template ) {
            if ( isset( $template['id'], $template['title'] ) ) {
                SD_Email_Template_Helper::register_template_label( $template['id'], $template['title'] );
            }
        }
        $meta_labels = array(
            'trigger'             => __( 'Trigger', 'super-directory' ),
            'communication_type'  => __( 'Communication Type', 'super-directory' ),
            'category'            => __( 'Category', 'super-directory' ),
        );
        $meta_order  = array( 'trigger', 'communication_type', 'category' );
        $column_count = count( $meta_order ) + 2; // Title and actions columns.

        echo '<div class="sd-communications sd-communications--email-templates">';
        echo '<div class="sd-accordion-group sd-accordion-group--table" data-sd-accordion-group="communications">';
        echo '<table class="wp-list-table widefat striped sd-accordion-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col" class="sd-accordion__heading sd-accordion__heading--title">' . esc_html__( 'Communication Name', 'super-directory' ) . '</th>';

        foreach ( $meta_order as $meta_key ) {
            if ( ! isset( $meta_labels[ $meta_key ] ) ) {
                continue;
            }

            printf(
                '<th scope="col" class="sd-accordion__heading sd-accordion__heading--%1$s">%2$s</th>',
                esc_attr( $meta_key ),
                esc_html( $meta_labels[ $meta_key ] )
            );
        }

        echo '<th scope="col" class="sd-accordion__heading sd-accordion__heading--actions">' . esc_html__( 'Actions', 'super-directory' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ( $templates as $template ) {
            $item_id    = sanitize_html_class( $template['id'] );
            $panel_id   = $item_id . '-panel';
            $header_id  = $item_id . '-header';
            $tooltip    = isset( $template['tooltip'] ) ? $template['tooltip'] : '';
            $meta_items = isset( $template['meta'] ) ? $template['meta'] : array();

            printf(
                '<tr id="%1$s" class="sd-accordion__summary-row" tabindex="0" role="button" aria-expanded="false" aria-controls="%2$s">',
                esc_attr( $header_id ),
                esc_attr( $panel_id )
            );

            echo '<td class="sd-accordion__cell sd-accordion__cell--title">';

            if ( $tooltip ) {
                printf(
                    '<span class="dashicons dashicons-info sd-tooltip-icon" aria-hidden="true" data-tooltip="%1$s"></span><span class="screen-reader-text">%2$s</span>',
                    esc_attr( $tooltip ),
                    esc_html( $tooltip )
                );
            }

            echo '<span class="sd-accordion__title-text">' . esc_html( $template['title'] ) . '</span>';
            echo '</td>';

            foreach ( $meta_order as $meta_key ) {
                $label      = isset( $meta_labels[ $meta_key ] ) ? $meta_labels[ $meta_key ] : '';
                $meta_value = isset( $meta_items[ $meta_key ] ) ? $meta_items[ $meta_key ] : '';

                echo '<td class="sd-accordion__cell sd-accordion__cell--meta">';

                if ( $label ) {
                    printf(
                        '<span class="sd-accordion__meta-text"><span class="sd-accordion__meta-label">%1$s:</span> <span class="sd-accordion__meta-value">%2$s</span></span>',
                        esc_html( $label ),
                        $meta_value ? esc_html( $meta_value ) : '&mdash;'
                    );
                }

                echo '</td>';
            }

            echo '<td class="sd-accordion__cell sd-accordion__cell--actions">';
            echo '<span class="sd-accordion__action-link" aria-hidden="true">' . esc_html__( 'Edit', 'super-directory' ) . '</span>';
            echo '<span class="dashicons dashicons-arrow-down-alt2 sd-accordion__icon" aria-hidden="true"></span>';
            echo '<span class="screen-reader-text">' . esc_html__( 'Toggle template details', 'super-directory' ) . '</span>';
            echo '</td>';
            echo '</tr>';

            printf(
                '<tr id="%1$s" class="sd-accordion__panel-row" role="region" aria-labelledby="%2$s" aria-hidden="true">',
                esc_attr( $panel_id ),
                esc_attr( $header_id )
            );
            printf(
                '<td colspan="%1$d">',
                absint( $column_count )
            );
            echo '<div class="sd-accordion__panel">';
            $this->render_email_template_panel( $template );
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    }

    private function render_email_template_panel( $template ) {
        if ( isset( $template['id'] ) && 'sd-email-welcome' === $template['id'] ) {
            $this->render_welcome_email_template_panel( $template );
            return;
        }

        if ( isset( $template['content'] ) ) {
            echo '<p>' . esc_html( $template['content'] ) . '</p>';
        }
    }

    private function render_welcome_email_template_panel( $template ) {
        $template_id   = isset( $template['id'] ) ? $template['id'] : 'sd-email-welcome';
        $field_prefix  = sanitize_html_class( $template_id );
        $from_name_id  = $field_prefix . '-from-name';
        $from_email_id = $field_prefix . '-from-email';
        $subject_id    = $field_prefix . '-subject';
        $body_id       = $field_prefix . '-body';
        $sms_id        = $field_prefix . '-sms';
        $token_groups       = $this->get_main_entity_token_groups();
        $template_settings  = $this->get_email_template_settings( $template_id );
        $from_name_value    = isset( $template_settings['from_name'] ) ? $template_settings['from_name'] : '';
        $from_email_value   = isset( $template_settings['from_email'] ) ? $template_settings['from_email'] : '';
        $subject_value      = isset( $template_settings['subject'] ) ? $template_settings['subject'] : '';
        $body_value         = isset( $template_settings['body'] ) ? $template_settings['body'] : '';
        $sms_value          = isset( $template_settings['sms'] ) ? $template_settings['sms'] : '';
        $default_from_name  = SD_Email_Template_Helper::get_default_from_name();
        $default_from_email = SD_Email_Template_Helper::get_default_from_email();
        $preview_data       = SD_Main_Entity_Helper::get_first_preview_data();
        $has_preview        = ! empty( $preview_data );
        $save_spinner_id    = $field_prefix . '-save-spinner';
        $save_feedback_id   = $field_prefix . '-save-feedback';
        $test_email_id      = $field_prefix . '-test-email';
        $test_spinner_id    = $field_prefix . '-test-spinner';
        $test_feedback_id   = $field_prefix . '-test-feedback';

        $preview_notice = $has_preview
            ? __( 'Enter a subject or body to generate the preview.', 'super-directory' )
            : __( 'Add a Directory Listing entry to generate a preview.', 'super-directory' );

        echo '<div class="sd-template-editor" data-template="' . esc_attr( $template_id ) . '">';

        echo '<div class="sd-template-editor__fields">';

        printf(
            '<div class="sd-template-editor__field"><label for="%1$s">%2$s</label><input type="text" id="%1$s" name="templates[%3$s][from_name]" class="regular-text" data-template-field="from_name" value="%4$s" placeholder="%5$s" autocomplete="name"></div>',
            esc_attr( $from_name_id ),
            esc_html__( 'Email From Name', 'super-directory' ),
            esc_attr( $template_id ),
            esc_attr( $from_name_value ),
            esc_attr( $default_from_name )
        );

        printf(
            '<div class="sd-template-editor__field"><label for="%1$s">%2$s</label><input type="email" id="%1$s" name="templates[%3$s][from_email]" class="regular-text" data-template-field="from_email" value="%4$s" placeholder="%5$s" autocomplete="email"></div>',
            esc_attr( $from_email_id ),
            esc_html__( 'Email From Address', 'super-directory' ),
            esc_attr( $template_id ),
            esc_attr( $from_email_value ),
            esc_attr( $default_from_email )
        );

        printf(
            '<div class="sd-template-editor__field"><label for="%1$s">%2$s</label><input type="text" id="%1$s" name="templates[%3$s][subject]" class="regular-text sd-token-target" data-token-context="subject" value="%4$s"></div>',
            esc_attr( $subject_id ),
            esc_html__( 'Email Subject', 'super-directory' ),
            esc_attr( $template_id ),
            esc_attr( $subject_value )
        );

        printf(
            '<div class="sd-template-editor__field"><label for="%1$s">%2$s</label><textarea id="%1$s" name="templates[%3$s][body]" rows="8" class="widefat sd-token-target" data-token-context="body">%4$s</textarea></div>',
            esc_attr( $body_id ),
            esc_html__( 'Email Body', 'super-directory' ),
            esc_attr( $template_id ),
            esc_textarea( $body_value )
        );

        printf(
            '<div class="sd-template-editor__field"><label for="%1$s">%2$s</label><textarea id="%1$s" name="templates[%3$s][sms]" rows="4" class="widefat sd-token-target" data-token-context="sms">%4$s</textarea></div>',
            esc_attr( $sms_id ),
            esc_html__( 'SMS Text', 'super-directory' ),
            esc_attr( $template_id ),
            esc_textarea( $sms_value )
        );

        echo '<div class="sd-template-preview" aria-live="polite">';
        echo '<h3 class="sd-template-preview__title">' . esc_html__( 'Email Preview', 'super-directory' ) . '</h3>';
        echo '<p class="sd-template-preview__notice">' . esc_html( $preview_notice ) . '</p>';
        echo '<div class="sd-template-preview__content" data-preview-role="content">';
        echo '<p class="sd-template-preview__subject"><span class="sd-template-preview__label">' . esc_html__( 'Subject:', 'super-directory' ) . '</span> <span class="sd-template-preview__value" data-preview-field="subject"></span></p>';
        echo '<div class="sd-template-preview__body" data-preview-field="body"></div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="sd-template-editor__test">';
        printf(
            '<button type="button" class="button button-primary sd-template-test-send" data-template="%1$s" data-email-input="#%2$s" data-spinner="#%3$s" data-feedback="#%4$s">%5$s</button>',
            esc_attr( $template_id ),
            esc_attr( $test_email_id ),
            esc_attr( $test_spinner_id ),
            esc_attr( $test_feedback_id ),
            esc_html__( 'Send Test Email', 'super-directory' )
        );
        echo '<div class="sd-template-editor__test-input">';
        printf(
            '<label class="screen-reader-text" for="%1$s">%2$s</label><input type="email" id="%1$s" class="regular-text sd-template-test-email" placeholder="%3$s" autocomplete="off">',
            esc_attr( $test_email_id ),
            esc_html__( 'Test email address', 'super-directory' ),
            esc_attr__( 'Enter an Email Address', 'super-directory' )
        );
        echo '</div>';
        printf(
            '<span class="sd-feedback-area sd-feedback-area--inline"><span id="%1$s" class="spinner sd-template-spinner" aria-hidden="true"></span><span id="%2$s" class="sd-template-feedback" role="status" aria-live="polite"></span></span>',
            esc_attr( $test_spinner_id ),
            esc_attr( $test_feedback_id )
        );
        echo '</div>';

        echo '<div class="sd-template-editor__actions">';
        printf(
            '<button type="button" class="button button-primary sd-template-save" data-template="%1$s" data-spinner="#%2$s" data-feedback="#%3$s">%4$s</button>',
            esc_attr( $template_id ),
            esc_attr( $save_spinner_id ),
            esc_attr( $save_feedback_id ),
            esc_html__( 'Save Template', 'super-directory' )
        );
        printf(
            '<span class="sd-feedback-area sd-feedback-area--inline"><span id="%1$s" class="spinner sd-template-spinner" aria-hidden="true"></span><span id="%2$s" class="sd-template-feedback" role="status" aria-live="polite"></span></span>',
            esc_attr( $save_spinner_id ),
            esc_attr( $save_feedback_id )
        );
        echo '</div>';

        echo '</div>';

        if ( ! empty( $token_groups ) ) {
            echo '<div class="sd-template-editor__tokens">';
            echo '<h3 class="sd-template-editor__tokens-heading">' . esc_html__( 'Tokens', 'super-directory' ) . '</h3>';

            foreach ( $token_groups as $group ) {
                if ( empty( $group['tokens'] ) ) {
                    continue;
                }

                echo '<div class="sd-token-group">';

                if ( ! empty( $group['title'] ) ) {
                    echo '<h4 class="sd-token-group__title">' . esc_html( $group['title'] ) . '</h4>';
                }

                echo '<div class="sd-token-group__buttons">';

                foreach ( $group['tokens'] as $token ) {
                    if ( empty( $token['value'] ) ) {
                        continue;
                    }

                    $label = isset( $token['label'] ) ? $token['label'] : $token['value'];

                    printf(
                        '<button type="button" class="button button-secondary sd-token-button" data-token="%1$s">%2$s</button>',
                        esc_attr( $token['value'] ),
                        esc_html( $label )
                    );
                }

                echo '</div>';
                echo '</div>';
            }

            echo '</div>';
        }

        echo '</div>';
    }

    private function get_main_entity_token_groups() {
        $labels      = $this->get_placeholder_labels();
        $token_group = array(
            'title'  => __( 'Directory Listing Information', 'super-directory' ),
            'tokens' => array(),
        );

        foreach ( $labels as $key => $label ) {
            $token_group['tokens'][] = array(
                'value' => '{' . $key . '}',
                'label' => $label,
            );
        }

        /**
         * Filter the token groups displayed for communications templates.
         *
         * This filter allows child plugins to add new token collections or adjust
         * the existing Directory Listing defaults when repurposing the boilerplate for
         * client-specific data models.
         *
         * @param array $groups Array of token group definitions. Each group should contain
         *                      a `title` and a `tokens` list where every token includes
         *                      `value` (the merge tag) and `label` (the admin-facing text).
         */
        $groups = apply_filters( 'sd_communications_token_groups', array( $token_group ) );

        return array_map( array( $this, 'normalize_token_group' ), $groups );
    }

    private function get_email_templates_option_name() {
        return SD_Email_Template_Helper::get_option_name();
    }

    private function get_email_template_settings( $template_id ) {
        $template_id = sanitize_key( $template_id );

        if ( '' === $template_id ) {
            return array();
        }

        return SD_Email_Template_Helper::get_template_settings( $template_id );
    }

    private function normalize_token_group( $group ) {
        if ( ! is_array( $group ) ) {
            return array(
                'title'  => '',
                'tokens' => array(),
            );
        }

        $title  = isset( $group['title'] ) ? $group['title'] : '';
        $tokens = isset( $group['tokens'] ) && is_array( $group['tokens'] ) ? $group['tokens'] : array();

        $normalized_tokens = array();

        foreach ( $tokens as $token ) {
            if ( ! is_array( $token ) || empty( $token['value'] ) ) {
                continue;
            }

            $normalized_tokens[] = array(
                'value' => (string) $token['value'],
                'label' => isset( $token['label'] ) ? (string) $token['label'] : (string) $token['value'],
            );
        }

        return array(
            'title'  => (string) $title,
            'tokens' => $normalized_tokens,
        );
    }

    private function render_email_logs_tab() {
        $log_available = SD_Email_Log_Helper::is_log_available();
        $entries       = $log_available ? SD_Email_Log_Helper::get_log_entries() : array();
        $empty_message = __( 'No email activity has been recorded yet.', 'super-directory' );
        $time_notice   = __( 'Timestamps display Eastern United States time.', 'super-directory' );
        $clear_label   = __( 'Clear log', 'super-directory' );
        $download_label = __( 'Download log file', 'super-directory' );
        $sent_format   = __( 'Sent %s', 'super-directory' );
        $not_available = __( 'Email logging is unavailable. Confirm that WordPress can write to the uploads directory.', 'super-directory' );
        $body_empty    = __( 'No body content recorded.', 'super-directory' );

        $empty_classes = 'sd-email-log__empty';
        $empty_hidden  = '';

        if ( empty( $entries ) ) {
            $empty_classes .= ' is-visible';
        } else {
            $empty_hidden = ' hidden';
        }

        echo '<div class="sd-communications sd-communications--email-logs">';

        if ( ! $log_available ) {
            echo '<div class="notice notice-error inline"><p>' . esc_html( $not_available ) . '</p></div>';
        }

        echo '<div class="sd-email-log">';
        echo '<p class="description">' . esc_html( $time_notice ) . '</p>';
        echo '<div id="sd-email-log-list" class="sd-email-log__list" data-empty-message="' . esc_attr( $empty_message ) . '">';
        echo '<p id="sd-email-log-empty" class="' . esc_attr( $empty_classes ) . '"' . $empty_hidden . '>' . esc_html( $empty_message ) . '</p>';

        foreach ( $entries as $entry ) {
            $template_title   = isset( $entry['template_title'] ) ? trim( $entry['template_title'] ) : '';
            $template_id      = isset( $entry['template_id'] ) ? $entry['template_id'] : '';
            $template_display = $template_title;

            if ( '' === $template_display && isset( $entry['template_display'] ) ) {
                $template_display = trim( $entry['template_display'] );
            }

            if ( '' === $template_display ) {
                $template_display = $template_id ? $template_id : __( 'Email template', 'super-directory' );
            }

            if ( $template_id && false === strpos( $template_display, $template_id ) ) {
                $template_display .= ' (' . $template_id . ')';
            }

            $time_display = isset( $entry['time_display'] ) ? $entry['time_display'] : '';
            $recipient    = isset( $entry['recipient'] ) ? $entry['recipient'] : '';
            $from_name    = isset( $entry['from_name'] ) ? $entry['from_name'] : '';
            $from_email   = isset( $entry['from_email'] ) ? $entry['from_email'] : '';
            $subject      = isset( $entry['subject'] ) ? $entry['subject'] : '';
            $context      = isset( $entry['context'] ) ? $entry['context'] : '';
            $triggered_by = isset( $entry['triggered_by'] ) ? $entry['triggered_by'] : '';
            $body         = isset( $entry['body'] ) ? $entry['body'] : '';

            echo '<article class="sd-email-log__entry">';
            echo '<header class="sd-email-log__header">';
            echo '<h3 class="sd-email-log__title">' . esc_html( $template_display ) . '</h3>';

            if ( $time_display ) {
                printf(
                    '<p class="sd-email-log__time">%s</p>',
                    esc_html( sprintf( $sent_format, $time_display ) )
                );
            }

            echo '</header>';

            $meta_items = array(
                array(
                    'label' => __( 'Sent (ET)', 'super-directory' ),
                    'value' => $time_display,
                ),
                array(
                    'label' => __( 'Recipient', 'super-directory' ),
                    'value' => $recipient,
                ),
                array(
                    'label' => __( 'From name', 'super-directory' ),
                    'value' => $from_name,
                ),
                array(
                    'label' => __( 'From email', 'super-directory' ),
                    'value' => $from_email,
                ),
                array(
                    'label' => __( 'Subject', 'super-directory' ),
                    'value' => $subject,
                ),
            );

            if ( $template_id ) {
                $meta_items[] = array(
                    'label' => __( 'Template ID', 'super-directory' ),
                    'value' => $template_id,
                );
            }

            if ( $context ) {
                $meta_items[] = array(
                    'label' => __( 'Context', 'super-directory' ),
                    'value' => $context,
                );
            }

            if ( $triggered_by ) {
                $meta_items[] = array(
                    'label' => __( 'Initiated by', 'super-directory' ),
                    'value' => $triggered_by,
                );
            }

            echo '<dl class="sd-email-log__meta">';

            foreach ( $meta_items as $item ) {
                $label = isset( $item['label'] ) ? $item['label'] : '';
                $value = isset( $item['value'] ) ? $item['value'] : '';

                echo '<div class="sd-email-log__meta-item">';
                echo '<dt>' . esc_html( $label ) . '</dt>';
                echo '<dd>' . esc_html( '' !== trim( $value ) ? $value : '—' ) . '</dd>';
                echo '</div>';
            }

            echo '</dl>';

            if ( '' !== $body ) {
                echo '<div class="sd-email-log__body" aria-label="' . esc_attr__( 'Email body', 'super-directory' ) . '">';
                echo wp_kses_post( nl2br( esc_html( $body ) ) );
                echo '</div>';
            } else {
                echo '<div class="sd-email-log__body sd-email-log__body--empty">' . esc_html( $body_empty ) . '</div>';
            }

            echo '</article>';
        }

        echo '</div>';

        $disabled_attr      = ' disabled="disabled" aria-disabled="true"';
        $clear_disabled    = $log_available ? '' : $disabled_attr;
        $download_disabled = $log_available ? '' : $disabled_attr;

        echo '<div class="sd-email-log__actions">';
        echo '<button type="button" class="button button-secondary sd-email-log__clear" data-spinner="#sd-email-log-spinner" data-feedback="#sd-email-log-feedback"' . $clear_disabled . '>' . esc_html( $clear_label ) . '</button>';
        echo '<form method="post" class="sd-email-log__download" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'sd_download_email_log', 'sd_email_log_nonce' );
        echo '<input type="hidden" name="action" value="sd_download_email_log" />';
        echo '<button type="submit" class="button button-secondary"' . $download_disabled . '>' . esc_html( $download_label ) . '</button>';
        echo '</form>';
        echo '<span class="spinner sd-email-log__spinner" id="sd-email-log-spinner"></span>';
        echo '<p class="sd-email-log__feedback" id="sd-email-log-feedback" aria-live="polite"></p>';
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    private function render_communications_placeholder_tab( $message ) {
        echo '<div class="sd-communications sd-communications--placeholder">';
        echo '<p>' . esc_html( $message ) . '</p>';
        echo '</div>';
    }

    private function get_sample_email_templates() {
        return array(
            array(
                'id'       => 'sd-email-welcome',
                'title'    => __( 'Welcome Aboard', 'super-directory' ),
                'tooltip'  => __( 'Sent after a customer signs up to introduce key onboarding steps.', 'super-directory' ),
                'meta'     => array(
                    'trigger'            => __( 'New registration', 'super-directory' ),
                    'communication_type' => __( 'External', 'super-directory' ),
                    'category'           => __( 'Onboarding', 'super-directory' ),
                ),
                'content'  => __( 'Test text', 'super-directory' ),
            ),
            array(
                'id'       => 'sd-email-follow-up',
                'title'    => __( 'Consultation Follow Up', 'super-directory' ),
                'tooltip'  => __( 'Delivers recap notes and next steps after a discovery call wraps up.', 'super-directory' ),
                'meta'     => array(
                    'trigger'            => __( 'Completed consultation', 'super-directory' ),
                    'communication_type' => __( 'External', 'super-directory' ),
                    'category'           => __( 'Sales Enablement', 'super-directory' ),
                ),
                'content'  => __( 'Test text', 'super-directory' ),
            ),
            array(
                'id'       => 'sd-email-renewal',
                'title'    => __( 'Membership Renewal Reminder', 'super-directory' ),
                'tooltip'  => __( 'Warns members that their plan expires soon and outlines renewal options.', 'super-directory' ),
                'meta'     => array(
                    'trigger'            => __( 'Approaching renewal date', 'super-directory' ),
                    'communication_type' => __( 'External', 'super-directory' ),
                    'category'           => __( 'Retention', 'super-directory' ),
                ),
                'content'  => __( 'Test text', 'super-directory' ),
            ),
            array(
                'id'       => 'sd-email-alert',
                'title'    => __( 'Internal Alert: Payment Review', 'super-directory' ),
                'tooltip'  => __( 'Flags the support team when a payment requires manual approval.', 'super-directory' ),
                'meta'     => array(
                    'trigger'            => __( 'Payment pending review', 'super-directory' ),
                    'communication_type' => __( 'Internal', 'super-directory' ),
                    'category'           => __( 'Operations', 'super-directory' ),
                ),
                'content'  => __( 'Test text', 'super-directory' ),
            ),
        );
    }

    public function enqueue_assets( $hook ) {
        if ( false === strpos( $hook, 'sd' ) ) {
            return;
        }
        wp_enqueue_style( 'sd-admin', SD_PLUGIN_URL . 'assets/css/admin.css', array(), SD_VERSION );
        wp_enqueue_script( 'sd-admin', SD_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SD_VERSION, true );
        wp_enqueue_media();
        wp_enqueue_editor();

        $placeholder_labels = $this->get_placeholder_labels();
        $field_definitions  = $this->prepare_main_entity_fields_for_js();

        wp_localize_script( 'sd-admin', 'sdAjax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'sd_ajax_nonce' ),
        ) );
        wp_localize_script( 'sd-admin', 'sdAdmin', array(
            'placeholders' => array_values( $placeholder_labels ),
            'placeholderMap' => $placeholder_labels,
            'delete'       => __( 'Delete', 'super-directory' ),
            'none'         => __( 'No entries found.', 'super-directory' ),
            'mediaTitle'   => __( 'Select Image', 'super-directory' ),
            'mediaButton'  => __( 'Use this image', 'super-directory' ),
            'itemPlaceholder' => __( 'Item #%d', 'super-directory' ),
            'addAnotherItem' => __( '+ Add Another Item', 'super-directory' ),
            'makeSelection' => __( 'Make a Selection...', 'super-directory' ),
            'error'        => __( 'Something went wrong. Please try again.', 'super-directory' ),
            'loadError'    => __( 'Unable to load records. Please try again.', 'super-directory' ),
            'totalRecords' => __( 'Total records: %s', 'super-directory' ),
            'pageOf'       => __( 'Page %1$s of %2$s', 'super-directory' ),
            'firstPage'    => __( 'First page', 'super-directory' ),
            'prevPage'     => __( 'Previous page', 'super-directory' ),
            'nextPage'     => __( 'Next page', 'super-directory' ),
            'lastPage'     => __( 'Last page', 'super-directory' ),
            'toggleDetails' => __( 'Toggle entity details', 'super-directory' ),
            'nameLabel'    => __( 'Name', 'super-directory' ),
            'editAction'   => __( 'Edit', 'super-directory' ),
            'saveChanges'  => __( 'Save Changes', 'super-directory' ),
            'entityFields' => $field_definitions,
            'editorSettings' => $this->get_inline_editor_settings(),
            'previewEntity' => SD_Main_Entity_Helper::get_first_preview_data(),
            'previewEmptyMessage' => __( 'Enter a subject or body to generate the preview.', 'super-directory' ),
            'previewUnavailableMessage' => __( 'Add a Directory Listing entry to generate a preview.', 'super-directory' ),
            'testEmailRequired' => __( 'Enter an email address before sending a test.', 'super-directory' ),
            'testEmailSuccess'  => __( 'Test email sent.', 'super-directory' ),
            'emailLogCleared'   => __( 'Email log cleared.', 'super-directory' ),
            'emailLogError'     => __( 'Unable to clear the email log. Please try again.', 'super-directory' ),
            'emailLogEmpty'     => __( 'No email activity has been recorded yet.', 'super-directory' ),
        ) );
    }

    private function get_placeholder_labels() {
        static $labels = null;

        if ( null === $labels ) {
            $labels = array();

            for ( $i = 1; $i <= 28; $i++ ) {
                $labels[ 'placeholder_' . $i ] = sprintf( __( 'Placeholder %d', 'super-directory' ), $i );
            }

            /**
             * Allow customizing placeholder labels across the admin experience when cloning the plugin.
             *
             * Updating this filter ensures the edit table, creation form, and localized JavaScript
             * all stay in sync when Placeholder 1 becomes "Resource Name", "Student Name", etc.
             *
             * @param array $labels Associative array of placeholder slugs to labels.
             */
            $labels = apply_filters( 'sd_main_entity_placeholder_labels', $labels );
        }

        return $labels;
    }

    private function get_placeholder_label( $index ) {
        $labels = $this->get_placeholder_labels();
        $key    = 'placeholder_' . absint( $index );

        if ( isset( $labels[ $key ] ) ) {
            return $labels[ $key ];
        }

        return sprintf( __( 'Placeholder %d', 'super-directory' ), absint( $index ) );
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

    private function get_tooltips() {
        $tooltips = array();
        for ( $i = 1; $i <= 28; $i++ ) {
            $tooltips[ 'placeholder_' . $i ] = sprintf(
                __( 'Tooltip placeholder text for Placeholder %d', 'super-directory' ),
                $i
            );
        }
        return $tooltips;
    }

    private function top_message_center() {
        echo '<div class="sd-top-message">';
        echo '<div class="sd-top-row">';
        echo '<div class="sd-top-left">';
        echo '<h3>' . esc_html__( 'Need help? Watch the Tutorial video!', 'super-directory' ) . '</h3>';
        echo '<div class="sd-video-container"><iframe width="100%" height="200" src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
        echo '</div>';
        echo '<div class="sd-top-right">';
        echo '<h3>' . esc_html__( 'Upgrade to Premium Today', 'super-directory' ) . '</h3>';
        $upgrade_text = sprintf(
            __( 'Upgrade to the Premium version of SuperDirectory today and receive additional features, options, priority customer support, and a dedicated hour of setup and customization! %s', 'super-directory' ),
            '<a href="https://levelupmarketers.com" target="_blank">' . esc_html__( 'Click here to upgrade now.', 'super-directory' ) . '</a>'
        );
        echo '<p>' . wp_kses_post( $upgrade_text ) . '</p>';
        echo '<a class="sd-upgrade-button" href="https://levelupmarketers.com" target="_blank">' . esc_html__( 'Upgrade Now', 'super-directory' ) . '</a>';
        echo '<a href="https://levelupmarketers.com" target="_blank"><img src="' . esc_url( SD_PLUGIN_URL . 'assets/images/levelup-logo.svg' ) . '" alt="' . esc_attr__( 'Level Up Digital Marketing logo', 'super-directory' ) . '" class="sd-premium-logo" /></a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function bottom_message_center() {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin_data = get_plugin_data( SD_PLUGIN_DIR . 'sp-directory.php' );
        $plugin_name = $plugin_data['Name'];

        echo '<div class="sd-top-message sd-bottom-message-digital-marketing-section">';
        echo '<div class="sd-top-logo-row">';
        echo '<a href="https://levelupmarketers.com" target="_blank"><img src="' . esc_url( SD_PLUGIN_URL . 'assets/images/levelup-logo.svg' ) . '" alt="' . esc_attr__( 'Level Up Digital Marketing logo', 'super-directory' ) . '" class="sd-premium-logo" /></a>';
        $thanks = sprintf(
            /* translators: %s: Plugin name. */
            __( 'Thanks <span class="sd-so-much">SO MUCH</span> for using %s - a Level Up plugin!', 'super-directory' ),
            esc_html( $plugin_name )
        );
        echo '<p class="sd-thanks-message">' . wp_kses_post( $thanks ) . '</p>';
        $tagline = sprintf(
            __( 'Need marketing or custom software development help? Email %1$s or call %2$s now!', 'super-directory' ),
            '<a href="mailto:contact@levelupmarketers.com">contact@levelupmarketers.com</a>',
            '<a href="tel:18044898188">(804) 489-8188</a>'
        );
        echo '<p class="sd-top-tagline">' . wp_kses_post( $tagline ) . '</p>';
        echo '</div>';
        echo '</div>';
    }

    private function render_tab_intro( $title, $description ) {
        if ( empty( $title ) && empty( $description ) ) {
            return;
        }

        echo '<div class="sd-tab-intro">';

        if ( $title ) {
            echo '<h2 class="sd-tab-intro__title">' . esc_html( $title ) . '</h2>';
        }

        if ( $description ) {
            echo '<p class="sd-tab-intro__description">' . esc_html( $description ) . '</p>';
        }

        echo '</div>';
    }

    public function render_main_entity_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'create';
        echo '<div class="wrap"><h1>' . esc_html__( 'SuperDirectory Directory', 'super-directory' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=sd-main-entity&tab=create" class="nav-tab ' . ( 'create' === $active_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Create a Directory Listing', 'super-directory' ) . '</a>';
        echo '<a href="?page=sd-main-entity&tab=edit" class="nav-tab ' . ( 'edit' === $active_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Edit Directory Listing', 'super-directory' ) . '</a>';
        echo '</h2>';
        $this->top_message_center();

        $tab_titles = array(
            'create' => __( 'Create a Directory Listing', 'super-directory' ),
            'edit'   => __( 'Edit Directory Listing', 'super-directory' ),
        );

        $tab_descriptions = array(
            'create' => __( 'Build a new directory listing record by completing the placeholder fields and saving your changes.', 'super-directory' ),
            'edit'   => __( 'Review saved entities to confirm their data, trigger edits, or remove records you no longer need.', 'super-directory' ),
        );

        $title       = isset( $tab_titles[ $active_tab ] ) ? $tab_titles[ $active_tab ] : '';
        $description = isset( $tab_descriptions[ $active_tab ] ) ? $tab_descriptions[ $active_tab ] : '';

        $this->render_tab_intro( $title, $description );

        if ( 'edit' === $active_tab ) {
            $this->render_edit_tab();
        } else {
            $this->render_create_tab();
        }

        $this->bottom_message_center();
        echo '</div>';
    }

    private function get_main_entity_fields() {
        $tooltips = $this->get_tooltips();
        $fields    = array(
            array(
                'name'    => 'placeholder_1',
                'label'   => $this->get_placeholder_label( 1 ),
                'type'    => 'text',
                'tooltip' => $tooltips['placeholder_1'],
            ),
            array(
                'name'    => 'placeholder_2',
                'label'   => $this->get_placeholder_label( 2 ),
                'type'    => 'text',
                'tooltip' => $tooltips['placeholder_2'],
            ),
            array(
                'name'    => 'placeholder_3',
                'label'   => $this->get_placeholder_label( 3 ),
                'type'    => 'date',
                'tooltip' => $tooltips['placeholder_3'],
            ),
            array(
                'name'    => 'placeholder_4',
                'label'   => $this->get_placeholder_label( 4 ),
                'type'    => 'select',
                'options' => array(
                    ''  => __( 'Make a Selection...', 'super-directory' ),
                    '0' => __( 'No', 'super-directory' ),
                    '1' => __( 'Yes', 'super-directory' ),
                ),
                'tooltip' => $tooltips['placeholder_4'],
            ),
            array(
                'name'    => 'placeholder_5',
                'label'   => $this->get_placeholder_label( 5 ),
                'type'    => 'time',
                'tooltip' => $tooltips['placeholder_5'],
            ),
            array(
                'name'    => 'placeholder_6',
                'label'   => $this->get_placeholder_label( 6 ),
                'type'    => 'time',
                'tooltip' => $tooltips['placeholder_6'],
            ),
            array(
                'name'    => 'placeholder_7',
                'label'   => $this->get_placeholder_label( 7 ),
                'type'    => 'select',
                'options' => array(
                    ''  => __( 'Make a Selection...', 'super-directory' ),
                    '0' => __( 'No', 'super-directory' ),
                    '1' => __( 'Yes', 'super-directory' ),
                ),
                'tooltip' => $tooltips['placeholder_7'],
            ),
            array(
                'name'    => 'placeholder_8',
                'label'   => $this->get_placeholder_label( 8 ),
                'type'    => 'text',
                'tooltip' => $tooltips['placeholder_8'],
            ),
            array(
                'name'    => 'placeholder_9',
                'label'   => $this->get_placeholder_label( 9 ),
                'type'    => 'text',
                'tooltip' => $tooltips['placeholder_9'],
            ),
            array(
                'name'    => 'placeholder_10',
                'label'   => $this->get_placeholder_label( 10 ),
                'type'    => 'text',
                'tooltip' => $tooltips['placeholder_10'],
            ),
            array(
                'name'    => 'placeholder_11',
                'label'   => $this->get_placeholder_label( 11 ),
                'type'    => 'state',
                'options' => $this->get_us_states(),
                'tooltip' => $tooltips['placeholder_11'],
            ),
            array(
                'name'    => 'placeholder_12',
                'label'   => $this->get_placeholder_label( 12 ),
                'type'    => 'text',
                'tooltip' => $tooltips['placeholder_12'],
            ),
            array(
                'name'    => 'placeholder_13',
                'label'   => $this->get_placeholder_label( 13 ),
                'type'    => 'text',
                'tooltip' => $tooltips['placeholder_13'],
            ),
            array(
                'name'    => 'placeholder_14',
                'label'   => $this->get_placeholder_label( 14 ),
                'type'    => 'url',
                'tooltip' => $tooltips['placeholder_14'],
            ),
            array(
                'name'    => 'placeholder_15',
                'label'   => $this->get_placeholder_label( 15 ),
                'type'    => 'select',
                'options' => array(
                    ''        => __( 'Make a Selection...', 'super-directory' ),
                    'option1' => __( 'Option 1', 'super-directory' ),
                    'option2' => __( 'Option 2', 'super-directory' ),
                    'option3' => __( 'Option 3', 'super-directory' ),
                ),
                'tooltip' => $tooltips['placeholder_15'],
            ),
            array(
                'name'    => 'placeholder_16',
                'label'   => $this->get_placeholder_label( 16 ),
                'type'    => 'number',
                'attrs'   => 'step="0.01" min="0"',
                'tooltip' => $tooltips['placeholder_16'],
            ),
            array(
                'name'    => 'placeholder_17',
                'label'   => $this->get_placeholder_label( 17 ),
                'type'    => 'number',
                'attrs'   => 'step="0.01" min="0"',
                'tooltip' => $tooltips['placeholder_17'],
            ),
            array(
                'name'    => 'placeholder_18',
                'label'   => $this->get_placeholder_label( 18 ),
                'type'    => 'number',
                'attrs'   => 'step="0.01" min="0"',
                'tooltip' => $tooltips['placeholder_18'],
            ),
            array(
                'name'    => 'placeholder_19',
                'label'   => $this->get_placeholder_label( 19 ),
                'type'    => 'select',
                'options' => array(
                    ''  => __( 'Make a Selection...', 'super-directory' ),
                    '0' => __( 'No', 'super-directory' ),
                    '1' => __( 'Yes', 'super-directory' ),
                ),
                'tooltip' => $tooltips['placeholder_19'],
            ),
            array(
                'name'    => 'placeholder_20',
                'label'   => $this->get_placeholder_label( 20 ),
                'type'    => 'select',
                'options' => array(
                    ''  => __( 'Make a Selection...', 'super-directory' ),
                    '0' => __( 'No', 'super-directory' ),
                    '1' => __( 'Yes', 'super-directory' ),
                ),
                'tooltip' => $tooltips['placeholder_20'],
            ),
            array(
                'name'    => 'placeholder_21',
                'label'   => $this->get_placeholder_label( 21 ),
                'type'    => 'state',
                'options' => $this->get_us_states_and_territories(),
                'tooltip' => $tooltips['placeholder_21'],
            ),
            array(
                'name'    => 'placeholder_22',
                'label'   => $this->get_placeholder_label( 22 ),
                'type'    => 'text',
                'tooltip' => $tooltips['placeholder_22'],
            ),
            array(
                'name'    => 'placeholder_23',
                'label'   => $this->get_placeholder_label( 23 ),
                'type'    => 'radio',
                'options' => array(
                    'option1' => array(
                        'label'   => __( 'Option 1', 'super-directory' ),
                        'tooltip' => __( 'Tooltip placeholder text for Placeholder 22 Option 1', 'super-directory' ),
                    ),
                    'option2' => array(
                        'label'   => __( 'Option 2', 'super-directory' ),
                        'tooltip' => __( 'Tooltip placeholder text for Placeholder 22 Option 2', 'super-directory' ),
                    ),
                    'option3' => array(
                        'label'   => __( 'Option 3', 'super-directory' ),
                        'tooltip' => __( 'Tooltip placeholder text for Placeholder 22 Option 3', 'super-directory' ),
                    ),
                ),
                'tooltip' => $tooltips['placeholder_23'],
            ),
            array(
                'name'    => 'placeholder_24',
                'label'   => $this->get_placeholder_label( 24 ),
                'type'    => 'opt_in',
                'tooltip' => $tooltips['placeholder_24'],
                'options' => array(
                    array(
                        'name'    => 'opt_in_marketing_email',
                        'label'   => __( 'Option 1', 'super-directory' ),
                        'tooltip' => __( 'Tooltip placeholder text for Placeholder 23 Option 1', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'opt_in_marketing_sms',
                        'label'   => __( 'Option 2', 'super-directory' ),
                        'tooltip' => __( 'Tooltip placeholder text for Placeholder 23 Option 2', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'opt_in_event_update_email',
                        'label'   => __( 'Option 3', 'super-directory' ),
                        'tooltip' => __( 'Tooltip placeholder text for Placeholder 23 Option 3', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'opt_in_event_update_sms',
                        'label'   => __( 'Option 4', 'super-directory' ),
                        'tooltip' => __( 'Tooltip placeholder text for Placeholder 23 Option 4', 'super-directory' ),
                    ),
                ),
            ),
            array(
                'name'    => 'placeholder_25',
                'label'   => $this->get_placeholder_label( 25 ),
                'type'    => 'items',
                'tooltip' => $tooltips['placeholder_25'],
            ),
            array(
                'name'    => 'placeholder_26',
                'label'   => $this->get_placeholder_label( 26 ),
                'type'    => 'color',
                'attrs'   => 'value="#000000"',
                'tooltip' => $tooltips['placeholder_26'],
            ),
            array(
                'name'    => 'placeholder_27',
                'label'   => $this->get_placeholder_label( 27 ),
                'type'    => 'image',
                'tooltip' => $tooltips['placeholder_27'],
            ),
            array(
                'name'    => 'placeholder_28',
                'label'   => $this->get_placeholder_label( 28 ),
                'type'    => 'editor',
                'tooltip' => $tooltips['placeholder_28'],
                'full_width' => true,
            ),
        );
        return $fields;
    }

    private function prepare_main_entity_fields_for_js() {
        $fields    = $this->get_main_entity_fields();
        $prepared  = array();

        foreach ( $fields as $field ) {
            $prepared_field = array(
                'name'      => $field['name'],
                'type'      => $field['type'],
                'label'     => $field['label'],
                'tooltip'   => $field['tooltip'],
                'fullWidth' => ! empty( $field['full_width'] ),
            );

            if ( isset( $field['options'] ) ) {
                $prepared_field['options'] = $field['options'];
            }

            if ( isset( $field['attrs'] ) ) {
                $prepared_field['attrs'] = $field['attrs'];
            }

            $prepared[] = $prepared_field;
        }

        return $prepared;
    }

    private function get_inline_editor_settings() {
        $default_settings = array(
            'tinymce'   => array(
                'wpautop' => true,
            ),
            'quicktags' => true,
        );

        if ( function_exists( 'wp_get_editor_settings' ) ) {
            $settings = wp_get_editor_settings( 'placeholder_28', array( 'textarea_name' => 'placeholder_28' ) );

            if ( is_array( $settings ) ) {
                return $settings;
            }
        }

        return $default_settings;
    }

    private function render_create_tab() {
        $fields = $this->get_main_entity_fields();

        echo '<form id="sd-create-form"><div class="sd-flex-form">';
        foreach ( $fields as $field ) {
            $classes = 'sd-field';
            if ( ! empty( $field['full_width'] ) ) {
                $classes .= ' sd-field-full';
            }
            echo '<div class="' . $classes . '">';
            echo '<label><span class="sd-tooltip-icon dashicons dashicons-editor-help" data-tooltip="' . esc_attr( $field['tooltip'] ) . '"></span>' . esc_html( $field['label'] ) . '</label>';
            switch ( $field['type'] ) {
                case 'select':
                    echo '<select name="' . esc_attr( $field['name'] ) . '">';
                    foreach ( $field['options'] as $value => $label ) {
                        if ( '' === $value ) {
                            echo '<option value="" disabled selected>' . esc_html( $label ) . '</option>';
                        } else {
                            echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
                        }
                    }
                    echo '</select>';
                    break;
                case 'state':
                    $states = isset( $field['options'] ) ? $field['options'] : $this->get_us_states();
                    echo '<select name="' . esc_attr( $field['name'] ) . '">';
                    echo '<option value="" disabled selected>' . esc_html__( 'Make a Selection...', 'super-directory' ) . '</option>';
                    foreach ( $states as $state ) {
                        echo '<option value="' . esc_attr( $state ) . '">' . esc_html( $state ) . '</option>';
                    }
                    echo '</select>';
                    break;
                case 'radio':
                    foreach ( $field['options'] as $value => $opt ) {
                        echo '<label class="sd-radio-option"><input type="radio" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '" />';
                        echo ' <span class="sd-tooltip-icon dashicons dashicons-editor-help" data-tooltip="' . esc_attr( $opt['tooltip'] ) . '"></span>';
                        echo esc_html( $opt['label'] ) . '</label>';
                    }
                    break;
                case 'editor':
                    wp_editor( '', $field['name'], array( 'textarea_name' => $field['name'] ) );
                    break;
                case 'opt_in':
                    $opts = isset( $field['options'] ) ? $field['options'] : array();

                    if ( empty( $opts ) ) {
                        $opts = array(
                            array(
                                'name'    => 'opt_in_marketing_email',
                                'label'   => __( 'Option 1', 'super-directory' ),
                                'tooltip' => __( 'Tooltip placeholder text for Placeholder 23 Option 1', 'super-directory' ),
                            ),
                            array(
                                'name'    => 'opt_in_marketing_sms',
                                'label'   => __( 'Option 2', 'super-directory' ),
                                'tooltip' => __( 'Tooltip placeholder text for Placeholder 23 Option 2', 'super-directory' ),
                            ),
                            array(
                                'name'    => 'opt_in_event_update_email',
                                'label'   => __( 'Option 3', 'super-directory' ),
                                'tooltip' => __( 'Tooltip placeholder text for Placeholder 23 Option 3', 'super-directory' ),
                            ),
                            array(
                                'name'    => 'opt_in_event_update_sms',
                                'label'   => __( 'Option 4', 'super-directory' ),
                                'tooltip' => __( 'Tooltip placeholder text for Placeholder 23 Option 4', 'super-directory' ),
                            ),
                        );
                    }

                    echo '<fieldset>';
                    foreach ( $opts as $opt ) {
                        echo '<label class="sd-opt-in-option"><input type="checkbox" name="' . esc_attr( $opt['name'] ) . '" value="1" />';
                        echo ' <span class="sd-tooltip-icon dashicons dashicons-editor-help" data-tooltip="' . esc_attr( $opt['tooltip'] ) . '"></span>';
                        echo esc_html( $opt['label'] ) . '</label>';
                    }
                    echo '</fieldset>';
                    break;
                case 'items':
                    echo '<div id="sd-items-container" class="sd-items-container" data-placeholder="' . esc_attr( $field['name'] ) . '">';
                    echo '<div class="sd-item-row" style="margin-bottom:8px; display:flex; align-items:center;">';
                    echo '<input type="text" name="' . esc_attr( $field['name'] ) . '[]" class="regular-text sd-item-field" placeholder="' . esc_attr__( 'Item #1', 'super-directory' ) . '" />';
                    echo '</div></div>';
                    echo '<button type="button" class="button sd-add-item" id="sd-add-item" data-target="#sd-items-container" style="margin-top:8px;">' . esc_html__( '+ Add Another Item', 'super-directory' ) . '</button>';
                    break;
                case 'textarea':
                    echo '<textarea name="' . esc_attr( $field['name'] ) . '"></textarea>';
                    break;
                case 'image':
                    echo '<input type="hidden" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['name'] ) . '" />';
                    echo '<button type="button" class="button sd-upload" data-target="#' . esc_attr( $field['name'] ) . '">' . esc_html__( 'Select Image', 'super-directory' ) . '</button>';
                    echo '<div id="' . esc_attr( $field['name'] ) . '-preview" style="margin-top:10px;"></div>';
                    break;
                default:
                    $attrs = isset( $field['attrs'] ) ? ' ' . $field['attrs'] : '';
                    echo '<input type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $field['name'] ) . '"' . $attrs . ' />';
                    break;
            }
            echo '</div>';
        }
        echo '</div>';
        $submit_button = get_submit_button( __( 'Save', 'super-directory' ), 'primary', 'submit', false );
        echo '<p class="submit">' . $submit_button;
        echo '<span class="sd-feedback-area sd-feedback-area--inline"><span id="sd-spinner" class="spinner" aria-hidden="true"></span><span id="sd-feedback" role="status" aria-live="polite"></span></span>';
        echo '</p>';
        echo '</form>';
    }

    private function render_edit_tab() {
        $per_page     = 20;
        $column_count = 6; // Five placeholder columns plus actions.

        echo '<div class="sd-communications sd-communications--main-entities">';
        echo '<div class="sd-accordion-group sd-accordion-group--table" data-sd-accordion-group="main-entities">';
        echo '<table class="wp-list-table widefat striped sd-accordion-table">';
        echo '<thead>';
        echo '<tr>';

        for ( $i = 1; $i <= 5; $i++ ) {
            $label = $this->get_placeholder_label( $i );

            printf(
                '<th scope="col" class="sd-accordion__heading sd-accordion__heading--placeholder-%1$d">%2$s</th>',
                absint( $i ),
                esc_html( $label )
            );
        }

        echo '<th scope="col" class="sd-accordion__heading sd-accordion__heading--actions">' . esc_html__( 'Actions', 'super-directory' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        printf(
            '<tbody id="sd-entity-list" data-per-page="%1$d" data-column-count="%2$d">',
            absint( $per_page ),
            absint( $column_count )
        );
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '<div class="tablenav"><div id="sd-entity-pagination" class="tablenav-pages"></div></div>';
        echo '</div>';
        echo '<div id="sd-entity-feedback" class="sd-feedback-area sd-feedback-area--block" role="status" aria-live="polite"></div>';
    }

    public function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        echo '<div class="wrap"><h1>' . esc_html__( 'SuperDirectory Settings', 'super-directory' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=sd-settings&tab=general" class="nav-tab ' . ( 'general' === $active_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'General Settings', 'super-directory' ) . '</a>';
        echo '<a href="?page=sd-settings&tab=style" class="nav-tab ' . ( 'style' === $active_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Style Settings', 'super-directory' ) . '</a>';
        echo '<a href="?page=sd-settings&tab=cron" class="nav-tab ' . ( 'cron' === $active_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Cron Jobs', 'super-directory' ) . '</a>';
        echo '</h2>';
        $this->top_message_center();

        $tab_titles = array(
            'general' => __( 'General Settings', 'super-directory' ),
            'style'   => __( 'Style Settings', 'super-directory' ),
            'cron'    => __( 'Cron Jobs', 'super-directory' ),
        );

        $tab_descriptions = array(
            'general' => __( 'Adjust the baseline configuration values that control how SuperDirectory behaves across your site.', 'super-directory' ),
            'style'   => __( 'Apply design tweaks and CSS overrides to align the boilerplate output with your brand guidelines.', 'super-directory' ),
            'cron'    => __( 'Review and manage every scheduled cron event created by SuperDirectory, including running or deleting hooks on demand.', 'super-directory' ),
        );

        $title       = isset( $tab_titles[ $active_tab ] ) ? $tab_titles[ $active_tab ] : '';
        $description = isset( $tab_descriptions[ $active_tab ] ) ? $tab_descriptions[ $active_tab ] : '';

        $this->render_tab_intro( $title, $description );

        if ( 'style' === $active_tab ) {
            $this->render_style_settings_tab();
        } elseif ( 'cron' === $active_tab ) {
            $this->render_cron_jobs_tab();
        } else {
            $this->render_general_settings_tab();
        }

        $this->bottom_message_center();
        echo '</div>';
    }

    private function render_general_settings_tab() {
        echo '<form id="sd-general-settings-form">';
        echo '<label>' . esc_html__( 'Option', 'super-directory' ) . ' <span class="sd-tooltip-icon dashicons dashicons-editor-help" data-tooltip="' . esc_attr__( 'Tooltip placeholder text for Option', 'super-directory' ) . '"></span></label>';
        echo '<input type="text" name="option" />';
        $submit_button = get_submit_button( __( 'Save Settings', 'super-directory' ), 'primary', 'submit', false );
        echo '<p class="submit">' . $submit_button;
        echo '<span class="sd-feedback-area sd-feedback-area--inline"><span id="sd-spinner" class="spinner" aria-hidden="true"></span><span id="sd-feedback" role="status" aria-live="polite"></span></span>';
        echo '</p>';
        echo '</form>';
    }

    private function render_style_settings_tab() {
        echo '<form id="sd-style-settings-form">';
        echo '<label>' . esc_html__( 'Custom CSS', 'super-directory' ) . ' <span class="sd-tooltip-icon dashicons dashicons-editor-help" data-tooltip="' . esc_attr__( 'Tooltip placeholder text for Custom CSS', 'super-directory' ) . '"></span></label>';
        echo '<textarea name="custom_css"></textarea>';
        $submit_button = get_submit_button( __( 'Save Settings', 'super-directory' ), 'primary', 'submit', false );
        echo '<p class="submit">' . $submit_button;
        echo '<span class="sd-feedback-area sd-feedback-area--inline"><span id="sd-spinner" class="spinner" aria-hidden="true"></span><span id="sd-feedback" role="status" aria-live="polite"></span></span>';
        echo '</p>';
        echo '</form>';
    }

    private function render_cron_jobs_tab() {
        echo '<div class="sd-cron-tab">';

        $messages = array(
            'deleted'       => array(
                'type'    => 'success',
                'message' => __( 'Cron event deleted successfully.', 'super-directory' ),
            ),
            'delete_failed' => array(
                'type'    => 'error',
                'message' => __( 'Unable to delete the cron event. Please try again.', 'super-directory' ),
            ),
            'run'           => array(
                'type'    => 'success',
                'message' => __( 'Cron event executed immediately.', 'super-directory' ),
            ),
            'run_failed'    => array(
                'type'    => 'error',
                'message' => __( 'Unable to execute the cron event. Ensure the hook is registered.', 'super-directory' ),
            ),
        );

        $notice_key = isset( $_GET['sd_cron_message'] ) ? sanitize_text_field( wp_unslash( $_GET['sd_cron_message'] ) ) : '';

        if ( $notice_key && isset( $messages[ $notice_key ] ) ) {
            $notice = $messages[ $notice_key ];
            printf(
                '<div class="notice notice-%1$s"><p>%2$s</p></div>',
                esc_attr( $notice['type'] ),
                esc_html( $notice['message'] )
            );
        }

        $events    = SD_Cron_Manager::get_plugin_cron_events();
        $per_page  = 20;
        $total     = count( $events );
        $page      = isset( $_GET['sd_cron_page'] ) ? max( 1, absint( wp_unslash( $_GET['sd_cron_page'] ) ) ) : 1;
        $max_pages = max( 1, (int) ceil( $total / $per_page ) );

        if ( $page > $max_pages ) {
            $page = $max_pages;
        }

        $offset          = ( $page - 1 ) * $per_page;
        $displayed_events = array_slice( $events, $offset, $per_page );

        $pagination_base = add_query_arg(
            array(
                'page' => 'sd-settings',
                'tab'  => 'cron',
                'sd_cron_page' => '%#%',
            ),
            admin_url( 'admin.php' )
        );

        $pagination = paginate_links(
            array(
                'base'      => $pagination_base,
                'format'    => '%#%',
                'current'   => $page,
                'total'     => $max_pages,
                'prev_text' => __( '&laquo; Previous', 'super-directory' ),
                'next_text' => __( 'Next &raquo;', 'super-directory' ),
                'type'      => 'list',
            )
        );

        if ( $pagination ) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $pagination ) . '</div></div>';
        }

        echo '<table class="widefat striped sd-cron-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Cron Job', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Description', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Type', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Schedule', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Hook', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Next Run', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Countdown', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Arguments', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'super-directory' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $displayed_events ) ) {
            echo '<tr><td colspan="9">' . esc_html__( 'No cron events found for SuperDirectory.', 'super-directory' ) . '</td></tr>';
        } else {
            $redirect = add_query_arg(
                array(
                    'page' => 'sd-settings',
                    'tab'  => 'cron',
                ),
                admin_url( 'admin.php' )
            );

            if ( $page > 1 ) {
                $redirect = add_query_arg( 'sd_cron_page', $page, $redirect );
            }

            foreach ( $displayed_events as $event ) {
                $hook_data      = SD_Cron_Manager::get_hook_display_data( $event['hook'] );
                $type_label     = SD_Cron_Manager::is_recurring( $event['schedule'] ) ? esc_html__( 'Recurring', 'super-directory' ) : esc_html__( 'One-off', 'super-directory' );
                $schedule_label = SD_Cron_Manager::get_schedule_label( $event['schedule'], $event['interval'] );
                $next_run       = SD_Cron_Manager::format_timestamp( $event['timestamp'] );
                $countdown      = SD_Cron_Manager::get_countdown( $event['timestamp'] );
                $args_display   = empty( $event['args'] ) ? '&mdash;' : esc_html( wp_json_encode( $event['args'] ) );
                $args_encoded   = base64_encode( wp_json_encode( $event['args'] ) );

                if ( false === $args_encoded ) {
                    $args_encoded = '';
                }

                echo '<tr>';
                echo '<td><strong>' . esc_html( $hook_data['name'] ) . '</strong> <span class="sd-tooltip-icon dashicons dashicons-editor-help" data-tooltip="' . esc_attr( $hook_data['description'] ) . '"></span></td>';
                echo '<td>' . esc_html( $hook_data['description'] ) . '</td>';
                echo '<td>' . esc_html( $type_label ) . '</td>';
                echo '<td>' . esc_html( $schedule_label ) . '</td>';
                echo '<td><code>' . esc_html( $event['hook'] ) . '</code></td>';
                echo '<td>' . esc_html( $next_run ) . '</td>';
                echo '<td>' . esc_html( $countdown ) . '</td>';
                echo '<td>' . ( empty( $event['args'] ) ? '&mdash;' : $args_display ) . '</td>';
                echo '<td>';
                echo '<div class="sd-cron-actions">';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sd-cron-action-form">';
                wp_nonce_field( 'sd_run_cron_event', 'sd_run_cron_event_nonce' );
                echo '<input type="hidden" name="action" value="sd_run_cron_event" />';
                echo '<input type="hidden" name="hook" value="' . esc_attr( $event['hook'] ) . '" />';
                echo '<input type="hidden" name="args" value="' . esc_attr( $args_encoded ) . '" />';
                echo '<input type="hidden" name="redirect" value="' . esc_attr( $redirect ) . '" />';
                echo '<button type="submit" class="button button-secondary">' . esc_html__( 'Run Now', 'super-directory' ) . '</button>';
                echo '</form>';

                $confirm = esc_js( __( 'Are you sure you want to delete this cron event?', 'super-directory' ) );

                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sd-cron-action-form" onsubmit="return confirm(\'' . $confirm . '\');">';
                wp_nonce_field( 'sd_delete_cron_event', 'sd_delete_cron_event_nonce' );
                echo '<input type="hidden" name="action" value="sd_delete_cron_event" />';
                echo '<input type="hidden" name="hook" value="' . esc_attr( $event['hook'] ) . '" />';
                echo '<input type="hidden" name="timestamp" value="' . esc_attr( $event['timestamp'] ) . '" />';
                echo '<input type="hidden" name="args" value="' . esc_attr( $args_encoded ) . '" />';
                echo '<input type="hidden" name="redirect" value="' . esc_attr( $redirect ) . '" />';
                echo '<button type="submit" class="button button-link-delete">' . esc_html__( 'Delete Event', 'super-directory' ) . '</button>';
                echo '</form>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';

        if ( $pagination ) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $pagination ) . '</div></div>';
        }

        echo '</div>';
    }

    private function decode_cron_args( $encoded ) {
        if ( empty( $encoded ) ) {
            return array();
        }

        $decoded = base64_decode( wp_unslash( $encoded ), true );

        if ( false === $decoded ) {
            return array();
        }

        $args = json_decode( $decoded, true );

        return is_array( $args ) ? $args : array();
    }

    private function get_cron_redirect_url() {
        $fallback = add_query_arg(
            array(
                'page' => 'sd-settings',
                'tab'  => 'cron',
            ),
            admin_url( 'admin.php' )
        );

        if ( empty( $_POST['redirect'] ) ) {
            return $fallback;
        }

        $redirect = esc_url_raw( wp_unslash( $_POST['redirect'] ) );

        return $redirect ? $redirect : $fallback;
    }

    private function redirect_with_cron_message( $redirect, $message ) {
        $url = add_query_arg( 'sd_cron_message', $message, $redirect );
        wp_safe_redirect( $url );
        exit;
    }

    public function handle_delete_cron_event() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'super-directory' ) );
        }

        check_admin_referer( 'sd_delete_cron_event', 'sd_delete_cron_event_nonce' );

        $redirect = $this->get_cron_redirect_url();
        $hook     = isset( $_POST['hook'] ) ? sanitize_text_field( wp_unslash( $_POST['hook'] ) ) : '';
        $timestamp = isset( $_POST['timestamp'] ) ? absint( wp_unslash( $_POST['timestamp'] ) ) : 0;
        $args     = $this->decode_cron_args( isset( $_POST['args'] ) ? $_POST['args'] : '' );

        if ( empty( $hook ) || 0 !== strpos( $hook, SD_Cron_Manager::HOOK_PREFIX ) || empty( $timestamp ) ) {
            $this->redirect_with_cron_message( $redirect, 'delete_failed' );
        }

        $deleted = wp_unschedule_event( $timestamp, $hook, $args );

        if ( $deleted ) {
            $this->redirect_with_cron_message( $redirect, 'deleted' );
        }

        $this->redirect_with_cron_message( $redirect, 'delete_failed' );
    }

    public function handle_run_cron_event() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'super-directory' ) );
        }

        check_admin_referer( 'sd_run_cron_event', 'sd_run_cron_event_nonce' );

        $redirect = $this->get_cron_redirect_url();
        $hook     = isset( $_POST['hook'] ) ? sanitize_text_field( wp_unslash( $_POST['hook'] ) ) : '';
        $args     = $this->decode_cron_args( isset( $_POST['args'] ) ? $_POST['args'] : '' );

        if ( empty( $hook ) || 0 !== strpos( $hook, SD_Cron_Manager::HOOK_PREFIX ) ) {
            $this->redirect_with_cron_message( $redirect, 'run_failed' );
        }

        if ( ! has_action( $hook ) ) {
            $this->redirect_with_cron_message( $redirect, 'run_failed' );
        }

        do_action_ref_array( $hook, $args );

        $this->redirect_with_cron_message( $redirect, 'run' );
    }

    public function render_logs_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'generated_content';
        echo '<div class="wrap"><h1>' . esc_html__( 'SuperDirectory Logs', 'super-directory' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=sd-logs&tab=generated_content" class="nav-tab ' . ( 'generated_content' === $active_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Generated Content', 'super-directory' ) . '</a>';
        echo '</h2>';
        $this->top_message_center();

        $tab_titles = array(
            'generated_content' => __( 'Generated Content', 'super-directory' ),
        );

        $tab_descriptions = array(
            'generated_content' => __( 'Inspect saved content entries and jump to editing, viewing, or deleting items created by the logger.', 'super-directory' ),
        );

        $title       = isset( $tab_titles[ $active_tab ] ) ? $tab_titles[ $active_tab ] : '';
        $description = isset( $tab_descriptions[ $active_tab ] ) ? $tab_descriptions[ $active_tab ] : '';

        $this->render_tab_intro( $title, $description );

        if ( 'generated_content' === $active_tab ) {
            $this->render_generated_content_log();
        }

        $this->bottom_message_center();
        echo '</div>';
    }

    private function render_generated_content_log() {
        $logger  = new SD_Content_Logger();
        $entries = $logger->get_logged_content();
        echo '<table class="widefat"><thead><tr>';
        echo '<th>' . esc_html__( 'Title', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Type', 'super-directory' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'super-directory' ) . '</th>';
        echo '</tr></thead><tbody>';
        if ( $entries ) {
            foreach ( $entries as $entry ) {
                $post = get_post( $entry->post_id );
                if ( ! $post ) {
                    continue;
                }
                $view   = get_permalink( $post );
                $edit   = get_edit_post_link( $post->ID );
                $delete = wp_nonce_url( admin_url( 'admin-post.php?action=sd_delete_generated_content&post_id=' . $post->ID ), 'sd_delete_generated_content_' . $post->ID );
                echo '<tr>';
                echo '<td><a href="' . esc_url( $view ) . '" target="_blank">' . esc_html( get_the_title( $post ) ) . '</a></td>';
                echo '<td>' . esc_html( ucfirst( $entry->post_type ) ) . '</td>';
                echo '<td><a href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'super-directory' ) . '</a> | ';
                $confirm = esc_js( __( 'Are you sure you want to delete this item?', 'super-directory' ) );
                echo '<a href="' . esc_url( $delete ) . '" onclick="return confirm(\'' . $confirm . '\');">' . esc_html__( 'Delete', 'super-directory' ) . '</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="3">' . esc_html__( 'No generated content found.', 'super-directory' ) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function handle_download_email_log() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to download the email log.', 'super-directory' ) );
        }

        check_admin_referer( 'sd_download_email_log', 'sd_email_log_nonce' );

        if ( ! SD_Email_Log_Helper::is_log_available() ) {
            wp_die( esc_html__( 'The email log could not be found. Check upload directory permissions.', 'super-directory' ) );
        }

        $contents = SD_Email_Log_Helper::get_log_contents();
        $filename = SD_Email_Log_Helper::get_download_filename();

        if ( '' === $filename ) {
            $filename = 'sd-email-log.txt';
        }

        $filename = sanitize_file_name( $filename );

        if ( '' === $contents ) {
            $contents = '';
        }

        nocache_headers();
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $contents ) );

        echo $contents;
        exit;
    }

    public function handle_delete_generated_content() {
        if ( ! current_user_can( 'delete_posts' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'super-directory' ) );
        }
        $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
        check_admin_referer( 'sd_delete_generated_content_' . $post_id );
        wp_delete_post( $post_id, true );
        wp_redirect( admin_url( 'admin.php?page=sd-logs&tab=generated_content' ) );
        exit;
    }
}
