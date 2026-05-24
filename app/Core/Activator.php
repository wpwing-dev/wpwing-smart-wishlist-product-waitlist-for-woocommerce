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
	 * Run on plugin activation: create tables (if needed) and schedule cron.
	 */
	public static function activate(): void {
		if ( Settings::get( 'db_version' ) !== WPWING_WL_VERSION ) {
			self::create_tables();
		}

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
	 * Creates the two custom DB tables via dbDelta.
	 */
	private static function create_tables(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Waitlist — reactive intent, needs status lifecycle tracking.
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
			KEY user_id (user_id)
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
}
