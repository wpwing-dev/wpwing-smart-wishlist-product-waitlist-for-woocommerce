<?php
/**
 * Cascade-deletes wishlist and waitlist rows when their referenced WooCommerce
 * product or variation is permanently deleted.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Cleans up orphaned plugin rows when a product is permanently deleted.
 *
 * The product is matched against both `product_id` and `variation_id` so that
 * deleting either a parent product or a single variation removes the matching
 * subscriptions and wishlist entries.
 */
class ProductDeleteHandler {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		// before_delete_post fires for permanent deletion only (not trash).
		add_action( 'before_delete_post', array( $this, 'maybe_cascade' ), 10, 2 );
	}

	/**
	 * If the post being deleted is a product or variation, remove all matching
	 * wishlist and waitlist rows.
	 *
	 * @param int      $post_id Post ID being deleted.
	 * @param \WP_Post $post    Post object.
	 */
	public function maybe_cascade( int $post_id, \WP_Post $post ): void {
		if ( ! in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		global $wpdb;

		$wishlists = Database::wishlists();
		$waitlists = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM `{$wishlists}` WHERE product_id = %d OR variation_id = %d",
				$post_id,
				$post_id
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM `{$waitlists}` WHERE product_id = %d OR variation_id = %d",
				$post_id,
				$post_id
			)
		);
	}
}
