<?php
/**
 * Waitlist admin page — entries table, delete actions, and CSV export.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Admin;

use WPWing\WishlistWaitlist\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Waitlist admin page — entries table, delete actions, and CSV export.
 */
class AdminWaitlist {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'admin_post_wpwing_wl_export_waitlist', array( $this, 'export_csv' ) );
		add_action( 'admin_post_wpwing_wl_delete_waitlist_entry', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_wpwing_wl_bulk_delete_waitlist', array( $this, 'handle_bulk_delete' ) );
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
	 * Handle single-entry delete via admin-post.php.
	 * URL: admin-post.php?action=wpwing_wl_delete_waitlist_entry&entry_id=X&_wpnonce=Y
	 */
	public function handle_delete(): void {
		// Capability is checked before nonce verification so unauthorized callers
		// short-circuit on permission rather than triggering nonce-failure logging.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) );
		}

		$entry_id = isset( $_REQUEST['entry_id'] ) ? absint( $_REQUEST['entry_id'] ) : 0;

		check_admin_referer( 'wpwing_wl_delete_entry_' . $entry_id );

		if ( $entry_id ) {
			global $wpdb;
			$table = Database::waitlists();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'id' => $entry_id ), array( '%d' ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wpwing-wl-waitlist',
					'deleted' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle bulk delete via admin-post.php (POST form).
	 */
	public function handle_bulk_delete(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_bulk_delete' );

		// Build redirect args, preserving active filters and page.
		$redirect_args = array( 'page' => 'wpwing-wl-waitlist' );
		if ( ! empty( $_POST['filter_product'] ) ) {
			$redirect_args['filter_product'] = absint( $_POST['filter_product'] );
		}
		if ( ! empty( $_POST['filter_status'] ) ) {
			$redirect_args['filter_status'] = sanitize_key( wp_unslash( $_POST['filter_status'] ) );
		}
		if ( ! empty( $_POST['paged'] ) && absint( $_POST['paged'] ) > 1 ) {
			$redirect_args['paged'] = absint( $_POST['paged'] );
		}

		$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';

		if ( 'delete' !== $bulk_action ) {
			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}

		$ids = isset( $_POST['entry_ids'] ) ? array_filter( array_map( 'absint', (array) $_POST['entry_ids'] ) ) : array();

		$deleted = 0;
		if ( $ids ) {
			global $wpdb;
			$table        = Database::waitlists();
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			// The $placeholders string is built solely from the literal '%d' and a comma —
			// no caller-controlled data — so this is safe to interpolate, but PHPCS can't
			// statically prove that. Suppress the false positives across the multi-line
			// call with a disable/enable block (a single phpcs:ignore drifts off the SQL
			// line when phpcbf reflows the statement).
			// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery
			$deleted = (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `{$table}` WHERE id IN ({$placeholders})",
					$ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery
		}

		$redirect_args['deleted'] = $deleted;
		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the Waitlist admin page with product/status filters, paginated entries table,
	 * single-row delete action, and bulk-delete support.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) );
		}

		global $wpdb;
		$table    = Database::waitlists();
		$per_page = 20;

		// Filter inputs from $_GET — this is a read-only listing page so no
		// nonce is required; the listing is gated by the manage_woocommerce
		// capability check above.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page              = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$filter_product_id = isset( $_GET['filter_product'] ) ? absint( $_GET['filter_product'] ) : 0;
		$filter_status     = isset( $_GET['filter_status'] ) && in_array( $_GET['filter_status'], array( 'active', 'notified', 'unsubscribed' ), true )
			? sanitize_key( wp_unslash( $_GET['filter_status'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$offset = ( $page - 1 ) * $per_page;

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

		// $where_sql is built only from literal fragments + %s/%d placeholders;
		// $where_values is the matching parameter list. PHPCS can't see through
		// the conditional construction, so the false-positive count/placeholder
		// warnings are silenced across the whole block.
		// phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery
		if ( $where_values ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` {$where_sql}", $where_values ) );
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		}

		$entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				array_merge( $where_values, array( $per_page, $offset ) )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery

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

			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$deleted_count = isset( $_GET['deleted'] ) ? absint( $_GET['deleted'] ) : 0;
			if ( $deleted_count ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						printf(
							esc_html(
								/* translators: %d: number of deleted entries */
								_n(
									'%d entry deleted.',
									'%d entries deleted.',
									$deleted_count,
									'wpwing-wishlist-and-waitlist-for-woocommerce'
								)
							),
							(int) $deleted_count
						);
						?>
					</p>
				</div>
			<?php endif; ?>

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

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wpwing_wl_bulk_delete_waitlist" />
				<?php wp_nonce_field( 'wpwing_wl_bulk_delete' ); ?>
				<input type="hidden" name="filter_product" value="<?php echo esc_attr( $filter_product_id ); ?>" />
				<input type="hidden" name="filter_status" value="<?php echo esc_attr( $filter_status ); ?>" />
				<input type="hidden" name="paged" value="<?php echo esc_attr( $page ); ?>" />

				<?php $this->render_bulk_actions( 'top' ); ?>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<input
									type="checkbox"
									id="wpwing-cb-select-all"
									onclick="document.querySelectorAll('.wpwing-entry-cb').forEach(function(cb){cb.checked=this.checked;},this)"
								/>
							</td>
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
								$delete_url = add_query_arg(
									array(
										'action'   => 'wpwing_wl_delete_waitlist_entry',
										'entry_id' => (int) $entry->id,
										'_wpnonce' => wp_create_nonce( 'wpwing_wl_delete_entry_' . (int) $entry->id ),
									),
									admin_url( 'admin-post.php' )
								);
								?>
								<tr>
									<th class="check-column">
										<input
											type="checkbox"
											class="wpwing-entry-cb"
											name="entry_ids[]"
											value="<?php echo esc_attr( $entry->id ); ?>"
										/>
									</th>
									<td><?php echo (int) $entry->id; ?></td>
									<td>
										<?php echo esc_html( $entry->email ); ?>
										<div class="row-actions">
											<span class="delete">
												<a
													href="<?php echo esc_url( $delete_url ); ?>"
													class="submitdelete"
													onclick="return confirm('<?php esc_attr_e( 'Delete this entry? This cannot be undone.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>')"
												>
													<?php esc_html_e( 'Delete', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
												</a>
											</span>
										</div>
									</td>
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
								<td colspan="7"><?php esc_html_e( 'No waitlist entries yet.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<?php $this->render_bulk_actions( 'bottom' ); ?>
			</form>

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
	 * Output the bulk-actions bar (shared between top and bottom positions).
	 *
	 * @param string $position 'top' or 'bottom'.
	 */
	private function render_bulk_actions( string $position ): void {
		$select_id = 'bulk-action-selector-' . $position;
		?>
		<div class="tablenav <?php echo esc_attr( $position ); ?>">
			<div class="alignleft actions bulkactions">
				<label for="<?php echo esc_attr( $select_id ); ?>" class="screen-reader-text">
					<?php esc_html_e( 'Select bulk action', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>
				</label>
				<select name="bulk_action" id="<?php echo esc_attr( $select_id ); ?>">
					<option value=""><?php esc_html_e( '— Bulk actions —', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?></option>
				</select>
				<input
					type="submit"
					class="button action"
					value="<?php esc_attr_e( 'Apply', 'wpwing-wishlist-and-waitlist-for-woocommerce' ); ?>"
					onclick="
						if (this.form.bulk_action.value === 'delete') {
							var checked = document.querySelectorAll('.wpwing-entry-cb:checked');
							if (!checked.length) { alert('<?php echo esc_js( __( 'Please select at least one entry.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ); ?>'); return false; }
							return confirm('<?php echo esc_js( __( 'Delete the selected entries? This cannot be undone.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) ); ?>');
						}
					"
				/>
			</div>
		</div>
		<?php
	}

	/**
	 * Stream all waitlist entries as a CSV download via admin-post.php.
	 */
	public function export_csv(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export this data.', 'wpwing-wishlist-and-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_export_waitlist', 'nonce' );

		global $wpdb;
		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$entries = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY created_at DESC", ARRAY_A );

		$filename = 'wpwing-waitlist-' . gmdate( 'Y-m-d' ) . '.csv';

		// Discard any buffered output so PHP can still send response headers.
		@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, array( 'ID', 'Product ID', 'Variation ID', 'Email', 'User ID', 'Status', 'Created At', 'Notified At' ) );

		foreach ( (array) $entries as $row ) {
			// User-controlled string cells are passed through csv_safe_cell() so a
			// signup email like "=cmd|'/c calc'!A1@x.com" cannot trigger formula
			// execution when the CSV is opened in Excel / Google Sheets.
			fputcsv(
				$output,
				array(
					(int) $row['id'],
					(int) $row['product_id'],
					(int) $row['variation_id'],
					self::csv_safe_cell( (string) $row['email'] ),
					null !== $row['user_id'] ? (int) $row['user_id'] : '',
					self::csv_safe_cell( (string) $row['status'] ),
					(string) $row['created_at'],
					(string) $row['notified_at'],
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Defuse CSV-formula-injection. Cells whose first character is one of
	 * `= + - @ \t \r` are interpreted as formulas by Excel/Sheets. Prefixing
	 * with a single apostrophe forces plaintext rendering without altering
	 * the visible value.
	 *
	 * @param string $value Raw cell value.
	 */
	private static function csv_safe_cell( string $value ): string {
		if ( '' === $value ) {
			return $value;
		}
		$first = substr( $value, 0, 1 );
		if ( in_array( $first, array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}
}
