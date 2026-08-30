<?php

use PHPUnit\Framework\TestCase;
use WPWing\WishlistWaitlist\Core\Database;

class DatabaseTest extends TestCase {

	protected function setUp(): void {
		global $wpdb;
		$wpdb         = new FakeWpdb();
		$wpdb->prefix = 'wp_';
	}

	public function test_waitlists_table_name_is_prefixed(): void {
		$this->assertSame( 'wp_wpwing_wl_waitlists', Database::waitlists() );
	}

	public function test_wishlists_table_name_is_prefixed(): void {
		$this->assertSame( 'wp_wpwing_wl_wishlists', Database::wishlists() );
	}

	public function test_table_names_respect_a_custom_prefix(): void {
		global $wpdb;
		$wpdb->prefix = 'wp_multisite_3_';
		$this->assertSame( 'wp_multisite_3_wpwing_wl_waitlists', Database::waitlists() );
		$this->assertSame( 'wp_multisite_3_wpwing_wl_wishlists', Database::wishlists() );
	}
}
