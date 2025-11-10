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
        unset( $wp_theme, $post, $post_type );

        if ( ! is_array( $page_templates ) ) {
            $page_templates = array();
        }

        $page_templates[ SD_DIRECTORY_TEMPLATE_SLUG ] = __( 'SuperDirectory Listing Page', 'super-directory' );

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

        $assigned_template = get_page_template_slug( $post );

        if ( SD_DIRECTORY_TEMPLATE_SLUG !== $assigned_template ) {
            return $template;
        }

        $plugin_template = trailingslashit( SD_PLUGIN_DIR . 'templates' ) . SD_DIRECTORY_TEMPLATE_SLUG;

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return $template;
    }
}
