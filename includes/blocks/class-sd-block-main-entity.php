<?php
/**
 * Gutenberg block mirroring the directory listing shortcode.
 *
 * @package SuperDirectory
 */

class SD_Block_Main_Entity {

    public function register() {
        add_action( 'init', array( $this, 'register_block' ) );
    }

    public function register_block() {
        wp_register_script(
            'sd-block-main-entity',
            SD_PLUGIN_URL . 'assets/js/blocks/main-entity.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor' ),
            SD_VERSION,
            true
        );

        wp_register_style(
            'sd-block-main-entity',
            SD_PLUGIN_URL . 'assets/css/blocks/main-entity.css',
            array(),
            SD_VERSION
        );

        register_block_type( 'sd/main-entity', array(
            'editor_script'   => 'sd-block-main-entity',
            'editor_style'    => 'sd-block-main-entity',
            'style'           => 'sd-block-main-entity',
            'render_callback' => array( $this, 'render' ),
        ) );
    }

    public function render( $attributes, $content ) {
        $shortcode = new SD_Shortcode_Main_Entity();
        return $shortcode->render();
    }
}
