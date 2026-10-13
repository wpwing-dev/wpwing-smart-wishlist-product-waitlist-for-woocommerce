/**
 * Waitlist frontend — AJAX form submission, leave, and variation-aware show/hide.
 *
 * Reads window.wpwingWl.ajaxUrl and window.wpwingWl.waitlistNonce (injected
 * by Assets::enqueue via wp_localize_script).
 *
 * @package WPWing\WishlistWaitlist
 */

/* global wpwingWl, grecaptcha, turnstile */
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
			// --- Waitlist shortcode view: "Leave Waitlist" row buttons ---
			// Registered here, before any early returns, so it works on pages that
			// only have the [wpwing_waitlist] shortcode with no product form present.
			$( document ).on(
				'click',
				'.wpwing-waitlist-view-leave',
				function () {
					var $btn        = $( this );
					var $row        = $btn.closest( '.wpwing-waitlist-row' );
					var $table      = $btn.closest( '.wpwing-waitlist-table' );
					var productId   = $btn.data( 'product-id' );
					var variationId = $btn.data( 'variation-id' ) || '0';
					var $msg        = $btn.closest( 'td' ).find( '.wpwing-waitlist-leave-message' );

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
				}
			);

			var $container    = $( '.wpwing-waitlist-form' );
			var $form         = $container.find( '.wpwing-waitlist-fields' );
			var $joined       = $container.find( '.wpwing-waitlist-joined' );
			var $message      = $container.find( '.wpwing-waitlist-message' );
			var isPopupPreset = $container.hasClass( 'wpwing-form--preset-popup' );
			var $modalOverlay = $container.find( '.wpwing-waitlist-modal-overlay' );
			var $modalTrigger = $container.find( '.wpwing-waitlist-modal-open' );
			var lastFocusedEl = null;

			if ( ! $form.length ) {
				return;
			}

			var productId = $container.data( 'product-id' );

			function openModal() {
				lastFocusedEl = document.activeElement;
				$modalOverlay.removeClass( 'wpwing-wl-hidden' );
				$modalTrigger.attr( 'aria-expanded', 'true' );
				var $input = $modalOverlay.find( '.wpwing-waitlist-email' );
				if ( $input.length ) {
					$input.trigger( 'focus' );
				}
			}

			function closeModal() {
				$modalOverlay.addClass( 'wpwing-wl-hidden' );
				$modalTrigger.attr( 'aria-expanded', 'false' );
				if ( lastFocusedEl && typeof lastFocusedEl.focus === 'function' ) {
					lastFocusedEl.focus();
				}
			}

			if ( isPopupPreset ) {
				$container.on(
					'click',
					'.wpwing-waitlist-modal-open',
					function ( e ) {
						e.preventDefault();
						openModal();
					}
				);

				$container.on(
					'click',
					'.wpwing-waitlist-modal-close',
					function ( e ) {
						e.preventDefault();
						closeModal();
					}
				);

				$modalOverlay.on(
					'click',
					function ( e ) {
						if ( $( e.target ).is( $modalOverlay ) ) {
							closeModal();
						}
					}
				);

				$( document ).on(
					'keydown',
					function ( e ) {
						if ( 27 === e.keyCode && ! $modalOverlay.hasClass( 'wpwing-wl-hidden' ) ) {
							closeModal();
						}
					}
				);
			}

			function showFormState() {
				$container.find( '.wpwing-waitlist-popup-trigger' ).show();
				$container.find( '.wpwing-waitlist-intro' ).show();
				$form.show();
				$joined.hide();
				$joined.find( '.wpwing-waitlist-leave-message' )
					.text( '' )
					.attr( 'class', 'wpwing-waitlist-leave-message' );
				$message.text( '' ).attr( 'class', 'wpwing-waitlist-message' );
			}

			function showJoinedState() {
				$container.find( '.wpwing-waitlist-popup-trigger' ).hide();
				$container.find( '.wpwing-waitlist-intro' ).hide();
				$form.hide();
				$joined.show();
				$message.text( '' ).attr( 'class', 'wpwing-waitlist-message' );
			}

			var turnstileWidgetId = null;

			function initTurnstile() {
				if ( ! wpwingWl.captcha || ! wpwingWl.captcha.enabled || wpwingWl.captcha.provider !== 'turnstile' ) {
					return;
				}
				var $turnstileContainer = $form.find( '.wpwing-turnstile-container' );
				if ( ! $turnstileContainer.length || typeof window.turnstile === 'undefined' ) {
					return;
				}
				if ( turnstileWidgetId === null ) {
					try {
						turnstileWidgetId = window.turnstile.render(
							$turnstileContainer[0],
							{
								sitekey: wpwingWl.captcha.siteKey,
								theme: 'auto',
							}
						);
					} catch ( e ) {
						// Turnstile render fallback.
					}
				}
			}

			if ( wpwingWl.captcha && wpwingWl.captcha.enabled && wpwingWl.captcha.provider === 'turnstile' ) {
				if ( typeof window.turnstile !== 'undefined' ) {
					initTurnstile();
				} else {
					var turnstileCheckInterval = setInterval(
						function () {
							if ( typeof window.turnstile !== 'undefined' ) {
								clearInterval( turnstileCheckInterval );
								initTurnstile();
							}
						},
						200
					);
					setTimeout(
						function () {
							clearInterval( turnstileCheckInterval );
						},
						5000
					);
				}
			}

			function getCaptchaToken( callback ) {
				if ( ! wpwingWl.captcha || ! wpwingWl.captcha.enabled || wpwingWl.captcha.provider === 'none' ) {
					callback( '' );
					return;
				}

				if ( wpwingWl.captcha.provider === 'recaptcha_v3' ) {
					if ( typeof window.grecaptcha !== 'undefined' && window.grecaptcha.execute ) {
						window.grecaptcha.ready(
							function () {
								window.grecaptcha.execute( wpwingWl.captcha.siteKey, { action: 'wpwing_waitlist' } )
									.then(
										function ( token ) {
											callback( token );
										}
									)
									.catch(
										function () {
											callback( '' );
										}
									);
							}
						);
						return;
					}
					callback( '' );
					return;
				}

				if ( wpwingWl.captcha.provider === 'turnstile' ) {
					if ( typeof window.turnstile !== 'undefined' && turnstileWidgetId !== null ) {
						var token = window.turnstile.getResponse( turnstileWidgetId );
						callback( token || '' );
						return;
					}
					callback( '' );
					return;
				}

				callback( '' );
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

					getCaptchaToken(
						function ( captchaToken ) {
							$.post(
								wpwingWl.ajaxUrl,
								{
									action        : 'wpwing_wl_join_waitlist',
									nonce         : wpwingWl.waitlistNonce,
									email         : $form.find( '[name="email"]' ).val(),
									product_id    : $form.find( '[name="product_id"]' ).val(),
									variation_id  : variationId,
									wpwing_hp     : $form.find( '[name="wpwing_hp"]' ).val(),
									captcha_token : captchaToken,
								},
								function ( res ) {
									if ( res.success ) {
										markJoined( productId, variationId );
										showJoinedState();
										if ( isPopupPreset ) {
											setTimeout( closeModal, 1200 );
										}
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
									if ( typeof window.turnstile !== 'undefined' && turnstileWidgetId !== null ) {
										try {
											window.turnstile.reset( turnstileWidgetId );
										} catch ( err ) {
											// Turnstile reset error ignored.
										}
									}
								}
							);
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

			// --- Variable product: show/hide per selected variation ---
			$( '.variations_form' )
			.on(
				'found_variation',
				function ( e, variation ) {
					var vid = variation.variation_id || 0;
					$form.find( '.wpwing-variation-id' ).val( vid );

					var stockStatus     = variation.stock_status || ( variation.is_in_stock ? 'instock' : 'outofstock' );
					var allowedStatuses = ( wpwingWl && wpwingWl.allowedStockStatuses ) ? wpwingWl.allowedStockStatuses : [ 'outofstock' ];
					var isAllowed       = allowedStatuses.indexOf( stockStatus ) !== -1;

					if ( ! isAllowed ) {
						$container.addClass( 'wpwing-wl-hidden' );
						if ( isPopupPreset ) {
							closeModal();
						}
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
					if ( isPopupPreset ) {
						closeModal();
					}
				}
			);
		}
	);
}( jQuery ) );
