<?php
/**
 * Wishlist shortcode view — renders the customer's saved products.
 *
 * Variables available from FrontendWishlist::render_shortcode():
 *   array $wpwing_wl_wishlist_items  Each element: ['row' => object, 'product' => WC_Product].
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'ABSPATH' ) || exit;

$wpwing_wl_wishlist_items = isset( $wpwing_wl_wishlist_items ) ? (array) $wpwing_wl_wishlist_items : array();
?>

<?php if ( ! $wpwing_wl_wishlist_items ) : ?>
	<p class="wpwing-wishlist-empty">
		<?php esc_html_e( 'Your wishlist is empty.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
	</p>
<?php else : ?>
	<table class="wpwing-wishlist-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Product', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Price', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $wpwing_wl_wishlist_items as $wpwing_wl_item ) : ?>
				<?php
				$wpwing_wl_row     = $wpwing_wl_item['row'];
				$wpwing_wl_product = $wpwing_wl_item['product'];
				?>
				<tr class="wpwing-wishlist-row" data-row-id="<?php echo esc_attr( $wpwing_wl_row->id ); ?>">
					<td>
						<a href="<?php echo esc_url( $wpwing_wl_product->get_permalink() ); ?>">
							<?php echo wp_kses_post( $wpwing_wl_product->get_image( 'thumbnail' ) ); ?>
							<?php echo esc_html( $wpwing_wl_product->get_name() ); ?>
						</a>
					</td>
					<td><?php echo wp_kses_post( wc_price( (float) $wpwing_wl_product->get_price() ) ); ?></td>
					<td class="wpwing-wishlist-actions">
						<a
							href="<?php echo esc_url( $wpwing_wl_product->add_to_cart_url() ); ?>"
							class="button wpwing-wishlist-add-to-cart"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Add "%s" to cart', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ), $wpwing_wl_product->get_name() ) ); ?>"
						>
							<?php echo esc_html( $wpwing_wl_product->add_to_cart_text() ); ?>
						</a>
						<button
							type="button"
							class="wpwing-wishlist-toggle button"
							data-product-id="<?php echo esc_attr( $wpwing_wl_row->product_id ); ?>"
							data-variation-id="<?php echo esc_attr( $wpwing_wl_row->variation_id ); ?>"
							data-in-wishlist="1"
						>
							<?php esc_html_e( 'Remove', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
