<?php
/*
Plugin Name: Instant Order Status Notifier with Telegram
Plugin URI:  https://raisul.dev/projects/instant-order-status-notifier-with-telegram
Description: This plugin sends order status change notifications directly to an admin's Telegram bot.
Version:     1.2.3
Author:      Raisul Islam Shagor
Author URI:  https://raisul.dev
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: woocommerce
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: instant-order-status-notifier-with-telegram 
*/

defined( 'ABSPATH' ) || exit;

// New Slug: Completely removed "WooCommerce" to comply with latest feedback
define( 'RSLDVTOST_TEXT_DOMAIN', 'instant-order-status-notifier-with-telegram' ); 
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
            add_action( 'admin_notices', array( $this, 'admin_notice_woocommerce_missing' ) );
            return;
        }

        $this->includes();
        $this->hooks();
    }

    private function is_woocommerce_active() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active( 'woocommerce/woocommerce.php' );
    }

    public function admin_notice_woocommerce_missing() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Instant Order Status Notifier with Telegram', 'instant-order-status-notifier-with-telegram' ); ?></strong>
                <?php esc_html_e( 'requires WooCommerce to be installed and active.', 'instant-order-status-notifier-with-telegram' ); ?>
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
        add_filter( 'plugin_action_links_' . RSLDVTOST_BASENAME, array( $this, 'add_settings_link' ) );
        add_action( 'plugins_loaded', array( 'RSLDVTOST_Telegram_Notifier', 'init' ) );
        add_action( 'admin_enqueue_scripts', array( 'RSLDVTOST_Admin_Settings', 'enqueue_admin_styles' ) );
        add_action( 'wp_ajax_rsldvtost_test_connection', array( 'RSLDVTOST_Admin_Settings', 'ajax_test_connection' ) );
        add_action( 'wp_ajax_rsldvtost_fetch_chat_id', array( 'RSLDVTOST_Admin_Settings', 'ajax_fetch_chat_id' ) );
    }

    public function add_settings_link( $links ) {
        // Text domain updated to the new slug
        $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . RSLDVTOST_PREFIX . '-settings' ) ) . '">' . esc_html__( 'Settings', 'instant-order-status-notifier-with-telegram' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }
}

function rsldvtost_run() {
    return RSLDVTOST_Plugin::instance();
}

rsldvtost_run();