<?php
/**
 * Scheduled cleanup jobs.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles WP-Cron jobs for the plugin.
 */
class Cron {

	const CLEANUP_HOOK = 'wpwing_wl_cleanup_guest_wishlists';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( self::CLEANUP_HOOK, array( $this, 'cleanup_guest_wishlists' ) );
	}

	/**
	 * Delete guest wishlist rows older than 30 days.
	 * Runs weekly via WP-Cron. Orphaned guest tokens accumulate when guests
	 * add items but never return to clear the cookie.
	 */
	public function cleanup_guest_wishlists(): void {
		global $wpdb;

		$table     = Database::wishlists();
		$threshold = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM `{$table}` WHERE guest_token IS NOT NULL AND created_at < %s",
				$threshold
			)
		);
	}
}
