<?php
/**
 * Wishlist admin page — entries table, delete actions, and CSV export.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Wishlist;

use WPWing\WishlistWaitlist\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist admin page — entries table, delete actions, and CSV export.
 */
class AdminWishlist {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'admin_post_wpwing_wl_export_wishlist', array( $this, 'export_csv' ) );
		add_action( 'admin_post_wpwing_wl_delete_wishlist_entry', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_wpwing_wl_bulk_delete_wishlist', array( $this, 'handle_bulk_delete' ) );
	}

	/**
	 * Add the Wishlist submenu under the shared WPWing parent menu.
	 */
	public function register_submenu(): void {
		add_submenu_page(
			'wpwing',
			__( 'Wishlist', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			__( 'Wishlist', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			'wpwing-wl-wishlist',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle single-entry delete via admin-post.php.
	 */
	public function handle_delete(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wishlist-waitlist-for-woocommerce' ) );
		}

		$entry_id = isset( $_REQUEST['entry_id'] ) ? absint( $_REQUEST['entry_id'] ) : 0;

		check_admin_referer( 'wpwing_wl_delete_wishlist_entry_' . $entry_id );

		if ( $entry_id ) {
			global $wpdb;
			$table = Database::wishlists();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'id' => $entry_id ), array( '%d' ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wpwing-wl-wishlist',
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wishlist-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_bulk_delete_wishlist' );

		$redirect_args = array( 'page' => 'wpwing-wl-wishlist' );
		if ( ! empty( $_POST['filter_product'] ) ) {
			$redirect_args['filter_product'] = absint( $_POST['filter_product'] );
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
			$table        = Database::wishlists();
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery
			$deleted = (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM %i WHERE id IN ({$placeholders})",
					array_merge( array( $table ), $ids )
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery
		}

		$redirect_args['deleted'] = $deleted;
		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the Wishlist admin page with paginated entries, delete actions, and bulk-delete.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-wishlist-waitlist-for-woocommerce' ) );
		}

		global $wpdb;
		$table    = Database::wishlists();
		$per_page = 20;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page              = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$filter_product_id = isset( $_GET['filter_product'] ) ? absint( $_GET['filter_product'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$offset = ( $page - 1 ) * $per_page;

		if ( $filter_product_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE product_id = %d', $table, $filter_product_id )
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE product_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$table,
					$filter_product_id,
					$per_page,
					$offset
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table )
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$table,
					$per_page,
					$offset
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$products_in_wishlist = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT product_id FROM %i ORDER BY product_id ASC', $table ) );

		$total_pages  = (int) ceil( $total / $per_page );
		$export_nonce = wp_create_nonce( 'wpwing_wl_export_wishlist' );
		$export_url   = add_query_arg(
			array(
				'action' => 'wpwing_wl_export_wishlist',
				'nonce'  => $export_nonce,
			),
			admin_url( 'admin-post.php' )
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Wishlist', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></h1>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">
				<?php esc_html_e( 'Export CSV', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?>
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
									'wpwing-wishlist-waitlist-for-woocommerce'
								)
							),
							(int) $deleted_count
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $products_in_wishlist ) : ?>
				<form method="get" style="margin-bottom:1rem;">
					<input type="hidden" name="page" value="wpwing-wl-wishlist" />

					<select name="filter_product">
						<option value=""><?php esc_html_e( 'All products', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></option>
						<?php foreach ( $products_in_wishlist as $pid ) : ?>
							<?php $pobj = wc_get_product( (int) $pid ); ?>
							<?php if ( $pobj ) : ?>
								<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $filter_product_id, (int) $pid ); ?>>
									<?php echo esc_html( $pobj->get_name() ); ?> (#<?php echo absint( $pid ); ?>)
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>

					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?>" />
					<?php if ( $filter_product_id ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwing-wl-wishlist' ) ); ?>" class="button">
							<?php esc_html_e( 'Clear', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?>
						</a>
					<?php endif; ?>
				</form>
			<?php endif; ?>

			<p>
				<?php
				printf(
					/* translators: %d: total number of wishlist entries */
					esc_html__( 'Total entries: %d', 'wpwing-wishlist-waitlist-for-woocommerce' ),
					(int) $total
				);
				?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wpwing_wl_bulk_delete_wishlist" />
				<?php wp_nonce_field( 'wpwing_wl_bulk_delete_wishlist' ); ?>
				<input type="hidden" name="filter_product" value="<?php echo esc_attr( $filter_product_id ); ?>" />
				<input type="hidden" name="paged" value="<?php echo esc_attr( $page ); ?>" />

				<?php $this->render_bulk_actions( 'top' ); ?>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<input
									type="checkbox"
									id="wpwing-wb-select-all"
									onclick="document.querySelectorAll('.wpwing-entry-cb').forEach(function(cb){cb.checked=this.checked;},this)"
								/>
							</td>
							<th><?php esc_html_e( 'ID', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'User', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Product', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Added', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></th>
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
									__( 'Product #%d', 'wpwing-wishlist-waitlist-for-woocommerce' ),
									$lookup_id
								);

								if ( $entry->user_id ) {
									$wp_user    = get_user_by( 'id', (int) $entry->user_id );
									$user_label = $wp_user
										? $wp_user->display_name . ' (#' . (int) $entry->user_id . ')'
										: sprintf(
											/* translators: %d: user ID */
											__( 'User #%d', 'wpwing-wishlist-waitlist-for-woocommerce' ),
											(int) $entry->user_id
										);
									$user_url = $wp_user ? get_edit_user_link( (int) $entry->user_id ) : '';
								} else {
									$user_label = __( 'Guest', 'wpwing-wishlist-waitlist-for-woocommerce' );
									$user_url   = '';
								}

								$delete_url = add_query_arg(
									array(
										'action'   => 'wpwing_wl_delete_wishlist_entry',
										'entry_id' => (int) $entry->id,
										'_wpnonce' => wp_create_nonce( 'wpwing_wl_delete_wishlist_entry_' . (int) $entry->id ),
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
									<td><?php echo absint( $entry->id ); ?></td>
									<td>
										<?php if ( $user_url ) : ?>
											<a href="<?php echo esc_url( $user_url ); ?>"><?php echo esc_html( $user_label ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $user_label ); ?>
										<?php endif; ?>
										<div class="row-actions">
											<span class="delete">
												<a
													href="<?php echo esc_url( $delete_url ); ?>"
													class="submitdelete"
													onclick="return confirm('<?php esc_attr_e( 'Delete this entry? This cannot be undone.', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?>')"
												>
													<?php esc_html_e( 'Delete', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?>
												</a>
											</span>
										</div>
									</td>
									<td>
										<?php if ( $product ) : ?>
											<a href="<?php echo esc_url( (string) get_edit_post_link( $lookup_id ) ); ?>">
												<?php echo esc_html( $product_name ); ?>
											</a>
										<?php else : ?>
											<?php echo esc_html( $product_name ); ?>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $entry->created_at ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5"><?php esc_html_e( 'No wishlist entries yet.', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></td>
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
					<?php esc_html_e( 'Select bulk action', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?>
				</label>
				<select name="bulk_action" id="<?php echo esc_attr( $select_id ); ?>">
					<option value=""><?php esc_html_e( '— Bulk actions —', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?></option>
				</select>
				<input
					type="submit"
					class="button action"
					value="<?php esc_attr_e( 'Apply', 'wpwing-wishlist-waitlist-for-woocommerce' ); ?>"
					onclick="
						if (this.form.bulk_action.value === 'delete') {
							var checked = document.querySelectorAll('.wpwing-entry-cb:checked');
							if (!checked.length) { alert('<?php echo esc_js( __( 'Please select at least one entry.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ); ?>'); return false; }
							return confirm('<?php echo esc_js( __( 'Delete the selected entries? This cannot be undone.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ); ?>');
						}
					"
				/>
			</div>
		</div>
		<?php
	}

	/**
	 * Stream all wishlist entries as a CSV download via admin-post.php.
	 */
	public function export_csv(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export this data.', 'wpwing-wishlist-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_export_wishlist', 'nonce' );

		global $wpdb;
		$table = Database::wishlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY created_at DESC', $table ), ARRAY_A );

		$filename = 'wpwing-wishlist-' . gmdate( 'Y-m-d' ) . '.csv';

		@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, array( 'ID', 'User ID', 'Guest Token', 'Product ID', 'Variation ID', 'Created At' ) );

		foreach ( (array) $entries as $row ) {
			fputcsv(
				$output,
				array(
					(int) $row['id'],
					null !== $row['user_id'] ? (int) $row['user_id'] : '',
					self::csv_safe_cell( (string) $row['guest_token'] ),
					(int) $row['product_id'],
					(int) $row['variation_id'],
					(string) $row['created_at'],
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Defuse CSV formula injection by prefixing dangerous leading characters
	 * with an apostrophe so Excel/Sheets treats the cell as plain text.
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
