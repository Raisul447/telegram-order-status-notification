<?php
/**
 * Admin Settings Class
 *
 * @package Instant Order Status Notifier with Telegram
 */

defined( 'ABSPATH' ) || exit;

class RSLDVTOST_Admin_Settings {
    
    public static function init() {
        self::register_settings();
    }

    public static function register_settings() {
        register_setting(
            RSLDVTOST_PREFIX . '_option_group',
            RSLDVTOST_PREFIX . '_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( 'RSLDVTOST_Admin_Settings', 'sanitize_settings' ),
            )
        );
    }

    /**
     * Enqueue modern admin styles and scripts
     */
    public static function enqueue_admin_styles( $hook ) {
        if ( 'settings_page_' . RSLDVTOST_PREFIX . '-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'rsldvtost-admin-css',
            plugins_url( 'admin-settings.css', __FILE__ ),
            array(),
            '1.2.3'
        );

        wp_enqueue_script(
            'rsldvtost-admin-js',
            plugins_url( 'admin-settings.js', __FILE__ ),
            array( 'jquery' ),
            '1.2.3',
            true
        );

        wp_localize_script(
            'rsldvtost-admin-js',
            'rsldvtost_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'rsldvtost_test_connection_nonce' ),
            )
        );
    }

    public static function add_plugin_page() {
        add_options_page(
            __( 'Telegram Notifier', 'instant-order-status-notifier-with-telegram' ), 
            __( 'Telegram Notifier', 'instant-order-status-notifier-with-telegram' ),
            'manage_options',
            RSLDVTOST_PREFIX . '-settings',
            array( 'RSLDVTOST_Admin_Settings', 'create_admin_page' )
        );
    }

    public static function create_admin_page() {
        $options          = get_option( RSLDVTOST_PREFIX . '_options' );
        $bot_token        = isset( $options[ RSLDVTOST_PREFIX . '_bot_token' ] ) ? $options[ RSLDVTOST_PREFIX . '_bot_token' ] : '';
        $chat_id          = isset( $options[ RSLDVTOST_PREFIX . '_chat_id' ] ) ? $options[ RSLDVTOST_PREFIX . '_chat_id' ] : '';
        $template         = isset( $options[ RSLDVTOST_PREFIX . '_template' ] ) ? $options[ RSLDVTOST_PREFIX . '_template' ] : '';
        $checked_statuses = isset( $options[ RSLDVTOST_PREFIX . '_statuses' ] ) ? (array) $options[ RSLDVTOST_PREFIX . '_statuses' ] : array();
        
        $statuses = wc_get_order_statuses();
        ?>
        <div class="rsldvtost-settings-container">
            <div class="rsldvtost-header">
                <div class="rsldvtost-header-info">
                    <h1><svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; display: inline-block;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8l-1.67 7.88c-.12.56-.46.69-.92.43l-2.54-1.87-1.22 1.18c-.14.14-.25.26-.52.26l.18-2.58 4.7-4.25c.2-.18-.04-.28-.31-.1l-5.81 3.66-2.5-1.22c-.54-.17-.55-.54.11-.8l9.77-3.77c.45-.17.85.1.73.74z"/></svg> Telegram Notifier Settings</h1>
                    <p>Get instant WooCommerce order notifications directly in your Telegram Chat.</p>
                </div>
                <div class="rsldvtost-version-badge">v1.2.3</div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( RSLDVTOST_PREFIX . '_option_group' ); ?>
                
                <div class="rsldvtost-layout">
                    <!-- Sidebar Navigation -->
                    <aside class="rsldvtost-sidebar">
                        <nav class="rsldvtost-nav">
                            <button type="button" class="rsldvtost-nav-item active" data-tab="api">
                                🔑 API Credentials
                            </button>
                            <button type="button" class="rsldvtost-nav-item" data-tab="status">
                                ✅ Status Triggers
                            </button>
                            <button type="button" class="rsldvtost-nav-item" data-tab="template">
                                📝 Custom Template
                            </button>
                            <button type="button" class="rsldvtost-nav-item" data-tab="guide">
                                📖 Setup Guide
                            </button>
                        </nav>
                    </aside>

                    <!-- Main Panel Content -->
                    <main class="rsldvtost-main-content">
                        
                        <!-- TAB 1: API Configuration -->
                        <div id="panel-api" class="rsldvtost-tab-panel active">
                            <h2 class="rsldvtost-section-title">🔑 API Credentials</h2>
                            <p class="rsldvtost-section-subtitle">Configure your Telegram bot details to establish connectivity.</p>
                            
                            <div class="rsldvtost-card">
                                <div class="rsldvtost-form-group">
                                    <label for="rsldvtost_bot_token">Telegram Bot Token</label>
                                    <input type="password" id="rsldvtost_bot_token" name="rsldvtost_options[rsldvtost_bot_token]" value="<?php echo esc_attr( $bot_token ); ?>" class="rsldvtost-input-text" placeholder="e.g. 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ" />
                                </div>
                                
                                <div class="rsldvtost-form-group">
                                    <label for="rsldvtost_chat_id">Admin Chat ID</label>
                                    <div class="rsldvtost-input-with-button">
                                        <input type="text" id="rsldvtost_chat_id" name="rsldvtost_options[rsldvtost_chat_id]" value="<?php echo esc_attr( $chat_id ); ?>" class="rsldvtost-input-text" placeholder="e.g. 987654321" />
                                        <button type="button" id="rsldvtost_btn_fetch_chat" class="rsldvtost-btn rsldvtost-btn-secondary">Fetch Chat ID</button>
                                    </div>
                                    <p class="description" style="margin-top: 8px;">Need Chat ID? Type a message to your Telegram bot first, then click "Fetch Chat ID".</p>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <button type="button" id="rsldvtost_btn_test_conn" class="rsldvtost-btn rsldvtost-btn-action">🧪 Test Connection</button>
                            </div>
                        </div>

                        <!-- TAB 2: Status Triggers -->
                        <div id="panel-status" class="rsldvtost-tab-panel">
                            <h2 class="rsldvtost-section-title">✅ Status Triggers</h2>
                            <p class="rsldvtost-section-subtitle">Select which WooCommerce order status transitions will fire a Telegram notification.</p>
                            
                            <div class="rsldvtost-card">
                                <div class="rsldvtost-checkbox-grid">
                                    <?php foreach ( $statuses as $status_slug => $status_name ) : ?>
                                        <label class="rsldvtost-checkbox-card">
                                            <input type="checkbox" name="rsldvtost_options[rsldvtost_statuses][]" value="<?php echo esc_attr( $status_slug ); ?>" <?php checked( in_array( $status_slug, $checked_statuses ) ); ?> />
                                            <span><?php echo esc_html( $status_name ); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: Custom Template -->
                        <div id="panel-template" class="rsldvtost-tab-panel">
                            <h2 class="rsldvtost-section-title">📝 Custom Template</h2>
                            <p class="rsldvtost-section-subtitle">Design your order notification layout using shortcodes and Telegram Markdown formatting.</p>
                            
                            <div class="rsldvtost-template-editor-layout">
                                <div class="rsldvtost-template-editor-box">
                                    <div class="rsldvtost-form-group" style="margin-bottom: 0;">
                                        <label for="rsldvtost_template">Message Template</label>
                                        <textarea id="rsldvtost_template" name="rsldvtost_options[rsldvtost_template]" class="rsldvtost-textarea" placeholder="Enter template here..."><?php echo esc_textarea( $template ); ?></textarea>
                                    </div>
                                    
                                    <div class="rsldvtost-form-group">
                                        <label>Available Shortcodes (Click to Insert)</label>
                                        <div class="rsldvtost-shortcodes-list">
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{site_name}">{site_name}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{order_number}">{order_number}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{order_status}">{order_status}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{order_total}">{order_total}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{order_currency}">{order_currency}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{payment_method}">{payment_method}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{items}">{items}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{customer_name}">{customer_name}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{customer_email}">{customer_email}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{customer_phone}">{customer_phone}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{billing_address}">{billing_address}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{shipping_address}">{shipping_address}</div>
                                            <div class="rsldvtost-shortcode-pill" data-shortcode="{view_order_url}">{view_order_url}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="rsldvtost-form-group">
                                        <label>Live Telegram Preview</label>
                                        <div class="rsldvtost-telegram-preview-wrapper">
                                            <div class="rsldvtost-telegram-preview-header">
                                                <div class="rsldvtost-telegram-preview-header-avatar">🤖</div>
                                                <div>Telegram Bot</div>
                                            </div>
                                            <div class="rsldvtost-telegram-preview-body">
                                                <div class="rsldvtost-telegram-bubble"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: Setup Guide -->
                        <div id="panel-guide" class="rsldvtost-tab-panel">
                            <h2 class="rsldvtost-section-title">📖 Telegram Bot Setup Guide</h2>
                            <p class="rsldvtost-section-subtitle">Follow these quick steps to set up your Telegram bot notifications.</p>
                            
                            <div class="rsldvtost-guide-steps">
                                <div class="rsldvtost-guide-step">
                                    <div class="rsldvtost-guide-step-num">1</div>
                                    <h3>Create a Telegram Bot</h3>
                                    <p>Open Telegram, search for <strong>@BotFather</strong>, and start a chat. Send the following command to create a new bot:</p>
                                    <div class="rsldvtost-code-block">
                                        <span class="rsldvtost-code-text">/newbot</span>
                                        <button type="button" class="rsldvtost-btn-copy-code">Copy</button>
                                    </div>
                                    <p>Follow the prompts to assign a name and username. @BotFather will send you a <strong>HTTP API Token</strong> (Bot Token). Copy that token and paste it into the <strong>API Credentials</strong> tab of this plugin.</p>
                                </div>

                                <div class="rsldvtost-guide-step">
                                    <div class="rsldvtost-guide-step-num">2</div>
                                    <h3>Start the Bot Chat</h3>
                                    <p>Search for your newly created bot in Telegram by its username, click the <strong>Start</strong> button (or send a message like <code>/start</code>) to initiate communication.</p>
                                </div>

                                <div class="rsldvtost-guide-step">
                                    <div class="rsldvtost-guide-step-num">3</div>
                                    <h3>Get Your Admin Chat ID</h3>
                                    <p>To deliver notifications, we need your Telegram Chat ID. You can fetch it automatically:
                                       go to the <strong>API Credentials</strong> tab, verify your Bot Token is entered, and click <strong>Fetch Chat ID</strong>.</p>
                                    <p>Alternatively, you can query the Telegram Bot API manually by accessing this link in your browser (replace <code>[BOT_TOKEN]</code> with your actual bot token):</p>
                                    <div class="rsldvtost-code-block">
                                        <span class="rsldvtost-code-text">https://api.telegram.org/bot[BOT_TOKEN]/getUpdates</span>
                                        <button type="button" class="rsldvtost-btn-copy-code">Copy</button>
                                    </div>
                                    <p>Look for the <code>"chat":{"id":XXXXXXXXX}</code> segment in the returned JSON page. That number is your Admin Chat ID.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions Panel -->
                        <div class="rsldvtost-submit-panel">
                            <?php submit_button( 'Save Settings', 'rsldvtost-btn rsldvtost-btn-primary', 'submit', false ); ?>
                        </div>
                    </main>
                </div>
            </form>

            <div class="rsldvtost-footer">
                <span>Instant Order Status Notifier with Telegram v1.2.3</span>
                <span>•</span>
                <span>Developed by <a href="https://raisul.dev" target="_blank" rel="noopener noreferrer">Raisul Islam Shagor</a></span>
            </div>
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
            $new_input[ RSLDVTOST_PREFIX . '_statuses' ] = array_intersect( array_map( 'sanitize_text_field', $input[ RSLDVTOST_PREFIX . '_statuses' ] ), $valid_statuses );
        }
        if ( isset( $input[ RSLDVTOST_PREFIX . '_template' ] ) ) {
            $new_input[ RSLDVTOST_PREFIX . '_template' ] = sanitize_textarea_field( $input[ RSLDVTOST_PREFIX . '_template' ] );
        }
        return $new_input;
    }

    /**
     * AJAX action to test Telegram bot connection
     */
    public static function ajax_test_connection() {
        check_ajax_referer( 'rsldvtost_test_connection_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized user.' ) );
        }
        
        $bot_token = isset( $_POST['bot_token'] ) ? sanitize_text_field( wp_unslash( $_POST['bot_token'] ) ) : '';
        $chat_id   = isset( $_POST['chat_id'] ) ? sanitize_text_field( wp_unslash( $_POST['chat_id'] ) ) : '';
        
        if ( empty( $bot_token ) || empty( $chat_id ) ) {
            wp_send_json_error( array( 'message' => 'Bot Token and Chat ID are required.' ) );
        }
        
        $test_message = "🧪 *Instant Order Status Notifier with Telegram*\n\nYour bot integration is set up correctly and working! 🎉";
        
        $response = RSLDVTOST_Telegram_Notifier::send_telegram_message( $bot_token, $chat_id, $test_message );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( $code === 200 && isset( $body['ok'] ) && $body['ok'] === true ) {
            wp_send_json_success( array( 'message' => 'Connection test successful! A test notification has been sent.' ) );
        } else {
            $desc = isset( $body['description'] ) ? $body['description'] : 'Unknown Telegram API Error';
            wp_send_json_error( array( 'message' => 'Telegram API Error: ' . $desc . ' (HTTP ' . $code . ')' ) );
        }
    }
 
    /**
     * AJAX action to fetch latest Chat ID from Telegram getUpdates
     */
    public static function ajax_fetch_chat_id() {
        check_ajax_referer( 'rsldvtost_test_connection_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized user.' ) );
        }
        
        $bot_token = isset( $_POST['bot_token'] ) ? sanitize_text_field( wp_unslash( $_POST['bot_token'] ) ) : '';
        
        if ( empty( $bot_token ) ) {
            wp_send_json_error( array( 'message' => 'Bot Token is required to fetch updates.' ) );
        }
        
        $api_url  = 'https://api.telegram.org/bot' . $bot_token . '/getUpdates';
        $response = wp_remote_get( $api_url, array( 'timeout' => 10 ) );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Failed to reach Telegram API: ' . $response->get_error_message() ) );
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( $code !== 200 || ! isset( $body['ok'] ) || $body['ok'] !== true ) {
            $desc = isset( $body['description'] ) ? $body['description'] : 'Unknown error from Telegram API';
            wp_send_json_error( array( 'message' => 'Telegram Error: ' . $desc . ' (HTTP ' . $code . ')' ) );
        }
        
        if ( empty( $body['result'] ) ) {
            wp_send_json_error( array( 'message' => 'No messages found for this bot. Please search for the bot username in Telegram, click "Start" or send a message, then try again!' ) );
        }
        
        // Loop backwards to find the latest valid chat ID
        $chat_id    = null;
        $chat_title = '';
        $updates    = array_reverse( $body['result'] );
        
        foreach ( $updates as $update ) {
            if ( isset( $update['message']['chat']['id'] ) ) {
                $chat_id    = $update['message']['chat']['id'];
                $first_name = isset( $update['message']['chat']['first_name'] ) ? $update['message']['chat']['first_name'] : '';
                $last_name  = isset( $update['message']['chat']['last_name'] ) ? $update['message']['chat']['last_name'] : '';
                $username   = isset( $update['message']['chat']['username'] ) ? '@' . $update['message']['chat']['username'] : '';
                $chat_title = trim( $first_name . ' ' . $last_name );
                if ( empty( $chat_title ) && ! empty( $username ) ) {
                    $chat_title = $username;
                }
                break;
            } elseif ( isset( $update['edited_message']['chat']['id'] ) ) {
                $chat_id = $update['edited_message']['chat']['id'];
                break;
            }
        }
        
        if ( $chat_id ) {
            $display_msg = 'Successfully fetched Chat ID: ' . $chat_id;
            if ( ! empty( $chat_title ) ) {
                $display_msg .= ' (' . $chat_title . ')';
            }
            wp_send_json_success( array(
                'chat_id' => $chat_id,
                'message' => $display_msg
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Could not retrieve a valid Chat ID from the latest updates.' ) );
        }
    }
}
