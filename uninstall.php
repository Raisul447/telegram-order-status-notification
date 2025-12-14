<?php
/**
 * Fired when the plugin is uninstalled (deleted) from WordPress.
 *
 * @package RSLDVTOST
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( ! defined( 'RSLDVTOST_PREFIX' ) ) {
    define( 'RSLDVTOST_PREFIX', 'rsldvtost' );
}

// Delete all plugin settings (Telegram Bot Token, Chat ID, Statuses)
delete_option( RSLDVTOST_PREFIX . '_options' );