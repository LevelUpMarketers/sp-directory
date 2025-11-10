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
            __( 'SuperDirectory Logs', 'super-directory' ),
            __( 'SuperDirectory Logs', 'super-directory' ),
            'manage_options',
            'sd-logs',
            array( $this, 'render_logs_page' )
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

        echo '<div class="sd-directory-table sd-directory-table--main-entities">';
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
        echo '<div class="wrap"><h1>' . esc_html__( 'SuperDirectory Settings', 'super-directory' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=sd-settings&tab=general" class="nav-tab nav-tab-active">' . esc_html__( 'General Settings', 'super-directory' ) . '</a>';
        echo '</h2>';

        $this->top_message_center();

        $this->render_tab_intro(
            __( 'General Settings', 'super-directory' ),
            __( 'Adjust the baseline configuration values that control how SuperDirectory behaves across your site.', 'super-directory' )
        );

        $this->render_general_settings_tab();

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
