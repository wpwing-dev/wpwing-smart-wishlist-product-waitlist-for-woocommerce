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

	$(
		function () {
			var $container = $( '.wpwing-waitlist-form' );
			var $form      = $container.find( '.wpwing-waitlist-fields' );

			if ( ! $form.length ) {
					return;
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

					var $button  = $form.find( '.wpwing-waitlist-submit' );
					var $message = $container.find( '.wpwing-waitlist-message' );

					$button.prop( 'disabled', true );

					$.post(
						wpwingWl.ajaxUrl,
						{
							action       : 'wpwing_wl_join_waitlist',
							nonce        : wpwingWl.waitlistNonce,
							email        : $form.find( '[name="email"]' ).val(),
							product_id   : $form.find( '[name="product_id"]' ).val(),
							variation_id : $form.find( '[name="variation_id"]' ).val(),
							wpwing_hp    : $form.find( '[name="wpwing_hp"]' ).val(),
						},
						function ( res ) {
							$message
							.text( res.data.message )
							.attr( 'class', 'wpwing-waitlist-message ' + ( res.success ? 'wpwing-wl-success' : 'wpwing-wl-error' ) );

							if ( res.success ) {
								$form.hide();
							}
						}
					).fail(
						function () {
							$message
							.text( 'An error occurred. Please try again.' )
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
					$form.find( '.wpwing-variation-id' ).val( variation.variation_id || 0 );

					if ( variation.is_in_stock ) {
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
