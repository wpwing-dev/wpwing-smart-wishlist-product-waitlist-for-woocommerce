<?php

use PHPUnit\Framework\TestCase;
use WPWing\WishlistWaitlist\Frontend\MyAccount;

class MyAccountTest extends TestCase {

	private MyAccount $my_account;

	protected function setUp(): void {
		global $wpwing_test_options, $wpwing_test_endpoints;
		$wpwing_test_options   = array();
		$wpwing_test_endpoints = array();
		$this->my_account      = new MyAccount();
	}

	public function test_add_endpoint_registers_rewrite_endpoint(): void {
		global $wpwing_test_endpoints;
		$this->my_account->add_endpoint();
		$this->assertArrayHasKey( MyAccount::ENDPOINT, $wpwing_test_endpoints );
	}

	public function test_add_query_vars_adds_endpoint(): void {
		$vars = $this->my_account->add_query_vars( array( 'orders' => 'orders' ) );
		$this->assertSame(
			array(
				'orders'          => 'orders',
				'wpwing-wishlist' => 'wpwing-wishlist',
			),
			$vars
		);
	}

	public function test_add_menu_item_places_before_logout(): void {
		$items = array(
			'dashboard'       => 'Dashboard',
			'orders'          => 'Orders',
			'customer-logout' => 'Logout',
		);

		$result = $this->my_account->add_menu_item( $items );
		$keys   = array_keys( $result );

		$this->assertSame(
			array( 'dashboard', 'orders', 'wpwing-wishlist', 'customer-logout' ),
			$keys
		);
		$this->assertSame( 'Wishlist & Waitlist', $result['wpwing-wishlist'] );
	}

	public function test_add_menu_item_appends_when_no_logout_key(): void {
		$items = array(
			'dashboard' => 'Dashboard',
			'orders'    => 'Orders',
		);

		$result = $this->my_account->add_menu_item( $items );
		$this->assertArrayHasKey( 'wpwing-wishlist', $result );
		$this->assertSame( 'Wishlist & Waitlist', $result['wpwing-wishlist'] );
	}

	public function test_add_menu_item_omitted_when_tab_setting_disabled(): void {
		update_option( 'wpwing_wl_enable_my_account_tab', false );
		$items = array(
			'dashboard'       => 'Dashboard',
			'customer-logout' => 'Logout',
		);

		$result = $this->my_account->add_menu_item( $items );
		$this->assertArrayNotHasKey( 'wpwing-wishlist', $result );
	}

	public function test_add_menu_item_omitted_when_both_features_disabled(): void {
		update_option( 'wpwing_wl_enable_wishlist', false );
		update_option( 'wpwing_wl_enable_waitlist', false );
		$items = array(
			'dashboard'       => 'Dashboard',
			'customer-logout' => 'Logout',
		);

		$result = $this->my_account->add_menu_item( $items );
		$this->assertArrayNotHasKey( 'wpwing-wishlist', $result );
	}

	public function test_tab_title_adapts_to_active_features(): void {
		// Both enabled.
		update_option( 'wpwing_wl_enable_wishlist', true );
		update_option( 'wpwing_wl_enable_waitlist', true );
		$this->assertSame( 'Wishlist & Waitlist', $this->my_account->get_tab_title() );

		// Wishlist only.
		update_option( 'wpwing_wl_enable_wishlist', true );
		update_option( 'wpwing_wl_enable_waitlist', false );
		$this->assertSame( 'My Wishlist', $this->my_account->get_tab_title() );

		// Waitlist only.
		update_option( 'wpwing_wl_enable_wishlist', false );
		update_option( 'wpwing_wl_enable_waitlist', true );
		$this->assertSame( 'My Waitlist', $this->my_account->get_tab_title() );
	}
}
