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
		add_action( 'admin_post_wpwing_wl_resend_notifications', array( $this, 'handle_resend_notifications' ) );
	}

	/**
	 * Add the Waitlist submenu under the shared WPWing parent menu.
	 */
	public function register_submenu(): void {
		add_submenu_page(
			'wpwing',
			__( 'Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			__( 'Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
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
	 * Handle "Resend Notifications" for all active entries of a given product.
	 *
	 * Schedules a fresh Action Scheduler job for each distinct variation (including
	 * variation_id = 0 for simple/parent products) that has active waitlist entries.
	 * If a job is already queued for a given (product_id, variation_id) pair it is
	 * skipped to avoid double-sending; the redirect tells the admin how many were
	 * newly scheduled vs already pending.
	 */
	public function handle_resend_notifications(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

		check_admin_referer( 'wpwing_wl_resend_notifications_' . $product_id );

		$redirect_args = array(
			'page'           => 'wpwing-wl-waitlist',
			'filter_product' => $product_id,
		);

		if ( ! $product_id ) {
			$redirect_args['resend_error'] = 'invalid_product';
			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( ! function_exists( 'as_schedule_single_action' ) || ! function_exists( 'as_has_scheduled_action' ) ) {
			$redirect_args['resend_error'] = 'no_action_scheduler';
			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}

		global $wpdb;
		$table = Database::waitlists();

		// Get distinct variation_ids (including 0 for simple/parent) that have active entries.
		// %i quotes the table name as an identifier — no string interpolation needed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$variation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT variation_id FROM %i WHERE product_id = %d AND status = 'active'",
				$table,
				$product_id
			)
		);

		$scheduled = 0;
		$skipped   = 0;

		foreach ( $variation_ids as $variation_id ) {
			$variation_id = (int) $variation_id;
			$args         = array( $product_id, $variation_id, 0 );

			if ( as_has_scheduled_action( 'wpwing_wl_process_restock_queue', $args, 'wpwing-wl-queue' ) ) {
				++$skipped;
				continue;
			}

			as_schedule_single_action( time(), 'wpwing_wl_process_restock_queue', $args, 'wpwing-wl-queue' );
			++$scheduled;
		}

		$redirect_args['resent']  = $scheduled;
		$redirect_args['skipped'] = $skipped;

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the Waitlist admin page with product/status filters, paginated entries table,
	 * single-row delete action, and bulk-delete support.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
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

		if ( $filter_product_id && $filter_status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE product_id = %d AND status = %s', $table, $filter_product_id, $filter_status )
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE product_id = %d AND status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$table,
					$filter_product_id,
					$filter_status,
					$per_page,
					$offset
				)
			);
		} elseif ( $filter_product_id ) {
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
		} elseif ( $filter_status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE status = %s', $table, $filter_status )
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$table,
					$filter_status,
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

		// Distinct products that have waitlist entries (for the product filter dropdown).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$products_in_waitlist = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT product_id FROM %i ORDER BY product_id ASC', $table ) );

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
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Waitlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h1>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">
				<?php esc_html_e( 'Export CSV', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
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
									'wpwing-smart-wishlist-product-waitlist-for-woocommerce'
								)
							),
							(int) $deleted_count
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['resend_error'] ) ) : ?>
				<div class="notice notice-error is-dismissible">
					<p>
						<?php
						$resend_error = sanitize_key( wp_unslash( $_GET['resend_error'] ) );
						if ( 'no_action_scheduler' === $resend_error ) {
							esc_html_e( 'Action Scheduler is not available. Ensure WooCommerce is active and up to date.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
						} else {
							esc_html_e( 'Invalid product. Could not schedule notifications.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
						}
						?>
					</p>
				</div>
			<?php elseif ( isset( $_GET['resent'] ) ) : ?>
				<?php
				$resent_count  = absint( $_GET['resent'] );
				$skipped_count = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;
				if ( $resent_count > 0 ) :
					?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php
							printf(
								esc_html(
									/* translators: %d: number of scheduled jobs */
									_n(
										'%d notification job scheduled. Active waitlist subscribers will be emailed shortly.',
										'%d notification jobs scheduled. Active waitlist subscribers will be emailed shortly.',
										$resent_count,
										'wpwing-smart-wishlist-product-waitlist-for-woocommerce'
									)
								),
								(int) $resent_count
							);
							?>
						</p>
					</div>
				<?php elseif ( $skipped_count > 0 ) : ?>
					<div class="notice notice-info is-dismissible">
						<p><?php esc_html_e( 'Notification jobs are already queued for this product. No new jobs were added.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
					</div>
				<?php else : ?>
					<div class="notice notice-warning is-dismissible">
						<p><?php esc_html_e( 'No active waitlist entries found for this product. Nothing was scheduled.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>
			<?php // phpcs:enable WordPress.Security.NonceVerification.Recommended ?>


			<?php if ( $products_in_waitlist ) : ?>
				<form method="get" style="margin-bottom:1rem;">
					<input type="hidden" name="page" value="wpwing-wl-waitlist" />

					<select name="filter_product">
						<option value=""><?php esc_html_e( 'All products', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<?php foreach ( $products_in_waitlist as $pid ) : ?>
							<?php $pobj = wc_get_product( (int) $pid ); ?>
							<?php if ( $pobj ) : ?>
								<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $filter_product_id, (int) $pid ); ?>>
									<?php echo esc_html( $pobj->get_name() ); ?> (#<?php echo absint( $pid ); ?>)
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>

					<select name="filter_status">
						<option value=""><?php esc_html_e( 'All statuses', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<option value="active" <?php selected( $filter_status, 'active' ); ?>><?php esc_html_e( 'Active', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<option value="notified" <?php selected( $filter_status, 'notified' ); ?>><?php esc_html_e( 'Notified', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<option value="unsubscribed" <?php selected( $filter_status, 'unsubscribed' ); ?>><?php esc_html_e( 'Unsubscribed', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
					</select>

					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>" />
					<?php if ( $filter_product_id || $filter_status ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwing-wl-waitlist' ) ); ?>" class="button">
							<?php esc_html_e( 'Clear', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
						</a>
					<?php endif; ?>
				</form>
			<?php endif; ?>

			<?php if ( $filter_product_id ) : ?>
				<form
					method="post"
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
					style="display:inline-block;margin-bottom:1rem;"
				>
					<input type="hidden" name="action" value="wpwing_wl_resend_notifications" />
					<input type="hidden" name="product_id" value="<?php echo esc_attr( $filter_product_id ); ?>" />
					<?php wp_nonce_field( 'wpwing_wl_resend_notifications_' . $filter_product_id ); ?>
					<input
						type="submit"
						class="button button-secondary"
						value="<?php esc_attr_e( 'Resend Notifications', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
						onclick="return confirm('<?php echo esc_js( __( 'Schedule restock notification emails for all active waitlist entries of this product?', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ); ?>')"
					/>
				</form>
			<?php endif; ?>

			<p>
				<?php
				printf(
					/* translators: %d: total number of waitlist entries */
					esc_html__( 'Total entries: %d', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
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
							<th><?php esc_html_e( 'ID', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Email', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Product', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Status', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Signed Up', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Notified', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
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
									__( 'Product #%d', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
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
									<td><?php echo absint( $entry->id ); ?></td>
									<td>
										<?php echo esc_html( $entry->email ); ?>
										<div class="row-actions">
											<span class="delete">
												<a
													href="<?php echo esc_url( $delete_url ); ?>"
													class="submitdelete"
													onclick="return confirm('<?php esc_attr_e( 'Delete this entry? This cannot be undone.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>')"
												>
													<?php esc_html_e( 'Delete', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
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
									<td>
									<?php
									if ( $entry->notified_at ) :
										?>
										<?php echo esc_html( $entry->notified_at ); ?>
										<?php
else :
	?>
										&mdash;<?php endif; ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="7"><?php esc_html_e( 'No waitlist entries yet.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></td>
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
					<?php esc_html_e( 'Select bulk action', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
				</label>
				<select name="bulk_action" id="<?php echo esc_attr( $select_id ); ?>">
					<option value=""><?php esc_html_e( '— Bulk actions —', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
				</select>
				<input
					type="submit"
					class="button action"
					value="<?php esc_attr_e( 'Apply', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
					onclick="
						if (this.form.bulk_action.value === 'delete') {
							var checked = document.querySelectorAll('.wpwing-entry-cb:checked');
							if (!checked.length) { alert('<?php echo esc_js( __( 'Please select at least one entry.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ); ?>'); return false; }
							return confirm('<?php echo esc_js( __( 'Delete the selected entries? This cannot be undone.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) ); ?>');
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
			wp_die( esc_html__( 'You do not have permission to export this data.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_export_waitlist', 'nonce' );

		global $wpdb;
		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY created_at DESC', $table ), ARRAY_A );

		$filename = 'wpwing-waitlist-' . gmdate( 'Y-m-d' ) . '.csv';

		// Discard any buffered output so PHP can still send response headers.
		@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, array( 'ID', 'Product ID', 'Product Name', 'Variation ID', 'Variation Name', 'Email', 'User ID', 'Status', 'Created At', 'Notified At' ) );

		$product_cache = array();

		foreach ( (array) $entries as $row ) {
			$product_id   = (int) $row['product_id'];
			$variation_id = (int) $row['variation_id'];

			if ( ! isset( $product_cache[ $product_id ] ) ) {
				$product_cache[ $product_id ] = wc_get_product( $product_id );
			}
			$product      = $product_cache[ $product_id ];
			$product_name = $product ? $product->get_name() : '';

			$variation_name = '';
			if ( $variation_id ) {
				if ( ! isset( $product_cache[ $variation_id ] ) ) {
					$product_cache[ $variation_id ] = wc_get_product( $variation_id );
				}
				$variation      = $product_cache[ $variation_id ];
				$variation_name = $variation ? $variation->get_name() : '';
			}

			// User-controlled string cells are passed through csv_safe_cell() so a
			// signup email like "=cmd|'/c calc'!A1@x.com" cannot trigger formula
			// execution when the CSV is opened in Excel / Google Sheets.
			fputcsv(
				$output,
				array(
					(int) $row['id'],
					$product_id,
					self::csv_safe_cell( $product_name ),
					$variation_id ? $variation_id : '',
					self::csv_safe_cell( $variation_name ),
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
