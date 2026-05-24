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
				'emptyWishlist' => __( 'Your wishlist is empty.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
				'networkError'  => __( 'An error occurred. Please try again.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			)
		);
	}

	/**
	 * Returns true on single product pages and pages containing the wishlist shortcode.
	 */
	private function should_enqueue(): bool {
		return \is_product() || $this->is_wishlist_shortcode_page();
	}

	/**
	 * Returns true when the current page's content contains [wpwing_wishlist].
	 */
	private function is_wishlist_shortcode_page(): bool {
		global $post;
		return $post instanceof \WP_Post && \has_shortcode( $post->post_content, 'wpwing_wishlist' );
	}
}
