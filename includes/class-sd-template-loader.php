<?php
/**
 * Register and load SuperDirectory page templates.
 *
 * @package SuperDirectory
 */

class SD_Template_Loader {

    /**
     * Boot the template loader.
     */
    public function register() {
        add_filter( 'theme_page_templates', array( $this, 'register_template' ), 10, 4 );
        add_filter( 'template_include', array( $this, 'maybe_load_template' ) );
    }

    /**
     * Add the SuperDirectory template to the list of selectable templates.
     *
     * @param array        $page_templates Existing templates.
     * @param WP_Theme     $wp_theme       Current theme.
     * @param WP_Post|null $post           Current post object.
     * @param string       $post_type      Current post type.
     *
     * @return array
     */
    public function register_template( $page_templates, $wp_theme, $post, $post_type ) {
        unset( $post, $post_type );

        if ( ! is_array( $page_templates ) ) {
            $page_templates = array();
        }

        $page_templates[ SD_DIRECTORY_TEMPLATE_SLUG ] = __( 'SuperDirectory Listing Page', 'super-directory' );

        if ( class_exists( 'WP_Theme' ) && $wp_theme instanceof WP_Theme ) {
            $cache_hash = md5( $wp_theme->get_stylesheet() . $wp_theme->get_template() );
            wp_cache_delete( 'page_templates-' . $cache_hash, 'themes' );
        }

        return $page_templates;
    }

    /**
     * Load the plugin template when assigned to a page.
     *
     * @param string $template Template path resolved by WordPress.
     *
     * @return string
     */
    public function maybe_load_template( $template ) {
        $post = get_post();

        if ( ! $post ) {
            return $template;
        }

        $assigned_template  = get_page_template_slug( $post );
        $has_directory_meta = metadata_exists( 'post', $post->ID, '_sd_main_entity_id' );

        if ( ! $has_directory_meta ) {
            return $template;
        }

        if ( defined( 'SD_DIRECTORY_TEMPLATE_LEGACY_SLUG' ) && SD_DIRECTORY_TEMPLATE_LEGACY_SLUG === $assigned_template ) {
            update_post_meta( $post->ID, '_wp_page_template', SD_DIRECTORY_TEMPLATE_SLUG );
            $assigned_template = SD_DIRECTORY_TEMPLATE_SLUG;
        }

        if ( SD_DIRECTORY_TEMPLATE_SLUG !== $assigned_template ) {
            update_post_meta( $post->ID, '_wp_page_template', SD_DIRECTORY_TEMPLATE_SLUG );
            $assigned_template = SD_DIRECTORY_TEMPLATE_SLUG;
        }

        if ( SD_DIRECTORY_TEMPLATE_SLUG !== $assigned_template ) {
            return $template;
        }

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_directory_assets' ) );

        $plugin_template = trailingslashit( SD_PLUGIN_DIR ) . SD_DIRECTORY_TEMPLATE_SLUG;

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return $template;
    }

    /**
     * Enqueue front-end assets for the directory template.
     */
    public function enqueue_directory_assets() {
        wp_enqueue_style(
            'sd-directory-entry',
            SD_PLUGIN_URL . 'assets/css/templates/directory-entry.css',
            array(),
            SD_VERSION
        );
    }
}
