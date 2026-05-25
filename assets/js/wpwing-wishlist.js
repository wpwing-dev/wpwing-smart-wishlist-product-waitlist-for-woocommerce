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
								.data( 'in-wishlist', inWishlist )
								.prop( 'disabled', false );
							}

							// Always sync every count badge on the page (nav menu, shortcode, header widget).
							$( '.wpwing-wishlist-count' ).text( res.data.count );
						}
					).fail(
						function () {
							$btn.prop( 'disabled', false );
						}
					);
				}
			);

			// Variable products: sync data-variation-id with the selected variation and
			// fetch the real wishlist state for that variation via a lightweight AJAX check.
			var $wishlistBtn = $( '.wpwing-wishlist-toggle' );

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
								$wishlistBtn
									.text( res.data.label )
									.data( 'in-wishlist', res.data.in_wishlist ? 1 : 0 );
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
				}
			);

		}
	);
}( jQuery ) );
