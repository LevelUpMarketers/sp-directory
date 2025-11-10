<?php
/**
 * Shortcode for displaying directory listings.
 *
 * @package SuperDirectory
 */

class SD_Shortcode_Main_Entity {

    public function register() {
        add_shortcode( 'sd-main-entity', array( $this, 'render' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function render( $atts = array(), $content = '' ) {
        return '<div class="sd-main-entity">' . esc_html__( 'Directory Listing Output', 'super-directory' ) . '</div>';
    }

    public function enqueue_assets() {
        if ( is_singular() ) {
            global $post;
            if ( has_shortcode( $post->post_content, 'sd-main-entity' ) ) {
                wp_enqueue_style( 'sd-shortcode-main-entity', SD_PLUGIN_URL . 'assets/css/shortcodes/main-entity.css', array(), SD_VERSION );
                wp_enqueue_script( 'sd-shortcode-main-entity', SD_PLUGIN_URL . 'assets/js/shortcodes/main-entity.js', array(), SD_VERSION, true );
            }
        }
    }
}
