<?php
/**
 * The core plugin class.
 *
 * @package Codex_Plugin_Boilerplate
 */

class CPB_Plugin {

    private $i18n;
    private $admin;
    private $ajax;
    private $shortcode;
    private $block;
    private $content_logger;
    private $cron_manager;

    public function __construct() {
        $this->i18n     = new CPB_I18n();
        $this->admin    = new CPB_Admin();
        $this->ajax     = new CPB_Ajax();
        $this->shortcode      = new CPB_Shortcode_Main_Entity();
        $this->block          = new CPB_Block_Main_Entity();
        $this->content_logger = new CPB_Content_Logger();
        $this->cron_manager   = new CPB_Cron_Manager();
    }

    public function run() {
        $this->i18n->load_textdomain();
        $this->admin->register();
        $this->ajax->register();
        $this->shortcode->register();
        $this->block->register();
        $this->content_logger->register();
        $this->cron_manager->register();
    }
}
