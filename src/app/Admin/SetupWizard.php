<?php
/**
 * Guided first-run setup wizard for the plugin.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Admin;

use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Allows the merchant to enable the feature set, choose a wishlist button style,
 * and pick a wishlist/waitlist page in a three-step guided flow. Steps 2 and 3
 * adapt to (or are skipped based on) which features were enabled in step 1.
 */
class SetupWizard {

	private const PAGE_SLUG = 'wpwing-wl-setup';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
		add_action( 'admin_post_wpwing_wl_setup_save', array( $this, 'handle_submit' ) );
	}

	/**
	 * Whether the given step has anything to show/collect.
	 * Step 2 (button style) only applies when Wishlist is on; step 3 (page
	 * pickers) only applies when at least one of Wishlist/Waitlist is on.
	 *
	 * @param int  $step             Wizard step number (1-3).
	 * @param bool $enable_wishlist  Whether the Wishlist feature is enabled.
	 * @param bool $enable_waitlist  Whether the Waitlist feature is enabled.
	 */
	private function step_applicable( int $step, bool $enable_wishlist, bool $enable_waitlist ): bool {
		if ( 2 === $step ) {
			return $enable_wishlist;
		}
		if ( 3 === $step ) {
			return $enable_wishlist || $enable_waitlist;
		}
		return true;
	}

	/**
	 * First applicable step at or after $from. Returns 4 when none remain,
	 * signalling the wizard is done.
	 *
	 * @param int  $from             Step to start searching forward from.
	 * @param bool $enable_wishlist  Whether the Wishlist feature is enabled.
	 * @param bool $enable_waitlist  Whether the Waitlist feature is enabled.
	 */
	private function next_applicable_step( int $from, bool $enable_wishlist, bool $enable_waitlist ): int {
		for ( $step = $from; $step <= 3; $step++ ) {
			if ( $this->step_applicable( $step, $enable_wishlist, $enable_waitlist ) ) {
				return $step;
			}
		}
		return 4;
	}

	/**
	 * First applicable step at or before $from, falling back to step 1.
	 *
	 * @param int  $from             Step to start searching backward from.
	 * @param bool $enable_wishlist  Whether the Wishlist feature is enabled.
	 * @param bool $enable_waitlist  Whether the Waitlist feature is enabled.
	 */
	private function previous_applicable_step( int $from, bool $enable_wishlist, bool $enable_waitlist ): int {
		for ( $step = $from; $step >= 1; $step-- ) {
			if ( $this->step_applicable( $step, $enable_wishlist, $enable_waitlist ) ) {
				return $step;
			}
		}
		return 1;
	}

	/**
	 * Redirect first-time merchants to the wizard unless it has been completed.
	 */
	public function maybe_redirect(): void {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( Settings::wizard_completed() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && self::PAGE_SLUG === $_GET['page'] ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['action'] ) && str_starts_with( sanitize_key( wp_unslash( $_POST['action'] ) ), 'wpwing_wl_' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && str_starts_with( sanitize_key( wp_unslash( $_GET['action'] ) ), 'wpwing_wl_' ) ) {
			return;
		}
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return;
		}
		if ( function_exists( 'wp_safe_redirect' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=1' ) );
			exit;
		}
	}

	/**
	 * Register the wizard submenu.
	 */
	public function register_submenu(): void {
		add_submenu_page(
			'wpwing',
			__( 'Setup Wizard', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			__( 'Setup Wizard', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Process and persist the current wizard step.
	 */
	public function handle_submit(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_setup' );

		$step = isset( $_POST['step'] ) ? max( 1, min( 3, absint( $_POST['step'] ) ) ) : 1;

		if ( 1 === $step ) {
			Settings::set( 'enable_wishlist', ! empty( $_POST['enable_wishlist'] ) );
			Settings::set( 'enable_waitlist', ! empty( $_POST['enable_waitlist'] ) );
		}

		if ( 2 === $step ) {
			Settings::set( 'wishlist_btn_style', Settings::sanitize_button_style( sanitize_text_field( wp_unslash( $_POST['wishlist_btn_style'] ?? 'default' ) ) ) );
		}

		if ( 3 === $step ) {
			if ( Settings::is_wishlist_enabled() ) {
				$wishlist_page_id = isset( $_POST['wishlist_page_id'] ) ? absint( $_POST['wishlist_page_id'] ) : 0;
				if ( $wishlist_page_id > 0 ) {
					Settings::set( 'wishlist_page_id', $wishlist_page_id );
				}
			}
			if ( Settings::is_waitlist_enabled() ) {
				$waitlist_page_id = isset( $_POST['waitlist_page_id'] ) ? absint( $_POST['waitlist_page_id'] ) : 0;
				if ( $waitlist_page_id > 0 ) {
					Settings::set( 'waitlist_page_id', $waitlist_page_id );
				}
			}
		}

		$next_step = $this->next_applicable_step( $step + 1, Settings::is_wishlist_enabled(), Settings::is_waitlist_enabled() );

		if ( $next_step > 3 ) {
			Settings::set( 'wizard_completed', true );
			wp_safe_redirect( admin_url( 'admin.php?page=wpwing-wl-settings' ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=' . $next_step ) );
		exit;
	}

	/**
	 * Render the setup wizard.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested_step  = isset( $_GET['step'] ) ? max( 1, min( 3, absint( $_GET['step'] ) ) ) : 1;
		$enable_wishlist = Settings::is_wishlist_enabled();
		$enable_waitlist = Settings::is_waitlist_enabled();
		$button_style    = Settings::wishlist_button_style();
		$wishlist_page   = Settings::wishlist_page_id();
		$waitlist_page   = Settings::waitlist_page_id();

		// Skip to the next step with something to show; fall back to step 1
		// if nothing is left (e.g. both features were disabled).
		$step = $this->next_applicable_step( $requested_step, $enable_wishlist, $enable_waitlist );
		if ( $step > 3 ) {
			$step = 1;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Setup Wizard', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'Set up your wishlist and waitlist in a few quick steps.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
			<div class="wpwing-wl-wizard-steps" style="margin: 0 0 20px;">
				<strong>
				<?php
				/* translators: %d: current wizard step number. */
				printf( esc_html__( 'Step %d of 3', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ), absint( $step ) );
				?>
				</strong>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wpwing_wl_setup' ); ?>
				<input type="hidden" name="action" value="wpwing_wl_setup_save" />
				<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />

				<?php if ( 1 === $step ) : ?>
					<h2><?php esc_html_e( 'Enable features', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h2>
					<p><?php esc_html_e( 'Turn on the features you want to use immediately.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
					<label>
						<input type="checkbox" name="enable_wishlist" value="1" <?php checked( $enable_wishlist, true ); ?> />
						<?php esc_html_e( 'Enable Wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="enable_waitlist" value="1" <?php checked( $enable_waitlist, true ); ?> />
						<?php esc_html_e( 'Enable Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
					</label>
				<?php elseif ( 2 === $step ) : ?>
					<h2><?php esc_html_e( 'Choose button style', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h2>
					<p><?php esc_html_e( 'Pick the default wishlist button style used across your store.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
					<select name="wishlist_btn_style">
						<option value="default" <?php selected( $button_style, 'default' ); ?>><?php esc_html_e( 'Default', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<option value="filled" <?php selected( $button_style, 'filled' ); ?>><?php esc_html_e( 'Filled', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<option value="outline" <?php selected( $button_style, 'outline' ); ?>><?php esc_html_e( 'Outline', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<option value="icon" <?php selected( $button_style, 'icon' ); ?>><?php esc_html_e( 'Icon only', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
					</select>
				<?php else : ?>
					<?php if ( $enable_wishlist && $enable_waitlist ) : ?>
						<h2><?php esc_html_e( 'Select your pages', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h2>
						<p><?php esc_html_e( 'Choose the pages that display your public wishlist and waitlist.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
					<?php elseif ( $enable_wishlist ) : ?>
						<h2><?php esc_html_e( 'Select wishlist page', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h2>
						<p><?php esc_html_e( 'Choose the page that displays the public wishlist, or create one and save it here.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
					<?php else : ?>
						<h2><?php esc_html_e( 'Select waitlist page', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h2>
						<p><?php esc_html_e( 'Choose the page that displays active waitlist entries, or create one and save it here.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
					<?php endif; ?>

					<?php if ( $enable_wishlist ) : ?>
						<p>
							<label for="wishlist_page_id"><strong><?php esc_html_e( 'Wishlist page', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></strong></label><br />
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'wishlist_page_id',
									'id'                => 'wishlist_page_id',
									'show_option_none'  => esc_html__( 'Select a page', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
									'option_none_value' => '0',
									'selected'          => absint( $wishlist_page ),
								)
							);
							?>
						</p>
					<?php endif; ?>

					<?php if ( $enable_waitlist ) : ?>
						<p>
							<label for="waitlist_page_id"><strong><?php esc_html_e( 'Waitlist page', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></strong></label><br />
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'waitlist_page_id',
									'id'                => 'waitlist_page_id',
									'show_option_none'  => esc_html__( 'Select a page', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
									'option_none_value' => '0',
									'selected'          => absint( $waitlist_page ),
								)
							);
							?>
						</p>
					<?php endif; ?>
				<?php endif; ?>

				<p class="submit">
					<?php if ( 1 !== $step ) : ?>
						<?php $back_step = $this->previous_applicable_step( $step - 1, $enable_wishlist, $enable_waitlist ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=' . $back_step ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Back', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
						</a>
					<?php endif; ?>
					<button type="submit" class="button button-primary">
						<?php echo 3 === $step ? esc_html__( 'Finish setup', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) : esc_html__( 'Continue', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
