<?php
/**
 * Handles plugin activation and deactivation.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation and deactivation tasks.
 */
class Activator {

	/**
	 * Run on plugin activation: create/upgrade tables, schedule cron, seed
	 * factory-default settings on first activation.
	 */
	public static function activate(): void {
		// Always run dbDelta on activation — it is idempotent and will add any
		// missing columns or indexes (e.g. unsubscribe_token index) to existing
		// tables without touching existing data.
		self::create_tables();

		self::seed_default_options();
		self::maybe_create_pages();

		if ( ! \wp_next_scheduled( Cron::CLEANUP_HOOK ) ) {
			\wp_schedule_event( time(), 'weekly', Cron::CLEANUP_HOOK );
		}

		\flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		$timestamp = \wp_next_scheduled( Cron::CLEANUP_HOOK );
		if ( $timestamp ) {
			\wp_unschedule_event( $timestamp, Cron::CLEANUP_HOOK );
		}

		\flush_rewrite_rules();
	}

	/**
	 * Creates (or upgrades, via dbDelta) the two custom DB tables.
	 *
	 * The dbDelta routine is idempotent and additive — it adds missing columns
	 * and indexes but never removes or alters existing ones. Bumping
	 * WPWING_WL_VERSION is what triggers this routine to run again on the next
	 * activation.
	 */
	private static function create_tables(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Waitlist — reactive intent, needs status lifecycle tracking.
		// status_created covers the admin list view's "ORDER BY created_at DESC" plus optional status filter.
		$sql_waitlists = "CREATE TABLE {$wpdb->prefix}wpwing_wl_waitlists (
			id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id        BIGINT UNSIGNED NOT NULL,
			variation_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
			email             VARCHAR(190) NOT NULL,
			user_id           BIGINT UNSIGNED DEFAULT NULL,
			status            VARCHAR(20) NOT NULL DEFAULT 'active',
			unsubscribe_token VARCHAR(64) NOT NULL,
			created_at        DATETIME NOT NULL,
			notified_at       DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY product_var (product_id, variation_id),
			KEY email_status (email, status),
			KEY status_created (status, created_at),
			KEY user_id (user_id),
			KEY unsubscribe_token (unsubscribe_token)
		) ENGINE=InnoDB $charset;";

		// Wishlist — persistent intent, one row per saved item.
		// Two separate UNIQUE keys because MySQL treats NULL as non-unique,
		// so a single key on (user_id, guest_token, product_id, variation_id) would not enforce uniqueness correctly.
		$sql_wishlists = "CREATE TABLE {$wpdb->prefix}wpwing_wl_wishlists (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id       BIGINT UNSIGNED DEFAULT NULL,
			guest_token   VARCHAR(64) DEFAULT NULL,
			product_id    BIGINT UNSIGNED NOT NULL,
			variation_id  BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at    DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY unique_user_item (user_id, product_id, variation_id),
			UNIQUE KEY unique_guest_item (guest_token, product_id, variation_id),
			KEY user_id (user_id),
			KEY guest_token (guest_token)
		) ENGINE=InnoDB $charset;";

		\dbDelta( $sql_waitlists );
		\dbDelta( $sql_wishlists );

		Settings::set( 'db_version', WPWING_WL_VERSION );
	}

	/**
	 * Seed missing merchant-facing options with their factory defaults.
	 * Uses add_option() so existing values on reactivation are preserved.
	 */
	private static function seed_default_options(): void {
		foreach ( Settings::defaults() as $key => $value ) {
			\add_option( Settings::option_name( $key ), $value );
		}
	}

	/**
	 * Create the Wishlist and Waitlist pages if they don't exist, then
	 * store their IDs in settings and queue a one-time welcome notice.
	 * Safe to call on every activation — all checks are idempotent.
	 */
	private static function maybe_create_pages(): void {
		$wishlist_id = Settings::wishlist_page_id();
		if ( ! $wishlist_id || ! \get_post( $wishlist_id ) ) {
			$id = \wp_insert_post(
				array(
					'post_title'   => \__( 'My Wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
					'post_content' => '[wpwing_wishlist]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( $id && ! \is_wp_error( $id ) ) {
				Settings::set( 'wishlist_page_id', $id );
			}
		}

		$waitlist_id = Settings::waitlist_page_id();
		if ( ! $waitlist_id || ! \get_post( $waitlist_id ) ) {
			$id = \wp_insert_post(
				array(
					'post_title'   => \__( 'My Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
					'post_content' => '[wpwing_waitlist]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( $id && ! \is_wp_error( $id ) ) {
				Settings::set( 'waitlist_page_id', $id );
			}
		}

		// Queue the welcome notice. add_option() is a no-op when the option
		// already exists, so a previously dismissed notice stays dismissed.
		\add_option( 'wpwing_wl_welcome_notice', '1' );
	}
}
