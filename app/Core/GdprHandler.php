<?php
/**
 * GDPR / personal data tooling — registers exporters and erasers for both
 * wishlist and waitlist data so the WordPress Tools → Export/Erase Personal
 * Data flow surfaces and removes this plugin's data.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Personal data exporter and eraser registrations.
 */
class GdprHandler {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_erasers' ) );
	}

	/**
	 * Register the wishlist and waitlist exporters.
	 *
	 * @param array $exporters Existing exporter registrations.
	 * @return array
	 */
	public function register_exporters( array $exporters ): array {
		$exporters['wpwing-wl-waitlist'] = array(
			'exporter_friendly_name' => __( 'WPWing Waitlist Signups', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			'callback'               => array( $this, 'export_waitlist' ),
		);
		$exporters['wpwing-wl-wishlist'] = array(
			'exporter_friendly_name' => __( 'WPWing Wishlist Items', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			'callback'               => array( $this, 'export_wishlist' ),
		);
		return $exporters;
	}

	/**
	 * Register the wishlist and waitlist erasers.
	 *
	 * @param array $erasers Existing eraser registrations.
	 * @return array
	 */
	public function register_erasers( array $erasers ): array {
		$erasers['wpwing-wl-waitlist'] = array(
			'eraser_friendly_name' => __( 'WPWing Waitlist Signups', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			'callback'             => array( $this, 'erase_waitlist' ),
		);
		$erasers['wpwing-wl-wishlist'] = array(
			'eraser_friendly_name' => __( 'WPWing Wishlist Items', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			'callback'             => array( $this, 'erase_wishlist' ),
		);
		return $erasers;
	}

	/**
	 * Export all waitlist rows matching the request email.
	 *
	 * @param string $email_address Email address to export.
	 * @param int    $page          Page (unused — all rows fit on page 1 in practice).
	 * @return array{data: array, done: bool}
	 */
	public function export_waitlist( string $email_address, int $page = 1 ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		global $wpdb;
		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, product_id, variation_id, email, status, created_at, notified_at FROM `{$table}` WHERE email = %s",
				$email_address
			)
		);

		$data = array();
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'wpwing-wl-waitlist',
				'group_label' => __( 'Waitlist Signups', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
				'item_id'     => 'wpwing-wl-waitlist-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Product ID', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => (int) $row->product_id,
					),
					array(
						'name'  => __( 'Variation ID', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => (int) $row->variation_id,
					),
					array(
						'name'  => __( 'Email', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => (string) $row->email,
					),
					array(
						'name'  => __( 'Status', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => (string) $row->status,
					),
					array(
						'name'  => __( 'Signed up', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => (string) $row->created_at,
					),
					array(
						'name'  => __( 'Notified', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => $row->notified_at ? (string) $row->notified_at : '—',
					),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * Export all wishlist rows belonging to the user whose account matches
	 * the request email. Guest wishlist rows are not tied to an email and so
	 * are unreachable here — but the cleanup cron eventually removes them.
	 *
	 * @param string $email_address Email address to export.
	 * @param int    $page          Page (unused).
	 * @return array{data: array, done: bool}
	 */
	public function export_wishlist( string $email_address, int $page = 1 ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$user = \get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		global $wpdb;
		$table = Database::wishlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, product_id, variation_id, created_at FROM `{$table}` WHERE user_id = %d",
				(int) $user->ID
			)
		);

		$data = array();
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'wpwing-wl-wishlist',
				'group_label' => __( 'Wishlist Items', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
				'item_id'     => 'wpwing-wl-wishlist-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Product ID', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => (int) $row->product_id,
					),
					array(
						'name'  => __( 'Variation ID', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => (int) $row->variation_id,
					),
					array(
						'name'  => __( 'Saved', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
						'value' => (string) $row->created_at,
					),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * Erase all waitlist rows matching the request email.
	 *
	 * @param string $email_address Email address to erase.
	 * @param int    $page          Page (unused).
	 * @return array{items_removed: int, items_retained: int, messages: array, done: bool}
	 */
	public function erase_waitlist( string $email_address, int $page = 1 ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		global $wpdb;
		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$removed = (int) $wpdb->delete( $table, array( 'email' => $email_address ), array( '%s' ) );

		return array(
			'items_removed'  => $removed,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Erase wishlist rows for the user matching the request email.
	 *
	 * @param string $email_address Email address to erase.
	 * @param int    $page          Page (unused).
	 * @return array{items_removed: int, items_retained: int, messages: array, done: bool}
	 */
	public function erase_wishlist( string $email_address, int $page = 1 ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$user = \get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return array(
				'items_removed'  => 0,
				'items_retained' => 0,
				'messages'       => array(),
				'done'           => true,
			);
		}

		global $wpdb;
		$table = Database::wishlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$removed = (int) $wpdb->delete( $table, array( 'user_id' => (int) $user->ID ), array( '%d' ) );

		return array(
			'items_removed'  => $removed,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
