<?php
/**
 * Waitlist admin page — entries table and CSV export.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Admin;

use WPWing\WishlistWaitlist\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Waitlist admin page — entries table and CSV export.
 */
class AdminWaitlist {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'admin_post_wpwing_wl_export_waitlist', array( $this, 'export_csv' ) );
	}

	/**
	 * Add the Waitlist submenu under the shared WPWing parent menu.
	 */
	public function register_submenu(): void {
		add_submenu_page(
			'wpwing',
			__( 'Waitlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			__( 'Waitlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			'wpwing-wl-waitlist',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the Waitlist admin page with a paginated entries table.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) );
		}

		global $wpdb;
		$table    = Database::waitlists();
		$per_page = 20;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset = ( $page - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM `{$table}` ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$total_pages  = (int) ceil( $total / $per_page );
		$export_nonce = wp_create_nonce( 'wpwing_wl_export_waitlist' );
		$export_url   = add_query_arg(
			array(
				'action' => 'wpwing_wl_export_waitlist',
				'nonce'  => $export_nonce,
			),
			admin_url( 'admin-post.php' )
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Waitlist', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></h1>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">
				<?php esc_html_e( 'Export CSV', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
			</a>
			<hr class="wp-header-end">

			<p>
				<?php
				printf(
					/* translators: %d: total number of waitlist entries */
					esc_html__( 'Total entries: %d', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
					(int) $total
				);
				?>
			</p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Email', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Product', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Signed Up', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Notified', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $entries ) : ?>
						<?php foreach ( $entries as $entry ) : ?>
							<?php
							$lookup_id    = (int) $entry->variation_id ? (int) $entry->variation_id : (int) $entry->product_id;
							$product      = wc_get_product( $lookup_id );
							$product_name = $product ? $product->get_name() : sprintf(
								/* translators: %d: product ID */
								__( 'Product #%d', 'wpwing-wishlist-and-waitlist-for-woocommerce' ),
								$lookup_id
							);
							?>
							<tr>
								<td><?php echo (int) $entry->id; ?></td>
								<td><?php echo esc_html( $entry->email ); ?></td>
								<td>
									<?php if ( $product ) : ?>
										<a href="<?php echo esc_url( get_edit_post_link( $lookup_id ) ); ?>">
											<?php echo esc_html( $product_name ); ?>
										</a>
									<?php else : ?>
										<?php echo esc_html( $product_name ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $entry->status ); ?></td>
								<td><?php echo esc_html( $entry->created_at ); ?></td>
								<td><?php echo $entry->notified_at ? esc_html( $entry->notified_at ) : '—'; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="6"><?php esc_html_e( 'No waitlist entries yet.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'current'   => $page,
									'total'     => $total_pages,
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
								)
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Stream all waitlist entries as a CSV download via admin-post.php.
	 */
	public function export_csv(): void {
		check_admin_referer( 'wpwing_wl_export_waitlist', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export this data.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) );
		}

		global $wpdb;
		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$entries = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY created_at DESC", ARRAY_A );

		$filename = 'wpwing-waitlist-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, array( 'ID', 'Product ID', 'Variation ID', 'Email', 'User ID', 'Status', 'Created At', 'Notified At' ) );

		foreach ( (array) $entries as $row ) {
			fputcsv(
				$output,
				array(
					$row['id'],
					$row['product_id'],
					$row['variation_id'],
					$row['email'],
					$row['user_id'],
					$row['status'],
					$row['created_at'],
					$row['notified_at'],
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}
}
