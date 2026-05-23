<?php
/**
 * Registers the shared WPWing top-level admin menu.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the shared WPWing top-level admin menu.
 *
 * The top-level `wpwing` menu slug is intentionally shared across all WPWing
 * plugins. The guard below ensures only the first plugin to load registers it;
 * every subsequent WPWing plugin skips `add_menu_page` and just attaches its
 * own submenu to the existing parent.
 */
class AdminMenu {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Register the top-level WPWing menu if no other WPWing plugin has done so.
	 */
	public function register_menu(): void {
		if ( '' !== menu_page_url( 'wpwing', false ) ) {
			return;
		}

		add_menu_page(
			__( 'WPWing', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			__( 'WPWing', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			'wpwing',
			'__return_null',
			'dashicons-heart',
			58
		);
	}
}
