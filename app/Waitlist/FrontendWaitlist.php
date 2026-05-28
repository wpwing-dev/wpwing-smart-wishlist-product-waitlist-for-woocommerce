<?php
/**
 * Frontend rendering for the waitlist form and unsubscribe handler.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Waitlist;

use WPWing\WishlistWaitlist\Core\Database;
use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend rendering for the waitlist form and unsubscribe handler.
 */
class FrontendWaitlist {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'woocommerce_single_product_summary', array( $this, 'maybe_show_form' ), 35 );
		add_action( 'init', array( $this, 'handle_unsubscribe' ) );
	}

	/**
	 * Render the waitlist form on the single product page.
	 *
	 * For simple products: shown only when out of stock.
	 * For variable products: always output (hidden); JS controls visibility per variation.
	 */
	public function maybe_show_form(): void {
		if ( ! Settings::is_waitlist_enabled() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( ! in_array( $product->get_type(), array( 'simple', 'variable' ), true ) ) {
			return;
		}

		if ( $product->is_type( 'simple' ) && $product->is_in_stock() ) {
			return;
		}

		// For variable products the container is hidden until JS reveals it.
		$is_variable               = $product->is_type( 'variable' );
		$wpwing_wl_hidden          = $is_variable;
		$wpwing_wl_variation_aware = $is_variable;
		$wpwing_wl_prefill_email   = is_user_logged_in() ? wp_get_current_user()->user_email : '';

		// For simple OOS products, replace the form with a notice when the
		// current visitor already has an active entry. Logged-in users are
		// checked via user_id; guests are checked via a cookie that stores the
		// unsubscribe token — if the admin deleted the entry the DB lookup fails,
		// PHP clears the stale cookie, and the form reappears automatically.
		$wpwing_wl_already_on_waitlist = false;
		if ( ! $is_variable ) {
			if ( is_user_logged_in() ) {
				$wpwing_wl_already_on_waitlist = $this->is_on_waitlist( $product->get_id(), 0 );
			} else {
				$cookie_key = 'wpwing_wl_wj_' . $product->get_id() . '_0';
				if ( isset( $_COOKIE[ $cookie_key ] ) ) {
					$guest_token = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) );
					if ( $this->is_token_active( $guest_token ) ) {
						$wpwing_wl_already_on_waitlist = true;
					} else {
						// Stale cookie — admin deleted the entry; clear it so the form shows.
						wc_setcookie( $cookie_key, '', time() - HOUR_IN_SECONDS );
					}
				}
			}
		}

		include WPWING_WL_PATH . 'templates/waitlist-form.php';
	}

	/**
	 * Check whether the current logged-in user already has an active waitlist
	 * entry for the given product + variation combination.
	 *
	 * @param int $product_id   Parent product ID.
	 * @param int $variation_id Variation ID, or 0 for simple products.
	 */
	private function is_on_waitlist( int $product_id, int $variation_id ): bool {
		global $wpdb;
		$table = Database::waitlists();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM %i WHERE user_id = %d AND product_id = %d AND variation_id = %d AND status = 'active'",
				$table,
				get_current_user_id(),
				$product_id,
				$variation_id
			)
		);
	}

	/**
	 * Check whether a given unsubscribe token still corresponds to an active
	 * waitlist entry. Used to validate the guest join cookie.
	 *
	 * @param string $token The unsubscribe token stored in the guest cookie.
	 */
	private function is_token_active( string $token ): bool {
		global $wpdb;
		$table = Database::waitlists();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM %i WHERE unsubscribe_token = %s AND status = 'active'",
				$table,
				$token
			)
		);
	}

	/**
	 * Handle ?wpwing_action=unsubscribe&token=… requests.
	 * Marks the waitlist entry as unsubscribed and redirects to the home page.
	 */
	public function handle_unsubscribe(): void {
		// Nonce is intentionally absent: the 64-byte random token in the URL is the
		// authentication mechanism. Adding a nonce would break one-click email links
		// (the nonce would expire before many recipients open the email).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['wpwing_action'] ) ? sanitize_key( wp_unslash( $_GET['wpwing_action'] ) ) : '';

		if ( 'unsubscribe' !== $action ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( ! $token ) {
			return;
		}

		global $wpdb;
		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM %i WHERE unsubscribe_token = %s AND status = 'active'",
				$table,
				$token
			)
		);

		if ( $entry ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'status' => 'unsubscribed' ),
				array( 'id' => (int) $entry->id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
