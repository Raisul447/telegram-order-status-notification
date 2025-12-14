=== Telegram Order Status Notification ===
Contributors: @shagor447
Tags: woocommerce, telegram, notification, order, admin
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.2
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin sends WooCommerce order status change notifications directly to an admin's Telegram bot.

== Description ==

This plugin provides instant order notifications for WooCommerce directly to your Telegram chat. Configure your Telegram Bot Token and Chat ID, select the order statuses you wish to track, and receive timely alerts every time an order status changes on your site.

**Features:**
* Instant Telegram notifications for WooCommerce order status changes.
* Selectable order statuses (Pending, Processing, On-hold, Completed, etc.).
* Easy setup instructions for Telegram Bot integration.
* Clean, easy to configure settings interface.
* Customizable notification message structure.

== Installation ==

1.  Upload the entire 'telegram-order-status-notification' folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Settings > Telegram Notification** to configure your Bot Token and Admin Chat ID.
4.  Select the order statuses for which you want to receive notifications and click 'Save Changes'.

== Changelog ==

= 1.0.2 =
* FIX: Fully resolved a critical error on the settings page and ensured proper internationalization (i18n) usage.
* FIX: Prevented duplicate Telegram notifications and removed unwanted HTML and currency symbols from order totals.
* FIX: Ensured product names are displayed correctly in Telegram messages.
* FIX: Addressed multiple security and data escaping issues reported by Plugin Check.

= 1.0.0 =
* Initial release of the Telegram Order Status Notification plugin.

== Upgrade Notice ==

= 1.0.2 =
Stable release fixing a critical timing issue in WooCommerce order status notifications and improving overall compliance.