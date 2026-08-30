<?php
/**
 * Idempotent E2E fixture products for Playwright tests.
 * Run via: wp eval-file docker/wordpress/seed-e2e-fixtures.php --allow-root
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

/**
 * @param string $slug
 * @return WC_Product|false
 */
function wpwing_e2e_find_product_by_slug( string $slug ) {
	$posts = get_posts(
		array(
			'name'        => $slug,
			'post_type'   => 'product',
			'post_status' => 'any',
			'numberposts' => 1,
		)
	);
	return $posts ? wc_get_product( $posts[0]->ID ) : false;
}

// Dedicated category so loop-context tests (shop/archive card rendering) can
// navigate straight to a small, deterministic set of products instead of
// depending on WooCommerce sample-data pagination or sort order — and so the
// page qualifies as is_woocommerce() (a product taxonomy archive), which is
// required for the plugin's JS to be enqueued at all.
$category = get_term_by( 'slug', 'e2e-fixtures', 'product_cat' );
if ( ! $category ) {
	$result   = wp_insert_term( 'E2E Fixtures', 'product_cat', array( 'slug' => 'e2e-fixtures' ) );
	$category = is_wp_error( $result ) ? null : get_term( $result['term_id'], 'product_cat' );
}
$category_ids = $category ? array( $category->term_id ) : array();

// Simple, in-stock — used by wishlist tests.
if ( ! wpwing_e2e_find_product_by_slug( 'e2e-wishlist-product' ) ) {
	$product = new WC_Product_Simple();
	$product->set_name( 'E2E Wishlist Product' );
	$product->set_slug( 'e2e-wishlist-product' );
	$product->set_regular_price( '19.99' );
	$product->set_stock_status( 'instock' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_status( 'publish' );
	$product->set_category_ids( $category_ids );
	$product->save();
	WP_CLI::log( 'Created e2e-wishlist-product' );
}

// Simple, out of stock — used by waitlist tests.
if ( ! wpwing_e2e_find_product_by_slug( 'e2e-oos-simple' ) ) {
	$product = new WC_Product_Simple();
	$product->set_name( 'E2E Out of Stock Simple' );
	$product->set_slug( 'e2e-oos-simple' );
	$product->set_regular_price( '29.99' );
	$product->set_stock_status( 'outofstock' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_status( 'publish' );
	$product->set_category_ids( $category_ids );
	$product->save();
	WP_CLI::log( 'Created e2e-oos-simple' );
}

// Variable — one in-stock variation (Small), one out-of-stock variation (Large).
if ( ! wpwing_e2e_find_product_by_slug( 'e2e-variable' ) ) {
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( 0 );
	$attribute->set_name( 'Size' );
	$attribute->set_options( array( 'Small', 'Large' ) );
	$attribute->set_position( 0 );
	$attribute->set_visible( true );
	$attribute->set_variation( true );

	$product = new WC_Product_Variable();
	$product->set_name( 'E2E Variable Product' );
	$product->set_slug( 'e2e-variable' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_status( 'publish' );
	$product->set_attributes( array( $attribute ) );
	$product_id = $product->save();

	$small = new WC_Product_Variation();
	$small->set_parent_id( $product_id );
	$small->set_attributes( array( 'size' => 'Small' ) );
	$small->set_regular_price( '15.00' );
	$small->set_stock_status( 'instock' );
	$small->set_status( 'publish' );
	$small->save();

	$large = new WC_Product_Variation();
	$large->set_parent_id( $product_id );
	$large->set_attributes( array( 'size' => 'Large' ) );
	$large->set_regular_price( '18.00' );
	$large->set_stock_status( 'outofstock' );
	$large->set_status( 'publish' );
	$large->save();

	WP_CLI::log( 'Created e2e-variable with Small (in stock) / Large (out of stock) variations' );
}

WP_CLI::success( 'E2E fixtures ready.' );
