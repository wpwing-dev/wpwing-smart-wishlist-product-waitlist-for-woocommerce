<?php
/**
 * Frontend rendering for the waitlist form and unsubscribe handler.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Waitlist;

use WPWing\WishlistWaitlist\Core\Database;

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
		$hidden        = $product->is_type( 'variable' );
		$prefill_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';

		include WPWING_WL_PATH . 'templates/waitlist-form.php';
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
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM `{$table}` WHERE unsubscribe_token = %s AND status = 'active'",
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
