<?php
/**
 * Option registry — get/set/delete wrappers with wpwing_wl_ prefix.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Option registry — get/set/delete wrappers with wpwing_wl_ prefix.
 */
class Settings {

	/**
	 * Shared option key prefix for all plugin options.
	 */
	private const PREFIX = 'wpwing_wl_';

	/**
	 * Retrieve a plugin option.
	 *
	 * @param string $key      Option key (without prefix).
	 * @param mixed  $fallback Value returned when the option does not exist.
	 * @return mixed
	 */
	public static function get( string $key, mixed $fallback = null ): mixed {
		return \get_option( self::PREFIX . $key, $fallback );
	}

	/**
	 * Persist a plugin option.
	 *
	 * @param string $key   Option key (without prefix).
	 * @param mixed  $value Value to store.
	 */
	public static function set( string $key, mixed $value ): bool {
		return \update_option( self::PREFIX . $key, $value );
	}

	/**
	 * Delete a plugin option.
	 *
	 * @param string $key Option key (without prefix).
	 */
	public static function delete( string $key ): bool {
		return \delete_option( self::PREFIX . $key );
	}
}
