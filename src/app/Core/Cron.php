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
	 * Delete guest wishlist rows older than the configured retention window.
	 * Runs weekly via WP-Cron. Orphaned guest tokens accumulate when guests
	 * add items but never return to clear the cookie.
	 */
	public function cleanup_guest_wishlists(): void {
		global $wpdb;

		$table = Database::wishlists();
		$days  = Settings::guest_retention_days();

		// Compute the threshold in the site's local timezone so it lines up with
		// created_at, which is stored via current_time( 'mysql' ) (also local).
		// Using gmdate() here would skew the cutoff by the site's UTC offset.
		$threshold = date( 'Y-m-d H:i:s', \current_time( 'timestamp' ) - ( $days * DAY_IN_SECONDS ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date, WordPress.DateTime.CurrentTimeTimestamp.Requested

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE guest_token IS NOT NULL AND created_at < %s',
				$table,
				$threshold
			)
		);
	}
}
