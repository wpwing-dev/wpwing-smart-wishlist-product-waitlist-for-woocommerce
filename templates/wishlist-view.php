<?php
/**
 * Wishlist shortcode view — renders the customer's saved products.
 *
 * Variables available from FrontendWishlist::render_shortcode():
 *   array $wishlist_items  Each element: ['row' => object, 'product' => WC_Product].
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'ABSPATH' ) || exit;

$wishlist_items = isset( $wishlist_items ) ? (array) $wishlist_items : array();
?>

<?php if ( ! $wishlist_items ) : ?>
	<p class="wpwing-wishlist-empty">
		<?php esc_html_e( 'Your wishlist is empty.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
	</p>
<?php else : ?>
	<table class="wpwing-wishlist-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Product', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Price', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $wishlist_items as $item ) : ?>
				<?php
				$row     = $item['row'];
				$product = $item['product'];
				?>
				<tr class="wpwing-wishlist-row" data-row-id="<?php echo esc_attr( $row->id ); ?>">
					<td>
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
							<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
							<?php echo esc_html( $product->get_name() ); ?>
						</a>
					</td>
					<td><?php echo wp_kses_post( wc_price( (float) $product->get_price() ) ); ?></td>
					<td>
						<button
							type="button"
							class="wpwing-wishlist-toggle button"
							data-product-id="<?php echo esc_attr( $row->product_id ); ?>"
							data-variation-id="<?php echo esc_attr( $row->variation_id ); ?>"
							data-in-wishlist="1"
						>
							<?php esc_html_e( 'Remove', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
