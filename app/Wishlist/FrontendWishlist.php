<?php
/**
 * Frontend wishlist toggle button and [wpwing_wishlist] shortcode.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Wishlist;

use WPWing\WishlistWaitlist\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend wishlist toggle button and [wpwing_wishlist] shortcode.
 */
class FrontendWishlist {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_button' ), 32 );
		add_shortcode( 'wpwing_wishlist', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the wishlist toggle button on single product pages.
	 */
	public function render_button(): void {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$product_id   = $product->get_id();
		$variation_id = 0;
		$in_wishlist  = $this->is_in_wishlist( $product_id, $variation_id );
		$label        = $in_wishlist
			? __( '♥ Remove from wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' )
			: __( '♡ Add to wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
		?>
		<button
			type="button"
			class="wpwing-wishlist-toggle"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
			data-in-wishlist="<?php echo $in_wishlist ? '1' : '0'; ?>"
		>
			<?php echo esc_html( $label ); ?>
		</button>
		<?php
	}

	/**
	 * Render the [wpwing_wishlist] shortcode — returns the wishlist table HTML.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string
	 */
	public function render_shortcode( array $atts ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$wishlist_items = $this->get_wishlist_items();

		ob_start();
		include WPWING_WL_PATH . 'templates/wishlist-view.php';
		return (string) ob_get_clean();
	}

	/**
	 * Check whether a product is already in the current user's or guest's wishlist.
	 *
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID, or 0.
	 */
	private function is_in_wishlist( int $product_id, int $variation_id = 0 ): bool {
		global $wpdb;
		$table = Database::wishlists();

		if ( \is_user_logged_in() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (bool) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM `{$table}` WHERE user_id = %d AND product_id = %d AND variation_id = %d",
					\get_current_user_id(),
					$product_id,
					$variation_id
				)
			);
		}

		$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
			? sanitize_text_field( \wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
			: '';

		if ( ! $guest_token ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM `{$table}` WHERE guest_token = %s AND product_id = %d AND variation_id = %d",
				$guest_token,
				$product_id,
				$variation_id
			)
		);
	}

	/**
	 * Fetch all wishlist rows for the current user or guest, with product objects attached.
	 *
	 * @return array<int, array{row: object, product: \WC_Product}>
	 */
	private function get_wishlist_items(): array {
		global $wpdb;
		$table = Database::wishlists();

		if ( \is_user_logged_in() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM `{$table}` WHERE user_id = %d ORDER BY created_at DESC",
					\get_current_user_id()
				)
			);
		} else {
			$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
				? sanitize_text_field( \wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
				: '';

			if ( ! $guest_token ) {
				return array();
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM `{$table}` WHERE guest_token = %s ORDER BY created_at DESC",
					$guest_token
				)
			);
		}

		$items = array();
		foreach ( (array) $rows as $row ) {
			$lookup_id = (int) $row->variation_id ? (int) $row->variation_id : (int) $row->product_id;
			$product   = wc_get_product( $lookup_id );
			if ( $product ) {
				$items[] = array(
					'row'     => $row,
					'product' => $product,
				);
			}
		}

		return $items;
	}
}
