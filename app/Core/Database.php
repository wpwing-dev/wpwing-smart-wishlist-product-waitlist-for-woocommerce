<?php
/**
 * Table name helpers.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Provides table name helpers for all plugin custom tables.
 */
class Database {

	/**
	 * Returns the fully prefixed waitlist table name.
	 */
	public static function waitlists(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpwing_wl_waitlists';
	}

	/**
	 * Returns the fully prefixed wishlist table name.
	 */
	public static function wishlists(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpwing_wl_wishlists';
	}
}
