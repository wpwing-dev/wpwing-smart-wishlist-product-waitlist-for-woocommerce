<?php
/**
 * Wishlist shortcode view — renders the customer's saved products.
 *
 * Variables available from FrontendWishlist::render_shortcode():
 *   array  $wpwing_wl_wishlist_items  Each element: ['row' => object, 'product' => WC_Product].
 *   string $wpwing_wl_share_url      The wishlist page URL, for the social share links.
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'ABSPATH' ) || exit;

$wpwing_wl_wishlist_items = isset( $wpwing_wl_wishlist_items ) ? (array) $wpwing_wl_wishlist_items : array();
$wpwing_wl_share_url      = isset( $wpwing_wl_share_url ) ? (string) $wpwing_wl_share_url : '';
?>

<?php if ( ! $wpwing_wl_wishlist_items ) : ?>
	<p class="wpwing-wishlist-empty">
		<?php esc_html_e( 'Your wishlist is empty.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
	</p>
<?php else : ?>
	<div class="wpwing-wishlist-bulk-actions">
		<button
			type="button"
			class="button button-primary wpwing-wishlist-bulk-add-to-cart"
		>
			<?php esc_html_e( 'Add all to cart', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
		</button>
		<button
			type="button"
			class="button wpwing-wishlist-clear-all"
		>
			<?php esc_html_e( 'Clear wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
		</button>
	</div>
	<table class="wpwing-wishlist-table">
		<thead>
			<tr>
				<th class="wpwing-wishlist-col-select">
					<input
						type="checkbox"
						class="wpwing-wishlist-select-all"
						aria-label="<?php esc_attr_e( 'Select all items', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
					/>
				</th>
				<th class="wpwing-wishlist-col-product"><?php esc_html_e( 'Product', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<th class="wpwing-wishlist-col-price"><?php esc_html_e( 'Price', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<th class="wpwing-wishlist-col-stock"><?php esc_html_e( 'Stock Status', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<th class="wpwing-wishlist-col-quantity"><?php esc_html_e( 'Quantity', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<th class="wpwing-wishlist-col-actions"><?php esc_html_e( 'Action', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $wpwing_wl_wishlist_items as $wpwing_wl_item ) : ?>
				<?php
				$wpwing_wl_row      = $wpwing_wl_item['row'];
				$wpwing_wl_product  = $wpwing_wl_item['product'];
				$wpwing_wl_qty      = isset( $wpwing_wl_row->quantity ) ? max( 1, (int) $wpwing_wl_row->quantity ) : 1;
				$wpwing_wl_price    = (float) $wpwing_wl_product->get_price();
				$wpwing_wl_in_stock = $wpwing_wl_product->is_in_stock();
				$wpwing_wl_backord  = $wpwing_wl_product->is_on_backorder();
				$wpwing_wl_max_qty  = $wpwing_wl_product->get_max_purchase_quantity();
				$wpwing_wl_max_attr = $wpwing_wl_max_qty > 0 ? 'max="' . esc_attr( (string) $wpwing_wl_max_qty ) . '"' : '';

				$wpwing_wl_cart_url = $wpwing_wl_product->add_to_cart_url();
				if ( ! $wpwing_wl_product->is_type( 'variable' ) && $wpwing_wl_in_stock ) {
					$wpwing_wl_cart_url = add_query_arg( 'quantity', $wpwing_wl_qty, $wpwing_wl_cart_url );
				}
				?>
				<tr
					class="wpwing-wishlist-row"
					data-row-id="<?php echo esc_attr( (string) $wpwing_wl_row->id ); ?>"
					data-product-id="<?php echo esc_attr( (string) $wpwing_wl_row->product_id ); ?>"
					data-variation-id="<?php echo esc_attr( (string) $wpwing_wl_row->variation_id ); ?>"
					data-price="<?php echo esc_attr( (string) $wpwing_wl_price ); ?>"
				>
					<td class="wpwing-wishlist-col-select">
						<input
							type="checkbox"
							class="wpwing-wishlist-select-item"
							data-row-id="<?php echo esc_attr( (string) $wpwing_wl_row->id ); ?>"
							data-product-id="<?php echo esc_attr( (string) $wpwing_wl_row->product_id ); ?>"
							data-variation-id="<?php echo esc_attr( (string) $wpwing_wl_row->variation_id ); ?>"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Select "%s"', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ), $wpwing_wl_product->get_name() ) ); ?>"
						/>
					</td>
					<td class="wpwing-wishlist-col-product">
						<a href="<?php echo esc_url( $wpwing_wl_product->get_permalink() ); ?>">
							<?php echo wp_kses_post( $wpwing_wl_product->get_image( 'thumbnail' ) ); ?>
							<?php echo esc_html( $wpwing_wl_product->get_name() ); ?>
						</a>
					</td>
					<td class="wpwing-wishlist-col-price"><?php echo wp_kses_post( wc_price( $wpwing_wl_price ) ); ?></td>
					<td class="wpwing-wishlist-col-stock">
						<?php if ( $wpwing_wl_in_stock ) : ?>
							<span class="wpwing-stock-status wpwing-in-stock"><?php esc_html_e( 'In Stock', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></span>
						<?php elseif ( $wpwing_wl_backord ) : ?>
							<span class="wpwing-stock-status wpwing-on-backorder"><?php esc_html_e( 'On Backorder', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></span>
						<?php else : ?>
							<span class="wpwing-stock-status wpwing-out-of-stock"><?php esc_html_e( 'Out of Stock', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></span>
						<?php endif; ?>
					</td>
					<td class="wpwing-wishlist-col-quantity">
						<input
							type="number"
							class="wpwing-wishlist-item-qty"
							value="<?php echo esc_attr( (string) $wpwing_wl_qty ); ?>"
							min="1"
							<?php echo $wpwing_wl_max_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							step="1"
							data-row-id="<?php echo esc_attr( (string) $wpwing_wl_row->id ); ?>"
							data-product-id="<?php echo esc_attr( (string) $wpwing_wl_row->product_id ); ?>"
							data-variation-id="<?php echo esc_attr( (string) $wpwing_wl_row->variation_id ); ?>"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Quantity for "%s"', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ), $wpwing_wl_product->get_name() ) ); ?>"
						/>
					</td>
					<td class="wpwing-wishlist-actions wpwing-wishlist-col-actions">
						<a
							href="<?php echo esc_url( $wpwing_wl_cart_url ); ?>"
							class="button wpwing-wishlist-add-to-cart"
							data-original-url="<?php echo esc_url( $wpwing_wl_product->add_to_cart_url() ); ?>"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Add "%s" to cart', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ), $wpwing_wl_product->get_name() ) ); ?>"
						>
							<?php echo esc_html( $wpwing_wl_product->add_to_cart_text() ); ?>
						</a>
						<button
							type="button"
							class="wpwing-wishlist-toggle button"
							data-product-id="<?php echo esc_attr( (string) $wpwing_wl_row->product_id ); ?>"
							data-variation-id="<?php echo esc_attr( (string) $wpwing_wl_row->variation_id ); ?>"
							data-in-wishlist="1"
							aria-pressed="true"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Remove "%s" from wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ), $wpwing_wl_product->get_name() ) ); ?>"
						>
							<?php esc_html_e( 'Remove', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr class="wpwing-wishlist-total">
				<th colspan="4"><?php esc_html_e( 'Total', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
				<td class="wpwing-wishlist-total-amount" colspan="2">
					<?php
					$wpwing_wl_total = array_reduce(
						$wpwing_wl_wishlist_items,
						function ( $wpwing_wl_carry, $wpwing_wl_item ) {
							$qty = isset( $wpwing_wl_item['row']->quantity ) ? max( 1, (int) $wpwing_wl_item['row']->quantity ) : 1;
							return $wpwing_wl_carry + ( (float) $wpwing_wl_item['product']->get_price() * $qty );
						},
						0.0
					);
					echo wp_kses_post( wc_price( $wpwing_wl_total ) );
					?>
				</td>
			</tr>
		</tfoot>
	</table>
	<?php if ( $wpwing_wl_share_url ) : ?>
		<?php
		$wpwing_wl_share_label   = __( 'Check out my wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
		$wpwing_wl_share_message = sprintf(
			/* translators: 1: share message label, 2: wishlist page URL */
			__( '%1$s: %2$s', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			$wpwing_wl_share_label,
			$wpwing_wl_share_url
		);
		?>
		<div class="wpwing-wishlist-share">
			<span class="wpwing-wishlist-share-label"><?php esc_html_e( 'Share your wishlist:', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></span>
			<a
				href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $wpwing_wl_share_message ) ); ?>"
				class="wpwing-wishlist-share-link wpwing-wishlist-share-whatsapp"
				target="_blank"
				rel="noopener noreferrer"
			><?php esc_html_e( 'WhatsApp', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></a>
			<a
				href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( $wpwing_wl_share_url ) . '&text=' . rawurlencode( $wpwing_wl_share_label ) ); ?>"
				class="wpwing-wishlist-share-link wpwing-wishlist-share-x"
				target="_blank"
				rel="noopener noreferrer"
			><?php esc_html_e( 'X', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></a>
			<a
				href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $wpwing_wl_share_url ) ); ?>"
				class="wpwing-wishlist-share-link wpwing-wishlist-share-facebook"
				target="_blank"
				rel="noopener noreferrer"
			><?php esc_html_e( 'Facebook', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></a>
			<a
				href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( $wpwing_wl_share_label ) . '&body=' . rawurlencode( $wpwing_wl_share_message ) ); ?>"
				class="wpwing-wishlist-share-link wpwing-wishlist-share-email"
			><?php esc_html_e( 'Email', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></a>
			<button
				type="button"
				class="wpwing-wishlist-share-link wpwing-wishlist-share-copy"
				data-share-url="<?php echo esc_attr( $wpwing_wl_share_url ); ?>"
				data-copied-label="<?php echo esc_attr__( 'Copied!', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
			><?php esc_html_e( 'Copy link', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></button>
		</div>
	<?php endif; ?>
<?php endif; ?>
