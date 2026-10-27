<?php
/**
 * Seed realistic sample data for WordPress.org listing screenshots.
 * Run via: wp --path=/var/www/html eval-file /docker/wordpress/seed-screenshots-data.php --allow-root
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

\WPWing\WishlistWaitlist\Core\Activator::activate();

global $wpdb;

// Ensure E2E fixtures exist first
require_once __DIR__ . '/seed-e2e-fixtures.php';

// Helper to look up products by slug
$wishlist_product = wpwing_e2e_find_product_by_slug( 'e2e-wishlist-product' );
$oos_product      = wpwing_e2e_find_product_by_slug( 'e2e-oos-simple' );
$variable_product = wpwing_e2e_find_product_by_slug( 'e2e-variable' );

if ( ! $wishlist_product || ! $oos_product || ! $variable_product ) {
	WP_CLI::error( 'Could not find required fixture products.' );
}

// Find the Large (out of stock) variation ID
$large_variation_id = 0;
foreach ( $variable_product->get_children() as $child_id ) {
	$variation = wc_get_product( $child_id );
	if ( $variation && 'outofstock' === $variation->get_stock_status() ) {
		$large_variation_id = $child_id;
		break;
	}
}

// Ensure Wishlist Page exists
$wishlist_page = get_page_by_path( 'my-wishlist' );
if ( ! $wishlist_page ) {
	$wishlist_page_id = wp_insert_post(
		array(
			'post_title'   => 'My Wishlist',
			'post_name'    => 'my-wishlist',
			'post_content' => '[wpwing_wishlist]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		)
	);
} else {
	$wishlist_page_id = $wishlist_page->ID;
}
update_option( 'wpwing_wl_wishlist_page_id', (int) $wishlist_page_id );

// Clear existing tables
$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpwing_wl_wishlists" );
$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpwing_wl_waitlists" );
$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpwing_wl_email_logs" );

// Ensure admin user exists (ID 1)
$admin_user = get_user_by( 'login', 'admin' );
$admin_id   = $admin_user ? (int) $admin_user->ID : 1;

// 1. Seed Wishlist (admin has 2 items: one in-stock, one out-of-stock with varied quantities)
$wpdb->insert(
	"{$wpdb->prefix}wpwing_wl_wishlists",
	array(
		'user_id'      => $admin_id,
		'guest_token'  => '',
		'product_id'   => $wishlist_product->get_id(),
		'variation_id' => 0,
		'quantity'     => 1,
		'created_at'   => gmdate( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
	)
);
$wpdb->insert(
	"{$wpdb->prefix}wpwing_wl_wishlists",
	array(
		'user_id'      => $admin_id,
		'guest_token'  => '',
		'product_id'   => $oos_product->get_id(),
		'variation_id' => 0,
		'quantity'     => 2,
		'created_at'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
	)
);

// Add some anonymous guest rows for realistic count in Admin Wishlist
$sample_products = wc_get_products(
	array(
		'limit'   => 5,
		'exclude' => array( $wishlist_product->get_id(), $oos_product->get_id() ),
	)
);
$seed_counts     = array(
	$wishlist_product->get_id() => 14,
	$oos_product->get_id()      => 9,
);
foreach ( $sample_products as $idx => $sp ) {
	$seed_counts[ $sp->get_id() ] = max( 1, 7 - ( $idx * 2 ) );
}

foreach ( $seed_counts as $pid => $count ) {
	for ( $i = 0; $i < $count; $i++ ) {
		$wpdb->insert(
			"{$wpdb->prefix}wpwing_wl_wishlists",
			array(
				'user_id'      => null,
				'guest_token'  => wp_generate_password( 64, false ),
				'product_id'   => $pid,
				'variation_id' => 0,
				'quantity'     => 1,
				'created_at'   => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( $i + 1 ) . ' days' ) ),
			)
		);
	}
}

// 2. Seed Waitlist
$waitlist_rows = array(
	array(
		'product_id'        => $oos_product->get_id(),
		'variation_id'      => 0,
		'user_id'           => null,
		'email'             => 'sophia.miller@example.com',
		'status'            => 'active',
		'unsubscribe_token' => wp_generate_password( 64, false ),
		'created_at'        => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
		'notified_at'       => null,
	),
	array(
		'product_id'        => $variable_product->get_id(),
		'variation_id'      => $large_variation_id,
		'user_id'           => null,
		'email'             => 'liam.johnson@example.com',
		'status'            => 'active',
		'unsubscribe_token' => wp_generate_password( 64, false ),
		'created_at'        => gmdate( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
		'notified_at'       => null,
	),
	array(
		'product_id'        => $oos_product->get_id(),
		'variation_id'      => 0,
		'user_id'           => null,
		'email'             => 'emma.watson@example.com',
		'status'            => 'notified',
		'unsubscribe_token' => wp_generate_password( 64, false ),
		'created_at'        => gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
		'notified_at'       => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
	),
	array(
		'product_id'        => $variable_product->get_id(),
		'variation_id'      => $large_variation_id,
		'user_id'           => null,
		'email'             => 'noah.davis@example.com',
		'status'            => 'active',
		'unsubscribe_token' => wp_generate_password( 64, false ),
		'created_at'        => gmdate( 'Y-m-d H:i:s', strtotime( '-6 hours' ) ),
		'notified_at'       => null,
	),
	array(
		'product_id'        => $oos_product->get_id(),
		'variation_id'      => 0,
		'user_id'           => null,
		'email'             => 'olivia.wilson@example.com',
		'status'            => 'active',
		'unsubscribe_token' => wp_generate_password( 64, false ),
		'created_at'        => gmdate( 'Y-m-d H:i:s', strtotime( '-12 hours' ) ),
		'notified_at'       => null,
	),
);

foreach ( $waitlist_rows as $row ) {
	$wpdb->insert( "{$wpdb->prefix}wpwing_wl_waitlists", $row );
}

// 3. Seed Email Logs
$email_logs = array(
	array(
		'waitlist_id'   => 3,
		'product_id'    => $oos_product->get_id(),
		'variation_id'  => 0,
		'email'         => 'emma.watson@example.com',
		'status'        => 'sent',
		'retry_count'   => 0,
		'error_message' => null,
		'sent_at'       => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
	),
	array(
		'waitlist_id'   => 2,
		'product_id'    => $variable_product->get_id(),
		'variation_id'  => $large_variation_id,
		'email'         => 'oliver.brown@example.com',
		'status'        => 'sent',
		'retry_count'   => 0,
		'error_message' => null,
		'sent_at'       => gmdate( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
	),
	array(
		'waitlist_id'   => 1,
		'product_id'    => $oos_product->get_id(),
		'variation_id'  => 0,
		'email'         => 'lucas.smith@invalid-host.test',
		'status'        => 'failed',
		'retry_count'   => 3,
		'error_message' => 'SMTP Error: Data not accepted - 550 5.1.1 Recipient address rejected: User unknown',
		'sent_at'       => gmdate( 'Y-m-d H:i:s', strtotime( '-8 hours' ) ),
	),
);

foreach ( $email_logs as $log ) {
	$wpdb->insert( "{$wpdb->prefix}wpwing_wl_email_logs", $log );
}

WP_CLI::success( 'Screenshot fixture data seeded successfully.' );

