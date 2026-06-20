<?php
/**
 * One-time dismissible welcome notice shown after plugin activation.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Admin;

use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and dismisses the post-activation welcome notice.
 */
class WelcomeNotice {

	private const DISMISS_ACTION = 'wpwing_wl_dismiss_welcome';
	private const OPTION_KEY     = 'wpwing_wl_welcome_notice';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render' ) );
		add_action( 'wp_ajax_' . self::DISMISS_ACTION, array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Render the welcome notice if it has not yet been dismissed.
	 */
	public function render(): void {
		if ( \get_option( self::OPTION_KEY ) !== '1' ) {
			return;
		}

		if ( ! \current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings_url = \admin_url( 'admin.php?page=wpwing-wl-settings' );
		$nonce        = \wp_create_nonce( self::DISMISS_ACTION );

		$wishlist_page_id = Settings::wishlist_page_id();
		$waitlist_page_id = Settings::waitlist_page_id();
		$wishlist_url     = $wishlist_page_id ? \get_permalink( $wishlist_page_id ) : false;
		$waitlist_url     = $waitlist_page_id ? \get_permalink( $waitlist_page_id ) : false;

		$links = array();
		if ( $wishlist_url ) {
			$links[] = '<a href="' . esc_url( $wishlist_url ) . '">' . esc_html__( 'View Wishlist page', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) . '</a>';
		}
		if ( $waitlist_url ) {
			$links[] = '<a href="' . esc_url( $waitlist_url ) . '">' . esc_html__( 'View Waitlist page', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) . '</a>';
		}
		$links[] = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configure settings', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) . '</a>';

		?>
		<div
			class="notice notice-success is-dismissible wpwing-wl-welcome-notice"
			data-nonce="<?php echo esc_attr( $nonce ); ?>"
			data-action="<?php echo esc_attr( self::DISMISS_ACTION ); ?>"
		>
			<p><strong><?php esc_html_e( 'WPWing Smart Wishlist & Waitlist is active!', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></strong></p>
			<p>
				<?php
				// Each $links entry is built with esc_url() + esc_html() above — safe to output.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo implode( ' &middot; ', $links );
				?>
			</p>
		</div>
		<script>
		( function () {
			var notice = document.querySelector( '.wpwing-wl-welcome-notice' );
			if ( ! notice ) return;
			document.addEventListener( 'click', function ( e ) {
				if ( ! e.target || ! e.target.classList.contains( 'notice-dismiss' ) ) return;
				if ( ! e.target.closest( '.wpwing-wl-welcome-notice' ) ) return;
				var fd = new FormData();
				fd.append( 'action', notice.dataset.action );
				fd.append( 'nonce', notice.dataset.nonce );
				fetch( ajaxurl, { method: 'POST', body: fd } ); // eslint-disable-line no-undef
			} );
		}() );
		</script>
		<?php
	}

	/**
	 * AJAX handler: mark the notice as dismissed.
	 */
	public function handle_dismiss(): void {
		\check_ajax_referer( self::DISMISS_ACTION, 'nonce' );

		if ( ! \current_user_can( 'manage_woocommerce' ) ) {
			\wp_die( -1, '', array( 'response' => 403 ) );
		}

		\update_option( self::OPTION_KEY, '0' );
		\wp_die( 1 );
	}
}
