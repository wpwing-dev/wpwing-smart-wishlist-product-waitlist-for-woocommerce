<?php
/**
 * Waitlist business logic — signup, restock detection, and notification dispatch.
 *
 * @package WPWing\WishlistWaitlist
 */

namespace WPWing\WishlistWaitlist\Waitlist;

use WPWing\WishlistWaitlist\Core\Database;
use WPWing\WishlistWaitlist\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Waitlist business logic — signup, restock detection, and notification dispatch.
 */
class WaitlistController {

	/**
	 * Emails sent per Action Scheduler job; next batch is chained if more remain.
	 */
	const BATCH_SIZE = 50;

	/**
	 * Per-IP signup attempts permitted per hour. Prevents an attacker from
	 * spam-signing-up a victim's product to flood the waitlists table.
	 */
	const IP_LIMIT_PER_HOUR = 30;

	/**
	 * Per-email signup attempts permitted per hour, across all products.
	 */
	const EMAIL_LIMIT_PER_HOUR = 5;

	/**
	 * Hook into WordPress and WooCommerce.
	 */
	public function register(): void {
		add_action( 'woocommerce_product_set_stock', array( $this, 'on_stock_changed' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'on_stock_changed' ) );
		add_action( 'wpwing_wl_process_restock_queue', array( $this, 'process_restock_queue' ), 10, 3 );
		add_action( 'wp_ajax_wpwing_wl_join_waitlist', array( $this, 'ajax_join_waitlist' ) );
		add_action( 'wp_ajax_nopriv_wpwing_wl_join_waitlist', array( $this, 'ajax_join_waitlist' ) );
		add_action( 'wp_ajax_wpwing_wl_leave_waitlist', array( $this, 'ajax_leave_waitlist' ) );
		add_action( 'wp_ajax_nopriv_wpwing_wl_leave_waitlist', array( $this, 'ajax_leave_waitlist' ) );
	}

	/**
	 * When a product's stock quantity changes, schedule a restock job if stock
	 * has just become available.
	 *
	 * @param \WC_Product $product The product whose stock changed.
	 */
	public function on_stock_changed( \WC_Product $product ): void {
		do_action( 'wpwing_wl_product_stock_changed', $product );

		if ( ! $product->is_in_stock() || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		$is_variation = $product->is_type( 'variation' );
		$product_id   = $is_variation ? $product->get_parent_id() : $product->get_id();
		$variation_id = $is_variation ? $product->get_id() : 0;
		$args         = array( $product_id, $variation_id, 0 ); // third arg is the last id processed; 0 means start from the beginning.

		if ( as_has_scheduled_action( 'wpwing_wl_process_restock_queue', $args, 'wpwing-wl-queue' ) ) {
			return;
		}

		as_schedule_single_action( time(), 'wpwing_wl_process_restock_queue', $args, 'wpwing-wl-queue' );
	}

	/**
	 * Action Scheduler callback: send one batch of restock emails and chain the
	 * next batch if more active entries remain.
	 *
	 * Pagination is keyset-based (id > last_id) rather than OFFSET so that
	 * batches stay O(BATCH_SIZE) on the index regardless of waitlist depth.
	 *
	 * @param int $product_id   Parent product ID.
	 * @param int $variation_id Variation ID, or 0 for simple products.
	 * @param int $last_id      Highest waitlist row id processed by the previous batch (0 for first batch).
	 */
	public function process_restock_queue( int $product_id, int $variation_id = 0, int $last_id = 0 ): void {
		global $wpdb;

		$table = Database::waitlists();

		do_action( 'wpwing_wl_before_process_restock_queue', $product_id, $variation_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$db_entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE product_id = %d AND variation_id = %d AND status = 'active' AND id > %d ORDER BY id ASC LIMIT %d",
				$table,
				$product_id,
				$variation_id,
				$last_id,
				self::BATCH_SIZE
			)
		);

		// Capture the raw DB count before the filter runs. The filter may remove entries
		// (e.g. deduplication, suppression lists), which would shrink the count below
		// BATCH_SIZE and falsely prevent the next batch from being chained.
		$raw_count = count( (array) $db_entries );

		// Highest id we saw in the DB result — used as the keyset cursor for the next batch.
		$next_last_id = $raw_count > 0 ? (int) end( $db_entries )->id : $last_id;
		reset( $db_entries );

		$entries = (array) apply_filters( 'wpwing_wl_restock_queue_entries', $db_entries, $product_id, $variation_id );

		foreach ( $entries as $entry ) {
			$this->send_restock_email( $entry );
		}

		// Chain the next batch based on how many rows the DB returned, not how many
		// survived the filter — a filter that drops entries must not truncate processing.
		if ( self::BATCH_SIZE === $raw_count ) {
			as_schedule_single_action(
				time(),
				'wpwing_wl_process_restock_queue',
				array( $product_id, $variation_id, $next_last_id ),
				'wpwing-wl-queue'
			);
		}

		do_action( 'wpwing_wl_after_process_restock_queue', $product_id, $variation_id, $entries );
	}

	/**
	 * Send one restock notification email (HTML, wrapped in WC email template)
	 * and mark the entry as notified.
	 *
	 * Returns true on success, false on failure. Failures are logged but do not
	 * throw — the caller loop must continue to remaining entries rather than
	 * aborting the entire batch on a single bad address or transient SMTP error.
	 *
	 * @param object $entry Waitlist DB row.
	 * @return bool Whether the email was sent and the entry marked notified.
	 */
	private function send_restock_email( object $entry ): bool {
		global $wpdb;

		$lookup_id = (int) $entry->variation_id ? (int) $entry->variation_id : (int) $entry->product_id;
		$product   = wc_get_product( $lookup_id );
		if ( ! $product ) {
			$table = Database::waitlists();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'status' => 'product_deleted' ),
				array( 'id' => (int) $entry->id ),
				array( '%s' ),
				array( '%d' )
			);
			return false;
		}

		// Re-check stock at send time — the product may have gone out of stock
		// again between when the AS job was scheduled and when it actually runs.
		// Leave the entry active so it is notified on the next restock instead.
		if ( ! $product->is_in_stock() ) {
			return false;
		}

		$unsubscribe_url = add_query_arg(
			array(
				'wpwing_action' => 'unsubscribe',
				'token'         => $entry->unsubscribe_token,
			),
			home_url( '/' )
		);

		$placeholders = array(
			'product_name'    => $product->get_name(),
			'product_url'     => $product->get_permalink(),
			'unsubscribe_url' => $unsubscribe_url,
			'store_name'      => (string) get_bloginfo( 'name' ),
		);

		$subject = Settings::expand_placeholders( Settings::email_subject_template(), $placeholders );

		// Heading is not template-customisable for v1.1 — kept simple and translatable.
		$heading = sprintf(
			/* translators: %s: product name */
			__( '"%s" is back in stock', 'wpwing-wishlist-waitlist-for-woocommerce' ),
			$product->get_name()
		);

		// Pre-escape placeholder values that will land in HTML contexts. The body
		// template itself is wp_kses_post-sanitised on save so the template author
		// can use HTML; values substituted into the template must be safe too.
		$html_placeholders = array(
			'product_name'    => esc_html( $placeholders['product_name'] ),
			'product_url'     => esc_url( $placeholders['product_url'] ),
			'unsubscribe_url' => esc_url( $placeholders['unsubscribe_url'] ),
			'store_name'      => esc_html( $placeholders['store_name'] ),
		);
		$default_message   = Settings::expand_placeholders( Settings::email_body_template(), $html_placeholders );

		$message = (string) apply_filters( 'wpwing_wl_restock_email_message', $default_message, $entry, $product );

		// Wrap in WooCommerce email header/footer for consistent store branding.
		ob_start();
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $heading ) );
		echo wp_kses_post( $message );
		wc_get_template( 'emails/email-footer.php' );
		$body = (string) ob_get_clean();

		do_action( 'wpwing_wl_before_send_restock_email', $entry, $product );

		// Per-message From/Reply-To via short-lived filters: we install them just
		// for this wp_mail() call and remove them again so we don't bleed into
		// other plugin emails (order receipts, password resets, etc.).
		$from_name  = Settings::from_name();
		$from_email = Settings::from_email();
		$reply_to   = Settings::reply_to();

		$set_from_name  = static function () use ( $from_name ) {
			return $from_name;
		};
		$set_from_email = static function () use ( $from_email ) {
			return $from_email;
		};
		add_filter( 'wp_mail_from_name', $set_from_name );
		add_filter( 'wp_mail_from', $set_from_email );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . $reply_to,
			// RFC 8058 one-click unsubscribe — surfaces the "Unsubscribe" link in
			// Gmail/Outlook header chrome and is now a Yahoo/Gmail bulk-sender
			// requirement, which is what this plugin sends.
			'List-Unsubscribe: <' . esc_url_raw( $unsubscribe_url ) . '>',
			'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
		);

		$sent = wp_mail( $entry->email, $subject, $body, $headers );

		remove_filter( 'wp_mail_from_name', $set_from_name );
		remove_filter( 'wp_mail_from', $set_from_email );

		if ( ! $sent ) {
			do_action( 'wpwing_wl_after_send_restock_email', $entry, $product, false );
			// Log the failure but do not throw — throwing would abort the batch
			// loop and leave all subsequent entries unprocessed until AS retries.
			// The entry stays 'active' so it will be picked up on the next run or
			// when the merchant uses the admin "Resend" action.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WPWing Waitlist: wp_mail() failed for waitlist entry ' . (int) $entry->id );
			return false;
		}

		$table = Database::waitlists();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'status'      => 'notified',
				'notified_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $entry->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		do_action( 'wpwing_wl_after_send_restock_email', $entry, $product, true );

		return true;
	}

	/**
	 * AJAX handler — add the current user/guest to the product waitlist.
	 */
	public function ajax_join_waitlist(): void {
		check_ajax_referer( 'wpwing_wl_waitlist', 'nonce' );

		if ( ! Settings::is_waitlist_enabled() ) {
			wp_send_json_error( array( 'message' => __( 'Waitlist signups are currently disabled.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		// Honeypot: bots fill hidden fields, humans don't.
		if ( ! empty( $_POST['wpwing_hp'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Spam detected.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( (string) $_POST['email'] ) ) : '';
		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		// Best-effort rate limiting via transients. Two separate counters so a
		// single abuser can't burn through both budgets with the same payload.
		if ( ! $this->check_rate_limit( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many signup attempts. Please try again in a little while.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		if ( ! $product_id || ! wc_get_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		// If a variation is specified, validate it exists and belongs to the parent product.
		// An invalid variation_id would produce a stuck 'active' entry that never gets notified.
		if ( $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation || ! $variation->is_type( 'variation' ) || (int) $variation->get_parent_id() !== $product_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid product variation.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
			}
		}

		// Server-side stock check — use the variation if provided, otherwise the parent product.
		$stock_product = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
		if ( $stock_product && $stock_product->is_in_stock() ) {
			wp_send_json_error( array( 'message' => __( 'This product is currently in stock.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table = Database::waitlists();

		// Look for any existing entry for this email+product+variation regardless of
		// status. If active → reject. If unsubscribed/notified → reactivate in place
		// rather than inserting a new row, keeping the table free of duplicates.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, status FROM %i WHERE email = %s AND product_id = %d AND variation_id = %d',
				$table,
				$email,
				$product_id,
				$variation_id
			)
		);

		if ( $existing && 'active' === $existing->status ) {
			wp_send_json_error( array( 'message' => __( "You're already on the waitlist for this product.", 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		$token   = bin2hex( random_bytes( 32 ) );
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		if ( $existing ) {
			// Reactivate the existing row — avoids duplicate unsubscribed entries.
			if ( $user_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE %i SET status = 'active', unsubscribe_token = %s, created_at = %s, notified_at = NULL, user_id = %d WHERE id = %d",
						$table,
						$token,
						current_time( 'mysql' ),
						$user_id,
						(int) $existing->id
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE %i SET status = 'active', unsubscribe_token = %s, created_at = %s, notified_at = NULL, user_id = NULL WHERE id = %d",
						$table,
						$token,
						current_time( 'mysql' ),
						(int) $existing->id
					)
				);
			}
		} else {
			// No prior entry — insert a fresh row.
			$data   = array(
				'product_id'        => $product_id,
				'variation_id'      => $variation_id,
				'email'             => $email,
				'status'            => 'active',
				'unsubscribe_token' => $token,
				'created_at'        => current_time( 'mysql' ),
			);
			$format = array( '%d', '%d', '%s', '%s', '%s', '%s' );

			if ( $user_id ) {
				$data['user_id'] = $user_id;
				$format[]        = '%d';
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert( $table, $data, $format );

			if ( ! $inserted ) {
				wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
			}
		}

		// For guest signups, set a cookie containing the unsubscribe token so
		// PHP can verify the entry is still active on page reload. If an admin
		// later deletes the row the DB lookup will fail, PHP clears the stale
		// cookie, and the form reappears — localStorage alone cannot do this
		// because it has no server-side visibility.
		if ( ! $user_id ) {
			wc_setcookie(
				'wpwing_wl_wj_' . $product_id . '_' . $variation_id,
				$token,
				time() + YEAR_IN_SECONDS
			);
		}

		wp_send_json_success( array( 'message' => __( "You're on the waitlist! We'll email you when this product is back in stock.", 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
	}

	/**
	 * AJAX handler — remove the current user/guest from the product waitlist.
	 *
	 * Logged-in users are identified by their user_id. Guests are identified by
	 * the unsubscribe token stored in the PHP cookie set during join — the cookie
	 * is sent automatically with same-origin AJAX requests, so no token needs to
	 * be passed explicitly from JS.
	 */
	public function ajax_leave_waitlist(): void {
		check_ajax_referer( 'wpwing_wl_waitlist', 'nonce' );

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table = Database::waitlists();

		if ( is_user_logged_in() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update(
				$table,
				array( 'status' => 'unsubscribed' ),
				array(
					'user_id'      => get_current_user_id(),
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'status'       => 'active',
				),
				array( '%s' ),
				array( '%d', '%d', '%d', '%s' )
			);
		} else {
			$cookie_key = 'wpwing_wl_wj_' . $product_id . '_' . $variation_id;
			$token      = isset( $_COOKIE[ $cookie_key ] )
				? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) )
				: '';

			if ( ! $token ) {
				wp_send_json_error( array( 'message' => __( "You're not on the waitlist for this product.", 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update(
				$table,
				array( 'status' => 'unsubscribed' ),
				array(
					'unsubscribe_token' => $token,
					'status'            => 'active',
				),
				array( '%s' ),
				array( '%s', '%s' )
			);

			// Expire the join cookie so the form reappears on refresh.
			wc_setcookie( $cookie_key, '', time() - HOUR_IN_SECONDS );
		}

		if ( false === $updated ) {
			wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
		}

		wp_send_json_success( array( 'message' => __( "You've been removed from the waitlist.", 'wpwing-wishlist-waitlist-for-woocommerce' ) ) );
	}

	/**
	 * Best-effort per-IP and per-email signup throttle. Uses 1-hour transients
	 * and bumps the counters before the signup work so failed validations are
	 * still counted against the budget — that's the desired behaviour against
	 * scripted abuse.
	 *
	 * Two separate transients are used because transient writes are not atomic;
	 * a tiny race here just means an attacker squeaks one extra request past
	 * the limit, which is acceptable for what this is.
	 *
	 * @param string $email Sanitised email already validated via is_email().
	 * @return bool True if the request may proceed, false if it should be rejected.
	 */
	private function check_rate_limit( string $email ): bool {
		$ip = $this->get_client_ip();

		$ip_key    = 'wpwing_wl_rl_ip_' . md5( $ip );
		$email_key = 'wpwing_wl_rl_em_' . md5( strtolower( $email ) );

		$ip_count    = (int) get_transient( $ip_key );
		$email_count = (int) get_transient( $email_key );

		if ( $ip_count >= self::IP_LIMIT_PER_HOUR || $email_count >= self::EMAIL_LIMIT_PER_HOUR ) {
			return false;
		}

		set_transient( $ip_key, $ip_count + 1, HOUR_IN_SECONDS );
		set_transient( $email_key, $email_count + 1, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Best-effort client IP. Returns a string suitable for hashing into a
	 * transient key — empty REMOTE_ADDR falls back to a sentinel so the limit
	 * still applies to that bucket of unknown callers.
	 */
	private function get_client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		return $ip ? $ip : 'unknown';
	}
}
