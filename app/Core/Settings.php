<?php
/**
 * Option registry — get/set/delete wrappers with wpwing_wl_ prefix, plus
 * typed getters and sanitizers for the merchant-facing settings.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Option registry and settings accessors.
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

	/**
	 * Fully prefixed option name. Used by AdminSettings to register options
	 * with the WordPress Settings API.
	 *
	 * @param string $key Option key (without prefix).
	 */
	public static function option_name( string $key ): string {
		return self::PREFIX . $key;
	}

	/**
	 * Merchant-facing setting keys and their factory defaults. Used by the
	 * settings page to seed missing options on first activation, and by
	 * uninstall.php to delete every option without drift.
	 *
	 * Values are resolved lazily (via the getters below) so that defaults
	 * dependent on site config (admin email, store name) reflect runtime
	 * state rather than activation-time state.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'enable_wishlist'        => true,
			'enable_waitlist'        => true,
			'wishlist_page_id'       => 0,
			'from_name'              => '',
			'from_email'             => '',
			'reply_to'               => '',
			'email_subject_template' => '',
			'email_body_template'    => '',
			'guest_retention_days'   => 30,
		);
	}

	/**
	 * Whether the wishlist half of the plugin is enabled. Defaults to true so
	 * the feature is on out of the box and existing installs upgrade silently.
	 */
	public static function is_wishlist_enabled(): bool {
		return (bool) self::get( 'enable_wishlist', true );
	}

	/**
	 * Whether the waitlist half of the plugin is enabled.
	 */
	public static function is_waitlist_enabled(): bool {
		return (bool) self::get( 'enable_waitlist', true );
	}

	/**
	 * ID of the page that displays the [wpwing_wishlist] shortcode.
	 * Returns 0 when not configured.
	 */
	public static function wishlist_page_id(): int {
		return (int) self::get( 'wishlist_page_id', 0 );
	}

	/**
	 * From-name for restock notifications. Falls back to the WP site title.
	 */
	public static function from_name(): string {
		$value = trim( (string) self::get( 'from_name', '' ) );
		return '' !== $value ? $value : (string) \get_bloginfo( 'name' );
	}

	/**
	 * From-email for restock notifications. Falls back to the WP admin email.
	 */
	public static function from_email(): string {
		$value = trim( (string) self::get( 'from_email', '' ) );
		if ( '' !== $value && \is_email( $value ) ) {
			return $value;
		}
		return (string) \get_option( 'admin_email' );
	}

	/**
	 * Reply-To address for restock notifications. Falls back to the From email.
	 */
	public static function reply_to(): string {
		$value = trim( (string) self::get( 'reply_to', '' ) );
		if ( '' !== $value && \is_email( $value ) ) {
			return $value;
		}
		return self::from_email();
	}

	/**
	 * Subject template for restock notifications.
	 */
	public static function email_subject_template(): string {
		$value = trim( (string) self::get( 'email_subject_template', '' ) );
		if ( '' !== $value ) {
			return $value;
		}
		return \__( '[{store_name}] "{product_name}" is back in stock', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
	}

	/**
	 * HTML body template for restock notifications. The merchant may edit this
	 * in the settings page; values are filtered through wp_kses_post on save.
	 */
	public static function email_body_template(): string {
		$value = (string) self::get( 'email_body_template', '' );
		if ( '' !== trim( $value ) ) {
			return $value;
		}
		// Default template — kept as a single translatable HTML blob so translators
		// can localise the surrounding copy. Placeholders are intentionally outside
		// the translatable string so translators don't accidentally remove them.
		$intro       = \__( 'Good news! "{product_name}" is back in stock.', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
		$shop_now    = \__( 'Shop now', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
		$unsubscribe = \__( 'Unsubscribe from this notification', 'wpwing-wishlist-and-waitlist-for-woocommerce' );
		return '<p>' . $intro . '</p>'
			. '<p><a href="{product_url}">' . $shop_now . ' &rarr;</a></p>'
			. '<p style="font-size:12px;color:#888;"><a href="{unsubscribe_url}">' . $unsubscribe . '</a></p>';
	}

	/**
	 * Guest wishlist retention in days, clamped to a sensible range.
	 */
	public static function guest_retention_days(): int {
		$days = (int) self::get( 'guest_retention_days', 30 );
		if ( $days < 1 ) {
			return 30;
		}
		if ( $days > 3650 ) {
			return 3650;
		}
		return $days;
	}

	/**
	 * Cast any incoming value to a boolean for the Settings API.
	 *
	 * @param mixed $value Raw value from $_POST or programmatic update.
	 */
	public static function sanitize_bool( mixed $value ): bool {
		return (bool) $value;
	}

	/**
	 * Single-line text sanitizer for the Settings API.
	 *
	 * @param mixed $value Raw value from $_POST or programmatic update.
	 */
	public static function sanitize_text( mixed $value ): string {
		return \sanitize_text_field( (string) $value );
	}

	/**
	 * Optional email sanitizer — returns an empty string when the value is
	 * blank or malformed so the typed getters can fall back to a default.
	 *
	 * @param mixed $value Raw value from $_POST or programmatic update.
	 */
	public static function sanitize_optional_email( mixed $value ): string {
		$value = \sanitize_email( (string) $value );
		return \is_email( $value ) ? $value : '';
	}

	/**
	 * HTML body template sanitizer — wp_kses_post permits the markup we want
	 * in email bodies and strips script/style.
	 *
	 * @param mixed $value Raw value from $_POST or programmatic update.
	 */
	public static function sanitize_template_body( mixed $value ): string {
		return \wp_kses_post( (string) $value );
	}

	/**
	 * Retention-days sanitizer — clamps to [1, 3650].
	 *
	 * @param mixed $value Raw value from $_POST or programmatic update.
	 */
	public static function sanitize_retention_days( mixed $value ): int {
		$days = (int) $value;
		if ( $days < 1 ) {
			return 1;
		}
		if ( $days > 3650 ) {
			return 3650;
		}
		return $days;
	}

	// --- Placeholder expansion ---------------------------------------------

	/**
	 * Replace {placeholder} tokens in a template string. Replacement values
	 * are inserted verbatim — callers must pre-escape values that will land
	 * in HTML or URL contexts.
	 *
	 * @param string                $template The template string.
	 * @param array<string, string> $vars     Map of placeholder name => value.
	 */
	public static function expand_placeholders( string $template, array $vars ): string {
		$search  = array();
		$replace = array();
		foreach ( $vars as $name => $value ) {
			$search[]  = '{' . $name . '}';
			$replace[] = (string) $value;
		}
		return str_replace( $search, $replace, $template );
	}
}
