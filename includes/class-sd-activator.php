<?php
/**
 * Fired during plugin activation
 *
 * @package SuperDirectory
 */

class SD_Activator {

    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $main_table      = $wpdb->prefix . 'sd_main_entity';
        $settings_table  = $wpdb->prefix . 'sd_settings';
        $content_log     = $wpdb->prefix . 'sd_content_log';

        $sql_main = "CREATE TABLE $main_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL DEFAULT '',
            category varchar(50) DEFAULT '',
            industry_vertical varchar(191) DEFAULT '',
            service_model varchar(50) DEFAULT '',
            website_url varchar(255) DEFAULT '',
            phone_number varchar(50) DEFAULT '',
            email_address varchar(191) DEFAULT '',
            state varchar(50) DEFAULT '',
            city varchar(191) DEFAULT '',
            street_address varchar(255) DEFAULT '',
            zip_code varchar(20) DEFAULT '',
            country varchar(191) DEFAULT '',
            short_description text,
            long_description_primary longtext,
            long_description_secondary longtext,
            facebook_url varchar(255) DEFAULT '',
            instagram_url varchar(255) DEFAULT '',
            youtube_url varchar(255) DEFAULT '',
            linkedin_url varchar(255) DEFAULT '',
            google_business_url varchar(255) DEFAULT '',
            logo_attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
            homepage_screenshot_id bigint(20) unsigned NOT NULL DEFAULT 0,
            gallery_image_ids longtext,
            directory_page_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_settings = "CREATE TABLE $settings_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            option_name varchar(191) NOT NULL,
            option_value longtext NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY option_name (option_name)
        ) $charset_collate;";

        $sql_content_log = "CREATE TABLE $content_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            post_type varchar(20) NOT NULL,
            entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY entity_id (entity_id)
        ) $charset_collate;";

        dbDelta( $sql_main );
        dbDelta( $sql_settings );
        dbDelta( $sql_content_log );
    }
}
