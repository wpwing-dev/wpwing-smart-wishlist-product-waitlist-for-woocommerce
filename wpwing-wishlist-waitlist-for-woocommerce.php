<?php
/**
 * Plugin Name:          WPWing Wishlist Waitlist for WooCommerce
 * Plugin URI:           https://wpwing.com
 * Description:          Wishlist and back-in-stock waitlist for WooCommerce. Guests and logged-in users supported. Zero configuration.
 * Version:              1.0.0
 * Requires at least:    6.4
 * Tested up to:         7.0
 * Requires PHP:         8.0
 * Requires Plugins:     woocommerce
 * WC requires at least: 9.0
 * WC tested up to:      10.8.1
 * Author:               WPWing
 * Author URI:           https://profiles.wordpress.org/wpwing/
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          wpwing-wishlist-waitlist-for-woocommerce
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'ABSPATH' ) || exit;

define( 'WPWING_WL_VERSION', '1.0.0' );
define( 'WPWING_WL_MIN_WC', '9.0' );
define( 'WPWING_WL_FILE', __FILE__ );
define( 'WPWING_WL_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPWING_WL_URL', plugin_dir_url( __FILE__ ) );
define( 'WPWING_WL_SLUG', 'wpwing-wishlist-waitlist-for-woocommerce' );

require_once WPWING_WL_PATH . 'vendor/autoload.php';

add_action( 'plugins_loaded', 'wpwing_wl_boot' );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Boot the plugin after all plugins are loaded so WooCommerce is available.
 */
function wpwing_wl_boot(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wpwing_wl_woocommerce_missing_notice' );
		return;
	}
	if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, WPWING_WL_MIN_WC, '<' ) ) {
		add_action( 'admin_notices', 'wpwing_wl_woocommerce_version_notice' );
		return;
	}
	\WPWing\WishlistWaitlist\Core\Plugin::instance()->boot();
}

/**
 * Admin notice shown when WooCommerce is not active.
 */
function wpwing_wl_woocommerce_missing_notice(): void {
	?>
	<div class="notice notice-error"><p><?php esc_html_e( 'WPWing Wishlist and Waitlist for WooCommerce requires WooCommerce to be active.', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></p></div>
	<?php
}

/**
 * Admin notice shown when WooCommerce is below the minimum required version.
 */
function wpwing_wl_woocommerce_version_notice(): void {
	?>
	<div class="notice notice-error"><p>
	<?php
		printf(
			/* translators: %s: minimum required WooCommerce version number */
			esc_html__( 'WPWing Wishlist and Waitlist for WooCommerce requires WooCommerce %s or higher.', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			esc_html( WPWING_WL_MIN_WC )
		);
	?>
	</p></div>
	<?php
}

register_activation_hook( __FILE__, array( \WPWing\WishlistWaitlist\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \WPWing\WishlistWaitlist\Core\Activator::class, 'deactivate' ) );
