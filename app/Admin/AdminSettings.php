<?php
/**
 * Settings page — merchant-facing knobs for feature toggles, email branding,
 * notification templates, and guest wishlist retention.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Admin;

use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Settings submenu and wires every option through the
 * WordPress Settings API so save handling, nonces, and capability checks
 * are inherited from core.
 */
class AdminSettings {

	private const OPTION_GROUP = 'wpwing_wl_settings';
	private const PAGE_SLUG    = 'wpwing-wl-settings';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add the Settings submenu under the shared WPWing parent menu.
	 */
	public function register_submenu(): void {
		add_submenu_page(
			'wpwing',
			__( 'WPWing Settings', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			__( 'Settings', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register every setting via the Settings API. Sanitisers live on Settings
	 * so they're reused by other entry points (programmatic updates, tests).
	 */
	public function register_settings(): void {
		// Feature toggles.
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'enable_wishlist' ),
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( Settings::class, 'sanitize_bool' ),
				'default'           => true,
			)
		);
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'enable_waitlist' ),
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( Settings::class, 'sanitize_bool' ),
				'default'           => true,
			)
		);
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'wishlist_page_id' ),
			array(
				'type'              => 'integer',
				'sanitize_callback' => static function ( $value ): int {
					$id = (int) $value;
					return $id > 0 ? $id : 0;
				},
				'default'           => 0,
			)
		);

		// Email branding.
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'from_name' ),
			array(
				'type'              => 'string',
				'sanitize_callback' => array( Settings::class, 'sanitize_text' ),
				'default'           => '',
			)
		);
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'from_email' ),
			array(
				'type'              => 'string',
				'sanitize_callback' => array( Settings::class, 'sanitize_optional_email' ),
				'default'           => '',
			)
		);
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'reply_to' ),
			array(
				'type'              => 'string',
				'sanitize_callback' => array( Settings::class, 'sanitize_optional_email' ),
				'default'           => '',
			)
		);

		// Notification templates.
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'email_subject_template' ),
			array(
				'type'              => 'string',
				'sanitize_callback' => array( Settings::class, 'sanitize_text' ),
				'default'           => '',
			)
		);
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'email_body_template' ),
			array(
				'type'              => 'string',
				'sanitize_callback' => array( Settings::class, 'sanitize_template_body' ),
				'default'           => '',
			)
		);

		// Retention.
		register_setting(
			self::OPTION_GROUP,
			Settings::option_name( 'guest_retention_days' ),
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( Settings::class, 'sanitize_retention_days' ),
				'default'           => 30,
			)
		);

		add_settings_section(
			'wpwing_wl_features',
			__( 'Features', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Disable a feature to hide its frontend UI. Existing data is preserved.', 'wpwing-wishlist-waitlist-for-woocommerce' ) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field(
			'enable_wishlist',
			__( 'Enable Wishlist', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_checkbox' ),
			self::PAGE_SLUG,
			'wpwing_wl_features',
			array(
				'key'         => 'enable_wishlist',
				'description' => __( 'Show the wishlist toggle button on product pages and the [wpwing_wishlist] shortcode output.', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			)
		);
		add_settings_field(
			'wishlist_page_id',
			__( 'Wishlist Page', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_page_select' ),
			self::PAGE_SLUG,
			'wpwing_wl_features',
			array(
				'key'         => 'wishlist_page_id',
				'description' => __( 'The page that displays your customers\' saved products via the [wpwing_wishlist] shortcode. Used for the nav badge link and count shortcode.', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			)
		);
		add_settings_field(
			'enable_waitlist',
			__( 'Enable Waitlist', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_checkbox' ),
			self::PAGE_SLUG,
			'wpwing_wl_features',
			array(
				'key'         => 'enable_waitlist',
				'description' => __( 'Show the back-in-stock signup form on out-of-stock product pages.', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			)
		);

		add_settings_section(
			'wpwing_wl_email_branding',
			__( 'Notification Email — Branding', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Leave a field blank to fall back to the WordPress site name and admin email.', 'wpwing-wishlist-waitlist-for-woocommerce' ) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field(
			'from_name',
			__( 'From name', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_text' ),
			self::PAGE_SLUG,
			'wpwing_wl_email_branding',
			array(
				'key'         => 'from_name',
				'placeholder' => (string) get_bloginfo( 'name' ),
			)
		);
		add_settings_field(
			'from_email',
			__( 'From email', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_text' ),
			self::PAGE_SLUG,
			'wpwing_wl_email_branding',
			array(
				'key'         => 'from_email',
				'type'        => 'email',
				'placeholder' => (string) get_option( 'admin_email' ),
			)
		);
		add_settings_field(
			'reply_to',
			__( 'Reply-To email', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_text' ),
			self::PAGE_SLUG,
			'wpwing_wl_email_branding',
			array(
				'key'         => 'reply_to',
				'type'        => 'email',
				'placeholder' => (string) get_option( 'admin_email' ),
				'description' => __( 'Where customer replies should go. Defaults to the From email.', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			)
		);

		add_settings_section(
			'wpwing_wl_email_template',
			__( 'Notification Email — Template', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Supported placeholders: {product_name}, {product_url}, {unsubscribe_url}, {store_name}. Leave a field blank to use the default.', 'wpwing-wishlist-waitlist-for-woocommerce' ) . '</p>';
			},
			self::PAGE_SLUG
		);

		add_settings_field(
			'email_subject_template',
			__( 'Subject', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_text' ),
			self::PAGE_SLUG,
			'wpwing_wl_email_template',
			array(
				'key'         => 'email_subject_template',
				'placeholder' => Settings::email_subject_template(),
			)
		);
		add_settings_field(
			'email_body_template',
			__( 'Body (HTML)', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_textarea' ),
			self::PAGE_SLUG,
			'wpwing_wl_email_template',
			array(
				'key'         => 'email_body_template',
				'placeholder' => Settings::email_body_template(),
				'description' => __( 'The HTML body is wrapped in your WooCommerce email header and footer.', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			)
		);

		add_settings_section(
			'wpwing_wl_retention',
			__( 'Data Retention', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'guest_retention_days',
			__( 'Guest wishlist retention (days)', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			array( $this, 'field_number' ),
			self::PAGE_SLUG,
			'wpwing_wl_retention',
			array(
				'key'         => 'guest_retention_days',
				'min'         => 1,
				'max'         => 3650,
				'description' => __( 'Guest wishlist rows older than this are deleted by the weekly cleanup job. Logged-in user wishlists are never auto-deleted.', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			)
		);
	}

	/**
	 * Render the settings page wrapper.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-wishlist-waitlist-for-woocommerce' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WPWing Wishlist & Waitlist Settings', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a single checkbox field.
	 *
	 * @param array $args Field args (key, description).
	 */
	public function field_checkbox( array $args ): void {
		$key   = (string) $args['key'];
		$name  = Settings::option_name( $key );
		$value = (bool) get_option( $name, true );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php if ( ! empty( $args['description'] ) ) : ?>
				<?php echo esc_html( $args['description'] ); ?>
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render a single line text/email field.
	 *
	 * @param array $args Field args (key, type, placeholder, description).
	 */
	public function field_text( array $args ): void {
		$key         = (string) $args['key'];
		$name        = Settings::option_name( $key );
		$type        = isset( $args['type'] ) ? (string) $args['type'] : 'text';
		$placeholder = isset( $args['placeholder'] ) ? (string) $args['placeholder'] : '';
		$value       = (string) get_option( $name, '' );
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			class="regular-text"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render a multiline textarea field.
	 *
	 * @param array $args Field args (key, placeholder, description).
	 */
	public function field_textarea( array $args ): void {
		$key         = (string) $args['key'];
		$name        = Settings::option_name( $key );
		$placeholder = isset( $args['placeholder'] ) ? (string) $args['placeholder'] : '';
		$value       = (string) get_option( $name, '' );
		?>
		<textarea
			name="<?php echo esc_attr( $name ); ?>"
			rows="8"
			class="large-text code"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
		><?php echo esc_textarea( $value ); ?></textarea>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render a bounded integer field.
	 *
	 * @param array $args Field args (key, min, max, description).
	 */
	public function field_number( array $args ): void {
		$key   = (string) $args['key'];
		$name  = Settings::option_name( $key );
		$min   = isset( $args['min'] ) ? (int) $args['min'] : 1;
		$max   = isset( $args['max'] ) ? (int) $args['max'] : 9999;
		$value = (int) get_option( $name, $min );
		?>
		<input
			type="number"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="<?php echo esc_attr( $min ); ?>"
			max="<?php echo esc_attr( $max ); ?>"
			class="small-text"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render a WordPress page-selector dropdown.
	 *
	 * @param array $args Field args (key, description).
	 */
	public function field_page_select( array $args ): void {
		$key       = (string) $args['key'];
		$name      = Settings::option_name( $key );
		$name_attr = sanitize_key( $name );
		$value     = (int) get_option( $name, 0 );

		wp_dropdown_pages(
			array(
				'name'              => esc_attr( $name_attr ),
				'id'                => esc_attr( $name_attr ),
				'selected'          => absint( $value ),
				'show_option_none'  => esc_html__( '— Select a page —', 'wpwing-wishlist-waitlist-for-woocommerce' ),
				'option_none_value' => '0',
			)
		);

		if ( $value > 0 ) {
			$page_url = get_permalink( $value );
			if ( $page_url ) {
				printf(
					' <a href="%s" target="_blank">%s</a>',
					esc_url( $page_url ),
					esc_html__( 'View page', 'wpwing-wishlist-waitlist-for-woocommerce' )
				);
			}
		}

		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}
}
