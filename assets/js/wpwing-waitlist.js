/**
 * Waitlist frontend — AJAX form submission and variation-aware show/hide.
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

	$(
		function () {
			var $container = $( '.wpwing-waitlist-form' );
			var $form      = $container.find( '.wpwing-waitlist-fields' );

			if ( ! $form.length ) {
				return;
			}

			var productId = $container.data( 'product-id' );

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
					var $message    = $container.find( '.wpwing-waitlist-message' );
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
							$message
							.text( res.data.message )
							.attr( 'class', 'wpwing-waitlist-message ' + ( res.success ? 'wpwing-wl-success' : 'wpwing-wl-error' ) );

							if ( res.success ) {
								// Persist so the form stays hidden on refresh.
								markJoined( productId, variationId );
								// Hide only the intro and form — keep the container
								// visible so the success message remains readable.
								$container.find( '.wpwing-waitlist-intro' ).hide();
								$form.hide();
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

			// --- Variable product: show/hide per selected variation ---
			$( '.variations_form' )
			.on(
				'found_variation',
				function ( e, variation ) {
					var vid = variation.variation_id || 0;
					$form.find( '.wpwing-variation-id' ).val( vid );

					if ( variation.is_in_stock || isJoined( productId, vid ) ) {
						$container.addClass( 'wpwing-wl-hidden' );
					} else {
						$container.removeClass( 'wpwing-wl-hidden' );
					}
				}
			)
			.on(
				'reset_data',
				function () {
					$container.addClass( 'wpwing-wl-hidden' );
					$form.find( '.wpwing-variation-id' ).val( 0 );
				}
			);
		}
	);
}( jQuery ) );
