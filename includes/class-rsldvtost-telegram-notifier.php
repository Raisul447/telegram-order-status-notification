<?php
/**
 * Telegram Notifier Class
 *
 * @package RSLDVTOST
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles sending notifications to Telegram.
 */
class RSLDVTOST_Telegram_Notifier {

    /**
     * Initialize the notifier hooks.
     */
    public static function init() {
        // Hook into woocommerce_order_status_changed action
        add_action( 'woocommerce_order_status_changed', array( 'RSLDVTOST_Telegram_Notifier', 'send_notification_on_status_change' ), 10, 4 );
    }

    /**
     * Checks if a notification should be sent for the status change.
     *
     * @param int    $order_id   The order ID.
     * @param string $old_status The old order status (without wc- prefix).
     * @param string $new_status The new order status (without wc- prefix).
     * @param WC_Order $order    The order object.
     */
    public static function send_notification_on_status_change( $order_id, $old_status, $new_status, $order ) {
        if ( in_array( $old_status, array( 'pending', 'pending-payment' ) ) && in_array( $new_status, array( 'on-hold', 'processing' ) ) ) {
            if ( $old_status !== $new_status && $old_status !== 'pending' ) {
                // If it's a transition from 'pending' to a target, only send for the target status.
            } else if ($old_status === $new_status) {
                return; // Set do not send notification if the status remains the same.
            }
        }
        
        // Get plugin settings
        $options          = get_option( RSLDVTOST_PREFIX . '_options' );
        $bot_token        = isset( $options[ RSLDVTOST_PREFIX . '_bot_token' ] ) ? $options[ RSLDVTOST_PREFIX . '_bot_token' ] : '';
        $chat_id          = isset( $options[ RSLDVTOST_PREFIX . '_chat_id' ] ) ? $options[ RSLDVTOST_PREFIX . '_chat_id' ] : '';
        $enabled_statuses = isset( $options[ RSLDVTOST_PREFIX . '_statuses' ] ) ? (array) $options[ RSLDVTOST_PREFIX . '_statuses' ] : array();

        // Check if API credentials are set
        if ( empty( $bot_token ) || empty( $chat_id ) ) {
            return;
        }

        // Status slugs are stored as 'wc-status' in settings.
        $new_status_slug = 'wc-' . $new_status;

        // Check if the new status is one of the enabled statuses.
        if ( ! in_array( $new_status_slug, $enabled_statuses ) ) {
            return;
        }

        // Construct the message, explicitly passing the correct new status.
        $message = self::build_telegram_message( $order, $new_status );

        // Send the message.
        self::send_telegram_message( $bot_token, $chat_id, $message );
    }

    /**
     * Builds the formatted message for Telegram.
     *
     * @param WC_Order $order The WooCommerce order object.
     * @param string   $new_status_slug The new status slug (without wc- prefix).
     * @return string The formatted message.
     */
    private static function build_telegram_message( $order, $new_status_slug ) {
        $items_list = '';

        foreach ( $order->get_items() as $item ) {
            $product_name = $item->get_name();
            $quantity     = $item->get_quantity();
            // Markdown escaping for basic characters in product name
            $product_name = str_replace( ['*', '_', '`', '.', '-', '(', ')'], ['\*', '\_', '\`', '\.', '\-', '\(', '\)'], $product_name );
            $items_list .= "\n- *{$product_name}* (Qty: {$quantity})";
        }
        
        $order_status_name = wc_get_order_status_name( $new_status_slug );
        $website_name      = get_bloginfo( 'name' );
        $order_number      = $order->get_order_number();
        
        // Get raw total amount and append currency code to remove currency symbols/HTML
        $total_amount_raw = $order->get_total();
        $total_amount     = $total_amount_raw . ' ' . $order->get_currency();
        
        $payment_method    = $order->get_payment_method_title();
        $order_link        = $order->get_edit_order_url(); // Admin link

        // MESSAGE STRUCTURE
        $message = "🔔 *{$website_name} Order Notification* 🔔\n\n";
        $message .= "🛒 *Products:* {$items_list}\n";
        $message .= "🛒 *Order Number:* #{$order_number}\n";
        $message .= "🤑 *Total Amount:* {$total_amount}\n";
        $message .= "🏦 *Payment Method:* {$payment_method}\n";
        $message .= "🛎️ *Order Status:* __{$order_status_name}__\n\n"; 
        $message .= "[View Order in Dashboard]({$order_link})";
        
        return $message;
    }

    /**
     * Sends the message to the Telegram bot API.
     *
     * @param string $bot_token The Telegram Bot Token.
     * @param string $chat_id The recipient Chat ID.
     * @param string $message The message to send.
     * @return array|WP_Error The response from wp_remote_post or WP_Error.
     */
    private static function send_telegram_message( $bot_token, $chat_id, $message ) {
        $api_url = 'https://api.telegram.org/bot' . $bot_token . '/sendMessage';
        
        $args = array(
            'body' => array(
                'chat_id'    => $chat_id,
                'text'       => $message,
                'parse_mode' => 'Markdown',
            ),
            'timeout' => 10,
        );

        $response = wp_remote_post( $api_url, $args );
        
        return $response;
    }
}