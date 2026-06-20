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
			__( 'Wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
			__( 'Wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'wpwing_wl_bulk_delete_wishlist' );

		$redirect_args = array( 'page' => 'wpwing-wl-wishlist' );
		if ( ! empty( $_POST['filter_product'] ) ) {
			$redirect_args['filter_product'] = absint( $_POST['filter_product'] );
		}
		if ( ! empty( $_POST['filter_user'] ) ) {
			$redirect_args['filter_user'] = sanitize_text_field( wp_unslash( $_POST['filter_user'] ) );
		}
		if ( ! empty( $_POST['filter_date_from'] ) ) {
			$redirect_args['filter_date_from'] = sanitize_text_field( wp_unslash( $_POST['filter_date_from'] ) );
		}
		if ( ! empty( $_POST['filter_date_to'] ) ) {
			$redirect_args['filter_date_to'] = sanitize_text_field( wp_unslash( $_POST['filter_date_to'] ) );
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
	 * Build a reusable WHERE clause and its argument list from the active filters.
	 *
	 * $placeholders in the user_id IN(...) clause is built solely from literal '%d'
	 * strings — no caller-controlled data — so it is safe to interpolate, but PHPCS
	 * cannot prove that statically. Callers suppress the warning around prepare().
	 *
	 * @param int        $product_id   Filter by product ID, or 0 for none.
	 * @param int[]|null $user_ids     Filter by user IDs. Null = no filter; empty array = force zero results.
	 * @param string     $date_from    ISO date (Y-m-d) lower bound for created_at, or ''.
	 * @param string     $date_to      ISO date (Y-m-d) upper bound for created_at, or ''.
	 * @return array<string, mixed>
	 */
	private function build_where( int $product_id, ?array $user_ids, string $date_from, string $date_to ): array {
		$clauses = array();
		$args    = array();

		if ( $product_id ) {
			$clauses[] = 'product_id = %d';
			$args[]    = $product_id;
		}

		if ( null !== $user_ids ) {
			if ( empty( $user_ids ) ) {
				$clauses[] = '1 = 0';
			} else {
				$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
				$clauses[]    = "user_id IN ({$placeholders})";
				foreach ( $user_ids as $uid ) {
					$args[] = $uid;
				}
			}
		}

		if ( '' !== $date_from ) {
			$clauses[] = 'created_at >= %s';
			$args[]    = $date_from . ' 00:00:00';
		}

		if ( '' !== $date_to ) {
			$clauses[] = 'created_at <= %s';
			$args[]    = $date_to . ' 23:59:59';
		}

		return array(
			'sql'  => $clauses ? 'WHERE ' . implode( ' AND ', $clauses ) : '',
			'args' => $args,
		);
	}

	/**
	 * Render the Wishlist admin page with paginated entries, delete actions, and bulk-delete.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
		}

		global $wpdb;
		$table    = Database::wishlists();
		$per_page = 20;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page              = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$filter_product_id = isset( $_GET['filter_product'] ) ? absint( $_GET['filter_product'] ) : 0;
		$filter_user_raw   = isset( $_GET['filter_user'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_user'] ) ) : '';
		$filter_date_from  = isset( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : '';
		$filter_date_to    = isset( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Enforce Y-m-d format to prevent unexpected strings reaching the query.
		$filter_date_from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filter_date_from ) ? $filter_date_from : '';
		$filter_date_to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filter_date_to ) ? $filter_date_to : '';

		// Resolve user search string to a list of matching user IDs (null = no filter).
		$filter_user_ids = null;
		if ( '' !== $filter_user_raw ) {
			$matched         = get_users(
				array(
					'search'         => '*' . $filter_user_raw . '*',
					'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
					'fields'         => 'ID',
					'number'         => 100,
				)
			);
			$filter_user_ids = array_map( 'absint', $matched );
		}

		$has_any_filters = $filter_product_id || '' !== $filter_user_raw || '' !== $filter_date_from || '' !== $filter_date_to;
		$offset          = ( $page - 1 ) * $per_page;
		$where           = $this->build_where( $filter_product_id, $filter_user_ids, $filter_date_from, $filter_date_to );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery
		$total   = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i {$where['sql']}",
				array_merge( array( $table ), $where['args'] )
			)
		);
		$entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i {$where['sql']} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				array_merge( array( $table ), $where['args'], array( $per_page, $offset ) )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery

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
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Wishlist', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></h1>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">
				<?php esc_html_e( 'Export CSV', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
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
									'wpwing-smart-wishlist-product-waitlist-for-woocommerce'
								)
							),
							(int) $deleted_count
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<form method="get" style="margin-bottom:1rem;">
				<input type="hidden" name="page" value="wpwing-wl-wishlist" />

				<?php if ( $products_in_wishlist ) : ?>
					<select name="filter_product">
						<option value=""><?php esc_html_e( 'All products', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></option>
						<?php foreach ( $products_in_wishlist as $pid ) : ?>
							<?php $pobj = wc_get_product( (int) $pid ); ?>
							<?php if ( $pobj ) : ?>
								<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $filter_product_id, (int) $pid ); ?>>
									<?php echo esc_html( $pobj->get_name() ); ?> (#<?php echo absint( $pid ); ?>)
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>

				<input
					type="text"
					name="filter_user"
					value="<?php echo esc_attr( $filter_user_raw ); ?>"
					placeholder="<?php esc_attr_e( 'Search user&hellip;', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
					style="width:180px;"
				/>

				<input
					type="date"
					name="filter_date_from"
					value="<?php echo esc_attr( $filter_date_from ); ?>"
					title="<?php esc_attr_e( 'From date', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
				/>
				<span aria-hidden="true">&ndash;</span>
				<input
					type="date"
					name="filter_date_to"
					value="<?php echo esc_attr( $filter_date_to ); ?>"
					title="<?php esc_attr_e( 'To date', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>"
				/>

				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>" />
				<?php if ( $has_any_filters ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpwing-wl-wishlist' ) ); ?>" class="button">
						<?php esc_html_e( 'Clear', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
					</a>
				<?php endif; ?>
			</form>

			<p>
				<?php
				printf(
					/* translators: %d: total number of wishlist entries */
					esc_html__( 'Total entries: %d', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
					(int) $total
				);
				?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wpwing_wl_bulk_delete_wishlist" />
				<?php wp_nonce_field( 'wpwing_wl_bulk_delete_wishlist' ); ?>
				<input type="hidden" name="filter_product" value="<?php echo esc_attr( $filter_product_id ); ?>" />
				<input type="hidden" name="filter_user" value="<?php echo esc_attr( $filter_user_raw ); ?>" />
				<input type="hidden" name="filter_date_from" value="<?php echo esc_attr( $filter_date_from ); ?>" />
				<input type="hidden" name="filter_date_to" value="<?php echo esc_attr( $filter_date_to ); ?>" />
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
							<th><?php esc_html_e( 'ID', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'User', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Product', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Variation', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Added', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( $entries ) : ?>
							<?php foreach ( $entries as $entry ) : ?>
								<?php
								$product_id   = (int) $entry->product_id;
								$variation_id = (int) $entry->variation_id;
								$product      = wc_get_product( $product_id );
								$product_name = $product ? $product->get_name() : sprintf(
									/* translators: %d: product ID */
									__( 'Product #%d', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
									$product_id
								);

								$variation_label = '';
								if ( $variation_id ) {
									$variation = wc_get_product( $variation_id );
									if ( $variation instanceof \WC_Product_Variation ) {
										$attrs = array();
										foreach ( $variation->get_variation_attributes() as $attr_key => $attr_val ) {
											$label   = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ) );
											$attrs[] = $label . ': ' . $attr_val;
										}
										$variation_label = $attrs ? implode( ' / ', $attrs ) : '#' . $variation_id;
									} else {
										$variation_label = '#' . $variation_id;
									}
								}

								if ( $entry->user_id ) {
									$wp_user    = get_user_by( 'id', (int) $entry->user_id );
									$user_label = $wp_user
										? $wp_user->display_name . ' (#' . (int) $entry->user_id . ')'
										: sprintf(
											/* translators: %d: user ID */
											__( 'User #%d', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ),
											(int) $entry->user_id
										);
									$user_url = $wp_user ? get_edit_user_link( (int) $entry->user_id ) : '';
								} else {
									$user_label = __( 'Guest', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' );
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
													onclick="return confirm('<?php esc_attr_e( 'Delete this entry? This cannot be undone.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>')"
												>
													<?php esc_html_e( 'Delete', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?>
												</a>
											</span>
										</div>
									</td>
									<td>
										<?php if ( $product ) : ?>
											<a href="<?php echo esc_url( (string) get_edit_post_link( $product_id ) ); ?>">
												<?php echo esc_html( $product_name ); ?>
											</a>
										<?php else : ?>
											<?php echo esc_html( $product_name ); ?>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( '' !== $variation_label ) : ?>
											<?php echo esc_html( $variation_label ); ?>
										<?php else : ?>
											&mdash;
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $entry->created_at ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No wishlist entries yet.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ); ?></td>
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
	 * Stream all wishlist entries as a CSV download via admin-post.php.
	 */
	public function export_csv(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export this data.', 'wpwing-smart-wishlist-product-waitlist-for-woocommerce' ) );
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

		fputcsv( $output, array( 'ID', 'User ID', 'Guest Token', 'Product ID', 'Product Name', 'Variation ID', 'Variation', 'Created At' ) );

		$product_cache = array();

		foreach ( (array) $entries as $row ) {
			$product_id   = (int) $row['product_id'];
			$variation_id = (int) $row['variation_id'];

			if ( ! isset( $product_cache[ $product_id ] ) ) {
				$product_cache[ $product_id ] = wc_get_product( $product_id );
			}
			$product      = $product_cache[ $product_id ];
			$product_name = $product ? $product->get_name() : '';

			$variation_label = '';
			if ( $variation_id ) {
				if ( ! isset( $product_cache[ $variation_id ] ) ) {
					$product_cache[ $variation_id ] = wc_get_product( $variation_id );
				}
				$variation = $product_cache[ $variation_id ];
				if ( $variation instanceof \WC_Product_Variation ) {
					$attrs = array();
					foreach ( $variation->get_variation_attributes() as $attr_key => $attr_val ) {
						$label   = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ) );
						$attrs[] = $label . ': ' . $attr_val;
					}
					$variation_label = implode( ' / ', array_filter( $attrs ) );
				}
			}

			fputcsv(
				$output,
				array(
					(int) $row['id'],
					null !== $row['user_id'] ? (int) $row['user_id'] : '',
					self::csv_safe_cell( (string) $row['guest_token'] ),
					$product_id,
					self::csv_safe_cell( $product_name ),
					$variation_id ? $variation_id : '',
					self::csv_safe_cell( $variation_label ),
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
