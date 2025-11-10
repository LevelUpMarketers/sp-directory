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
        add_action( 'init', array( $this, 'prime_template_cache' ) );
        add_action( 'after_switch_theme', array( $this, 'prime_template_cache' ) );
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
    public function register_template( $page_templates, $wp_theme, $post = null, $post_type = 'page' ) {
        unset( $post, $post_type );

        if ( ! is_array( $page_templates ) ) {
            $page_templates = array();
        }

        $page_templates[ SD_DIRECTORY_TEMPLATE_SLUG ] = __( 'SuperDirectory Listing Page', 'super-directory' );

        if ( class_exists( 'WP_Theme' ) && $wp_theme instanceof WP_Theme ) {
            $this->flush_theme_template_cache( $wp_theme );
        }

        return $page_templates;
    }

    /**
     * Ensure WordPress caches are aware of the SuperDirectory template.
     */
    public function prime_template_cache() {
        if ( ! function_exists( 'wp_get_theme' ) ) {
            return;
        }

        $theme = wp_get_theme();

        if ( ! $theme || ! $theme->exists() ) {
            return;
        }

        $templates = $theme->get_page_templates();

        if ( ! is_array( $templates ) ) {
            $templates = array();
        }

        if ( ! isset( $templates[ SD_DIRECTORY_TEMPLATE_SLUG ] ) ) {
            $templates[ SD_DIRECTORY_TEMPLATE_SLUG ] = __( 'SuperDirectory Listing Page', 'super-directory' );
            $this->cache_templates_for_theme( $theme, $templates );
        }
    }

    /**
     * Flush the template cache for a theme and replace it with the latest values.
     *
     * @param WP_Theme $theme Theme instance.
     */
    private function flush_theme_template_cache( $theme ) {
        $cache_hash = md5( $theme->get_stylesheet() . $theme->get_template() );
        wp_cache_delete( 'page_templates-' . $cache_hash, 'themes' );

        $templates = $theme->get_page_templates();

        if ( ! is_array( $templates ) ) {
            $templates = array();
        }

        $templates[ SD_DIRECTORY_TEMPLATE_SLUG ] = __( 'SuperDirectory Listing Page', 'super-directory' );
        $this->cache_templates_for_theme( $theme, $templates );
    }

    /**
     * Store the provided templates for the supplied theme in WordPress' cache.
     *
     * @param WP_Theme $theme      Theme instance.
     * @param array    $templates  Template mapping.
     */
    private function cache_templates_for_theme( $theme, array $templates ) {
        $cache_hash = md5( $theme->get_stylesheet() . $theme->get_template() );
        wp_cache_set( 'page_templates-' . $cache_hash, $templates, 'themes', 1800 );
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

        $valid_templates = array( SD_DIRECTORY_TEMPLATE_SLUG );

        if ( defined( 'SD_DIRECTORY_TEMPLATE_LEGACY_SLUG' ) && SD_DIRECTORY_TEMPLATE_LEGACY_SLUG ) {
            $valid_templates[] = SD_DIRECTORY_TEMPLATE_LEGACY_SLUG;
        }

        if ( empty( $assigned_template ) || ! in_array( $assigned_template, $valid_templates, true ) ) {
            update_post_meta( $post->ID, '_wp_page_template', SD_DIRECTORY_TEMPLATE_SLUG );
            $assigned_template = SD_DIRECTORY_TEMPLATE_SLUG;
        }

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_directory_assets' ) );

        $template_file = defined( 'SD_DIRECTORY_TEMPLATE_FILE' ) ? SD_DIRECTORY_TEMPLATE_FILE : SD_DIRECTORY_TEMPLATE_SLUG;
        $plugin_template = trailingslashit( SD_PLUGIN_DIR ) . $template_file;

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
