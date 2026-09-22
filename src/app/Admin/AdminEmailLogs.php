<?php
/**
 * Waitlist email logs admin page — send status, retries, error messages, and log management.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Admin;

use WPWing\WishlistWaitlist\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Waitlist email logs admin page.
 */
class AdminEmailLogs {

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'admin_post_wpwing_wl_delete_email_log', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_wpwing_wl_bulk_delete_email_logs', array( $this, 'handle_bulk_delete' ) );
		add_action( 'admin_post_wpwing_wl_clear_all_email_logs', array( $this, 'handle_clear_all' ) );
	}

	/**
	 * Add the Email Logs submenu under the shared WPWing parent menu.
	 */
	public function register_submenu(): void {
		add_submenu_page(
			'wpwing',
			__( 'Email Logs', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			__( 'Email Logs', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			'wpwing-wl-email-logs',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle single log deletion via admin-post.php.
	 */
	public function handle_delete(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		$log_id = isset( $_REQUEST['log_id'] ) ? absint( $_REQUEST['log_id'] ) : 0;
		check_admin_referer( 'wpwing_wl_delete_log_' . $log_id );

		if ( $log_id ) {
			global $wpdb;
			$table = Database::email_logs();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'id' => $log_id ), array( '%d' ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wpwing-wl-email-logs',
					'deleted' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle bulk deletion of email logs via POST.
	 */
	public function handle_bulk_delete(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_bulk_delete_logs' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$action  = sanitize_text_field( wp_unslash( $_POST['action2'] ?? $_POST['action'] ?? '' ) );
		$log_ids = isset( $_POST['log_ids'] ) && is_array( $_POST['log_ids'] )
			? array_filter( array_map( 'absint', wp_unslash( $_POST['log_ids'] ) ) )
			: array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$count = 0;
		if ( 'delete' === $action && ! empty( $log_ids ) ) {
			global $wpdb;
			$table        = Database::email_logs();
			$placeholders = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"DELETE FROM %i WHERE id IN ({$placeholders})",
					array_merge( array( $table ), $log_ids )
				)
			);
			$count = false !== $deleted ? (int) $deleted : 0;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wpwing-wl-email-logs',
					'deleted' => $count,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Clear all email log entries.
	 */
	public function handle_clear_all(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_clear_all_logs' );

		global $wpdb;
		$table = Database::email_logs();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wpwing-wl-email-logs',
					'cleared' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the Email Logs admin screen.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		global $wpdb;
		$table    = Database::email_logs();
		$per_page = 20;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page          = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$filter_status = isset( $_GET['filter_status'] ) && in_array( $_GET['filter_status'], array( 'sent', 'failed' ), true )
			? sanitize_key( wp_unslash( $_GET['filter_status'] ) )
			: '';
		$search_query  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$offset = ( $page - 1 ) * $per_page;

		$where_clauses = array();
		$where_params  = array( $table );

		if ( '' !== $filter_status ) {
			$where_clauses[] = 'status = %s';
			$where_params[]  = $filter_status;
		}

		if ( '' !== $search_query ) {
			$where_clauses[] = '(email LIKE %s OR product_id = %d)';
			$where_params[]  = '%' . $wpdb->esc_like( $search_query ) . '%';
			$where_params[]  = absint( $search_query );
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		// Count total rows matching filters.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$total = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM %i {$where_sql}",
				$where_params
			)
		);

		// Query paginated rows.
		$query_params = array_merge( $where_params, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$logs = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM %i {$where_sql} ORDER BY sent_at DESC, id DESC LIMIT %d OFFSET %d",
				$query_params
			)
		);

		$total_pages = (int) ceil( $total / $per_page );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Waitlist Email Logs', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h1>
			<hr class="wp-header-end">

			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_GET['deleted'] ) ) :
				$deleted_count = absint( $_GET['deleted'] );
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						printf(
							esc_html(
								/* translators: %d: number of deleted logs */
								_n( '%d log entry deleted.', '%d log entries deleted.', $deleted_count, 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' )
							),
							(int) $deleted_count
						);
						?>
					</p>
				</div>
			<?php elseif ( ! empty( $_GET['cleared'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'All email log entries have been cleared.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
				</div>
			<?php endif; // phpcs:enable WordPress.Security.NonceVerification.Recommended ?>

			<div class="wpwing-logs-toolbar" style="display:flex;justify-content:space-between;align-items:center;margin:1rem 0;flex-wrap:wrap;gap:10px;">
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:flex;gap:8px;align-items:center;">
					<input type="hidden" name="page" value="wpwing-wl-email-logs" />
					<select name="filter_status">
						<option value=""><?php esc_html_e( 'All statuses', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<option value="sent" <?php selected( $filter_status, 'sent' ); ?>><?php esc_html_e( 'Sent', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<option value="failed" <?php selected( $filter_status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
					</select>
					<input
						type="search"
						name="s"
						value="<?php echo esc_attr( $search_query ); ?>"
						placeholder="<?php esc_attr_e( 'Search by email or product ID', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
					/>
					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>" />
					<?php if ( '' !== $filter_status || '' !== $search_query ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwing-wl-email-logs' ) ); ?>" class="button">
							<?php esc_html_e( 'Reset', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
						</a>
					<?php endif; ?>
				</form>

				<?php if ( $total > 0 ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wpwing_wl_clear_all_email_logs" />
						<?php wp_nonce_field( 'wpwing_wl_clear_all_logs' ); ?>
						<input
							type="submit"
							class="button button-secondary"
							value="<?php esc_attr_e( 'Clear All Logs', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
							onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently clear all waitlist email logs?', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ); ?>');"
						/>
					</form>
				<?php endif; ?>
			</div>

			<p>
				<?php
				printf(
					/* translators: %d: total number of logs */
					esc_html__( 'Total logged sends: %d', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
					(int) $total
				);
				?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wpwing_wl_bulk_delete_email_logs" />
				<?php wp_nonce_field( 'wpwing_wl_bulk_delete_logs' ); ?>

				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<label for="bulk-action-selector-top" class="screen-reader-text">
							<?php esc_html_e( 'Select bulk action', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
						</label>
						<select name="action" id="bulk-action-selector-top">
							<option value="-1"><?php esc_html_e( 'Bulk actions', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						</select>
						<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>" />
					</div>
					<?php $this->render_pagination( $page, $total_pages ); ?>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td id="cb" class="manage-column column-cb check-column">
								<label class="screen-reader-text" for="cb-select-all-1">
									<?php esc_html_e( 'Select All', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
								</label>
								<input id="cb-select-all-1" type="checkbox" />
							</td>
							<th scope="col" style="width:160px;"><?php esc_html_e( 'Date / Time', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Recipient Email', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Product', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th scope="col" style="width:100px;"><?php esc_html_e( 'Status', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th scope="col" style="width:80px;"><?php esc_html_e( 'Retries', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Error / Details', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th scope="col" style="width:80px;"><?php esc_html_e( 'Actions', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $logs ) ) : ?>
							<?php foreach ( $logs as $log ) : ?>
								<?php
								$lookup_id    = $log->variation_id ? (int) $log->variation_id : (int) $log->product_id;
								$product      = function_exists( 'wc_get_product' ) ? wc_get_product( $lookup_id ) : null;
								$product_name = $product ? $product->get_name() : sprintf( '#%d', (int) $log->product_id );
								$delete_url   = add_query_arg(
									array(
										'action'   => 'wpwing_wl_delete_email_log',
										'log_id'   => (int) $log->id,
										'_wpnonce' => wp_create_nonce( 'wpwing_wl_delete_log_' . (int) $log->id ),
									),
									admin_url( 'admin-post.php' )
								);
								?>
								<tr>
									<th scope="row" class="check-column">
										<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( (string) $log->id ); ?>">
											<?php
											printf(
												/* translators: %s: email address */
												esc_html__( 'Select log for %s', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
												esc_html( $log->email )
											);
											?>
										</label>
										<input id="cb-select-<?php echo esc_attr( (string) $log->id ); ?>" type="checkbox" name="log_ids[]" value="<?php echo esc_attr( (string) $log->id ); ?>" />
									</th>
									<td><?php echo esc_html( $log->sent_at ); ?></td>
									<td><strong><?php echo esc_html( $log->email ); ?></strong></td>
									<td>
										<?php if ( $product ) : ?>
											<a href="<?php echo esc_url( get_edit_post_link( $lookup_id ) ); ?>">
												<?php echo esc_html( $product_name ); ?>
											</a>
										<?php else : ?>
											<?php echo esc_html( $product_name ); ?>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( 'sent' === $log->status ) : ?>
											<span style="background:#e7f7ed;color:#1e7e34;padding:3px 8px;border-radius:3px;font-weight:600;display:inline-block;">
												<?php esc_html_e( 'Sent', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
											</span>
										<?php else : ?>
											<span style="background:#fbeae5;color:#c92a2a;padding:3px 8px;border-radius:3px;font-weight:600;display:inline-block;">
												<?php esc_html_e( 'Failed', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
											</span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( (string) $log->retry_count ); ?></td>
									<td>
										<?php if ( ! empty( $log->error_message ) ) : ?>
											<span style="color:#c92a2a;font-family:monospace;font-size:12px;">
												<?php echo esc_html( $log->error_message ); ?>
											</span>
										<?php else : ?>
											&mdash;
										<?php endif; ?>
									</td>
									<td>
										<a
											href="<?php echo esc_url( $delete_url ); ?>"
											class="button button-small"
											onclick="return confirm('<?php echo esc_js( __( 'Delete this log entry?', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ); ?>')"
										>
											<?php esc_html_e( 'Delete', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="8"><?php esc_html_e( 'No email logs found.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<div class="tablenav bottom">
					<div class="alignleft actions bulkactions">
						<label for="bulk-action-selector-bottom" class="screen-reader-text">
							<?php esc_html_e( 'Select bulk action', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
						</label>
						<select name="action2" id="bulk-action-selector-bottom">
							<option value="-1"><?php esc_html_e( 'Bulk actions', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						</select>
						<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>" />
					</div>
					<?php $this->render_pagination( $page, $total_pages ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render pagination controls for the table.
	 *
	 * @param int $current_page Current page number.
	 * @param int $total_pages  Total available pages.
	 */
	private function render_pagination( int $current_page, int $total_pages ): void {
		if ( $total_pages <= 1 ) {
			return;
		}
		?>
		<div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $current_page,
						'total'     => $total_pages,
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
					)
				)
			);
			?>
		</div>
		<?php
	}
}
