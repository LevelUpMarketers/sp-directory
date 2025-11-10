<?php
/**
 * The core plugin class.
 *
 * @package SuperDirectory
 */

class SD_Plugin {

    private $i18n;
    private $admin;
    private $ajax;
    private $shortcode;
    private $block;
    private $content_logger;
    private $template_loader;

    public function __construct() {
        $this->i18n     = new SD_I18n();
        $this->admin    = new SD_Admin();
        $this->ajax     = new SD_Ajax();
        $this->shortcode      = new SD_Shortcode_Main_Entity();
        $this->block          = new SD_Block_Main_Entity();
        $this->content_logger = new SD_Content_Logger();
        $this->template_loader = new SD_Template_Loader();
    }

    public function run() {
        $this->i18n->load_textdomain();
        $this->admin->register();
        $this->ajax->register();
        $this->shortcode->register();
        $this->block->register();
        $this->content_logger->register();
        $this->template_loader->register();
    }
}
