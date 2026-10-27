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
		add_action( 'wp_ajax_wpwing_wl_wishlist_update_quantity', array( $this, 'ajax_update_quantity' ) );
		add_action( 'wp_ajax_nopriv_wpwing_wl_wishlist_update_quantity', array( $this, 'ajax_update_quantity' ) );
		add_action( 'wp_ajax_wpwing_wl_wishlist_bulk_add_to_cart', array( $this, 'ajax_bulk_add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_wpwing_wl_wishlist_bulk_add_to_cart', array( $this, 'ajax_bulk_add_to_cart' ) );
		add_action( 'wp_ajax_wpwing_wl_wishlist_clear', array( $this, 'ajax_clear' ) );
		add_action( 'wp_ajax_nopriv_wpwing_wl_wishlist_clear', array( $this, 'ajax_clear' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'remove_on_add_to_cart' ), 10, 4 );
	}

	/**
	 * Remove a product from the current user's or guest's wishlist once it's added to the cart.
	 *
	 * @param string $cart_item_key Unused, part of the woocommerce_add_to_cart signature.
	 * @param int    $product_id    Product ID added to the cart.
	 * @param int    $quantity      Unused, part of the woocommerce_add_to_cart signature.
	 * @param int    $variation_id  Variation ID added to the cart, or 0.
	 */
	public function remove_on_add_to_cart( $cart_item_key, int $product_id, $quantity, int $variation_id ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! Settings::is_wishlist_enabled() ) {
			return;
		}

		global $wpdb;
		$table   = Database::wishlists();
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		if ( $user_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->delete(
				$table,
				array(
					'user_id'      => $user_id,
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
				),
				array( '%d', '%d', '%d' )
			);
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';

			if ( ! preg_match( '/^[a-f0-9]{64}$/', $guest_token ) ) {
				return;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->delete(
				$table,
				array(
					'guest_token'  => $guest_token,
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
				),
				array( '%s', '%d', '%d' )
			);
		}

		if ( $deleted ) {
			do_action( 'wpwing_wl_wishlist_item_removed', $user_id, $product_id, $variation_id );
		}
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
					'SELECT id FROM %i WHERE user_id = %d AND product_id = %d AND variation_id = %d',
					$table,
					$user_id,
					$product_id,
					$variation_id
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE user_id = %d',
					$table,
					$user_id
				)
			);
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';

			if ( ! preg_match( '/^[a-f0-9]{64}$/', $guest_token ) ) {
				wp_send_json_success(
					array(
						'in_wishlist' => false,
						'label'       => Settings::wishlist_btn_add(),
						'count'       => 0,
					)
				);
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$in_wishlist = (bool) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM %i WHERE guest_token = %s AND product_id = %d AND variation_id = %d',
					$table,
					$guest_token,
					$product_id,
					$variation_id
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE guest_token = %s',
					$table,
					$guest_token
				)
			);
		}

		$label       = $in_wishlist
			? Settings::wishlist_btn_remove()
			: Settings::wishlist_btn_add();
		$shape       = Settings::wishlist_icon_shape();
		$icon_symbol = Settings::wishlist_icon_symbol( $shape, $in_wishlist );

		wp_send_json_success(
			array(
				'in_wishlist' => $in_wishlist,
				'label'       => $label,
				'icon'        => $icon_symbol,
				'count'       => $count,
			)
		);
	}

	/**
	 * AJAX handler — toggle a product in the current user's or guest's wishlist.
	 */
	public function ajax_toggle(): void {
		check_ajax_referer( 'wpwing_wl_wishlist', 'nonce' );

		if ( ! Settings::is_wishlist_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Wishlist is currently disabled.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

		if ( ! $product_id || ! wc_get_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		if ( $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation || ! $variation->is_type( 'variation' ) || (int) $variation->get_parent_id() !== $product_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid product variation.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
			}
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
					'SELECT id FROM %i WHERE user_id = %d AND product_id = %d AND variation_id = %d',
					$table,
					$user_id,
					$product_id,
					$variation_id
				)
			);
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';

			if ( ! preg_match( '/^[a-f0-9]{64}$/', $guest_token ) ) {
				$guest_token                = bin2hex( random_bytes( 32 ) );
				$_COOKIE['wpwing_wl_guest'] = $guest_token;
				wc_setcookie( 'wpwing_wl_guest', $guest_token, time() + YEAR_IN_SECONDS, is_ssl(), true );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM %i WHERE guest_token = %s AND product_id = %d AND variation_id = %d',
					$table,
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
			$label  = Settings::wishlist_btn_add();
		} else {
			$data   = array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'quantity'     => 1,
				'created_at'   => current_time( 'mysql' ),
			);
			$format = array( '%d', '%d', '%d', '%s' );

			if ( $user_id ) {
				$data['user_id'] = $user_id;
				$format[]        = '%d';
			} else {
				$data['guest_token'] = $guest_token;
				$format[]            = '%s';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $wpdb->insert( $table, $data, $format ) ) {
				wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
			}
			do_action( 'wpwing_wl_wishlist_item_added', $user_id, $product_id, $variation_id );
			$action = 'added';
			$label  = Settings::wishlist_btn_remove();
		}

		// Return updated count so the UI can reflect the new total.
		if ( $user_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE user_id = %d',
					$table,
					$user_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE guest_token = %s',
					$table,
					$guest_token
				)
			);
		}

		$shape       = Settings::wishlist_icon_shape();
		$icon_symbol = Settings::wishlist_icon_symbol( $shape, 'added' === $action );

		wp_send_json_success(
			array(
				'action' => $action,
				'label'  => $label,
				'icon'   => $icon_symbol,
				'count'  => $count,
			)
		);
	}

	/**
	 * AJAX handler — update quantity for a wishlist item.
	 */
	public function ajax_update_quantity(): void {
		check_ajax_referer( 'wpwing_wl_wishlist', 'nonce' );

		if ( ! Settings::is_wishlist_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Wishlist is currently disabled.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table   = Database::wishlists();
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		if ( $user_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'quantity' => $quantity ),
				array(
					'user_id'      => $user_id,
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
				),
				array( '%d' ),
				array( '%d', '%d', '%d' )
			);
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';

			if ( ! preg_match( '/^[a-f0-9]{64}$/', $guest_token ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid session.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'quantity' => $quantity ),
				array(
					'guest_token'  => $guest_token,
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
				),
				array( '%d' ),
				array( '%s', '%d', '%d' )
			);
		}

		do_action( 'wpwing_wl_wishlist_quantity_updated', $user_id, $product_id, $variation_id, $quantity );

		wp_send_json_success(
			array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'quantity'     => $quantity,
			)
		);
	}

	/**
	 * AJAX handler — clear all wishlist items for the current user or guest.
	 */
	public function ajax_clear(): void {
		check_ajax_referer( 'wpwing_wl_wishlist', 'nonce' );

		if ( ! Settings::is_wishlist_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Wishlist is currently disabled.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table       = Database::wishlists();
		$user_id     = is_user_logged_in() ? get_current_user_id() : null;
		$guest_token = '';

		if ( $user_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'user_id' => $user_id ), array( '%d' ) );
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';

			if ( preg_match( '/^[a-f0-9]{64}$/', $guest_token ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $table, array( 'guest_token' => $guest_token ), array( '%s' ) );
			}
		}

		do_action( 'wpwing_wl_wishlist_cleared', $user_id, $guest_token );

		wp_send_json_success(
			array(
				'count'   => 0,
				'message' => __( 'Your wishlist has been cleared.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			)
		);
	}

	/**
	 * AJAX handler — add all or selected wishlist items to WooCommerce cart.
	 */
	public function ajax_bulk_add_to_cart(): void {
		check_ajax_referer( 'wpwing_wl_wishlist', 'nonce' );

		if ( ! Settings::is_wishlist_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Wishlist is currently disabled.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		if ( function_exists( 'WC' ) && null === WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart is not available.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table        = Database::wishlists();
		$user_id      = is_user_logged_in() ? get_current_user_id() : null;
		$items_to_add = array();

		// If specific items were posted, use those.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! empty( $_POST['items'] ) && is_array( $_POST['items'] ) ) {
			$raw_items = (array) wp_unslash( $_POST['items'] );
			foreach ( $raw_items as $item ) {
				if ( is_array( $item ) ) {
					$items_to_add[] = array(
						'product_id'   => isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0,
						'variation_id' => isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0,
						'quantity'     => isset( $item['quantity'] ) ? max( 1, absint( $item['quantity'] ) ) : 1,
					);
				}
			}
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// If no items were posted, fetch all rows for user/guest.
		if ( empty( $items_to_add ) ) {
			if ( $user_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT product_id, variation_id, quantity FROM %i WHERE user_id = %d',
						$table,
						$user_id
					)
				);
			} else {
				$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
					? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
					: '';

				if ( ! preg_match( '/^[a-f0-9]{64}$/', $guest_token ) ) {
					wp_send_json_error( array( 'message' => __( 'Wishlist is empty.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT product_id, variation_id, quantity FROM %i WHERE guest_token = %s',
						$table,
						$guest_token
					)
				);
			}

			foreach ( (array) $rows as $row ) {
				$items_to_add[] = array(
					'product_id'   => (int) $row->product_id,
					'variation_id' => (int) $row->variation_id,
					'quantity'     => isset( $row->quantity ) ? max( 1, (int) $row->quantity ) : 1,
				);
			}
		}

		if ( empty( $items_to_add ) ) {
			wp_send_json_error( array( 'message' => __( 'Wishlist is empty.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		$added_count = 0;
		foreach ( $items_to_add as $item ) {
			$product_id   = $item['product_id'];
			$variation_id = $item['variation_id'];
			$quantity     = $item['quantity'];
			$lookup_id    = $variation_id ? $variation_id : $product_id;
			$product      = wc_get_product( $lookup_id );

			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}

			// Passing product_id, quantity, and variation_id.
			$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
			if ( $cart_item_key ) {
				++$added_count;
			}
		}

		if ( 0 === $added_count ) {
			wp_send_json_error( array( 'message' => __( 'No items could be added to the cart (items may be out of stock).', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ) );
		}

		// Calculate remaining wishlist count.
		if ( $user_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$remaining = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE user_id = %d',
					$table,
					$user_id
				)
			);
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$remaining = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE guest_token = %s',
					$table,
					$guest_token
				)
			);
		}

		wp_send_json_success(
			array(
				'added'    => $added_count,
				'count'    => $remaining,
				'cart_url' => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
				'message'  => sprintf(
					/* translators: %d: number of items added to cart */
					_n( '%d item added to cart.', '%d items added to cart.', $added_count, 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
					$added_count
				),
			)
		);
	}
}
