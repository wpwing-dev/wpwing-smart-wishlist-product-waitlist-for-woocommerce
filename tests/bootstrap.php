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

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		global $wpwing_test_options;
		if ( ! is_array( $wpwing_test_options ) ) {
			$wpwing_test_options = array();
		}
		return array_key_exists( $name, $wpwing_test_options ) ? $wpwing_test_options[ $name ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value ) {
		global $wpwing_test_options;
		if ( ! is_array( $wpwing_test_options ) ) {
			$wpwing_test_options = array();
		}
		$wpwing_test_options[ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	function add_option( $name, $value, $deprecated = '', $autoload = 'no' ) {
		global $wpwing_test_options;
		if ( ! is_array( $wpwing_test_options ) ) {
			$wpwing_test_options = array();
		}
		if ( ! array_key_exists( $name, $wpwing_test_options ) ) {
			$wpwing_test_options[ $name ] = $value;
		}
		return true;
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

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
		return $text;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '' ) {
		global $wpwing_test_bloginfo;
		if ( ! is_array( $wpwing_test_bloginfo ) ) {
			$wpwing_test_bloginfo = array();
		}
		return $wpwing_test_bloginfo[ $show ] ?? '';
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		global $wpwing_test_current_user_id;
		return (bool) $wpwing_test_current_user_id;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		global $wpwing_test_current_user_id;
		return (int) $wpwing_test_current_user_id;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( ...$args ) {
		global $wpwing_test_actions;
		if ( ! is_array( $wpwing_test_actions ) ) {
			$wpwing_test_actions = array();
		}
		$wpwing_test_actions[] = $args;
	}
}

if ( ! function_exists( 'get_user_by' ) ) {
	function get_user_by( $field, $value ) {
		global $wpwing_test_users;
		if ( ! is_array( $wpwing_test_users ) ) {
			return false;
		}
		if ( 'email' === $field && isset( $wpwing_test_users[ $value ] ) ) {
			return $wpwing_test_users[ $value ];
		}
		return false;
	}
}

/**
 * Minimal $wpdb double. Doesn't parse SQL — prepare() just returns the query
 * unchanged and get_results()/delete() return whatever the test configured,
 * so tests can exercise the PHP-side data shaping around these calls without
 * a real database.
 */
class FakeWpdb {
	public string $prefix = 'wp_';
	public array $results = array();
	public $delete_return = 0;
	public array $deleted_calls = array();

	public function prepare( $query, ...$args ) {
		return $query;
	}

	public function get_results( $query ) {
		return $this->results;
	}

	public function delete( $table, $where, $format = null ) {
		$this->deleted_calls[] = array(
			'table' => $table,
			'where' => $where,
		);
		return $this->delete_return;
	}
}
