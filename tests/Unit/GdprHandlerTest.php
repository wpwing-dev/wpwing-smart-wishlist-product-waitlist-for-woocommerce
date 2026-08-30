<?php

use PHPUnit\Framework\TestCase;
use WPWing\WishlistWaitlist\Core\GdprHandler;

class GdprHandlerTest extends TestCase {

	protected function setUp(): void {
		global $wpdb, $wpwing_test_users;
		$wpdb              = new FakeWpdb();
		$wpwing_test_users = array();
	}

	/**
	 * Flatten a GDPR data-group's {name, value} pairs into name => value for
	 * easier assertions.
	 *
	 * @param array<int, array{name: string, value: mixed}> $pairs
	 * @return array<string, mixed>
	 */
	private function pluck( array $pairs ): array {
		$out = array();
		foreach ( $pairs as $pair ) {
			$out[ $pair['name'] ] = $pair['value'];
		}
		return $out;
	}

	public function test_register_exporters_adds_both_callbacks_without_dropping_existing(): void {
		$handler   = new GdprHandler();
		$exporters = $handler->register_exporters( array( 'existing' => array() ) );

		$this->assertArrayHasKey( 'existing', $exporters );
		$this->assertSame( array( $handler, 'export_waitlist' ), $exporters['wpwing-wl-waitlist']['callback'] );
		$this->assertSame( array( $handler, 'export_wishlist' ), $exporters['wpwing-wl-wishlist']['callback'] );
	}

	public function test_register_erasers_adds_both_callbacks_without_dropping_existing(): void {
		$handler = new GdprHandler();
		$erasers = $handler->register_erasers( array( 'existing' => array() ) );

		$this->assertArrayHasKey( 'existing', $erasers );
		$this->assertSame( array( $handler, 'erase_waitlist' ), $erasers['wpwing-wl-waitlist']['callback'] );
		$this->assertSame( array( $handler, 'erase_wishlist' ), $erasers['wpwing-wl-wishlist']['callback'] );
	}

	public function test_export_waitlist_shapes_rows_and_marks_unnotified_entries(): void {
		global $wpdb;
		$wpdb->results = array(
			(object) array(
				'id'           => 5,
				'product_id'   => 12,
				'variation_id' => 0,
				'email'        => 'customer@example.com',
				'status'       => 'active',
				'created_at'   => '2026-01-01 10:00:00',
				'notified_at'  => null,
			),
		);

		$result = ( new GdprHandler() )->export_waitlist( 'customer@example.com' );

		$this->assertTrue( $result['done'] );
		$this->assertCount( 1, $result['data'] );
		$this->assertSame( 'wpwing-wl-waitlist', $result['data'][0]['group_id'] );
		$this->assertSame( 'wpwing-wl-waitlist-5', $result['data'][0]['item_id'] );

		$fields = $this->pluck( $result['data'][0]['data'] );
		$this->assertSame( 12, $fields['Product ID'] );
		$this->assertSame( 0, $fields['Variation ID'] );
		$this->assertSame( 'customer@example.com', $fields['Email'] );
		$this->assertSame( 'active', $fields['Status'] );
		$this->assertSame( '2026-01-01 10:00:00', $fields['Signed up'] );
		$this->assertSame( '—', $fields['Notified'] );
	}

	public function test_export_waitlist_shows_the_notified_timestamp_when_present(): void {
		global $wpdb;
		$wpdb->results = array(
			(object) array(
				'id'           => 6,
				'product_id'   => 12,
				'variation_id' => 0,
				'email'        => 'customer@example.com',
				'status'       => 'notified',
				'created_at'   => '2026-01-01 10:00:00',
				'notified_at'  => '2026-01-05 09:00:00',
			),
		);

		$result = ( new GdprHandler() )->export_waitlist( 'customer@example.com' );
		$fields = $this->pluck( $result['data'][0]['data'] );

		$this->assertSame( '2026-01-05 09:00:00', $fields['Notified'] );
	}

	public function test_export_wishlist_returns_empty_when_no_account_matches_the_email(): void {
		$result = ( new GdprHandler() )->export_wishlist( 'stranger@example.com' );

		$this->assertSame( array( 'data' => array(), 'done' => true ), $result );
	}

	public function test_export_wishlist_shapes_rows_for_a_matched_account(): void {
		global $wpdb, $wpwing_test_users;
		$wpwing_test_users['customer@example.com'] = (object) array( 'ID' => 42 );
		$wpdb->results                              = array(
			(object) array(
				'id'           => 9,
				'product_id'   => 21,
				'variation_id' => 3,
				'created_at'   => '2026-02-01 08:00:00',
			),
		);

		$result = ( new GdprHandler() )->export_wishlist( 'customer@example.com' );

		$this->assertTrue( $result['done'] );
		$this->assertSame( 'wpwing-wl-wishlist', $result['data'][0]['group_id'] );
		$this->assertSame( 'wpwing-wl-wishlist-9', $result['data'][0]['item_id'] );

		$fields = $this->pluck( $result['data'][0]['data'] );
		$this->assertSame( 21, $fields['Product ID'] );
		$this->assertSame( 3, $fields['Variation ID'] );
		$this->assertSame( '2026-02-01 08:00:00', $fields['Saved'] );
	}

	public function test_erase_waitlist_deletes_by_email_and_reports_the_removed_count(): void {
		global $wpdb;
		$wpdb->delete_return = 2;

		$result = ( new GdprHandler() )->erase_waitlist( 'customer@example.com' );

		$this->assertSame(
			array(
				'items_removed'  => 2,
				'items_retained' => 0,
				'messages'       => array(),
				'done'           => true,
			),
			$result
		);
		$this->assertSame( array( 'email' => 'customer@example.com' ), $wpdb->deleted_calls[0]['where'] );
	}

	public function test_erase_wishlist_does_nothing_when_no_account_matches_the_email(): void {
		global $wpdb;

		$result = ( new GdprHandler() )->erase_wishlist( 'stranger@example.com' );

		$this->assertSame( 0, $result['items_removed'] );
		$this->assertTrue( $result['done'] );
		$this->assertSame( array(), $wpdb->deleted_calls );
	}

	public function test_erase_wishlist_deletes_by_user_id_for_a_matched_account(): void {
		global $wpdb, $wpwing_test_users;
		$wpwing_test_users['customer@example.com'] = (object) array( 'ID' => 42 );
		$wpdb->delete_return                        = 4;

		$result = ( new GdprHandler() )->erase_wishlist( 'customer@example.com' );

		$this->assertSame( 4, $result['items_removed'] );
		$this->assertSame( array( 'user_id' => 42 ), $wpdb->deleted_calls[0]['where'] );
	}
}
