<?php
/**
 * Frontend wishlist toggle button and [wpwing_wishlist] shortcode.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Wishlist;

use WPWing\WishlistWaitlist\Core\Database;
use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend wishlist toggle button and [wpwing_wishlist] shortcode.
 */
class FrontendWishlist {

	/**
	 * Per-request cache so multiple nav menus on one page don't re-query.
	 *
	 * @var int|null
	 */
	private ?int $count_cache = null;

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_button' ), 32 );
		add_shortcode( 'wpwing_wishlist', array( $this, 'render_shortcode' ) );
		add_shortcode( 'wpwing_wishlist_count', array( $this, 'render_count_shortcode' ) );
		add_filter( 'wp_nav_menu_items', array( $this, 'inject_count_in_nav' ), 10, 2 );
	}

	/**
	 * Render the wishlist toggle button on single product pages.
	 */
	public function render_button(): void {
		if ( ! Settings::is_wishlist_enabled() ) {
			return;
		}

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
		if ( ! Settings::is_wishlist_enabled() ) {
			return '';
		}

		$wishlist_items = $this->get_wishlist_items();

		ob_start();
		include WPWING_WL_PATH . 'templates/wishlist-view.php';
		return (string) ob_get_clean();
	}

	/**
	 * [wpwing_wishlist_count] shortcode — renders the count badge with a link to
	 * the wishlist page. Merchants can place this anywhere: widget, FSE block, etc.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 */
	public function render_count_shortcode( array $atts ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! Settings::is_wishlist_enabled() ) {
			return '';
		}

		return $this->get_count_badge_html();
	}

	/**
	 * Append a wishlist count badge to every nav menu rendered by wp_nav_menu().
	 * Follows the same pattern WooCommerce uses for the mini-cart nav item.
	 * Theme developers can opt out by returning false from the
	 * `wpwing_wl_show_count_in_nav` filter.
	 *
	 * @param string    $items The HTML list content for the menu.
	 * @param \stdClass $args  An object of wp_nav_menu() arguments.
	 */
	public function inject_count_in_nav( string $items, \stdClass $args ): string {
		if ( ! Settings::is_wishlist_enabled() ) {
			return $items;
		}

		if ( ! apply_filters( 'wpwing_wl_show_count_in_nav', true, $args ) ) {
			return $items;
		}

		$badge  = $this->get_count_badge_html();
		$items .= '<li class="menu-item wpwing-wishlist-nav-item">' . $badge . '</li>';

		return $items;
	}

	/**
	 * Build the shared count badge anchor element used by both the shortcode
	 * and the nav injection.
	 */
	private function get_count_badge_html(): string {
		$wishlist_page_id = (int) Settings::get( 'wishlist_page_id', 0 );
		$wishlist_url     = $wishlist_page_id ? (string) get_permalink( $wishlist_page_id ) : home_url( '/' );
		$count            = $this->get_current_wishlist_count();

		return sprintf(
			'<a href="%s" class="wpwing-wishlist-count-link" aria-label="%s">&#9825; <span class="wpwing-wishlist-count">%d</span></a>',
			esc_url( $wishlist_url ),
			esc_attr(
				sprintf(
					/* translators: %d: number of saved wishlist items */
					_n( 'Wishlist (%d item)', 'Wishlist (%d items)', $count, 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
					$count
				)
			),
			$count
		);
	}

	/**
	 * Return the current user's (or guest's) wishlist item count.
	 * Result is cached for the duration of the request.
	 */
	private function get_current_wishlist_count(): int {
		if ( null !== $this->count_cache ) {
			return $this->count_cache;
		}

		global $wpdb;
		$table = Database::wishlists();

		if ( \is_user_logged_in() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->count_cache = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT(*) FROM `{$table}` WHERE user_id = %d",
					\get_current_user_id()
				)
			);
			return $this->count_cache;
		}

		$guest_token = isset( $_COOKIE['wpwing_wl_guest'] )
			? sanitize_text_field( \wp_unslash( $_COOKIE['wpwing_wl_guest'] ) )
			: '';

		if ( ! $guest_token ) {
			$this->count_cache = 0;
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->count_cache = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM `{$table}` WHERE guest_token = %s",
				$guest_token
			)
		);

		return $this->count_cache;
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
