<?php
/**
 * Waitlist email capture form.
 *
 * Variables available from FrontendWaitlist::maybe_show_form():
 *   bool        $hidden   True for variable products — JS controls visibility.
 *   \WC_Product $product  The current WooCommerce product (via global $product).
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'ABSPATH' ) || exit;

$hidden     = isset( $hidden ) && $hidden;
$product_id = $product->get_id();
?>
<div class="wpwing-waitlist-form<?php echo $hidden ? ' wpwing-wl-hidden' : ''; ?>">
	<p class="wpwing-waitlist-intro">
		<?php esc_html_e( 'This product is currently out of stock. Enter your email address to be notified when it becomes available.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
	</p>

	<form class="wpwing-waitlist-fields" novalidate>
		<?php /* Honeypot — hidden from humans via CSS, traps automated bots */ ?>
		<div class="wpwing-hp" aria-hidden="true">
			<label for="wpwing_hp_field">
				<?php esc_html_e( 'Leave this field empty', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
			</label>
			<input
				type="text"
				id="wpwing_hp_field"
				name="wpwing_hp"
				tabindex="-1"
				autocomplete="off"
			/>
		</div>

		<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
		<input type="hidden" name="variation_id" class="wpwing-variation-id" value="0" />

		<input
			type="email"
			name="email"
			class="wpwing-waitlist-email"
			placeholder="<?php esc_attr_e( 'Your email address', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>"
			required
		/>

		<button type="submit" class="wpwing-waitlist-submit button">
			<?php esc_html_e( 'Notify Me', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
		</button>
	</form>

	<div class="wpwing-waitlist-message" aria-live="polite" role="status"></div>
</div>
