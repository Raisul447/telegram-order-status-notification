<?php
/**
 * Telegram Notifier Class
 */

defined( 'ABSPATH' ) || exit;

class RSLDVTOST_Telegram_Notifier {

    public static function init() {
        add_action( 'woocommerce_order_status_changed', array( 'RSLDVTOST_Telegram_Notifier', 'send_notification_on_status_change' ), 10, 4 );
    }

    public static function send_notification_on_status_change( $order_id, $old_status, $new_status, $order ) {
        if ( $old_status === $new_status ) return;
        
        $options          = get_option( RSLDVTOST_PREFIX . '_options' );
        $bot_token        = isset( $options[ RSLDVTOST_PREFIX . '_bot_token' ] ) ? $options[ RSLDVTOST_PREFIX . '_bot_token' ] : '';
        $chat_id          = isset( $options[ RSLDVTOST_PREFIX . '_chat_id' ] ) ? $options[ RSLDVTOST_PREFIX . '_chat_id' ] : '';
        $enabled_statuses = isset( $options[ RSLDVTOST_PREFIX . '_statuses' ] ) ? (array) $options[ RSLDVTOST_PREFIX . '_statuses' ] : array();

        if ( empty( $bot_token ) || empty( $chat_id ) || ! in_array( 'wc-' . $new_status, $enabled_statuses ) ) {
            return;
        }

        $message = self::build_telegram_message( $order, $new_status );
        self::send_telegram_message( $bot_token, $chat_id, $message );
    }

    private static function build_telegram_message( $order, $new_status_slug ) {
        $options  = get_option( RSLDVTOST_PREFIX . '_options' );
        $template = isset( $options[ RSLDVTOST_PREFIX . '_template' ] ) ? $options[ RSLDVTOST_PREFIX . '_template' ] : self::get_default_template();

        if ( empty( trim( $template ) ) ) {
            $template = self::get_default_template();
        }

        // Build items list
        $items_list = '';
        foreach ( $order->get_items() as $item ) {
            $product_name = str_replace( ['*', '_', '`', '.', '-', '(', ')'], ['\*', '\_', '\`', '\.', '\-', '\(', '\)'], $item->get_name() );
            $items_list  .= "- *{$product_name}* (Qty: " . $item->get_quantity() . ")\n";
        }
        $items_list = rtrim( $items_list );

        // Extract WooCommerce Order info
        $site_name        = get_bloginfo( 'name' );
        $order_number     = $order->get_order_number();
        $order_status     = wc_get_order_status_name( $new_status_slug );
        $order_total      = $order->get_total();
        $order_currency   = $order->get_currency();
        $payment_method   = $order->get_payment_method_title();
        $customer_name    = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $customer_email   = $order->get_billing_email();
        $customer_phone   = $order->get_billing_phone();
        $billing_address  = str_replace( '<br/>', "\n", $order->get_formatted_billing_address() );
        $shipping_address = str_replace( '<br/>', "\n", $order->get_formatted_shipping_address() );
        $view_order_url   = $order->get_edit_order_url();

        // Placeholders array
        $placeholders = array(
            '{site_name}'        => $site_name,
            '{order_number}'     => $order_number,
            '{order_status}'     => $order_status,
            '{order_total}'      => $order_total,
            '{order_currency}'   => $order_currency,
            '{payment_method}'   => $payment_method,
            '{items}'            => $items_list,
            '{customer_name}'    => $customer_name,
            '{customer_email}'   => $customer_email,
            '{customer_phone}'   => $customer_phone,
            '{billing_address}'  => $billing_address,
            '{shipping_address}' => $shipping_address,
            '{view_order_url}'   => '[View Order in Dashboard](' . $view_order_url . ')',
        );

        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
    }

    public static function get_default_template() {
        return "🔔 *{site_name} Order Notification* 🔔\n\n" .
               "🛒 *Order Number:* #{order_number}\n" .
               "🛎️ *Order Status:* _{order_status}_\n" .
               "🤑 *Total Amount:* {order_total} {order_currency}\n" .
               "🏦 *Payment Method:* {payment_method}\n" .
               "📦 *Products:*\n{items}\n\n" .
               "{view_order_url}";
    }

    public static function send_telegram_message( $bot_token, $chat_id, $message ) {
        $api_url = 'https://api.telegram.org/bot' . $bot_token . '/sendMessage';
        return wp_remote_post( $api_url, array(
            'body' => array(
                'chat_id'    => $chat_id,
                'text'       => $message,
                'parse_mode' => 'Markdown',
            ),
            'timeout' => 10,
        ));
    }
}
