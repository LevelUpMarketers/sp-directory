<?php
/**
 * Gutenberg block mirroring the main entity shortcode.
 *
 * @package Codex_Plugin_Boilerplate
 */

class CPB_Block_Main_Entity {

    public function register() {
        add_action( 'init', array( $this, 'register_block' ) );
    }

    public function register_block() {
        wp_register_script(
            'cpb-block-main-entity',
            CPB_PLUGIN_URL . 'assets/js/blocks/main-entity.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor' ),
            CPB_VERSION,
            true
        );

        wp_register_style(
            'cpb-block-main-entity',
            CPB_PLUGIN_URL . 'assets/css/blocks/main-entity.css',
            array(),
            CPB_VERSION
        );

        register_block_type( 'cpb/main-entity', array(
            'editor_script'   => 'cpb-block-main-entity',
            'editor_style'    => 'cpb-block-main-entity',
            'style'           => 'cpb-block-main-entity',
            'render_callback' => array( $this, 'render' ),
        ) );
    }

    public function render( $attributes, $content ) {
        $shortcode = new CPB_Shortcode_Main_Entity();
        return $shortcode->render();
    }
}
