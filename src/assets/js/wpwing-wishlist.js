/**
 * Wishlist frontend — AJAX toggle for product page button and shortcode remove button.
 *
 * Reads window.wpwingWl.ajaxUrl, window.wpwingWl.wishlistNonce,
 * and window.wpwingWl.emptyWishlist (injected by Assets::enqueue).
 *
 * @package WPWing\WishlistWaitlist
 */

/* global wpwingWl */
( function ( $ ) {
	'use strict';

	$(
		function () {

			// Event delegation covers both the product page button and shortcode remove buttons.
			$( document ).on(
				'click',
				'.wpwing-wishlist-toggle',
				function () {
					var $btn = $( this );
					var $row = $btn.closest( '.wpwing-wishlist-row' );

					$btn.prop( 'disabled', true );

					$.post(
						wpwingWl.ajaxUrl,
						{
							action       : 'wpwing_wl_wishlist_toggle',
							nonce        : wpwingWl.wishlistNonce,
							product_id   : $btn.data( 'product-id' ),
							variation_id : $btn.data( 'variation-id' ) || 0,
						},
						function ( res ) {
							if ( ! res.success ) {
								$btn.prop( 'disabled', false );
								return;
							}

							if ( $row.length ) {
								// Shortcode view — remove the row; replace table with empty message if needed.
								var $tbody = $row.closest( 'tbody' );
								$row.fadeOut(
									300,
									function () {
										$( this ).remove();

										if ( 0 === $tbody.find( 'tr' ).length ) {
												$tbody.closest( 'table' ).replaceWith(
													'<p class="wpwing-wishlist-empty">' + wpwingWl.emptyWishlist + '</p>'
												);
										}
									}
								);
							} else {
								// Product page — update button label and state.
								var inWishlist = 'added' === res.data.action ? 1 : 0;
								$btn.text( res.data.label )
								.attr( 'aria-label', res.data.label )
								.attr( 'aria-pressed', inWishlist ? 'true' : 'false' )
								.attr( 'data-in-wishlist', inWishlist )
								.prop( 'disabled', false );
							}

							// Always sync every count badge on the page (nav menu, shortcode, header widget).
							$( '.wpwing-wishlist-count' ).text( res.data.count );
						}
					).fail(
						function () {
							$btn.prop( 'disabled', false );
							var $err = $( '<span class="wpwing-wishlist-error"></span>' ).text( wpwingWl.networkError );
							$btn.after( $err );
							setTimeout(
								function () {
									$err.remove(); },
								4000
							);
						}
					);
				}
			);

			// Variable products: sync data-variation-id with the selected variation and
			// fetch the real wishlist state for that variation via a lightweight AJAX check.
			var $wishlistBtn = $( '.wpwing-wishlist-toggle' );
			var initialLabel = $wishlistBtn.attr( 'aria-label' );
			var initialState = 'true' === $wishlistBtn.attr( 'aria-pressed' );

			function updateWishlistButton( label, inWishlist ) {
				$wishlistBtn
					.text( label )
					.attr( 'aria-label', label )
					.attr( 'aria-pressed', inWishlist ? 'true' : 'false' )
					.attr( 'data-in-wishlist', inWishlist ? 1 : 0 );
			}

			$( '.variations_form' )
			.on(
				'found_variation',
				function ( e, variation ) {
					var newVariationId = variation.variation_id || 0;

					$wishlistBtn
						.data( 'variation-id', newVariationId )
						.attr( 'data-variation-id', newVariationId );

					$.post(
						wpwingWl.ajaxUrl,
						{
							action       : 'wpwing_wl_wishlist_check',
							nonce        : wpwingWl.wishlistNonce,
							product_id   : $wishlistBtn.data( 'product-id' ),
							variation_id : newVariationId,
						},
						function ( res ) {
							if ( res.success ) {
								updateWishlistButton( res.data.label, res.data.in_wishlist );
							}
						}
					);
				}
			)
			.on(
				'reset_data',
				function () {
					$wishlistBtn
						.data( 'variation-id', 0 )
						.attr( 'data-variation-id', 0 );
					updateWishlistButton( initialLabel, initialState );
				}
			);

			// AJAX add-to-cart (shop/archive loop, and AJAX-enabled single product themes)
			// auto-removes the item server-side; sync the matching button(s) and count badge.
			$( document.body ).on(
				'added_to_cart',
				function ( e, fragments, cartHash, $button ) {
					var productId = $button && $button.data ? $button.data( 'product_id' ) : 0;

					if ( ! productId ) {
						return;
					}

					$( '.wpwing-wishlist-toggle[data-product-id="' + productId + '"]' ).each(
						function () {
							var $toggle = $( this );

							$.post(
								wpwingWl.ajaxUrl,
								{
									action       : 'wpwing_wl_wishlist_check',
									nonce        : wpwingWl.wishlistNonce,
									product_id   : productId,
									variation_id : $toggle.data( 'variation-id' ) || 0,
								},
								function ( res ) {
									if ( ! res.success ) {
										return;
									}

									$toggle
										.text( res.data.label )
										.attr( 'aria-label', res.data.label )
										.attr( 'aria-pressed', res.data.in_wishlist ? 'true' : 'false' )
										.attr( 'data-in-wishlist', res.data.in_wishlist ? 1 : 0 );

									$( '.wpwing-wishlist-count' ).text( res.data.count );
								}
							);
						}
					);
				}
			);

			// Copy-link share button: clipboard API where available, with an
			// execCommand fallback for browsers/contexts without it.
			$( document ).on(
				'click',
				'.wpwing-wishlist-share-copy',
				function () {
					var $btn = $( this );
					var url  = $btn.data( 'share-url' );

					function showCopied() {
						var original = $btn.text();
						$btn.text( $btn.data( 'copied-label' ) || original );
						setTimeout(
							function () {
								$btn.text( original );
							},
							2000
						);
					}

					if ( navigator.clipboard && navigator.clipboard.writeText ) {
						navigator.clipboard.writeText( url ).then( showCopied );
						return;
					}

					var $temp = $( '<textarea readonly></textarea>' )
						.css( { position: 'fixed', top: '-1000px' } )
						.val( url )
						.appendTo( 'body' );
					$temp[ 0 ].select();
					document.execCommand( 'copy' );
					$temp.remove();
					showCopied();
				}
			);

		}
	);
}( jQuery ) );
