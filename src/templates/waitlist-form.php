<?php
/**
 * Waitlist email capture form.
 *
 * Variables available from FrontendWaitlist::maybe_show_form():
 *   bool        $wpwing_wl_hidden          True for variable products — JS controls visibility.
 *   bool        $wpwing_wl_variation_aware True for variable products — use variation-specific intro text.
 *   \WC_Product $product                   The current WooCommerce product (via global $product).
 *   string      $wpwing_wl_prefill_email   Logged-in user's email, or empty string for guests.
 *   bool        $wpwing_wl_already_on_waitlist True when the visitor has an active entry.
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'ABSPATH' ) || exit;

$wpwing_wl_hidden              = isset( $wpwing_wl_hidden ) && $wpwing_wl_hidden;
$wpwing_wl_variation_aware     = isset( $wpwing_wl_variation_aware ) && $wpwing_wl_variation_aware;
$wpwing_wl_already_on_waitlist = isset( $wpwing_wl_already_on_waitlist ) && $wpwing_wl_already_on_waitlist;
$wpwing_wl_prefill_email       = isset( $wpwing_wl_prefill_email ) ? (string) $wpwing_wl_prefill_email : '';

/**
 * Product injected by FrontendWaitlist::maybe_show_form() via include.
 *
 * @var \WC_Product $product
 */
$wpwing_wl_product_id = $product->get_id();

// PHP controls which state is visible on initial render.
// JS transitions between states after join/leave actions.
$wpwing_wl_form_hidden        = $wpwing_wl_already_on_waitlist ? ' wpwing-wl-hidden' : '';
$wpwing_wl_form_inline_hidden = $wpwing_wl_already_on_waitlist ? ' style="display:none"' : '';
$wpwing_wl_joined_hidden      = $wpwing_wl_already_on_waitlist ? '' : ' wpwing-wl-hidden';
?>
<div
	class="wpwing-waitlist-form<?php echo esc_attr( $wpwing_wl_hidden ? ' wpwing-wl-hidden' : '' ); ?>"
	data-product-id="<?php echo esc_attr( $wpwing_wl_product_id ); ?>"
>
	<p class="wpwing-waitlist-intro<?php echo esc_attr( $wpwing_wl_form_hidden ); ?>"<?php echo $wpwing_wl_form_inline_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value is a static string literal, never user input ?>>
		<?php
		if ( $wpwing_wl_variation_aware ) {
			esc_html_e( 'The selected variation is currently out of stock. Enter your email address to be notified when it becomes available.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
		} else {
			esc_html_e( 'This product is currently out of stock. Enter your email address to be notified when it becomes available.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
		}
		?>
	</p>

	<form class="wpwing-waitlist-fields<?php echo esc_attr( $wpwing_wl_form_hidden ); ?>"<?php echo $wpwing_wl_form_inline_hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value is a static string literal, never user input ?> novalidate>
		<?php /* Honeypot — hidden from humans via CSS, traps automated bots */ ?>
		<div class="wpwing-hp" aria-hidden="true">
			<label for="wpwing_hp_field">
				<?php esc_html_e( 'Leave this field empty', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
			</label>
			<input
				type="text"
				id="wpwing_hp_field"
				name="wpwing_hp"
				tabindex="-1"
				autocomplete="off"
			/>
		</div>

		<input type="hidden" name="product_id" value="<?php echo esc_attr( $wpwing_wl_product_id ); ?>" />
		<input type="hidden" name="variation_id" class="wpwing-variation-id" value="0" />

		<input
			type="email"
			name="email"
			class="wpwing-waitlist-email"
			placeholder="<?php esc_attr_e( 'Your email address', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
			value="<?php echo esc_attr( $wpwing_wl_prefill_email ); ?>"
			autocomplete="email"
			required
		/>

		<button type="submit" class="wpwing-waitlist-submit button">
			<?php esc_html_e( 'Notify Me', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
		</button>
	</form>

	<div class="wpwing-waitlist-message" aria-live="polite" role="status"></div>

	<div class="wpwing-waitlist-joined<?php echo esc_attr( $wpwing_wl_joined_hidden ); ?>">
		<p class="wpwing-waitlist-joined-text">
			<?php esc_html_e( "You're on the waitlist! We'll notify you when it's back in stock.", 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
		</p>
		<button type="button" class="wpwing-waitlist-leave button">
			<?php esc_html_e( 'Remove me from waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
		</button>
		<div class="wpwing-waitlist-leave-message" aria-live="polite" role="status"></div>
	</div>
</div>
