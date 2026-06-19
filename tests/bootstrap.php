<?php
require_once __DIR__ . '/../src/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

define( 'ABSPATH', '/' );
define( 'WPWING_WL_VERSION', '0.0.0-test' );
define( 'WPWING_WL_MIN_WC', '9.0' );
define( 'WPWING_WL_FILE', __DIR__ . '/../src/wpwing-smart-wishlist-product-waitlist-for-woocommerce.php' );
define( 'WPWING_WL_PATH', __DIR__ . '/../src/' );
define( 'WPWING_WL_URL', 'http://localhost/' );
define( 'WPWING_WL_SLUG', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( (string) $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	function is_email( $email ) {
		return false !== filter_var( (string) $email, FILTER_VALIDATE_EMAIL );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return strip_tags( (string) $data, '<p><a><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6>' );
	}
}
