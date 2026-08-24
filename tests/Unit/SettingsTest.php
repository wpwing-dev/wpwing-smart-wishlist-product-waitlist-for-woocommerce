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
}
