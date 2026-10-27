/**
 * Wishlist frontend — AJAX toggle, quantity update, bulk add to cart, and clear wishlist.
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

			function updateButtonState( $button, label, icon, inWishlist ) {
				if ( $button.hasClass( 'wpwing-wishlist-style-icon' ) && icon ) {
					$button.html( '<span class="wpwing-wishlist-icon" aria-hidden="true">' + icon + '</span><span class="wpwing-sr-only">' + label + '</span>' );
				} else {
					$button.text( label );
				}
				$button
					.attr( 'aria-label', label )
					.attr( 'aria-pressed', inWishlist ? 'true' : 'false' )
					.attr( 'data-in-wishlist', inWishlist ? 1 : 0 )
					.prop( 'disabled', false );
			}

			function updateWishlistTotal() {
				var total = 0;
				$( '.wpwing-wishlist-row' ).each(
					function () {
						var $r    = $( this );
						var price = parseFloat( $r.data( 'price' ) ) || 0;
						var qty   = parseInt( $r.find( '.wpwing-wishlist-item-qty' ).val(), 10 ) || 1;
						total    += price * qty;
					}
				);
				var formatted    = total.toFixed( 2 );
				var $totalAmount = $( '.wpwing-wishlist-total-amount' );
				if ( $totalAmount.length ) {
					var $bdi = $totalAmount.find( 'bdi' );
					if ( $bdi.length ) {
						var $symbol = $bdi.find( '.woocommerce-Price-currencySymbol' );
						var symHtml = $symbol.length ? $symbol[ 0 ].outerHTML : ( wpwingWl.currencySymbol || '$' );
						$bdi.html( symHtml + formatted );
					} else {
						$totalAmount.text( ( wpwingWl.currencySymbol || '$' ) + formatted );
					}
				}
			}

			function updateBulkButtonText() {
				var checkedCount = $( '.wpwing-wishlist-select-item:checked' ).length;
				var $bulkBtn     = $( '.wpwing-wishlist-bulk-add-to-cart' );
				if ( checkedCount > 0 ) {
					$bulkBtn.text( ( wpwingWl.addSelectedToCart || 'Add selected to cart' ) + ' (' + checkedCount + ')' );
				} else {
					$bulkBtn.text( wpwingWl.addAllToCart || 'Add all to cart' );
				}
			}

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
											$( '.wpwing-wishlist-bulk-actions' ).remove();
											$tbody.closest( 'table' ).replaceWith(
												'<p class="wpwing-wishlist-empty">' + wpwingWl.emptyWishlist + '</p>'
											);
										} else {
											updateWishlistTotal();
											updateBulkButtonText();
										}
									}
								);
							} else {
								// Product page — update button label, icon, and state.
								var inWishlist = 'added' === res.data.action ? 1 : 0;
								updateButtonState( $btn, res.data.label, res.data.icon, inWishlist );
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
									$err.remove();
								},
								4000
							);
						}
					);
				}
			);

			// Quantity change handling.
			var quantityTimer = null;
			$( document ).on(
				'change input',
				'.wpwing-wishlist-item-qty',
				function () {
					var $input = $( this );
					var qty    = parseInt( $input.val(), 10 ) || 1;
					if ( qty < 1 ) {
						qty = 1;
						$input.val( 1 );
					}
					var $row       = $input.closest( '.wpwing-wishlist-row' );
					var $addToCart = $row.find( '.wpwing-wishlist-add-to-cart' );
					var origUrl    = $addToCart.data( 'original-url' ) || $addToCart.attr( 'href' );
					if ( origUrl && -1 === origUrl.indexOf( '#' ) ) {
						try {
							var urlObj = new URL( origUrl, window.location.href );
							urlObj.searchParams.set( 'quantity', qty );
							$addToCart.attr( 'href', urlObj.toString() );
						} catch ( e ) {
							// Fallback if URL parsing is not available.
							if ( origUrl.indexOf( 'quantity=' ) !== -1 ) {
								$addToCart.attr( 'href', origUrl.replace( /quantity=\d+/, 'quantity=' + qty ) );
							} else {
								var sep = origUrl.indexOf( '?' ) !== -1 ? '&' : '?';
								$addToCart.attr( 'href', origUrl + sep + 'quantity=' + qty );
							}
						}
					}

					updateWishlistTotal();

					clearTimeout( quantityTimer );
					quantityTimer = setTimeout(
						function () {
							$.post(
								wpwingWl.ajaxUrl,
								{
									action       : 'wpwing_wl_wishlist_update_quantity',
									nonce        : wpwingWl.wishlistNonce,
									product_id   : $input.data( 'product-id' ),
									variation_id : $input.data( 'variation-id' ) || 0,
									quantity     : qty,
								}
							);
						},
						400
					);
				}
			);

			// Bulk selection checkboxes.
			$( document ).on(
				'change',
				'.wpwing-wishlist-select-all',
				function () {
					var isChecked = $( this ).prop( 'checked' );
					$( '.wpwing-wishlist-select-item' ).prop( 'checked', isChecked );
					updateBulkButtonText();
				}
			);

			$( document ).on(
				'change',
				'.wpwing-wishlist-select-item',
				function () {
					var allCount     = $( '.wpwing-wishlist-select-item' ).length;
					var checkedCount = $( '.wpwing-wishlist-select-item:checked' ).length;
					$( '.wpwing-wishlist-select-all' ).prop( 'checked', allCount > 0 && allCount === checkedCount );
					updateBulkButtonText();
				}
			);

			// Bulk action — add all or selected to cart.
			$( document ).on(
				'click',
				'.wpwing-wishlist-bulk-add-to-cart',
				function () {
					var $btn        = $( this );
					var items       = [];
					var $checked    = $( '.wpwing-wishlist-select-item:checked' );
					var $targetRows = $checked.length ? $checked.closest( '.wpwing-wishlist-row' ) : $( '.wpwing-wishlist-row' );

					$targetRows.each(
						function () {
							var $r  = $( this );
							var qty = parseInt( $r.find( '.wpwing-wishlist-item-qty' ).val(), 10 ) || 1;
							items.push(
								{
									product_id   : $r.data( 'product-id' ),
									variation_id : $r.data( 'variation-id' ) || 0,
									quantity     : qty,
								}
							);
						}
					);

					if ( ! items.length ) {
						return;
					}

					$btn.prop( 'disabled', true ).text( wpwingWl.addingToCart || 'Adding to cart…' );

					$.post(
						wpwingWl.ajaxUrl,
						{
							action : 'wpwing_wl_wishlist_bulk_add_to_cart',
							nonce  : wpwingWl.wishlistNonce,
							items  : items,
						},
						function ( res ) {
							if ( res.success && res.data && res.data.cart_url ) {
								window.location.href = res.data.cart_url;
							} else {
								$btn.prop( 'disabled', false );
								updateBulkButtonText();
								alert( res.data && res.data.message ? res.data.message : wpwingWl.networkError );
							}
						}
					).fail(
						function () {
							$btn.prop( 'disabled', false );
							updateBulkButtonText();
							alert( wpwingWl.networkError );
						}
					);
				}
			);

			// Bulk action — clear wishlist.
			$( document ).on(
				'click',
				'.wpwing-wishlist-clear-all',
				function () {
					var $btn       = $( this );
					var confirmMsg = wpwingWl.confirmClearWishlist || 'Are you sure you want to clear your wishlist?';
					if ( ! window.confirm( confirmMsg ) ) {
						return;
					}

					$btn.prop( 'disabled', true ).text( wpwingWl.clearingWishlist || 'Clearing wishlist…' );

					$.post(
						wpwingWl.ajaxUrl,
						{
							action : 'wpwing_wl_wishlist_clear',
							nonce  : wpwingWl.wishlistNonce,
						},
						function ( res ) {
							if ( res.success ) {
								$( '.wpwing-wishlist-bulk-actions' ).remove();
								$( '.wpwing-wishlist-table' ).replaceWith(
									'<p class="wpwing-wishlist-empty">' + wpwingWl.emptyWishlist + '</p>'
								);
								$( '.wpwing-wishlist-count' ).text( '0' );
							} else {
								$btn.prop( 'disabled', false ).text( wpwingWl.clearWishlist || 'Clear wishlist' );
								alert( res.data && res.data.message ? res.data.message : wpwingWl.networkError );
							}
						}
					).fail(
						function () {
							$btn.prop( 'disabled', false );
							alert( wpwingWl.networkError );
						}
					);
				}
			);

			// Variable products: sync data-variation-id with the selected variation and
			// fetch the real wishlist state for that variation via a lightweight AJAX check.
			var $wishlistBtn = $( '.wpwing-wishlist-toggle' );
			var initialLabel = $wishlistBtn.attr( 'aria-label' );
			var initialState = 'true' === $wishlistBtn.attr( 'aria-pressed' );

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
								updateButtonState( $wishlistBtn, res.data.label, res.data.icon, res.data.in_wishlist );
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
					updateButtonState( $wishlistBtn, initialLabel, null, initialState );
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

									updateButtonState( $toggle, res.data.label, res.data.icon, res.data.in_wishlist );

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
