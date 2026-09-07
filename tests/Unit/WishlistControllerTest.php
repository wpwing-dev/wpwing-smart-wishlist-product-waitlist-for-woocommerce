<?php

use PHPUnit\Framework\TestCase;
use WPWing\WishlistWaitlist\Wishlist\WishlistController;

class WishlistControllerTest extends TestCase {

	private WishlistController $controller;

	protected function setUp(): void {
		global $wpdb, $wpwing_test_options, $wpwing_test_current_user_id, $wpwing_test_actions;

		$wpdb                         = new FakeWpdb();
		$wpwing_test_options          = array();
		$wpwing_test_current_user_id  = 0;
		$wpwing_test_actions          = array();
		unset( $_COOKIE['wpwing_wl_guest'] );

		$this->controller = new WishlistController();
	}

	public function test_removes_logged_in_users_row_on_add_to_cart(): void {
		global $wpdb, $wpwing_test_current_user_id, $wpwing_test_actions;

		$wpwing_test_current_user_id = 42;
		$wpdb->delete_return          = 1;

		$this->controller->remove_on_add_to_cart( 'key', 7, 1, 0 );

		$this->assertSame(
			array(
				'table' => 'wp_wpwing_wl_wishlists',
				'where' => array(
					'user_id'      => 42,
					'product_id'   => 7,
					'variation_id' => 0,
				),
			),
			$wpdb->deleted_calls[0]
		);
		$this->assertCount( 1, $wpwing_test_actions );
		$this->assertSame( 'wpwing_wl_wishlist_item_removed', $wpwing_test_actions[0][0] );
		$this->assertSame( array( 42, 7, 0 ), array_slice( $wpwing_test_actions[0], 1 ) );
	}

	public function test_removes_guests_row_using_cookie_token_on_add_to_cart(): void {
		global $wpdb, $wpwing_test_actions;

		$token                          = str_repeat( 'a', 64 );
		$_COOKIE['wpwing_wl_guest']      = $token;
		$wpdb->delete_return             = 1;

		$this->controller->remove_on_add_to_cart( 'key', 9, 1, 3 );

		$this->assertSame(
			array(
				'table' => 'wp_wpwing_wl_wishlists',
				'where' => array(
					'guest_token'  => $token,
					'product_id'   => 9,
					'variation_id' => 3,
				),
			),
			$wpdb->deleted_calls[0]
		);
		$this->assertCount( 1, $wpwing_test_actions );
	}

	public function test_does_nothing_for_a_guest_without_a_wishlist_cookie(): void {
		global $wpdb, $wpwing_test_actions;

		$this->controller->remove_on_add_to_cart( 'key', 9, 1, 0 );

		$this->assertCount( 0, $wpdb->deleted_calls );
		$this->assertCount( 0, $wpwing_test_actions );
	}

	public function test_does_nothing_when_wishlist_is_disabled(): void {
		global $wpdb, $wpwing_test_current_user_id, $wpwing_test_options, $wpwing_test_actions;

		$wpwing_test_current_user_id       = 42;
		$wpwing_test_options['wpwing_wl_enable_wishlist'] = false;

		$this->controller->remove_on_add_to_cart( 'key', 7, 1, 0 );

		$this->assertCount( 0, $wpdb->deleted_calls );
		$this->assertCount( 0, $wpwing_test_actions );
	}

	public function test_does_not_fire_the_removed_action_when_no_row_matched(): void {
		global $wpdb, $wpwing_test_current_user_id, $wpwing_test_actions;

		$wpwing_test_current_user_id = 42;
		$wpdb->delete_return          = 0;

		$this->controller->remove_on_add_to_cart( 'key', 7, 1, 0 );

		$this->assertCount( 1, $wpdb->deleted_calls );
		$this->assertCount( 0, $wpwing_test_actions );
	}
}
