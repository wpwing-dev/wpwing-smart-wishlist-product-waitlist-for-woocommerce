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
}
