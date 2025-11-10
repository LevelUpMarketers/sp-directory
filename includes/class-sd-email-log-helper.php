<?php
/**
 * Utility methods for storing and presenting email delivery logs.
 *
 * @package SuperDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SD_Email_Log_Helper {

    const LOG_FILENAME = 'sd-email-log.txt';

    /**
     * Cache of the resolved log path.
     *
     * @var string|null
     */
    protected static $log_path = null;

    /**
     * Retrieve the filesystem path to the log file, creating the directory/file when needed.
     *
     * @return string Empty string when the log path cannot be prepared.
     */
    public static function get_log_file_path() {
        if ( null !== self::$log_path ) {
            return self::$log_path;
        }

        $upload_dir = wp_upload_dir();

        if ( ! empty( $upload_dir['error'] ) ) {
            return '';
        }

        $directory = trailingslashit( $upload_dir['basedir'] ) . 'sd-logs';

        /**
         * Filter the directory used to store email log files.
         *
         * @since 0.1.0
         *
         * @param string $directory  Absolute directory path.
         * @param array  $upload_dir Upload directory information from {@see wp_upload_dir()}.
         */
        $directory = apply_filters( 'sd_email_log_directory', $directory, $upload_dir );
        $directory = untrailingslashit( $directory );

        if ( '' === $directory ) {
            return '';
        }

        if ( ! wp_mkdir_p( $directory ) ) {
            return '';
        }

        $path = trailingslashit( $directory ) . self::LOG_FILENAME;

        if ( ! file_exists( $path ) ) {
            $handle = @fopen( $path, 'a' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

            if ( ! $handle ) {
                return '';
            }

            fclose( $handle );
        }

        self::$log_path = $path;

        return self::$log_path;
    }

    /**
     * Determine whether email logging can write to disk.
     *
     * @return bool
     */
    public static function is_log_available() {
        return '' !== self::get_log_file_path();
    }

    /**
     * Append an email delivery record to the log file.
     *
     * @param array $args Log context with the following keys:
     *                    `template_id`, `template_title`, `recipient`, `from_name`, `from_email`,
     *                    `subject`, `body`, `context`, and `triggered_by`.
     *
     * @return bool
     */
    public static function log_email( array $args ) {
        $path = self::get_log_file_path();

        if ( '' === $path ) {
            return false;
        }

        $template_id    = isset( $args['template_id'] ) ? sanitize_key( $args['template_id'] ) : '';
        $template_title = isset( $args['template_title'] ) ? self::clean_line( $args['template_title'] ) : '';
        $recipient      = isset( $args['recipient'] ) ? self::clean_line( $args['recipient'] ) : '';
        $from_name      = isset( $args['from_name'] ) ? self::clean_line( $args['from_name'] ) : '';
        $from_email     = isset( $args['from_email'] ) ? self::clean_line( $args['from_email'] ) : '';
        $subject        = isset( $args['subject'] ) ? self::clean_line( $args['subject'] ) : '';
        $context        = isset( $args['context'] ) ? self::clean_line( $args['context'] ) : '';
        $triggered_by   = isset( $args['triggered_by'] ) ? self::clean_line( $args['triggered_by'] ) : '';
        $body           = isset( $args['body'] ) ? self::normalize_body( $args['body'] ) : '';

        $timestamp_utc = gmdate( 'c' );

        $eastern_time = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
        $time_display = $eastern_time->format( 'F j, Y g:i:s A T' );

        $template_display = $template_title;

        if ( '' === $template_display ) {
            $template_display = $template_id;
        }

        if ( '' !== $template_id && '' !== $template_display && false === strpos( $template_display, '(' . $template_id . ')' ) ) {
            $template_display .= ' (' . $template_id . ')';
        }

        $lines = array(
            '=== Email Sent ===',
            'Timestamp: ' . $timestamp_utc,
            'Time (ET): ' . $time_display,
            'Template: ' . ( $template_display ? $template_display : '—' ),
            'Recipient: ' . ( $recipient ? $recipient : '—' ),
            'From Name: ' . ( $from_name ? $from_name : '—' ),
            'From Email: ' . ( $from_email ? $from_email : '—' ),
            'Subject: ' . ( $subject ? $subject : '—' ),
        );

        if ( $context ) {
            $lines[] = 'Context: ' . $context;
        }

        if ( $triggered_by ) {
            $lines[] = 'Triggered By: ' . $triggered_by;
        }

        $lines[] = 'Body:';
        $lines[] = $body ? $body : '[No body provided]';
        $lines[] = '=== End Email Sent ===';
        $lines[] = '';

        $entry = implode( "\n", $lines );

        $result = file_put_contents( $path, $entry, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

        return false !== $result;
    }

    /**
     * Retrieve parsed log entries ordered from newest to oldest.
     *
     * @param int $limit Optional maximum number of entries to return.
     *
     * @return array
     */
    public static function get_log_entries( $limit = 0 ) {
        $path = self::get_log_file_path();

        if ( '' === $path ) {
            return array();
        }

        $contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents

        if ( false === $contents || '' === trim( $contents ) ) {
            return array();
        }

        $pattern = '/=== Email Sent ===\s*(.*?)\s*=== End Email Sent ===/s';

        if ( ! preg_match_all( $pattern, $contents, $matches ) ) {
            return array();
        }

        $entries = array();

        foreach ( $matches[1] as $block ) {
            $entry = self::parse_entry_block( $block );

            if ( ! empty( $entry ) ) {
                $entries[] = $entry;
            }
        }

        if ( empty( $entries ) ) {
            return array();
        }

        usort(
            $entries,
            function ( $a, $b ) {
                $a_time = isset( $a['timestamp'] ) ? (int) $a['timestamp'] : 0;
                $b_time = isset( $b['timestamp'] ) ? (int) $b['timestamp'] : 0;

                if ( $a_time === $b_time ) {
                    return 0;
                }

                return ( $a_time > $b_time ) ? -1 : 1;
            }
        );

        if ( $limit > 0 ) {
            $entries = array_slice( $entries, 0, $limit );
        }

        return $entries;
    }

    /**
     * Retrieve the raw contents of the log file.
     *
     * @return string
     */
    public static function get_log_contents() {
        $path = self::get_log_file_path();

        if ( '' === $path ) {
            return '';
        }

        $contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents

        if ( false === $contents ) {
            return '';
        }

        return $contents;
    }

    /**
     * Clear the log file.
     *
     * @return bool
     */
    public static function clear_log() {
        $path = self::get_log_file_path();

        if ( '' === $path ) {
            return false;
        }

        $result = file_put_contents( $path, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

        return false !== $result;
    }

    /**
     * Generate a download-safe filename for exporting the log.
     *
     * @return string
     */
    public static function get_download_filename() {
        $eastern_time = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );

        return sprintf( 'sd-email-log-%s.txt', $eastern_time->format( 'Ymd-His' ) );
    }

    /**
     * Convert a single log entry block into a structured array.
     *
     * @param string $block Entry text.
     *
     * @return array
     */
    protected static function parse_entry_block( $block ) {
        $lines = preg_split( '/\r\n|\r|\n/', trim( $block ) );

        if ( ! $lines ) {
            return array();
        }

        $data       = array();
        $body_lines = array();
        $is_body    = false;

        foreach ( $lines as $line ) {
            if ( $is_body ) {
                $body_lines[] = $line;
                continue;
            }

            if ( 0 === strpos( $line, 'Body:' ) ) {
                $body_content = trim( substr( $line, strlen( 'Body:' ) ) );

                if ( '' !== $body_content ) {
                    $body_lines[] = $body_content;
                }

                $is_body = true;
                continue;
            }

            $parts = explode( ':', $line, 2 );

            if ( count( $parts ) < 2 ) {
                continue;
            }

            $label = trim( $parts[0] );
            $value = trim( $parts[1] );

            switch ( $label ) {
                case 'Timestamp':
                    $data['timestamp_iso'] = $value;
                    $data['timestamp']     = strtotime( $value );
                    break;
                case 'Time (ET)':
                    $data['time_display'] = $value;
                    break;
                case 'Template':
                    $data['template_display'] = $value;

                    if ( preg_match( '/^(.*)\(([^)]+)\)$/', $value, $matches ) ) {
                        $data['template_title'] = trim( $matches[1] );
                        $data['template_id']    = sanitize_key( trim( $matches[2] ) );
                    } else {
                        $data['template_title'] = $value;
                        $data['template_id']    = '';
                    }
                    break;
                case 'Recipient':
                    $data['recipient'] = $value;
                    break;
                case 'From Name':
                    $data['from_name'] = $value;
                    break;
                case 'From Email':
                    $data['from_email'] = $value;
                    break;
                case 'Subject':
                    $data['subject'] = $value;
                    break;
                case 'Context':
                    $data['context'] = $value;
                    break;
                case 'Triggered By':
                    $data['triggered_by'] = $value;
                    break;
                default:
                    break;
            }
        }

        $data['body'] = trim( implode( "\n", $body_lines ) );

        if ( ! isset( $data['timestamp'] ) ) {
            $data['timestamp'] = 0;
        }

        return $data;
    }

    /**
     * Prepare a line of text for storage.
     *
     * @param string $value Raw value.
     *
     * @return string
     */
    protected static function clean_line( $value ) {
        if ( ! is_scalar( $value ) ) {
            return '';
        }

        $value = (string) $value;
        $value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) );
        $value = wp_strip_all_tags( $value );
        $value = preg_replace( '/[\r\n]+/', ' ', $value );

        return trim( $value );
    }

    /**
     * Normalize a message body for log storage.
     *
     * @param string $body Raw message body.
     *
     * @return string
     */
    protected static function normalize_body( $body ) {
        if ( ! is_scalar( $body ) ) {
            return '';
        }

        $body = (string) $body;
        $body = str_replace( array( "\r\n", "\r" ), "\n", $body );
        $body = preg_replace( '#<\s*br\s*/?>#i', "\n", $body );
        $body = preg_replace( '#</\s*p\s*>#i', "\n\n", $body );
        $body = html_entity_decode( $body, ENT_QUOTES, get_bloginfo( 'charset' ) );
        $body = wp_strip_all_tags( $body, false );
        $body = preg_replace( "/\n{3,}/", "\n\n", $body );

        return trim( $body );
    }
}
