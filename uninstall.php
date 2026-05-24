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

// Remove all plugin options.
delete_option( 'wpwing_wl_db_version' );

// Cancel all pending Action Scheduler jobs for this plugin.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( '', array(), 'wpwing-wl-queue' );
}

// Remove the guest cleanup WP-Cron event.
\wp_clear_scheduled_hook( 'wpwing_wl_cleanup_guest_wishlists' );
