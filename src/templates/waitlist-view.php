<?php
/**
 * Waitlist shortcode view — renders the products the current user is waiting for.
 *
 * Variables available from FrontendWaitlist::render_shortcode():
 *   array $wpwing_wl_waitlist_items  Each element: ['row' => object, 'product' => WC_Product].
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'ABSPATH' ) || exit;

$wpwing_wl_waitlist_items = isset( $wpwing_wl_waitlist_items ) ? (array) $wpwing_wl_waitlist_items : array();
?>

<?php if ( ! $wpwing_wl_waitlist_items ) : ?>
	<p class="wpwing-waitlist-empty">
		<?php esc_html_e( "You're not on the waitlist for any products.", 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
	</p>
<?php else : ?>
	<table class="wpwing-waitlist-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Product', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Price', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $wpwing_wl_waitlist_items as $wpwing_wl_item ) : ?>
				<?php
				$wpwing_wl_row     = $wpwing_wl_item['row'];
				$wpwing_wl_product = $wpwing_wl_item['product'];
				?>
				<tr class="wpwing-waitlist-row" data-row-id="<?php echo esc_attr( $wpwing_wl_row->id ); ?>">
					<td>
						<a href="<?php echo esc_url( $wpwing_wl_product->get_permalink() ); ?>">
							<?php echo wp_kses_post( $wpwing_wl_product->get_image( 'thumbnail' ) ); ?>
							<?php echo esc_html( $wpwing_wl_product->get_name() ); ?>
						</a>
					</td>
					<td><?php echo wp_kses_post( wc_price( (float) $wpwing_wl_product->get_price() ) ); ?></td>
					<td>
						<div class="wpwing-waitlist-actions">
							<button
								type="button"
								class="wpwing-waitlist-view-leave button"
								data-product-id="<?php echo esc_attr( $wpwing_wl_row->product_id ); ?>"
								data-variation-id="<?php echo esc_attr( $wpwing_wl_row->variation_id ); ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Leave waitlist for "%s"', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ), $wpwing_wl_product->get_name() ) ); ?>"
							>
								<?php esc_html_e( 'Leave Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
							</button>
						</div>
						<span class="wpwing-waitlist-leave-message" aria-live="polite"></span>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
