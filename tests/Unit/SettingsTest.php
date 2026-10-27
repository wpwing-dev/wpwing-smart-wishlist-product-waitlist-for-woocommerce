<?php

use PHPUnit\Framework\TestCase;
use WPWing\WishlistWaitlist\Core\Settings;

class SettingsTest extends TestCase {

	protected function setUp(): void {
		global $wpwing_test_options, $wpwing_test_bloginfo, $wpwing_test_users;
		$wpwing_test_options  = array();
		$wpwing_test_bloginfo = array();
		$wpwing_test_users    = array();
	}

	public function test_optional_email_sanitizer_keeps_valid_addresses(): void {
		$this->assertSame( 'customer@example.com', Settings::sanitize_optional_email( ' customer@example.com ' ) );
	}

	public function test_optional_email_sanitizer_discards_invalid_addresses(): void {
		$this->assertSame( '', Settings::sanitize_optional_email( 'not-an-email' ) );
	}

	public function test_retention_sanitizer_clamps_values(): void {
		$this->assertSame( 1, Settings::sanitize_retention_days( 0 ) );
		$this->assertSame( 3650, Settings::sanitize_retention_days( 5000 ) );
		$this->assertSame( 30, Settings::sanitize_retention_days( 30 ) );
	}

	public function test_placeholder_expansion_replaces_known_tokens(): void {
		$this->assertSame(
			'Hello Store - Product',
			Settings::expand_placeholders(
				'Hello {store_name} - {product_name}',
				array(
					'store_name'   => 'Store',
					'product_name' => 'Product',
				)
			)
		);
	}

	public function test_placeholder_expansion_leaves_unmatched_tokens_untouched(): void {
		$this->assertSame(
			'Hi {name}, order 123 shipped',
			Settings::expand_placeholders( 'Hi {name}, order {order_id} shipped', array( 'order_id' => '123' ) )
		);
	}

	public function test_setup_wizard_defaults_to_incomplete(): void {
		$this->assertFalse( Settings::wizard_completed() );
	}

	public function test_button_style_sanitizes_to_supported_values(): void {
		$this->assertSame( 'filled', Settings::sanitize_button_style( 'filled' ) );
		$this->assertSame( 'default', Settings::sanitize_button_style( 'unknown' ) );
		$this->assertSame( 'default', Settings::wishlist_button_style() );
	}

	public function test_button_position_sanitizes_to_supported_values(): void {
		$this->assertSame( 'before_add_to_cart', Settings::sanitize_button_position( 'before_add_to_cart' ) );
		$this->assertSame( 'after_add_to_cart', Settings::sanitize_button_position( 'unknown' ) );
		$this->assertSame( 'after_add_to_cart', Settings::wishlist_button_position() );
	}

	public function test_setup_wizard_completion_can_be_saved_and_read_back(): void {
		Settings::set( 'wizard_completed', true );
		$this->assertTrue( Settings::wizard_completed() );
		Settings::set( 'wizard_completed', false );
		$this->assertFalse( Settings::wizard_completed() );
	}

	public function test_is_wishlist_and_waitlist_enabled_default_to_true(): void {
		$this->assertTrue( Settings::is_wishlist_enabled() );
		$this->assertTrue( Settings::is_waitlist_enabled() );
	}

	public function test_is_wishlist_and_waitlist_enabled_respect_stored_value(): void {
		update_option( 'wpwing_wl_enable_wishlist', false );
		update_option( 'wpwing_wl_enable_waitlist', false );
		$this->assertFalse( Settings::is_wishlist_enabled() );
		$this->assertFalse( Settings::is_waitlist_enabled() );
	}

	public function test_wishlist_button_labels_fall_back_to_translated_defaults(): void {
		$this->assertSame( '♡ Add to wishlist', Settings::wishlist_btn_add() );
		$this->assertSame( '♥ Remove from wishlist', Settings::wishlist_btn_remove() );
	}

	public function test_wishlist_button_labels_use_custom_values_when_set(): void {
		update_option( 'wpwing_wl_wishlist_btn_add', 'Save it' );
		update_option( 'wpwing_wl_wishlist_btn_remove', 'Unsave it' );
		$this->assertSame( 'Save it', Settings::wishlist_btn_add() );
		$this->assertSame( 'Unsave it', Settings::wishlist_btn_remove() );
	}

	public function test_waitlist_form_text_falls_back_to_translated_defaults(): void {
		$this->assertSame(
			'This product is currently out of stock. Enter your email address to be notified when it becomes available.',
			Settings::waitlist_form_intro()
		);
		$this->assertSame(
			'The selected variation is currently out of stock. Enter your email address to be notified when it becomes available.',
			Settings::waitlist_form_intro_variable()
		);
		$this->assertSame( 'Notify Me', Settings::waitlist_btn_submit() );
		$this->assertSame( 'Your email address', Settings::waitlist_email_placeholder() );
		$this->assertSame( "You're on the waitlist! We'll notify you when it's back in stock.", Settings::waitlist_joined_text() );
		$this->assertSame( 'Remove me from waitlist', Settings::waitlist_btn_leave() );
	}

	public function test_waitlist_form_text_uses_custom_values_when_set(): void {
		update_option( 'wpwing_wl_waitlist_btn_submit', 'Notify me please' );
		update_option( 'wpwing_wl_waitlist_joined_text', "You're in!" );
		$this->assertSame( 'Notify me please', Settings::waitlist_btn_submit() );
		$this->assertSame( "You're in!", Settings::waitlist_joined_text() );
	}

	public function test_email_templates_fall_back_to_translated_defaults(): void {
		$this->assertStringContainsString( '{store_name}', Settings::email_subject_template() );
		$this->assertStringContainsString( '{product_name}', Settings::email_subject_template() );
		$this->assertStringContainsString( '{product_name}', Settings::email_body_template() );
		$this->assertStringContainsString( '{unsubscribe_url}', Settings::email_body_template() );
	}

	public function test_email_templates_use_custom_values_when_set(): void {
		update_option( 'wpwing_wl_email_subject_template', 'Back in stock: {product_name}' );
		update_option( 'wpwing_wl_email_body_template', '<p>{product_name} is back.</p>' );
		$this->assertSame( 'Back in stock: {product_name}', Settings::email_subject_template() );
		$this->assertSame( '<p>{product_name} is back.</p>', Settings::email_body_template() );
	}

	public function test_from_name_falls_back_to_site_title(): void {
		global $wpwing_test_bloginfo;
		$wpwing_test_bloginfo = array( 'name' => 'Test Store' );
		$this->assertSame( 'Test Store', Settings::from_name() );
	}

	public function test_from_name_uses_custom_value_when_set(): void {
		update_option( 'wpwing_wl_from_name', 'Acme Shop' );
		$this->assertSame( 'Acme Shop', Settings::from_name() );
	}

	public function test_from_email_uses_custom_value_when_valid(): void {
		update_option( 'wpwing_wl_from_email', 'store@example.com' );
		$this->assertSame( 'store@example.com', Settings::from_email() );
	}

	public function test_from_email_falls_back_to_admin_email_when_unset(): void {
		update_option( 'admin_email', 'admin@example.com' );
		$this->assertSame( 'admin@example.com', Settings::from_email() );
	}

	public function test_from_email_falls_back_to_admin_email_when_stored_value_is_invalid(): void {
		// Defensive re-validation at read time — e.g. a value written directly to
		// the DB, bypassing Settings::sanitize_optional_email().
		update_option( 'wpwing_wl_from_email', 'not-an-email' );
		update_option( 'admin_email', 'admin@example.com' );
		$this->assertSame( 'admin@example.com', Settings::from_email() );
	}

	public function test_reply_to_falls_back_to_from_email_when_unset(): void {
		update_option( 'admin_email', 'admin@example.com' );
		$this->assertSame( 'admin@example.com', Settings::reply_to() );
	}

	public function test_reply_to_uses_custom_value_when_valid(): void {
		update_option( 'wpwing_wl_reply_to', 'support@example.com' );
		$this->assertSame( 'support@example.com', Settings::reply_to() );
	}

	public function test_guest_retention_days_clamps_a_bad_stored_value(): void {
		// The getter's fallback (30, the factory default) differs deliberately
		// from the sanitizer's clamp floor (1) — a stored 0/negative value means
		// "never saved", not "retain for 1 day", so it should read as unset.
		update_option( 'wpwing_wl_guest_retention_days', 0 );
		$this->assertSame( 30, Settings::guest_retention_days() );

		update_option( 'wpwing_wl_guest_retention_days', 9999 );
		$this->assertSame( 3650, Settings::guest_retention_days() );

		update_option( 'wpwing_wl_guest_retention_days', 45 );
		$this->assertSame( 45, Settings::guest_retention_days() );
	}

	public function test_option_name_adds_the_shared_prefix(): void {
		$this->assertSame( 'wpwing_wl_guest_retention_days', Settings::option_name( 'guest_retention_days' ) );
	}

	public function test_sanitize_text_strips_tags_and_trims(): void {
		$this->assertSame( 'Hello world', Settings::sanitize_text( '  <b>Hello world</b>  ' ) );
	}

	public function test_defaults_includes_the_documented_setting_keys(): void {
		$defaults = Settings::defaults();
		$this->assertArrayHasKey( 'enable_wishlist', $defaults );
		$this->assertArrayHasKey( 'guest_retention_days', $defaults );
		$this->assertArrayHasKey( 'captcha_provider', $defaults );
		$this->assertArrayHasKey( 'captcha_site_key', $defaults );
		$this->assertArrayHasKey( 'captcha_secret_key', $defaults );
		$this->assertSame( 30, $defaults['guest_retention_days'] );
		$this->assertSame( 'after_add_to_cart', $defaults['wishlist_btn_position'] );
		$this->assertSame( 'none', $defaults['captcha_provider'] );
	}

	public function test_captcha_provider_defaults_to_none(): void {
		$this->assertSame( 'none', Settings::captcha_provider() );
	}

	public function test_captcha_provider_sanitizes_to_supported_values(): void {
		$this->assertSame( 'recaptcha_v3', Settings::sanitize_captcha_provider( 'recaptcha_v3' ) );
		$this->assertSame( 'turnstile', Settings::sanitize_captcha_provider( 'TURNSTILE' ) );
		$this->assertSame( 'none', Settings::sanitize_captcha_provider( 'invalid_provider' ) );
	}

	public function test_captcha_keys_default_to_empty(): void {
		$this->assertSame( '', Settings::captcha_site_key() );
		$this->assertSame( '', Settings::captcha_secret_key() );
	}

	public function test_is_captcha_enabled_requires_provider_and_keys(): void {
		// Default is provider 'none'
		$this->assertFalse( Settings::is_captcha_enabled() );

		// Provider set but keys missing
		update_option( 'wpwing_wl_captcha_provider', 'recaptcha_v3' );
		$this->assertFalse( Settings::is_captcha_enabled() );

		// Only site key set
		update_option( 'wpwing_wl_captcha_site_key', 'site-key-123' );
		$this->assertFalse( Settings::is_captcha_enabled() );

		// Both keys set
		update_option( 'wpwing_wl_captcha_secret_key', 'secret-key-456' );
		$this->assertTrue( Settings::is_captcha_enabled() );

		// Turnstile with keys
		update_option( 'wpwing_wl_captcha_provider', 'turnstile' );
		$this->assertTrue( Settings::is_captcha_enabled() );

		// If provider is set back to 'none', should be disabled even if keys exist
		update_option( 'wpwing_wl_captcha_provider', 'none' );
		$this->assertFalse( Settings::is_captcha_enabled() );
	}

	public function test_form_preset_sanitizes_to_supported_values(): void {
		$this->assertSame( 'default', Settings::waitlist_form_preset() );
		$this->assertSame( 'compact', Settings::sanitize_form_preset( 'compact' ) );
		$this->assertSame( 'card', Settings::sanitize_form_preset( 'card' ) );
		$this->assertSame( 'popup', Settings::sanitize_form_preset( 'popup' ) );
		$this->assertSame( 'default', Settings::sanitize_form_preset( 'invalid_preset' ) );
	}

	public function test_form_preset_respects_stored_option(): void {
		update_option( 'wpwing_wl_waitlist_form_preset', 'card' );
		$this->assertSame( 'card', Settings::waitlist_form_preset() );
	}

	public function test_stock_status_sanitizes_and_defaults_to_outofstock(): void {
		$this->assertSame( array( 'outofstock' ), Settings::waitlist_stock_status() );
		$this->assertSame( array( 'outofstock', 'onbackorder' ), Settings::sanitize_stock_status( array( 'outofstock', 'onbackorder' ) ) );
		$this->assertSame( array( 'onbackorder' ), Settings::sanitize_stock_status( 'onbackorder' ) );
		$this->assertSame( array( 'outofstock' ), Settings::sanitize_stock_status( array( 'invalid_status' ) ) );
		$this->assertSame( array( 'outofstock' ), Settings::sanitize_stock_status( '' ) );
	}

	public function test_is_waitlist_allowed_for_stock_status(): void {
		$this->assertTrue( Settings::is_waitlist_allowed_for_stock_status( 'outofstock' ) );
		$this->assertFalse( Settings::is_waitlist_allowed_for_stock_status( 'onbackorder' ) );
		$this->assertFalse( Settings::is_waitlist_allowed_for_stock_status( 'instock' ) );

		update_option( 'wpwing_wl_waitlist_stock_status', array( 'outofstock', 'onbackorder' ) );
		$this->assertTrue( Settings::is_waitlist_allowed_for_stock_status( 'outofstock' ) );
		$this->assertTrue( Settings::is_waitlist_allowed_for_stock_status( 'onbackorder' ) );
		$this->assertFalse( Settings::is_waitlist_allowed_for_stock_status( 'instock' ) );
	}

	public function test_wishlist_icon_shape_defaults_to_heart(): void {
		$this->assertSame( 'heart', Settings::wishlist_icon_shape() );
	}

	public function test_wishlist_icon_shape_sanitizes_to_supported_values(): void {
		$this->assertSame( 'heart', Settings::sanitize_icon_shape( 'heart' ) );
		$this->assertSame( 'star', Settings::sanitize_icon_shape( 'STAR' ) );
		$this->assertSame( 'bookmark', Settings::sanitize_icon_shape( 'bookmark' ) );
		$this->assertSame( 'bell', Settings::sanitize_icon_shape( 'bell' ) );
		$this->assertSame( 'heart', Settings::sanitize_icon_shape( 'invalid' ) );
	}

	public function test_wishlist_icon_shape_respects_stored_option(): void {
		update_option( 'wpwing_wl_wishlist_icon_shape', 'star' );
		$this->assertSame( 'star', Settings::wishlist_icon_shape() );
	}

	public function test_wishlist_icon_symbol_returns_correct_symbols(): void {
		$this->assertSame( '♡', Settings::wishlist_icon_symbol( 'heart', false ) );
		$this->assertSame( '♥', Settings::wishlist_icon_symbol( 'heart', true ) );
		$this->assertSame( '☆', Settings::wishlist_icon_symbol( 'star', false ) );
		$this->assertSame( '★', Settings::wishlist_icon_symbol( 'star', true ) );
		$this->assertSame( '🔖', Settings::wishlist_icon_symbol( 'bookmark', false ) );
		$this->assertSame( '🔔', Settings::wishlist_icon_symbol( 'bell', false ) );
	}

	public function test_wishlist_btn_labels_adapt_to_icon_shape_when_unset(): void {
		update_option( 'wpwing_wl_wishlist_icon_shape', 'star' );
		$this->assertSame( '☆ Add to wishlist', Settings::wishlist_btn_add() );
		$this->assertSame( '★ Remove from wishlist', Settings::wishlist_btn_remove() );

		update_option( 'wpwing_wl_wishlist_icon_shape', 'bookmark' );
		$this->assertSame( '🔖 Add to wishlist', Settings::wishlist_btn_add() );
		$this->assertSame( '🔖 Remove from wishlist', Settings::wishlist_btn_remove() );

		update_option( 'wpwing_wl_wishlist_icon_shape', 'bell' );
		$this->assertSame( '🔔 Add to wishlist', Settings::wishlist_btn_add() );
		$this->assertSame( '🔔 Remove from wishlist', Settings::wishlist_btn_remove() );

		update_option( 'wpwing_wl_wishlist_icon_shape', 'heart' );
		$this->assertSame( '♡ Add to wishlist', Settings::wishlist_btn_add() );
		$this->assertSame( '♥ Remove from wishlist', Settings::wishlist_btn_remove() );
	}

	public function test_wishlist_btn_labels_custom_override_takes_precedence_over_icon_shape(): void {
		update_option( 'wpwing_wl_wishlist_icon_shape', 'star' );
		update_option( 'wpwing_wl_wishlist_btn_add', 'Custom Add' );
		update_option( 'wpwing_wl_wishlist_btn_remove', 'Custom Remove' );
		$this->assertSame( 'Custom Add', Settings::wishlist_btn_add() );
		$this->assertSame( 'Custom Remove', Settings::wishlist_btn_remove() );
	}
}

