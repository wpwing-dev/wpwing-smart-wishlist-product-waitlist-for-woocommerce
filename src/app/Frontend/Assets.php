<?php
/**
 * Enqueues frontend CSS/JS and injects the shared JS config object.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues frontend CSS/JS and injects the shared JS config object.
 */
class Assets {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue assets only on pages that need them.
	 */
	public function enqueue(): void {
		if ( ! $this->should_enqueue() ) {
			return;
		}

		wp_enqueue_style(
			'wpwing-wl-public',
			WPWING_WL_URL . 'assets/css/wpwing-public.css',
			array(),
			WPWING_WL_VERSION
		);

		wp_enqueue_script(
			'wpwing-wl-waitlist',
			WPWING_WL_URL . 'assets/js/wpwing-waitlist.js',
			array( 'jquery' ),
			WPWING_WL_VERSION,
			true
		);

		wp_enqueue_script(
			'wpwing-wl-wishlist',
			WPWING_WL_URL . 'assets/js/wpwing-wishlist.js',
			array( 'jquery' ),
			WPWING_WL_VERSION,
			true
		);

		wp_localize_script(
			'wpwing-wl-waitlist',
			'wpwingWl',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'waitlistNonce' => wp_create_nonce( 'wpwing_wl_waitlist' ),
				'wishlistNonce' => wp_create_nonce( 'wpwing_wl_wishlist' ),
				'emptyWishlist' => __( 'Your wishlist is empty.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
				'emptyWaitlist' => __( "You're not on the waitlist for any products.", 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
				'networkError'  => __( 'An error occurred. Please try again.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			)
		);
	}

	/**
	 * Returns true on any WooCommerce page (product, shop, categories, archives)
	 * and on pages containing either shortcode.
	 */
	private function should_enqueue(): bool {
		return ( function_exists( 'is_woocommerce' ) && \is_woocommerce() ) || $this->is_shortcode_page();
	}

	/**
	 * Returns true when the current page's content contains [wpwing_wishlist] or [wpwing_waitlist].
	 *
	 * Note: has_shortcode() scans post_content, so it won't detect the shortcode when
	 * a page builder or Full Site Editor stores it outside post_content. In that case
	 * the store owner should enqueue the assets manually via wp_enqueue_scripts.
	 */
	private function is_shortcode_page(): bool {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		return \has_shortcode( $post->post_content, 'wpwing_wishlist' )
			|| \has_shortcode( $post->post_content, 'wpwing_waitlist' );
	}
}
