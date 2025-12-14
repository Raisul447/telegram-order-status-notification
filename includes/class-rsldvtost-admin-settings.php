<?php
/**
 * Admin Settings Class
 *
 * @package RSLDVTOST
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the plugin admin settings page and options management.
 */
class RSLDVTOST_Admin_Settings {
    
    const TEXT_DOMAIN = 'telegram-order-status-notification';

    public static function init() {
        self::register_settings();
    }

    public static function register_settings() {
        register_setting(
            RSLDVTOST_PREFIX . '_option_group',
            RSLDVTOST_PREFIX . '_options',
            array( 'RSLDVTOST_Admin_Settings', 'sanitize_settings' )
        );

        add_settings_section(
            RSLDVTOST_PREFIX . '_api_section',
            'Telegram API Settings',
            array( 'RSLDVTOST_Admin_Settings', 'api_section_info' ),
            RSLDVTOST_PREFIX . '-settings'
        );
        add_settings_field(
            RSLDVTOST_PREFIX . '_bot_token',
            'Telegram Bot Token',
            array( 'RSLDVTOST_Admin_Settings', 'bot_token_callback' ),
            RSLDVTOST_PREFIX . '-settings',
            RSLDVTOST_PREFIX . '_api_section'
        );
        add_settings_field(
            RSLDVTOST_PREFIX . '_chat_id',
            'Admin Chat ID',
            array( 'RSLDVTOST_Admin_Settings', 'chat_id_callback' ),
            RSLDVTOST_PREFIX . '-settings',
            RSLDVTOST_PREFIX . '_api_section'
        );
        
        add_settings_section(
            RSLDVTOST_PREFIX . '_status_section',
            'Order Status Notifications',
            array( 'RSLDVTOST_Admin_Settings', 'status_section_info' ),
            RSLDVTOST_PREFIX . '-settings'
        );

        add_settings_field(
            RSLDVTOST_PREFIX . '_statuses',
            'Enable Notifications For:',
            array( 'RSLDVTOST_Admin_Settings', 'statuses_callback' ),
            RSLDVTOST_PREFIX . '-settings',
            RSLDVTOST_PREFIX . '_status_section'
        );
    }

    /**
     * Add the plugin settings page to the WordPress dashboard menu.
     */
    public static function add_plugin_page() {
        add_options_page(
            __( 'Telegram Notification', 'telegram-order-status-notification' ), 
            __( 'Telegram Notification', 'telegram-order-status-notification' ),
            'manage_options',
            RSLDVTOST_PREFIX . '-settings',
            array( 'RSLDVTOST_Admin_Settings', 'create_admin_page' )
        );
    }

    /**
     * Admin settings page content.
     */
    public static function create_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( RSLDVTOST_PREFIX . '_option_group' );
                do_settings_sections( RSLDVTOST_PREFIX . '-settings' ); 
                submit_button( 'Save Changes', 'primary' ); 
                ?>
            </form>
            
            <style>
                /* Used internal css */
                .rsldvtost-instruction-box {
                    background: #eef5ff;
                    border-left: 5px solid #007cba;
                    padding: 15px;
                    margin: 10px 0 20px 0;
                    border-radius: 3px;
                }
                .rsldvtost-instruction-box h3 {
                    margin-top: 0;
                    color: #007cba;
                    font-size: 1.1em;
                    padding-bottom: 5px;
                }
                .rsldvtost-checkbox-container {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 10px;
                }
                .rsldvtost-checkbox-container label {
                    display: block;
                    padding: 5px 0;
                    font-weight: 500;
                }
                .rsldvtost-checkbox-container input {
                    margin-right: 8px;
                }
                .form-table th {
                    width: 200px;
                }
            </style>
        </div>
        <?php
    }

    public static function sanitize_settings( $input ) {
        $new_input = array();
        if ( isset( $input[ RSLDVTOST_PREFIX . '_bot_token' ] ) ) {
            $new_input[ RSLDVTOST_PREFIX . '_bot_token' ] = sanitize_text_field( $input[ RSLDVTOST_PREFIX . '_bot_token' ] );
        }

        if ( isset( $input[ RSLDVTOST_PREFIX . '_chat_id' ] ) ) {
            $new_input[ RSLDVTOST_PREFIX . '_chat_id' ] = sanitize_text_field( $input[ RSLDVTOST_PREFIX . '_chat_id' ] );
        }

        if ( isset( $input[ RSLDVTOST_PREFIX . '_statuses' ] ) && is_array( $input[ RSLDVTOST_PREFIX . '_statuses' ] ) ) {
            $valid_statuses = array_keys( wc_get_order_statuses() );
            $sanitized_statuses = array_intersect( array_map( 'sanitize_text_field', $input[ RSLDVTOST_PREFIX . '_statuses' ] ), $valid_statuses );
            $new_input[ RSLDVTOST_PREFIX . '_statuses' ] = $sanitized_statuses;
        } else {
            $new_input[ RSLDVTOST_PREFIX . '_statuses' ] = array();
        }

        return $new_input;
    }
    
    public static function api_section_info() {
        ?>
        <div class="rsldvtost-instruction-box">
            <h3>🤖 Telegram Bot Setup Guide:</h3>
            <ol>
                <li><strong>Create a Bot:</strong> Open Telegram and search for <strong>@BotFather</strong>. Use the command <code>/newbot</code> and follow the instructions to get your <strong>Bot Token</strong> (e.g., <code>123456:ABC-DEF...</code>).</li>
                <li><strong>Get Admin Chat ID:</strong>
                    <ul>
                        <li><strong>Private Chat (Recommended):</strong> Start a chat with your new bot and send any message (e.g., <code>/start</code>). Then, open this URL in your browser, replacing <code>[YOUR_BOT_TOKEN]</code> with your token: <br><code>https://api.telegram.org/bot[YOUR_BOT_TOKEN]/getUpdates</code>. Look for the <code>"id"</code> field inside the <code>"chat"</code> object. This is your <strong>Admin Chat ID</strong> (usually a positive number, e.g., <code>123456789</code>).</li>
                        <li><strong>Group/Channel:</strong> For a group/channel, the ID usually starts with <code>-100</code>.</li>
                    </ul>
                </li>
                <li>Fill in the fields below with the retrieved credentials.</li>
            </ol>
        </div>
        <?php
        echo '<p>Enter your Telegram Bot Token and Admin Chat ID to enable notifications.</p>';
    }

    /**
     * Callback for Bot Token field.
     */
    public static function bot_token_callback() {
        $options = get_option( RSLDVTOST_PREFIX . '_options' );
        $token   = isset( $options[ RSLDVTOST_PREFIX . '_bot_token' ] ) ? $options[ RSLDVTOST_PREFIX . '_bot_token' ] : '';

        printf(
            '<input type="text" id="%1$s_bot_token" name="%1$s_options[%1$s_bot_token]" value="%2$s" class="regular-text" placeholder="e.g. 123456:ABC-DEF1234ghIkl-456_jkl456" />',
            esc_attr( RSLDVTOST_PREFIX ),
            esc_attr( $token )
        );
    }

    /**
     * Callback for Chat ID field.
     */
    public static function chat_id_callback() {
        $options = get_option( RSLDVTOST_PREFIX . '_options' );
        $chat_id = isset( $options[ RSLDVTOST_PREFIX . '_chat_id' ] ) ? $options[ RSLDVTOST_PREFIX . '_chat_id' ] : '';

        printf(
            '<input type="text" id="%1$s_chat_id" name="%1$s_options[%1$s_chat_id]" value="%2$s" class="regular-text" placeholder="e.g. 123456789 or -1001234567890" />',
            esc_attr( RSLDVTOST_PREFIX ),
            esc_attr( $chat_id )
        );
    }

    public static function status_section_info() {
        echo '<p>Select which order status changes should trigger a Telegram notification.</p>';
    }

    /**
     * Callback for Order Statuses checkboxes.
     */
    public static function statuses_callback() {
        $statuses = wc_get_order_statuses();
        $options  = get_option( RSLDVTOST_PREFIX . '_options' );
        $checked_statuses = isset( $options[ RSLDVTOST_PREFIX . '_statuses' ] ) ? (array) $options[ RSLDVTOST_PREFIX . '_statuses' ] : array();

        echo '<div class="rsldvtost-checkbox-container">';
        
        foreach ( $statuses as $status_slug => $status_name ) {
            $id = RSLDVTOST_PREFIX . '_statuses_' . str_replace( 'wc-', '', $status_slug );
            $name_attr = RSLDVTOST_PREFIX . '_options[' . RSLDVTOST_PREFIX . '_statuses][]';
            $checked = in_array( $status_slug, $checked_statuses ) ? 'checked="checked"' : '';

            printf(
                '<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="%3$s" %4$s /> %5$s</label>',
                esc_attr( $id ),
                esc_attr( $name_attr ),
                esc_attr( $status_slug ),
                esc_attr( $checked ),
                esc_html( $status_name )
            );
        }

        echo '</div>';
    }
}