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
	 * Render the Waitlist admin page with product/status filters and paginated entries table.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) );
		}

		global $wpdb;
		$table    = Database::waitlists();
		$per_page = 20;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page              = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_product_id = isset( $_GET['filter_product'] ) ? absint( $_GET['filter_product'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_status     = isset( $_GET['filter_status'] ) && in_array( $_GET['filter_status'], array( 'active', 'notified', 'unsubscribed' ), true )
			? sanitize_key( wp_unslash( $_GET['filter_status'] ) )
			: '';
		$offset            = ( $page - 1 ) * $per_page;

		// Build a reusable WHERE clause from whichever filters are active.
		$where_parts  = array();
		$where_values = array();

		if ( $filter_product_id ) {
			$where_parts[]  = 'product_id = %d';
			$where_values[] = $filter_product_id;
		}

		if ( $filter_status ) {
			$where_parts[]  = 'status = %s';
			$where_values[] = $filter_status;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$where_sql = $where_parts ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';

		if ( $where_values ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` {$where_sql}", $where_values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM `{$table}` {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				array_merge( $where_values, array( $per_page, $offset ) )
			)
		);

		// Distinct products that have waitlist entries (for the product filter dropdown).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$products_in_waitlist = $wpdb->get_col( "SELECT DISTINCT product_id FROM `{$table}` ORDER BY product_id ASC" );

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

			<?php if ( $products_in_waitlist ) : ?>
				<form method="get" style="margin-bottom:1rem;">
					<input type="hidden" name="page" value="wpwing-wl-waitlist" />

					<select name="filter_product">
						<option value=""><?php esc_html_e( 'All products', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></option>
						<?php foreach ( $products_in_waitlist as $pid ) : ?>
							<?php $pobj = wc_get_product( (int) $pid ); ?>
							<?php if ( $pobj ) : ?>
								<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $filter_product_id, (int) $pid ); ?>>
									<?php echo esc_html( $pobj->get_name() ); ?> (#<?php echo (int) $pid; ?>)
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>

					<select name="filter_status">
						<option value=""><?php esc_html_e( 'All statuses', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></option>
						<option value="active" <?php selected( $filter_status, 'active' ); ?>><?php esc_html_e( 'Active', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></option>
						<option value="notified" <?php selected( $filter_status, 'notified' ); ?>><?php esc_html_e( 'Notified', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></option>
						<option value="unsubscribed" <?php selected( $filter_status, 'unsubscribed' ); ?>><?php esc_html_e( 'Unsubscribed', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></option>
					</select>

					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>" />
					<?php if ( $filter_product_id || $filter_status ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwing-wl-waitlist' ) ); ?>" class="button">
							<?php esc_html_e( 'Clear', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
						</a>
					<?php endif; ?>
				</form>
			<?php endif; ?>

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
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
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
