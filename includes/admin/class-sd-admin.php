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

        $field_definitions = $this->prepare_main_entity_fields_for_js();
        $field_map         = array();

        foreach ( $field_definitions as $definition ) {
            if ( empty( $definition['name'] ) ) {
                continue;
            }

            $field_map[ $definition['name'] ] = $definition;
        }

        $table_columns = $this->prepare_table_columns_for_js();

        wp_localize_script( 'sd-admin', 'sdAjax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'sd_ajax_nonce' ),
        ) );

        wp_localize_script( 'sd-admin', 'sdAdmin', array(
            'fields'          => $field_definitions,
            'fieldMap'        => $field_map,
            'tableColumns'    => $table_columns,
            'delete'          => __( 'Delete', 'super-directory' ),
            'none'            => __( 'No entries found.', 'super-directory' ),
            'mediaTitle'      => __( 'Select Image', 'super-directory' ),
            'mediaButton'     => __( 'Use this image', 'super-directory' ),
            'selectImage'     => __( 'Select Image', 'super-directory' ),
            'selectImages'    => __( 'Select Images', 'super-directory' ),
            'imageSelected'   => __( 'Image selected', 'super-directory' ),
            'galleryImageSingle' => __( '1 image', 'super-directory' ),
            'galleryImagesCount' => __( '%d images', 'super-directory' ),
            'itemPlaceholder' => __( 'Item #%d', 'super-directory' ),
            'addAnotherItem'  => __( '+ Add Another Item', 'super-directory' ),
            'makeSelection'   => __( 'Make a Selection...', 'super-directory' ),
            'error'           => __( 'Something went wrong. Please try again.', 'super-directory' ),
            'loadError'       => __( 'Unable to load records. Please try again.', 'super-directory' ),
            'saved'           => __( 'Saved', 'super-directory' ),
            'changesSaved'    => __( 'Changes saved.', 'super-directory' ),
            'totalRecords'    => __( 'Total records: %s', 'super-directory' ),
            'pageOf'          => __( 'Page %1$s of %2$s', 'super-directory' ),
            'firstPage'       => __( 'First page', 'super-directory' ),
            'prevPage'        => __( 'Previous page', 'super-directory' ),
            'nextPage'        => __( 'Next page', 'super-directory' ),
            'lastPage'        => __( 'Last page', 'super-directory' ),
            'toggleDetails'   => __( 'Toggle entity details', 'super-directory' ),
            'editAction'      => __( 'Edit', 'super-directory' ),
            'saveChanges'     => __( 'Save Changes', 'super-directory' ),
            'entityFields'    => $field_definitions,
            'editorSettings'  => $this->get_inline_editor_settings(),
            'previewEntity'   => SD_Main_Entity_Helper::get_first_preview_data(),
            'previewEmptyMessage'      => __( 'Add a summary or description to generate the preview.', 'super-directory' ),
            'previewUnavailableMessage' => __( 'Add a Directory Listing entry to generate a preview.', 'super-directory' ),
        ) );
    }

    private function get_category_options() {
        $options = array(
            'crm'                   => __( 'CRM', 'super-directory' ),
            'chatbots'              => __( 'Chatbots', 'super-directory' ),
            'hiring_platform'       => __( 'Hiring Platform', 'super-directory' ),
            'lead_generation'       => __( 'Lead Generation', 'super-directory' ),
            'answering_service'     => __( 'Answering Service', 'super-directory' ),
            'csr_training'          => __( 'CSR Training', 'super-directory' ),
            'business_development'  => __( 'Business Development', 'super-directory' ),
            'onboarding_companies'  => __( 'Onboarding Companies', 'super-directory' ),
            'review_solicitation'   => __( 'Review Solicitation', 'super-directory' ),
        );

        $options = apply_filters( 'sd_directory_categories', $options );

        $normalized = array();

        foreach ( $options as $value => $label ) {
            $key              = sanitize_key( $value );
            $normalized[ $key ] = wp_strip_all_tags( (string) $label );
        }

        return array( '' => __( 'Make a Selection...', 'super-directory' ) ) + $normalized;
    }

    private function get_industry_options() {
        $options = array(
            'all'                           => __( 'All Industries', 'super-directory' ),
            'multiple'                      => __( 'Multiple', 'super-directory' ),
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
            $key              = sanitize_key( $value );
            $normalized[ $key ] = wp_strip_all_tags( (string) $label );
        }

        return array( '' => __( 'Make a Selection...', 'super-directory' ) ) + $normalized;
    }




    private function get_service_model_options() {
        $options = array(
            'local'  => __( 'Local Customers Only', 'super-directory' ),
            'virtual' => __( 'Virtual / National', 'super-directory' ),
            'both'    => __( 'Both Local & National', 'super-directory' ),
        );

        $options = apply_filters( 'sd_directory_service_models', $options );

        $normalized = array();

        foreach ( $options as $value => $label ) {
            $key              = sanitize_key( $value );
            $normalized[ $key ] = wp_strip_all_tags( (string) $label );
        }

        return array( '' => __( 'Make a Selection...', 'super-directory' ) ) + $normalized;
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

    private function get_main_entity_field_groups() {
        $category_options      = $this->get_category_options();
        $industry_options      = $this->get_industry_options();
        $service_model_options = $this->get_service_model_options();

        return array(
            'basics' => array(
                'label'       => __( 'Listing Basics', 'super-directory' ),
                'description' => __( 'Identify the company and how it fits within the directory.', 'super-directory' ),
                'fields'      => array(
                    array(
                        'name'      => 'name',
                        'label'     => __( 'Resource Name', 'super-directory' ),
                        'type'      => 'text',
                        'tooltip'   => __( 'This name is shown on cards, generated pages, and search results.', 'super-directory' ),
                    ),
                    array(
                        'name'      => 'category',
                        'label'     => __( 'Category', 'super-directory' ),
                        'type'      => 'select',
                        'options'   => $category_options,
                        'tooltip'   => __( 'Choose the closest category from the curated SuperDirectory list.', 'super-directory' ),
                    ),
                    array(
                        'name'      => 'industry_vertical',
                        'label'     => __( 'Industry', 'super-directory' ),
                        'type'      => 'select',
                        'options'   => $industry_options,
                        'tooltip'   => __( 'Choose the closest category from the curated SuperDirectory list.', 'super-directory' ),
                    ),
                    array(
                        'name'      => 'service_model',
                        'label'     => __( 'Local, National, Both?', 'super-directory' ),
                        'type'      => 'select',
                        'options'   => $service_model_options,
                        'tooltip'   => __( 'Clarify the service footprint so prospects know where support is available.', 'super-directory' ),
                    ),
                ),
            ),
            'imagery' => array(
                'label'       => __( 'Logos & Imagery', 'super-directory' ),
                'description' => __( 'Upload a brand logo and supporting gallery assets for the listing.', 'super-directory' ),
                'fields'      => array(
                    array(
                        'name'    => 'logo_attachment_id',
                        'label'   => __( 'Logo Image', 'super-directory' ),
                        'type'    => 'image',
                        'tooltip' => __( 'Select a primary logo for the resource card and header.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'homepage_screenshot_id',
                        'label'   => __( 'Homepage Screenshot', 'super-directory' ),
                        'type'    => 'image',
                        'tooltip' => __( 'Upload a homepage screenshot to feature on the listing.', 'super-directory' ),
                    ),
                    array(
                        'name'       => 'gallery_image_ids',
                        'label'      => __( 'Gallery Images', 'super-directory' ),
                        'type'       => 'gallery',
                        'tooltip'    => __( 'Choose supporting images that can power a gallery on the front-end template.', 'super-directory' ),
                        'full_width' => true,
                    ),
                ),
            ),
            'contact' => array(
                'label'       => __( 'Contact & Web Presence', 'super-directory' ),
                'description' => __( 'Share the primary ways prospects can learn more or get in touch.', 'super-directory' ),
                'fields'      => array(
                    array(
                        'name'    => 'website_url',
                        'label'   => __( 'Website URL', 'super-directory' ),
                        'type'    => 'url',
                        'tooltip' => __( 'Primary marketing or sign-up destination for this listing.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'phone_number',
                        'label'   => __( 'Phone Number', 'super-directory' ),
                        'type'    => 'tel',
                        'tooltip' => __( 'Main phone number for customers to call.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'email_address',
                        'label'   => __( 'Email', 'super-directory' ),
                        'type'    => 'email',
                        'tooltip' => __( 'Customer-facing inbox used for questions or onboarding.', 'super-directory' ),
                    ),
                ),
            ),
            'location' => array(
                'label'       => __( 'Location & Coverage', 'super-directory' ),
                'description' => __( 'Capture where the resource is based and how to find it.', 'super-directory' ),
                'fields'      => array(
                    array(
                        'name'       => 'street_address',
                        'label'      => __( 'Street Address', 'super-directory' ),
                        'type'       => 'text',
                        'tooltip'    => __( 'Headquarters or primary service location street address.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'city',
                        'label'   => __( 'City', 'super-directory' ),
                        'type'    => 'text',
                        'tooltip' => __( 'City associated with the headquarters or primary facility.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'state',
                        'label'   => __( 'State', 'super-directory' ),
                        'type'    => 'state',
                        'options' => $this->get_us_states_and_territories(),
                        'tooltip' => __( 'Select the applicable U.S. state or territory.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'zip_code',
                        'label'   => __( 'Zip Code', 'super-directory' ),
                        'type'    => 'text',
                        'tooltip' => __( 'Postal or ZIP code used for mailing and lookups.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'country',
                        'label'   => __( 'Country', 'super-directory' ),
                        'type'    => 'text',
                        'tooltip' => __( 'Country where the business is based or primarily serves.', 'super-directory' ),
                    ),
                ),
            ),
            'social' => array(
                'label'       => __( 'Social & Listings', 'super-directory' ),
                'description' => __( 'Log the primary social channels and directory listings to promote credibility.', 'super-directory' ),
                'fields'      => array(
                    array(
                        'name'    => 'facebook_url',
                        'label'   => __( 'Facebook URL', 'super-directory' ),
                        'type'    => 'url',
                        'tooltip' => __( 'Public Facebook page or group associated with the listing.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'instagram_url',
                        'label'   => __( 'Instagram URL', 'super-directory' ),
                        'type'    => 'url',
                        'tooltip' => __( 'Instagram profile that showcases work, culture, or resources.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'youtube_url',
                        'label'   => __( 'YouTube URL', 'super-directory' ),
                        'type'    => 'url',
                        'tooltip' => __( 'YouTube channel or playlist featuring video content.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'linkedin_url',
                        'label'   => __( 'LinkedIn URL', 'super-directory' ),
                        'type'    => 'url',
                        'tooltip' => __( 'LinkedIn company page or showcase profile.', 'super-directory' ),
                    ),
                    array(
                        'name'    => 'google_business_url',
                        'label'   => __( 'GBP URL', 'super-directory' ),
                        'type'    => 'url',
                        'tooltip' => __( 'Direct link to the Google Business Profile listing.', 'super-directory' ),
                    ),
                ),
            ),
            'descriptions' => array(
                'label'       => __( 'Descriptions & Messaging', 'super-directory' ),
                'description' => __( 'Tell the story with short and long-form content.', 'super-directory' ),
                'fields'      => array(
                    array(
                        'name'       => 'short_description',
                        'label'      => __( 'Short Description', 'super-directory' ),
                        'type'       => 'textarea',
                        'tooltip'    => __( 'A brief overview used on condensed directory layouts.', 'super-directory' ),
                        'full_width' => true,
                    ),
                    array(
                        'name'       => 'long_description_primary',
                        'label'      => __( 'What This Resource Does', 'super-directory' ),
                        'type'       => 'editor',
                        'tooltip'    => __( 'Primary body content for the generated detail page.', 'super-directory' ),
                        'full_width' => true,
                    ),
                    array(
                        'name'       => 'long_description_secondary',
                        'label'      => __( 'Why We Recommend This Resource', 'super-directory' ),
                        'type'       => 'editor',
                        'tooltip'    => __( 'Secondary narrative space for testimonials, processes, or offers.', 'super-directory' ),
                        'full_width' => true,
                    ),
                ),
            ),
        );
    }

    private function get_main_entity_fields() {
        $groups = $this->get_main_entity_field_groups();
        $fields = array();

        foreach ( $groups as $group_key => $group ) {
            if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
                continue;
            }

            foreach ( $group['fields'] as $field ) {
                $field['group'] = $group_key;

                if ( ! isset( $field['full_width'] ) ) {
                    $field['full_width'] = false;
                }

                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function get_edit_table_columns() {
        return array(
            array(
                'key'   => 'name',
                'label' => __( 'Listing Name', 'super-directory' ),
                'type'  => 'title',
            ),
            array(
                'key'   => 'category',
                'label' => __( 'Category', 'super-directory' ),
                'type'  => 'meta',
            ),
            array(
                'key'   => 'industry_vertical',
                'label' => __( 'Industry', 'super-directory' ),
                'type'  => 'meta',
            ),
            array(
                'key'   => 'service_model',
                'label' => __( 'Service Model', 'super-directory' ),
                'type'  => 'meta',
            ),
            array(
                'key'   => 'website_url',
                'label' => __( 'Website', 'super-directory' ),
                'type'  => 'meta',
            ),
            array(
                'key'   => 'actions',
                'label' => __( 'Actions', 'super-directory' ),
                'type'  => 'actions',
            ),
        );
    }

    private function prepare_main_entity_fields_for_js() {
        $fields   = $this->get_main_entity_fields();
        $prepared = array();

        foreach ( $fields as $field ) {
            $prepared_field = array(
                'name'      => $field['name'],
                'type'      => $field['type'],
                'label'     => $field['label'],
                'tooltip'   => $field['tooltip'],
                'fullWidth' => ! empty( $field['full_width'] ),
                'group'     => isset( $field['group'] ) ? $field['group'] : '',
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

    private function prepare_table_columns_for_js() {
        $columns  = $this->get_edit_table_columns();
        $prepared = array();

        foreach ( $columns as $column ) {
            $prepared[] = array(
                'key'   => $column['key'],
                'label' => isset( $column['label'] ) ? $column['label'] : '',
                'type'  => isset( $column['type'] ) ? $column['type'] : 'meta',
            );
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
            $settings = wp_get_editor_settings( 'long_description_primary', array( 'textarea_name' => 'long_description_primary' ) );

            if ( is_array( $settings ) ) {
                return $settings;
            }
        }

        return $default_settings;
    }

    private function render_create_tab() {
        $groups = $this->get_main_entity_field_groups();

        echo '<form id="sd-create-form"><div class="sd-flex-form">';

        foreach ( $groups as $group_key => $group ) {
            echo '<div class="sd-section-grouping">';
            $group_label       = isset( $group['label'] ) ? $group['label'] : '';
            $group_description = isset( $group['description'] ) ? $group['description'] : '';

            if ( $group_label || $group_description ) {
                echo '<div class="sd-field sd-field-full sd-field-group">';

                if ( $group_label ) {
                    echo '<h3 class="sd-field-group__title">' . esc_html( $group_label ) . '</h3>';
                }

                if ( $group_description ) {
                    echo '<p class="sd-field-group__description">' . esc_html( $group_description ) . '</p>';
                }

                echo '</div>';
            }

            if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
                continue;
            }

            foreach ( $group['fields'] as $field ) {
                $classes = 'sd-field';

                if ( ! empty( $field['full_width'] ) ) {
                    $classes .= ' sd-field-full';
                }

                echo '<div class="' . esc_attr( $classes ) . '">';
                echo '<label><span class="sd-tooltip-icon dashicons dashicons-editor-help" data-tooltip="' . esc_attr( $field['tooltip'] ) . '"></span>' . esc_html( $field['label'] ) . '</label>';

                switch ( $field['type'] ) {
                    case 'select':
                        $options = isset( $field['options'] ) ? $field['options'] : array( '' => __( 'Make a Selection...', 'super-directory' ) );
                        echo '<select name="' . esc_attr( $field['name'] ) . '">';

                        foreach ( $options as $value => $label ) {
                            if ( '' === (string) $value ) {
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
                    case 'editor':
                        wp_editor( '', $field['name'], array( 'textarea_name' => $field['name'] ) );
                        break;
                    case 'items':
                        $container_id = 'sd-items-' . sanitize_html_class( $field['name'] );
                        echo '<div id="' . esc_attr( $container_id ) . '" class="sd-items-container" data-field="' . esc_attr( $field['name'] ) . '">';
                        echo '<div class="sd-item-row" style="margin-bottom:8px; display:flex; align-items:center;">';
                        echo '<input type="text" name="' . esc_attr( $field['name'] ) . '[]" class="regular-text sd-item-field" placeholder="' . esc_attr__( 'Item #1', 'super-directory' ) . '" />';
                        echo '</div></div>';
                        echo '<button type="button" class="button sd-add-item" data-target="#' . esc_attr( $container_id ) . '" data-field-name="' . esc_attr( $field['name'] ) . '" style="margin-top:8px;">' . esc_html__( '+ Add Another Item', 'super-directory' ) . '</button>';
                        break;
                    case 'textarea':
                        echo '<textarea name="' . esc_attr( $field['name'] ) . '" rows="4"></textarea>';
                        break;
                    case 'image':
                        $input_id = sanitize_html_class( $field['name'] );
                        echo '<input type="hidden" class="sd-media-input" data-media-type="image" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $input_id ) . '" />';
                        echo '<button type="button" class="button sd-upload" data-target="#' . esc_attr( $input_id ) . '">' . esc_html__( 'Select Image', 'super-directory' ) . '</button>';
                        echo '<div id="' . esc_attr( $input_id ) . '-preview" class="sd-media-preview" aria-live="polite"></div>';
                        break;
                    case 'gallery':
                        $input_id = sanitize_html_class( $field['name'] );
                        echo '<input type="hidden" class="sd-media-input" data-media-type="gallery" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $input_id ) . '" />';
                        echo '<button type="button" class="button sd-upload-gallery" data-target="#' . esc_attr( $input_id ) . '">' . esc_html__( 'Select Images', 'super-directory' ) . '</button>';
                        echo '<div id="' . esc_attr( $input_id ) . '-preview" class="sd-media-preview sd-media-preview--gallery" aria-live="polite"></div>';
                        break;
                    default:
                        $attrs = isset( $field['attrs'] ) ? ' ' . $field['attrs'] : '';
                        echo '<input type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $field['name'] ) . '"' . $attrs . ' />';
                        break;
                }

                echo '</div>';
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
        $columns      = $this->get_edit_table_columns();
        $column_count = count( $columns );

        echo '<div class="sd-directory-table sd-directory-table--main-entities">';
        echo '<div class="sd-accordion-group sd-accordion-group--table" data-sd-accordion-group="main-entities">';
        echo '<table class="wp-list-table widefat striped sd-accordion-table">';
        echo '<thead>';
        echo '<tr>';

        foreach ( $columns as $column ) {
            $heading_class = 'sd-accordion__heading sd-accordion__heading--' . sanitize_html_class( $column['key'] );
            $label         = isset( $column['label'] ) ? $column['label'] : '';

            echo '<th scope="col" class="' . esc_attr( $heading_class ) . '">' . esc_html( $label ) . '</th>';
        }

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
            'create' => __( 'Capture the resource details, contact info, and highlights for a new SuperDirectory listing.', 'super-directory' ),
            'edit'   => __( 'Review saved listings to confirm their data, trigger edits, or remove records you no longer need.', 'super-directory' ),
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
        echo '<div class="sd-settings-panel">';
        echo '<h3>' . esc_html__( 'Bulk Directory Upload', 'super-directory' ) . '</h3>';
        echo '<p class="description">' . esc_html__( 'Upload a CSV or TSV export that follows the provided column order to import multiple resources at once.', 'super-directory' ) . '</p>';

        echo '<form id="sd-bulk-import-form" class="sd-form sd-form--stacked" enctype="multipart/form-data">';
        echo '<div class="sd-field sd-field--file">';
        echo '<label>' . esc_html__( 'Upload spreadsheet', 'super-directory' ) . ' <span class="sd-tooltip-icon dashicons dashicons-editor-help" data-tooltip="' . esc_attr__( 'Use the provided column headings to map values into the directory automatically.', 'super-directory' ) . '"></span></label>';
        echo '<input type="file" name="sd_bulk_file" id="sd_bulk_file" accept=".csv,.tsv,.txt" />';
        echo '</div>';

        echo '<details class="sd-bulk-import-details">';
        echo '<summary>' . esc_html__( 'Expected column headings (in order)', 'super-directory' ) . '</summary>';
        echo '<ol class="sd-bulk-import-columns">';
        $columns = array(
            __( 'Resource/Company/Vendor Name', 'super-directory' ),
            __( 'Category', 'super-directory' ),
            __( 'Website URL', 'super-directory' ),
            __( 'Phone', 'super-directory' ),
            __( 'Email', 'super-directory' ),
            __( 'Related Trade/Industry/Vertical', 'super-directory' ),
            __( 'Serving Only Local Customers, Virtual/National, or Both?', 'super-directory' ),
            __( 'State', 'super-directory' ),
            __( 'City', 'super-directory' ),
            __( 'Street Address', 'super-directory' ),
            __( 'Zip Code', 'super-directory' ),
            __( 'Short description/lead-in teaser sentence about the company (no longer thatn 98 characters, including spaces and punctuation)', 'super-directory' ),
            __( 'What [COMPANY NAME] Does', 'super-directory' ),
            __( 'Why We Recommend [COMPANY NAME]', 'super-directory' ),
            __( 'Facebook URL', 'super-directory' ),
            __( 'Instagram URL', 'super-directory' ),
            __( 'YouTube URL', 'super-directory' ),
            __( 'Linkedin URL', 'super-directory' ),
            __( 'Google Business Listing URL', 'super-directory' ),
            __( 'Logo WordPress media library attachment ID #', 'super-directory' ),
            __( 'Homepage Screenshot WordPress media library attachment ID #', 'super-directory' ),
        );

        foreach ( $columns as $column ) {
            echo '<li>' . esc_html( $column ) . '</li>';
        }

        echo '</ol>';
        echo '</details>';

        $submit_button = get_submit_button( __( 'Upload and Import', 'super-directory' ), 'primary', 'submit', false );
        echo '<p class="submit">' . $submit_button;
        echo '<span class="sd-feedback-area sd-feedback-area--inline"><span id="sd-bulk-import-spinner" class="spinner" aria-hidden="true"></span><span id="sd-bulk-import-feedback" role="status" aria-live="polite"></span></span>';
        echo '</p>';
        echo '</form>';
        echo '</div>';
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
        $logger       = new SD_Content_Logger();
        $entries      = $logger->get_logged_content();
        $columns      = array(
            array(
                'key'   => 'title',
                'label' => __( 'Content Title', 'super-directory' ),
                'type'  => 'title',
            ),
            array(
                'key'   => 'type',
                'label' => __( 'Type', 'super-directory' ),
                'type'  => 'meta',
            ),
            array(
                'key'   => 'created',
                'label' => __( 'Generated On', 'super-directory' ),
                'type'  => 'meta',
            ),
            array(
                'key'   => 'actions',
                'label' => __( 'Actions', 'super-directory' ),
                'type'  => 'actions',
            ),
        );
        $column_count = count( $columns );

        echo '<div class="sd-directory-table sd-directory-table--generated-content">';
        echo '<div class="sd-accordion-group sd-accordion-group--table" data-sd-accordion-group="generated-content">';
        echo '<table class="wp-list-table widefat striped sd-accordion-table">';
        echo '<thead><tr>';

        foreach ( $columns as $column ) {
            $heading_class = 'sd-accordion__heading sd-accordion__heading--' . sanitize_html_class( $column['key'] );
            $label         = isset( $column['label'] ) ? $column['label'] : '';

            echo '<th scope="col" class="' . esc_attr( $heading_class ) . '">' . esc_html( $label ) . '</th>';
        }

        echo '</tr></thead><tbody>';

        if ( empty( $entries ) ) {
            echo '<tr class="no-items"><td colspan="' . esc_attr( $column_count ) . '">' . esc_html__( 'No generated content found.', 'super-directory' ) . '</td></tr>';
        } else {
            $has_rows = false;

            foreach ( $entries as $entry ) {
                $post = get_post( $entry->post_id );

                if ( ! $post ) {
                    continue;
                }

                $has_rows = true;

                $post_id        = $post->ID;
                $header_id      = 'sd-generated-' . $post_id . '-header';
                $panel_id       = 'sd-generated-' . $post_id . '-panel';
                $title          = get_the_title( $post );
                $type_object    = get_post_type_object( $post->post_type );
                $type_label     = $type_object ? $type_object->labels->singular_name : ucfirst( $post->post_type );
                $created_at     = ! empty( $entry->created_at ) ? mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry->created_at ) : get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $post );
                $view_link      = get_permalink( $post );
                $edit_link      = get_edit_post_link( $post_id );
                $delete_link    = wp_nonce_url( admin_url( 'admin-post.php?action=sd_delete_generated_content&post_id=' . $post_id ), 'sd_delete_generated_content_' . $post_id );
                $entity_name    = ! empty( $entry->entity_name ) ? $entry->entity_name : '';
                $summary_action = __( 'Details', 'super-directory' );

                echo '<tr id="' . esc_attr( $header_id ) . '" class="sd-accordion__summary-row" tabindex="0" role="button" aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '">';
                echo '<td class="sd-accordion__cell sd-accordion__cell--title"><span class="sd-accordion__title-text">' . esc_html( $title ) . '</span></td>';
                echo '<td class="sd-accordion__cell sd-accordion__cell--meta"><span class="sd-accordion__meta-text"><span class="sd-accordion__meta-label">' . esc_html__( 'Type', 'super-directory' ) . ':</span> <span class="sd-accordion__meta-value">' . esc_html( $type_label ) . '</span></span></td>';
                echo '<td class="sd-accordion__cell sd-accordion__cell--meta"><span class="sd-accordion__meta-text"><span class="sd-accordion__meta-label">' . esc_html__( 'Generated On', 'super-directory' ) . ':</span> <span class="sd-accordion__meta-value">' . esc_html( $created_at ) . '</span></span></td>';
                echo '<td class="sd-accordion__cell sd-accordion__cell--actions"><span class="sd-accordion__action-link" aria-hidden="true">' . esc_html( $summary_action ) . '</span><span class="dashicons dashicons-arrow-down-alt2 sd-accordion__icon" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Toggle generated content details', 'super-directory' ) . '</span></td>';
                echo '</tr>';

                echo '<tr id="' . esc_attr( $panel_id ) . '" class="sd-accordion__panel-row" role="region" aria-labelledby="' . esc_attr( $header_id ) . '" aria-hidden="true">';
                echo '<td colspan="' . esc_attr( $column_count ) . '">';
                echo '<div class="sd-accordion__panel">';

                echo '<p><strong>' . esc_html__( 'Associated Listing', 'super-directory' ) . ':</strong> ';
                if ( $entity_name ) {
                    echo esc_html( $entity_name );
                } else {
                    echo esc_html__( 'No directory listing is linked to this page.', 'super-directory' );
                }
                echo '</p>';

                if ( $view_link ) {
                    echo '<p><strong>' . esc_html__( 'Permalink', 'super-directory' ) . ':</strong> <a href="' . esc_url( $view_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $view_link ) . '</a></p>';
                }

                echo '<p class="sd-generated-content-actions">';
                if ( $view_link ) {
                    echo '<a class="button button-secondary" href="' . esc_url( $view_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Page', 'super-directory' ) . '</a> ';
                }

                if ( $edit_link ) {
                    echo '<a class="button" href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit Page', 'super-directory' ) . '</a> ';
                }

                $confirm = esc_js( __( 'Are you sure you want to delete this item?', 'super-directory' ) );
                echo '<a class="button button-link-delete" href="' . esc_url( $delete_link ) . '" onclick="return confirm(\'' . $confirm . '\');">' . esc_html__( 'Delete Page', 'super-directory' ) . '</a>';
                echo '</p>';

                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }

            if ( ! $has_rows ) {
                echo '<tr class="no-items"><td colspan="' . esc_attr( $column_count ) . '">' . esc_html__( 'No generated content found.', 'super-directory' ) . '</td></tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
        echo '</div>';
    }

    public function handle_delete_generated_content() {
        if ( ! current_user_can( 'delete_posts' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'super-directory' ) );
        }
        $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
        if ( $post_id <= 0 ) {
            wp_die( esc_html__( 'Invalid generated content reference.', 'super-directory' ) );
        }
        check_admin_referer( 'sd_delete_generated_content_' . $post_id );
        wp_delete_post( $post_id, true );
        wp_redirect( admin_url( 'admin.php?page=sd-logs&tab=generated_content' ) );
        exit;
    }
}
