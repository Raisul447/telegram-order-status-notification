<?php
/*
Plugin Name: Telegram Order Status Notification
Plugin URI:  https://raisul.dev/projects/telegram-order-status-notification-for-woocommerce
Description: This plugin sends WooCommerce order status change notifications directly to an admin's Telegram bot.
Version:     1.0.2
Author:      Raisul Islam Shagor
Author URI:  https://raisul.dev
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Requires Plugins: woocommerce
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: telegram-order-status-notification
*/

defined( 'ABSPATH' ) || exit;

// Constants
define( 'RSLDVTOST_TEXT_DOMAIN', 'telegram-order-status-notification' );
define( 'RSLDVTOST_PREFIX', 'rsldvtost' );
define( 'RSLDVTOST_PATH', plugin_dir_path( __FILE__ ) );
define( 'RSLDVTOST_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
final class RSLDVTOST_Plugin {

    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {

        if ( ! $this->is_woocommerce_active() ) {
            add_action(
                'admin_notices',
                array( $this, 'admin_notice_woocommerce_missing' )
            );
            return;
        }

        $this->includes();
        $this->hooks();
    }

    /**
     * Check WooCommerce status (Plugin Check SAFE)
     */
    private function is_woocommerce_active() {

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active( 'woocommerce/woocommerce.php' );
    }

    /**
     * Admin notice
     */
    public function admin_notice_woocommerce_missing() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Telegram Order Status Notification', 'telegram-order-status-notification' ); ?></strong>
                <?php esc_html_e( 'requires WooCommerce to be installed and active.', 'telegram-order-status-notification' ); ?>
            </p>
        </div>
        <?php
    }

    private function includes() {
        require_once RSLDVTOST_PATH . 'includes/class-rsldvtost-admin-settings.php';
        require_once RSLDVTOST_PATH . 'includes/class-rsldvtost-telegram-notifier.php';
    }

    private function hooks() {
        add_action( 'admin_init', array( 'RSLDVTOST_Admin_Settings', 'init' ) );
        add_action( 'admin_menu', array( 'RSLDVTOST_Admin_Settings', 'add_plugin_page' ) );
        add_filter(
            'plugin_action_links_' . RSLDVTOST_BASENAME,
            array( $this, 'add_settings_link' )
        );
        add_action( 'plugins_loaded', array( 'RSLDVTOST_Telegram_Notifier', 'init' ) );
    }

    /**
     * Settings link
     */
    public function add_settings_link( $links ) {

        $settings_link = '<a href="' .
            esc_url(
                admin_url(
                    'options-general.php?page=' . RSLDVTOST_PREFIX . '-settings'
                )
            ) .
            '">' .
            esc_html__( 'Settings', 'telegram-order-status-notification' ) .
            '</a>';

        array_unshift( $links, $settings_link );
        return $links;
    }
}

/**
 * Run plugin
 */
function rsldvtost_run() {
    return RSLDVTOST_Plugin::instance();
}

rsldvtost_run();