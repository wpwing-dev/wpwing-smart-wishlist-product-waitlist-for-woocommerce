<?php
/**
 * WooCommerce My Account "Wishlist & Waitlist" tab integration.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Frontend;

use WPWing\WishlistWaitlist\Core\Settings;
use WPWing\WishlistWaitlist\Waitlist\FrontendWaitlist;
use WPWing\WishlistWaitlist\Wishlist\FrontendWishlist;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce My Account tab handler.
 */
class MyAccount {

	/**
	 * My Account endpoint slug.
	 */
	public const ENDPOINT = 'wpwing-wishlist';

	/**
	 * Hook into WordPress and WooCommerce.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ), 40 );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( $this, 'endpoint_title' ) );
	}

	/**
	 * Register the rewrite endpoint for My Account.
	 */
	public function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Register endpoint query variable in WooCommerce.
	 *
	 * @param array<string, string> $vars Query variables.
	 * @return array<string, string>
	 */
	public function add_query_vars( array $vars ): array {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Add "Wishlist & Waitlist" tab to WooCommerce My Account navigation.
	 *
	 * @param array<string, string> $items My Account menu items.
	 * @return array<string, string>
	 */
	public function add_menu_item( array $items ): array {
		if ( ! Settings::is_my_account_tab_enabled() ) {
			return $items;
		}

		if ( ! Settings::is_wishlist_enabled() && ! Settings::is_waitlist_enabled() ) {
			return $items;
		}

		$title      = $this->get_tab_title();
		$menu_items = array();

		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$menu_items[ self::ENDPOINT ] = $title;
			}
			$menu_items[ $key ] = $label;
		}

		if ( ! isset( $menu_items[ self::ENDPOINT ] ) ) {
			$menu_items[ self::ENDPOINT ] = $title;
		}

		return $menu_items;
	}

	/**
	 * Filter the document title for the custom endpoint.
	 *
	 * @param string $title Existing title.
	 */
	public function endpoint_title( string $title ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return $this->get_tab_title();
	}

	/**
	 * Determine the tab label based on active features.
	 */
	public function get_tab_title(): string {
		$wishlist_on = Settings::is_wishlist_enabled();
		$waitlist_on = Settings::is_waitlist_enabled();

		if ( $wishlist_on && $waitlist_on ) {
			$title = __( 'Wishlist & Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
		} elseif ( $wishlist_on ) {
			$title = __( 'My Wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
		} else {
			$title = __( 'My Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
		}

		return (string) apply_filters( 'wpwing_wl_my_account_tab_title', $title );
	}

	/**
	 * Render the endpoint content inside My Account.
	 */
	public function render_endpoint(): void {
		$wishlist_on = Settings::is_wishlist_enabled();
		$waitlist_on = Settings::is_waitlist_enabled();

		if ( ! $wishlist_on && ! $waitlist_on ) {
			return;
		}

		$wishlist_html = $wishlist_on ? ( new FrontendWishlist() )->render_shortcode( array() ) : '';
		$waitlist_html = $waitlist_on ? ( new FrontendWaitlist() )->render_shortcode( '' ) : '';

		?>
		<div class="wpwing-my-account-wrap">
			<?php if ( $wishlist_on && $waitlist_on ) : ?>
				<div class="wpwing-my-account-section wpwing-my-account-wishlist">
					<h2><?php esc_html_e( 'My Wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h2>
					<?php echo $wishlist_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="wpwing-my-account-section wpwing-my-account-waitlist">
					<h2><?php esc_html_e( 'My Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h2>
					<?php echo $waitlist_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php elseif ( $wishlist_on ) : ?>
				<div class="wpwing-my-account-section wpwing-my-account-wishlist">
					<?php echo $wishlist_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php else : ?>
				<div class="wpwing-my-account-section wpwing-my-account-waitlist">
					<?php echo $waitlist_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
