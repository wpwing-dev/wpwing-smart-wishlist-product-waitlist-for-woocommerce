<?php
/**
 * Waitlist email capture form.
 *
 * Variables available from FrontendWaitlist::maybe_show_form():
 *   bool        $hidden          True for variable products — JS controls visibility.
 *   bool        $variation_aware True for variable products — use variation-specific intro text.
 *   \WC_Product $product         The current WooCommerce product (via global $product).
 *   string      $prefill_email   Logged-in user's email, or empty string for guests.
 *   bool        $already_on_waitlist True when the visitor has an active entry.
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'ABSPATH' ) || exit;

$hidden              = isset( $hidden ) && $hidden;
$variation_aware     = isset( $variation_aware ) && $variation_aware;
$already_on_waitlist = isset( $already_on_waitlist ) && $already_on_waitlist;
$prefill_email       = isset( $prefill_email ) ? (string) $prefill_email : '';

/**
 * Product injected by FrontendWaitlist::maybe_show_form() via include.
 *
 * @var \WC_Product $product
 */
$product_id = $product->get_id();

// PHP controls which state is visible on initial render.
// JS transitions between states after join/leave actions.
$form_hidden   = $already_on_waitlist ? ' style="display:none"' : '';
$joined_hidden = $already_on_waitlist ? '' : ' wpwing-wl-hidden';
?>
<div
	class="wpwing-waitlist-form<?php echo $hidden ? ' wpwing-wl-hidden' : ''; ?>"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
>
	<p class="wpwing-waitlist-intro"<?php echo $form_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		if ( $variation_aware ) {
			esc_html_e( 'The selected variation is currently out of stock. Enter your email address to be notified when it becomes available.', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
		} else {
			esc_html_e( 'This product is currently out of stock. Enter your email address to be notified when it becomes available.', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
		}
		?>
	</p>

	<form class="wpwing-waitlist-fields"<?php echo $form_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> novalidate>
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
			value="<?php echo esc_attr( $prefill_email ); ?>"
			autocomplete="email"
			required
		/>

		<button type="submit" class="wpwing-waitlist-submit button">
			<?php esc_html_e( 'Notify Me', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
		</button>
	</form>

	<div class="wpwing-waitlist-message" aria-live="polite" role="status"></div>

	<div class="wpwing-waitlist-joined<?php echo $joined_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
		<p class="wpwing-waitlist-joined-text">
			<?php esc_html_e( "You're on the waitlist! We'll notify you when it's back in stock.", 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
		</p>
		<button type="button" class="wpwing-waitlist-leave button">
			<?php esc_html_e( 'Remove me from waitlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
		</button>
		<div class="wpwing-waitlist-leave-message" aria-live="polite" role="status"></div>
	</div>
</div>
