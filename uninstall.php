<?php
/**
 * Fired when the plugin is uninstalled (deleted) from WordPress.
 *
 * @package Instant Order Status Notifier with Telegram
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Define the unique prefix for safety, if not already defined.
if ( ! defined( 'RSLDVTOST_PREFIX' ) ) {
    define( 'RSLDVTOST_PREFIX', 'rsldvtost' );
}

/**
 * Delete all plugin settings (Telegram Bot Token, Chat ID, Statuses)
 * This ensures no leftover data remains in the database after deletion.
 */
delete_option( RSLDVTOST_PREFIX . '_options' );