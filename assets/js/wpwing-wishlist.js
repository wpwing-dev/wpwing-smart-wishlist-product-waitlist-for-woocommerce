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
								$btn.text( res.data.label )
								.data( 'in-wishlist', 'added' === res.data.action ? 1 : 0 )
								.prop( 'disabled', false );
							}
						}
					).fail(
						function () {
							$btn.prop( 'disabled', false );
						}
					);
				}
			);

		}
	);
}( jQuery ) );
