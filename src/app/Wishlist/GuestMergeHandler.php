<?php
/**
 * Merges guest wishlist items into the user's account on login.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Wishlist;

use WPWing\WishlistWaitlist\Core\Database;
use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Merges guest wishlist items into the user's account on login.
 */
class GuestMergeHandler {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_login', array( $this, 'merge_guest_items' ), 10, 2 );
		// wc_set_customer_auth_cookie() (used on registration) does not fire
		// wp_login, so registration needs its own hook into the same merge logic.
		add_action( 'woocommerce_created_customer', array( $this, 'merge_guest_items_for_new_customer' ) );
	}

	/**
	 * On registration, resolve the new customer and merge guest items the same
	 * way merge_guest_items() does on login.
	 *
	 * @param int $customer_id Newly created customer's user ID.
	 */
	public function merge_guest_items_for_new_customer( int $customer_id ): void {
		$user = get_user_by( 'id', $customer_id );
		if ( $user ) {
			$this->merge_guest_items( $user->user_login, $user );
		}
	}

	/**
	 * On login, copy all guest wishlist rows to the user account via INSERT IGNORE
	 * (skipping duplicates), then delete the guest rows and clear the cookie.
	 *
	 * @param string   $user_login The user's login name (unused).
	 * @param \WP_User $user       The logged-in user object.
	 */
	public function merge_guest_items( string $user_login, \WP_User $user ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		if ( ! Settings::is_wishlist_enabled() ) {
			return;
		}

		$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
			? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
			: '';

		if ( ! preg_match( '/^[a-f0-9]{64}$/', $guest_token ) ) {
			return;
		}

		global $wpdb;
		$table = Database::wishlists();

		// INSERT IGNORE skips rows that would violate the unique_user_item key,
		// so existing wishlist items are preserved if there's a conflict.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO %i (user_id, product_id, variation_id, quantity, created_at) SELECT %d, product_id, variation_id, quantity, created_at FROM %i WHERE guest_token = %s', $table, $user->ID, $table, $guest_token ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'guest_token' => $guest_token ), array( '%s' ) );

		wc_setcookie( 'wpwing_wl_guest', '', time() - HOUR_IN_SECONDS, is_ssl(), true );
	}
}
