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
$wpwing_wl_form_intro          = isset( $wpwing_wl_form_intro ) ? (string) $wpwing_wl_form_intro : \WPWing\WishlistWaitlist\Core\Settings::waitlist_form_intro();
$wpwing_wl_form_intro_variable = isset( $wpwing_wl_form_intro_variable ) ? (string) $wpwing_wl_form_intro_variable : \WPWing\WishlistWaitlist\Core\Settings::waitlist_form_intro_variable();
$wpwing_wl_email_placeholder   = isset( $wpwing_wl_email_placeholder ) ? (string) $wpwing_wl_email_placeholder : \WPWing\WishlistWaitlist\Core\Settings::waitlist_email_placeholder();
$wpwing_wl_btn_submit          = isset( $wpwing_wl_btn_submit ) ? (string) $wpwing_wl_btn_submit : \WPWing\WishlistWaitlist\Core\Settings::waitlist_btn_submit();
$wpwing_wl_joined_text         = isset( $wpwing_wl_joined_text ) ? (string) $wpwing_wl_joined_text : \WPWing\WishlistWaitlist\Core\Settings::waitlist_joined_text();
$wpwing_wl_btn_leave           = isset( $wpwing_wl_btn_leave ) ? (string) $wpwing_wl_btn_leave : \WPWing\WishlistWaitlist\Core\Settings::waitlist_btn_leave();

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
		<?php echo esc_html( $wpwing_wl_variation_aware ? $wpwing_wl_form_intro_variable : $wpwing_wl_form_intro ); ?>
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
			placeholder="<?php echo esc_attr( $wpwing_wl_email_placeholder ); ?>"
			value="<?php echo esc_attr( $wpwing_wl_prefill_email ); ?>"
			autocomplete="email"
			required
		/>

		<button type="submit" class="wpwing-waitlist-submit button">
			<?php echo esc_html( $wpwing_wl_btn_submit ); ?>
		</button>
	</form>

	<div class="wpwing-waitlist-message" aria-live="polite" role="status"></div>

	<div class="wpwing-waitlist-joined<?php echo esc_attr( $wpwing_wl_joined_hidden ); ?>">
		<p class="wpwing-waitlist-joined-text">
			<?php echo esc_html( $wpwing_wl_joined_text ); ?>
		</p>
		<button type="button" class="wpwing-waitlist-leave button">
			<?php echo esc_html( $wpwing_wl_btn_leave ); ?>
		</button>
		<div class="wpwing-waitlist-leave-message" aria-live="polite" role="status"></div>
	</div>
</div>
