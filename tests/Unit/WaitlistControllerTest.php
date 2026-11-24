<?php

use PHPUnit\Framework\TestCase;
use WPWing\WishlistWaitlist\Core\Settings;
use WPWing\WishlistWaitlist\Waitlist\WaitlistController;

class WaitlistControllerTest extends TestCase {

	private WaitlistController $controller;

	protected function setUp(): void {
		global $wpwing_test_options, $wpwing_test_remote_post_response, $wpwing_test_remote_post_calls, $wpwing_test_filters;

		$wpwing_test_options              = array();
		$wpwing_test_remote_post_response = null;
		$wpwing_test_remote_post_calls    = array();
		$wpwing_test_filters              = array();

		unset( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		unset( $_SERVER['REMOTE_ADDR'] );

		$this->controller = new WaitlistController();
	}

	public function test_verify_captcha_returns_false_when_token_is_empty(): void {
		$this->assertFalse( $this->controller->verify_captcha( '' ) );
		$this->assertFalse( $this->controller->verify_captcha( '   ' ) );
	}

	public function test_verify_captcha_returns_true_when_provider_is_none(): void {
		// Default provider is 'none'
		$this->assertTrue( $this->controller->verify_captcha( 'dummy-token' ) );
	}

	public function test_verify_captcha_recaptcha_v3_success(): void {
		global $wpwing_test_remote_post_response, $wpwing_test_remote_post_calls;

		update_option( 'wpwing_wl_captcha_provider', 'recaptcha_v3' );
		update_option( 'wpwing_wl_captcha_secret_key', 'test-secret-key' );

		$wpwing_test_remote_post_response = array(
			'body' => (string) json_encode(
				array(
					'success' => true,
					'score'   => 0.9,
				)
			),
		);

		$result = $this->controller->verify_captcha( 'valid-token' );

		$this->assertTrue( $result );
		$this->assertCount( 1, $wpwing_test_remote_post_calls );
		$this->assertSame( 'https://www.google.com/recaptcha/api/siteverify', $wpwing_test_remote_post_calls[0]['url'] );
		$this->assertSame( 'test-secret-key', $wpwing_test_remote_post_calls[0]['args']['body']['secret'] );
		$this->assertSame( 'valid-token', $wpwing_test_remote_post_calls[0]['args']['body']['response'] );
	}

	public function test_verify_captcha_recaptcha_v3_low_score_fails(): void {
		global $wpwing_test_remote_post_response;

		update_option( 'wpwing_wl_captcha_provider', 'recaptcha_v3' );
		update_option( 'wpwing_wl_captcha_secret_key', 'test-secret-key' );

		$wpwing_test_remote_post_response = array(
			'body' => (string) json_encode(
				array(
					'success' => true,
					'score'   => 0.2, // Below default 0.5 threshold
				)
			),
		);

		$this->assertFalse( $this->controller->verify_captcha( 'bot-token' ) );
	}

	public function test_verify_captcha_recaptcha_v3_http_error_fails(): void {
		global $wpwing_test_remote_post_response;

		update_option( 'wpwing_wl_captcha_provider', 'recaptcha_v3' );
		update_option( 'wpwing_wl_captcha_secret_key', 'test-secret-key' );

		$wpwing_test_remote_post_response = new WP_Error( 'http_request_failed', 'Connection timed out' );

		$this->assertFalse( $this->controller->verify_captcha( 'some-token' ) );
	}

	public function test_verify_captcha_turnstile_success(): void {
		global $wpwing_test_remote_post_response, $wpwing_test_remote_post_calls;

		update_option( 'wpwing_wl_captcha_provider', 'turnstile' );
		update_option( 'wpwing_wl_captcha_secret_key', 'cf-secret-key' );

		$wpwing_test_remote_post_response = array(
			'body' => (string) json_encode(
				array(
					'success' => true,
				)
			),
		);

		$result = $this->controller->verify_captcha( 'turnstile-token' );

		$this->assertTrue( $result );
		$this->assertCount( 1, $wpwing_test_remote_post_calls );
		$this->assertSame( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', $wpwing_test_remote_post_calls[0]['url'] );
		$this->assertSame( 'cf-secret-key', $wpwing_test_remote_post_calls[0]['args']['body']['secret'] );
		$this->assertSame( 'turnstile-token', $wpwing_test_remote_post_calls[0]['args']['body']['response'] );
	}

	public function test_verify_captcha_turnstile_failure(): void {
		global $wpwing_test_remote_post_response;

		update_option( 'wpwing_wl_captcha_provider', 'turnstile' );
		update_option( 'wpwing_wl_captcha_secret_key', 'cf-secret-key' );

		$wpwing_test_remote_post_response = array(
			'body' => (string) json_encode(
				array(
					'success' => false,
				)
			),
		);

		$this->assertFalse( $this->controller->verify_captcha( 'invalid-turnstile-token' ) );
	}

	public function test_verify_captcha_extracts_cloudflare_client_ip(): void {
		global $wpwing_test_remote_post_response, $wpwing_test_remote_post_calls;

		$_SERVER['HTTP_CF_CONNECTING_IP'] = '203.0.113.50';
		$_SERVER['REMOTE_ADDR']           = '192.0.2.1';

		update_option( 'wpwing_wl_captcha_provider', 'turnstile' );
		update_option( 'wpwing_wl_captcha_secret_key', 'cf-secret' );

		$wpwing_test_remote_post_response = array(
			'body' => (string) json_encode( array( 'success' => true ) ),
		);

		$this->controller->verify_captcha( 'token' );

		$this->assertSame( '203.0.113.50', $wpwing_test_remote_post_calls[0]['args']['body']['remoteip'] );
	}

	public function test_verify_captcha_extracts_first_ip_from_forwarded_for(): void {
		global $wpwing_test_remote_post_response, $wpwing_test_remote_post_calls;

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.22, 10.0.0.1, 172.16.0.2';

		update_option( 'wpwing_wl_captcha_provider', 'turnstile' );
		update_option( 'wpwing_wl_captcha_secret_key', 'cf-secret' );

		$wpwing_test_remote_post_response = array(
			'body' => (string) json_encode( array( 'success' => true ) ),
		);

		$this->controller->verify_captcha( 'token' );

		$this->assertSame( '198.51.100.22', $wpwing_test_remote_post_calls[0]['args']['body']['remoteip'] );
	}

	public function test_process_restock_queue_fair_sending_stops_when_quota_exhausted(): void {
		global $wpdb, $wpwing_test_as_scheduled_actions;

		$wpdb = new FakeWpdb();
		$wpwing_test_as_scheduled_actions = array();

		update_option( 'wpwing_wl_enable_fair_sending', true );

		$entries = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$entries[] = (object) array(
				'id'                => $i,
				'product_id'        => 100,
				'variation_id'      => 0,
				'email'             => "user{$i}@example.test",
				'unsubscribe_token' => "unsub{$i}",
				'created_at'        => '2026-11-24 08:00:00',
			);
		}
		$wpdb->results = $entries;

		$this->controller->process_restock_queue( 100, 0, 0, 5 );

		// Since remaining_quota was 5 and 5 entries were processed, quota became 0 so no next batch should be chained.
		$this->assertEmpty( $wpwing_test_as_scheduled_actions );
	}

	public function test_process_restock_queue_fair_sending_chains_with_remaining_quota_and_throttling(): void {
		global $wpdb, $wpwing_test_as_scheduled_actions;

		$wpdb = new FakeWpdb();
		$wpwing_test_as_scheduled_actions = array();

		update_option( 'wpwing_wl_enable_fair_sending', true );
		update_option( 'wpwing_wl_enable_send_rate_limit', true );
		update_option( 'wpwing_wl_send_rate_limit', 5 );

		$entries = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$entries[] = (object) array(
				'id'                => $i,
				'product_id'        => 100,
				'variation_id'      => 0,
				'email'             => "user{$i}@example.test",
				'unsubscribe_token' => "unsub{$i}",
				'created_at'        => '2026-11-24 08:00:00',
			);
		}
		$wpdb->results = $entries;

		// Quota 12, batch size 5 -> processes 5, new quota 7, chains next batch scheduled 60s in the future.
		$now = time();
		$this->controller->process_restock_queue( 100, 0, 0, 12 );

		$this->assertCount( 1, $wpwing_test_as_scheduled_actions );
		$chained = $wpwing_test_as_scheduled_actions[0];
		$this->assertSame( 'wpwing_wl_process_restock_queue', $chained['hook'] );
		$this->assertGreaterThanOrEqual( $now + 59, $chained['timestamp'] );
		$this->assertSame( array( 100, 0, 5, 7 ), $chained['args'] );
	}

	public function test_get_queue_health_returns_correct_metrics(): void {
		global $wpwing_test_as_get_actions;

		// When no actions are in queue -> status is idle
		$wpwing_test_as_get_actions = array();
		$health = WaitlistController::get_queue_health();
		$this->assertTrue( $health['available'] );
		$this->assertSame( 'idle', $health['status'] );
		$this->assertSame( 0, $health['pending'] );

		// When pending actions exist
		$wpwing_test_as_get_actions = array( (object) array( 'id' => 1 ) );
		$health = WaitlistController::get_queue_health();
		$this->assertTrue( $health['available'] );
		$this->assertSame( 'has_failures', $health['status'] ); // Because mock returns 1 for all queries including failed
	}
}
