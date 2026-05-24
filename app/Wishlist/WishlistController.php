<?php
/**
 * Wishlist business logic — AJAX toggle, guest cookie management.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Wishlist;

use WPWing\WishlistWaitlist\Core\Database;
use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist business logic — AJAX toggle, guest cookie management.
 */
class WishlistController {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_ajax_wpwing_wl_wishlist_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_nopriv_wpwing_wl_wishlist_toggle', array( $this, 'ajax_toggle' ) );
		add_action( 'wp_ajax_wpwing_wl_wishlist_check', array( $this, 'ajax_check' ) );
		add_action( 'wp_ajax_nopriv_wpwing_wl_wishlist_check', array( $this, 'ajax_check' ) );
	}

	/**
	 * AJAX handler — return the wishlist state for a product/variation without modifying it.
	 * Called when a variable product variation is selected so the button reflects the correct state.
	 */
	public function ajax_check(): void {
		check_ajax_referer( 'wpwing_wl_wishlist', 'nonce' );

		if ( ! Settings::is_wishlist_enabled() ) {
			wp_send_json_error();
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error();
		}

		global $wpdb;
		$table   = Database::wishlists();
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		if ( $user_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$in_wishlist = (bool) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM `{$table}` WHERE user_id = %d AND product_id = %d AND variation_id = %d",
					$user_id,
					$product_id,
					$variation_id
				)
			);
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';

			if ( ! $guest_token ) {
				wp_send_json_success(
					array(
						'in_wishlist' => false,
						'label'       => __( '♡ Add to wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
					)
				);
				return;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$in_wishlist = (bool) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM `{$table}` WHERE guest_token = %s AND product_id = %d AND variation_id = %d",
					$guest_token,
					$product_id,
					$variation_id
				)
			);
		}

		$label = $in_wishlist
			? __( '♥ Remove from wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' )
			: __( '♡ Add to wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' );

		wp_send_json_success(
			array(
				'in_wishlist' => $in_wishlist,
				'label'       => $label,
			)
		);
	}

	/**
	 * AJAX handler — toggle a product in the current user's or guest's wishlist.
	 */
	public function ajax_toggle(): void {
		check_ajax_referer( 'wpwing_wl_wishlist', 'nonce' );

		if ( ! Settings::is_wishlist_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Wishlist is currently disabled.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

		if ( ! $product_id || ! wc_get_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table       = Database::wishlists();
		$user_id     = is_user_logged_in() ? get_current_user_id() : null;
		$guest_token = null;
		$existing_id = 0;

		if ( $user_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM `{$table}` WHERE user_id = %d AND product_id = %d AND variation_id = %d",
					$user_id,
					$product_id,
					$variation_id
				)
			);
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';

			if ( ! $guest_token ) {
				$guest_token = bin2hex( random_bytes( 32 ) );
				wc_setcookie( 'wpwing_wl_guest', $guest_token, time() + YEAR_IN_SECONDS );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM `{$table}` WHERE guest_token = %s AND product_id = %d AND variation_id = %d",
					$guest_token,
					$product_id,
					$variation_id
				)
			);
		}

		if ( $existing_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'id' => $existing_id ), array( '%d' ) );
			do_action( 'wpwing_wl_wishlist_item_removed', $user_id, $product_id, $variation_id );
			$action = 'removed';
			$label  = __( '♡ Add to wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
		} else {
			$data   = array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'created_at'   => current_time( 'mysql' ),
			);
			$format = array( '%d', '%d', '%s' );

			if ( $user_id ) {
				$data['user_id'] = $user_id;
				$format[]        = '%d';
			} else {
				$data['guest_token'] = $guest_token;
				$format[]            = '%s';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $wpdb->insert( $table, $data, $format ) ) {
				wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ) );
			}
			do_action( 'wpwing_wl_wishlist_item_added', $user_id, $product_id, $variation_id );
			$action = 'added';
			$label  = __( '♥ Remove from wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
		}

		// Return updated count so the UI can reflect the new total.
		if ( $user_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT(*) FROM `{$table}` WHERE user_id = %d",
					$user_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT(*) FROM `{$table}` WHERE guest_token = %s",
					$guest_token
				)
			);
		}

		wp_send_json_success(
			array(
				'action' => $action,
				'label'  => $label,
				'count'  => $count,
			)
		);
	}
}
