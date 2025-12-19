<?php
/**
 * Favor Shipping Logger Class
 *
 * Provides a centralized logging system for the Favor Despaches plugin.
 *
 * @package Favor_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * WC_Favor_Shipping_Logger class.
 */
class WC_Favor_Shipping_Logger {

    /**
     * Log source identifier.
     *
     * @var string
     */
    const LOG_SOURCE = 'favor-shipping-plugin';

    /**
     * WooCommerce logger instance.
     *
     * @var WC_Logger|null
     */
    private static $logger = null;

    /**
     * Get the WooCommerce logger instance.
     *
     * @return WC_Logger
     */
    private static function get_logger() {
        if ( null === self::$logger ) {
            self::$logger = wc_get_logger();
        }
        return self::$logger;
    }

    /**
     * Log a message with the specified level.
     *
     * @param string $level   Log level (debug, info, notice, warning, error, critical, alert, emergency).
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function log( $level, $message, $context = array() ) {
        $logger = self::get_logger();
        $context['source'] = self::LOG_SOURCE;
        $logger->log( $level, $message, $context );
    }

    /**
     * Log a debug message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function debug( $message, $context = array() ) {
        self::log( 'debug', $message, $context );
    }

    /**
     * Log an info message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function info( $message, $context = array() ) {
        self::log( 'info', $message, $context );
    }

    /**
     * Log a warning message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function warning( $message, $context = array() ) {
        self::log( 'warning', $message, $context );
    }

    /**
     * Log an error message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function error( $message, $context = array() ) {
        self::log( 'error', $message, $context );
    }

    /**
     * Log a critical message.
     *
     * @param string $message The log message.
     * @param array  $context Additional context data.
     */
    public static function critical( $message, $context = array() ) {
        self::log( 'critical', $message, $context );
    }

    /**
     * Get the log file path.
     *
     * @return string|false The log file path or false if not found.
     */
    public static function get_log_file_path() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/wc-logs/';
        
        // Find the log file for our source
        $files = glob( $log_dir . self::LOG_SOURCE . '-*.log' );
        
        if ( ! empty( $files ) ) {
            // Sort by modification time, newest first
            usort( $files, function( $a, $b ) {
                return filemtime( $b ) - filemtime( $a );
            });
            return $files[0];
        }
        
        return false;
    }

    /**
     * Get the log contents.
     *
     * @param int $lines Maximum number of lines to return (0 for all).
     * @return string The log contents.
     */
    public static function get_logs( $lines = 0 ) {
        $log_file = self::get_log_file_path();
        
        if ( ! $log_file || ! file_exists( $log_file ) ) {
            return __( 'Nenhum log encontrado.', 'favor-despaches-woocommerce-plugin' );
        }

        $contents = file_get_contents( $log_file );
        
        if ( $lines > 0 ) {
            $log_lines = explode( "\n", $contents );
            $log_lines = array_slice( $log_lines, -$lines );
            $contents = implode( "\n", $log_lines );
        }

        return $contents;
    }

    /**
     * Clear the log file.
     *
     * @return bool True on success, false on failure.
     */
    public static function clear_logs() {
        $log_file = self::get_log_file_path();
        
        if ( $log_file && file_exists( $log_file ) ) {
            return unlink( $log_file );
        }
        
        return true;
    }
}
