/**
 * Waitlist frontend — AJAX form submission, leave, and variation-aware show/hide.
 *
 * Reads window.wpwingWl.ajaxUrl and window.wpwingWl.waitlistNonce (injected
 * by Assets::enqueue via wp_localize_script).
 *
 * @package WPWing\WishlistWaitlist
 */

/* global wpwingWl */
( function ( $ ) {
	'use strict';

	// --- localStorage helpers (guest + cross-refresh persistence) ---

	function storageKey( productId, variationId ) {
		return 'wpwing_wl_joined_' + productId + '_' + variationId;
	}

	function isJoined( productId, variationId ) {
		try {
			return '1' === localStorage.getItem( storageKey( productId, variationId ) );
		} catch ( e ) {
			return false;
		}
	}

	function markJoined( productId, variationId ) {
		try {
			localStorage.setItem( storageKey( productId, variationId ), '1' );
		} catch ( e ) {
		}
	}

	function clearJoined( productId, variationId ) {
		try {
			localStorage.removeItem( storageKey( productId, variationId ) );
		} catch ( e ) {
		}
	}

	$(
		function () {
			var $container = $( '.wpwing-waitlist-form' );
			var $form      = $container.find( '.wpwing-waitlist-fields' );
			var $joined    = $container.find( '.wpwing-waitlist-joined' );
			var $message   = $container.find( '.wpwing-waitlist-message' );

			if ( ! $form.length ) {
				return;
			}

			var productId = $container.data( 'product-id' );

			function showFormState() {
				$container.find( '.wpwing-waitlist-intro' ).show();
				$form.show();
				$joined.hide();
				$joined.find( '.wpwing-waitlist-leave-message' )
					.text( '' )
					.attr( 'class', 'wpwing-waitlist-leave-message' );
				$message.text( '' ).attr( 'class', 'wpwing-waitlist-message' );
			}

			function showJoinedState() {
				$container.find( '.wpwing-waitlist-intro' ).hide();
				$form.hide();
				$joined.show();
				$message.text( '' ).attr( 'class', 'wpwing-waitlist-message' );
			}

			// --- AJAX form submission ---
			$form.on(
				'submit',
				function ( e ) {
					e.preventDefault();

					// Client-side honeypot reinforcement.
					if ( $( '[name="wpwing_hp"]', $form ).val() ) {
						return;
					}

					var $button     = $form.find( '.wpwing-waitlist-submit' );
					var variationId = $form.find( '.wpwing-variation-id' ).val() || '0';

					$button.prop( 'disabled', true );

					$.post(
						wpwingWl.ajaxUrl,
						{
							action       : 'wpwing_wl_join_waitlist',
							nonce        : wpwingWl.waitlistNonce,
							email        : $form.find( '[name="email"]' ).val(),
							product_id   : $form.find( '[name="product_id"]' ).val(),
							variation_id : variationId,
							wpwing_hp    : $form.find( '[name="wpwing_hp"]' ).val(),
						},
						function ( res ) {
							if ( res.success ) {
								markJoined( productId, variationId );
								showJoinedState();
							} else {
								$message
									.text( res.data.message )
									.attr( 'class', 'wpwing-waitlist-message wpwing-wl-error' );
							}
						}
					).fail(
						function () {
							$message
								.text( wpwingWl.networkError )
								.attr( 'class', 'wpwing-waitlist-message wpwing-wl-error' );
						}
					).always(
						function () {
							$button.prop( 'disabled', false );
						}
					);
				}
			);

			// --- Remove from waitlist ---
			$joined.find( '.wpwing-waitlist-leave' ).on(
				'click',
				function () {
					var $btn      = $( this );
					var $leaveMsg = $joined.find( '.wpwing-waitlist-leave-message' );
					// For variable products, read the currently selected variation.
					// For simple products the hidden input always holds "0".
					var variationId = $form.find( '.wpwing-variation-id' ).val() || '0';

					$btn.prop( 'disabled', true );

					$.post(
						wpwingWl.ajaxUrl,
						{
							action       : 'wpwing_wl_leave_waitlist',
							nonce        : wpwingWl.waitlistNonce,
							product_id   : productId,
							variation_id : variationId,
						},
						function ( res ) {
							if ( res.success ) {
								clearJoined( productId, variationId );
								showFormState();
								$message
									.text( res.data.message )
									.attr( 'class', 'wpwing-waitlist-message wpwing-wl-success' );
							} else {
								$leaveMsg
									.text( res.data.message )
									.attr( 'class', 'wpwing-waitlist-leave-message wpwing-wl-error' );
								$btn.prop( 'disabled', false );
							}
						}
					).fail(
						function () {
							$leaveMsg
								.text( wpwingWl.networkError )
								.attr( 'class', 'wpwing-waitlist-leave-message wpwing-wl-error' );
							$btn.prop( 'disabled', false );
						}
					);
				}
			);

			// --- Simple product: restore joined state from localStorage on page load ---
			// PHP handles initial state via cookie/DB, but localStorage provides a
			// client-side fallback (e.g. page cache serving stale HTML to a guest).
			if ( ! $container.hasClass( 'wpwing-wl-hidden' ) && isJoined( productId, '0' ) ) {
				showJoinedState();
			}

			// --- Waitlist shortcode view: "Leave Waitlist" row buttons ---
			$( document ).on( 'click', '.wpwing-waitlist-view-leave', function () {
				var $btn        = $( this );
				var $row        = $btn.closest( '.wpwing-waitlist-row' );
				var $table      = $btn.closest( '.wpwing-waitlist-table' );
				var productId   = $btn.data( 'product-id' );
				var variationId = $btn.data( 'variation-id' ) || '0';
				var $msg        = $btn.siblings( '.wpwing-waitlist-leave-message' );

				$btn.prop( 'disabled', true );

				$.post(
					wpwingWl.ajaxUrl,
					{
						action       : 'wpwing_wl_leave_waitlist',
						nonce        : wpwingWl.waitlistNonce,
						product_id   : productId,
						variation_id : variationId,
					},
					function ( res ) {
						if ( res.success ) {
							clearJoined( productId, variationId );
							$row.fadeOut(
								400,
								function () {
									$( this ).remove();
									if ( ! $table.find( '.wpwing-waitlist-row' ).length ) {
										$table.replaceWith( '<p class="wpwing-waitlist-empty">' + wpwingWl.emptyWaitlist + '</p>' );
									}
								}
							);
						} else {
							$msg.text( res.data.message ).attr( 'class', 'wpwing-waitlist-leave-message wpwing-wl-error' );
							$btn.prop( 'disabled', false );
						}
					}
				).fail(
					function () {
						$msg.text( wpwingWl.networkError ).attr( 'class', 'wpwing-waitlist-leave-message wpwing-wl-error' );
						$btn.prop( 'disabled', false );
					}
				);
			} );

			// --- Variable product: show/hide per selected variation ---
			$( '.variations_form' )
			.on(
				'found_variation',
				function ( e, variation ) {
					var vid = variation.variation_id || 0;
					$form.find( '.wpwing-variation-id' ).val( vid );

					if ( variation.is_in_stock ) {
						$container.addClass( 'wpwing-wl-hidden' );
					} else if ( isJoined( productId, vid ) ) {
						$container.removeClass( 'wpwing-wl-hidden' );
						showJoinedState();
					} else {
						$container.removeClass( 'wpwing-wl-hidden' );
						showFormState();
					}
				}
			)
			.on(
				'reset_data',
				function () {
					$container.addClass( 'wpwing-wl-hidden' );
					$form.find( '.wpwing-variation-id' ).val( 0 );
					showFormState();
				}
			);
		}
	);
}( jQuery ) );
