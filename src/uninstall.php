<?php
/**
 * Fired when the plugin is uninstalled (deleted).
 *
 * Drops both custom tables, removes all plugin options, and cancels all
 * pending Action Scheduler jobs in the wpwing-wl-queue group.
 *
 * @package WPWing\WishlistWaitlist
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// No WordPress API exists for dropping custom plugin tables — direct query is the only option here.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}wpwing_wl_waitlists`" );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}wpwing_wl_wishlists`" );

// Remove plugin options. The Settings class is loaded so the defaults() map
// drives the deletion — adding a new option in code is enough to have it
// cleaned up here, no second edit required.
require_once __DIR__ . '/vendor/autoload.php';
delete_option( 'wpwing_wl_db_version' );
foreach ( array_keys( \WPWing\WishlistWaitlist\Core\Settings::defaults() ) as $wpwing_wl_option_key ) {
	delete_option( \WPWing\WishlistWaitlist\Core\Settings::option_name( $wpwing_wl_option_key ) );
}
unset( $wpwing_wl_option_key );

// Cancel all pending Action Scheduler jobs for this plugin.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( '', array(), 'wpwing-wl-queue' );
}

// Remove the guest cleanup WP-Cron event.
\wp_clear_scheduled_hook( 'wpwing_wl_cleanup_guest_wishlists' );
