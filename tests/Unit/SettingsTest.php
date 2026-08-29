<?php

use PHPUnit\Framework\TestCase;
use WPWing\WishlistWaitlist\Core\Settings;

class SettingsTest extends TestCase {

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

	public function test_setup_wizard_defaults_to_incomplete(): void {
		$this->assertFalse( Settings::wizard_completed() );
	}

	public function test_button_style_sanitizes_to_supported_values(): void {
		$this->assertSame( 'filled', Settings::sanitize_button_style( 'filled' ) );
		$this->assertSame( 'default', Settings::sanitize_button_style( 'unknown' ) );
		$this->assertSame( 'default', Settings::wishlist_button_style() );
	}

	public function test_setup_wizard_completion_can_be_saved_and_read_back(): void {
		Settings::set( 'wizard_completed', true );
		$this->assertTrue( Settings::wizard_completed() );
		Settings::set( 'wizard_completed', false );
		$this->assertFalse( Settings::wizard_completed() );
	}
}
