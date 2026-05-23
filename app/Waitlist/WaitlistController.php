<?php
/**
 * Waitlist business logic — signup, restock detection, and notification dispatch.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Waitlist;

use WPWing\WishlistWaitlist\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Waitlist business logic — signup, restock detection, and notification dispatch.
 */
class WaitlistController {

	/**
	 * Hook into WordPress and WooCommerce.
	 */
	public function register(): void {
		add_action( 'woocommerce_product_set_stock', array( $this, 'on_stock_changed' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'on_stock_changed' ) );
		add_action( 'wpwing_wl_process_restock_queue', array( $this, 'process_restock_queue' ), 10, 2 );
		add_action( 'wp_ajax_wpwing_wl_join_waitlist', array( $this, 'ajax_join_waitlist' ) );
		add_action( 'wp_ajax_nopriv_wpwing_wl_join_waitlist', array( $this, 'ajax_join_waitlist' ) );
	}

	/**
	 * When a product's stock quantity changes, schedule a restock job if stock
	 * has just become available.
	 *
	 * @param \WC_Product $product The product whose stock changed.
	 */
	public function on_stock_changed( \WC_Product $product ): void {
		do_action( 'wpwing_wl_product_stock_changed', $product );

		if ( ! $product->is_in_stock() || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		$is_variation = $product->is_type( 'variation' );
		$product_id   = $is_variation ? $product->get_parent_id() : $product->get_id();
		$variation_id = $is_variation ? $product->get_id() : 0;
		$args         = array( $product_id, $variation_id );

		if ( as_has_scheduled_action( 'wpwing_wl_process_restock_queue', $args, 'wpwing-wl-queue' ) ) {
			return;
		}

		as_schedule_single_action( time(), 'wpwing_wl_process_restock_queue', $args, 'wpwing-wl-queue' );
	}

	/**
	 * Action Scheduler callback: send restock emails for all active waitlist entries.
	 *
	 * @param int $product_id   Parent product ID.
	 * @param int $variation_id Variation ID, or 0 for simple products.
	 */
	public function process_restock_queue( int $product_id, int $variation_id = 0 ): void {
		global $wpdb;

		$table = Database::waitlists();

		do_action( 'wpwing_wl_before_process_restock_queue', $product_id, $variation_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM `{$table}` WHERE product_id = %d AND variation_id = %d AND status = 'active'",
				$product_id,
				$variation_id
			)
		);

		$entries = (array) apply_filters( 'wpwing_wl_restock_queue_entries', $entries, $product_id, $variation_id );

		foreach ( $entries as $entry ) {
			$this->send_restock_email( $entry );
		}

		do_action( 'wpwing_wl_after_process_restock_queue', $product_id, $variation_id, $entries );
	}

	/**
	 * Send one restock notification email and mark the entry as notified.
	 *
	 * @param object $entry Waitlist DB row.
	 */
	private function send_restock_email( object $entry ): void {
		global $wpdb;

		$lookup_id = (int) $entry->variation_id ? (int) $entry->variation_id : (int) $entry->product_id;
		$product   = wc_get_product( $lookup_id );
		if ( ! $product ) {
			return;
		}

		$unsubscribe_url = add_query_arg(
			array(
				'wpwing_action' => 'unsubscribe',
				'token'         => $entry->unsubscribe_token,
			),
			home_url( '/' )
		);

		$subject = sprintf(
			/* translators: 1: site name 2: product name */
			__( '[%1$s] "%2$s" is back in stock', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			get_bloginfo( 'name' ),
			$product->get_name()
		);

		$default_message = sprintf(
			/* translators: 1: product name 2: product URL 3: unsubscribe URL */
			__( "Good news! \"%1\$s\" is back in stock.\n\nShop now: %2\$s\n\nNo longer interested? Unsubscribe: %3\$s", 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			$product->get_name(),
			$product->get_permalink(),
			$unsubscribe_url
		);

		$message = (string) apply_filters( 'wpwing_wl_restock_email_message', $default_message, $entry, $product );

		do_action( 'wpwing_wl_before_send_restock_email', $entry, $product );

		wp_mail( $entry->email, $subject, $message );

		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'status'      => 'notified',
				'notified_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $entry->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		do_action( 'wpwing_wl_after_send_restock_email', $entry, $product );
	}

	/**
	 * AJAX handler — add the current user/guest to the product waitlist.
	 */
	public function ajax_join_waitlist(): void {
		check_ajax_referer( 'wpwing_wl_waitlist', 'nonce' );

		// Honeypot: bots fill hidden fields, humans don't.
		if ( ! empty( $_POST['wpwing_hp'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Spam detected.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
		}

		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( (string) $_POST['email'] ) ) : '';
		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
		}

		if ( ! $product_id || ! wc_get_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM `{$table}` WHERE email = %s AND product_id = %d AND variation_id = %d AND status = 'active'",
				$email,
				$product_id,
				$variation_id
			)
		);

		if ( $existing ) {
			wp_send_json_error( array( 'message' => __( "You're already on the waitlist for this product.", 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
		}

		$token   = bin2hex( random_bytes( 32 ) );
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		$data   = array(
			'product_id'        => $product_id,
			'variation_id'      => $variation_id,
			'email'             => $email,
			'status'            => 'active',
			'unsubscribe_token' => $token,
			'created_at'        => current_time( 'mysql' ),
		);
		$format = array( '%d', '%d', '%s', '%s', '%s', '%s' );

		if ( $user_id ) {
			$data['user_id'] = $user_id;
			$format[]        = '%d';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert( $table, $data, $format );

		if ( ! $inserted ) {
			wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
		}

		wp_send_json_success( array( 'message' => __( "You're on the waitlist! We'll email you when this product is back in stock.", 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
	}
}
