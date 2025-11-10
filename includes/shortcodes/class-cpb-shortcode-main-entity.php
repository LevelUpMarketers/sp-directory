<?php
/**
 * Shortcode for displaying main entities.
 *
 * @package Codex_Plugin_Boilerplate
 */

class CPB_Shortcode_Main_Entity {

    public function register() {
        add_shortcode( 'cpb-main-entity', array( $this, 'render' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function render( $atts = array(), $content = '' ) {
        return '<div class="cpb-main-entity">' . esc_html__( 'Main Entity Output', 'codex-plugin-boilerplate' ) . '</div>';
    }

    public function enqueue_assets() {
        if ( is_singular() ) {
            global $post;
            if ( has_shortcode( $post->post_content, 'cpb-main-entity' ) ) {
                wp_enqueue_style( 'cpb-shortcode-main-entity', CPB_PLUGIN_URL . 'assets/css/shortcodes/main-entity.css', array(), CPB_VERSION );
                wp_enqueue_script( 'cpb-shortcode-main-entity', CPB_PLUGIN_URL . 'assets/js/shortcodes/main-entity.js', array(), CPB_VERSION, true );
            }
        }
    }
}
