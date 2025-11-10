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
            placeholder_1 varchar(191) DEFAULT '',
            placeholder_2 varchar(191) DEFAULT '',
            placeholder_3 date DEFAULT NULL,
            placeholder_4 tinyint(1) DEFAULT 0,
            placeholder_5 time DEFAULT NULL,
            placeholder_6 time DEFAULT NULL,
            placeholder_7 tinyint(1) DEFAULT 0,
            placeholder_8 varchar(191) DEFAULT '',
            placeholder_9 varchar(191) DEFAULT '',
            placeholder_10 varchar(191) DEFAULT '',
            placeholder_11 varchar(50) DEFAULT '',
            placeholder_12 varchar(191) DEFAULT '',
            placeholder_13 varchar(191) DEFAULT '',
            placeholder_14 varchar(255) DEFAULT '',
            placeholder_15 varchar(50) DEFAULT '',
            placeholder_16 decimal(10,2) DEFAULT 0.00,
            placeholder_17 decimal(10,2) DEFAULT 0.00,
            placeholder_18 decimal(10,2) DEFAULT 0.00,
            placeholder_19 tinyint(1) DEFAULT 0,
            placeholder_20 tinyint(1) DEFAULT 0,
            placeholder_21 varchar(50) DEFAULT '',
            placeholder_22 varchar(191) DEFAULT '',
            placeholder_23 varchar(50) DEFAULT '',
            placeholder_24 longtext,
            placeholder_25 longtext,
            placeholder_26 varchar(20) DEFAULT '',
            placeholder_27 bigint(20) unsigned DEFAULT 0,
            placeholder_28 longtext,
            opt_in_marketing_email tinyint(1) DEFAULT 0,
            opt_in_marketing_sms tinyint(1) DEFAULT 0,
            opt_in_event_update_email tinyint(1) DEFAULT 0,
            opt_in_event_update_sms tinyint(1) DEFAULT 0,
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
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        dbDelta( $sql_main );
        dbDelta( $sql_settings );
        dbDelta( $sql_content_log );
    }
}
