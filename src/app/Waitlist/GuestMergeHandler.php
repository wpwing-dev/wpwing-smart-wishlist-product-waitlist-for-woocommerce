<?php
/**
 * Merges guest waitlist entries into the user's account on login.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Waitlist;

use WPWing\WishlistWaitlist\Core\Database;
use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Merges guest waitlist entries into the user's account on login.
 */
class GuestMergeHandler {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_login', array( $this, 'merge_guest_entries' ), 10, 2 );
	}

	/**
	 * On login, claim any active guest waitlist entries that belong to this
	 * browser by updating their user_id. If the user already has an active entry
	 * for the same product+variation, the guest duplicate is unsubscribed instead.
	 * Cookies are cleared after merging so the entries behave as logged-in rows.
	 *
	 * @param string   $user_login The user's login name (unused).
	 * @param \WP_User $user       The logged-in user object.
	 */
	public function merge_guest_entries( string $user_login, \WP_User $user ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		if ( ! Settings::is_waitlist_enabled() ) {
			return;
		}

		$tokens = array();
		foreach ( $_COOKIE as $key => $value ) {
			if ( str_starts_with( $key, 'wpwing_wl_wj_' ) ) {
				$tokens[ $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
			}
		}

		if ( ! $tokens ) {
			return;
		}

		global $wpdb;
		$table = Database::waitlists();

		foreach ( $tokens as $cookie_key => $token ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$entry = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, product_id, variation_id FROM %i WHERE unsubscribe_token = %s AND status = 'active'",
					$table,
					$token
				)
			);

			if ( ! $entry ) {
				wc_setcookie( $cookie_key, '', time() - HOUR_IN_SECONDS );
				continue;
			}

			// Check whether the user already has an active entry for this product+variation.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM %i WHERE user_id = %d AND product_id = %d AND variation_id = %d AND status = 'active'",
					$table,
					$user->ID,
					(int) $entry->product_id,
					(int) $entry->variation_id
				)
			);

			if ( $existing ) {
				// User is already on the waitlist — unsubscribe the orphaned guest row.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table,
					array( 'status' => 'unsubscribed' ),
					array( 'id' => (int) $entry->id ),
					array( '%s' ),
					array( '%d' )
				);
			} else {
				// Claim the guest row by setting user_id.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table,
					array( 'user_id' => $user->ID ),
					array( 'id' => (int) $entry->id ),
					array( '%d' ),
					array( '%d' )
				);
			}

			wc_setcookie( $cookie_key, '', time() - HOUR_IN_SECONDS );
		}
	}
}
