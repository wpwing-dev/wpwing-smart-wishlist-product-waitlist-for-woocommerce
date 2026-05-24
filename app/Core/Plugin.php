<?php
/**
 * Plugin singleton — boots and wires all controllers.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin singleton — boots and wires all controllers.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns the single instance of this class.
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Register all plugin controllers and hooks.
	 */
	public function boot(): void {
		( new \WPWing\WishlistWaitlist\Core\Cron() )->register();
		( new \WPWing\WishlistWaitlist\Admin\AdminMenu() )->register();
		( new \WPWing\WishlistWaitlist\Frontend\Assets() )->register();
		( new \WPWing\WishlistWaitlist\Waitlist\WaitlistController() )->register();
		( new \WPWing\WishlistWaitlist\Waitlist\FrontendWaitlist() )->register();
		( new \WPWing\WishlistWaitlist\Admin\AdminWaitlist() )->register();
		( new \WPWing\WishlistWaitlist\Wishlist\WishlistController() )->register();
		( new \WPWing\WishlistWaitlist\Wishlist\FrontendWishlist() )->register();
		( new \WPWing\WishlistWaitlist\Wishlist\GuestMergeHandler() )->register();
		( new \WPWing\WishlistWaitlist\Wishlist\AdminWishlist() )->register();
	}
}
