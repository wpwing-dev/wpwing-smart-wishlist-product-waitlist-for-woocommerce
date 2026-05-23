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
		// Phase 2: shared admin menu and asset loader.
		// Phase 3: waitlist engine.
		// Phase 4: wishlist engine.
	}
}
