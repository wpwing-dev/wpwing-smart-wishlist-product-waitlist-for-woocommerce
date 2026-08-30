<?php
/**
 * Reset plugin data between E2E runs so tests start from a known-empty state.
 * Run via: wp eval-file docker/wordpress/reset-e2e-data.php --allow-root
 *
 * Without this, wishlist/waitlist entries (and admin/e2e-customer's own
 * "already joined" state) accumulate across repeated `make test-e2e` runs,
 * since the wp_data volume persists — breaking row-count assumptions and
 * silently blocking re-signup for the shared test accounts.
 */

global $wpdb;

$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpwing_wl_wishlists" );
$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpwing_wl_waitlists" );

$like = $wpdb->esc_like( 'wpwing_wl_rl_' ) . '%';
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_' . $like,
		'_transient_timeout_' . $like
	)
);

WP_CLI::success( 'E2E plugin data reset.' );
