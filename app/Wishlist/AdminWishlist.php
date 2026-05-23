<?php
/**
 * Wishlist admin page — top-50 most-wishlisted products.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Wishlist;

use WPWing\WishlistWaitlist\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist admin page — top-50 most-wishlisted products.
 */
class AdminWishlist {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
	}

	/**
	 * Add the Wishlist submenu under the shared WPWing parent menu.
	 */
	public function register_submenu(): void {
		add_submenu_page(
			'wpwing',
			__( 'Wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			__( 'Wishlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			'wpwing-wl-wishlist',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the Wishlist admin page showing the top-50 most-wishlisted products.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) );
		}

		global $wpdb;
		$table = Database::wishlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT product_id, COUNT(*) AS wishlist_count FROM `{$table}` GROUP BY product_id ORDER BY wishlist_count DESC LIMIT 50" );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Most Wishlisted Products', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></h1>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Wishlist Count', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $rows ) : ?>
						<?php foreach ( $rows as $row ) : ?>
							<?php
							$product      = wc_get_product( (int) $row->product_id );
							$product_name = $product
								? $product->get_name()
								: sprintf(
									/* translators: %d: product ID */
									__( 'Product #%d', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
									(int) $row->product_id
								);
							?>
							<tr>
								<td>
									<?php if ( $product ) : ?>
										<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $row->product_id ) ); ?>">
											<?php echo esc_html( $product_name ); ?>
										</a>
									<?php else : ?>
										<?php echo esc_html( $product_name ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo (int) $row->wishlist_count; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="2"><?php esc_html_e( 'No wishlist data yet.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
